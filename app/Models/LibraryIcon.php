<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Support\IconLibrary;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An icon in the admin library (Pustaka Ikon). "ekasa" icons come from
 * resources/icons/ekasa (drawing read-only in the admin), "upload" icons are
 * SVG files added in the admin, cleaned by IconLibrary::sanitize().
 *
 * @property int $id
 * @property string $key
 * @property string $label_en
 * @property string|null $label_id
 * @property string $category
 * @property string $svg
 * @property string $source
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['key', 'label_en', 'label_id', 'category', 'svg', 'source', 'sort_order', 'is_active'])]
class LibraryIcon extends Model
{
    use FlushesFrontendCache;

    public const SOURCE_EKASA = 'ekasa';

    public const SOURCE_UPLOAD = 'upload';

    protected static function booted(): void
    {
        static::saved(fn () => IconLibrary::flush());
        static::deleted(fn () => IconLibrary::flush());
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function isBuiltIn(): bool
    {
        return $this->source === self::SOURCE_EKASA;
    }

    public function label(): string
    {
        return $this->label_id ?: $this->label_en;
    }

    /**
     * Usages of every icon at once (two queries, once per request), for lists.
     *
     * @return array<string, list<string>>
     */
    public static function usageMap(): array
    {
        return once(function (): array {
            $map = [];

            foreach (Service::query()->whereNotNull('icon')->ordered()->get() as $service) {
                $map[$service->icon][] = 'Layanan: '.$service->translation('title', 'en');
            }

            foreach (HeroStat::query()->whereNotNull('icon')->ordered()->get() as $stat) {
                $map[$stat->icon][] = 'Statistik: '.$stat->translation('label', 'en');
            }

            return $map;
        });
    }

    /**
     * Where the icon is used: "Layanan: Cold Storage", "Statistik: Delivery Points".
     *
     * @return list<string>
     */
    public function usages(): array
    {
        $usages = [];

        foreach (Service::query()->where('icon', $this->key)->ordered()->get() as $service) {
            $usages[] = 'Layanan: '.$service->translation('title', 'en');
        }

        foreach (HeroStat::query()->where('icon', $this->key)->ordered()->get() as $stat) {
            $usages[] = 'Statistik: '.$stat->translation('label', 'en');
        }

        return $usages;
    }
}
