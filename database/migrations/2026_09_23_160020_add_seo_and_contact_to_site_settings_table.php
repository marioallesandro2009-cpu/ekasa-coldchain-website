<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The Open Graph image is stored in the media table (collection "og_image").
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('email')->nullable()->after('whatsapp_number');
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('instagram_url')->nullable()->after('phone');
            $table->string('linkedin_url')->nullable()->after('instagram_url');
            $table->string('seo_title', 120)->default('Company Name')->after('mega_menu_link_label');
            $table->string('seo_description', 320)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'instagram_url', 'linkedin_url', 'seo_title', 'seo_description']);
        });
    }
};
