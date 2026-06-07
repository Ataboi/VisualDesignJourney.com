<?php

namespace App\Http\Controllers;

use App\Models\BriefTemplate;
use Illuminate\Http\Request;

class BriefController extends Controller
{
    public function index()
    {
        $templates = BriefTemplate::where('is_active', true)->latest()->get();
        return view('briefs.index', compact('templates'));
    }

    public function show(BriefTemplate $briefTemplate)
    {
        return view('briefs.show', compact('briefTemplate'));
    }

    public function generate(Request $request, BriefTemplate $briefTemplate)
    {
        $briefTemplate->increment('use_count');
        $inputs = $request->only(array_column($briefTemplate->fields, 'key'));
        return view('briefs.result', compact('briefTemplate', 'inputs'));
    }
}
