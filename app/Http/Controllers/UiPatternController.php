<?php

namespace App\Http\Controllers;

use App\Models\UiPattern;
use Illuminate\Http\Request;

class UiPatternController extends Controller
{
    public function index(Request $request, string $type)
    {
        $query = UiPattern::where('is_active', true)->where('pattern_type', $type);

        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"));
        }

        if ($request->style) {
            $query->where('style', $request->style);
        }

        if ($request->framework) {
            $query->where('framework', $request->framework);
        }

        $patterns  = $query->latest()->paginate(12)->withQueryString();
        $label     = UiPattern::$typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
        $styles    = UiPattern::where('pattern_type', $type)->whereNotNull('style')->distinct()->pluck('style');
        $frameworks = UiPattern::where('pattern_type', $type)->whereNotNull('framework')->distinct()->pluck('framework');

        return view('ui-patterns.index', compact('patterns', 'type', 'label', 'styles', 'frameworks'));
    }

    public function show(string $type, string $slug)
    {
        $pattern = UiPattern::where('pattern_type', $type)->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $pattern->increment('view_count');

        $related = UiPattern::where('pattern_type', $type)
            ->where('id', '!=', $pattern->id)
            ->where('is_active', true)
            ->take(4)->get();

        $label = UiPattern::$typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));

        return view('ui-patterns.show', compact('pattern', 'type', 'related', 'label'));
    }
}
