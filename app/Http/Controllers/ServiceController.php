<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Support\FrontendData;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\ServicePage;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Service detail page (/services/{slug}, /id/layanan/{slug}). A draft page is only
 * shown to a logged-in admin (preview, noindex); a slug of another language
 * redirects to this language's slug.
 */
class ServiceController extends Controller
{
    public function __invoke(string $slug): View|RedirectResponse
    {
        $locale = Locales::current();
        $services = Service::query()->active()->get();
        $service = $services->first(fn (Service $service): bool => $service->slugFor($locale) === $slug);

        if (! $service) {
            $other = $services->first(fn (Service $service): bool => in_array($slug, (array) $service->slugs, true));
            abort_unless($other && ($other->isPublished() || auth()->check()), 404);

            return redirect($other->detailPath($locale), 301);
        }

        $preview = ! $service->isPublished();
        abort_if($preview && ! auth()->check(), 404);

        $data = FrontendData::all();
        $settings = $data['settings'];
        $build = fn (): array => ServicePage::build($service, $settings, $locale);
        $page = $preview ? $build() : FrontendData::remember("service.{$service->id}.{$locale}", $build);

        Locales::setPagePaths($page['paths']);
        $seo = [...Seo::service($page, $settings), 'noindex' => $preview];

        return view('services.show', [
            'settings' => $settings,
            'services' => $data['services'],
            'featuredServices' => $data['featuredServices'],
            'sections' => $data['sections'],
            'page' => $page,
            'preview' => $preview,
            'seo' => $seo,
            'jsonLd' => $preview ? null : StructuredData::service($page, $settings, $seo),
        ]);
    }
}
