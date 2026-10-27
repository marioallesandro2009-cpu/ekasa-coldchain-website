<?php

namespace Database\Seeders;

use App\Support\FrontendData;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
            IconLibrarySeeder::class,
            ServiceDataSeeder::class,
            PageSectionSeeder::class,
            AboutContentSeeder::class,
            AboutStatSeeder::class,
            AboutValueSeeder::class,
            PostSeeder::class,
            BusinessPostSeeder::class,
            ServiceSeeder::class,
            HeroSlideSeeder::class,
            HeroStatSeeder::class,
            TrustLogoSeeder::class,
        ]);

        FrontendData::flush();
    }
}
