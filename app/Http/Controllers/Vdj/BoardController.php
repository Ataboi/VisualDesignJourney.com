<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Support\Vdj;
use Throwable;

class BoardController extends Controller
{
    public function show(int $id, ?string $slug = null)
    {
        try {
            $board = Board::query()
                ->with(['user', 'images', 'comments.user'])
                ->withCount(['likes', 'saves', 'comments'])
                ->findOrFail($id);

            if (Vdj::hasColumn('boards', 'view_count')) {
                $board->increment('view_count');
            }
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        return view('vdj.boards.show', [
            'board' => $board,
            'canonical' => Vdj::boardUrl($board),
        ]);
    }
}
