<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hero "realtime tracker" card was removed: drop its columns and its
     * status text ("tracker_live") from ui_texts.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['tracker_enabled', 'tracker_label', 'tracker_min', 'tracker_max', 'tracker_unit']);
        });

        foreach (DB::table('site_settings')->whereNotNull('ui_texts')->get(['id', 'ui_texts']) as $row) {
            $texts = json_decode($row->ui_texts, true);

            if (is_array($texts) && array_key_exists('tracker_live', $texts)) {
                unset($texts['tracker_live']);
                DB::table('site_settings')->where('id', $row->id)->update(['ui_texts' => json_encode($texts)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('tracker_enabled')->default(true)->after('hero_slide_duration');
            $table->string('tracker_label', 60)->default('Chiller Room')->after('tracker_enabled');
            $table->decimal('tracker_min', 5, 1)->default(-19.0)->after('tracker_label');
            $table->decimal('tracker_max', 5, 1)->default(-17.5)->after('tracker_min');
            $table->string('tracker_unit', 8)->default('°C')->after('tracker_max');
        });
    }
};
