@extends('layouts.app')
@section('title', 'A/B Test Hypothesis Builder')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:780px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">UX Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">A/B Test Hypothesis Builder</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 48px;">Fill in the fields to generate a structured hypothesis statement ready for your test plan.</p>

    <div style="display:flex;flex-direction:column;gap:20px;margin-bottom:32px;">
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">We believe that changing <span style="color:#7F77DD;">_</span></label>
            <input type="text" id="f-change" placeholder="e.g. the CTA button color from gray to green" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">For <span style="color:#7F77DD;">_</span></label>
            <input type="text" id="f-who" placeholder="e.g. first-time visitors on the pricing page" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Will result in <span style="color:#7F77DD;">_</span></label>
            <input type="text" id="f-result" placeholder="e.g. a higher click-through rate on the CTA" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Because <span style="color:#7F77DD;">_</span></label>
            <textarea id="f-because" rows="3" placeholder="e.g. green is associated with positive action and creates higher visual salience on a dark background" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;resize:vertical;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Primary Metric</label>
                <input type="text" id="f-metric" placeholder="e.g. CTA click-through rate" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Target Lift</label>
                <input type="text" id="f-lift" placeholder="e.g. +15%" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Confidence Level</label>
                <select id="f-confidence" onchange="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;outline:none;">
                    <option>80%</option><option selected>95%</option><option>99%</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#454F5E;display:block;margin-bottom:8px;">Estimated Duration</label>
                <input type="text" id="f-duration" placeholder="e.g. 2 weeks" oninput="generate()" style="width:100%;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 16px;font-size:14px;color:#131B2E;font-family:inherit;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
            </div>
        </div>
    </div>

    {{-- Output card --}}
    <div style="border:1px solid rgba(127,119,221,0.2);border-radius:14px;padding:28px;background:#F8F9FA;margin-bottom:24px;">
        <p style="font-size:11px;font-weight:600;color:#7F77DD;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 16px;">Generated Hypothesis</p>
        <div id="hypothesis-output" style="font-size:16px;line-height:1.7;color:#131B2E;"></div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <button onclick="copyStatement()" style="background:#7F77DD;color:#F8F9FA;border:none;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">Copy Statement</button>
        <button onclick="copyMarkdown()" style="background:#FFFFFF;color:#131B2E;border:.5px solid #E9ECEF;border-radius:10px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">Copy as Markdown</button>
    </div>

    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:32px 0 12px;">Quick Presets</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach(['CTA Colour','Headline Copy','Form Length','Social Proof','Pricing Layout'] as $p)
        <button onclick="loadPreset('{{ $p }}')" style="background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:8px;padding:7px 14px;cursor:pointer;font-size:12px;color:#454F5E;" onmouseover="this.style.borderColor='rgba(127,119,221,0.3)'" onmouseout="this.style.borderColor='#E9ECEF'">{{ $p }}</button>
        @endforeach
    </div>
</div>
@push('scripts')
<script>
function generate(){
    const change=document.getElementById('f-change').value;
    const who=document.getElementById('f-who').value;
    const result=document.getElementById('f-result').value;
    const because=document.getElementById('f-because').value;
    const metric=document.getElementById('f-metric').value;
    const lift=document.getElementById('f-lift').value;
    const conf=document.getElementById('f-confidence').value;
    const dur=document.getElementById('f-duration').value;
    const el=document.getElementById('hypothesis-output');
    let html='';
    if(change)html+=`<p><strong style="color:#454F5E;">If we change</strong> ${change}</p>`;
    if(who)html+=`<p><strong style="color:#454F5E;">For</strong> ${who}</p>`;
    if(result)html+=`<p><strong style="color:#454F5E;">We expect</strong> ${result}</p>`;
    if(because)html+=`<p><strong style="color:#454F5E;">Because</strong> ${because}</p>`;
    if(metric||lift)html+=`<p><strong style="color:#454F5E;">Success metric:</strong> ${metric}${lift?' (target: '+lift+')':''}</p>`;
    if(conf||dur)html+=`<p><strong style="color:#454F5E;">Test parameters:</strong> ${conf} confidence level${dur?', run for '+dur:''}</p>`;
    el.innerHTML=html||'<p style="color:#787583;font-style:italic;">Fill in the fields above to build your hypothesis.</p>';
}
function getPlainText(){
    const c=document.getElementById('f-change').value,w=document.getElementById('f-who').value,r=document.getElementById('f-result').value,b=document.getElementById('f-because').value,m=document.getElementById('f-metric').value,l=document.getElementById('f-lift').value,conf=document.getElementById('f-confidence').value,d=document.getElementById('f-duration').value;
    return[c&&`If we change ${c}`,w&&`For ${w}`,r&&`We expect ${r}`,b&&`Because ${b}`,m&&`Success metric: ${m}${l?' (target: '+l+')':''}`,conf&&`Test parameters: ${conf} confidence level${d?', run for '+d:''}`].filter(Boolean).join('\n');
}
function copyStatement(){const t=getPlainText();navigator.clipboard.writeText(t);}
function copyMarkdown(){
    const c=document.getElementById('f-change').value,w=document.getElementById('f-who').value,r=document.getElementById('f-result').value,b=document.getElementById('f-because').value,m=document.getElementById('f-metric').value,l=document.getElementById('f-lift').value,conf=document.getElementById('f-confidence').value,d=document.getElementById('f-duration').value;
    const md=['## A/B Test Hypothesis',c&&`**If we change** ${c}`,w&&`**For** ${w}`,r&&`**We expect** ${r}`,b&&`**Because** ${b}`,m&&`**Success metric:** ${m}${l?' (target: '+l+')':''}`,conf&&`**Test parameters:** ${conf} confidence${d?', '+d:''}`].filter(Boolean).join('\n\n');
    navigator.clipboard.writeText(md);
}
const PRESETS={'CTA Colour':{change:'the CTA button from gray to green',who:'new visitors on the landing page',result:'a higher click-through rate to sign-up',because:'green is associated with positive action and provides stronger contrast against the white background',metric:'CTA click-through rate',lift:'+15%',dur:'2 weeks'},'Headline Copy':{change:'the headline from feature-focused to benefit-focused copy',who:'cold traffic from paid ads',result:'a reduction in bounce rate and increased scroll depth',because:'benefit-led headlines create immediate relevance for users who have not yet explored our features',metric:'Bounce rate / scroll depth',lift:'-10% bounce',dur:'3 weeks'},'Form Length':{change:'the registration form from 8 fields to 3 fields (email + password)',who:'mobile users at sign-up',result:'an increase in form completion rate',because:'shorter forms reduce friction particularly on mobile where typing is slower',metric:'Form completion rate',lift:'+20%',dur:'2 weeks'},'Social Proof':{change:'add customer logos and testimonials above the fold on the pricing page',who:'users who visit pricing without having signed up',result:'an increase in free trial starts',because:'social proof from recognisable brands reduces risk perception at the moment of decision',metric:'Trial sign-up rate',lift:'+10%',dur:'4 weeks'},'Pricing Layout':{change:'the pricing layout from 3 columns to a horizontal comparison table',who:'users who visit the pricing page on desktop',result:'higher plan selection clarity and a reduced "contact us" click rate',because:'tables allow direct feature comparison which reduces cognitive load during plan selection',metric:'Plan selected / contact rate',lift:'+8% selections',dur:'3 weeks'}};
function loadPreset(name){const p=PRESETS[name];if(!p)return;document.getElementById('f-change').value=p.change||'';document.getElementById('f-who').value=p.who||'';document.getElementById('f-result').value=p.result||'';document.getElementById('f-because').value=p.because||'';document.getElementById('f-metric').value=p.metric||'';document.getElementById('f-lift').value=p.lift||'';document.getElementById('f-duration').value=p.dur||'';generate();}
generate();
</script>
@endpush
@endsection
