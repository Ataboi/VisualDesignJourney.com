@extends('layouts.app')
@section('title', 'README Badge Generator')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:780px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Dev Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">README Badge Generator</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Build shields.io badges for your project README. Click to copy markdown or HTML.</p>

    <div style="border:.5px solid #E9ECEF;border-radius:14px;padding:24px;margin-bottom:32px;">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
            <div><label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Label</label><input type="text" id="badge-label" value="build" oninput="update()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;"></div>
            <div><label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Message</label><input type="text" id="badge-message" value="passing" oninput="update()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;"></div>
            <div><label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Color</label><div style="display:flex;gap:8px;"><input type="color" id="badge-color-pick" value="#6EE7B7" oninput="document.getElementById('badge-color').value=this.value.replace('#','');update()" style="width:44px;height:40px;border:.5px solid #E9ECEF;border-radius:8px;background:#FFFFFF;cursor:pointer;padding:2px;"><input type="text" id="badge-color" value="6EE7B7" oninput="update()" placeholder="4CAF50" style="flex:1;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;"></div></div>
            <div><label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Style</label><select id="badge-style" onchange="update()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;outline:none;"><option value="flat">flat</option><option value="flat-square">flat-square</option><option value="for-the-badge">for-the-badge</option><option value="plastic">plastic</option></select></div>
            <div><label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Link (optional)</label><input type="text" id="badge-link" placeholder="https://..." oninput="update()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;"></div>
            <div><label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Logo (optional)</label><input type="text" id="badge-logo" placeholder="github, npm, vercel..." oninput="update()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:10px 14px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;"></div>
        </div>
        <div style="text-align:center;padding:24px;background:#F8F9FA;border-radius:10px;"><img id="badge-preview" src="" alt="badge preview" style="height:28px;"></div>
    </div>

    <div class="code-block" style="margin-bottom:12px;">
        <div class="code-block-header"><span class="code-block-label">Markdown</span><button onclick="copy('md-out',this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="md-out" style="padding:14px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
    </div>
    <div class="code-block">
        <div class="code-block-header"><span class="code-block-label">HTML</span><button onclick="copy('html-out',this)" style="font-size:11px;color:#787583;background:none;border:.5px solid #E9ECEF;border-radius:6px;padding:3px 10px;cursor:pointer;font-family:inherit;" onmouseover="this.style.color='#7F77DD';this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF';if(!this.dataset.copied)this.style.color='#787583'">Copy</button></div>
        <pre id="html-out" style="padding:14px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
    </div>

    {{-- Popular presets --}}
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:32px 0 12px;">Quick Presets</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach([['build','passing','4CAF50',''],['tests','passing','6EE7B7',''],['coverage','98%','7F77DD',''],['license','MIT','574EB1',''],['version','1.0.0','8B5CF6','npm'],['made with','Laravel','FF2D20','laravel'],['deploy','Vercel','131B2E','vercel']] as [$l,$m,$c,$lg])
        <button onclick="preset('{{ $l }}','{{ $m }}','{{ $c }}','{{ $lg }}')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">{{ $l }}: {{ $m }}</button>
        @endforeach
    </div>
</div>
@push('scripts')
<script>
function update(){
    const label=document.getElementById('badge-label').value,msg=document.getElementById('badge-message').value,color=document.getElementById('badge-color').value,style=document.getElementById('badge-style').value,link=document.getElementById('badge-link').value.trim(),logo=document.getElementById('badge-logo').value.trim();
    let url=`https://img.shields.io/badge/${encodeURIComponent(label)}-${encodeURIComponent(msg)}-${color}?style=${style}`;
    if(logo)url+=`&logo=${encodeURIComponent(logo)}`;
    document.getElementById('badge-preview').src=url;
    const md=link?`[![${label}](${url})](${link})`:`![${label}](${url})`;
    const html=link?`<a href="${link}"><img src="${url}" alt="${label}"></a>`:`<img src="${url}" alt="${label}">`;
    document.getElementById('md-out').textContent=md;
    document.getElementById('html-out').textContent=html;
}
function preset(l,m,c,lg){document.getElementById('badge-label').value=l;document.getElementById('badge-message').value=m;document.getElementById('badge-color').value=c;document.getElementById('badge-logo').value=lg;update();}
function copy(id,btn){navigator.clipboard.writeText(document.getElementById(id).textContent);btn.textContent='Copied!';btn.style.color='#7F77DD';btn.dataset.copied='1';setTimeout(()=>{btn.textContent='Copy';btn.style.color='';delete btn.dataset.copied;},2000);}
update();
</script>
@endpush
@endsection
