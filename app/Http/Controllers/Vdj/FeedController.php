<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Throwable;

class FeedController extends Controller
{
    public function blog()
    {
        try {
            $posts = BlogPost::query()
                ->latest('published_at')
                ->take(40)
                ->get();
        } catch (Throwable $e) {
            report($e);
            $posts = collect();
        }

        $xml = view('vdj.xml.feed', ['posts' => $posts])->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
