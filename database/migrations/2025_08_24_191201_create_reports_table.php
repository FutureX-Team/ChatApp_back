<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 4) reports
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tweet_id')
                  ->constrained('tweets')
                  ->cascadeOnDelete(); // ON DELETE CASCADE

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete(); // ON DELETE CASCADE

            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tweet_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
