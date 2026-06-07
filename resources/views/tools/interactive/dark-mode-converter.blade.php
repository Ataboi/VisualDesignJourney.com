@extends('layouts.app')
@section('title', 'Dark Mode Palette Converter')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:860px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Color Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Dark Mode Converter</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Paste your light palette and get dark-mode equivalents using lightness inversion and surface remapping.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <span style="font-size:12px;font-weight:600;color:#454F5E;text-transform:uppercase;letter-spacing:0.06em;">Light Colors</span>
                <button onclick="addColor()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">+ Add</button>
            </div>
            <div id="light-inputs" style="display:flex;flex-direction:column;gap:8px;"></div>
        </div>
        <div>
            <p style="font-size:12px;font-weight:600;color:#454F5E;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 12px;">Dark Equivalents</p>
            <div id="dark-outputs" style="display:flex;flex-direction:column;gap:8px;"></div>
        </div>
    </div>

    <div class="code-block" style="margin-bottom:12px;">
        <div class="code-block-header"><span class="code-block-label">CSS (light + dark)</span><button onclick="copyCss(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="css-out" style="padding:14px 20px;margin:0;font-size:12px;overflow-x:auto;max-height:360px;white-space:pre-wrap;"></pre>
    </div>

    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:24px 0 12px;">Load Preset</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button onclick="loadPreset('brand')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">Brand palette</button>
        <button onclick="loadPreset('neutral')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">Neutral grays</button>
        <button onclick="loadPreset('warm')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">Warm tones</button>
    </div>
</div>
@push('scripts')
<script>
let colors=[];
let cId=0;
const PRESETS={
    brand:[{name:'background',hex:'#FFFFFF'},{name:'surface',hex:'#F8F9FA'},{name:'border',hex:'#E2E8F0'},{name:'text',hex:'#0F172A'},{name:'muted',hex:'#64748B'},{name:'primary',hex:'#6366F1'}],
    neutral:[{name:'gray-50',hex:'#F9FAFB'},{name:'gray-100',hex:'#F3F4F6'},{name:'gray-200',hex:'#E5E7EB'},{name:'gray-400',hex:'#9CA3AF'},{name:'gray-700',hex:'#374151'},{name:'gray-900',hex:'#111827'}],
    warm:[{name:'background',hex:'#FFFBF5'},{name:'surface',hex:'#FEF3E2'},{name:'border',hex:'#FDDCB5'},{name:'text',hex:'#1C0A00'},{name:'muted',hex:'#8B6038'},{name:'accent',hex:'#F97316'}],
};

function hexToHsl(hex){hex=hex.replace('#','');if(hex.length===3)hex=hex.split('').map(c=>c+c).join('');let r=parseInt(hex.slice(0,2),16)/255,g=parseInt(hex.slice(2,4),16)/255,b=parseInt(hex.slice(4,6),16)/255;const max=Math.max(r,g,b),min=Math.min(r,g,b);let h,s,l=(max+min)/2;if(max===min){h=s=0;}else{const d=max-min;s=l>0.5?d/(2-max-min):d/(max+min);switch(max){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;case b:h=(r-g)/d+4;}h/=6;}return[h*360,s*100,l*100];}
function hslToHex(h,s,l){h/=360;s/=100;l/=100;function f(n){const k=(n+h*12)%12;const a=s*Math.min(l,1-l);return l-a*Math.max(-1,Math.min(k-3,Math.min(9-k,1)));}return'#'+[f(0),f(8),f(4)].map(x=>Math.round(x*255).toString(16).padStart(2,'0')).join('');}
function toDark(hex){const [h,s,l]=hexToHsl(hex);const dl=l>50?100-l:Math.min(l+8,90);return hslToHex(h,s,dl);}

function addColor(name='',hex='#FFFFFF'){
    const id=++cId;
    colors.push({id,name,hex});
    render();
}
function removeColor(id){colors=colors.filter(c=>c.id!==id);render();}
function updateColor(id,key,val){const c=colors.find(c=>c.id===id);if(c)c[key]=val;renderOutputs();}
function loadPreset(key){colors=PRESETS[key].map(p=>({id:++cId,...p}));render();}

function render(){
    const li=document.getElementById('light-inputs');
    li.innerHTML=colors.map(c=>`
        <div style="display:flex;align-items:center;gap:6px;">
            <input type="color" value="${c.hex}" oninput="updateColor(${c.id},'hex',this.value);document.getElementById('hex-${c.id}').value=this.value;" style="width:32px;height:32px;border:.5px solid #E9ECEF;border-radius:6px;background:#FFFFFF;cursor:pointer;padding:2px;flex-shrink:0;">
            <input type="text" id="hex-${c.id}" value="${c.hex}" oninput="updateColor(${c.id},'hex',this.value)" style="width:88px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:6px 8px;font-size:12px;color:#131B2E;font-family:var(--font-mono);outline:none;">
            <input type="text" placeholder="name" value="${c.name}" oninput="updateColor(${c.id},'name',this.value)" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:6px 8px;font-size:12px;color:#454F5E;font-family:inherit;outline:none;">
            <button onclick="removeColor(${c.id})" style="background:none;border:none;color:#787583;cursor:pointer;font-size:16px;line-height:1;padding:0 4px;">×</button>
        </div>
    `).join('');
    renderOutputs();
}
function renderOutputs(){
    const do_=document.getElementById('dark-outputs');
    do_.innerHTML=colors.map(c=>{const d=toDark(c.hex);return`
        <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="navigator.clipboard.writeText('${d}')">
            <div style="width:32px;height:32px;border-radius:6px;background:${d};border:.5px solid #E9ECEF;flex-shrink:0;"></div>
            <span style="font-size:12px;font-family:var(--font-mono);color:#454F5E;">${d}</span>
        </div>
    `;}).join('');
    // CSS output
    const lightVars=colors.map(c=>`  --${c.name||'color-'+c.id}: ${c.hex};`).join('\n');
    const darkVars=colors.map(c=>`  --${c.name||'color-'+c.id}: ${toDark(c.hex)};`).join('\n');
    document.getElementById('css-out').textContent=`:root {\n${lightVars}\n}\n\n@media (prefers-color-scheme: dark) {\n  :root {\n${darkVars.split('\n').map(l=>'  '+l).join('\n')}\n  }\n}`;
}
function copyCss(btn){navigator.clipboard.writeText(document.getElementById('css-out').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}

loadPreset('brand');
</script>
@endpush
@endsection
