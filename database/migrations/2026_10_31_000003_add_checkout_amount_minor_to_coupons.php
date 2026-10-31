<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('coupons', 'checkout_amount_minor')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->unsignedBigInteger('checkout_amount_minor')->nullable()->after('discount_value');
            });
        }

        DB::table('coupons')->whereIn('code', ['DEV25', 'SAVE25'])->update(['checkout_amount_minor' => 300]);
        DB::table('coupons')->whereIn('code', ['DEV50', 'SAVE50'])->update(['checkout_amount_minor' => 500]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('coupons', 'checkout_amount_minor')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('checkout_amount_minor');
            });
        }
    }
};