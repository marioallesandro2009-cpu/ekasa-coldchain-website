<?php

namespace App\Filament\Widgets;

use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\Locales;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard: how many translated texts are still empty or waiting for a check.
 * Both show the English text on the site until they are filled and checked.
 */
class TranslationStatusOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Terjemahan';

    // A few small tables: rendered with the dashboard instead of a second request.
    protected static bool $isLazy = false;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $stats = [];

        foreach (array_diff(Locales::all(), [Locales::default()]) as $locale) {
            [$empty, $drafts] = self::counts($locale);
            $code = strtoupper($locale);

            $stats[] = Stat::make("Teks {$code} kosong", (string) $empty)
                ->description($empty > 0 ? 'Tampil versi '.strtoupper(Locales::default()).' di situs' : 'Semua sudah diisi')
                ->icon(Heroicon::OutlinedLanguage)
                ->color($empty > 0 ? 'danger' : 'success');

            $stats[] = Stat::make("Draf {$code} perlu dicek", (string) $drafts)
                ->description($drafts > 0 ? 'Tandai "Sudah dicek" agar tampil di situs' : 'Tidak ada draf')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color($drafts > 0 ? 'warning' : 'success');
        }

        return $stats;
    }

    /**
     * @return array{0: int, 1: int} empty texts, drafts
     */
    public static function counts(string $locale): array
    {
        $records = [
            SiteSetting::current(),
            ...Service::query()->get()->all(),
            ...HeroSlide::query()->get()->all(),
            ...HeroStat::query()->get()->all(),
            ...PageSection::query()->get()->all(),
        ];

        $empty = 0;
        $drafts = 0;

        foreach ($records as $record) {
            $empty += count($record->missingTranslations($locale));
            $drafts += count($record->draftTranslations($locale));
        }

        return [$empty, $drafts];
    }
}
