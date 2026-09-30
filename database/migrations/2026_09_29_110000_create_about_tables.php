<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton: one row holds the #about section's story (Filament Page,
        // like site_settings — App\Filament\Pages\ManageAboutContent).
        Schema::create('about_contents', function (Blueprint $table): void {
            $table->id();
            $table->json('story')->nullable();
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });

        // Achievement numbers under the story (e.g. "12+ Years", "500+ Clients").
        // Same shape as hero_stats, its own table so the two sections never share data.
        Schema::create('about_stats', function (Blueprint $table): void {
            $table->id();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('prefix', 16)->nullable();
            $table->string('suffix', 16)->nullable();
            $table->json('text_value')->nullable();
            $table->json('label')->nullable();
            $table->string('icon', 64)->nullable();
            $table->boolean('use_count_up')->default(true);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });

        // "Why choose us" pillar cards below the story (icon + title + description).
        Schema::create('about_values', function (Blueprint $table): void {
            $table->id();
            $table->string('icon', 64)->nullable();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_values');
        Schema::dropIfExists('about_stats');
        Schema::dropIfExists('about_contents');
    }
};
