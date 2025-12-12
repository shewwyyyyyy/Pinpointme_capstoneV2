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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'faculty_id')) {
                $table->string('faculty_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->string('staff_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'faculty_id')) {
                $table->dropColumn('faculty_id');
            }
            if (Schema::hasColumn('users', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });
    }
};
