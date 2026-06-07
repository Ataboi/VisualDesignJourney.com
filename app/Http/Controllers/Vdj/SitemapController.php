<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Board;
use App\Models\User;
use App\Support\Vdj;
use Illuminate\Support\Carbon;
use Throwable;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => now()],
            ['loc' => url('/blog/'), 'lastmod' => now()],
            ['loc' => url('/tools'), 'lastmod' => now()],
            ['loc' => url('/gradient-builder'), 'lastmod' => now()],
        ];

        try {
            BlogPost::query()
                ->latest('published_at')
                ->limit(5000)
                ->get()
                ->each(function (BlogPost $post) use (&$urls): void {
                    $urls[] = ['loc' => Vdj::blogUrl($post, 'en'), 'lastmod' => $post->published_at ?: $post->created_at];

                    if (filled($post->slug_tr)) {
                        $urls[] = ['loc' => Vdj::blogUrl($post, 'tr'), 'lastmod' => $post->published_at ?: $post->created_at];
                    }

                    if (filled($post->slug_de)) {
                        $urls[] = ['loc' => Vdj::blogUrl($post, 'de'), 'lastmod' => $post->published_at ?: $post->created_at];
                    }
                });

            Board::query()
                ->where(function ($query): void {
                    $query->where('is_draft', false)->orWhereNull('is_draft');
                })
                ->latest('created_at')
                ->limit(1000)
                ->get()
                ->each(fn (Board $board) => $urls[] = ['loc' => Vdj::boardUrl($board), 'lastmod' => $board->created_at]);

            User::query()
                ->latest('created_at')
                ->limit(1000)
                ->get()
                ->each(fn (User $user) => $urls[] = ['loc' => url('/profile/'.$user->username), 'lastmod' => $user->created_at]);
        } catch (Throwable $e) {
            report($e);
        }

        $xml = view('vdj.xml.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
