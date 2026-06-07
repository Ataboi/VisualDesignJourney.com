@extends('layouts.app')
@section('title', 'Component Prop Table Generator')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:900px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Dev Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Component Prop Table Generator</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 32px;">Define your component API and generate a Markdown prop table for documentation.</p>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div style="display:flex;gap:10px;align-items:center;">
            <label style="font-size:12px;font-weight:600;color:#454F5E;">Component name</label>
            <input type="text" id="comp-name" value="Button" oninput="generate()" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 12px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;width:160px;">
        </div>
        <button onclick="addProp()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">+ Add Prop</button>
    </div>

    {{-- Prop rows --}}
    <div style="border:.5px solid #E9ECEF;border-radius:12px;overflow:hidden;margin-bottom:24px;">
        <div style="display:grid;grid-template-columns:160px 140px 140px 80px 1fr 36px;gap:0;background:#FFFFFF;padding:10px 16px;border-bottom:.5px solid #E9ECEF;">
            @foreach(['Prop','Type','Default','Required','Description',''] as $h)
            <span style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.06em;">{{ $h }}</span>
            @endforeach
        </div>
        <div id="prop-rows"></div>
    </div>

    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 12px;">Quick Presets</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;">
        @foreach(['Button','Input','Modal','Badge','Avatar'] as $p)
        <button onclick="loadPreset('{{ $p }}')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;font-family:inherit;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">{{ $p }}</button>
        @endforeach
    </div>

    <div class="code-block" style="margin-bottom:12px;">
        <div class="code-block-header"><span class="code-block-label">Markdown</span><button onclick="copyMd(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="md-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;max-height:400px;white-space:pre;"></pre>
    </div>
    <div class="code-block">
        <div class="code-block-header"><span class="code-block-label">TypeScript Interface</span><button onclick="copyTs(this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="ts-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;max-height:300px;white-space:pre;"></pre>
    </div>
</div>
@push('scripts')
<script>
let props=[];let pId=0;
const TYPES=['string','number','boolean','ReactNode','() => void','string[]','object','any'];
const PRESETS={
    Button:[{name:'variant',type:'string',default:'"primary"',required:false,desc:'Visual style: primary | secondary | ghost | danger'},{name:'size',type:'string',default:'"md"',required:false,desc:'Button size: sm | md | lg'},{name:'disabled',type:'boolean',default:'false',required:false,desc:'Disables the button'},{name:'loading',type:'boolean',default:'false',required:false,desc:'Shows a spinner inside the button'},{name:'onClick',type:'() => void',default:'-',required:false,desc:'Click handler'},{name:'children',type:'ReactNode',default:'-',required:true,desc:'Button label or content'}],
    Input:[{name:'value',type:'string',default:'""',required:true,desc:'Controlled input value'},{name:'onChange',type:'() => void',default:'-',required:true,desc:'Change handler'},{name:'placeholder',type:'string',default:'""',required:false,desc:'Placeholder text'},{name:'type',type:'string',default:'"text"',required:false,desc:'Input type: text | email | password | number'},{name:'error',type:'string',default:'-',required:false,desc:'Error message shown below the input'},{name:'disabled',type:'boolean',default:'false',required:false,desc:'Disables the input'}],
    Modal:[{name:'isOpen',type:'boolean',default:'false',required:true,desc:'Controls modal visibility'},{name:'onClose',type:'() => void',default:'-',required:true,desc:'Called when the modal should close'},{name:'title',type:'string',default:'-',required:false,desc:'Modal title text'},{name:'children',type:'ReactNode',default:'-',required:true,desc:'Modal body content'},{name:'size',type:'string',default:'"md"',required:false,desc:'Modal width: sm | md | lg | full'}],
    Badge:[{name:'label',type:'string',default:'-',required:true,desc:'Badge text'},{name:'variant',type:'string',default:'"neutral"',required:false,desc:'Color variant: neutral | success | warning | danger | info'},{name:'dot',type:'boolean',default:'false',required:false,desc:'Show a colored dot instead of text'}],
    Avatar:[{name:'src',type:'string',default:'-',required:false,desc:'Image URL'},{name:'alt',type:'string',default:'"avatar"',required:false,desc:'Alt text for the image'},{name:'size',type:'string',default:'"md"',required:false,desc:'Size: xs | sm | md | lg | xl'},{name:'fallback',type:'string',default:'-',required:false,desc:'Initials shown when no image is available'}],
};

function addProp(p={}){
    const id=++pId;
    props.push({id,name:p.name||'',type:p.type||'string',default:p.default||'-',required:p.required||false,desc:p.desc||''});
    renderRows();generate();
}
function removeProp(id){props=props.filter(p=>p.id!==id);renderRows();generate();}
function updateProp(id,key,val){const p=props.find(p=>p.id===id);if(p)p[key]=val;generate();}
function loadPreset(name){props=[];pId=0;document.getElementById('comp-name').value=name;(PRESETS[name]||[]).forEach(p=>addProp(p));renderRows();generate();}
function renderRows(){
    document.getElementById('prop-rows').innerHTML=props.map(p=>`
        <div style="display:grid;grid-template-columns:160px 140px 140px 80px 1fr 36px;gap:0;padding:8px 16px;border-bottom:1px solid #0F0F17;align-items:center;">
            <input value="${p.name}" oninput="updateProp(${p.id},'name',this.value)" placeholder="propName" style="background:transparent;border:1px solid #1A1A24;border-radius:6px;padding:5px 8px;font-size:12px;color:#131B2E;font-family:var(--font-mono);outline:none;width:148px;box-sizing:border-box;">
            <select onchange="updateProp(${p.id},'type',this.value)" style="background:#F8F9FA;border:1px solid #1A1A24;border-radius:6px;padding:5px 6px;font-size:12px;color:#454F5E;outline:none;">
                ${TYPES.map(t=>`<option${t===p.type?' selected':''}>${t}</option>`).join('')}
            </select>
            <input value="${p.default}" oninput="updateProp(${p.id},'default',this.value)" style="background:transparent;border:1px solid #1A1A24;border-radius:6px;padding:5px 8px;font-size:12px;color:#454F5E;font-family:var(--font-mono);outline:none;width:128px;box-sizing:border-box;">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#787583;"><input type="checkbox" ${p.required?'checked':''} onchange="updateProp(${p.id},'required',this.checked)" style="accent-color:#7F77DD;"> Yes</label>
            <input value="${p.desc}" oninput="updateProp(${p.id},'desc',this.value)" placeholder="Description" style="background:transparent;border:1px solid #1A1A24;border-radius:6px;padding:5px 8px;font-size:12px;color:#454F5E;outline:none;width:100%;box-sizing:border-box;">
            <button onclick="removeProp(${p.id})" style="background:none;border:none;color:#787583;cursor:pointer;font-size:18px;line-height:1;padding:0 4px;">×</button>
        </div>
    `).join('');
}
function generate(){
    const name=document.getElementById('comp-name').value||'Component';
    const header=`| Prop | Type | Default | Required | Description |\n|------|------|---------|----------|-------------|`;
    const rows=props.map(p=>`| \`${p.name}\` | \`${p.type}\` | \`${p.default}\` | ${p.required?'✓':''} | ${p.desc} |`).join('\n');
    document.getElementById('md-output').textContent=`### \`<${name}>\` Props\n\n${header}\n${rows}`;
    const tsProps=props.map(p=>`  ${p.name}${p.required?'':'?'}: ${p.type};`).join('\n');
    document.getElementById('ts-output').textContent=`interface ${name}Props {\n${tsProps}\n}`;
}
function copyMd(btn){navigator.clipboard.writeText(document.getElementById('md-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
function copyTs(btn){navigator.clipboard.writeText(document.getElementById('ts-output').textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
loadPreset('Button');
</script>
@endpush
@endsection
