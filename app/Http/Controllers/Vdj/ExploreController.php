<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Board;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class ExploreController extends Controller
{
    public function index()
    {
        try {
            $boards = Board::query()
                ->with(['user', 'images'])
                ->withCount(['likes', 'saves', 'comments'])
                ->where(function ($query): void {
                    $query->where('is_draft', false)->orWhereNull('is_draft');
                })
                ->latest('created_at')
                ->paginate(18);

            $posts = BlogPost::query()
                ->latest('published_at')
                ->take(6)
                ->get();
        } catch (Throwable $e) {
            report($e);
            $boards = new LengthAwarePaginator([], 0, 18);
            $posts = collect();
        }

        return view('vdj.home', [
            'boards' => $boards,
            'posts' => $posts,
        ]);
    }
}
