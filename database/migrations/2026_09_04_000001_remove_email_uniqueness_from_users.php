<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Schema::getIndexes('users') as $index) {
            if (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['email']) {
                Schema::table('users', function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index['name']);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
        });
    }
};