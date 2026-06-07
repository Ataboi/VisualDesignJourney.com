@extends('layouts.app')
@section('title', 'Favicon Generator')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:780px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Asset Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Favicon Generator</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Create a favicon from text or emoji. Download as PNG or copy the data URL.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Text / Emoji</label>
                <input type="text" id="fav-text" value="⚡" maxlength="3" oninput="render()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 14px;font-size:22px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;text-align:center;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Background Color</label>
                <div style="display:flex;gap:8px;">
                    <input type="color" id="bg-pick" value="#FFFFFF" oninput="document.getElementById('bg-hex').value=this.value;render()" style="width:44px;height:40px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                    <input type="text" id="bg-hex" value="#FFFFFF" oninput="document.getElementById('bg-pick').value=this.value;render()" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;">
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Text Color</label>
                <div style="display:flex;gap:8px;">
                    <input type="color" id="txt-pick" value="#7F77DD" oninput="document.getElementById('txt-hex').value=this.value;render()" style="width:44px;height:40px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                    <input type="text" id="txt-hex" value="#7F77DD" oninput="document.getElementById('txt-pick').value=this.value;render()" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;">
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Border Radius</label>
                <div style="display:flex;align-items:center;gap:12px;">
                    <input type="range" id="radius" min="0" max="64" value="16" oninput="document.getElementById('radius-val').textContent=this.value+'px';render()" style="flex:1;accent-color:#7F77DD;">
                    <span id="radius-val" style="font-size:12px;font-family:var(--font-mono);color:#7F77DD;min-width:36px;">16px</span>
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Font Size</label>
                <div style="display:flex;align-items:center;gap:12px;">
                    <input type="range" id="font-size" min="20" max="90" value="56" oninput="document.getElementById('fs-val').textContent=this.value;render()" style="flex:1;accent-color:#7F77DD;">
                    <span id="fs-val" style="font-size:12px;font-family:var(--font-mono);color:#7F77DD;min-width:24px;">56</span>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;">
            {{-- 32px preview --}}
            <div>
                <p style="font-size:11px;color:#787583;text-align:center;margin:0 0 8px;">32×32</p>
                <canvas id="canvas-32" width="32" height="32" style="border:.5px solid #E9ECEF;border-radius:4px;image-rendering:pixelated;width:64px;height:64px;display:block;margin:0 auto;"></canvas>
            </div>
            {{-- 64px preview --}}
            <div>
                <p style="font-size:11px;color:#787583;text-align:center;margin:0 0 8px;">64×64</p>
                <canvas id="canvas-64" width="64" height="64" style="border:.5px solid #E9ECEF;border-radius:6px;width:96px;height:96px;display:block;margin:0 auto;"></canvas>
            </div>
            {{-- 512px preview (scaled down) --}}
            <div>
                <p style="font-size:11px;color:#787583;text-align:center;margin:0 0 8px;">512×512 (apple-touch-icon)</p>
                <canvas id="canvas-512" width="512" height="512" style="border:.5px solid #E9ECEF;border-radius:10px;width:120px;height:120px;display:block;margin:0 auto;"></canvas>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px;">
        <button onclick="download(32)" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:10px;padding:12px 22px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">↓ favicon-32.png</button>
        <button onclick="download(64)" style="background:#FFFFFF;color:#131B2E;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 22px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">↓ favicon-64.png</button>
        <button onclick="download(512)" style="background:#FFFFFF;color:#131B2E;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 22px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">↓ apple-touch-icon.png</button>
    </div>

    <div class="code-block" style="margin-bottom:12px;">
        <div class="code-block-header"><span class="code-block-label">HTML &lt;head&gt; snippet</span><button onclick="copyHtml(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="html-snippet" style="padding:14px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
    </div>

    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:24px 0 12px;">Quick Presets</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach([['⚡','#FFFFFF','#7F77DD'],['🚀','#F8F9FA','#FFFFFF'],['🎨','#7F77DD','#FFFFFF'],['✦','#7F77DD','#F8F9FA'],['▲','#F8F9FA','#FFFFFF'],['◆','#FF2D20','#FFFFFF']] as [$t,$bg,$fg])
        <button onclick="preset('{{ $t }}','{{ $bg }}','{{ $fg }}')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 16px;cursor:pointer;font-size:18px;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">{{ $t }}</button>
        @endforeach
    </div>
</div>
@push('scripts')
<script>
function render(){
    const text=document.getElementById('fav-text').value||'?';
    const bg=document.getElementById('bg-hex').value;
    const fg=document.getElementById('txt-hex').value;
    const radius=parseInt(document.getElementById('radius').value);
    const fs=parseInt(document.getElementById('font-size').value);
    [32,64,512].forEach(size=>{
        const c=document.getElementById('canvas-'+size);
        const ctx=c.getContext('2d');
        const r=radius*(size/64);
        ctx.clearRect(0,0,size,size);
        ctx.beginPath();
        ctx.moveTo(r,0);ctx.lineTo(size-r,0);ctx.quadraticCurveTo(size,0,size,r);
        ctx.lineTo(size,size-r);ctx.quadraticCurveTo(size,size,size-r,size);
        ctx.lineTo(r,size);ctx.quadraticCurveTo(0,size,0,size-r);
        ctx.lineTo(0,r);ctx.quadraticCurveTo(0,0,r,0);
        ctx.closePath();
        ctx.fillStyle=bg;ctx.fill();
        const fsPx=fs*(size/64);
        ctx.font=`${fsPx}px serif`;
        ctx.fillStyle=fg;
        ctx.textAlign='center';ctx.textBaseline='middle';
        ctx.fillText(text.slice(0,2),size/2,size/2+fsPx*0.05);
    });
    document.getElementById('html-snippet').textContent=`<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">\n<link rel="icon" type="image/png" sizes="64x64" href="/favicon-64.png">\n<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">`;
}
function download(size){
    const c=document.getElementById('canvas-'+size);
    const a=document.createElement('a');
    a.download=size===512?'apple-touch-icon.png':`favicon-${size}.png`;
    a.href=c.toDataURL('image/png');
    a.click();
}
function preset(t,bg,fg){
    document.getElementById('fav-text').value=t;
    document.getElementById('bg-hex').value=bg;document.getElementById('bg-pick').value=bg;
    document.getElementById('txt-hex').value=fg;document.getElementById('txt-pick').value=fg;
    render();
}
function copyHtml(btn){navigator.clipboard.writeText(document.getElementById('html-snippet').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
render();
</script>
@endpush
@endsection
