<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // guests
        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('id')->primary(); 
            $table->string('nickname', 50)->nullable();         // سنولّدها تلقائيًا في الموديل
            $table->string('device_id', 64)->unique()->nullable(); // معرف الجهاز (كوكي/لوكل ستوريج)
            $table->char('user_agent_hash', 64)->nullable();    // اختياري
            $table->char('ip_hash', 64)->nullable();            // اختياري
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
        });

        // tweets: إضافة guest_id وربطه
        Schema::table('tweets', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_id')->nullable()->after('user_id');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('set null');
        });

    }

    public function down(): void
    {
        Schema::table('tweets', function (Blueprint $table) {
            $table->dropForeign(['guest_id']);
            $table->dropColumn('guest_id');
        });

        Schema::dropIfExists('guests');
    }
};
