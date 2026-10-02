<?php

use App\Models\Location;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Revisi 1 (klien): peta Lokasi hanya menampilkan titik kantor (Meruya); Pool dikosongkan
     * dulu. Lokasi contoh (gudang dan hub dummy) dihapus. Alamat kantor dari klien; titik peta
     * diperkirakan dari OpenStreetMap, bisa digeser dari admin.
     */
    public function up(): void
    {
        // Through the model so the sites' photos (media library) are removed with them.
        Location::query()->where('is_sample', true)->where('kind', '!=', 'office')->get()->each->delete();

        if (! DB::table('locations')->where('kind', 'office')->exists()) {
            DB::table('locations')->insert([
                'name' => 'Kantor Pusat',
                'kind' => 'office',
                'city' => 'Jakarta Barat',
                'area' => 'Meruya Utara, Kembangan',
                'address' => 'Jl. Penyelesaian Tomang III Jl. Kav. DKI No.1 Blk 10A, RT.8/RW.10, Meruya Utara, Kec. Kembangan, Kota Jakarta Barat, DKI Jakarta 11620',
                'lat' => -6.1998,
                'lng' => 106.7287,
                'temperature_zones' => json_encode([]),
                'opening_hours' => json_encode(['en' => '8am - 6pm', 'id' => '08.00 - 18.00'], JSON_UNESCAPED_UNICODE),
                'sort_order' => 0,
                'is_active' => true,
                'is_sample' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('locations')->where('kind', 'office')->where('name', 'Kantor Pusat')->delete();
    }
};
