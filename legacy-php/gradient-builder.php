<?php
$pageTitle  = 'CSS Gradient Builder — Visual Design Journey';
$metaDesc   = 'Build CSS gradients visually. Linear, radial, and conic gradients with live preview and copy-ready code.';
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
  <h1 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:800;letter-spacing:-.025em;color:#F5F5F7;margin:0 0 12px;">Gradient Builder</h1>
  <p style="font-size:16px;color:#777783;margin:0 0 48px;line-height:1.6;">Build linear, radial, or conic CSS gradients with a live preview.</p>

  <div class="tool-grid-2col">
    <!-- Controls -->
    <div>
      <div style="margin-bottom:20px;">
        <label class="vk-label">Type</label>
        <div style="display:flex;gap:8px;">
          <button onclick="setType('linear')" id="type-linear" class="vk-pill active">Linear</button>
          <button onclick="setType('radial')" id="type-radial" class="vk-pill">Radial</button>
          <button onclick="setType('conic')"  id="type-conic"  class="vk-pill">Conic</button>
        </div>
      </div>

      <div id="angle-row" style="margin-bottom:20px;">
        <label class="vk-label">Angle: <span id="angle-val" style="color:#D7FF3F;font-family:var(--font-mono);">135°</span></label>
        <input type="range" id="angle" min="0" max="360" value="135" style="width:100%;" oninput="document.getElementById('angle-val').textContent=this.value+'°';update()">
      </div>

      <div style="margin-bottom:20px;">
        <label class="vk-label">Color Stops</label>
        <div id="stops">
          <div class="stop-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <input type="color" value="#D7FF3F" oninput="update()" style="width:40px;height:36px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;">
            <input type="range" min="0" max="100" value="0" oninput="update()" style="flex:1;">
            <span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#4D4D57;width:36px;">0%</span>
            <button onclick="removeStop(this)" style="background:none;border:none;color:#4D4D57;cursor:pointer;font-size:14px;padding:4px;" onmouseover="this.style.color='#FF4D4D'" onmouseout="this.style.color='#4D4D57'">×</button>
          </div>
          <div class="stop-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <input type="color" value="#8B5CF6" oninput="update()" style="width:40px;height:36px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;">
            <input type="range" min="0" max="100" value="100" oninput="update()" style="flex:1;">
            <span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#4D4D57;width:36px;">100%</span>
            <button onclick="removeStop(this)" style="background:none;border:none;color:#4D4D57;cursor:pointer;font-size:14px;padding:4px;" onmouseover="this.style.color='#FF4D4D'" onmouseout="this.style.color='#4D4D57'">×</button>
          </div>
        </div>
        <button onclick="addStop()" style="display:inline-flex;align-items:center;gap:6px;background:#17171F;border:1px solid #2A2A35;color:#F5F5F7;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:8px;transition:border-color 150ms;" onmouseover="this.style.borderColor='#3A3A48'" onmouseout="this.style.borderColor='#2A2A35'">
          <i class="fa-solid fa-plus" style="font-size:10px;"></i> Add Stop
        </button>
      </div>

      <!-- Quick presets -->
      <div style="margin-bottom:0;">
        <label class="vk-label">Presets</label>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          <button onclick="loadPreset(['#D7FF3F','#8B5CF6'],'linear',135)" class="vk-pill" style="font-size:11px;padding:4px 10px;">Acid</button>
          <button onclick="loadPreset(['#FF4D4D','#FF8C42'],'linear',45)"  class="vk-pill" style="font-size:11px;padding:4px 10px;">Sunset</button>
          <button onclick="loadPreset(['#050505','#1E1B4B'],'linear',160)" class="vk-pill" style="font-size:11px;padding:4px 10px;">Void</button>
          <button onclick="loadPreset(['#38BDF8','#6EE7B7'],'linear',120)" class="vk-pill" style="font-size:11px;padding:4px 10px;">Ocean</button>
          <button onclick="loadPreset(['#F5F5F7','#B8B8C3'],'linear',180)" class="vk-pill" style="font-size:11px;padding:4px 10px;">Smoke</button>
          <button onclick="loadPreset(['#D7FF3F','#38BDF8','#8B5CF6'],'conic',0)" class="vk-pill" style="font-size:11px;padding:4px 10px;">Rainbow</button>
        </div>
      </div>
    </div>

    <!-- Preview + output -->
    <div>
      <div id="preview" style="width:100%;aspect-ratio:1;border-radius:14px;margin-bottom:16px;border:1px solid #2A2A35;transition:background .1s;"></div>
      <div class="code-block">
        <div class="code-block-header">
          <span class="code-block-label">CSS</span>
          <button class="vk-copy-btn" onclick="copyCode(this)">Copy</button>
        </div>
        <pre id="gradient-output" style="padding:16px 20px;margin:0;font-size:12px;overflow-x:auto;white-space:pre-wrap;"></pre>
      </div>
    </div>
  </div>

</div>
</div>

<script>
let gradType = 'linear';

function setType(t) {
  gradType = t;
  ['linear','radial','conic'].forEach(x => {
    document.getElementById('type-'+x).classList.toggle('active', x===t);
  });
  document.getElementById('angle-row').style.display = t==='linear' ? 'block' : 'none';
  update();
}

function getStops() {
  return [...document.querySelectorAll('.stop-row')].map(row => {
    const color = row.querySelector('input[type=color]').value;
    const pct   = row.querySelector('input[type=range]').value;
    row.querySelector('.stop-pct').textContent = pct + '%';
    return `${color} ${pct}%`;
  });
}

function update() {
  const stops = getStops().join(', ');
  let css;
  if (gradType==='linear')      css = `linear-gradient(${document.getElementById('angle').value}deg, ${stops})`;
  else if (gradType==='radial') css = `radial-gradient(circle, ${stops})`;
  else                          css = `conic-gradient(${stops})`;
  document.getElementById('preview').style.background = css;
  document.getElementById('gradient-output').textContent = `background: ${css};`;
}

function addStop() {
  const colors = ['#38BDF8','#FF4D4D','#6EE7B7','#FFB020','#F5F5F7'];
  const col = colors[document.querySelectorAll('.stop-row').length % colors.length];
  const row = document.createElement('div');
  row.className = 'stop-row';
  row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;';
  row.innerHTML = `<input type="color" value="${col}" oninput="update()" style="width:40px;height:36px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;"><input type="range" min="0" max="100" value="50" oninput="update()" style="flex:1;accent-color:#D7FF3F;"><span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#4D4D57;width:36px;">50%</span><button onclick="removeStop(this)" style="background:none;border:none;color:#4D4D57;cursor:pointer;font-size:14px;padding:4px;" onmouseover="this.style.color='#FF4D4D'" onmouseout="this.style.color='#4D4D57'">×</button>`;
  document.getElementById('stops').appendChild(row);
  update();
}

function removeStop(btn) {
  const rows = document.querySelectorAll('.stop-row');
  if (rows.length <= 2) return;
  btn.closest('.stop-row').remove();
  update();
}

function loadPreset(colors, type, angle) {
  document.getElementById('stops').innerHTML = '';
  colors.forEach((c, i) => {
    const pct = Math.round((i / (colors.length-1)) * 100);
    const row = document.createElement('div');
    row.className = 'stop-row';
    row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;';
    row.innerHTML = `<input type="color" value="${c}" oninput="update()" style="width:40px;height:36px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;"><input type="range" min="0" max="100" value="${pct}" oninput="update()" style="flex:1;accent-color:#D7FF3F;"><span class="stop-pct" style="font-size:12px;font-family:var(--font-mono);color:#4D4D57;width:36px;">${pct}%</span><button onclick="removeStop(this)" style="background:none;border:none;color:#4D4D57;cursor:pointer;font-size:14px;padding:4px;" onmouseover="this.style.color='#FF4D4D'" onmouseout="this.style.color='#4D4D57'">×</button>`;
    document.getElementById('stops').appendChild(row);
  });
  setType(type);
  document.getElementById('angle').value = angle;
  document.getElementById('angle-val').textContent = angle + '°';
  update();
}

function copyCode(btn) {
  navigator.clipboard.writeText(document.getElementById('gradient-output').textContent.trim());
  btn.textContent = 'Copied!'; btn.classList.add('copied');
  setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
}

update();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
