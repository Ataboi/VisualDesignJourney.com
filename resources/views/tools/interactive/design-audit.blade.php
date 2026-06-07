@extends('layouts.app')
@section('title', 'Design Audit Checklist')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:800px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">UX Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Design Audit Checklist</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 12px;">A structured checklist for auditing digital products. Check items as you review — copy the report when done.</p>

    <div style="display:flex;gap:16px;align-items:center;margin-bottom:40px;">
        <div id="progress-bar-wrap" style="flex:1;height:6px;background:#1A1A24;border-radius:3px;overflow:hidden;">
            <div id="progress-bar" style="height:100%;background:#7F77DD;width:0%;transition:width 0.3s;border-radius:3px;"></div>
        </div>
        <span id="progress-text" style="font-size:12px;font-family:var(--font-mono);color:#787583;white-space:nowrap;">0 / 0</span>
    </div>

    @php
    $categories = [
        'Visual Design' => [
            'Consistent color palette with defined roles (bg, surface, border, text)',
            'Typography scale is clear — ≤ 3 type sizes used per screen',
            'Spacing follows a consistent scale (e.g. 4px base)',
            'Icon style is unified across the product',
            'All text meets minimum contrast ratio (4.5:1 for body)',
            'Focus states are visible on all interactive elements',
            'Hover/active states are defined for all clickable elements',
            'Images have defined aspect ratios, no layout-breaking sizes',
        ],
        'Layout & Structure' => [
            'Clear visual hierarchy — headings, subheadings, body structured correctly',
            'Content uses a consistent grid or alignment system',
            'Responsive at 320px, 768px, 1024px, 1440px breakpoints',
            'No horizontal scrollbar at any standard breakpoint',
            'Touch targets are at least 44×44px on mobile',
            'Whitespace is generous and intentional — not accidental',
        ],
        'Navigation & Flows' => [
            'Current page / active state is clearly indicated in navigation',
            'Back/cancel paths exist for all multi-step flows',
            'Error states are defined for all form fields',
            'Empty states are designed (not just blank screens)',
            'Loading states or skeleton screens are present',
            '404 and 500 error pages are designed',
        ],
        'Content & Copy' => [
            'All placeholder text replaced with real or realistic content',
            'Microcopy is consistent in tone and tense',
            'Button labels use action verbs (not "Submit")',
            'Error messages are human-readable — not system errors',
            'Success/confirmation messages are present after key actions',
        ],
        'Accessibility' => [
            'All images have meaningful alt text',
            'Form labels are associated with their inputs',
            'Color is not the only indicator of meaning',
            'Reading order makes sense without CSS',
            'ARIA roles used where needed for dynamic content',
            'Motion respects prefers-reduced-motion',
        ],
        'Performance & Technical' => [
            'No unoptimised images (use WebP / AVIF)',
            'Fonts are limited — max 2 families, ≤ 4 weights',
            'Animations are < 300ms for micro-interactions',
            'Critical above-fold content loads within 2s on 4G',
        ],
    ];
    @endphp

    @foreach($categories as $cat => $items)
    <div style="margin-bottom:32px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #1A1A24;">
            <h3 style="font-size:14px;font-weight:700;color:#131B2E;margin:0;">{{ $cat }}</h3>
            <span class="cat-score" style="font-size:11px;font-family:var(--font-mono);color:#787583;">0/{{ count($items) }}</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($items as $item)
            <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;padding:10px 14px;border:1px solid #1A1A24;border-radius:10px;transition:border-color 0.15s;" onmouseover="this.style.borderColor='#E9ECEF'" onmouseout="this.style.borderColor=this.querySelector('input').checked?'rgba(127,119,221,0.2)':'#1A1A24'">
                <input type="checkbox" onchange="updateProgress()" style="margin-top:2px;accent-color:#7F77DD;width:16px;height:16px;flex-shrink:0;cursor:pointer;">
                <span style="font-size:14px;color:#454F5E;line-height:1.5;">{{ $item }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach

    <div style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;">
        <button onclick="copyReport()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">Copy Report</button>
        <button onclick="resetAll()" style="background:#FFFFFF;color:#131B2E;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">Reset</button>
    </div>
</div>
@push('scripts')
<script>
function updateProgress(){
    const all=document.querySelectorAll('input[type=checkbox]');
    const checked=[...all].filter(c=>c.checked).length;
    const pct=all.length?Math.round(checked/all.length*100):0;
    document.getElementById('progress-bar').style.width=pct+'%';
    document.getElementById('progress-text').textContent=`${checked} / ${all.length}`;
    // Update category scores
    document.querySelectorAll('[style*="margin-bottom:32px"]').forEach(section=>{
        const boxes=section.querySelectorAll('input[type=checkbox]');
        const cnt=section.querySelectorAll('input[type=checkbox]:checked').length;
        const score=section.querySelector('.cat-score');
        if(score)score.textContent=`${cnt}/${boxes.length}`;
    });
    // Update label border colors
    document.querySelectorAll('label').forEach(lbl=>{
        const cb=lbl.querySelector('input[type=checkbox]');
        if(cb)lbl.style.borderColor=cb.checked?'rgba(127,119,221,0.2)':'#1A1A24';
    });
}
function copyReport(){
    const lines=['# Design Audit Report','Generated by VibeKit Design Audit Tool','Date: '+new Date().toLocaleDateString(),''];
    document.querySelectorAll('[style*="margin-bottom:32px"]').forEach(section=>{
        const h=section.querySelector('h3');
        if(!h)return;
        lines.push('## '+h.textContent.trim());
        section.querySelectorAll('label').forEach(lbl=>{
            const cb=lbl.querySelector('input[type=checkbox]');
            const txt=lbl.querySelector('span').textContent.trim();
            lines.push(`${cb.checked?'[x]':'[ ]'} ${txt}`);
        });
        lines.push('');
    });
    navigator.clipboard.writeText(lines.join('\n'));
}
function resetAll(){if(confirm('Reset all checkboxes?')){document.querySelectorAll('input[type=checkbox]').forEach(c=>c.checked=false);updateProgress();}}
updateProgress();
</script>
@endpush
@endsection
