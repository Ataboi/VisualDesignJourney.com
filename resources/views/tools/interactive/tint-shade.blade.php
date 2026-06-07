@extends('layouts.app')
@section('title', 'Tint & Shade Generator')
@section('meta_description', 'Generate a full 50–950 color scale from any base hex color. Copy as CSS variables or Tailwind config.')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:860px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Color Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Tint & Shade Generator</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Enter a base color to generate a full 50–950 scale. Copy as CSS variables or Tailwind config.</p>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:40px;flex-wrap:wrap;">
        <input type="color" id="base-picker" value="#7F77DD" oninput="document.getElementById('base-hex').value=this.value;generate()" style="width:52px;height:52px;border:.5px solid #E9ECEF;border-radius:10px;background:#FFFFFF;cursor:pointer;padding:3px;">
        <input type="text" id="base-hex" value="#7F77DD" placeholder="#7F77DD" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:15px;color:#131B2E;font-family:var(--font-mono);outline:none;width:140px;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'" oninput="syncPicker();generate()">
        <input type="text" id="scale-name" value="primary" placeholder="variable name" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:15px;color:#131B2E;font-family:var(--font-mono);outline:none;width:140px;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'" oninput="generate()">
    </div>

    <div id="scale-strip" style="display:grid;grid-template-columns:repeat(10,1fr);gap:4px;margin-bottom:24px;border-radius:10px;overflow:hidden;"></div>

    <div id="scale-labels" style="display:grid;grid-template-columns:repeat(10,1fr);gap:4px;margin-bottom:32px;"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="code-block">
            <div class="code-block-header"><span class="code-block-label">CSS Variables</span><button onclick="copyCss(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
            <pre id="css-out" style="padding:16px 20px;margin:0;font-size:11px;overflow-x:auto;max-height:260px;"></pre>
        </div>
        <div class="code-block">
            <div class="code-block-header"><span class="code-block-label">Tailwind Config</span><button onclick="copyTw(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
            <pre id="tw-out" style="padding:16px 20px;margin:0;font-size:11px;overflow-x:auto;max-height:260px;"></pre>
        </div>
    </div>
</div>
@push('scripts')
<script>
const steps = [50,100,200,300,400,500,600,700,800,900];
function hexToHsl(hex) {
    hex=hex.replace('#','');if(hex.length===3)hex=hex.split('').map(c=>c+c).join('');
    let r=parseInt(hex.slice(0,2),16)/255,g=parseInt(hex.slice(2,4),16)/255,b=parseInt(hex.slice(4,6),16)/255;
    const max=Math.max(r,g,b),min=Math.min(r,g,b);let h,s,l=(max+min)/2;
    if(max===min){h=s=0;}else{const d=max-min;s=l>0.5?d/(2-max-min):d/(max+min);
    switch(max){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;case b:h=(r-g)/d+4;}h/=6;}
    return [h*360,s*100,l*100];
}
function hslToHex(h,s,l) {
    h/=360;s/=100;l/=100;
    function f(n){const k=(n+h*12)%12;const a=s*Math.min(l,1-l);return l-a*Math.max(-1,Math.min(k-3,Math.min(9-k,1)));}
    return '#'+[f(0),f(8),f(4)].map(x=>Math.round(x*255).toString(16).padStart(2,'0')).join('');
}
function generate() {
    const hex=document.getElementById('base-hex').value.trim();
    if(!/^#[0-9a-fA-F]{6}$/.test(hex))return;
    const [h,s,l]=hexToHsl(hex);
    const name=document.getElementById('scale-name').value.trim()||'primary';
    const strip=document.getElementById('scale-strip'),labels=document.getElementById('scale-labels');
    strip.innerHTML='';labels.innerHTML='';
    const colors={};
    steps.forEach((step,i)=>{
        const lightness=95-(i*(95-8)/9);
        const c=hslToHex(h,s,lightness);
        colors[step]=c;
        strip.innerHTML+=`<div onclick="navigator.clipboard.writeText('${c}')" title="${step}: ${c}" style="height:48px;background:${c};cursor:pointer;border-radius:4px;" onmouseover="this.style.transform='scaleY(1.1)'" onmouseout="this.style.transform='scaleY(1)'"></div>`;
        labels.innerHTML+=`<div style="text-align:center;"><p style="font-size:9px;color:#787583;margin:0 0 2px;">${step}</p><p style="font-size:9px;font-family:var(--font-mono);color:#454F5E;margin:0;">${c}</p></div>`;
    });
    document.getElementById('css-out').textContent=`:root {\n`+steps.map(s=>`  --${name}-${s}: ${colors[s]};`).join('\n')+'\n}';
    document.getElementById('tw-out').textContent=`colors: {\n  '${name}': {\n`+steps.map(s=>`    ${s}: '${colors[s]}',`).join('\n')+'\n  }\n}';
}
function syncPicker(){document.getElementById('base-picker').value=document.getElementById('base-hex').value.length===7?document.getElementById('base-hex').value:'#000000';}
function copyCss(btn){navigator.clipboard.writeText(document.getElementById('css-out').textContent);flash(btn);}
function copyTw(btn){navigator.clipboard.writeText(document.getElementById('tw-out').textContent);flash(btn);}
function flash(btn){btn.textContent='Copied!';btn.style.color='#7F77DD';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';},2000);}
generate();
</script>
@endpush
@endsection
