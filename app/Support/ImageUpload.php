<?php

namespace App\Support;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/**
 * Shared limits for image uploads in the admin.
 */
final class ImageUpload
{
    /** Longest side allowed for an uploaded image (SVG is not checked). */
    public const MAX_SIDE = 4000;

    /**
     * Rejects images whose width or height is above MAX_SIDE, with a message
     * that names the ideal size for this field (e.g. "1920 x 1080 px").
     */
    public static function limitDimensions(SpatieMediaLibraryFileUpload $upload, string $idealSize): SpatieMediaLibraryFileUpload
    {
        $max = self::MAX_SIDE;

        return $upload
            ->rule("dimensions:max_width={$max},max_height={$max}")
            ->validationMessages([
                'dimensions' => "Gambar terlalu besar: sisi terpanjang maksimal {$max} px. Perkecil dulu ke ukuran ideal {$idealSize}, lalu unggah lagi.",
            ]);
    }
}
