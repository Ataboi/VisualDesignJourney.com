@extends('layouts.app')
@section('title', 'Border Radius Builder')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:780px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">CSS Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Border Radius Builder</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Control each corner independently or use uniform radius.</p>

    <div style="display:flex;gap:8px;margin-bottom:32px;">
        <button onclick="setMode('uniform')" id="mode-uniform" class="filter-pill active" style="cursor:pointer;border:none;">Uniform</button>
        <button onclick="setMode('individual')" id="mode-individual" class="filter-pill" style="cursor:pointer;border:none;">Individual Corners</button>
    </div>

    <div id="uniform-controls">
        <label style="font-size:12px;font-weight:600;color:#454F5E;display:flex;justify-content:space-between;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;"><span>Radius</span><span id="u-val" style="color:#7F77DD;font-family:var(--font-mono);">16px</span></label>
        <input type="range" id="u-range" min="0" max="200" value="16" style="width:100%;accent-color:#7F77DD;margin-bottom:32px;" oninput="document.getElementById('u-val').textContent=this.value+'px';update()">
    </div>

    <div id="individual-controls" style="display:none;">
        @foreach([['tl','Top Left','16'],['tr','Top Right','16'],['br','Bottom Right','16'],['bl','Bottom Left','16']] as [$id,$lbl,$def])
        <div style="margin-bottom:18px;">
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:flex;justify-content:space-between;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;"><span>{{ $lbl }}</span><span id="{{ $id }}-val" style="color:#7F77DD;font-family:var(--font-mono);">{{ $def }}px</span></label>
            <input type="range" id="{{ $id }}-range" min="0" max="200" value="{{ $def }}" style="width:100%;accent-color:#7F77DD;" oninput="document.getElementById('{{ $id }}-val').textContent=this.value+'px';update()">
        </div>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;justify-content:center;padding:48px;background:#F8F9FA;border:.5px solid #E9ECEF;border-radius:14px;margin-bottom:24px;">
        <div id="preview-box" style="width:200px;height:120px;background:linear-gradient(135deg,#7F77DD,#7F77DD);"></div>
    </div>

    <div class="code-block">
        <div class="code-block-header"><span class="code-block-label">CSS</span><button onclick="copyBr(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="br-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;"></pre>
    </div>
</div>
@push('scripts')
<script>
let mode='uniform';
function setMode(m){mode=m;document.getElementById('mode-uniform').classList.toggle('active',m==='uniform');document.getElementById('mode-individual').classList.toggle('active',m==='individual');document.getElementById('uniform-controls').style.display=m==='uniform'?'block':'none';document.getElementById('individual-controls').style.display=m==='individual'?'block':'none';update();}
function update(){
    let v;
    if(mode==='uniform'){const r=document.getElementById('u-range').value;v=r+'px';document.getElementById('preview-box').style.borderRadius=v;document.getElementById('br-output').textContent=`border-radius: ${v};`;}
    else{const tl=document.getElementById('tl-range').value,tr=document.getElementById('tr-range').value,br=document.getElementById('br-range').value,bl=document.getElementById('bl-range').value;v=`${tl}px ${tr}px ${br}px ${bl}px`;document.getElementById('preview-box').style.borderRadius=v;document.getElementById('br-output').textContent=`border-radius: ${v};\n/* TL TR BR BL */`;}
}
function copyBr(btn){navigator.clipboard.writeText(document.getElementById('br-output').textContent.trim());btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
update();
</script>
@endpush
@endsection
