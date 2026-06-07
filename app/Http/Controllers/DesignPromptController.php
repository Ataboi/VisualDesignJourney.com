<?php

namespace App\Http\Controllers;

use App\Models\DesignPrompt;
use App\Models\Tag;
use Illuminate\Http\Request;

class DesignPromptController extends Controller
{
    public function index(Request $request)
    {
        $query = DesignPrompt::where('is_active', true)->with(['category', 'tags']);

        if ($request->search) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$request->search}%")
                ->orWhere('prompt', 'like', "%{$request->search}%"));
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->difficulty) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->tag) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $prompts = $query->latest()->paginate(15)->withQueryString();
        $tags = Tag::where('is_active', true)->get();
        $types = ['visual', 'ui', 'branding', 'motion', 'illustration', 'typography'];
        $difficulties = ['easy', 'medium', 'hard'];

        return view('design-prompts.index', compact('prompts', 'tags', 'types', 'difficulties'));
    }

    public function show(DesignPrompt $designPrompt)
    {
        $designPrompt->increment('view_count');
        $related = DesignPrompt::where('is_active', true)
            ->where('id', '!=', $designPrompt->id)
            ->where('type', $designPrompt->type)
            ->take(4)->get();
        return view('design-prompts.show', compact('designPrompt', 'related'));
    }
}
