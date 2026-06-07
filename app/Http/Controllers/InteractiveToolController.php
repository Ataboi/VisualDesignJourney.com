<?php

namespace App\Http\Controllers;

class InteractiveToolController extends Controller
{
    public function contrastChecker()    { return view('tools.interactive.contrast-checker'); }
    public function gradientBuilder()    { return view('tools.interactive.gradient-builder'); }
    public function shadowBuilder()      { return view('tools.interactive.shadow-builder'); }
    public function borderRadiusBuilder(){ return view('tools.interactive.border-radius-builder'); }
    public function spacingVisualizer()  { return view('tools.interactive.spacing-visualizer'); }
    public function paletteHarmony()     { return view('tools.interactive.palette-harmony'); }
    public function faviconGenerator()   { return view('tools.interactive.favicon-generator'); }
    public function ogImageGenerator()   { return view('tools.interactive.og-image-generator'); }
    public function readmeBadges()       { return view('tools.interactive.readme-badges'); }
    public function keyframeBuilder()    { return view('tools.interactive.keyframe-builder'); }
    public function responsivePreview()  { return view('tools.interactive.responsive-preview'); }
    public function tailwindTheme()      { return view('tools.interactive.tailwind-theme'); }
    public function colorExtractor()     { return view('tools.interactive.color-extractor'); }
    public function tintShadeGenerator() { return view('tools.interactive.tint-shade'); }
    public function darkModeConverter()  { return view('tools.interactive.dark-mode-converter'); }
    public function fontPairingPreview() { return view('tools.interactive.font-pairing-preview'); }
    public function designAudit()        { return view('tools.interactive.design-audit'); }
    public function abHypothesis()       { return view('tools.interactive.ab-hypothesis'); }
    public function componentProps()     { return view('tools.interactive.component-props'); }
    public function tailwindCheatsheet() { return view('tools.interactive.tailwind-cheatsheet'); }
}
