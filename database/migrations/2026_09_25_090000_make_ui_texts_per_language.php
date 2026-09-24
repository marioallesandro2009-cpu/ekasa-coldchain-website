<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Texts that were saved only because the old admin form was pre-filled with
     * the defaults; they are dropped so the defaults in lang/*.json apply.
     */
    private const OLD_DEFAULTS = [
        'hero_secondary_cta' => 'Explore Services',
        'whatsapp_button' => 'Chat on WhatsApp',
        'trust_label' => 'Trusted by',
        'footer_copyright' => 'All rights reserved.',
        'skip_link' => 'Skip to content',
        'menu_open' => 'Open menu',
        'menu_close' => 'Close menu',
        'menu_dialog' => 'Menu',
        'nav_main' => 'Main',
        'nav_mobile' => 'Mobile',
        'language' => 'Language: English',
        'slides_group' => 'Choose a service',
        'slide_show' => 'Show',
    ];

    /**
     * ui_texts {key: text} becomes {"en": {key: text}, "id": {}}: the existing
     * (custom) texts are the English version.
     */
    public function up(): void
    {
        foreach (DB::table('site_settings')->get(['id', 'ui_texts']) as $row) {
            $texts = json_decode((string) $row->ui_texts, true);
            $texts = is_array($texts) ? $texts : [];

            if (array_key_exists('en', $texts) && is_array($texts['en'])) {
                continue;
            }

            $english = array_filter(
                $texts,
                fn ($text, $key): bool => is_string($text) && trim($text) !== '' && (self::OLD_DEFAULTS[$key] ?? null) !== $text,
                ARRAY_FILTER_USE_BOTH,
            );

            DB::table('site_settings')->where('id', $row->id)->update(['ui_texts' => json_encode(['en' => $english, 'id' => []])]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('site_settings')->get(['id', 'ui_texts']) as $row) {
            $texts = json_decode((string) $row->ui_texts, true);

            DB::table('site_settings')->where('id', $row->id)->update(['ui_texts' => json_encode($texts['en'] ?? [])]);
        }
    }
};
