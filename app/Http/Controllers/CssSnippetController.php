<?php

namespace App\Http\Controllers;

use App\Models\CssSnippet;
use Illuminate\Http\Request;

class CssSnippetController extends Controller
{
    public function index(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, CssSnippet::$typeRoutes), 404);

        $routeKey = array_search($request->route()->uri(), array_map(fn ($r) => $r, CssSnippet::$typeRoutes));
        $snippetType = $type;

        $query = CssSnippet::where('is_active', true)->where('snippet_type', $snippetType);

        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"));
        }

        if ($request->style) {
            $query->where('style', $request->style);
        }

        $snippets = $query->latest()->paginate(12)->withQueryString();
        $label    = CssSnippet::$typeLabels[$snippetType];
        $styles   = CssSnippet::where('snippet_type', $snippetType)->whereNotNull('style')->distinct()->pluck('style');

        return view('snippets.index', compact('snippets', 'snippetType', 'label', 'styles'));
    }

    public function show(string $type, string $slug)
    {
        $snippet = CssSnippet::where('snippet_type', $type)->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $snippet->increment('view_count');

        $related = CssSnippet::where('snippet_type', $type)
            ->where('id', '!=', $snippet->id)
            ->where('is_active', true)
            ->take(4)->get();

        $label = CssSnippet::$typeLabels[$type];

        return view('snippets.show', compact('snippet', 'type', 'related', 'label'));
    }
}
