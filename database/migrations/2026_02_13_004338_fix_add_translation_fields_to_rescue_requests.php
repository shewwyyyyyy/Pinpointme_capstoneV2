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
            // Only add columns if they don't exist
            if (!Schema::hasColumn('rescue_requests', 'original_description')) {
                $table->text('original_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('rescue_requests', 'original_injuries')) {
                $table->text('original_injuries')->nullable()->after('injuries');
            }
            if (!Schema::hasColumn('rescue_requests', 'original_additional_info')) {
                $table->text('original_additional_info')->nullable()->after('additional_info');
            }
            if (!Schema::hasColumn('rescue_requests', 'is_translated')) {
                $table->boolean('is_translated')->default(false)->after('additional_info');
            }
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
