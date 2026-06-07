<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Vdj;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderIndex($request, 'en');
    }

    public function localizedIndex(Request $request, string $lang)
    {
        abort_unless(in_array($lang, ['tr', 'de'], true), 404);

        return $this->renderIndex($request, $lang);
    }

    public function show(Request $request, string $slug)
    {
        return $this->renderPost($request, $slug, 'en');
    }

    public function localizedShow(Request $request, string $lang, string $slug)
    {
        abort_unless(in_array($lang, ['tr', 'de'], true), 404);

        return $this->renderPost($request, $slug, $lang);
    }

    public function legacyShow(Request $request, string $slug)
    {
        if (in_array($slug, ['admin', 'api', 'assets', 'build', 'legacy-php', 'public', 'storage', 'uploads'], true)) {
            throw new NotFoundHttpException();
        }

        return $this->renderPost($request, $slug, 'en');
    }

    private function renderIndex(Request $request, string $locale)
    {
        try {
            $posts = BlogPost::query()
                ->with('categories')
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString();
        } catch (Throwable $e) {
            report($e);
            $posts = collect();
        }

        return view('vdj.blog.index', [
            'locale' => $locale,
            'posts' => $posts,
        ]);
    }

    private function renderPost(Request $request, string $slug, string $locale)
    {
        try {
            $post = $this->findPost($slug, $locale);
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        abort_unless($post, 404);

        $canonical = Vdj::blogUrl($post, $locale);
        $description = Vdj::plainText($post->localizedSeoDescription($locale) ?: $post->localizedContent($locale));
        $image = Vdj::imageUrl($post->featured_image);
        $jsonLd = Vdj::jsonLd(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->localizedSeoTitle($locale),
            'description' => $description,
            'image' => $image,
            'datePublished' => optional($post->published_at)->toAtomString(),
            'dateModified' => optional($post->published_at)->toAtomString(),
            'author' => [
                '@type' => 'Organization',
                'name' => $post->author ?: Vdj::siteName(),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => Vdj::siteName(),
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical,
            ],
        ]));

        return view('vdj.blog.show', [
            'post' => $post,
            'locale' => $locale,
            'canonical' => $canonical,
            'description' => $description,
            'image' => $image,
            'jsonLd' => $jsonLd,
            'alternates' => $this->alternateUrls($post),
        ]);
    }

    private function findPost(string $slug, string $locale): ?BlogPost
    {
        return BlogPost::query()
            ->with(['categories', 'tags'])
            ->where(function (Builder $query) use ($slug, $locale): void {
                if ($locale === 'tr' && Vdj::hasColumn('blog_posts', 'slug_tr')) {
                    $query->where('slug_tr', $slug);
                } elseif ($locale === 'de' && Vdj::hasColumn('blog_posts', 'slug_de')) {
                    $query->where('slug_de', $slug);
                } else {
                    $query->where('slug', $slug);
                }

                if ($locale !== 'en') {
                    $query->orWhere('slug', $slug);
                }
            })
            ->first();
    }

    /**
     * @return array<string, string>
     */
    private function alternateUrls(BlogPost $post): array
    {
        return array_filter([
            'en' => Vdj::blogUrl($post, 'en'),
            'tr' => Vdj::hasColumn('blog_posts', 'slug_tr') && filled($post->slug_tr) ? Vdj::blogUrl($post, 'tr') : null,
            'de' => Vdj::hasColumn('blog_posts', 'slug_de') && filled($post->slug_de) ? Vdj::blogUrl($post, 'de') : null,
            'x-default' => Vdj::blogUrl($post, 'en'),
        ]);
    }
}
