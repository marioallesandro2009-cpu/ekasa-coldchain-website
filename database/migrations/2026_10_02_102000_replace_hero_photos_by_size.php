<?php

use App\Models\HeroSlide;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Koreksi untuk 2026_10_02_095000_replace_hero_photos: versi pertamanya melewati foto yang namanya
     * sama dengan file di repo, padahal isinya foto lama (slide Logistic Partnership). Di sini yang
     * dibandingkan ukuran file, jadi foto yang belum terganti diganti sekarang dan yang sudah benar
     * dilewati. Selanjutnya foto bisa diganti lewat admin (Slide Hero).
     */
    public function up(): void
    {
        $slides = [
            'Cold Storage' => ['hero-truck', ['en' => 'Refrigerated truck on a mountain highway', 'id' => 'Truk berpendingin di jalan raya pegunungan']],
            'Logistic Partnership' => ['hero-partnership', ['en' => 'White freight truck on a highway', 'id' => 'Truk angkutan putih di jalan raya']],
        ];

        foreach ($slides as $label => [$file, $alt]) {
            $slide = HeroSlide::query()->where('label->en', $label)->first();
            $path = database_path("seeders/images/{$file}.jpg");

            // Same file name is not proof of the same photo (the old Logistic Partnership photo had it too): compare sizes.
            if (! $slide || ! is_file($path) || $slide->getFirstMedia('image')?->size === filesize($path)) {
                continue;
            }

            $slide->clearMediaCollection('image');
            $slide->forceFill(['image_alt' => $alt])->save();
            $slide->addMedia($path)->preservingOriginal()->usingName($file)->toMediaCollection('image');
        }

        // Gambar Open Graph (pratinjau saat tautan dibagikan) memakai foto truk, bukan foto gudang lama.
        $settings = SiteSetting::query()->first();
        $truck = database_path('seeders/images/hero-truck.jpg');

        if ($settings && is_file($truck) && $settings->getFirstMedia('og_image')?->size !== filesize($truck)) {
            $settings->clearMediaCollection('og_image');
            $settings->addMedia($truck)->preservingOriginal()->usingName('hero-truck')->toMediaCollection('og_image');
        }
    }

    public function down(): void
    {
        // Foto bisa diganti dari admin; tidak dikembalikan otomatis.
    }
};
