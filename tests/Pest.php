<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Seed the full site content on a fake public disk (no admin user). The photos of
 * the service pages (services, fleet, locations) only when asked: they are slow.
 */
function seedSite(bool $serviceImages = false): void
{
    Storage::fake('public');
    config(['media-library.disk_name' => 'public', 'site.admin.email' => null, 'site.seed_service_images' => $serviceImages]);

    test()->seed(DatabaseSeeder::class);
}
