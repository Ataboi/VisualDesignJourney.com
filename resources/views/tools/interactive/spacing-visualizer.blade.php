@extends('layouts.app')
@section('title', 'Spacing Scale Visualizer')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:860px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Layout Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Spacing Scale Visualizer</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Visualise your spacing scale and generate Tailwind or CSS custom properties.</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8" style="margin-bottom:32px;">
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Base (px)</label>
            <input type="number" id="base" value="4" min="1" max="20" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Scale</label>
            <select id="scale-type" onchange="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;">
                <option value="tailwind">Tailwind (0,0.5,1…96)</option>
                <option value="linear">Linear ×1 through ×24</option>
                <option value="golden">Golden Ratio</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Unit</label>
            <select id="unit" onchange="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;">
                <option value="px">px</option>
                <option value="rem">rem</option>
            </select>
        </div>
    </div>

    <div id="visual-scale" style="display:flex;flex-direction:column;gap:6px;margin-bottom:32px;"></div>

    <div class="code-block">
        <div class="code-block-header"><span class="code-block-label">CSS Custom Properties</span><button onclick="copyScale(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="scale-output" style="padding:16px 20px;margin:0;font-size:11px;overflow-x:auto;max-height:300px;"></pre>
    </div>
</div>
@push('scripts')
<script>
function generate(){
    const base=parseFloat(document.getElementById('base').value)||4;
    const type=document.getElementById('scale-type').value;
    const unit=document.getElementById('unit').value;
    const divisor=unit==='rem'?16:1;
    let pairs=[];
    if(type==='tailwind'){const tw=[0,0.5,1,1.5,2,2.5,3,3.5,4,5,6,7,8,9,10,11,12,14,16,20,24,28,32,36,40,44,48,52,56,60,64,72,80,96];pairs=tw.map(k=>[k,`${(k*base/divisor).toFixed(unit==='rem'?3:0)}${unit}`]);}
    else if(type==='linear'){pairs=Array.from({length:24},(_,i)=>[(i+1),`${((i+1)*base/divisor).toFixed(unit==='rem'?3:0)}${unit}`]);}
    else{let v=base;pairs=[[1,`${(v/divisor).toFixed(unit==='rem'?3:0)}${unit}`]];for(let i=1;i<12;i++){v=v*1.618;pairs.push([i+1,`${(v/divisor).toFixed(unit==='rem'?3:0)}${unit}`]);}}
    const vis=document.getElementById('visual-scale');
    vis.innerHTML=pairs.slice(0,20).map(([k,v])=>`<div style="display:flex;align-items:center;gap:12px;"><span style="font-size:11px;font-family:var(--font-mono);color:#787583;width:40px;text-align:right;">${k}</span><div style="background:#7F77DD;opacity:0.7;height:12px;border-radius:2px;width:${Math.min(parseFloat(v)*2,600)}px;min-width:2px;"></div><span style="font-size:11px;font-family:var(--font-mono);color:#454F5E;">${v}</span></div>`).join('');
    document.getElementById('scale-output').textContent=`:root {\n`+pairs.map(([k,v])=>`  --space-${k}: ${v};`).join('\n')+'\n}';
}
function copyScale(btn){navigator.clipboard.writeText(document.getElementById('scale-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';},2000);}
generate();
</script>
@endpush
@endsection
