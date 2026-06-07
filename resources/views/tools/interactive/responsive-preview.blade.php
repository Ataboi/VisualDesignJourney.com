@extends('layouts.app')
@section('title', 'Responsive Preview Tool')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:1100px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Testing Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Responsive Preview</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 32px;">Preview any URL at common viewport sizes side-by-side.</p>

    <div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;align-items:center;">
        <input type="url" id="url-input" placeholder="https://example.com" value="https://vibekit.app" style="flex:1;min-width:260px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
        <button onclick="loadFrames()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">Preview</button>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:28px;">
        @foreach([['Mobile S','320'],['Mobile M','375'],['Mobile L','425'],['Tablet','768'],['Laptop','1024'],['Desktop','1440']] as [$label,$w])
        <button onclick="toggleSize('{{ $w }}')" id="btn-{{ $w }}" class="filter-pill {{ $label==='Mobile M'||$label==='Tablet'||$label==='Laptop'?'active':'' }}" style="cursor:pointer;border:none;">{{ $label }} <span style="font-family:var(--font-mono);font-size:10px;color:#787583;">{{ $w }}px</span></button>
        @endforeach
    </div>

    <div id="frames-row" style="display:flex;gap:20px;overflow-x:auto;padding-bottom:16px;align-items:flex-start;"></div>

    <div style="margin-top:32px;padding:16px 20px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;">
        <p style="font-size:12px;color:#787583;margin:0;">⚠️ Many sites block iframe embedding via <code style="color:#454F5E;">X-Frame-Options</code> or <code style="color:#454F5E;">Content-Security-Policy</code>. If a preview shows blank, the site disallows embedding. Try your own local dev server (e.g. <code style="color:#454F5E;">http://localhost:3000</code>) for best results.</p>
    </div>
</div>
@push('scripts')
<script>
const SIZES={320:400,375:500,425:520,768:700,1024:800,1440:900};
let activeSizes=new Set(['375','768','1024']);

function toggleSize(w){
    if(activeSizes.has(w))activeSizes.delete(w);
    else activeSizes.add(w);
    document.querySelectorAll('[id^=btn-]').forEach(b=>{
        const bw=b.id.replace('btn-','');
        b.classList.toggle('active',activeSizes.has(bw));
    });
    loadFrames();
}

function loadFrames(){
    const url=document.getElementById('url-input').value.trim();
    const row=document.getElementById('frames-row');
    row.innerHTML='';
    if(!url){return;}
    const labels={320:'Mobile S',375:'Mobile M',425:'Mobile L',768:'Tablet',1024:'Laptop',1440:'Desktop'};
    [...activeSizes].sort((a,b)=>+a-+b).forEach(w=>{
        const h=SIZES[w]||600;
        const wrapper=document.createElement('div');
        wrapper.style.cssText=`flex:0 0 auto;display:flex;flex-direction:column;gap:8px;`;
        const label=document.createElement('p');
        label.style.cssText='font-size:11px;color:#787583;margin:0;font-family:var(--font-mono);';
        label.textContent=`${labels[w]||w} — ${w}px`;
        const box=document.createElement('div');
        box.style.cssText=`border:.5px solid #E9ECEF;border-radius:10px;overflow:hidden;background:#FFFFFF;`;
        box.style.width=Math.min(+w,500)+'px';
        const frame=document.createElement('iframe');
        frame.src=url;
        frame.style.cssText=`border:none;display:block;width:${w}px;height:${h}px;transform-origin:top left;transform:scale(${Math.min(500,+w)/+w});`;
        box.style.height=(h*Math.min(500,+w)/+w)+'px';
        box.appendChild(frame);
        wrapper.appendChild(label);wrapper.appendChild(box);
        row.appendChild(wrapper);
    });
}
loadFrames();
</script>
@endpush
@endsection
