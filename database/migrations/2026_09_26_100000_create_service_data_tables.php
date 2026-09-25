<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared data for the service detail pages (Tahap 3): fleet, locations,
     * rental schemes, routes, industries and FAQ. Text fields are JSON per
     * language ({"en": ..., "id": ...}); is_sample marks seeded DUMMY rows
     * ("Contoh" in the admin) until they are saved from the admin.
     */
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('model', 100);
            $table->string('brand', 100)->nullable();
            $table->string('type', 10)->default('reefer'); // reefer | dry
            $table->json('description')->nullable();
            $table->smallInteger('temp_min')->nullable(); // °C
            $table->smallInteger('temp_max')->nullable();
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->decimal('box_length', 5, 2)->nullable(); // m
            $table->decimal('box_width', 5, 2)->nullable();
            $table->decimal('box_height', 5, 2)->nullable();
            $table->decimal('volume_m3', 6, 2)->nullable();
            $table->json('notes')->nullable();
            // Display crop of the side view (CSS object-position / scale), percent.
            $table->unsignedTinyInteger('focus_x')->default(50);
            $table->unsignedTinyInteger('focus_y')->default(50);
            $table->unsignedSmallInteger('zoom')->default(100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('kind', 10)->default('warehouse'); // warehouse | hub
            $table->string('city', 100);
            $table->string('area', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->json('temperature_zones')->nullable(); // keys of App\Support\TemperatureZones
            $table->json('commodities')->nullable();
            $table->json('capacity')->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('phone', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_schemes', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('includes')->nullable(); // one item per line, per language
            $table->json('durations')->nullable(); // keys: per_trip | monthly | dedicated
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table): void {
            $table->id();
            $table->string('origin', 100);
            $table->string('destination', 100);
            $table->unsignedSmallInteger('eta_hours_min')->nullable();
            $table->unsignedSmallInteger('eta_hours_max')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->timestamps();
        });

        Schema::create('industries', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->json('question');
            $table->json('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['faqs', 'industries', 'transport_routes', 'rental_schemes', 'locations', 'fleet_vehicles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
