<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Checked with the same pixel-crop method as cold-storage: the Linehaul card
     * photo (a bright white truck box) makes the giant number fully invisible at
     * the default white/10% — Hyperlocal and Partnership stay legible as-is.
     */
    public function up(): void
    {
        DB::table('services')->where('slug', 'linehaul-transport')->update(['card_photo_light' => true]);
    }

    public function down(): void
    {
        DB::table('services')->where('slug', 'linehaul-transport')->update(['card_photo_light' => false]);
    }
};
