<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table (singleton). Logo, favicon, and mega menu image live in the media table.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Company Name');
            $table->string('whatsapp_number', 32)->nullable();
            $table->string('cta_label', 60)->default('Get a Quote');
            $table->string('hero_badge', 120)->default('Temperature-Controlled Logistics');
            $table->string('hero_headline', 160)->default('Keep your products fresh with');
            $table->text('hero_subtext')->nullable();
            $table->unsignedSmallInteger('hero_slide_duration')->default(5);
            $table->string('mega_menu_title', 120)->default('Need a custom solution?');
            $table->string('mega_menu_link_label', 60)->default('Talk to our team');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
