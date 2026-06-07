<?php
$pageTitle  = 'WCAG Contrast Checker — Visual Design Journey';
$metaDesc   = 'Check WCAG 2.1 color contrast ratios instantly. Enter foreground and background colors to get pass/fail results for AA and AAA.';
$activeNav  = 'tools';
require_once __DIR__ . '/includes/header.php';
?>
<style>
  .tool-bg { background:#050505; color:#F5F5F7; min-height:calc(100vh - 60px); }
  .tool-wrap { max-width:780px; margin:0 auto; padding:56px 32px 80px; }
  @media(max-width:640px){ .tool-wrap{padding:40px 16px 60px} }
  .result-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:32px; }
  @media(max-width:520px){ .result-grid{grid-template-columns:repeat(2,1fr)} }
  .result-box { border:1px solid #2A2A35; border-radius:12px; padding:16px; text-align:center; }
</style>

<div class="page-wrap tool-bg" style="padding-top:0;">
<div class="tool-wrap">

  <p style="font-size:10px;font-weight:700;color:#4D4D57;text-transform:uppercase;letter-spacing:.08em;margin:0 0 10px;">
    <a href="/tools" style="color:#4D4D57;text-decoration:none;" onmouseover="this.style.color='#D7FF3F'" onmouseout="this.style.color='#4D4D57'">Design Tools</a>
    <span style="margin:0 6px;">›</span>Accessibility
  </p>
  <h1 style="font-size:clamp(1.75rem,4vw,2.75rem);font-weight:800;letter-spacing:-.025em;color:#F5F5F7;margin:0 0 12px;">Contrast Checker</h1>
  <p style="font-size:16px;color:#777783;margin:0 0 40px;line-height:1.6;">Check WCAG 2.1 contrast ratios. Enter any two colors to get instant AA / AAA results.</p>

  <!-- Color pickers -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px;">
    <div>
      <label class="vk-label">Foreground (Text)</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <input type="color" id="fg-picker" value="#F5F5F7" style="width:44px;height:44px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;">
        <input type="text"  id="fg-hex"    value="#F5F5F7" class="vk-input" style="width:auto;flex:1;" placeholder="#F5F5F7">
      </div>
    </div>
    <div>
      <label class="vk-label">Background</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <input type="color" id="bg-picker" value="#050505" style="width:44px;height:44px;border:1px solid #2A2A35;border-radius:8px;background:#111117;cursor:pointer;padding:2px;">
        <input type="text"  id="bg-hex"    value="#050505" class="vk-input" style="width:auto;flex:1;" placeholder="#050505">
      </div>
    </div>
  </div>

  <!-- Live preview -->
  <div id="preview-box" style="border-radius:14px;padding:40px;margin-bottom:32px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:140px;transition:background 150ms;border:1px solid #2A2A35;">
    <p id="preview-lg" style="font-size:2rem;font-weight:700;margin:0 0 8px;transition:color 150ms;">Large Text Sample</p>
    <p id="preview-sm" style="font-size:14px;margin:0;transition:color 150ms;">Normal body text at 14px weight 400</p>
  </div>

  <!-- Results -->
  <div class="result-grid">
    <div class="result-box">
      <p style="font-size:10px;font-weight:600;color:#4D4D57;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">Ratio</p>
      <p id="ratio" style="font-size:1.75rem;font-weight:800;color:#F5F5F7;margin:0;font-family:var(--font-mono);">—</p>
    </div>
    <div class="result-box">
      <p style="font-size:10px;font-weight:600;color:#4D4D57;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">AA Normal</p>
      <p id="aa-normal" style="font-size:1.1rem;font-weight:700;margin:0;">—</p>
    </div>
    <div class="result-box">
      <p style="font-size:10px;font-weight:600;color:#4D4D57;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">AA Large</p>
      <p id="aa-large" style="font-size:1.1rem;font-weight:700;margin:0;">—</p>
    </div>
    <div class="result-box">
      <p style="font-size:10px;font-weight:600;color:#4D4D57;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">AAA</p>
      <p id="aaa" style="font-size:1.1rem;font-weight:700;margin:0;">—</p>
    </div>
  </div>

  <!-- WCAG guide -->
  <div style="background:#111117;border:1px solid #2A2A35;border-radius:12px;padding:16px 20px;margin-bottom:32px;">
    <p style="font-size:11px;font-weight:600;color:#4D4D57;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">WCAG 2.1 Requirements</p>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;font-size:12px;color:#B8B8C3;">
      <div><strong style="color:#F5F5F7;display:block;margin-bottom:2px;">AA Normal</strong>≥ 4.5:1 for body text</div>
      <div><strong style="color:#F5F5F7;display:block;margin-bottom:2px;">AA Large</strong>≥ 3:1 for 18pt+ or 14pt+ bold</div>
      <div><strong style="color:#F5F5F7;display:block;margin-bottom:2px;">AAA</strong>≥ 7:1 for enhanced contrast</div>
    </div>
  </div>

  <!-- Quick presets -->
  <p style="font-size:10px;font-weight:700;color:#4D4D57;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px;">Quick Presets</p>
  <div style="display:flex;flex-wrap:wrap;gap:8px;">
    <?php
    $presets = [
      ['#FFFFFF','#000000','White on Black'],
      ['#000000','#FFFFFF','Black on White'],
      ['#F5F5F7','#050505','Light on Dark'],
      ['#050505','#D7FF3F','Dark on Acid'],
      ['#FFFFFF','#7F77DD','White on Purple'],
      ['#050505','#F5F5F7','Dark on Light'],
    ];
    foreach ($presets as [$fg,$bg,$label]):
    ?>
    <button onclick="setColors('<?= $fg ?>','<?= $bg ?>')" style="background:#111117;border:1px solid #2A2A35;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:12px;color:#B8B8C3;display:flex;align-items:center;gap:8px;font-family:inherit;transition:border-color 150ms;" onmouseover="this.style.borderColor='rgba(215,255,63,0.3)'" onmouseout="this.style.borderColor='#2A2A35'">
      <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $fg ?>;border:1px solid rgba(255,255,255,.1);"></span>
      <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $bg ?>;border:1px solid rgba(255,255,255,.1);"></span>
      <?= htmlspecialchars($label) ?>
    </button>
    <?php endforeach; ?>
  </div>

</div>
</div>

<script>
function hexToRgb(hex) {
  hex = hex.replace('#','');
  if (hex.length===3) hex = hex.split('').map(c=>c+c).join('');
  const n = parseInt(hex, 16);
  return [n>>16&255, n>>8&255, n&255];
}
function linearize(c) {
  c /= 255;
  return c <= 0.03928 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4);
}
function luminance(hex) {
  const [r,g,b] = hexToRgb(hex).map(linearize);
  return 0.2126*r + 0.7152*g + 0.0722*b;
}
function contrast(fg, bg) {
  const l1=luminance(fg), l2=luminance(bg);
  const [hi,lo] = l1>l2 ? [l1,l2] : [l2,l1];
  return (hi+0.05)/(lo+0.05);
}
function pass(el, val, threshold) {
  const ok = val >= threshold;
  el.textContent = ok ? '✓ Pass' : '✗ Fail';
  el.style.color  = ok ? '#6EE7B7' : '#FF4D4D';
}
function update() {
  const fg = document.getElementById('fg-hex').value.trim();
  const bg = document.getElementById('bg-hex').value.trim();
  if (!/^#[0-9a-fA-F]{3,6}$/.test(fg) || !/^#[0-9a-fA-F]{3,6}$/.test(bg)) return;
  const r = contrast(fg, bg);
  document.getElementById('ratio').textContent = r.toFixed(2) + ':1';
  pass(document.getElementById('aa-normal'), r, 4.5);
  pass(document.getElementById('aa-large'),  r, 3);
  pass(document.getElementById('aaa'),       r, 7);
  document.getElementById('preview-box').style.background = bg;
  document.getElementById('preview-lg').style.color = fg;
  document.getElementById('preview-sm').style.color = fg;
  if (fg.length===7) document.getElementById('fg-picker').value = fg;
  if (bg.length===7) document.getElementById('bg-picker').value = bg;
}
function setColors(fg, bg) {
  document.getElementById('fg-hex').value = fg;
  document.getElementById('bg-hex').value = bg;
  update();
}
document.getElementById('fg-hex').addEventListener('input', update);
document.getElementById('bg-hex').addEventListener('input', update);
document.getElementById('fg-picker').addEventListener('input', e => { document.getElementById('fg-hex').value = e.target.value; update(); });
document.getElementById('bg-picker').addEventListener('input', e => { document.getElementById('bg-hex').value = e.target.value; update(); });
update();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
