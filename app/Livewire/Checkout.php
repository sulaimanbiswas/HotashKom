<?php

namespace App\Livewire;

use App\Http\Resources\ProductResource;
use App\Jobs\CallOnindaOrderApi;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\User\AccountCreated;
use App\Notifications\User\OrderPlaced;
use App\Services\DeliveryAreaService;
use App\Services\FacebookPixelService;
use App\Traits\ResolvesPackagingCharge;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

use function Illuminate\Support\defer;

class Checkout extends Component
{
    use ResolvesPackagingCharge;

    public ?Order $order = null;

    public $isFreeDelivery = false;

    public bool $productForcesFreeDelivery = false;

    public $name = '';

    public $phone = '';

    public $email = '';

    public $shipping = '';

    public $address = '';

    public $note = '';

    public $city_id = '';

    public $area_id = '';

    protected $listeners = ['updateField'];

    public array $retail = [];

    public $retailDeliveryFee = 0;

    #[Validate('required|numeric|min:0')]
    public $advanced = 0;

    #[Validate('nullable|numeric|min:0')]
    public $retailDiscount = 0;

    public $coupon_code = '';

    public $applied_coupon = null;

    public $coupon_discount = 0;

    /**
     * When true, `retailDeliveryFee` was explicitly set by the user and
     * should not be overridden by automatic shipping updates.
     */
    public bool $retailDeliveryFeeManuallySet = false;

    public string $fbp = '';

    public string $fbc = '';

    public string $eventSourceUrl = '';

    protected $facebookService;

    public function boot(FacebookPixelService $facebookService): void
    {
        $this->facebookService = $facebookService;
    }

    public function hydrate(): void
    {
        if (app()->environment('local') && request()->has('calls')) {
            logger()->debug('Checkout Livewire call queue', [
                'calls' => request()->input('calls'),
                'updates' => request()->input('updates'),
            ]);
        }
    }

    public function updateField($field, $value): void
    {
        if ($field === 'retail' && is_array($value)) {
            // Merge the retail array to preserve existing values
            $this->retail = array_merge($this->retail, $value);
        } else {
            $this->$field = $value;
            longCookie($field, $value);
        }

        // I don't know how, but it works.
        // $this->updatedShipping(); // doesn't work.
    }

    public function updatedCityId($value): void
    {
        longCookie('city_id', $value);

        // Reset area_id when city changes
        $this->area_id = '';
        longCookie('area_id', '');
    }

    public function updatedAreaId($value): void
    {
        longCookie('area_id', $value);
    }

    protected function refreshCouponDiscount(): void
    {
        if (! $this->applied_coupon) {
            $this->coupon_discount = 0;

            return;
        }

        if (! $this->applied_coupon->isValid()) {
            $this->removeCoupon();

            return;
        }

        $this->coupon_discount = $this->applied_coupon->calculateDiscount(cart()->subTotal());
        longCookie('coupon_discount', $this->coupon_discount);
    }

    public function updatedRetailDeliveryFee($value): void
    {
        // Mark the delivery fee as manually overridden so that subsequent
        // shipping updates (e.g. changing area or cart) don't reset it.
        $this->retailDeliveryFeeManuallySet = true;
    }

    public function applyCoupon(): void
    {
        $this->validate([
            'coupon_code' => 'required|string|min:1',
        ]);

        $coupon = Coupon::findByCode($this->coupon_code);

        if (! $coupon) {
            $this->addError('coupon_code', 'Invalid coupon code.');

            return;
        }

        if ($coupon->coupon_type !== 'purchase') {
            $this->addError('coupon_code', 'This coupon can only be used for subscription payments.');

            return;
        }

        if (! $coupon->isValid()) {
            $this->addError('coupon_code', 'Coupon has expired or reached its usage limit.');

            return;
        }

        $subtotal = cart()->subTotal();
        $discount = $coupon->calculateDiscount($subtotal);

        if ($discount <= 0) {
            $this->addError('coupon_code', 'This coupon does not apply to the current cart.');

            return;
        }

        $this->applied_coupon = $coupon;
        $this->coupon_discount = $discount;
        longCookie('coupon_code', $this->coupon_code);
        longCookie('coupon_discount', $discount);
        $this->resetErrorBag('coupon_code');

        session()->flash('message', 'Coupon applied successfully.');
    }

    public function removeCoupon(): void
    {
        $this->applied_coupon = null;
        $this->coupon_discount = 0;
        $this->coupon_code = '';
        longCookie('coupon_code', '');
        longCookie('coupon_discount', 0);
        $this->resetErrorBag('coupon_code');
    }

    public function updatedCouponCode($value): void
    {
        longCookie('coupon_code', $value);
    }

    protected function restoreCouponFromCookie(): void
    {
        if (! $this->coupon_code) {
            return;
        }

        $coupon = Coupon::findByCode($this->coupon_code);

        if (! $coupon || ! $coupon->isValid() || $coupon->coupon_type !== 'purchase') {
            $this->removeCoupon();

            return;
        }

        $this->applied_coupon = $coupon;
        $this->coupon_discount = $coupon->calculateDiscount(cart()->subTotal());
        longCookie('coupon_discount', $this->coupon_discount);
    }

    public function remove($id): void
    {
        cart()->remove($id);
        $this->cartUpdated();
    }

    public function increaseQuantity($id): void
    {
        $item = cart()->get($id);
        if ($item->qty < $item->options->max || $item->options->max === -1) {
            $qty = $item->qty + 1;
            $content = cart()->content();
            $product = Product::find($item->id);
            $item->price = $price = $product->getPrice($qty);
            if (! isOninda() || ! config('app.resell')) {
                $item->options['retail_price'] = $price;
            }
            $content->put($item->rowId, $item);
            // session()->put(cart()->currentInstance(), $content);

            cart()->update($id, $item->qty + 1);
            $this->cartUpdated();
        }
    }

    public function decreaseQuantity($id): void
    {
        $item = cart()->get($id);
        if ($item->qty > 1) {
            $qty = $item->qty - 1;
            $content = cart()->content();
            $product = Product::find($item->id);
            $item->price = $price = $product->getPrice($qty);
            if (! isOninda() || ! config('app.resell')) {
                $item->options['retail_price'] = $price;
            }
            $content->put($item->rowId, $item);
            // session()->put(cart()->currentInstance(), $content);

            cart()->update($id, $qty);
            $this->cartUpdated();
        }
    }

    /**
     * Return only cart items that should participate in delivery calculation.
     *
     * A product is excluded from delivery calculation when:
     * - the product itself has free_delivery enabled;
     * - its parent product has free_delivery enabled (variation support); or
     * - the cart item was added with landing_free_delivery enabled.
     *
     * IMPORTANT: free-delivery products are removed only from shipping
     * calculations. They remain in the cart/order normally.
     */
    private function chargeableCartItems()
    {
        $cartItems = cart()->content();

        if ($cartItems->isEmpty()) {
            return collect();
        }

        $productIds = $cartItems
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        $products = Product::query()
            ->with(['parent:id,free_delivery'])
            ->whereIn('id', $productIds)
            ->get(['id', 'parent_id', 'free_delivery'])
            ->keyBy('id');

        return $cartItems
            ->filter(function ($item) use ($products): bool {
                // Landing-page free delivery is item-level as well.
                if ((bool) ($item->options->landing_free_delivery ?? false)) {
                    return false;
                }

                $product = $products->get($item->id);

                // Safety fallback: if product cannot be loaded, do not
                // accidentally give free delivery.
                if (! $product) {
                    return true;
                }

                $hasFreeDelivery =
                    (bool) $product->free_delivery
                    || (bool) ($product->parent?->free_delivery ?? false);

                return ! $hasFreeDelivery;
            })
            ->values();
    }

    /**
     * Calculate delivery charge using ONLY chargeable products.
     *
     * Example:
     * - Free-delivery product: 5kg  -> excluded completely
     * - Normal product A: 1kg      -> included
     * - Normal product B: 1kg      -> included
     * - Base unit: 1kg
     * - Base delivery: 120
     * - Extra charge: 20/kg
     *
     * Chargeable weight = 2kg, so final delivery = 120 + 20 = 140.
     */
    public function shippingCost(?string $area = null)
    {
        $this->productForcesFreeDelivery = false;
        $this->isFreeDelivery = false;

        if (cart()->content()->isEmpty()) {
            return 0;
        }

        $chargeableItems = $this->chargeableCartItems();

        // Only when EVERY cart item is free-delivery should the whole
        // delivery fee become zero.
        if ($chargeableItems->isEmpty()) {
            $this->productForcesFreeDelivery = true;
            $this->isFreeDelivery = true;

            return 0;
        }

        $area ??= $this->shipping;

        // Free-delivery products must not help satisfy subtotal-based
        // delivery conditions either, so use only the chargeable subtotal.
        $chargeableSubtotal = $chargeableItems->sum(
            fn($item): float => (float) $item->price * (int) $item->qty
        );

        $isFree = false;

        // Base/area/product-specific delivery charge is calculated using
        // only the products that are not marked as free delivery.
        $shippingCost = (float) app(DeliveryAreaService::class)
            ->calculateShippingCost(
                $area,
                $chargeableItems,
                (float) $chargeableSubtotal,
                $isFree
            );

        $this->isFreeDelivery = (bool) $isFree;

        if ($isFree) {
            return 0;
        }

        // Advanced weight-based delivery charge.
        $advancedDelivery = (array) setting('advanced_delivery', []);

        if (
            ! empty($advancedDelivery)
            && ! empty($advancedDelivery['attribute_id'])
        ) {
            $attributeId = $advancedDelivery['attribute_id'];
            $baseUnit = max(0, (float) ($advancedDelivery['base_unit'] ?? 1));

            $normalizedArea = Str::lower((string) $area);

            $isInsideDhaka =
                Str::contains($normalizedArea, 'inside')
                || Str::contains($normalizedArea, 'ঢাকা');

            $extraChargePerUnit = $isInsideDhaka
                ? (float) ($advancedDelivery['extra_charge_inside'] ?? 0)
                : (float) ($advancedDelivery['extra_charge_outside'] ?? 0);

            if ($extraChargePerUnit > 0) {
                $chargeableProductIds = $chargeableItems
                    ->pluck('id')
                    ->filter()
                    ->unique()
                    ->values();

                // Load only chargeable products. Parent options are loaded as
                // a fallback for variations whose weight is defined on parent.
                $products = Product::query()
                    ->with(['options', 'parent.options'])
                    ->whereIn('id', $chargeableProductIds)
                    ->get()
                    ->keyBy('id');

                $totalWeight = 0.0;

                foreach ($chargeableItems as $item) {
                    $product = $products->get($item->id);

                    if (! $product) {
                        continue;
                    }

                    $option = $product->options
                        ->firstWhere('attribute_id', $attributeId);

                    // Variation fallback: use parent weight option if the
                    // variation itself does not have the configured attribute.
                    if (! $option && $product->parent) {
                        $option = $product->parent->options
                            ->firstWhere('attribute_id', $attributeId);
                    }

                    if (! $option) {
                        continue;
                    }

                    $weightValue = (float) filter_var(
                        (string) $option->name,
                        FILTER_SANITIZE_NUMBER_FLOAT,
                        FILTER_FLAG_ALLOW_FRACTION
                    );

                    if ($weightValue <= 0) {
                        continue;
                    }

                    $totalWeight += $weightValue * (int) $item->qty;
                }

                if ($totalWeight > $baseUnit) {
                    $extraWeight = ceil($totalWeight - $baseUnit);
                    $shippingCost += $extraWeight * $extraChargePerUnit;
                }
            }
        }

        return $shippingCost;
    }

    private function setDeliveryFee(float|int $fee): void
    {

        $current = (float) str_replace(
            ',',
            '',
            (string) (cart()->getCost('deliveryFee') ?: 0)
        );

        $fee = (float) $fee;

        $difference = $fee - $current;

        if (abs($difference) > 0.0001) {
            cart()->addCost('deliveryFee', $difference);
        }
    }

    public function updatedShipping(): void
    {
        $deliveryFee = (float) $this->shippingCost($this->shipping);

        $this->setDeliveryFee($deliveryFee);

        if ($this->productForcesFreeDelivery) {
            $this->retailDeliveryFee = 0;

            return;
        }

        if (
            isOninda()
            && config('app.resell')
            && auth('user')->check()
            && ! $this->retailDeliveryFeeManuallySet
        ) {
            /** @var User $reseller */
            $reseller = auth('user')->user();

            $this->retailDeliveryFee =
                $reseller->getShippingCost($this->shipping)
                ?: $deliveryFee;
        }
    }

    public function cartUpdated(): void
    {
        $this->updatedShipping();

        $this->retail = cart()->content()->mapWithKeys(
            fn($item): array => [
                (string) $item->id => [
                    'price' => $this->retail[(string) $item->id]['price']
                        ?? $item->options['retail_price']
                        ?? 0,

                    'quantity' => $item->qty,
                ],
            ]
        )->all();

        $this->refreshCouponDiscount();

        $this->dispatch('cartUpdated');
    }

    public function mount(): void
    {
        if (isOninda() && auth('user')->guest()) {
            $this->redirect(route('user.login'), navigate: true);
        }

        // if (!(setting('show_option')->hide_phone_prefix ?? false)) {
        //     $this->phone = '+880';
        // }

        $defaultArea = collect(setting('delivery_areas') ?? [])->first(fn($a) => (bool) data_get($a, 'is_default'));
        if ($defaultArea) {
            $shipping = data_get($defaultArea, 'name');
            if (! $this->retailDeliveryFeeManuallySet) {
                $this->retailDeliveryFee = $this->shippingCost($shipping);
            }
        }

        if ((! isOninda() || ! config('app.resell')) && $user = auth('user')->user()) {
            $this->name = $user->name;
            if ($user->phone_number) {
                $this->phone = Str::after($user->phone_number, '+880');
            }
            $this->email = $user->email ?? '';
            $this->address = $user->address ?? '';
            $this->note = $user->note ?? '';
            $this->retailDiscount = $user->discount ?? 0;
            $this->shipping = Cookie::get('shipping', $shipping ?? '');
        } elseif ($this->fillFromCookie()) {
            $this->name = Cookie::get('name', '');
            $this->shipping = Cookie::get('shipping', $shipping ?? '');
            $this->phone = Cookie::get('phone', '');
            $this->email = Cookie::get('email', '');
            $this->address = Cookie::get('address', '');
            $this->note = Cookie::get('note', '');
            $this->retailDiscount = Cookie::get('retail_discount', 0);
            $this->coupon_code = Cookie::get('coupon_code', '');
            $this->coupon_discount = Cookie::get('coupon_discount', 0);
            $this->city_id = Cookie::get('city_id', '');
            $this->area_id = Cookie::get('area_id', '');
        }

        $this->restoreCouponFromCookie();
        // Initialize retail array properly
        $this->cartUpdated();
    }

    public function checkout()
    {
        if (! ($hidePrefix = setting('show_option')->hide_phone_prefix ?? false)) {
            if (Str::startsWith($this->phone, '01')) {
                $this->phone = Str::after($this->phone, '0');
            } elseif (Str::startsWith($this->phone, '8801')) {
                $this->phone = Str::after($this->phone, '880');
            }
        } elseif (Str::startsWith($this->phone, '01')) { // hide prefix
            $this->phone = '+88' . $this->phone;
        } elseif (Str::startsWith($this->phone, '8801')) {
            $this->phone = '+' . $this->phone;
        }

        $validationRules = [
            'name' => 'required',
            'phone' => $hidePrefix ? 'required|regex:/^\+8801\d{9}$/' : 'required|regex:/^1\d{9}$/',
            'email' => 'nullable|email',
            'address' => 'required',
            'note' => 'nullable',
            'shipping' => 'required',
            'retailDiscount' => 'nullable|numeric|min:0',
        ];

        // Add validation for city and area if Pathao is enabled and user_selects_city_area is checked
        if ((setting('Pathao')->enabled ?? false) && (setting('Pathao')->user_selects_city_area ?? false) && (setting('Pathao')->user_required_city_area ?? false)) {
            $validationRules['city_id'] = 'required';
            $validationRules['area_id'] = 'required';
        }

        $data = $this->validate($validationRules);

        if (! $hidePrefix) {
            $data['phone'] = '+880' . $data['phone'];
        }

        throw_if(cart()->count() === 0, ValidationException::withMessages(['products' => 'Your cart is empty.']));

        $fraud = setting('fraud');

        if (
            cacheMemo()->get('fraud:hourly:' . request()->ip()) >= ($fraud->allow_per_hour ?? 3)
            || cacheMemo()->get('fraud:hourly:' . $data['phone']) >= ($fraud->allow_per_hour ?? 3)
            || cacheMemo()->get('fraud:daily:' . request()->ip()) >= ($fraud->allow_per_day ?? 7)
            || cacheMemo()->get('fraud:daily:' . $data['phone']) >= ($fraud->allow_per_day ?? 7)
        ) {
            return back()->with('error', 'প্রিয় গ্রাহক, আরও অর্ডার করতে চাইলে আমাদের হেল্প লাইন ' . setting('company')->phone . ' নাম্বারে কল দিয়ে সরাসরি কথা বলুন।');
        }

        $this->order = DB::transaction(function () use ($data, &$order, $fraud) {
            $data['products'] = Product::find(cart()->content()->pluck('id'))
                ->mapWithKeys(function (Product $product) use ($fraud) {
                    $id = $product->id;
                    $quantity = min(cart($id)->qty, $fraud->max_qty_per_product ?? 3);

                    if ($quantity <= 0) {
                        return null;
                    }

                    $productData = (new ProductResource($product))->toCartItem($quantity);
                    $productData['retail_price'] = $this->retail[$id]['price'] ?? $productData['price'];
                    $productData['landing_free_delivery'] = (bool) (cart($id)?->options?->landing_free_delivery ?? false);

                    return [$id => $productData];
                })->filter()->toArray();

            if (empty($data['products'])) {
                return $this->dispatch('notify', ['message' => 'All products are out of stock.', 'type' => 'danger']);
            }

            $user = $this->getUser($data);
            $oldOrders = $user->orders()->get();
            $status = $this->getDefaultStatus();

            $oldOrders = Order::select(['id', 'admin_id', 'status'])->where('phone', $data['phone'])->get();
            $adminIds = $oldOrders->pluck('admin_id')->unique()->toArray();

            if (config('app.round_robin_order_receiving')) {
                $adminQ = Admin::orderByRaw('CASE WHEN is_active = 1 THEN 0 ELSE 1 END, role_id desc, last_order_received_at asc');
                $admin = count($adminIds) > 0 ? $adminQ->whereIn('id', $adminIds)->first() ?? $adminQ->first() : $adminQ->first();
            } else {
                $adminQ = Admin::where('role_id', Admin::SALESMAN)->where('is_active', true)->inRandomOrder();
                if (count($adminIds) > 0) {
                    $admin = $adminQ->whereIn('id', $adminIds)->first() ?? $adminQ->first() ?? Admin::where('is_active', true)->inRandomOrder()->first();
                } else {
                    $admin = $adminQ->first() ?? Admin::where('is_active', true)->inRandomOrder()->first();
                }
            }

            $finalShippingCost = $this->shippingCost($data['shipping']);

            if ($this->productForcesFreeDelivery) {
                $this->retailDeliveryFee = 0;
            }


            $orderData = [
                'courier' => 'Other',

                'is_fraud' => $oldOrders
                    ->whereIn('status', ['CANCELLED', 'RETURNED', 'PAID_RETURN'])
                    ->count() > 0,

                'is_repeat' => $oldOrders->count() > 0,

                'shipping_area' => $data['shipping'],

                'shipping_cost' => $finalShippingCost,

                'retail_delivery_fee' => $this->productForcesFreeDelivery
                    ? 0
                    : $this->retailDeliveryFee,

                'advanced' => $this->advanced,
                'retail_discount' => $this->retailDiscount,
                'coupon_discount' => $this->coupon_discount,
                'coupon_id' => $this->applied_coupon?->id,
                'coupon_code' => $this->applied_coupon?->code,
                'subtotal' => cart()->subtotal(),

                'purchase_cost' => cart()->content()->sum(
                    fn($item): int|float => ($item->options->purchase_price ?: $item->options->price)
                        * $item->qty
                ),

                'packaging_charge' => $this->resolvePackagingCharge($data['products']),
            ];

            // Add city and area data if Pathao is enabled and user_selects_city_area is checked
            if ((setting('Pathao')->enabled ?? false) && (setting('Pathao')->user_selects_city_area ?? false)) {
                $orderData['city_id'] = $this->city_id;
                $orderData['area_id'] = $this->area_id;
                $orderData['courier'] = 'Pathao';
            }

            // Capture browser tracking signals at order time
            $fbp = $this->fbp;
            $fbc = $this->fbc;

            // Auto-build fbc from fbclid URL param if cookie was absent
            if (empty($fbc) && request()->has('fbclid')) {
                $fbc = 'fb.1.' . now()->getTimestampMs() . '.' . request()->query('fbclid');
            }

            $orderTracking = [
                'fbp' => $fbp,
                'fbc' => $fbc,
                'ip' => request()->ip(),
                'ua' => request()->userAgent(),
                'event_source_url' => $this->eventSourceUrl ?: url()->current(),
            ];

            $data += [
                'source_id' => config('app.instant_order_forwarding') ? 0 : null,
                'admin_id' => $admin->id ?? Admin::query()->inRandomOrder()->first()->id,
                'user_id' => $user->id, // If User Logged In
                'status' => $status,
                'status_at' => now()->toDateTimeString(),
                // Additional Data
                'data' => $orderData,
                'tracking' => $orderTracking,
            ];

            $order = Order::create($data);

            // Increment coupon usage if applied
            if ($this->applied_coupon) {
                $this->applied_coupon->incrementUsage();
            }

            defer(function () use ($admin, $user, $order): void {
                $admin->update(['last_order_received_at' => now()]);
                $user->notify(new OrderPlaced($order));

                deleteOrUpdateCart();

                Cache::add('fraud:hourly:' . request()->ip(), 0, now()->addHour());
                Cache::add('fraud:daily:' . request()->ip(), 0, now()->addDay());

                Cache::increment('fraud:hourly:' . request()->ip());
                Cache::increment('fraud:daily:' . request()->ip());

                Cache::add('fraud:hourly:' . $order->phone, 0, now()->addHour());
                Cache::add('fraud:daily:' . $order->phone, 0, now()->addDay());

                Cache::increment('fraud:hourly:' . $order->phone);
                Cache::increment('fraud:daily:' . $order->phone);
            });

            if (setting('meta_pixel') || config('meta-pixel.meta_pixel') || setting('pixel_ids')) {
                $orderPayload = [
                    'id' => $order->id,
                    'total' => $order->data['subtotal'],
                ];
                $userDataArr = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number,
                    'external_id' => $user->id,
                ];
                $orderTrackingData = $order->tracking ?? [];

                // Generate persistent event ID for Lead/Purchase to be reused on thank you page
                $eventName = config('meta-pixel.advanced_tracking') ? 'Lead' : 'Purchase';
                $eventId = 'ch_' . strtolower($eventName) . '_' . $order->id . '_' . time();
                $orderTrackingData['event_id'] = $eventId;

                // Save updated tracking data back to order
                $order->update(['tracking' => $orderTrackingData]);

                if (config('meta-pixel.advanced_tracking')) {
                    // Advanced tracking: fire Lead at checkout, Purchase on order confirmation
                    $this->facebookService->trackLead($orderPayload, $data['products'], $userDataArr, $this, $orderTrackingData);
                } else {
                    // Standard tracking: fire Purchase immediately at checkout
                    $this->facebookService->trackPurchase($orderPayload, $data['products'], $userDataArr, $this, $orderTrackingData);
                }
            }

            return $order;
        });

        if (! $this->order instanceof Order) {
            return back();
        }

        // Undefined index email.
        // $data['email'] && Mail::to($data['email'])->queue(new OrderPlaced($order));

        if (config('app.instant_order_forwarding') && ! config('app.demo')) {
            dispatch(new CallOnindaOrderApi($this->order->id));
        }

        cart()->destroy();
        session()->flash('completed', 'Dear ' . $data['name'] . ', Your Order is Successfully Received. Thanks For Your Order.');

        return to_route($this->getRedirectRoute(), [
            'order' => $this->order?->getKey(),
        ]);
    }

    private function getUser($data)
    {
        if ($user = auth('user')->user()) {
            return $user;
        }

        // $user->notify(new AccountCreated());

        return User::query()->firstOrCreate(
            ['phone_number' => $data['phone']],
            array_merge(Arr::except($data, 'phone'), [
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ])
        );
    }

    public function render()
    {
        $tempOrder = new Order;

        $template = setting('show_option')->checkout_template
            ?? config('app.checkout_template', 'legacy');

        $view = $template === 'simple'
            ? 'livewire.checkout-simple'
            : 'livewire.checkout';

        $cartProductIds = cart()
            ->content()
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return view($view, [
            'user' => optional(auth('user')->user()),
            'pathaoCities' => collect($tempOrder->pathaoCityList()),
            'pathaoAreas' => collect($tempOrder->pathaoAreaList($this->city_id)),
            'retail' => $this->retail,
            'advanced' => $this->advanced,
            'retailDeliveryFee' => $this->retailDeliveryFee,
            'retailDiscount' => $this->retailDiscount,
            'packagingCharge' => $this->resolvePackagingCharge(
                array_flip($cartProductIds)
            ),
        ]);
    }

    public function previewShippingCost(?string $area = null): float|int
    {
        $previousIsFreeDelivery = $this->isFreeDelivery;
        $previousProductForcesFreeDelivery = $this->productForcesFreeDelivery;

        $cost = $this->shippingCost($area);

        $this->isFreeDelivery = $previousIsFreeDelivery;
        $this->productForcesFreeDelivery = $previousProductForcesFreeDelivery;

        return $cost;
    }

    protected function fillFromCookie(): bool
    {
        if (isOninda() && config('app.resell')) {
            return false;
        }

        return true;
    }

    protected function getRedirectRoute(): string
    {
        return 'thank-you';
    }

    protected function getDefaultStatus()
    {
        return data_get(config('app.orders', []), 0, 'PENDING'); // Default Status
    }

    public function toJSON(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'shipping' => $this->shipping,
            'address' => $this->address,
            'note' => $this->note,
            'city_id' => $this->city_id,
            'area_id' => $this->area_id,
            'retail' => $this->retail,
            'retailDeliveryFee' => $this->retailDeliveryFee,
            'advanced' => $this->advanced,
            'retailDiscount' => $this->retailDiscount,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount,
        ];
    }
}
