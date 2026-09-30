<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\BlogStructuredData;
use App\Support\FrontendData;
use App\Support\Locales;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Blog post detail page (/blog/{slug}, /id/blog/{slug}). A draft post is only
 * shown to a logged-in admin (preview, noindex); a slug of another language
 * redirects to this language's slug.
 */
class BlogController extends Controller
{
    public function __invoke(string $slug): View|RedirectResponse
    {
        $locale = Locales::current();
        $posts = Post::query()->active()->get();
        $post = $posts->first(fn (Post $post): bool => $post->slugFor($locale) === $slug);

        if (! $post) {
            $other = $posts->first(fn (Post $post): bool => in_array($slug, (array) $post->slugs, true));
            abort_unless($other && ($other->isPublished() || auth()->check()), 404);

            return redirect($other->detailPath($locale), 301);
        }

        $preview = ! $post->isPublished();
        abort_if($preview && ! auth()->check(), 404);

        $data = FrontendData::all();
        $page = $post->toDetail();

        $paths = [];

        foreach (Locales::all() as $pathLocale) {
            $paths[$pathLocale] = $post->detailPath($pathLocale);
        }

        Locales::setPagePaths($paths);

        $seo = [...Seo::blogPost($page, $data['settings'], $paths), 'noindex' => $preview];

        return view('blog.show', [
            'settings' => $data['settings'],
            'services' => $data['services'],
            'featuredServices' => $data['featuredServices'],
            'sections' => $data['sections'],
            'post' => $page,
            'preview' => $preview,
            'seo' => $seo,
            'jsonLd' => $preview ? null : BlogStructuredData::post($page, $data['settings'], $seo),
        ]);
    }
}
