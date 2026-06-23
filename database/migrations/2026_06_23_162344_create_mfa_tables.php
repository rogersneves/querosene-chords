<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One-time codes sent by email
        Schema::create('mfa_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code');          // bcrypt hash of the 6-digit code
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('user_id');
        });

        // Browsers the user chose to trust for 30 days
        Schema::create('mfa_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64); // SHA-256 of the cookie token
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'token_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_codes');
        Schema::dropIfExists('mfa_trusted_devices');
    }
};
