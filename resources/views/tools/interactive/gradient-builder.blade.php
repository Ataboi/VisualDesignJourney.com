@extends('layouts.app')
@section('title', 'CSS Gradient Builder')
@section('meta_description', 'Build CSS gradients visually. Linear, radial, and conic gradients with live preview and copy-ready code.')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">CSS Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Gradient Builder</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Build linear, radial, or conic gradients with a live preview.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Controls --}}
        <div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Type</label>
                <div style="display:flex;gap:8px;">
                    @foreach(['linear','radial','conic'] as $t)
                    <button onclick="setType('{{ $t }}')" id="type-{{ $t }}" class="filter-pill {{ $t==='linear'?'active':'' }}" style="cursor:pointer;border:none;">{{ ucfirst($t) }}</button>
                    @endforeach
                </div>
            </div>

            <div id="angle-row" style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Angle: <span id="angle-val">135°</span></label>
                <input type="range" id="angle" min="0" max="360" value="135" style="width:100%;accent-color:#7F77DD;" oninput="document.getElementById('angle-val').textContent=this.value+'°';update()">
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.06em;">Color Stops</label>
                <div id="stops">
                    <div class="stop-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                        <input type="color" value="#7F77DD" oninput="update()" style="width:40px;height:36px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                        <input type="range" min="0" max="100" value="0" oninput="update()" style="flex:1;accent-color:#7F77DD;">
                        <span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#787583;width:36px;">0%</span>
                    </div>
                    <div class="stop-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                        <input type="color" value="#7F77DD" oninput="update()" style="width:40px;height:36px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                        <input type="range" min="0" max="100" value="100" oninput="update()" style="flex:1;accent-color:#7F77DD;">
                        <span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#787583;width:36px;">100%</span>
                    </div>
                </div>
                <button onclick="addStop()" class="btn-secondary" style="font-size:12px;padding:7px 14px;margin-top:8px;">+ Add Stop</button>
            </div>
        </div>

        {{-- Preview + output --}}
        <div>
            <div id="preview" style="width:100%;aspect-ratio:1;border-radius:14px;margin-bottom:16px;border:.5px solid #E9ECEF;"></div>
            <div class="code-block">
                <div class="code-block-header">
                    <span class="code-block-label">CSS</span>
                    <button onclick="copyGradient(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
                </div>
                <pre id="gradient-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
let gradType = 'linear';
function setType(t) {
    gradType = t;
    ['linear','radial','conic'].forEach(x => {
        const b = document.getElementById('type-'+x);
        b.classList.toggle('active', x===t);
    });
    document.getElementById('angle-row').style.display = t==='linear'?'block':'none';
    update();
}
function getStops() {
    return [...document.querySelectorAll('.stop-row')].map(row => {
        const color = row.querySelector('input[type=color]').value;
        const pct = row.querySelector('input[type=range]').value;
        row.querySelector('.stop-pct').textContent = pct+'%';
        return `${color} ${pct}%`;
    });
}
function update() {
    const stops = getStops().join(', ');
    let css;
    if (gradType==='linear') css = `linear-gradient(${document.getElementById('angle').value}deg, ${stops})`;
    else if (gradType==='radial') css = `radial-gradient(circle, ${stops})`;
    else css = `conic-gradient(${stops})`;
    document.getElementById('preview').style.background = css;
    document.getElementById('gradient-output').textContent = `background: ${css};`;
}
function addStop() {
    const row = document.createElement('div');
    row.className = 'stop-row';
    row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;';
    row.innerHTML = `<input type="color" value="#574EB1" oninput="update()" style="width:40px;height:36px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;"><input type="range" min="0" max="100" value="50" oninput="update()" style="flex:1;accent-color:#7F77DD;"><span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#787583;width:36px;">50%</span>`;
    document.getElementById('stops').appendChild(row);
    update();
}
function copyGradient(btn) {
    navigator.clipboard.writeText(document.getElementById('gradient-output').textContent.trim());
    btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';
    setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);
}
update();
</script>
@endpush
@endsection
