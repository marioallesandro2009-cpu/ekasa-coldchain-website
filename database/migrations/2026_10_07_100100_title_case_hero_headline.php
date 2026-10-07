<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permintaan klien: huruf depan tiap kata di judul besar hero dikapitalkan. Hanya teks bawaan
     * yang diubah (per bahasa); judul yang sudah diganti sendiri dari admin dibiarkan.
     */
    public function up(): void
    {
        $changes = [
            'en' => ['Keep your products fresh with', 'Keep Your Products Fresh With'],
            'id' => ['Jaga produk Anda tetap segar dengan', 'Jaga Produk Anda Tetap Segar Dengan'],
        ];

        foreach (DB::table('site_settings')->get(['id', 'hero_headline']) as $row) {
            $headline = json_decode((string) $row->hero_headline, true);

            if (! is_array($headline)) {
                continue;
            }

            $changed = false;

            foreach ($changes as $locale => [$old, $new]) {
                if (($headline[$locale] ?? null) === $old) {
                    $headline[$locale] = $new;
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('site_settings')->where('id', $row->id)->update([
                    'hero_headline' => json_encode($headline, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Judul bisa diubah dari admin; tidak dikembalikan otomatis.
    }
};
