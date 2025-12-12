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
        Schema::create('scan_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('restrict');
            $table->timestamp('scanned_at');
            $table->foreignId('property_id')->constrained()->onDelete('restrict');
            $table->string('meal_schedule'); // e.g., breakfast, lunch, dinner
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('meal_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_histories');
    }
};
