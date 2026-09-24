<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hero "realtime tracker" card (desktop): room label, temperature range and unit.
     * The shown value moves within tracker_min..tracker_max.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('tracker_enabled')->default(true)->after('hero_slide_duration');
            $table->string('tracker_label', 60)->default('Chiller Room')->after('tracker_enabled');
            $table->decimal('tracker_min', 5, 1)->default(-19.0)->after('tracker_label');
            $table->decimal('tracker_max', 5, 1)->default(-17.5)->after('tracker_min');
            $table->string('tracker_unit', 8)->default('°C')->after('tracker_max');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['tracker_enabled', 'tracker_label', 'tracker_min', 'tracker_max', 'tracker_unit']);
        });
    }
};
