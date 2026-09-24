<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Brand colors as hex codes (defaults in SiteSetting::COLOR_DEFAULTS).
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('colors')->nullable()->after('ui_texts');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('colors');
        });
    }
};
