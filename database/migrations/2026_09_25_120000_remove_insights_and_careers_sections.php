<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Insights and Careers sections are no longer part of the home page.
     */
    public function up(): void
    {
        DB::table('page_sections')->whereIn('key', ['insights', 'careers'])->delete();
    }

    public function down(): void
    {
        // The seeder no longer creates them; nothing to restore.
    }
};
