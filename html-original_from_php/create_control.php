<?php
// create_control.php
require_once "db_connection.php";

// Параметри: ?customer=, по избор ?plant= и/или ?date=YYYY-MM-DD
$customerId   = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;
$plantId      = isset($_GET['plant']) && $_GET['plant'] !== '' ? (int)$_GET['plant'] : null;
$initialDate  = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (!$customerId) {
    http_response_code(400);
    die("Missing customer id (?customer=).");
}

/* ---------- AJAX: зареждане за произволна дата ---------- */
if (isset($_GET['action']) && $_GET['action'] === 'load' && isset($_GET['date'])) {
    $date = $_GET['date'];

    $st = $conn->prepare("SELECT ScheduleID, Enabled FROM ScheduledLimits WHERE CustomerID=? AND (PlantID <=> ?)");
    $st->execute([$customerId, $plantId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $enabled = 0;
    $hours   = array_fill(0,24,100); // default 100%

    if ($row) {
        $scheduleId = (int)$row['ScheduleID'];
        $enabled    = (int)$row['Enabled'];

        $hstmt = $conn->prepare("SELECT Hour, Percent FROM ScheduledLimitHours WHERE ScheduleID=? AND Date=?");
        $hstmt->execute([$scheduleId, $date]);
        while ($r = $hstmt->fetch(PDO::FETCH_ASSOC)) {
            $h = (int)$r['Hour'];
            $p = (int)$r['Percent'];
            if ($h >= 0 && $h <= 23) $hours[$h] = $p;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['enabled'=>$enabled,'hours'=>$hours]);
    exit;
}

/* ---------- POST: запис за избрана дата ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ВАЖНО: четем реалната стойност от hidden полето (не само isset)
    $enabled = (isset($_POST['enabled']) && $_POST['enabled'] === '1') ? 1 : 0;
    $date    = $_POST['date'] ?? null;

    if (!$date) {
        http_response_code(422);
        die("Липсва дата.");
    }

    // Намираме/създаваме ScheduleID
    $st = $conn->prepare("SELECT ScheduleID FROM ScheduledLimits WHERE CustomerID=? AND (PlantID <=> ?)");
    $st->execute([$customerId, $plantId]);
    $scheduleId = $st->fetchColumn();

    if (!$scheduleId) {
        $st = $conn->prepare("INSERT INTO ScheduledLimits (CustomerID, PlantID, Enabled) VALUES (?,?,?)");
        $st->execute([$customerId, $plantId, $enabled]);
        $scheduleId = (int)$conn->lastInsertId();
    } else {
        $st = $conn->prepare("UPDATE ScheduledLimits SET Enabled=? WHERE ScheduleID=?");
        $st->execute([$enabled, $scheduleId]);
    }

    // 24 часа
    $hours = [];
    for ($h=0; $h<24; $h++) {
        $key = "h_$h";
        $pct = isset($_POST[$key]) ? (int)$_POST[$key] : 100;
        if ($pct < 0)   $pct = 0;
        if ($pct > 100) $pct = 100;
        $hours[$h] = $pct;
    }

    // Запис
    $conn->beginTransaction();
    try {
        $del = $conn->prepare("DELETE FROM ScheduledLimitHours WHERE ScheduleID=? AND Date=?");
        $del->execute([$scheduleId, $date]);

        $ins = $conn->prepare("INSERT INTO ScheduledLimitHours (ScheduleID, Date, Hour, Percent) VALUES (?,?,?,?)");
        foreach ($hours as $h => $p) {
            $ins->execute([$scheduleId, $date, $h, $p]);
        }

        // Лог (таблицата няма PlantID, а CommandType е само 'LIMIT')
        $cc = $conn->prepare("INSERT INTO ControlCommands (CustomerID, CommandType, Value) VALUES (?, 'LIMIT', 0)");
        $cc->execute([$customerId]);

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        http_response_code(500);
        die("Грешка при запис: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    header("Location: create_control.php?customer={$customerId}".($plantId!==null?"&plant=".$plantId:"")."&date=".$date);
    exit;
}

/* ---------- ИНИЦИАЛНИ СТОЙНОСТИ ПРИ ОТВАРЯНЕ ---------- */
$initialEnabled = 0;
$initialHours   = array_fill(0,24,100);

$st0 = $conn->prepare("SELECT ScheduleID, Enabled FROM ScheduledLimits WHERE CustomerID=? AND (PlantID <=> ?)");
$st0->execute([$customerId, $plantId]);
$prof = $st0->fetch(PDO::FETCH_ASSOC);

if ($prof) {
    $initialEnabled = (int)$prof['Enabled'];
    $scheduleId     = (int)$prof['ScheduleID'];

    $h0 = $conn->prepare("SELECT Hour, Percent FROM ScheduledLimitHours WHERE ScheduleID=? AND Date=?");
    $h0->execute([$scheduleId, $initialDate]);
    while ($r = $h0->fetch(PDO::FETCH_ASSOC)) {
        $h = (int)$r['Hour'];
        $p = (int)$r['Percent'];
        if ($h >= 0 && $h <= 23) $initialHours[$h] = $p;
    }
}

// За „СЕГА" (индикатор текущ час)
$nowHour = (int)date('G');
$currentNowPercent = $initialHours[$nowHour];
?>
<!doctype html>
<html lang="bg">
<head>
<meta charset="utf-8">
<title>Планиран контрол</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{
    --bg:#ffffff;
    --fg:#111827;
    --muted:#6b7280;
    --brand:#16a34a;  /* зелено */
    --brand-500:#22c55e;
    --brand-600:#16a34a;
    --brand-100:#ecfdf5;
    --brand-200:#d1fae5;
    --border:#e5e7eb;
    --card:#ffffff;

    --row-bg:#fff7ed;
    --row-border:#f5d0a9;

    --green-bg:#f0fdf4;
    --green-border:#86efac;

    --danger:#ef4444;
    --danger-600:#dc2626;
  }
  *{box-sizing:border-box}
  html,body{height:100vh;margin:0;padding:8px;overflow:hidden}
  body{background:var(--bg);color:var(--fg);font-family:system-ui,Arial,sans-serif}

  .container{display:grid;grid-template-rows:auto 1fr;height:calc(100vh - 16px);
    border:1px solid var(--border);border-radius:8px;overflow:hidden;background:var(--card)}
  .header{display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;background:var(--brand);color:#fff;border-bottom:1px solid var(--border)}
  h2{margin:0;font-weight:600}

  .header .switch{position:relative;display:flex;align-items:center;gap:10px;padding-left:58px}
  /* ЧЕКБОКСЪТ Е БЕЗ name (само id="enabled") */
  .header .switch input[type="checkbox"]{position:absolute;left:0;top:50%;transform:translateY(-50%);width:44px;height:26px;margin:0;opacity:0;cursor:pointer;}
  .header .switch::before{content:"";position:absolute;left:0;top:50%;transform:translateY(-50%);width:44px;height:26px;border-radius:999px;background:#e5e7eb;border:1px solid #d1d5db;transition:.2s;}
  .header .switch::after{content:"";position:absolute;left:2px;top:50%;transform:translateY(-50%);width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.12);transition:.2s;}
  .header .switch:has(input:checked)::before{background:var(--brand-500);border-color:var(--brand-500)}
  .header .switch:has(input:checked)::after{transform:translate(18px,-50%)}

  .main-layout{display:grid;grid-template-columns:420px 1fr;height:calc(100vh - 76px);overflow:hidden}
  @media (max-width:1000px){.main-layout{grid-template-columns:1fr;grid-template-rows:auto 1fr}}

  .calendar-section{border-right:1px solid var(--border);background:var(--card);display:flex;flex-direction:column;overflow:hidden}
  .cal-wrapper{padding:16px;flex:1;overflow:hidden}
  .cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .cal-title{font-size:18px;font-weight:600}
  .cal-nav{display:flex;gap:8px}
  .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
  .dow{text-align:center;font-size:12px;color:var(--muted);padding:6px 0}
  .day{border:1px solid var(--border);border-radius:10px;min-height:64px;padding:6px;
    position:relative;background:#fff;cursor:pointer;}
  .day.other{opacity:.45;background:#fafafa}
  .day.today{outline:2px solid var(--brand-500);outline-offset:1px}
  .day.selected{background:var(--brand-100);border-color:var(--brand-500)}
  .day:hover{background:var(--brand-100);border-color:var(--brand-200)}
  .legend{font-size:12px;color:var(--muted);margin-top:8px}
  .day .badge-active{position:absolute; right:6px; top:6px; font-size:10px; font-weight:700; background:#065f46; color:#fff; border:1px solid #064e3b; border-radius:999px; padding:2px 6px; line-height:1;}

  .controls-section{background:var(--card);display:flex;flex-direction:column;overflow:hidden}
  .controls-wrapper{padding:16px;flex:1;overflow:hidden;display:flex;flex-direction:column}

  .toolbar button{background:var(--danger);color:#fff;border:1px solid var(--danger-600);font-weight:600}
  .toolbar button:hover{background:var(--danger-600)}
  .toolbar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}

  button,select{padding:6px 10px;border-radius:8px;border:1px solid var(--border);font-size:12px}
  .controls-wrapper button[type="submit"]{background:var(--brand-500);color:#fff;border:1px solid var(--brand-500);font-weight:600;font-size:14px;padding:8px 14px;}
  .controls-wrapper button[type="submit"]:hover{background:var(--brand-600)}

  select{appearance:none;transition:background-color .2s,border-color .2s}
  select.nonzero{background-color:var(--green-bg)!important;border-color:var(--green-border)!important}

  .grid-hours{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;height:calc(100vh - 200px);overflow:hidden}
  .row{display:flex;align-items:center;gap:10px;justify-content:space-between;border:1px solid var(--row-border);border-radius:6px;padding:8px;background:var(--row-bg);font-size:12px}
  .hour-label{font-size:14px;font-weight:700;min-width:58px}
  .value-chip{display:inline-flex;align-items:center;justify-content:center;min-width:72px;font-size:13px;font-weight:700;padding:4px 10px;border-radius:10px;background:#065f46;color:#fff;border:1px solid #064e3b;box-shadow:0 1px 2px rgba(0,0,0,.2)}
  .nowchip{font-size:12px;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;padding:4px 8px;border-radius:999px}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h2>Планиран контрол (по дати и часове)</h2>
    <!-- ЧЕКБОКСЪТ Е БЕЗ name, синхронизираме към hidden -->
    <label class="switch"><input type="checkbox" id="enabled" <?= $initialEnabled ? 'checked' : '' ?>> Активен график</label>
  </div>
  <div class="main-layout">
    <div class="calendar-section">
      <div class="cal-wrapper">
        <div class="cal-header">
          <div class="cal-title" id="calTitle">Месец Година</div>
          <div class="cal-nav">
            <button type="button" id="prevMonth">‹</button>
            <button type="button" id="todayBtn">Днес</button>
            <button type="button" id="nextMonth">›</button>
          </div>
        </div>
        <div class="cal-grid" id="calGrid"></div>
        <div class="legend">Клик върху дата за избор и зареждане на почасовите стойности</div>
      </div>
    </div>

    <div class="controls-section">
      <form method="post" class="controls-wrapper">
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($initialDate,ENT_QUOTES,'UTF-8') ?>" required style="display:none">
        <!-- СКРИТОТО ПОЛЕ НОСИ СТОЙНОСТТА ЗА POST -->
        <input type="hidden" name="enabled" id="enabledHidden" value="<?= $initialEnabled ? '1' : '0' ?>">

        <div class="toolbar">
          <button type="button" id="fill0">Всички 0%</button>
          <button type="button" id="fill50">Всички 50%</button>
          <button type="button" id="fill100">Всички 100%</button>
          <button type="button" id="workday">Работен ден (8–18ч = 60%, друго 100%)</button>
          <span class="nowchip" id="nowChip">Сега: <?= sprintf('%02d:00 → %d%%', $nowHour, (int)$currentNowPercent) ?></span>
        </div>

        <div id="hoursGrid" class="grid-hours"></div>

        <div style="margin-top:14px"><button type="submit">Запис</button></div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const initialDate   = "<?= htmlspecialchars($initialDate,ENT_QUOTES,'UTF-8') ?>";
  const initialHours  = <?= json_encode(array_map('intval',$initialHours)) ?>;
  const initialEnable = <?= (int)$initialEnabled ?>;

  const pad2 = n => String(n).padStart(2,'0');
  const toYMD = d => `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;

  const enabledInput = document.getElementById('enabled');
  const enabledHidden = document.getElementById('enabledHidden');
  const dateInput    = document.getElementById('date');
  const hoursGrid    = document.getElementById('hoursGrid');
  const calTitle     = document.getElementById('calTitle');
  const calGrid      = document.getElementById('calGrid');
  const prevMonthBtn = document.getElementById('prevMonth');
  const nextMonthBtn = document.getElementById('nextMonth');
  const todayBtn     = document.getElementById('todayBtn');

  // синхронизация чекбокс -> hidden (за POST)
  enabledInput.addEventListener('change', ()=>{enabledHidden.value = enabledInput.checked ? '1':'0';});

  function updateSelectColor(sel){
    const val = parseInt(sel.value,10);
    sel.classList.toggle("nonzero", val > 0);
    let chip = sel.previousElementSibling;
    if (!chip || !chip.classList || !chip.classList.contains("value-chip")) {
      const parent = sel.parentElement;
      if (parent) chip = parent.querySelector('.value-chip');
    }
    if (chip) chip.textContent = `${isNaN(val)?'':val}%`;
  }

  function makeSelect(name){
    const sel = document.createElement('select'); sel.name = name;
    for(let p=0; p<=100; p++){
      const opt=document.createElement('option'); opt.value=p; opt.textContent=p+"%"; sel.appendChild(opt);
    }
    sel.value="100";
    sel.addEventListener('change', ()=>updateSelectColor(sel));
    return sel;
  }

  function renderHours(withValues){
    hoursGrid.innerHTML='';
    for(let h=0; h<24; h++){
      const row=document.createElement('div'); row.className='row';
      const label=document.createElement('div'); label.className='hour-label'; label.textContent=`${pad2(h)}:00`;
      const chip=document.createElement('div'); chip.className='value-chip';
      const sel=makeSelect('h_'+h);
      if(withValues && typeof withValues[h]!=='undefined'){ sel.value=String(withValues[h]); }
      row.appendChild(label); row.appendChild(chip); row.appendChild(sel);
      hoursGrid.appendChild(row);
      updateSelectColor(sel);
    }
  }

  function fillAll(v){
    for(let h=0;h<24;h++){
      const sel=document.querySelector(`select[name="h_${h}"]`);
      if(sel){ sel.value=String(v); updateSelectColor(sel); }
    }
  }
  document.getElementById('fill0').onclick = ()=>fillAll(0);
  document.getElementById('fill50').onclick= ()=>fillAll(50);
  document.getElementById('fill100').onclick=()=>fillAll(100);
  document.getElementById('workday').onclick=()=>{
    for(let h=0;h<24;h++){
      const sel=document.querySelector(`select[name="h_${h}"]`);
      if(!sel) continue;
      sel.value=(h>=8&&h<18)?"60":"100";
      updateSelectColor(sel);
    }
  };

  let viewYear,viewMonth;

  function setMonth(year,month){
    viewYear=year; viewMonth=month;
    const viewDate=new Date(year,month,1);
    const monthName=viewDate.toLocaleString('bg-BG',{month:'long'});
    calTitle.textContent=monthName.charAt(0).toUpperCase()+monthName.slice(1)+' '+year;

    calGrid.innerHTML='';
    ['Пн','Вт','Ср','Чт','Пт','Сб','Нд'].forEach(d=>{
      const head=document.createElement('div'); head.className='dow'; head.textContent=d; calGrid.appendChild(head);
    });

    const first=new Date(year,month,1);
    const startIdx=(first.getDay()+6)%7;
    const daysInMonth=new Date(year,month+1,0).getDate();
    const prevDays=new Date(year,month,0).getDate();

    for(let i=0;i<startIdx;i++){
      calGrid.appendChild(dayCell(new Date(year,month-1,prevDays-startIdx+1+i),true));
    }
    for(let d=1; d<=daysInMonth; d++){
      calGrid.appendChild(dayCell(new Date(year,month,d),false));
    }
    while(calGrid.children.length<49){
      const nextIndex=calGrid.children.length-6;
      calGrid.appendChild(dayCell(new Date(year,month+1,nextIndex),true));
    }
    markSelected(new Date(dateInput.value));
  }

  function dayCell(dateObj,isOther){
    const cell=document.createElement('div');
    cell.className='day'+(isOther?' other':'');
    cell.dataset.ymd = toYMD(dateObj);

    const num=document.createElement('div'); num.textContent=dateObj.getDate();
    const btn=document.createElement('button'); btn.type='button'; btn.onclick=()=>selectDate(dateObj);

    cell.appendChild(num); cell.appendChild(btn);

    const today=new Date();
    if(dateObj.toDateString()===today.toDateString()) cell.classList.add('today');
    return cell;
  }

  function setDayActiveBadge(ymd, isActive){
    const cell = [...calGrid.querySelectorAll('.day')].find(c => c.dataset.ymd === ymd);
    if (!cell) return;
    let badge = cell.querySelector('.badge-active');
    if (isActive){
      if (!badge){
        badge = document.createElement('div');
        badge.className='badge-active';
        badge.textContent='Активно';
        cell.appendChild(badge);
      }
    } else {
      if (badge) badge.remove();
    }
  }

  function markSelected(dObj){
    [...calGrid.querySelectorAll('.day')].forEach(el=>el.classList.remove('selected'));
    const ymd = toYMD(dObj);
    const cell = [...calGrid.querySelectorAll('.day')].find(c => c.dataset.ymd === ymd && !c.classList.contains('other'));
    if (cell) cell.classList.add('selected');
  }

  async function selectDate(dObj){
    const ymd=toYMD(dObj);
    dateInput.value=ymd;
    markSelected(dObj);
    await loadForDate(ymd);
  }

  prevMonthBtn.onclick=()=>{const m=new Date(viewYear,viewMonth-1,1);setMonth(m.getFullYear(),m.getMonth());};
  nextMonthBtn.onclick=()=>{const m=new Date(viewYear,viewMonth+1,1);setMonth(m.getFullYear(),m.getMonth());};
  todayBtn.onclick=()=>{const t=new Date();setMonth(t.getFullYear(),t.getMonth());const ymd=toYMD(t);dateInput.value=ymd;markSelected(t);loadForDate(ymd);};

  async function loadForDate(d){
    const url=new URL(window.location.href);
    url.searchParams.set('action','load');
    url.searchParams.set('date',d);
    try{
      const res=await fetch(url,{headers:{'Accept':'application/json'}});
      if(!res.ok) return;
      const data=await res.json();

      // визуално състояние + POST стойност
      enabledInput.checked=!!data.enabled;
      enabledHidden.value=data.enabled?'1':'0';

      if(Array.isArray(data.hours)&&data.hours.length===24){
        for(let h=0;h<24;h++){
          const sel=document.querySelector(`select[name="h_${h}"]`);
          if(sel){ sel.value=String(data.hours[h]); updateSelectColor(sel); }
        }
        const hasActive = data.hours.some(v => parseInt(v,10) !== 0);
        setDayActiveBadge(d, hasActive);

        const now=new Date();
        const isToday=d===toYMD(now);
        document.getElementById('nowChip').textContent = isToday
          ? `Сега: ${pad2(now.getHours())}:00 → ${data.hours[now.getHours()]}%`
          : `Избрана дата: ${d}`;
      }
    }catch(e){}
  }

  // Инициализация
  renderHours(initialHours);
  enabledInput.checked=!!initialEnable;
  enabledHidden.value=initialEnable?'1':'0';

  const init=new Date(initialDate);
  setMonth(init.getFullYear(),init.getMonth());
  markSelected(init);
  setDayActiveBadge(toYMD(init), initialHours.some(v => parseInt(v,10) !== 0));
})();
</script>
</body>
</html>
