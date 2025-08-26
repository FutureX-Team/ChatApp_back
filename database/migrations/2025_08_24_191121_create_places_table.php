<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2) places
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('location_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
