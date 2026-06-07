<?php

namespace App\Http\Controllers;

use App\Models\ColorPalette;
use App\Models\Tag;
use Illuminate\Http\Request;

class ColorPaletteController extends Controller
{
    public function index(Request $request)
    {
        $query = ColorPalette::where('is_active', true)->with(['category', 'tags']);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->mood) {
            $query->where('mood', $request->mood);
        }

        if ($request->tag) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $palettes = $query->latest()->paginate(16)->withQueryString();
        $tags = Tag::where('is_active', true)->get();
        $moods = ['dark', 'light', 'vibrant', 'pastel', 'earthy', 'monochrome', 'neon'];

        return view('color-palettes.index', compact('palettes', 'tags', 'moods'));
    }

    public function show(ColorPalette $colorPalette)
    {
        $colorPalette->increment('view_count');
        $related = ColorPalette::where('is_active', true)
            ->where('id', '!=', $colorPalette->id)
            ->where('mood', $colorPalette->mood)
            ->take(4)->get();
        return view('color-palettes.show', compact('colorPalette', 'related'));
    }
}
