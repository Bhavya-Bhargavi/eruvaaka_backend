<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('coupons', 'is_test')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->boolean('is_test')->default(false)->after('is_active');
            });
        }

        $now = now();
        $eligiblePlans = json_encode([
            'yearly', '1year', '3year', '5year',
            'emagazine_monthly', 'print_magazine_monthly', 'combo_monthly',
            'emagazine_1year', 'emagazine_3year', 'emagazine_5year',
            'print_magazine_1year', 'print_magazine_3year', 'print_magazine_5year',
            'combo_1year', 'combo_3year', 'combo_5year',
        ], JSON_THROW_ON_ERROR);

        DB::table('coupons')->upsert([
            [
                'code' => 'DEV25',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'is_active' => true,
                'is_test' => false,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'offer_title' => 'Demo 25% Discount',
                'offer_message' => '25% off eligible magazine subscription plans.',
                'eligible_plans' => $eligiblePlans,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DEV50',
                'discount_type' => 'percentage',
                'discount_value' => 50,
                'is_active' => true,
                'is_test' => false,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'offer_title' => 'Demo 50% Discount',
                'offer_message' => '50% off eligible magazine subscription plans.',
                'eligible_plans' => $eligiblePlans,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SAVE25',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'is_active' => true,
                'is_test' => false,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'offer_title' => 'Demo 25% Discount',
                'offer_message' => '25% off eligible magazine subscription plans.',
                'eligible_plans' => $eligiblePlans,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SAVE50',
                'discount_type' => 'percentage',
                'discount_value' => 50,
                'is_active' => true,
                'is_test' => false,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'offer_title' => 'Demo 50% Discount',
                'offer_message' => '50% off eligible magazine subscription plans.',
                'eligible_plans' => $eligiblePlans,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], [
            'discount_type', 'discount_value', 'is_active', 'is_test', 'starts_at', 'expires_at',
            'usage_limit', 'offer_title', 'offer_message', 'eligible_plans', 'updated_at',
        ]);
    }

    public function down(): void
    {
        DB::table('coupons')->whereIn('code', ['DEV25', 'DEV50', 'SAVE25', 'SAVE50'])->update(['is_active' => false]);
    }
};