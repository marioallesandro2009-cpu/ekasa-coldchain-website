<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Support\FaviconIco;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\MediaPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
        $this->configureMail();

        // <x-layouts::app> resolves to resources/views/layouts/app.blade.php.
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
    }

    /**
     * Text that visitors type (name, message...) goes into Markdown mails. With secured
     * encoding every interpolated value is escaped so it cannot become a live link, an
     * external image or HTML in the team's inbox (see tests/Feature/SecurityXssTest.php).
     */
    protected function configureMail(): void
    {
        Markdown::withSecuredEncoding();
    }

    /**
     * Contact form: 5 submissions per 15 minutes per IP. A blocked visitor gets the
     * form's own error message (JSON for fetch, flash + redirect for a plain POST),
     * not a bare 429 page.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perMinutes(15, 5)
            ->by((string) $request->ip())
            ->response(function (Request $request, array $headers) {
                $text = SiteSetting::current()->uiTexts(app()->getLocale())['contact_error'];

                if ($request->expectsJson()) {
                    return response()->json(['message' => $text], 429, $headers);
                }

                return redirect(Links::section('contact'))->with('contact_error', $text)->withHeaders($headers);
            }));
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
