<?php

namespace Database\Seeders;

use App\Models\TrustLogo;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

class TrustLogoSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        // DUMMY: fictional companies (food, pharma, retail — the industries the
        // About story mentions), not real businesses. Shown in the hero trust
        // row (capped at TrustLogo::MAX_VISIBLE) and in full in the #clients
        // section's logo loop.
        $logos = [
            ['Nusantara Fresh Foods', 'client-nusantara-fresh'],
            ['Andalas Pharma', 'client-andalas-pharma'],
            ['Borneo Seafood Co.', 'client-borneo-seafood'],
            ['Java Retail Group', 'client-java-retail'],
            ['Sentosa Dairy', 'client-sentosa-dairy'],
            ['Meridian Cold Foods', 'client-meridian-foods'],
        ];

        foreach ($logos as $index => [$name, $image]) {
            $logo = TrustLogo::query()->where('name', $name)->first() ?? new TrustLogo;

            $logo->fill([
                'name' => $name,
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->save();

            $this->attachSeedImage($logo, 'logo', $image, 400, 120);
        }
    }
}
