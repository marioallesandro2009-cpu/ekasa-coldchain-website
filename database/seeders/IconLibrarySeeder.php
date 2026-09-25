<?php

namespace Database\Seeders;

use App\Support\IconLibrary;
use Illuminate\Database\Seeder;

class IconLibrarySeeder extends Seeder
{
    /**
     * Copy the Ekasa icon stock into the admin library (Pustaka Ikon). Safe to
     * run again: admin changes to labels and on/off switches are kept.
     */
    public function run(): void
    {
        IconLibrary::sync();
    }
}
