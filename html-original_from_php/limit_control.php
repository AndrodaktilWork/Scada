<?php
// limit_control.php — Ръчно лимитиране (киоск попъп, fixed size, равномерен вътрешен отстъп)
require_once __DIR__ . '/db_connection.php';
header('Content-Type: text/html; charset=utf-8');

function int_or_null($v){ return (isset($v) && $v!=='') ? (int)$v : null; }

$customerId = int_or_null($_GET['customer'] ?? $_POST['customer'] ?? null);
if (!$customerId) {
    http_response_code(400);
    echo "<!doctype html><meta charset='utf-8'><body style='font-family:Verdana;padding:24px'>Липсва параметър <b>customer</b>.</body>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $limit = int_or_null($_POST['limit'] ?? null);
    if ($limit===null || $limit<0 || $limit>100) {
        http_response_code(422);
        echo "<!doctype html><meta charset='utf-8'><body style='font-family:Verdana;padding:24px;color:#c0392b'>Невалиден лимит (0–100%).</body>";
        exit;
    }
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS ManualLimits (
              LimitID    INT AUTO_INCREMENT PRIMARY KEY,
              CustomerID INT NOT NULL,
              LimitPct   INT NOT NULL,
              SetAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $st = $conn->prepare("INSERT INTO ManualLimits (CustomerID, LimitPct) VALUES (:cid,:pct)");
        $st->execute([':cid'=>$customerId, ':pct'=>$limit]);

        echo "<!doctype html><meta charset='utf-8'>
              <style>body{font-family:Verdana,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#2c3e50}</style>
              <div>Ръчният лимит е зададен: <b>{$limit}%</b>. Затваряне…</div>
              <script>
                if (window.opener) { try { window.opener.location.reload(); } catch(_){} }
                setTimeout(()=>window.close(), 700);
              </script>";
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo "<!doctype html><meta charset='utf-8'><style>body{font-family:Verdana;padding:18px;background:#fff3f3;color:#c0392b}</style>
              <h3>Грешка</h3><p>".htmlspecialchars($e->getMessage())."</p>";
        exit;
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
<meta charset="utf-8">
<title>Ръчно лимитиране</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{
    /* ЦЕЛЕВИ ВЪТРЕШЕН (viewport) РАЗМЕР на попъпа — адаптивен към бордерите */
    --win-w:600px;
    --win-h:700px;

    --shell-pad:16px;   /* външен отстъп от ръба на прозореца */
    --panel-pad:18px;   /* вътрешен падинг на картата */
    --v-gap:14px;       /* вертикален интервал между секциите */
    --panel-border:#cfd8e3;
  }
  /* === КИОСК ПРОЗОРЕЦ (фиксиран) === */
  html,body{
    margin:0; padding:0;
    width:var(--win-w); height:var(--win-h); overflow:hidden;
    background:#eef2f7; font-family:Verdana, sans-serif; color:#2c3e50;
  }
  /* Равномерен отстъп от всички страни спрямо ръба на прозореца */
  .shell{
    width:100%; height:100%;
    padding:var(--shell-pad);
    box-sizing:border-box;
    background:#eef2f7;
  }
  /* Главен панел с бордър и равномерен падинг */
  .panel{
    width:100%; height:100%;
    background:#ffffff;
    border:2px solid var(--panel-border);
    border-radius:16px;
    box-shadow:0 10px 28px rgba(0,0,0,.07);
    display:flex; flex-direction:column; gap:var(--v-gap);
    padding:var(--panel-pad); box-sizing:border-box;
  }
  /* Заглавна част */
  .hdr{ text-align:center; }
  .ttl{ margin:0; font-weight:800; color:#34495e; font-size:16px; letter-spacing:.2px; }
  .sub{ margin:0; font-size:12px; color:#6b7b8c; }

  /* Секция с копчето (knob) */
  .knob-wrap{ display:flex; align-items:center; justify-content:center; }
  .knob{
    --size:260px;
    --deg:180;          /* визуален ъгъл на маркера (0–360) */
    width:var(--size); height:var(--size); border-radius:50%;
    background:
      radial-gradient(circle at 50% 50%, #fff 0 53%, transparent 54%),
      conic-gradient(#e67e22 calc(var(--p,0)*1%), #e6eaf0 0);
    position:relative; cursor:pointer; user-select:none; touch-action:none;
    box-shadow:inset 0 0 0 1px #e6eaf0;
  }
  .knob::before{
    content:""; position:absolute; inset:10px; border-radius:50%;
    background:repeating-conic-gradient(#e0e7f1 0 2deg, transparent 2deg 6deg);
    mask:radial-gradient(circle at 50% 50%, transparent 64%, #000 64.5%);
  }

  /* Игла/маркер (стрелка) */
  .needle{
    position:absolute; left:50%; top:50%;
    width:2px; height:42%; background:#334155; opacity:.9;
    transform-origin:50% 85%;
    transform:translate(-50%,-85%) rotate(calc(var(--deg)*1deg));
    border-radius:2px;
  }
  .needle::after{
    content:""; position:absolute; left:50%; top:-8px; transform:translateX(-50%);
    width:0; height:0; border-left:6px solid transparent; border-right:6px solid transparent; border-bottom:8px solid #334155;
  }

  .label{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; flex-direction:column; z-index:2; }
  .val{ font-size:40px; font-weight:800; line-height:1; color:#2c3e50; }
  .percent{ font-size:12px; opacity:.75; }

  /* Бързи пресети */
  .presets{ display:flex; justify-content:center; gap:8px; flex-wrap:wrap; }
  .chip{
    border:1px solid #d6deea; background:#f5f8fc; color:#334155;
    border-radius:999px; padding:6px 10px; font-weight:700; font-size:11px; cursor:pointer;
  }
  .chip:hover{ filter:brightness(.97); }
  .chip.active{ background:#e67e22; border-color:#e67e22; color:#fff; }

  .help{ text-align:center; font-size:12px; color:#6b7b8c; margin:0; }

  /* Долна лента с бутони */
  .actions{ display:flex; justify-content:center; gap:10px; }
  .btn{ border:0; border-radius:10px; padding:10px 14px; font-weight:800; cursor:pointer; font-size:12px; }
  .save{ background:#2ecc71; color:#fff; } .cancel{ background:#e74c3c; color:#fff; }
  .btn:hover{ filter:brightness(.95); }

  /* Резултат под бутоните (зелен бокс) */
  .result-box{
    margin-top:10px;
    text-align:center;
    background:#ecfdf5;
    color:#065f46;
    border:1px solid #a7f3d0;
    border-radius:12px;
    padding:10px 12px;
    font-weight:800;
    font-size:13px;
  }
  .error-box{
    margin-top:10px;
    text-align:center;
    background:#fff1f2;
    color:#991b1b;
    border:1px solid #fecaca;
    border-radius:12px;
    padding:10px 12px;
    font-weight:800;
    font-size:13px;
  }
</style>

<!-- АДАПТИВНО ОРАЗМЕРЯВАНЕ: осигурява точния вътрешен размер според --win-w/--win-h -->
<script>
  function targetInnerSize() {
    const cs = getComputedStyle(document.documentElement);
    const w = parseInt(cs.getPropertyValue('--win-w')) || 600;
    const h = parseInt(cs.getPropertyValue('--win-h')) || 700;
    return {w, h};
  }
  function fitToCssViewport() {
    try {
      const {w, h} = targetInnerSize();
      const dw = (window.outerWidth  - window.innerWidth)  || 0;
      const dh = (window.outerHeight - window.innerHeight) || 0;
      window.resizeTo(w + dw, h + dh);
    } catch(e) {}
  }
  window.addEventListener('load',  fitToCssViewport);
  window.addEventListener('resize', ()=> { fitToCssViewport(); });
</script>
</head>
<body>
<div class="shell">
  <div class="panel">
    <div class="hdr">
      <h1 class="ttl">РЪЧНО ЛИМИТИРАНЕ</h1>
      <p class="sub">Клиент № <b><?= (int)$customerId ?></b></p>
    </div>

    <div class="knob-wrap">
      <div id="knob" class="knob" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50" tabindex="0" style="--p:50;--deg:180;">
        <div class="needle"></div>
        <div class="label"><div class="val" id="val">50</div><div class="percent">% мощност</div></div>
      </div>
    </div>

    <div class="presets">
      <button class="chip" data-v="0">0%</button>
      <button class="chip" data-v="25">25%</button>
      <button class="chip active" data-v="50">50%</button>
      <button class="chip" data-v="75">75%</button>
      <button class="chip" data-v="100">100%</button>
    </div>

    <p class="help">Плъзни по кръга или използвай ↑ / ↓. Избери пресет за бърза стойност.</p>

    <form id="f" method="post" style="display:none">
      <input type="hidden" name="customer" value="<?= (int)$customerId ?>">
      <input type="hidden" id="limit" name="limit" value="50">
    </form>

    <div class="actions">
      <!-- ВАЖНО: type="button", за да не прави стандартен submit -->
      <button class="btn save" id="saveBtn" type="button">Запази</button>
      <button class="btn cancel" type="button" onclick="window.close()">Отказ</button>
    </div>

    <!-- Тук ще се покаже резултатът (зелен бокс) или грешка (червен) -->
    <div id="resultBox" class="result-box" style="display:none"></div>
    <div id="errorBox" class="error-box" style="display:none"></div>
  </div>
</div>

<script>
(function(){
  const knob   = document.getElementById('knob');
  const valEl  = document.getElementById('val');
  const limit  = document.getElementById('limit');
  const chips  = Array.from(document.querySelectorAll('.chip'));
  const saveBtn= document.getElementById('saveBtn');
  const form   = document.getElementById('f');
  const resultBox = document.getElementById('resultBox');
  const errorBox  = document.getElementById('errorBox');

  let value = 50, dragging = false, lastValue = 50;

  function setValue(v, fromPreset=false){
    const newV = Math.max(0, Math.min(100, Math.round(v)));
    value = newV;
    lastValue = newV;
    knob.style.setProperty('--p', newV);
    knob.style.setProperty('--deg', newV * 3.6); // 0..100 -> 0..360°
    knob.setAttribute('aria-valuenow', newV);
    valEl.textContent = newV;
    limit.value = newV;
    chips.forEach(c=>{
      const cv = parseInt(c.dataset.v,10);
      c.classList.toggle('active', fromPreset && cv===newV);
    });
  }

  function angleFromEvent(e){
    const r = knob.getBoundingClientRect(), cx=r.left+r.width/2, cy=r.top+r.height/2;
    const p = e.touches? e.touches[0] : e;
    const x=p.clientX-cx, y=p.clientY-cy;
    let deg = (Math.atan2(y,x)*180/Math.PI + 90);
    if (deg<0) deg += 360;
    return deg;
  }
  function percentFromAngle(deg){ return deg / 360 * 100; }

  function safeUpdateByAngle(deg){
    const candidate = percentFromAngle(deg);
    const near0   = lastValue <= 10;
    const near100 = lastValue >= 90;
    if ((near0 && candidate >= 90) || (near100 && candidate <= 10)) return; // без wrap
    setValue(candidate);
  }
  function updateFromEvent(e){ safeUpdateByAngle(angleFromEvent(e)); }

  // Плъзгане/клик/тъч
  knob.addEventListener('pointerdown', e=>{ e.preventDefault(); dragging=true; knob.setPointerCapture(e.pointerId); updateFromEvent(e); });
  knob.addEventListener('pointermove', e=>{ if(dragging) updateFromEvent(e); });
  knob.addEventListener('pointerup',   e=>{ dragging=false; try{knob.releasePointerCapture(e.pointerId);}catch(_){} });
  knob.addEventListener('pointercancel', ()=> dragging=false);

  knob.addEventListener('mousedown', e=>{ e.preventDefault(); dragging=true; updateFromEvent(e); });
  window.addEventListener('mousemove', e=>{ if(dragging) updateFromEvent(e); });
  window.addEventListener('mouseup', ()=> dragging=false);

  knob.addEventListener('touchstart', e=>{ e.preventDefault(); dragging=true; updateFromEvent(e); }, {passive:false});
  knob.addEventListener('touchmove',  e=>{ e.preventDefault(); if(dragging) updateFromEvent(e); }, {passive:false});
  knob.addEventListener('touchend',   ()=> dragging=false);

  // Клавиатура
  knob.addEventListener('keydown', e=>{
    if(e.key==='ArrowUp')   { setValue(value+1); e.preventDefault(); }
    if(e.key==='ArrowDown') { setValue(value-1); e.preventDefault(); }
    if(e.key==='PageUp')    { setValue(value+10); e.preventDefault(); }
    if(e.key==='PageDown')  { setValue(value-10); e.preventDefault(); }
    if(e.key==='Home')      { setValue(0); e.preventDefault(); }
    if(e.key==='End')       { setValue(100); e.preventDefault(); }
  });

  // Пресети
  chips.forEach(c=>{
    c.addEventListener('click', ()=>{
      const v = parseInt(c.dataset.v,10) || 0;
      setValue(v, true);
    });
  });

  // AJAX Запис (без втори екран)
  saveBtn.addEventListener('click', async ()=>{
    resultBox.style.display = 'none';
    errorBox.style.display  = 'none';
    try{
      const fd = new FormData(form);
      // Пращаме като URL-encoded за по-съвместим бекенд
      const payload = new URLSearchParams();
      for (const [k,v] of fd.entries()) payload.append(k,v);

      const res = await fetch(location.href, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: payload.toString()
      });

      if (!res.ok) {
        // опитваме да извадим текст на грешка
        const t = await res.text();
        errorBox.textContent = 'Грешка при запис: ' + (t ? t.replace(/<[^>]+>/g, '').trim() : res.status+' '+res.statusText);
        errorBox.style.display = 'block';
        return;
      }

      // Успех: визуализираме зеления бокс със стойността
      resultBox.textContent = `Ръчният лимит е зададен: ${limit.value}%`;
      resultBox.style.display = 'block';

      // Обновяваме родителя (dash-а), ако има отворен
      try { if (window.opener) window.opener.location.reload(); } catch(_){}

    }catch(err){
      errorBox.textContent = 'Грешка при заявка.';
      errorBox.style.display = 'block';
    }
  });

  setValue(50, true);
})();
</script>
</body>
</html>
