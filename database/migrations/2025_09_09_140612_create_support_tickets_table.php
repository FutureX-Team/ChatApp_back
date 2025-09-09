<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('email')->nullable();
            $table->text('message');
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            $table->timestamps(); // يضيف created_at و updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
