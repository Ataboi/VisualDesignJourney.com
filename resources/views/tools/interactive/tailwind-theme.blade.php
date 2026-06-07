@extends('layouts.app')
@section('title', 'Tailwind Theme Generator')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Dev Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Tailwind Theme Generator</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Configure your design tokens and generate a <code style="font-size:14px;color:#454F5E;">tailwind.config.js</code> theme extension.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:32px;">
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div>
                <p style="font-size:12px;font-weight:600;color:#454F5E;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 12px;">Brand Colors</p>
                <div style="display:flex;flex-direction:column;gap:8px;" id="color-inputs">
                    @foreach(['primary'=>'#7F77DD','secondary'=>'#7F77DD','accent'=>'#574EB1','neutral'=>'#E9ECEF'] as $name=>$def)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="color" value="{{ $def }}" oninput="generate()" data-name="{{ $name }}" class="tw-color" style="width:36px;height:36px;border:.5px solid #E9ECEF;border-radius:6px;background:#FFFFFF;cursor:pointer;padding:2px;">
                        <span style="font-size:13px;font-family:var(--font-mono);color:#454F5E;width:80px;">{{ $name }}</span>
                        <input type="text" value="{{ $def }}" oninput="syncColor(this,'{{ $name }}')" data-name="{{ $name }}" class="tw-color-hex" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:6px 10px;font-size:12px;color:#131B2E;font-family:var(--font-mono);outline:none;">
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <p style="font-size:12px;font-weight:600;color:#454F5E;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 12px;">Typography</p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div>
                        <label style="font-size:11px;color:#787583;display:block;margin-bottom:4px;">Sans Font</label>
                        <input type="text" id="font-sans" value="Inter" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:8px 12px;font-size:13px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:11px;color:#787583;display:block;margin-bottom:4px;">Serif Font</label>
                        <input type="text" id="font-serif" value="Playfair Display" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:8px 12px;font-size:13px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:11px;color:#787583;display:block;margin-bottom:4px;">Mono Font</label>
                        <input type="text" id="font-mono" value="JetBrains Mono" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:8px 12px;font-size:13px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;">
                    </div>
                </div>
            </div>

            <div>
                <p style="font-size:12px;font-weight:600;color:#454F5E;text-transform:uppercase;letter-spacing:0.06em;margin:0 0 12px;">Border Radius</p>
                <select id="radius-preset" onchange="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;">
                    <option value="sharp">Sharp (0px)</option>
                    <option value="subtle">Subtle (4px)</option>
                    <option value="default" selected>Default (8px)</option>
                    <option value="rounded">Rounded (12px)</option>
                    <option value="pill">Pill (9999px)</option>
                </select>
            </div>
        </div>

        <div>
            <div class="code-block" style="height:100%;">
                <div class="code-block-header"><span class="code-block-label">tailwind.config.js</span><button onclick="copyTw(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
                <pre id="tw-output" style="padding:16px 20px;margin:0;font-size:11px;overflow-x:auto;max-height:560px;white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
const RADIUS_MAP={sharp:'0px',subtle:'4px',default:'8px',rounded:'12px',pill:'9999px'};
function getColors(){
    const out={};
    document.querySelectorAll('.tw-color').forEach(el=>{out[el.dataset.name]=el.value;});
    return out;
}
function syncColor(hexInput,name){
    const val=hexInput.value;
    document.querySelectorAll('.tw-color').forEach(el=>{if(el.dataset.name===name)el.value=val;});
    generate();
}
function generate(){
    const colors=getColors();
    const fontSans=document.getElementById('font-sans').value||'Inter';
    const fontSerif=document.getElementById('font-serif').value||'Playfair Display';
    const fontMono=document.getElementById('font-mono').value||'JetBrains Mono';
    const radiusKey=document.getElementById('radius-preset').value;
    const r=RADIUS_MAP[radiusKey];
    const colorLines=Object.entries(colors).map(([k,v])=>`      ${k}: '${v}',`).join('\n');
    const out=`/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./src/**/*.{html,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
${colorLines}
      },
      fontFamily: {
        sans: ['${fontSans}', 'system-ui', 'sans-serif'],
        serif: ['${fontSerif}', 'Georgia', 'serif'],
        mono: ['${fontMono}', 'monospace'],
      },
      borderRadius: {
        DEFAULT: '${r}',
        sm: 'calc(${r} * 0.5)',
        md: '${r}',
        lg: 'calc(${r} * 1.5)',
        xl: 'calc(${r} * 2)',
        full: '9999px',
      },
    },
  },
  plugins: [],
};`;
    document.getElementById('tw-output').textContent=out;
}
function copyTw(btn){navigator.clipboard.writeText(document.getElementById('tw-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
// Sync hex inputs back to color pickers on load
document.querySelectorAll('.tw-color').forEach(el=>{
    el.addEventListener('input',()=>{
        document.querySelectorAll('.tw-color-hex').forEach(h=>{if(h.dataset.name===el.dataset.name)h.value=el.value;});
        generate();
    });
});
generate();
</script>
@endpush
@endsection
