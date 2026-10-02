<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Revisi 1 (klien, koreksi): hanya Cold Storage yang "Coming soon". Linehaul, Hyperlocal dan
     * Logistic Partnership aktif seperti biasa. Berlaku untuk kartu layanan dan slide beranda.
     */
    public function up(): void
    {
        DB::table('services')->update(['is_coming_soon' => false]);
        DB::table('services')->where('slug', 'cold-storage')->update(['is_coming_soon' => true]);

        DB::table('hero_slides')->update(['is_coming_soon' => false]);
        DB::table('hero_slides')->where('label->en', 'Cold Storage')->update(['is_coming_soon' => true]);
    }

    public function down(): void
    {
        // Tanda ini bisa diubah dari admin; tidak dikembalikan otomatis.
    }
};
