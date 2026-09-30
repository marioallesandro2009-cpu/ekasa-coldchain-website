<?php

namespace App\Http\Controllers;

use App\Support\FrontendData;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $data = FrontendData::all();

        return view('home', [
            'settings' => $data['settings'],
            'services' => $data['services'],
            'featuredServices' => $data['featuredServices'],
            'locations' => $data['locations'],
            'heroSlides' => $data['heroSlides'],
            'heroStats' => $data['heroStats'],
            'trustLogos' => $data['trustLogos'],
            'clientLogos' => $data['clientLogos'],
            'sections' => $data['sections'],
            'recentPosts' => $data['recentPosts'],
            'about' => $data['about'],
            'seo' => Seo::home($data),
            'jsonLd' => StructuredData::home($data),
        ]);
    }
}
