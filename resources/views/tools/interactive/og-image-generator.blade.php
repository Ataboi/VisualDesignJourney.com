@extends('layouts.app')
@section('title', 'OG Image Generator')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Asset Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">OG Image Generator</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Design a 1200×630 Open Graph image and download as PNG.</p>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:28px;align-items:start;">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Title</label>
                <input type="text" id="og-title" value="Ship faster." oninput="render()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Subtitle</label>
                <input type="text" id="og-sub" value="Free design tools for makers." oninput="render()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Brand / Domain</label>
                <input type="text" id="og-brand" value="VibeKit" oninput="render()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Layout</label>
                <select id="og-layout" onchange="render()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;">
                    <option value="center">Centered</option>
                    <option value="left">Left Aligned</option>
                    <option value="gradient">Gradient Accent</option>
                    <option value="dark">Dark Minimal</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Background</label>
                <div style="display:flex;gap:8px;">
                    <input type="color" id="bg-pick" value="#F8F9FA" oninput="document.getElementById('bg-hex').value=this.value;render()" style="width:44px;height:40px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                    <input type="text" id="bg-hex" value="#F8F9FA" oninput="document.getElementById('bg-pick').value=this.value;render()" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;">
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Accent Color</label>
                <div style="display:flex;gap:8px;">
                    <input type="color" id="accent-pick" value="#7F77DD" oninput="document.getElementById('accent-hex').value=this.value;render()" style="width:44px;height:40px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;">
                    <input type="text" id="accent-hex" value="#7F77DD" oninput="document.getElementById('accent-pick').value=this.value;render()" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;">
                </div>
            </div>
            <button onclick="downloadOg()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;width:100%;margin-top:8px;">↓ Download 1200×630 PNG</button>
        </div>

        <div>
            <p style="font-size:11px;color:#787583;margin:0 0 8px;">Preview (scaled)</p>
            <canvas id="og-canvas" width="1200" height="630" style="width:100%;border-radius:10px;border:.5px solid #E9ECEF;display:block;"></canvas>
        </div>
    </div>
</div>
@push('scripts')
<script>
function render(){
    const c=document.getElementById('og-canvas');
    const ctx=c.getContext('2d');
    const W=1200,H=630;
    const bg=document.getElementById('bg-hex').value||'#F8F9FA';
    const accent=document.getElementById('accent-hex').value||'#7F77DD';
    const title=document.getElementById('og-title').value||'Title';
    const sub=document.getElementById('og-sub').value||'';
    const brand=document.getElementById('og-brand').value||'';
    const layout=document.getElementById('og-layout').value;
    ctx.clearRect(0,0,W,H);
    // Background
    ctx.fillStyle=bg;ctx.fillRect(0,0,W,H);

    if(layout==='gradient'){
        const g=ctx.createLinearGradient(0,0,W,H);
        g.addColorStop(0,bg);g.addColorStop(1,accent+'22');
        ctx.fillStyle=g;ctx.fillRect(0,0,W,H);
    }
    if(layout==='dark'){
        // subtle grid
        ctx.strokeStyle='#1A1A24';ctx.lineWidth=1;
        for(let x=0;x<W;x+=40){ctx.beginPath();ctx.moveTo(x,0);ctx.lineTo(x,H);ctx.stroke();}
        for(let y=0;y<H;y+=40){ctx.beginPath();ctx.moveTo(0,y);ctx.lineTo(W,y);ctx.stroke();}
    }

    // Accent bar
    ctx.fillStyle=accent;
    if(layout==='left'){ctx.fillRect(60,80,6,H-160);}
    else{ctx.fillRect(60,H-80,W-120,4);}

    const cx=layout==='center'||layout==='gradient'?'center':'left';
    ctx.textAlign=cx;
    const tx=cx==='center'?W/2:100;

    // Title
    ctx.fillStyle='#131B2E';
    const titleSize=layout==='center'?88:80;
    ctx.font=`bold ${titleSize}px -apple-system,system-ui,sans-serif`;
    const words=title.split(' ');
    let line='',lines=[],y0=layout==='center'?220:180;
    for(const w of words){const t=line?line+' '+w:w;if(ctx.measureText(t).width>W-200&&line){lines.push(line);line=w;}else line=t;}
    if(line)lines.push(line);
    lines.slice(0,3).forEach((l,i)=>{ctx.fillText(l,tx,y0+i*(titleSize*1.15));});

    // Subtitle
    if(sub){
        ctx.font=`${layout==='center'?36:32}px -apple-system,system-ui,sans-serif`;
        ctx.fillStyle='#454F5E';
        ctx.fillText(sub.slice(0,60),tx,y0+lines.length*(titleSize*1.15)+20);
    }

    // Brand pill
    if(brand){
        const bx=layout==='center'?W/2:100;
        const by=H-100;
        ctx.font='bold 26px -apple-system,system-ui,sans-serif';
        ctx.textAlign=cx;
        const bw=ctx.measureText(brand).width;
        const pad=16;
        const pillX=cx==='center'?bx-(bw/2)-pad:bx-pad;
        ctx.fillStyle=accent+'33';
        roundRect(ctx,pillX,by-30,bw+pad*2,44,10);ctx.fill();
        ctx.fillStyle=accent;
        ctx.fillText(brand,bx,by+6);
    }
}
function roundRect(ctx,x,y,w,h,r){ctx.beginPath();ctx.moveTo(x+r,y);ctx.lineTo(x+w-r,y);ctx.quadraticCurveTo(x+w,y,x+w,y+r);ctx.lineTo(x+w,y+h-r);ctx.quadraticCurveTo(x+w,y+h,x+w-r,y+h);ctx.lineTo(x+r,y+h);ctx.quadraticCurveTo(x,y+h,x,y+h-r);ctx.lineTo(x,y+r);ctx.quadraticCurveTo(x,y,x+r,y);ctx.closePath();}
function downloadOg(){const c=document.getElementById('og-canvas');const a=document.createElement('a');a.download='og-image.png';a.href=c.toDataURL('image/png');a.click();}
render();
</script>
@endpush
@endsection
