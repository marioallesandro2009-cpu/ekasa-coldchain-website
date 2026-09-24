<?php

namespace Database\Seeders\Concerns;

use App\Support\PlaceholderImage;
use Spatie\MediaLibrary\HasMedia;

trait SeedsImages
{
    /**
     * Attach database/seeders/images/{name}.(png|jpg|jpeg|webp) to a collection.
     * A placeholder PNG is generated first when the file does not exist.
     */
    protected function attachSeedImage(HasMedia $model, string $collection, string $name, int $width, int $height): void
    {
        if ($model->getMedia($collection)->isNotEmpty()) {
            return;
        }

        $model->addMedia($this->seedImagePath($name, $width, $height))
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }

    protected function seedImagePath(string $name, int $width, int $height): string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $extension) {
            $path = database_path("seeders/images/{$name}.{$extension}");

            if (is_file($path)) {
                return $path;
            }
        }

        $placeholder = storage_path("app/private/seed-placeholders/{$name}.png");

        if (! is_file($placeholder)) {
            PlaceholderImage::create($placeholder, $width, $height, "{$name}.png");
            $this->command->warn("Gambar {$name} tidak ditemukan, placeholder dibuat.");
        }

        return $placeholder;
    }
}
