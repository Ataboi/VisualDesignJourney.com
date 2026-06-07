@extends('layouts.app')
@section('title', 'Palette Harmony Generator')
@section('meta_description', 'Generate complementary, analogous, triadic, and split-complementary color palettes from any base color.')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:860px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Color Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Palette Harmony</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Generate harmonious color palettes from any base color. Click any swatch to copy.</p>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:32px;flex-wrap:wrap;">
        <input type="color" id="base-picker" value="#7F77DD" oninput="document.getElementById('base-hex').value=this.value;generate()" style="width:52px;height:52px;border:.5px solid #E9ECEF;border-radius:10px;background:#FFFFFF;cursor:pointer;padding:3px;">
        <input type="text" id="base-hex" value="#7F77DD" placeholder="#RRGGBB" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:15px;color:#131B2E;font-family:var(--font-mono);outline:none;width:140px;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'" oninput="syncPicker();generate()">
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:40px;">
        @foreach(['complementary','analogous','triadic','split','tetradic','monochromatic'] as $h)
        <button onclick="setHarmony('{{ $h }}')" id="h-{{ $h }}" class="filter-pill {{ $h==='complementary'?'active':'' }}" style="cursor:pointer;border:none;">{{ ucfirst($h) }}</button>
        @endforeach
    </div>

    <div id="harmony-result" style="display:grid;gap:16px;margin-bottom:32px;"></div>
</div>
@push('scripts')
<script>
let harmony='complementary';
function setHarmony(h){harmony=h;document.querySelectorAll('[id^=h-]').forEach(b=>b.classList.toggle('active',b.id==='h-'+h));generate();}
function hexToHsl(hex){hex=hex.replace('#','');if(hex.length===3)hex=hex.split('').map(c=>c+c).join('');let r=parseInt(hex.slice(0,2),16)/255,g=parseInt(hex.slice(2,4),16)/255,b=parseInt(hex.slice(4,6),16)/255;const max=Math.max(r,g,b),min=Math.min(r,g,b);let h,s,l=(max+min)/2;if(max===min){h=s=0;}else{const d=max-min;s=l>0.5?d/(2-max-min):d/(max+min);switch(max){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;case b:h=(r-g)/d+4;}h/=6;}return[h*360,s*100,l*100];}
function hslToHex(h,s,l){h/=360;s/=100;l/=100;function f(n){const k=(n+h*12)%12;const a=s*Math.min(l,1-l);return l-a*Math.max(-1,Math.min(k-3,Math.min(9-k,1)));}return'#'+[f(0),f(8),f(4)].map(x=>Math.round(x*255).toString(16).padStart(2,'0')).join('');}
function harmonyColors(h,s,l,type){const r=(x)=>((x%360)+360)%360;const palettes={complementary:[[h,s,l],[r(h+180),s,l]],analogous:[[r(h-30),s,l],[h,s,l],[r(h+30),s,l],[r(h+60),s,l]],triadic:[[h,s,l],[r(h+120),s,l],[r(h+240),s,l]],split:[[h,s,l],[r(h+150),s,l],[r(h+210),s,l]],tetradic:[[h,s,l],[r(h+90),s,l],[r(h+180),s,l],[r(h+270),s,l]],monochromatic:[[h,s,90],[h,s,70],[h,s,50],[h,s,30],[h,s,15]]};return(palettes[type]||palettes.complementary).map(([hh,ss,ll])=>hslToHex(hh,ss,ll));}
function generate(){const hex=document.getElementById('base-hex').value.trim();if(!/^#[0-9a-fA-F]{6}$/.test(hex))return;const [h,s,l]=hexToHsl(hex);const colors=harmonyColors(h,s,l,harmony);const el=document.getElementById('harmony-result');el.style.gridTemplateColumns=`repeat(${colors.length},1fr)`;el.innerHTML=colors.map(c=>`<div onclick="copyColor('${c}',this)" style="cursor:pointer;border-radius:12px;overflow:hidden;border:.5px solid #E9ECEF;" onmouseover="this.style.borderColor='rgba(127,119,221,0.4)'" onmouseout="this.style.borderColor='#E9ECEF'"><div style="height:100px;background:${c};"></div><div style="padding:10px 12px;background:#FFFFFF;"><p style="font-size:12px;font-family:var(--font-mono);color:#454F5E;margin:0;">${c}</p></div></div>`).join('');}
function copyColor(c,el){navigator.clipboard.writeText(c);const p=el.querySelector('p');const orig=p.textContent;p.textContent='Copied!';p.style.color='#7F77DD';setTimeout(()=>{p.textContent=orig;p.style.color='';},1500);}
function syncPicker(){document.getElementById('base-picker').value=document.getElementById('base-hex').value.length===7?document.getElementById('base-hex').value:'#000000';}
generate();
</script>
@endpush
@endsection
