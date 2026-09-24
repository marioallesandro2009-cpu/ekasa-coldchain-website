<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin text fields that exist in every language, stored as JSON
     * ({"en": "...", "id": "..."}). See App\Models\Concerns\HasTranslations.
     */
    private const COLUMNS = [
        'site_settings' => ['company_description', 'cta_label', 'hero_badge', 'hero_headline', 'hero_subtext', 'mega_menu_title', 'mega_menu_link_label', 'seo_title', 'seo_description'],
        'services' => ['title', 'short_description'],
        'hero_slides' => ['label', 'image_alt'],
        'hero_stats' => ['label', 'text_value'],
        'page_sections' => ['nav_label', 'eyebrow', 'title', 'description'],
    ];

    /**
     * Existing texts become the English version.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $rows = DB::table($table)->get(['id', ...$columns]);

            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->text($column)->nullable()->default(null)->change();
                }
            });

            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $text = $row->{$column};
                    $values[$column] = $text === null || $text === '' ? null : json_encode(['en' => $text], JSON_UNESCAPED_UNICODE);
                }

                DB::table($table)->where('id', $row->id)->update($values);
            }
        }
    }

    /**
     * Back to one language: the English version is kept (columns stay text).
     */
    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach (DB::table($table)->get(['id', ...$columns]) as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $decoded = json_decode((string) $row->{$column}, true);
                    $values[$column] = is_array($decoded) ? ($decoded['en'] ?? null) : $row->{$column};
                }

                DB::table($table)->where('id', $row->id)->update($values);
            }
        }
    }
};
