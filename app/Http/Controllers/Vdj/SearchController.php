<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Board;
use Illuminate\Http\Request;
use Throwable;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $boards = collect();
        $posts = collect();

        if ($term !== '') {
            try {
                $boards = Board::query()
                    ->with(['user', 'images'])
                    ->where('is_draft', false)
                    ->where(function ($query) use ($term): void {
                        $query->where('title', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('style_tags', 'like', "%{$term}%");
                    })
                    ->latest('created_at')
                    ->take(18)
                    ->get();

                $posts = BlogPost::query()
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%")
                    ->latest('published_at')
                    ->take(12)
                    ->get();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('vdj.search', [
            'term' => $term,
            'boards' => $boards,
            'posts' => $posts,
        ]);
    }
}
