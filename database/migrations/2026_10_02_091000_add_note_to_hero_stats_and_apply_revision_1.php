<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revisi 1 (klien): kolom catatan kecil di bawah label statistik, lalu isi statistik beranda:
     * "Multi Delivery Points", Warehouses "Coming soon", catatan pada Temperature Range,
     * Trusted Brands 20+. Selanjutnya semuanya bisa diubah dari admin.
     */
    public function up(): void
    {
        Schema::table('hero_stats', function (Blueprint $table) {
            $table->json('note')->nullable()->after('label');
        });

        $json = fn (string $en, string $id): string => json_encode(['en' => $en, 'id' => $id], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        DB::table('hero_stats')->where('label->en', 'Delivery Points')->update([
            'value' => null, 'suffix' => null, 'use_count_up' => false, 'text_value' => $json('Multi', 'Multi'),
        ]);
        DB::table('hero_stats')->where('label->en', 'Warehouses')->update([
            'value' => null, 'suffix' => null, 'use_count_up' => false, 'text_value' => $json('Coming soon', 'Segera hadir'),
        ]);
        DB::table('hero_stats')->where('label->en', 'Temperature Range')->update([
            'note' => $json('Customizable to each vendor', 'Dapat disesuaikan dengan vendor'),
        ]);
        DB::table('hero_stats')->where('label->en', 'Trusted Brands')->update([
            'value' => 20, 'suffix' => '+', 'use_count_up' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('hero_stats', fn (Blueprint $table) => $table->dropColumn('note'));
    }
};
