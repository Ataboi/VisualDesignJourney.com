<?php
$pageTitle  = 'Palette Harmony Generator — Visual Design Journey';
$metaDesc   = 'Generate complementary, analogous, triadic, and split-complementary color palettes from any base color.';
$activeNav  = 'tools';
require_once __DIR__ . '/includes/header.php';
?>
<style>
  .tool-bg { background:#050505; color:#F5F5F7; min-height:calc(100vh - 60px); }
  .tool-wrap { max-width:860px; margin:0 auto; padding:56px 32px 80px; }
  @media(max-width:640px){ .tool-wrap{padding:40px 16px 60px} }
</style>

<div class="page-wrap tool-bg" style="padding-top:0;">
<div class="tool-wrap">

  <p style="font-size:10px;font-weight:700;color:#4D4D57;text-transform:uppercase;letter-spacing:.08em;margin:0 0 10px;">
    <a href="/tools" style="color:#4D4D57;text-decoration:none;" onmouseover="this.style.color='#D7FF3F'" onmouseout="this.style.color='#4D4D57'">Design Tools</a>
    <span style="margin:0 6px;">›</span>Color Tools
  </p>
  <h1 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:800;letter-spacing:-.025em;color:#F5F5F7;margin:0 0 12px;">Palette Harmony</h1>
  <p style="font-size:16px;color:#777783;margin:0 0 40px;line-height:1.6;">Generate harmonious color palettes from any base color. Click any swatch to copy the hex.</p>

  <!-- Color picker -->
  <div style="display:flex;gap:12px;align-items:center;margin-bottom:28px;flex-wrap:wrap;">
    <input type="color" id="base-picker" value="#D7FF3F" oninput="document.getElementById('base-hex').value=this.value;generate()" style="width:52px;height:52px;border:1px solid #2A2A35;border-radius:10px;background:#111117;cursor:pointer;padding:3px;">
    <input type="text" id="base-hex" value="#D7FF3F" class="vk-input" style="width:150px;flex:0 0 auto;" placeholder="#RRGGBB" oninput="syncPicker();generate()">
    <p style="font-size:13px;color:#4D4D57;margin:0;">Enter any hex color or pick from the swatch</p>
  </div>

  <!-- Harmony type -->
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:40px;">
    <?php
    $harmonies = ['complementary','analogous','triadic','split','tetradic','monochromatic'];
    foreach ($harmonies as $h):
    ?>
    <button onclick="setHarmony('<?= $h ?>')" id="h-<?= $h ?>" class="vk-pill <?= $h==='complementary'?'active':'' ?>"><?= ucfirst($h) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- Result swatches -->
  <div id="harmony-result" style="display:grid;gap:12px;margin-bottom:32px;"></div>

  <!-- CSS output -->
  <div class="code-block" id="css-output-wrap" style="display:none;">
    <div class="code-block-header">
      <span class="code-block-label">CSS Custom Properties</span>
      <button class="vk-copy-btn" onclick="copyCSS(this)">Copy</button>
    </div>
    <pre id="css-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
  </div>

</div>
</div>

<script>
let harmony = 'complementary';

function setHarmony(h) {
  harmony = h;
  document.querySelectorAll('[id^="h-"]').forEach(b => b.classList.toggle('active', b.id==='h-'+h));
  generate();
}

function hexToHsl(hex) {
  hex = hex.replace('#','');
  if (hex.length===3) hex = hex.split('').map(c=>c+c).join('');
  let r=parseInt(hex.slice(0,2),16)/255, g=parseInt(hex.slice(2,4),16)/255, b=parseInt(hex.slice(4,6),16)/255;
  const max=Math.max(r,g,b), min=Math.min(r,g,b);
  let h, s, l=(max+min)/2;
  if (max===min) { h=s=0; } else {
    const d=max-min; s=l>0.5?d/(2-max-min):d/(max+min);
    switch(max){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;case b:h=(r-g)/d+4;}
    h/=6;
  }
  return [h*360, s*100, l*100];
}

function hslToHex(h, s, l) {
  h/=360; s/=100; l/=100;
  const f=n=>{const k=(n+h*12)%12,a=s*Math.min(l,1-l);return l-a*Math.max(-1,Math.min(k-3,Math.min(9-k,1)));};
  return '#'+[f(0),f(8),f(4)].map(x=>Math.round(x*255).toString(16).padStart(2,'0')).join('');
}

function harmonyColors(h, s, l, type) {
  const r=x=>((x%360)+360)%360;
  const p={
    complementary: [[h,s,l],[r(h+180),s,l]],
    analogous:     [[r(h-30),s,l],[h,s,l],[r(h+30),s,l],[r(h+60),s,l]],
    triadic:       [[h,s,l],[r(h+120),s,l],[r(h+240),s,l]],
    split:         [[h,s,l],[r(h+150),s,l],[r(h+210),s,l]],
    tetradic:      [[h,s,l],[r(h+90),s,l],[r(h+180),s,l],[r(h+270),s,l]],
    monochromatic: [[h,s,90],[h,s,70],[h,s,50],[h,s,30],[h,s,15]],
  };
  return (p[type]||p.complementary).map(([hh,ss,ll])=>hslToHex(hh,ss,ll));
}

function luminanceFor(hex) {
  hex = hex.replace('#','');
  const r=parseInt(hex.slice(0,2),16)/255, g=parseInt(hex.slice(2,4),16)/255, b=parseInt(hex.slice(4,6),16)/255;
  const lin=c=>c<=0.03928?c/12.92:Math.pow((c+0.055)/1.055,2.4);
  return 0.2126*lin(r)+0.7152*lin(g)+0.0722*lin(b);
}

function textColorFor(bg) {
  return luminanceFor(bg) > 0.35 ? '#050505' : '#F5F5F7';
}

function generate() {
  const hex = document.getElementById('base-hex').value.trim();
  if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
  const [h,s,l] = hexToHsl(hex);
  const colors = harmonyColors(h, s, l, harmony);
  const el = document.getElementById('harmony-result');
  el.style.gridTemplateColumns = `repeat(${colors.length}, 1fr)`;
  el.innerHTML = colors.map((c,i) =>
    `<div onclick="copyColor('${c}',this)" title="Click to copy ${c}" style="cursor:pointer;border-radius:12px;overflow:hidden;border:1px solid #2A2A35;transition:border-color 150ms,transform 150ms;" onmouseover="this.style.borderColor='rgba(215,255,63,0.4)';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='#2A2A35';this.style.transform=''">
      <div style="height:120px;background:${c};"></div>
      <div style="padding:10px 12px;background:#111117;">
        <p style="font-size:12px;font-family:var(--font-mono);color:#B8B8C3;margin:0 0 2px;">${c.toUpperCase()}</p>
        <p style="font-size:10px;color:#4D4D57;margin:0;">${i===0?'Base':''}</p>
      </div>
    </div>`
  ).join('');

  // CSS output
  const cssLines = colors.map((c,i)=>`  --color-${i+1}: ${c.toUpperCase()};`).join('\n');
  document.getElementById('css-output').textContent = `:root {\n${cssLines}\n}`;
  document.getElementById('css-output-wrap').style.display = 'block';
}

function copyColor(c, el) {
  navigator.clipboard.writeText(c.toUpperCase());
  const p = el.querySelector('p');
  const orig = p.textContent;
  p.textContent = 'Copied!'; p.style.color = '#D7FF3F';
  setTimeout(() => { p.textContent = orig; p.style.color = ''; }, 1500);
}

function copyCSS(btn) {
  navigator.clipboard.writeText(document.getElementById('css-output').textContent.trim());
  btn.textContent = 'Copied!'; btn.classList.add('copied');
  setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
}

function syncPicker() {
  const hex = document.getElementById('base-hex').value;
  if (hex.length===7) document.getElementById('base-picker').value = hex;
}

generate();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
