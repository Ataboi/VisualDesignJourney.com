<?php

namespace App\Http\Controllers;

use App\Models\ColorReference;
use Illuminate\Http\Request;

class ColorReferenceController extends Controller
{
    public function index(Request $request, string $type)
    {
        $query = ColorReference::where('is_active', true)->where('reference_type', $type);

        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"));
        }

        if ($request->style) {
            $query->where('style', $request->style);
        }

        $references = $query->latest()->paginate(12)->withQueryString();
        $label      = ColorReference::$typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
        $styles     = ColorReference::where('reference_type', $type)->whereNotNull('style')->distinct()->pluck('style');

        return view('color-references.index', compact('references', 'type', 'label', 'styles'));
    }

    public function show(string $type, string $slug)
    {
        $reference = ColorReference::where('reference_type', $type)->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $reference->increment('view_count');

        $related = ColorReference::where('reference_type', $type)
            ->where('id', '!=', $reference->id)
            ->where('is_active', true)
            ->take(4)->get();

        $label = ColorReference::$typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));

        return view('color-references.show', compact('reference', 'type', 'related', 'label'));
    }
}
