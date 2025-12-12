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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module')->nullable();
            $table->string('description')->nullable(); // e.g., "User created", "Profile updated"
            $table->string('status')->nullable(); // e.g., pending, completed, failed
            $table->string('type'); // e.g., create, update, delete
            $table->json('properties')->nullable(); // JSON to store additional properties
            $table->unsignedBigInteger('created_by')->nullable(); // Profile ID who created the log
            $table->unsignedBigInteger('updated_by')->nullable(); // Profile ID who updated the log
            $table->foreign('created_by')->references('id')->on('profiles');
            $table->foreign('updated_by')->references('id')->on('profiles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
