<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * card_photo_light: the /services index card's giant ordinal number needs
     * dark, higher-opacity ink on a bright/busy photo and pale, low-opacity
     * white on a dark/uniform one. An admin toggle (checked once per photo,
     * not auto-detected) is simpler and more predictable than measuring the
     * uploaded image's brightness in code.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('card_photo_light')->default(false)->after('card_focus_y');
        });

        DB::table('services')->where('slug', 'cold-storage')->update(['card_photo_light' => true]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('card_photo_light');
        });
    }
};
