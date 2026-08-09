<div class="tab-pane active" id="item-3" role="tabpanel">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex">
                {{-- Enable Productwise Delivery Charge --}}
                <div class="ml-3 checkbox checkbox-secondary">
                    <input type="hidden" name="show_option[productwise_delivery_charge]" value="0">
                    <x-checkbox id="productwise-delivery-charge" name="show_option[productwise_delivery_charge]"
                        value="1" :checked="!!($show_option->productwise_delivery_charge ?? false)" />
                    <label for="productwise-delivery-charge" class="my-1">Productwise Delivery Charge</label>
                </div>

                {{-- Enable Quantitywise Delivery Charge --}}
                <div class="ml-3 checkbox checkbox-secondary">
                    <input type="hidden" name="show_option[quantitywise_delivery_charge]" value="0">
                    <x-checkbox id="quantitywise-delivery-charge" name="show_option[quantitywise_delivery_charge]"
                        value="1" :checked="!!($show_option->quantitywise_delivery_charge ?? false)" />
                    <label for="quantitywise-delivery-charge" class="my-1">Quantitywise Delivery Charge</label>
                </div>
            </div>
        </div>
        <livewire:free-delivery :free-delivery="$free_delivery ?? null" :delivery-charge="$delivery_charge"
            :delivery-areas="$delivery_areas ?? []" />
              <div class="col-md-12 mt-4">
            <div class="card border border-success">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-primary">Advanced Weight/Unit Based Delivery Charge</h5>
                    <small class="text-muted">Set the advanced delivery charge based on weight or unit.</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Attribute Selection -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Select Unit Attribute</label>
                                <select name="advanced_delivery[attribute_id]" class="form-control">
                                    <option value="">-- Select Attribute --</option>
                                    @php
                                        $attributes = \App\Models\Attribute::all();
                                    @endphp
                                    @foreach($attributes as $attribute)
                                        <option value="{{ $attribute->id }}" 
                                            {{ data_get($advanced_delivery, 'attribute_id') == $attribute->id ? 'selected' : '' }}>
                                            {{ $attribute->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Base Unit -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Base Unit</label>
                                <input type="number" step="0.01" name="advanced_delivery[base_unit]" class="form-control" 
                                    value="{{ data_get($advanced_delivery, 'base_unit', 1) }}">
                            </div>
                        </div>

                        <!-- Extra Charge Inside Dhaka -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Extra Charge (Inside Dhaka)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Tk</span></div>
                                    <input type="number" name="advanced_delivery[extra_charge_inside]" class="form-control" 
                                        value="{{ data_get($advanced_delivery, 'extra_charge_inside', 20) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Extra Charge Outside Dhaka -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Extra Charge (Outside Dhaka)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Tk</span></div>
                                    <input type="number" name="advanced_delivery[extra_charge_outside]" class="form-control" 
                                        value="{{ data_get($advanced_delivery, 'extra_charge_outside', 30) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label for="delivery-text">Delivery Text</label>
                <x-textarea editor name="delivery_text" id="delivery-text">{!! $delivery_text ?? '' !!}</x-textarea>
                <x-error field="delivery_text" />
            </div>
        </div>
    </div>
</div>

@push('js')
    <script src="{{ asset('js/tinymce.js') }}" defer></script>
@endpush