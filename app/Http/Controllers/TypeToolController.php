<?php

namespace App\Http\Controllers;

use App\Models\TypeTool;
use Illuminate\Http\Request;

class TypeToolController extends Controller
{
    public function index(Request $request, string $type)
    {
        $query = TypeTool::where('is_active', true)->where('tool_type', $type);

        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"));
        }

        $tools = $query->latest()->paginate(12)->withQueryString();
        $label = TypeTool::$typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));

        return view('type-tools.index', compact('tools', 'type', 'label'));
    }

    public function show(string $type, string $slug)
    {
        $tool = TypeTool::where('tool_type', $type)->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $tool->increment('view_count');

        $related = TypeTool::where('tool_type', $type)
            ->where('id', '!=', $tool->id)
            ->where('is_active', true)
            ->take(4)->get();

        $label = TypeTool::$typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));

        return view('type-tools.show', compact('tool', 'type', 'related', 'label'));
    }
}
