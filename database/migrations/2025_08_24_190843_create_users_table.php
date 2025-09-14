<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1) users
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid();
            $table->string('username', 50)->unique();
            $table->string('email')->unique();
            $table->string('password_hash'); // مطابق للنص
            $table->string('google_id')->nullable()->unique();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->string('avatar_url')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->boolean('dark_mode')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // إن أردت updated_at لاحقًا، أضف: $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
