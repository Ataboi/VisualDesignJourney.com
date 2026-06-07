<?php

namespace App\Http\Controllers\Vdj;

use App\Http\Controllers\Controller;
use App\Models\User;
use Throwable;

class ProfileController extends Controller
{
    public function show(string $username)
    {
        try {
            $user = User::query()
                ->where('username', $username)
                ->with(['boards' => function ($query): void {
                    $query->with('images')
                        ->where(function ($query): void {
                            $query->where('is_draft', false)->orWhereNull('is_draft');
                        })
                        ->latest('created_at');
                }])
                ->firstOrFail();
        } catch (Throwable $e) {
            report($e);
            abort(404);
        }

        return view('vdj.profile.show', [
            'user' => $user,
        ]);
    }
}
