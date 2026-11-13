<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('pending_email')->nullable()->index();
            $table->string('email_change_token', 64)->nullable();
            $table->timestamp('email_change_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropIndex(['pending_email']);
            $table->dropColumn(['pending_email', 'email_change_token', 'email_change_expires_at']);
        });
    }
};
