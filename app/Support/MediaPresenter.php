<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Turns media into the payload the frontend expects. The shape is identical
 * whether or not WebP conversions exist: when a conversion is missing, both
 * sizes point at the original file.
 *
 * @phpstan-type ImageSize array{url: string, width: int, height: int}
 * @phpstan-type ImagePayload array{large: ImageSize, small: ImageSize, alt: string}
 */
final class MediaPresenter
{
    /**
     * Read and remember the original dimensions of an uploaded image.
     */
    public static function storeDimensions(Media $media): void
    {
        if ($media->hasCustomProperty('width')) {
            return;
        }

        [$width, $height] = self::readDimensions($media);

        if ($width > 0) {
            $media->setCustomProperty('width', $width);
            $media->setCustomProperty('height', $height);
            $media->saveQuietly();
        }
    }

    /**
     * @return ImagePayload|null
     */
    public static function image(?Media $media, string $large, string $small, int $largeWidth, int $smallWidth, ?string $alt = null): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'large' => self::size($media, $large, $largeWidth),
            'small' => self::size($media, $small, $smallWidth),
            'alt' => (string) $alt,
        ];
    }

    /**
     * srcset for an image payload; a single candidate when both sizes are the original file.
     *
     * @param  ImagePayload  $image
     */
    public static function srcset(array $image): string
    {
        ['large' => $large, 'small' => $small] = $image;

        if ($small['url'] === $large['url']) {
            return $large['url'].' '.$large['width'].'w';
        }

        return $small['url'].' '.$small['width'].'w, '.$large['url'].' '.$large['width'].'w';
    }

    /**
     * The original file with its dimensions.
     *
     * @return ImageSize
     */
    public static function original(Media $media): array
    {
        [$width, $height] = self::originalDimensions($media);

        return ['url' => $media->getUrl(), 'width' => $width, 'height' => $height];
    }

    /**
     * Logo payload: the original file (PNG/SVG) plus the WebP version when available.
     *
     * @return array{url: string, webp: string|null, width: int, height: int}|null
     */
    public static function logo(?Media $media, string $webpConversion): ?array
    {
        if (! $media) {
            return null;
        }

        [$width, $height] = self::originalDimensions($media);

        return [
            'url' => $media->getUrl(),
            'webp' => $media->hasGeneratedConversion($webpConversion) ? $media->getUrl($webpConversion) : null,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return ImageSize
     */
    private static function size(Media $media, string $conversion, int $targetWidth): array
    {
        [$width, $height] = self::originalDimensions($media);

        if (! $media->hasGeneratedConversion($conversion)) {
            return ['url' => $media->getUrl(), 'width' => $width, 'height' => $height];
        }

        // Conversions use Fit::Max, so images are never upscaled.
        $scaledWidth = $width > 0 ? min($targetWidth, $width) : $targetWidth;
        $scaledHeight = $width > 0 ? (int) round($height * $scaledWidth / $width) : 0;

        return ['url' => $media->getUrl($conversion), 'width' => $scaledWidth, 'height' => $scaledHeight];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function originalDimensions(Media $media): array
    {
        if ($media->hasCustomProperty('width')) {
            return [(int) $media->getCustomProperty('width'), (int) $media->getCustomProperty('height')];
        }

        return self::readDimensions($media);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function readDimensions(Media $media): array
    {
        $path = $media->getPath();

        if ($media->mime_type === 'image/svg+xml' && is_file($path)) {
            $svg = @simplexml_load_file($path);

            if ($svg !== false) {
                $width = (int) ($svg['width'] ?? 0);
                $height = (int) ($svg['height'] ?? 0);

                if (($width === 0 || $height === 0) && isset($svg['viewBox'])) {
                    $box = preg_split('/[\s,]+/', trim((string) $svg['viewBox'])) ?: [];
                    $width = (int) ($box[2] ?? 0);
                    $height = (int) ($box[3] ?? 0);
                }

                return [$width, $height];
            }
        }

        $size = is_file($path) ? @getimagesize($path) : false;

        return $size ? [(int) $size[0], (int) $size[1]] : [0, 0];
    }
}
