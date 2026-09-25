<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Support\FaviconIco;
use App\Support\FrontendData;
use App\Support\MediaPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMedia();

        // <x-layouts::app> resolves to resources/views/layouts/app.blade.php.
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
    }

    /**
     * Keep the front page cache (and public/favicon.ico) in sync with uploaded media.
     */
    protected function configureMedia(): void
    {
        Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event): void {
            MediaPresenter::storeDimensions($event->media);
            FrontendData::flush();

            if ($event->media->collection_name === 'favicon' && $event->media->model_type === (new SiteSetting)->getMorphClass()) {
                FaviconIco::writeFromMedia($event->media);
            }
        });

        Event::listen(ConversionHasBeenCompletedEvent::class, fn () => FrontendData::flush());

        Media::saved(fn () => FrontendData::flush());
        Media::deleted(fn () => FrontendData::flush());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
