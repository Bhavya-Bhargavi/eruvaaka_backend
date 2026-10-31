<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('books', 'price_minor')) {
            Schema::table('books', function (Blueprint $table): void {
                $table->unsignedBigInteger('price_minor')->nullable()->after('cover_image');
            });
        }

        if (!Schema::hasColumn('payment_orders', 'book_id')) {
            Schema::table('payment_orders', function (Blueprint $table): void {
                $table->foreignId('book_id')->nullable()->after('plan_type')->constrained('books')->nullOnDelete();
            });
        }

        $userIdColumn = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
        $userIdIsInt = ($userIdColumn['type_name'] ?? null) === 'int';

        if (!Schema::hasTable('book_purchases')) {
            Schema::create('book_purchases', function (Blueprint $table) use ($userIdIsInt): void {
                $table->id();
                if ($userIdIsInt) {
                    $table->unsignedInteger('user_id');
                } else {
                    $table->foreignId('user_id');
                }
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreignId('book_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_order_id')->unique()->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'book_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('book_purchases');

        if (Schema::hasColumn('payment_orders', 'book_id')) {
            Schema::table('payment_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('book_id'));
        }

        if (Schema::hasColumn('books', 'price_minor')) {
            Schema::table('books', fn (Blueprint $table) => $table->dropColumn('price_minor'));
        }
    }
};