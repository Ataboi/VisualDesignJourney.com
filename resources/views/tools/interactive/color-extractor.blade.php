@extends('layouts.app')
@section('title', 'Color Extractor')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:860px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Color Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Color Extractor</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Upload an image to extract its dominant color palette.</p>

    <div id="drop-zone" style="border:2px dashed #E9ECEF;border-radius:16px;padding:56px;text-align:center;cursor:pointer;margin-bottom:32px;transition:border-color 0.2s;" onclick="document.getElementById('file-input').click()" ondragover="event.preventDefault();this.style.borderColor='rgba(127,119,221,0.5)'" ondragleave="this.style.borderColor='#E9ECEF'" ondrop="handleDrop(event)">
        <div style="font-size:40px;margin-bottom:12px;">🎨</div>
        <p style="font-size:16px;color:#454F5E;margin:0 0 8px;">Drop an image here or click to upload</p>
        <p style="font-size:13px;color:#787583;margin:0;">PNG, JPG, GIF, WebP — processed locally in your browser</p>
        <input type="file" id="file-input" accept="image/*" style="display:none;" onchange="handleFile(this.files[0])">
    </div>

    <div id="preview-area" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
            <div>
                <p style="font-size:11px;color:#787583;margin:0 0 8px;">Source Image</p>
                <img id="preview-img" style="width:100%;border-radius:10px;border:.5px solid #E9ECEF;" alt="">
            </div>
            <div>
                <p style="font-size:11px;color:#787583;margin:0 0 8px;">Dominant Palette</p>
                <div id="palette-swatches" style="display:flex;flex-direction:column;gap:8px;"></div>
            </div>
        </div>

        <div class="code-block" style="margin-bottom:12px;">
            <div class="code-block-header"><span class="code-block-label">CSS Custom Properties</span><button onclick="copyCss(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
            <pre id="css-output" style="padding:14px 20px;margin:0;font-size:12px;overflow-x:auto;"></pre>
        </div>
        <div class="code-block">
            <div class="code-block-header"><span class="code-block-label">Tailwind Config Colors</span><button onclick="copyTw(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
            <pre id="tw-output" style="padding:14px 20px;margin:0;font-size:12px;overflow-x:auto;"></pre>
        </div>
    </div>
    <canvas id="work-canvas" style="display:none;"></canvas>
</div>
@push('scripts')
<script>
function handleDrop(e){e.preventDefault();document.getElementById('drop-zone').style.borderColor='#E9ECEF';const f=e.dataTransfer.files[0];if(f&&f.type.startsWith('image/'))handleFile(f);}
function handleFile(file){
    if(!file)return;
    const reader=new FileReader();
    reader.onload=e=>{
        const img=document.getElementById('preview-img');
        img.onload=()=>extractColors(img);
        img.src=e.target.result;
        document.getElementById('preview-area').style.display='block';
    };
    reader.readAsDataURL(file);
}
function extractColors(img){
    const c=document.getElementById('work-canvas');
    const size=100;
    c.width=size;c.height=size;
    const ctx=c.getContext('2d');
    ctx.drawImage(img,0,0,size,size);
    const data=ctx.getImageData(0,0,size,size).data;
    // Sample every 4th pixel, cluster by rounding to nearest 32
    const buckets={};
    for(let i=0;i<data.length;i+=16){
        const r=Math.round(data[i]/32)*32;
        const g=Math.round(data[i+1]/32)*32;
        const b=Math.round(data[i+2]/32)*32;
        const a=data[i+3];
        if(a<128)continue;
        const key=`${r},${g},${b}`;
        buckets[key]=(buckets[key]||0)+1;
    }
    const sorted=Object.entries(buckets).sort((a,b)=>b[1]-a[1]);
    // Pick top 8 distinct colors
    const palette=[];
    for(const [key] of sorted){
        if(palette.length>=8)break;
        const [r,g,b]=key.split(',').map(Number);
        const hex=`#${r.toString(16).padStart(2,'0')}${g.toString(16).padStart(2,'0')}${b.toString(16).padStart(2,'0')}`;
        // dedupe similar colors
        const isDup=palette.some(p=>{
            const pr=parseInt(p.slice(1,3),16),pg=parseInt(p.slice(3,5),16),pb=parseInt(p.slice(5,7),16);
            return Math.abs(pr-r)+Math.abs(pg-g)+Math.abs(pb-b)<50;
        });
        if(!isDup)palette.push(hex);
    }
    renderPalette(palette);
}
function renderPalette(colors){
    const swatches=document.getElementById('palette-swatches');
    swatches.innerHTML=colors.map((c,i)=>`
        <div onclick="copyHex('${c}',this)" style="display:flex;align-items:center;gap:10px;cursor:pointer;border:.5px solid #E9ECEF;border-radius:8px;padding:8px 12px;background:#FFFFFF;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">
            <div style="width:36px;height:36px;border-radius:6px;background:${c};flex-shrink:0;"></div>
            <span style="font-size:13px;font-family:var(--font-mono);color:#454F5E;">${c}</span>
            <span style="margin-left:auto;font-size:10px;color:#787583;">click to copy</span>
        </div>
    `).join('');
    // CSS vars
    const cssVars=`:root {\n${colors.map((c,i)=>`  --color-${i+1}: ${c};`).join('\n')}\n}`;
    document.getElementById('css-output').textContent=cssVars;
    // Tailwind
    const twColors=`colors: {\n${colors.map((c,i)=>`  extracted${i+1}: '${c}',`).join('\n')}\n}`;
    document.getElementById('tw-output').textContent=twColors;
}
function copyHex(hex,el){navigator.clipboard.writeText(hex);const s=el.querySelector('span:last-child');s.textContent='Copied!';s.style.color='#7F77DD';setTimeout(()=>{s.textContent='click to copy';s.style.color='';},1500);}
function copyCss(btn){navigator.clipboard.writeText(document.getElementById('css-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
function copyTw(btn){navigator.clipboard.writeText(document.getElementById('tw-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
</script>
@endpush
@endsection
