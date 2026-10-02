<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revisi 1 (klien): Logistic Partnership, Linehaul Transport, Hyperlocal Fulfilment dan
     * Cold Storage ditandai "Coming soon", dan urutan layanan di beranda menjadi Logistic
     * Partnership (kartu besar), Linehaul, Hyperlocal, Cold Storage. Data yang sudah ada diubah
     * di sini; setelah itu semuanya bisa diatur dari admin.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_coming_soon')->default(false)->after('is_featured');
        });

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->boolean('is_coming_soon')->default(false)->after('image_alt');
        });

        $order = ['logistic-partnership' => 1, 'linehaul-transport' => 2, 'hyperlocal-fulfilment' => 3, 'cold-storage' => 4, 'omnichannel-integration' => 5];

        foreach ($order as $slug => $position) {
            DB::table('services')->where('slug', $slug)->update([
                'sort_order' => $position,
                'is_coming_soon' => $slug !== 'omnichannel-integration',
            ]);
        }

        DB::table('hero_slides')->update(['is_coming_soon' => true]);
    }

    public function down(): void
    {
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('is_coming_soon'));
        Schema::table('hero_slides', fn (Blueprint $table) => $table->dropColumn('is_coming_soon'));
    }
};
