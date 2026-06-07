<?php

namespace App\Http\Controllers;

use App\Models\ColorPalette;
use App\Models\DesignPrompt;
use App\Models\FontPairing;
use App\Models\WebsiteSection;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'featuredFonts' => FontPairing::where('is_active', true)->where('is_featured', true)->latest()->take(6)->get(),
            'featuredPalettes' => ColorPalette::where('is_active', true)->where('is_featured', true)->latest()->take(8)->get(),
            'latestPrompts' => DesignPrompt::where('is_active', true)->latest()->take(6)->get(),
            'featuredSections' => WebsiteSection::where('is_active', true)->where('is_featured', true)->latest()->take(6)->get(),
        ]);
    }

    public function about()
    {
        return view('about');
    }

    public function tools()
    {
        return view('tools');
    }
}
