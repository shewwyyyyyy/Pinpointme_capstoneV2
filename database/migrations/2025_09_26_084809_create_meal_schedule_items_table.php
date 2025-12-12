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
        Schema::create('meal_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_schedule_id')->constrained()->onDelete('restrict');
            $table->string('day_type');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('meal_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_schedule_items');
    }
};
