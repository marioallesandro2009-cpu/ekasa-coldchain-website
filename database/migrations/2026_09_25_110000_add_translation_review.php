<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tables with texts per language, and their translatable columns. */
    private const COLUMNS = [
        'site_settings' => ['company_description', 'cta_label', 'hero_badge', 'hero_headline', 'hero_subtext', 'mega_menu_title', 'mega_menu_link_label', 'seo_title', 'seo_description'],
        'services' => ['title', 'short_description'],
        'hero_slides' => ['label', 'image_alt'],
        'hero_stats' => ['label', 'text_value'],
        'page_sections' => ['nav_label', 'eyebrow', 'title', 'description'],
    ];

    /**
     * translation_status: {"id": {"title": "draft" | "reviewed"}}. Only reviewed
     * translations are shown on the site. Existing translations were written by
     * hand, so they start as reviewed.
     *
     * translation_glossary (settings): terms used when drafting translations.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('translation_status')->nullable();
            });

            foreach (DB::table($table)->get(['id', ...$columns]) as $row) {
                $status = [];

                foreach ($columns as $column) {
                    $texts = json_decode((string) $row->{$column}, true);

                    if (is_array($texts) && trim((string) ($texts['id'] ?? '')) !== '') {
                        $status['id'][$column] = 'reviewed';
                    }
                }

                DB::table($table)->where('id', $row->id)->update(['translation_status' => $status === [] ? null : json_encode($status)]);
            }
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('translation_glossary')->nullable();
        });
    }

    public function down(): void
    {
        foreach (array_keys(self::COLUMNS) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('translation_status');
            });
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('translation_glossary');
        });
    }
};
