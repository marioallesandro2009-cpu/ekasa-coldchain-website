<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lucide icon shown above each hero statistic (null = no icon).
     */
    public function up(): void
    {
        Schema::table('hero_stats', function (Blueprint $table) {
            $table->string('icon', 64)->nullable()->after('label');
        });

        // Starting icons for the seeded statistics only; the admin can change them.
        foreach (['Delivery Points' => 'map-pin', 'Warehouses' => 'warehouse', 'Temperature Range' => 'thermometer-snowflake', 'Trusted Brands' => 'handshake'] as $label => $icon) {
            DB::table('hero_stats')->where('label', $label)->whereNull('icon')->update(['icon' => $icon]);
        }
    }

    public function down(): void
    {
        Schema::table('hero_stats', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
