<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('source_id')->unique();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->text('permalink');
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->char('currency_code', 3)->default('INR');
            $table->unsignedTinyInteger('currency_minor_unit')->default(2);
            $table->text('image_url')->nullable();
            $table->boolean('in_stock')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};