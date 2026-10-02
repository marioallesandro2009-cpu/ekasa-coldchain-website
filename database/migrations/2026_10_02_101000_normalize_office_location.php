<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Revisi 1 (klien): rapikan baris kantor yang mungkin sudah dibuat oleh migrasi sebelumnya
     * (nama, kota/area, alamat lengkap dari klien). Hanya menyentuh baris kantor bawaan; kantor yang
     * sudah diubah namanya dari admin dibiarkan.
     */
    public function up(): void
    {
        DB::table('locations')
            ->where('kind', 'office')
            ->whereIn('name', ['Kantor Pusat', 'Kantor Ekasa Cold Chain'])
            ->update([
                'name' => 'Kantor Pusat',
                'city' => 'Jakarta Barat',
                'area' => 'Meruya Utara, Kembangan',
                'address' => 'Jl. Penyelesaian Tomang III Jl. Kav. DKI No.1 Blk 10A, RT.8/RW.10, Meruya Utara, Kec. Kembangan, Kota Jakarta Barat, DKI Jakarta 11620',
                'is_sample' => false,
            ]);
    }

    public function down(): void
    {
        // Data lokasi bisa diubah dari admin; tidak dikembalikan otomatis.
    }
};
