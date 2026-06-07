@extends('layouts.app')
@section('title', 'Box Shadow Builder')
@section('meta_description', 'Build CSS box-shadows visually with live preview. Adjust offset, blur, spread, and color then copy the CSS.')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">CSS Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Box Shadow Builder</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Adjust every parameter visually and get the CSS in one click.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            @foreach([['X Offset','x','0','-80','80'],['Y Offset','y','8','-80','80'],['Blur','blur','24','0','120'],['Spread','spread','0','-40','40']] as [$lbl,$id,$def,$min,$max])
            <div style="margin-bottom:18px;">
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:flex;justify-content:space-between;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">
                    <span>{{ $lbl }}</span><span id="{{ $id }}-val" style="color:#7F77DD;font-family:var(--font-mono);">{{ $def }}px</span>
                </label>
                <input type="range" id="{{ $id }}" min="{{ $min }}" max="{{ $max }}" value="{{ $def }}" style="width:100%;accent-color:#7F77DD;" oninput="document.getElementById('{{ $id }}-val').textContent=this.value+'px';update()">
            </div>
            @endforeach
            <div style="margin-bottom:18px;">
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Color</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="color" id="shadow-color" value="#000000" oninput="update()" style="width:44px;height:44px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                    <input type="number" id="shadow-opacity" value="40" min="0" max="100" oninput="update()" style="width:80px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:8px 12px;font-size:13px;color:#131B2E;font-family:var(--font-mono);outline:none;">
                    <span style="font-size:12px;color:#787583;">% opacity</span>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Type</label>
                <div style="display:flex;gap:8px;">
                    <button onclick="setInset(false)" id="btn-outer" class="filter-pill active" style="cursor:pointer;border:none;">Outer</button>
                    <button onclick="setInset(true)"  id="btn-inner" class="filter-pill"        style="cursor:pointer;border:none;">Inner (inset)</button>
                </div>
            </div>
        </div>
        <div>
            <div style="display:flex;align-items:center;justify-content:center;background:#131B2E;border-radius:14px;padding:60px 40px;margin-bottom:16px;">
                <div id="shadow-preview" style="width:160px;height:100px;background:#ffffff;border-radius:12px;"></div>
            </div>
            <div class="code-block">
                <div class="code-block-header">
                    <span class="code-block-label">CSS</span>
                    <button onclick="copyShadow(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button>
                </div>
                <pre id="shadow-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
let isInset = false;
function setInset(v) {
    isInset = v;
    document.getElementById('btn-outer').classList.toggle('active',!v);
    document.getElementById('btn-inner').classList.toggle('active',v);
    update();
}
function hexToRgba(hex, opacity) {
    hex = hex.replace('#','');
    if (hex.length===3) hex=hex.split('').map(c=>c+c).join('');
    const r=parseInt(hex.slice(0,2),16),g=parseInt(hex.slice(2,4),16),b=parseInt(hex.slice(4,6),16);
    return `rgba(${r},${g},${b},${opacity/100})`;
}
function update() {
    const x=document.getElementById('x').value,y=document.getElementById('y').value,
          blur=document.getElementById('blur').value,spread=document.getElementById('spread').value,
          color=hexToRgba(document.getElementById('shadow-color').value,document.getElementById('shadow-opacity').value),
          prefix=isInset?'inset ':'';
    const val=`${prefix}${x}px ${y}px ${blur}px ${spread}px ${color}`;
    document.getElementById('shadow-preview').style.boxShadow=val;
    document.getElementById('shadow-output').textContent=`box-shadow: ${val};`;
}
function copyShadow(btn) {
    navigator.clipboard.writeText(document.getElementById('shadow-output').textContent.trim());
    btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';
    setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);
}
update();
</script>
@endpush
@endsection
