<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Brand palette of PT Ekasa Cold Chain: the "navy" section background is now
     * "dark", and colors saved for the old palette (navy, ice, accent, ...) are
     * dropped so the new defaults apply.
     */
    public function up(): void
    {
        DB::table('page_sections')->where('background', 'navy')->update(['background' => 'dark']);
        DB::table('site_settings')->update(['colors' => null]);
    }

    public function down(): void
    {
        DB::table('page_sections')->where('background', 'dark')->update(['background' => 'navy']);
    }
};
