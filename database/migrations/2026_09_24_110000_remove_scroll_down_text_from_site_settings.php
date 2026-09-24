<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The hero chevron was removed, so its screen reader label ("scroll_down",
     * a key inside the ui_texts JSON column) is dropped from the stored settings.
     */
    public function up(): void
    {
        foreach (DB::table('site_settings')->whereNotNull('ui_texts')->get(['id', 'ui_texts']) as $row) {
            $texts = json_decode((string) $row->ui_texts, true);

            if (is_array($texts) && array_key_exists('scroll_down', $texts)) {
                unset($texts['scroll_down']);
                DB::table('site_settings')->where('id', $row->id)->update(['ui_texts' => json_encode($texts)]);
            }
        }
    }

    /**
     * Nothing to restore: the label no longer exists in the code.
     */
    public function down(): void {}
};
