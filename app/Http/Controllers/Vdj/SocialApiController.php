<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardComment;
use App\Models\BoardLike;
use App\Models\BoardSave;
use App\Models\Follow;
use App\Models\Notification;
use App\Models\User;
use App\Support\Vdj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class SocialApiController extends Controller
{
    public function like(Request $request)
    {
        $userId = Auth::id();
        abort_unless($userId, 401);

        $data = $request->validate([
            'board_id' => ['required', 'integer', 'min:1'],
            'action' => ['nullable', 'in:like,unlike'],
        ]);

        $board = Board::query()->where('is_draft', false)->findOrFail($data['board_id']);
        $liked = ($data['action'] ?? 'like') !== 'unlike';

        if ($liked) {
            $created = BoardLike::query()->firstOrCreate(['board_id' => $board->id, 'user_id' => $userId])->wasRecentlyCreated;
            if ($created && (int) $board->user_id !== $userId) {
                Notification::query()->create([
                    'user_id' => $board->user_id,
                    'from_user_id' => $userId,
                    'type' => 'like',
                    'board_id' => $board->id,
                    'is_read' => false,
                ]);
            }
        } else {
            BoardLike::query()->where('board_id', $board->id)->where('user_id', $userId)->delete();
        }

        return response()->json([
            'ok' => true,
            'liked' => $liked,
            'count' => BoardLike::query()->where('board_id', $board->id)->count(),
        ]);
    }

    public function save(Request $request)
    {
        $userId = Auth::id();
        abort_unless($userId, 401);

        $data = $request->validate([
            'board_id' => ['required', 'integer', 'min:1'],
            'action' => ['nullable', 'in:save,unsave'],
        ]);

        $board = Board::query()->where('is_draft', false)->findOrFail($data['board_id']);
        $saved = ($data['action'] ?? 'save') !== 'unsave';

        if ($saved) {
            BoardSave::query()->firstOrCreate(['board_id' => $board->id, 'user_id' => $userId]);
        } else {
            BoardSave::query()->where('board_id', $board->id)->where('user_id', $userId)->delete();
        }

        $count = BoardSave::query()->where('board_id', $board->id)->count();
        if (Vdj::hasColumn('boards', 'save_count')) {
            $board->forceFill(['save_count' => $count])->save();
        }

        return response()->json(['ok' => true, 'saved' => $saved, 'count' => $count]);
    }

    public function comment(Request $request)
    {
        $userId = Auth::id();
        abort_unless($userId, 401);

        $action = $request->input('action', 'add');

        if ($action === 'delete') {
            $data = $request->validate(['comment_id' => ['required', 'integer', 'min:1']]);
            $comment = BoardComment::query()->findOrFail($data['comment_id']);
            abort_unless((int) $comment->user_id === $userId || Auth::user()?->is_admin, 403);
            $comment->delete();

            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'board_id' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'string', 'max:500'],
        ]);

        $board = Board::query()->where('is_draft', false)->findOrFail($data['board_id']);
        $comment = BoardComment::query()->create([
            'board_id' => $board->id,
            'user_id' => $userId,
            'content' => strip_tags($data['content']),
        ]);

        if ((int) $board->user_id !== $userId) {
            Notification::query()->create([
                'user_id' => $board->user_id,
                'from_user_id' => $userId,
                'type' => 'comment',
                'board_id' => $board->id,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'ok' => true,
            'comment' => [
                'id' => $comment->id,
                'username' => Auth::user()->username,
                'avatar' => Vdj::imageUrl(Auth::user()->avatar),
                'content' => $comment->content,
                'created_at' => optional($comment->created_at)->toDateTimeString(),
                'time_ago' => optional($comment->created_at)->diffForHumans(),
            ],
        ]);
    }

    public function follow(Request $request)
    {
        $userId = Auth::id();
        abort_unless($userId, 401);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'username' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'in:follow,unfollow'],
        ]);

        $target = isset($data['user_id'])
            ? User::query()->findOrFail($data['user_id'])
            : User::query()->where('username', $data['username'] ?? '')->firstOrFail();

        abort_if((int) $target->id === $userId, 422);
        $following = ($data['action'] ?? 'follow') !== 'unfollow';

        if ($following) {
            $created = Follow::query()->firstOrCreate(['follower_id' => $userId, 'following_id' => $target->id])->wasRecentlyCreated;
            if ($created) {
                Notification::query()->create([
                    'user_id' => $target->id,
                    'from_user_id' => $userId,
                    'type' => 'follow',
                    'is_read' => false,
                ]);
            }
        } else {
            Follow::query()->where('follower_id', $userId)->where('following_id', $target->id)->delete();
        }

        return response()->json([
            'ok' => true,
            'following' => $following,
            'count' => Follow::query()->where('following_id', $target->id)->count(),
        ]);
    }

    public function searchSuggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['boards' => [], 'users' => []]);
        }

        $boards = Board::query()
            ->with('user')
            ->where('is_draft', false)
            ->where('title', 'like', "%{$q}%")
            ->take(6)
            ->get()
            ->map(fn (Board $board): array => [
                'id' => $board->id,
                'title' => $board->title,
                'username' => optional($board->user)->username,
                'cover' => Vdj::imageUrl($board->cover_image),
                'url' => Vdj::boardUrl($board),
            ]);

        $users = User::query()
            ->where('username', 'like', "%{$q}%")
            ->take(4)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => Vdj::imageUrl($user->avatar),
                'url' => route('profile.show', ['username' => $user->username]),
            ]);

        return response()->json(['boards' => $boards, 'users' => $users]);
    }

    public function randomBoard()
    {
        $board = Board::query()->where('is_draft', false)->inRandomOrder()->first();

        return redirect($board ? Vdj::boardUrl($board) : route('home'));
    }

    public function palette(Request $request)
    {
        $board = Board::query()->find($request->integer('board_id'));
        $fallback = ['#FAF8FF', '#FFFFFF', '#E9ECEF', '#131B2E', '#7F77DD'];

        return response()->json(['colors' => $board?->style_tags ? $fallback : $fallback]);
    }

    public function track(Request $request)
    {
        try {
            if ($request->input('type') === 'click' && $request->integer('board_id') > 0 && Vdj::hasColumn('board_clicks', 'board_id')) {
                DB::table('board_clicks')->insert([
                    'board_id' => $request->integer('board_id'),
                    'click_source' => mb_substr((string) $request->input('click_src', 'unknown'), 0, 80),
                    'user_id' => Auth::id(),
                    'is_guest' => Auth::check() ? 0 : 1,
                    'created_at' => now(),
                ]);
            } elseif (Vdj::hasColumn('page_views', 'page_path')) {
                DB::table('page_views')->insert([
                    'page_path' => mb_substr((string) $request->input('path', $request->path()), 0, 500),
                    'page_title' => mb_substr((string) $request->input('title', ''), 0, 255),
                    'user_id' => Auth::id(),
                    'is_guest' => Auth::check() ? 0 : 1,
                    'referrer' => mb_substr((string) $request->input('ref', ''), 0, 500),
                    'device_type' => 'desktop',
                    'created_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return response('', 204);
    }
}
