<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\SourceBookmark;
use App\Support\Vdj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class SourceBookmarkController extends Controller
{
    public function store(Request $request)
    {
        if (! $request->has('source_url') && $request->has('url')) {
            $request->merge(['source_url' => $request->input('url')]);
        }

        if (! $request->has('suggested_tags') && $request->has('tags')) {
            $request->merge(['suggested_tags' => $request->input('tags')]);
        }

        $data = $request->validate([
            'source_url' => ['required', 'url', 'max:1000'],
            'source_platform' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'suggested_tags' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            SourceBookmark::query()->updateOrCreate(
                ['source_url' => $data['source_url']],
                $data + ['status' => 'pending'],
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Source queue is not ready yet.'], 503);
        }

        return response()->json(['ok' => true]);
    }

    public function discoverForBoard(Request $request)
    {
        abort_unless(Auth::check(), 401);
        abort_unless((bool) Auth::user()?->is_admin, 403);

        $data = $request->validate([
            'board_id' => ['required', 'integer', 'min:1'],
        ]);

        $board = Board::query()->with('images')->findOrFail($data['board_id']);
        $queries = collect([
            $board->title,
            ...preg_split('/[,#]+/', (string) $board->style_tags) ?: [],
        ])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $queued = 0;
        $skipped = 0;
        $items = [];

        try {
            foreach ($board->images as $image) {
                $sourceUrl = trim((string) ($image->source_url ?: $image->image_url));

                if (! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! Str::startsWith($sourceUrl, ['http://', 'https://'])) {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'user_id' => Auth::id(),
                    'source_url' => $sourceUrl,
                    'source_platform' => parse_url($sourceUrl, PHP_URL_HOST) ?: 'external',
                    'title' => Str::limit((string) $board->title, 255, ''),
                    'description' => Str::limit((string) $board->description, 500, ''),
                    'image_url' => Str::limit((string) $image->image_url, 1000, ''),
                    'suggested_tags' => Str::limit((string) $board->style_tags, 255, ''),
                    'status' => 'pending',
                ];

                if (Vdj::hasColumn('source_bookmarks', 'board_id')) {
                    $payload['board_id'] = $board->id;
                }

                SourceBookmark::query()->updateOrCreate(['source_url' => $sourceUrl], $payload);
                $queued++;
                $items[] = [
                    'title' => $payload['title'],
                    'image_url' => $payload['image_url'],
                    'source_url' => $payload['source_url'],
                    'platform' => $payload['source_platform'],
                ];
            }
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'error' => 'source_queue_not_ready'], 503);
        }

        return response()->json([
            'ok' => true,
            'queued' => $queued,
            'skipped' => $skipped,
            'queries' => $queries,
            'items' => array_slice($items, 0, 30),
        ]);
    }
}
