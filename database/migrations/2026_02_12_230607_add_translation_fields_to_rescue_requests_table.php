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
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->text('original_description')->nullable()->after('description');
            $table->text('original_injuries')->nullable()->after('injuries');
            $table->text('original_additional_info')->nullable()->after('additional_info');
            $table->boolean('is_translated')->default(false)->after('original_additional_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->dropColumn(['original_description', 'original_injuries', 'original_additional_info', 'is_translated']);
        });
    }
};
