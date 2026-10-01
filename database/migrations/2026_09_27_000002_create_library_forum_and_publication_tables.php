<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userIdColumn = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
        $userIdIsInt = ($userIdColumn['type_name'] ?? null) === 'int';

        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('cover_image')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('forum_discussions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('forum_comments', function (Blueprint $table) use ($userIdIsInt): void {
            $table->id();
            $table->foreignId('forum_discussion_id')->constrained()->cascadeOnDelete();
            if ($userIdIsInt) {
                $table->unsignedInteger('user_id');
            } else {
                $table->foreignId('user_id');
            }
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['forum_discussion_id', 'created_at']);
        });

        Schema::create('digital_publications', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 16);
            $table->string('slug');
            $table->string('title');
            $table->string('issue_date')->nullable();
            $table->string('file_path');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_publications');
        Schema::dropIfExists('forum_comments');
        Schema::dropIfExists('forum_discussions');
        Schema::dropIfExists('books');
    }
};