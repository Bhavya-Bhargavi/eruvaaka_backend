<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('coupons', 'eligible_plans')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->json('eligible_plans')->nullable()->after('offer_message');
            });
        }

        DB::table('coupons')->where('code', 'DIWALI2026')->update([
            'eligible_plans' => json_encode([
                'emagazine_1year', 'emagazine_3year', 'emagazine_5year',
                'print_magazine_1year', 'print_magazine_3year', 'print_magazine_5year',
                'combo_1year', 'combo_3year', 'combo_5year',
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('coupons', 'eligible_plans')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('eligible_plans');
            });
        }
    }
};