<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardImage;
use App\Models\BoardSave;
use App\Models\Follow;
use App\Models\NewsletterSubscriber;
use App\Models\Notification;
use App\Models\User;
use App\Support\Vdj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AccountController extends Controller
{
    public function create()
    {
        return view('vdj.account.board-form', [
            'board' => new Board(),
            'mode' => 'create',
        ]);
    }

    public function storeBoard(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'style_tags' => ['nullable', 'string', 'max:500'],
            'image_urls' => ['nullable', 'string', 'max:8000'],
            'images.*' => ['nullable', 'image', 'max:5120'],
            'action' => ['nullable', 'in:publish,draft'],
        ]);

        $images = $this->collectBoardImages($request);
        $slug = Str::slug($data['title']);

        $payload = [
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'style_tags' => $data['style_tags'] ?? null,
            'cover_image' => $images[0] ?? null,
            'is_draft' => ($data['action'] ?? 'publish') === 'draft',
        ];

        if (Vdj::hasColumn('boards', 'slug')) {
            $payload['slug'] = $slug;
        }

        $board = Board::query()->create($payload);

        foreach ($images as $index => $imageUrl) {
            BoardImage::query()->create([
                'board_id' => $board->id,
                'image_url' => $imageUrl,
                'sort_order' => $index,
                'source_url' => Str::startsWith($imageUrl, ['http://', 'https://']) ? $imageUrl : null,
                'source_platform' => Str::startsWith($imageUrl, ['http://', 'https://']) ? parse_url($imageUrl, PHP_URL_HOST) : null,
            ]);
        }

        return redirect(Vdj::boardUrl($board));
    }

    public function editBoard(int $id)
    {
        $board = $this->ownedBoard($id);

        return view('vdj.account.board-form', [
            'board' => $board,
            'mode' => 'edit',
        ]);
    }

    public function updateBoard(Request $request, int $id)
    {
        $board = $this->ownedBoard($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'style_tags' => ['nullable', 'string', 'max:500'],
            'image_urls' => ['nullable', 'string', 'max:8000'],
            'images.*' => ['nullable', 'image', 'max:5120'],
            'action' => ['nullable', 'in:publish,draft'],
        ]);

        $newImages = $this->collectBoardImages($request);
        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'style_tags' => $data['style_tags'] ?? null,
            'is_draft' => ($data['action'] ?? 'publish') === 'draft',
        ];

        if (Vdj::hasColumn('boards', 'slug')) {
            $payload['slug'] = $board->slug ?: Str::slug($data['title']);
        }

        $board->fill($payload);

        if ($newImages !== []) {
            $board->cover_image = $newImages[0];
        }

        $board->save();

        foreach ($newImages as $index => $imageUrl) {
            BoardImage::query()->create([
                'board_id' => $board->id,
                'image_url' => $imageUrl,
                'sort_order' => (int) $board->images()->count() + $index,
                'source_url' => Str::startsWith($imageUrl, ['http://', 'https://']) ? $imageUrl : null,
                'source_platform' => Str::startsWith($imageUrl, ['http://', 'https://']) ? parse_url($imageUrl, PHP_URL_HOST) : null,
            ]);
        }

        return redirect(Vdj::boardUrl($board));
    }

    public function saved()
    {
        try {
            $boards = Board::query()
                ->with(['user', 'images'])
                ->withCount(['likes', 'saves'])
                ->whereIn('id', BoardSave::query()->where('user_id', Auth::id())->pluck('board_id'))
                ->where(function ($query): void {
                    $query->where('is_draft', false)->orWhereNull('is_draft');
                })
                ->latest('created_at')
                ->paginate(18);
        } catch (Throwable $e) {
            report($e);
            $boards = collect();
        }

        return view('vdj.account.saved', ['boards' => $boards]);
    }

    public function settings(Request $request)
    {
        return view('vdj.account.settings', [
            'section' => $request->query('section', 'profile'),
            'user' => $request->user(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $section = $request->input('section', 'profile');
        $user = $request->user();

        if ($section === 'password') {
            $data = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);

            $user->password_hash = $data['password'];
            $user->save();

            return back()->with('status', 'Password updated.');
        }

        $data = $request->validate([
            'username' => ['required', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username,'.$user->id],
            'bio' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);

        if (! blank($data['website'] ?? null) && ! Str::startsWith($data['website'], ['http://', 'https://'])) {
            $data['website'] = 'https://'.$data['website'];
        }

        $user->fill($data);
        $user->save();

        return back()->with('status', 'Profile updated.');
    }

    public function notifications(Request $request)
    {
        $type = $request->query('type', 'all');
        $query = Notification::query()
            ->with(['fromUser', 'board'])
            ->where('user_id', Auth::id())
            ->latest('created_at');

        if (in_array($type, ['like', 'follow', 'comment', 'mention'], true)) {
            $query->where('type', $type);
        }

        return view('vdj.account.notifications', [
            'type' => $type,
            'notifications' => $query->paginate(30)->withQueryString(),
            'unreadCount' => Notification::query()->where('user_id', Auth::id())->where('is_read', false)->count(),
        ]);
    }

    public function markNotificationsRead()
    {
        Notification::query()->where('user_id', Auth::id())->update(['is_read' => true]);

        return back();
    }

    public function followers(string $username)
    {
        $user = User::query()->where('username', $username)->firstOrFail();

        return view('vdj.community.followers', [
            'user' => $user,
            'followers' => Follow::query()->with('follower')->where('following_id', $user->id)->latest('created_at')->paginate(24, ['*'], 'followers_page'),
            'following' => Follow::query()->with('following')->where('follower_id', $user->id)->latest('created_at')->paginate(24, ['*'], 'following_page'),
        ]);
    }

    public function onboarding()
    {
        return view('vdj.account.onboarding', ['user' => Auth::user()]);
    }

    public function finishOnboarding(Request $request)
    {
        $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'style_tags' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        if ($request->filled('bio')) {
            $user->bio = $request->input('bio');
        }
        $user->is_onboarded = true;
        $user->save();

        return redirect()->route('home');
    }

    public function newsletterPrefs(Request $request)
    {
        $subscriber = $this->subscriberFromToken((string) $request->query('token'));

        return view('vdj.newsletter.preferences', [
            'subscriber' => $subscriber,
            'token' => $request->query('token'),
        ]);
    }

    public function updateNewsletterPrefs(Request $request)
    {
        $subscriber = $this->subscriberFromToken((string) $request->input('token'));
        abort_unless($subscriber, 404);

        $subscriber->status = $request->input('action') === 'resubscribe' ? 'active' : 'unsubscribed';
        $subscriber->save();

        return back()->with('status', 'Preferences updated.');
    }

    public function unsubscribe(Request $request)
    {
        $subscriber = $this->subscriberFromToken((string) $request->query('token'));

        if ($subscriber) {
            $subscriber->status = 'unsubscribed';
            $subscriber->save();
        }

        return view('vdj.newsletter.unsubscribe', [
            'subscriber' => $subscriber,
            'token' => $request->query('token'),
        ]);
    }

    private function ownedBoard(int $id): Board
    {
        $board = Board::query()->with('images')->findOrFail($id);
        abort_unless((int) $board->user_id === Auth::id() || Auth::user()?->is_admin, 403);

        return $board;
    }

    /**
     * @return array<int, string>
     */
    private function collectBoardImages(Request $request): array
    {
        $images = [];

        foreach (preg_split('/\R+/', (string) $request->input('image_urls', '')) ?: [] as $url) {
            $url = trim($url);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && Str::startsWith($url, ['http://', 'https://'])) {
                $images[] = $url;
            }
        }

        foreach ($request->file('images', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $dir = base_path('uploads/boards');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::random(8).'.'.$file->extension();
            $file->move($dir, $name);
            $images[] = 'uploads/boards/'.$name;
        }

        return array_values(array_unique($images));
    }

    private function subscriberFromToken(string $token): ?NewsletterSubscriber
    {
        if ($token === '') {
            return null;
        }

        try {
            return NewsletterSubscriber::query()->where('unsubscribe_token', $token)->first();
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }
}
