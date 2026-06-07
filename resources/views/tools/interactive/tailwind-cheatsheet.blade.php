@extends('layouts.app')
@section('title', 'Tailwind CSS Cheatsheet')
@section('content')
<div class="vk-container" style="padding-top:56px;padding-bottom:80px;max-width:1000px;">
    <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Dev Tools</p>
    <h1 class="page-title" style="margin:0 0 12px;">Tailwind CSS Cheatsheet</h1>
    <p style="font-size:16px;color:#454F5E;margin:0 0 32px;">Quick-reference for the most-used Tailwind classes. Click any class to copy.</p>

    <div style="display:flex;gap:10px;margin-bottom:32px;flex-wrap:wrap;align-items:center;">
        <input type="text" id="tw-search" placeholder="Search classes..." oninput="filterCheatsheet()" style="flex:1;min-width:200px;background:#FFFFFF;border:.5px solid #E9ECEF;border-radius:10px;padding:11px 16px;font-size:14px;color:#131B2E;font-family:var(--font-mono);outline:none;" onfocus="this.style.borderColor='rgba(127,119,221,0.4)'" onblur="this.style.borderColor='#E9ECEF'">
        <div style="display:flex;flex-wrap:wrap;gap:6px;" id="category-filters"></div>
    </div>

    <div id="cheatsheet-body"></div>
</div>
@push('scripts')
<script>
const DATA = {
    'Spacing': {
        'Padding': ['p-0','p-1','p-2','p-3','p-4','p-5','p-6','p-8','p-10','p-12','p-16','p-20','p-24','px-4','py-2','pt-4','pb-4','pl-4','pr-4'],
        'Margin': ['m-0','m-1','m-2','m-4','m-auto','mx-auto','my-4','mt-4','mb-4','ml-4','mr-4','-mt-4','-mb-4'],
        'Gap': ['gap-0','gap-1','gap-2','gap-3','gap-4','gap-5','gap-6','gap-8','gap-x-4','gap-y-4'],
        'Space Between': ['space-x-2','space-x-4','space-y-2','space-y-4'],
    },
    'Sizing': {
        'Width': ['w-0','w-px','w-1','w-4','w-8','w-16','w-32','w-64','w-auto','w-full','w-screen','w-min','w-max','w-fit','w-1/2','w-1/3','w-2/3','w-1/4','w-3/4'],
        'Height': ['h-0','h-px','h-4','h-8','h-16','h-32','h-64','h-auto','h-full','h-screen','h-min','h-max','h-fit','h-svh','h-dvh'],
        'Max / Min': ['max-w-xs','max-w-sm','max-w-md','max-w-lg','max-w-xl','max-w-2xl','max-w-7xl','max-w-full','min-h-screen','min-w-0'],
    },
    'Typography': {
        'Font Size': ['text-xs','text-sm','text-base','text-lg','text-xl','text-2xl','text-3xl','text-4xl','text-5xl','text-6xl','text-7xl','text-8xl','text-9xl'],
        'Font Weight': ['font-thin','font-light','font-normal','font-medium','font-semibold','font-bold','font-extrabold','font-black'],
        'Line Height': ['leading-none','leading-tight','leading-snug','leading-normal','leading-relaxed','leading-loose'],
        'Letter Spacing': ['tracking-tighter','tracking-tight','tracking-normal','tracking-wide','tracking-wider','tracking-widest'],
        'Text Align': ['text-left','text-center','text-right','text-justify'],
        'Text Transform': ['uppercase','lowercase','capitalize','normal-case'],
        'Text Decoration': ['underline','no-underline','line-through'],
        'Text Color': ['text-white','text-black','text-gray-400','text-gray-500','text-gray-900','text-inherit','text-current'],
    },
    'Flexbox': {
        'Display': ['flex','inline-flex','hidden','block','inline-block','grid','inline-grid'],
        'Direction': ['flex-row','flex-col','flex-row-reverse','flex-col-reverse'],
        'Wrap': ['flex-wrap','flex-nowrap','flex-wrap-reverse'],
        'Justify': ['justify-start','justify-end','justify-center','justify-between','justify-around','justify-evenly'],
        'Align Items': ['items-start','items-end','items-center','items-baseline','items-stretch'],
        'Align Self': ['self-auto','self-start','self-end','self-center','self-stretch'],
        'Flex': ['flex-1','flex-auto','flex-none','grow','shrink','shrink-0'],
    },
    'Grid': {
        'Columns': ['grid-cols-1','grid-cols-2','grid-cols-3','grid-cols-4','grid-cols-5','grid-cols-6','grid-cols-12','grid-cols-none'],
        'Column Span': ['col-span-1','col-span-2','col-span-3','col-span-4','col-span-6','col-span-full'],
        'Auto': ['grid-flow-row','grid-flow-col','grid-flow-dense','auto-cols-fr','auto-rows-fr'],
    },
    'Borders': {
        'Border Width': ['border','border-0','border-2','border-4','border-t','border-b','border-l','border-r','border-x','border-y'],
        'Border Radius': ['rounded-none','rounded-sm','rounded','rounded-md','rounded-lg','rounded-xl','rounded-2xl','rounded-3xl','rounded-full'],
        'Border Color': ['border-transparent','border-gray-200','border-gray-300','border-gray-700','border-white','border-black'],
        'Border Style': ['border-solid','border-dashed','border-dotted','border-none'],
        'Ring': ['ring','ring-0','ring-1','ring-2','ring-4','ring-offset-2','ring-white','ring-black'],
    },
    'Backgrounds': {
        'Color': ['bg-transparent','bg-white','bg-black','bg-gray-50','bg-gray-100','bg-gray-900','bg-inherit','bg-current'],
        'Gradient': ['bg-gradient-to-r','bg-gradient-to-l','bg-gradient-to-t','bg-gradient-to-b','bg-gradient-to-tr','bg-gradient-to-br','from-white','to-black','via-gray-500'],
        'Opacity': ['bg-opacity-0','bg-opacity-25','bg-opacity-50','bg-opacity-75','bg-opacity-100'],
    },
    'Effects': {
        'Shadow': ['shadow-none','shadow-sm','shadow','shadow-md','shadow-lg','shadow-xl','shadow-2xl','shadow-inner'],
        'Opacity': ['opacity-0','opacity-25','opacity-50','opacity-75','opacity-100'],
        'Blur': ['blur-none','blur-sm','blur','blur-md','blur-lg','blur-xl','blur-2xl','blur-3xl'],
        'Mix Blend': ['mix-blend-multiply','mix-blend-screen','mix-blend-overlay','mix-blend-darken','mix-blend-lighten'],
        'Backdrop': ['backdrop-blur-sm','backdrop-blur','backdrop-blur-md','backdrop-blur-lg','backdrop-blur-xl','backdrop-opacity-50'],
    },
    'Transitions': {
        'Transition': ['transition','transition-all','transition-colors','transition-opacity','transition-transform','transition-shadow','transition-none'],
        'Duration': ['duration-75','duration-100','duration-150','duration-200','duration-300','duration-500','duration-700','duration-1000'],
        'Easing': ['ease-linear','ease-in','ease-out','ease-in-out'],
        'Transform': ['scale-90','scale-95','scale-100','scale-105','scale-110','rotate-0','rotate-45','rotate-90','rotate-180','-rotate-45','translate-x-4','translate-y-4','-translate-y-1'],
    },
    'Position': {
        'Position': ['static','relative','absolute','fixed','sticky'],
        'Inset': ['inset-0','inset-x-0','inset-y-0','top-0','right-0','bottom-0','left-0','top-4','right-4'],
        'Z-Index': ['z-0','z-10','z-20','z-30','z-40','z-50','z-auto'],
    },
    'Overflow': {
        'Overflow': ['overflow-auto','overflow-hidden','overflow-visible','overflow-scroll','overflow-x-auto','overflow-y-auto','overflow-x-hidden','overflow-y-hidden','truncate'],
        'Object Fit': ['object-contain','object-cover','object-fill','object-none','object-scale-down'],
    },
    'Cursor & Pointer': {
        'Cursor': ['cursor-auto','cursor-default','cursor-pointer','cursor-wait','cursor-text','cursor-move','cursor-not-allowed','cursor-crosshair','cursor-grab'],
        'Pointer Events': ['pointer-events-none','pointer-events-auto'],
        'User Select': ['select-none','select-text','select-all','select-auto'],
    },
};

let activeCategory='all';
function buildCategoryFilters(){
    const el=document.getElementById('category-filters');
    const cats=['All',...Object.keys(DATA)];
    el.innerHTML=cats.map(c=>`<button onclick="setCategory('${c}')" id="cat-${c.replace(/\s/g,'-')}" class="filter-pill${c==='All'?' active':''}" style="cursor:pointer;border:none;font-size:11px;">${c}</button>`).join('');
}
function setCategory(cat){
    activeCategory=cat==='All'?'all':cat;
    document.querySelectorAll('[id^=cat-]').forEach(b=>b.classList.toggle('active',b.id==='cat-'+cat.replace(/\s/g,'-')));
    renderCheatsheet();
}
function filterCheatsheet(){renderCheatsheet();}
function renderCheatsheet(){
    const q=document.getElementById('tw-search').value.toLowerCase();
    const body=document.getElementById('cheatsheet-body');
    let html='';
    for(const [cat,groups] of Object.entries(DATA)){
        if(activeCategory!=='all'&&cat!==activeCategory)continue;
        let catHtml='';
        for(const [group,classes] of Object.entries(groups)){
            const filtered=classes.filter(c=>!q||c.includes(q));
            if(!filtered.length)continue;
            catHtml+=`<div style="margin-bottom:16px;"><p style="font-size:11px;color:#787583;margin:0 0 8px;font-weight:600;">${group}</p><div style="display:flex;flex-wrap:wrap;gap:5px;">${filtered.map(c=>`<button onclick="copyClass('${c}',this)" title="${c}" style="background:#FFFFFF;border:1px solid #1A1A24;border-radius:6px;padding:4px 9px;font-size:11px;color:#454F5E;font-family:var(--font-mono);cursor:pointer;" onmouseover="this.style.background='#1A1A24';this.style.color='#7F77DD'" onmouseout="if(!this.dataset.copied){this.style.background='#FFFFFF';this.style.color='#454F5E'}">${c}</button>`).join('')}</div></div>`;
        }
        if(!catHtml)continue;
        html+=`<div style="margin-bottom:36px;"><h3 style="font-size:14px;font-weight:700;color:#131B2E;margin:0 0 16px;padding-bottom:10px;border-bottom:1px solid #1A1A24;">${cat}</h3>${catHtml}</div>`;
    }
    body.innerHTML=html||'<p style="color:#787583;">No classes match your search.</p>';
}
function copyClass(cls,btn){
    navigator.clipboard.writeText(cls);
    const orig=btn.textContent;btn.textContent='✓';btn.style.color='#7F77DD';btn.style.background='#1A1A24';btn.dataset.copied='1';
    setTimeout(()=>{btn.textContent=orig;btn.style.color='';btn.style.background='#FFFFFF';delete btn.dataset.copied;},1200);
}
buildCategoryFilters();renderCheatsheet();
</script>
@endpush
@endsection
