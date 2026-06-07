<?php
$pageTitle  = 'Box Shadow Builder — Visual Design Journey';
$metaDesc   = 'Build CSS box-shadows visually with live preview. Adjust offset, blur, spread, and color then copy the CSS.';
$activeNav  = 'tools';
require_once __DIR__ . '/includes/header.php';
?>
<style>
  .tool-bg { background:#050505; color:#F5F5F7; min-height:calc(100vh - 60px); }
  .tool-wrap { max-width:900px; margin:0 auto; padding:56px 32px 80px; }
  @media(max-width:640px){ .tool-wrap{padding:40px 16px 60px} }
  input[type=range]{ accent-color:#D7FF3F; }
</style>

<div class="page-wrap tool-bg" style="padding-top:0;">
<div class="tool-wrap">

  <p style="font-size:10px;font-weight:700;color:#4D4D57;text-transform:uppercase;letter-spacing:.08em;margin:0 0 10px;">
    <a href="/tools" style="color:#4D4D57;text-decoration:none;" onmouseover="this.style.color='#D7FF3F'" onmouseout="this.style.color='#4D4D57'">Design Tools</a>
    <span style="margin:0 6px;">›</span>CSS Tools
  </p>
  <h1 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:800;letter-spacing:-.025em;color:#F5F5F7;margin:0 0 12px;">Box Shadow Builder</h1>
  <p style="font-size:16px;color:#777783;margin:0 0 48px;line-height:1.6;">Adjust every parameter visually and get the CSS in one click.</p>

  <div class="tool-grid-2col">
    <!-- Controls -->
    <div>
      <?php
      $sliders = [
        ['X Offset','x','0','-80','80'],
        ['Y Offset','y','8','-80','80'],
        ['Blur','blur','24','0','120'],
        ['Spread','spread','0','-40','40'],
      ];
      foreach ($sliders as [$lbl,$id,$def,$min,$max]): ?>
      <div style="margin-bottom:18px;">
        <label style="font-size:11px;font-weight:600;color:#B8B8C3;display:flex;justify-content:space-between;margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em;">
          <span><?= $lbl ?></span>
          <span id="<?= $id ?>-val" style="color:#D7FF3F;font-family:var(--font-mono);"><?= $def ?>px</span>
        </label>
        <input type="range" id="<?= $id ?>" min="<?= $min ?>" max="<?= $max ?>" value="<?= $def ?>" style="width:100%;" oninput="document.getElementById('<?= $id ?>-val').textContent=this.value+'px';update()">
      </div>
      <?php endforeach; ?>

      <div style="margin-bottom:18px;">
        <label class="vk-label">Color &amp; Opacity</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="color" id="shadow-color" value="#000000" oninput="update()" style="width:44px;height:44px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;">
          <input type="number" id="shadow-opacity" value="40" min="0" max="100" oninput="update()" style="width:72px;background:#111117;border:1px solid #2A2A35;border-radius:8px;padding:8px 12px;font-size:13px;color:#F5F5F7;font-family:var(--font-mono);outline:none;">
          <span style="font-size:12px;color:#4D4D57;">% opacity</span>
        </div>
      </div>

      <div style="margin-bottom:24px;">
        <label class="vk-label">Type</label>
        <div style="display:flex;gap:8px;">
          <button onclick="setInset(false)" id="btn-outer" class="vk-pill active">Outer</button>
          <button onclick="setInset(true)"  id="btn-inner" class="vk-pill">Inner (inset)</button>
        </div>
      </div>

      <!-- Quick presets -->
      <div>
        <label class="vk-label">Presets</label>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          <button onclick="applyPreset(0,4,16,0,'#000000',20,false)"  class="vk-pill" style="font-size:11px;padding:4px 10px;">Subtle</button>
          <button onclick="applyPreset(0,8,32,0,'#000000',30,false)"  class="vk-pill" style="font-size:11px;padding:4px 10px;">Soft</button>
          <button onclick="applyPreset(0,20,60,0,'#000000',50,false)" class="vk-pill" style="font-size:11px;padding:4px 10px;">Dramatic</button>
          <button onclick="applyPreset(0,0,20,0,'#D7FF3F',40,false)"  class="vk-pill" style="font-size:11px;padding:4px 10px;">Acid Glow</button>
          <button onclick="applyPreset(0,0,24,0,'#8B5CF6',40,false)"  class="vk-pill" style="font-size:11px;padding:4px 10px;">Purple Glow</button>
          <button onclick="applyPreset(0,2,8,0,'#000000',60,true)"    class="vk-pill" style="font-size:11px;padding:4px 10px;">Inset</button>
        </div>
      </div>
    </div>

    <!-- Preview -->
    <div>
      <div style="display:flex;align-items:center;justify-content:center;background:#F5F5F7;border-radius:14px;padding:60px 40px;margin-bottom:16px;transition:background .1s;" id="preview-bg">
        <div id="shadow-preview" style="width:160px;height:100px;background:#ffffff;border-radius:12px;transition:box-shadow .1s;"></div>
      </div>

      <div style="display:flex;gap:8px;margin-bottom:16px;">
        <button onclick="setBg('light')" id="bg-light" class="vk-pill active" style="font-size:11px;padding:4px 10px;">Light BG</button>
        <button onclick="setBg('dark')"  id="bg-dark"  class="vk-pill"        style="font-size:11px;padding:4px 10px;">Dark BG</button>
      </div>

      <div class="code-block">
        <div class="code-block-header">
          <span class="code-block-label">CSS</span>
          <button class="vk-copy-btn" onclick="copyCode(this)">Copy</button>
        </div>
        <pre id="shadow-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
      </div>
    </div>
  </div>

</div>
</div>

<script>
let isInset = false;
let bgMode  = 'light';

function setInset(v) {
  isInset = v;
  document.getElementById('btn-outer').classList.toggle('active', !v);
  document.getElementById('btn-inner').classList.toggle('active', v);
  update();
}

function setBg(mode) {
  bgMode = mode;
  document.getElementById('bg-light').classList.toggle('active', mode==='light');
  document.getElementById('bg-dark').classList.toggle('active', mode==='dark');
  document.getElementById('preview-bg').style.background = mode==='dark' ? '#111117' : '#F5F5F7';
  document.getElementById('shadow-preview').style.background = mode==='dark' ? '#1C1C24' : '#ffffff';
}

function hexToRgba(hex, opacity) {
  hex = hex.replace('#','');
  if (hex.length===3) hex = hex.split('').map(c=>c+c).join('');
  const r=parseInt(hex.slice(0,2),16), g=parseInt(hex.slice(2,4),16), b=parseInt(hex.slice(4,6),16);
  return `rgba(${r},${g},${b},${opacity/100})`;
}

function update() {
  const x       = document.getElementById('x').value;
  const y       = document.getElementById('y').value;
  const blur    = document.getElementById('blur').value;
  const spread  = document.getElementById('spread').value;
  const color   = hexToRgba(document.getElementById('shadow-color').value, document.getElementById('shadow-opacity').value);
  const prefix  = isInset ? 'inset ' : '';
  const val     = `${prefix}${x}px ${y}px ${blur}px ${spread}px ${color}`;
  document.getElementById('shadow-preview').style.boxShadow = val;
  document.getElementById('shadow-output').textContent = `box-shadow: ${val};`;
}

function applyPreset(x, y, blur, spread, color, opacity, inset) {
  document.getElementById('x').value       = x;
  document.getElementById('y').value       = y;
  document.getElementById('blur').value    = blur;
  document.getElementById('spread').value  = spread;
  document.getElementById('shadow-color').value   = color;
  document.getElementById('shadow-opacity').value = opacity;
  document.getElementById('x-val').textContent      = x+'px';
  document.getElementById('y-val').textContent      = y+'px';
  document.getElementById('blur-val').textContent   = blur+'px';
  document.getElementById('spread-val').textContent = spread+'px';
  setInset(inset);
}

function copyCode(btn) {
  navigator.clipboard.writeText(document.getElementById('shadow-output').textContent.trim());
  btn.textContent = 'Copied!'; btn.classList.add('copied');
  setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
}

update();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
