<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Service detail pages: a slug per language, intro, key points, SEO texts,
     * page status, the page blocks (Filament Builder) and the card photo
     * (own photo, or a fleet side view with a display focus point).
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->json('slugs')->nullable()->after('slug');
            $table->json('intro')->nullable()->after('short_description');
            $table->json('key_points')->nullable()->after('intro');
            $table->json('seo_title')->nullable()->after('key_points');
            $table->json('seo_description')->nullable()->after('seo_title');
            $table->string('detail_status', 12)->default('draft')->after('seo_description');
            $table->json('blocks')->nullable()->after('detail_status');
            $table->foreignId('card_fleet_vehicle_id')->nullable()->after('blocks')->constrained('fleet_vehicles')->nullOnDelete();
            $table->unsignedTinyInteger('card_focus_x')->default(50)->after('card_fleet_vehicle_id');
            $table->unsignedTinyInteger('card_focus_y')->default(50)->after('card_focus_x');
            $table->boolean('is_sample')->default(false)->after('is_active');
        });

        // Existing services: the same slug in every language until the admin changes it.
        foreach (DB::table('services')->get(['id', 'slug']) as $service) {
            DB::table('services')->where('id', $service->id)->update([
                'slugs' => json_encode(['en' => $service->slug, 'id' => $service->slug]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('card_fleet_vehicle_id');
            $table->dropColumn(['slugs', 'intro', 'key_points', 'seo_title', 'seo_description', 'detail_status', 'blocks', 'card_focus_x', 'card_focus_y', 'is_sample']);
        });
    }
};
