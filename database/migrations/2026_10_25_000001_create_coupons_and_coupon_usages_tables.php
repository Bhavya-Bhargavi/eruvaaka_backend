<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('discount_type', 20);
                $table->decimal('discount_value', 12, 2);
                $table->boolean('is_active')->default(true);
                $table->date('expires_at')->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->timestamps();
                $table->index(['is_active', 'expires_at']);
            });
        }

        $userId = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
        $userIdIsInt = ($userId['type_name'] ?? null) === 'int';

        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) use ($userIdIsInt): void {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                if ($userIdIsInt) {
                    $table->unsignedInteger('user_id');
                } else {
                    $table->foreignId('user_id');
                }
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreignId('payment_order_id')->nullable()->unique()->constrained('payment_orders')->nullOnDelete();
                $table->unsignedBigInteger('discount_amount');
                $table->timestamp('used_at');
                $table->timestamps();
                $table->unique(['coupon_id', 'user_id']);
                $table->index(['coupon_id', 'used_at']);
            });
        } else {
            $usageColumns = collect(Schema::getColumns('coupon_usages'))->keyBy('name');
            if ($userIdIsInt && ($usageColumns['user_id']['type_name'] ?? null) !== 'int') {
                DB::statement('ALTER TABLE coupon_usages MODIFY user_id INT UNSIGNED NOT NULL');
            }

            $foreignKeys = collect(Schema::getForeignKeys('coupon_usages'))->pluck('name')->all();
            $indexes = collect(Schema::getIndexes('coupon_usages'))->pluck('name')->all();

            Schema::table('coupon_usages', function (Blueprint $table) use ($foreignKeys, $indexes): void {
                if (!in_array('coupon_usages_user_id_foreign', $foreignKeys, true)) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }
                if (!in_array('coupon_usages_payment_order_id_foreign', $foreignKeys, true)) {
                    $table->foreign('payment_order_id')->references('id')->on('payment_orders')->nullOnDelete();
                }
                if (!in_array('coupon_usages_payment_order_id_unique', $indexes, true)) {
                    $table->unique('payment_order_id');
                }
                if (!in_array('coupon_usages_coupon_id_user_id_unique', $indexes, true)) {
                    $table->unique(['coupon_id', 'user_id']);
                }
                if (!in_array('coupon_usages_coupon_id_used_at_index', $indexes, true)) {
                    $table->index(['coupon_id', 'used_at']);
                }
            });
        }

        Schema::table('payment_orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('payment_orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('payment_orders', 'discount_amount')) {
                $table->unsignedBigInteger('discount_amount')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('discount_amount');
        });

        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};