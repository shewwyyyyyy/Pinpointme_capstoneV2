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
            // Add phone_number field (map to existing phone field)
            if (!Schema::hasColumn('users', 'phone_number') && Schema::hasColumn('users', 'phone')) {
                // We'll handle this in the model accessor/mutator
            }
            
            // Add emergency contact fields
            if (!Schema::hasColumn('users', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('users', 'emergency_contact_relation')) {
                $table->string('emergency_contact_relation')->nullable()->after('emergency_contact_phone');
            }
            
            // Add medical fields
            if (!Schema::hasColumn('users', 'blood_type')) {
                $table->string('blood_type')->nullable()->after('emergency_contact_relation');
            }
            if (!Schema::hasColumn('users', 'allergies')) {
                $table->text('allergies')->nullable()->after('blood_type');
            }
            if (!Schema::hasColumn('users', 'medical_conditions')) {
                $table->text('medical_conditions')->nullable()->after('allergies');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'emergency_contact_name',
                'emergency_contact_phone', 
                'emergency_contact_relation',
                'blood_type',
                'allergies',
                'medical_conditions'
            ]);
        });
    }
};
