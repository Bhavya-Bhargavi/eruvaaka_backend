<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'subscription_status')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('subscription_status')->default('expired')->after('is_active');
            });
        }

        if (Schema::hasTable('payment_orders')) {
            if (!Schema::hasColumn('payment_orders', 'plan_type')) {
                Schema::table('payment_orders', function (Blueprint $table): void {
                    $table->string('plan_type')->nullable()->after('receipt');
                });
            }
        } else {
            Schema::create('payment_orders', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('razorpay_order_id')->unique();
                $table->string('razorpay_payment_id')->nullable();
                $table->unsignedBigInteger('amount');
                $table->string('currency', 3);
                $table->string('receipt')->unique();
                $table->string('plan_type')->nullable();
                $table->string('signature')->nullable();
                $table->string('status');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscriptions')) {
            $userId = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
            Schema::create('subscriptions', function (Blueprint $table) use ($userId): void {
                $table->id();
                if (($userId['type_name'] ?? null) === 'int') {
                    $table->unsignedInteger('user_id');
                } else {
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                }
                $table->dateTime('start_date');
                $table->dateTime('end_date');
                $table->string('plan_type');
                $table->string('status')->default('active');
                $table->timestamps();
                $table->index(['user_id', 'status', 'end_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        if (Schema::hasTable('payment_orders')) {
            Schema::table('payment_orders', fn (Blueprint $table) => $table->dropColumn('plan_type'));
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('subscription_status'));
    }
};