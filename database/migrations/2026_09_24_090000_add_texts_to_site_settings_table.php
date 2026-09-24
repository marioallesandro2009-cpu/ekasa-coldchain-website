<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ui_texts: button labels and screen reader labels (defaults in SiteSetting::UI_TEXT_DEFAULTS).
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('company_description')->nullable()->after('company_name');
            $table->string('area_served')->default('Indonesia')->after('company_description');
            $table->json('ui_texts')->nullable()->after('mega_menu_link_label');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['company_description', 'area_served', 'ui_texts']);
        });
    }
};
