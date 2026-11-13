<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 32)->default('content_manager');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('books', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('author');
            $table->string('pdf_path')->nullable()->after('cover_image');
        });

        Schema::table('digital_publications', function (Blueprint $table): void {
            $table->unsignedTinyInteger('month')->nullable()->after('issue_date');
            $table->unsignedSmallInteger('year')->nullable()->after('month');
            $table->text('description')->nullable()->after('title');
            $table->string('cover_image')->nullable()->after('file_path');
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('cover_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['is_published', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');

        Schema::table('digital_publications', function (Blueprint $table): void {
            $table->dropColumn(['month', 'year', 'description', 'cover_image']);
        });

        Schema::table('books', function (Blueprint $table): void {
            $table->dropColumn(['category', 'pdf_path']);
        });

        Schema::dropIfExists('admins');
    }
};
