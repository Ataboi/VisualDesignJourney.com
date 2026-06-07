<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            NewsletterSubscriber::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'] ?? null,
                    'status' => 'active',
                    'unsubscribe_token' => Str::random(48),
                ],
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Newsletter storage is not ready yet.'], 503);
        }

        return response()->json(['ok' => true]);
    }
}
