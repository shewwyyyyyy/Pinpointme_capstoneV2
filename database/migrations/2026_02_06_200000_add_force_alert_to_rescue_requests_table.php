<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->boolean('force_alert')->default(false)->after('lastName');
            $table->timestamp('force_alert_at')->nullable()->after('force_alert');
        });
    }

    public function down(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->dropColumn(['force_alert', 'force_alert_at']);
        });
    }
};
