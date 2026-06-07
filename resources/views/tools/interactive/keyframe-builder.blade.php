@extends('layouts.app')
@section('title', 'CSS Keyframe Builder')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Animation Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">CSS Keyframe Builder</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Build CSS @keyframes animations visually and copy the output.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:32px;">
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <span style="font-size:12px;font-weight:600;color:#454F5E;text-transform:uppercase;letter-spacing:0.06em;">Keyframes</span>
                <button onclick="addKeyframe()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;">+ Add</button>
            </div>
            <div id="keyframe-list" style="display:flex;flex-direction:column;gap:10px;"></div>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Animation Name</label>
                <input type="text" id="anim-name" value="fadeInUp" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Duration</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="range" id="duration" min="100" max="5000" value="600" step="50" oninput="document.getElementById('dur-val').textContent=(this.value/1000).toFixed(1)+'s';applyPreview()" style="flex:1;accent-color:#7F77DD;">
                    <span id="dur-val" style="font-size:12px;font-family:var(--font-mono);color:#7F77DD;min-width:36px;">0.6s</span>
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Easing</label>
                <select id="easing" onchange="applyPreview()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;">
                    <option>ease</option><option>ease-in</option><option>ease-out</option><option selected>ease-in-out</option>
                    <option>linear</option><option>cubic-bezier(0.34,1.56,0.64,1)</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Iteration</label>
                <select id="iteration" onchange="applyPreview()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;">
                    <option value="1">Once</option><option value="3">3 times</option><option value="infinite">Infinite</option>
                </select>
            </div>

            <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:8px 0 8px;">Presets</p>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                @foreach(['fadeInUp','slideInLeft','pulse','bounce','spin','shake'] as $p)
                <button onclick="applyPreset('{{ $p }}')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:6px 12px;cursor:pointer;font-size:12px;color:#454F5E;font-family:var(--font-mono);" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">{{ $p }}</button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Preview --}}
    <div style="background:#F8F9FA;border:.5px solid #E9ECEF;border-radius:14px;padding:48px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;min-height:160px;">
        <div id="preview-el" style="width:80px;height:80px;background:linear-gradient(135deg,#7F77DD,#7F77DD);border-radius:14px;"></div>
    </div>
    <div style="display:flex;gap:10px;margin-bottom:24px;">
        <button onclick="replay()" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 20px;font-size:13px;color:#131B2E;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">↺ Replay</button>
    </div>

    <div class="code-block">
        <div class="code-block-header"><span class="code-block-label">CSS</span><button onclick="copyKf(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="kf-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;max-height:400px;"></pre>
    </div>
</div>
@push('scripts')
<script>
let keyframes=[];
let kfCount=0;

const PRESETS={
    fadeInUp:{frames:[{pct:0,props:'opacity: 0; transform: translateY(20px)'},{pct:100,props:'opacity: 1; transform: translateY(0)'}],dur:600,ease:'ease-out'},
    slideInLeft:{frames:[{pct:0,props:'opacity: 0; transform: translateX(-40px)'},{pct:100,props:'opacity: 1; transform: translateX(0)'}],dur:500,ease:'ease-out'},
    pulse:{frames:[{pct:0,props:'transform: scale(1)'},{pct:50,props:'transform: scale(1.1)'},{pct:100,props:'transform: scale(1)'}],dur:800,ease:'ease-in-out'},
    bounce:{frames:[{pct:0,props:'transform: translateY(0)'},{pct:30,props:'transform: translateY(-20px)'},{pct:60,props:'transform: translateY(0)'},{pct:80,props:'transform: translateY(-8px)'},{pct:100,props:'transform: translateY(0)'}],dur:900,ease:'ease'},
    spin:{frames:[{pct:0,props:'transform: rotate(0deg)'},{pct:100,props:'transform: rotate(360deg)'}],dur:1000,ease:'linear'},
    shake:{frames:[{pct:0,props:'transform: translateX(0)'},{pct:20,props:'transform: translateX(-8px)'},{pct:40,props:'transform: translateX(8px)'},{pct:60,props:'transform: translateX(-8px)'},{pct:80,props:'transform: translateX(8px)'},{pct:100,props:'transform: translateX(0)'}],dur:500,ease:'ease'},
};

function applyPreset(name){
    const p=PRESETS[name];
    document.getElementById('anim-name').value=name;
    document.getElementById('duration').value=p.dur;
    document.getElementById('dur-val').textContent=(p.dur/1000).toFixed(1)+'s';
    document.getElementById('easing').value=p.ease;
    keyframes=p.frames.map((f,i)=>({id:++kfCount,pct:f.pct,props:f.props}));
    renderKeyframeList();
    generate();
    replay();
}

function renderKeyframeList(){
    const el=document.getElementById('keyframe-list');
    el.innerHTML=keyframes.map(kf=>`
        <div style="border:.5px solid #E9ECEF;border-radius:10px;padding:12px;background:#F8F9FA;">
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                <input type="number" min="0" max="100" value="${kf.pct}" onchange="updateKf(${kf.id},'pct',+this.value)" style="width:60px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:6px;padding:5px 8px;font-size:13px;color:#7F77DD;font-family:var(--font-mono);outline:none;text-align:center;">
                <span style="font-size:12px;color:#787583;">%</span>
                <button onclick="removeKf(${kf.id})" style="margin-left:auto;background:none;border:none;color:#787583;cursor:pointer;font-size:16px;line-height:1;">×</button>
            </div>
            <textarea rows="2" placeholder="transform: translateY(0); opacity: 1;" onchange="updateKf(${kf.id},'props',this.value)" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:8px 10px;font-size:12px;color:#454F5E;font-family:var(--font-mono);outline:none;box-sizing:border-box;resize:none;">${kf.props}</textarea>
        </div>
    `).join('');
}

function addKeyframe(){
    const pct=keyframes.length===0?0:keyframes.length===1?100:50;
    keyframes.push({id:++kfCount,pct,props:'opacity: 1'});
    keyframes.sort((a,b)=>a.pct-b.pct);
    renderKeyframeList();generate();
}
function removeKf(id){keyframes=keyframes.filter(k=>k.id!==id);renderKeyframeList();generate();}
function updateKf(id,key,val){const k=keyframes.find(f=>f.id===id);if(k){k[key]=val;}keyframes.sort((a,b)=>a.pct-b.pct);generate();}

function generate(){
    const name=document.getElementById('anim-name').value||'myAnimation';
    const dur=(document.getElementById('duration').value/1000).toFixed(1)+'s';
    const ease=document.getElementById('easing').value;
    const iter=document.getElementById('iteration').value;
    const frames=keyframes.map(kf=>`  ${kf.pct}% {\n    ${kf.props.split(';').map(s=>s.trim()).filter(Boolean).join(';\n    ')};\n  }`).join('\n');
    const css=`@keyframes ${name} {\n${frames}\n}\n\n.animated {\n  animation: ${name} ${dur} ${ease} ${iter};\n}`;
    document.getElementById('kf-output').textContent=css;
    applyPreview();
}

let styleEl=null;
function applyPreview(){
    const name=document.getElementById('anim-name').value||'myAnimation';
    const dur=document.getElementById('duration').value/1000+'s';
    const ease=document.getElementById('easing').value;
    const iter=document.getElementById('iteration').value;
    const frames=keyframes.map(kf=>`${kf.pct}%{${kf.props}}`).join('');
    if(!styleEl){styleEl=document.createElement('style');document.head.appendChild(styleEl);}
    styleEl.textContent=`@keyframes ${name}{${frames}}`;
    const el=document.getElementById('preview-el');
    el.style.animation='none';
    el.offsetHeight; // reflow
    el.style.animation=`${name} ${dur} ${ease} ${iter}`;
}

function replay(){
    applyPreview();
    const el=document.getElementById('preview-el');
    el.style.animation='none';el.offsetHeight;
    const name=document.getElementById('anim-name').value||'myAnimation';
    const dur=document.getElementById('duration').value/1000+'s';
    const ease=document.getElementById('easing').value;
    const iter=document.getElementById('iteration').value;
    el.style.animation=`${name} ${dur} ${ease} ${iter}`;
}

function copyKf(btn){navigator.clipboard.writeText(document.getElementById('kf-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}

applyPreset('fadeInUp');
</script>
@endpush
@endsection
