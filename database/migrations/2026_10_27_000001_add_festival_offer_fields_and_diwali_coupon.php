<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            if (!Schema::hasColumn('coupons', 'starts_at')) {
                $table->date('starts_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('coupons', 'offer_title')) {
                $table->string('offer_title', 150)->nullable()->after('usage_limit');
            }
            if (!Schema::hasColumn('coupons', 'offer_message')) {
                $table->text('offer_message')->nullable()->after('offer_title');
            }
        });

        $now = now();
        DB::table('coupons')->upsert([[
            'code' => 'DIWALI2026',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'starts_at' => '2026-10-27',
            'expires_at' => '2026-11-15',
            'usage_limit' => null,
            'offer_title' => 'Diwali Special',
            'offer_message' => 'Celebrate Diwali with 10% off your magazine plan.',
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['code'], [
            'discount_type', 'discount_value', 'is_active', 'starts_at', 'expires_at',
            'usage_limit', 'offer_title', 'offer_message', 'updated_at',
        ]);
    }

    public function down(): void
    {
        DB::table('coupons')->where('code', 'DIWALI2026')->update(['is_active' => false]);

        Schema::table('coupons', function (Blueprint $table): void {
            foreach (['offer_message', 'offer_title', 'starts_at'] as $column) {
                if (Schema::hasColumn('coupons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};