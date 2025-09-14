<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 3) tweets
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tweets', function (Blueprint $table) {
            $table->uuid();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete(); // ON DELETE CASCADE

            $table->string('text', 280);
            $table->foreignId('place_id')
                ->nullable()
                ->constrained('places')
                ->nullOnDelete(); // ON DELETE SET NULL

            $table->integer('up_count')->default(0);
            $table->integer('down_count')->default(0);

            $table->foreignId('reply_to_tweet_id')
                ->nullable()
                ->constrained('tweets')
                ->nullOnDelete(); // ON DELETE SET NULL

            $table->timestamp('created_at')->useCurrent();

            // فهارس مفيدة
            $table->index(['user_id', 'created_at']);
            $table->index('place_id');
            $table->index('reply_to_tweet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tweets');
    }
};
