<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('sessions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('sessions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'ip_address', 'user_agent']);
        });
    }
};
