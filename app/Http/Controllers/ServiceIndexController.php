<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Support\FrontendData;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

/**
 * Services index page (/services, /id/layanan): the four published, featured
 * services as a bento overview. Separate from the home page teaser (Bagian B)
 * and from the per-service detail page (ServiceController).
 */
class ServiceIndexController extends Controller
{
    public function __invoke(): View
    {
        $locale = Locales::current();
        $data = FrontendData::all($locale);
        $settings = $data['settings'];

        $paths = [];

        foreach (Locales::all() as $pathLocale) {
            $paths[$pathLocale] = Service::indexPath($pathLocale);
        }

        Locales::setPagePaths($paths);

        $seo = Seo::servicesIndex($data, $paths);

        return view('services.index', [
            'settings' => $settings,
            'services' => $data['services'],
            'sections' => $data['sections'],
            'seo' => $seo,
            'jsonLd' => StructuredData::servicesIndex($data, $seo),
        ]);
    }
}
