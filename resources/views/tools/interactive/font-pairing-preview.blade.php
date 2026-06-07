@extends('layouts.app')
@section('title', 'Font Pairing Preview')
@section('meta_description', 'Live-preview any Google Font combination. Type your own text and see heading + body fonts together before committing.')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Typography</p>
    <h1 class="page-title" style="margin:0 0 12px;">Font Pairing Preview</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Type any Google Font names and preview them live together.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" style="margin-bottom:32px;">
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Heading Font</label>
            <input type="text" id="heading-font" value="Playfair Display" list="font-suggestions" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'" oninput="loadFonts()">
            <div style="display:flex;gap:8px;margin-top:8px;align-items:center;">
                <label style="font-size:11px;color:#787583;">Weight</label>
                <select id="heading-weight" onchange="loadFonts()" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:6px;padding:4px 8px;font-size:12px;color:#131B2E;outline:none;font-family:var(--font-mono);">
                    @foreach([300,400,500,600,700,800,900] as $w)<option value="{{ $w }}" {{ $w==700?'selected':'' }}>{{ $w }}</option>@endforeach
                </select>
            </div>
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Body Font</label>
            <input type="text" id="body-font" value="Inter" list="font-suggestions" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'" oninput="loadFonts()">
            <div style="display:flex;gap:8px;margin-top:8px;align-items:center;">
                <label style="font-size:11px;color:#787583;">Weight</label>
                <select id="body-weight" onchange="loadFonts()" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:6px;padding:4px 8px;font-size:12px;color:#131B2E;outline:none;font-family:var(--font-mono);">
                    @foreach([300,400,500,600,700] as $w)<option value="{{ $w }}" {{ $w==400?'selected':'' }}>{{ $w }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>

    <div style="margin-bottom:32px;">
        <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Preview Text</label>
        <input type="text" id="preview-text" value="Design with clear intention." style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'" oninput="render()">
    </div>

    <div id="font-preview" style="border:.5px solid #E9ECEF;border-radius:14px;padding:48px;margin-bottom:24px;background:#F8F9FA;">
        <p id="preview-heading" style="font-size:3rem;line-height:1.05;color:#131B2E;margin:0 0 20px;letter-spacing:0;">Design with clear intention.</p>
        <p id="preview-body" style="font-size:16px;line-height:1.75;color:#454F5E;margin:0;">The body text flows naturally alongside a strong heading to create clear visual hierarchy. Good typography communicates before the reader even begins to read the words.</p>
    </div>

    <datalist id="font-suggestions">
        @foreach(['Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Raleway','Oswald','Merriweather','Playfair Display','Cormorant Garamond','EB Garamond','Lora','Libre Baskerville','DM Sans','Space Grotesk','Syne','Fraunces','Bebas Neue','Nunito','Work Sans','Outfit','Josefin Sans','Figtree','Plus Jakarta Sans','Albert Sans','Manrope','Barlow','Source Sans 3','Crimson Pro','Spectral','Abril Fatface','Rubik','Mulish','Cabin','Jost'] as $f)
        <option value="{{ $f }}">
        @endforeach
    </datalist>
</div>
@push('scripts')
<script>
let loaded = new Set();
function loadFonts() {
    const hf = document.getElementById('heading-font').value.trim();
    const bf = document.getElementById('body-font').value.trim();
    const hw = document.getElementById('heading-weight').value;
    const bw = document.getElementById('body-weight').value;
    [hf,bf].forEach(f => {
        if (f && !loaded.has(f)) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(f)}:wght@300;400;500;600;700;800;900&display=swap`;
            document.head.appendChild(link);
            loaded.add(f);
        }
    });
    render();
}
function render() {
    const hf = document.getElementById('heading-font').value;
    const bf = document.getElementById('body-font').value;
    const hw = document.getElementById('heading-weight').value;
    const bw = document.getElementById('body-weight').value;
    const text = document.getElementById('preview-text').value || 'Design with clear intention.';
    document.getElementById('preview-heading').style.fontFamily = `'${hf}', serif`;
    document.getElementById('preview-heading').style.fontWeight = hw;
    document.getElementById('preview-heading').textContent = text;
    document.getElementById('preview-body').style.fontFamily = `'${bf}', sans-serif`;
    document.getElementById('preview-body').style.fontWeight = bw;
}
loadFonts();
</script>
@endpush
@endsection
