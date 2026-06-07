<?php

namespace App\Http\Controllers;

use App\Models\FontPairing;
use App\Models\Tag;
use Illuminate\Http\Request;

class FontPairingController extends Controller
{
    public function index(Request $request)
    {
        $query = FontPairing::where('is_active', true)->with(['category', 'tags']);

        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('heading_font', 'like', "%{$request->search}%")
                ->orWhere('body_font', 'like', "%{$request->search}%"));
        }

        if ($request->style) {
            $query->where('style', $request->style);
        }

        if ($request->tag) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $pairings = $query->latest()->paginate(12)->withQueryString();
        $tags = Tag::where('is_active', true)->get();

        return view('font-pairings.index', compact('pairings', 'tags'));
    }

    public function show(FontPairing $fontPairing)
    {
        $fontPairing->increment('view_count');
        $related = FontPairing::where('is_active', true)
            ->where('id', '!=', $fontPairing->id)
            ->where('style', $fontPairing->style)
            ->take(4)->get();
        return view('font-pairings.show', compact('fontPairing', 'related'));
    }
}
