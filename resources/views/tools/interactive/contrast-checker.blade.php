@extends('layouts.app')
@section('title', 'Contrast Ratio Checker')
@section('meta_description', 'Check WCAG color contrast ratios instantly. Enter foreground and background colors to get pass/fail results for AA and AAA.')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:780px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Accessibility</p>
    <h1 class="page-title" style="margin:0 0 12px;">Contrast Checker</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Check WCAG 2.1 contrast ratios. Enter any two colors to get instant AA / AAA results.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" style="margin-bottom:32px;">
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#454F5E;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Foreground (Text)</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="color" id="fg-picker" value="#131B2E" style="width:44px;height:44px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                <input type="text" id="fg-hex" value="#131B2E" placeholder="#131B2E" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#454F5E;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Background</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="color" id="bg-picker" value="#FFFFFF" style="width:44px;height:44px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                <input type="text" id="bg-hex" value="#FFFFFF" placeholder="#FFFFFF" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            </div>
        </div>
    </div>

    {{-- Live preview --}}
    <div id="preview-box" style="border-radius:14px;padding:40px;margin-bottom:32px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:140px;transition:background 150ms;">
        <p id="preview-lg" style="font-size:2rem;font-weight:700;margin:0 0 8px;transition:color 150ms;">Large Text Sample</p>
        <p id="preview-sm" style="font-size:14px;margin:0;transition:color 150ms;">Normal body text at 14px weight 400</p>
    </div>

    {{-- Results --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6" style="margin-bottom:32px;">
        <div style="border:.5px solid #E9ECEF;border-radius:12px;padding:16px;text-align:center;">
            <p style="font-size:10px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 8px;">Ratio</p>
            <p id="ratio" style="font-size:1.75rem;font-weight:800;color:#131B2E;margin:0;font-family:var(--font-mono);">—</p>
        </div>
        <div style="border:.5px solid #E9ECEF;border-radius:12px;padding:16px;text-align:center;">
            <p style="font-size:10px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 8px;">AA Normal</p>
            <p id="aa-normal" style="font-size:1.1rem;font-weight:700;margin:0;">—</p>
        </div>
        <div style="border:.5px solid #E9ECEF;border-radius:12px;padding:16px;text-align:center;">
            <p style="font-size:10px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 8px;">AA Large</p>
            <p id="aa-large" style="font-size:1.1rem;font-weight:700;margin:0;">—</p>
        </div>
        <div style="border:.5px solid #E9ECEF;border-radius:12px;padding:16px;text-align:center;">
            <p style="font-size:10px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 8px;">AAA</p>
            <p id="aaa" style="font-size:1.1rem;font-weight:700;margin:0;">—</p>
        </div>
    </div>

    {{-- Quick presets --}}
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 12px;">Quick Presets</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach([['#FFFFFF','#000000','White on Black'],['#000000','#FFFFFF','Black on White'],['#131B2E','#FFFFFF','Light on Dark'],['#FFFFFF','#7F77DD','Dark on Acid'],['#FFFFFF','#3B82F6','White on Blue'],['#1A1A2E','#E2E2E2','Grey on Dark']] as [$fg,$bg,$label])
        <button onclick="setColors('{{ $fg }}','{{ $bg }}')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;display:flex;align-items:center;gap:8px;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $fg }};border:.5px solid #E9ECEF;"></span>
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $bg }};border:.5px solid #E9ECEF;"></span>
            {{ $label }}
        </button>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
function hexToRgb(hex) {
    hex = hex.replace('#','');
    if (hex.length === 3) hex = hex.split('').map(c=>c+c).join('');
    const n = parseInt(hex,16);
    return [n>>16&255, n>>8&255, n&255];
}
function linearize(c) {
    c /= 255;
    return c <= 0.03928 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4);
}
function luminance(hex) {
    const [r,g,b] = hexToRgb(hex).map(linearize);
    return 0.2126*r + 0.7152*g + 0.0722*b;
}
function contrast(fg, bg) {
    const l1 = luminance(fg), l2 = luminance(bg);
    const [hi,lo] = l1>l2 ? [l1,l2] : [l2,l1];
    return (hi+0.05)/(lo+0.05);
}
function pass(label, val, threshold) {
    const ok = val >= threshold;
    label.textContent = ok ? '✓ Pass' : '✗ Fail';
    label.style.color = ok ? '#6EE7B7' : '#FF4D4D';
}
function update() {
    const fg = document.getElementById('fg-hex').value.trim();
    const bg = document.getElementById('bg-hex').value.trim();
    if (!/^#[0-9a-fA-F]{3,6}$/.test(fg) || !/^#[0-9a-fA-F]{3,6}$/.test(bg)) return;
    const r = contrast(fg, bg);
    document.getElementById('ratio').textContent = r.toFixed(2) + ':1';
    pass(document.getElementById('aa-normal'), r, 4.5);
    pass(document.getElementById('aa-large'),  r, 3);
    pass(document.getElementById('aaa'),       r, 7);
    document.getElementById('preview-box').style.background = bg;
    document.getElementById('preview-lg').style.color = fg;
    document.getElementById('preview-sm').style.color = fg;
    document.getElementById('fg-picker').value = fg.length===7?fg:'#'+fg.replace('#','');
    document.getElementById('bg-picker').value = bg.length===7?bg:'#'+bg.replace('#','');
}
function setColors(fg, bg) {
    document.getElementById('fg-hex').value = fg;
    document.getElementById('bg-hex').value = bg;
    update();
}
document.getElementById('fg-hex').addEventListener('input', update);
document.getElementById('bg-hex').addEventListener('input', update);
document.getElementById('fg-picker').addEventListener('input', e=>{document.getElementById('fg-hex').value=e.target.value;update();});
document.getElementById('bg-picker').addEventListener('input', e=>{document.getElementById('bg-hex').value=e.target.value;update();});
update();
</script>
@endpush
@endsection
