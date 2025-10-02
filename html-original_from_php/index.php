<?php
// index.php – Дашборд + 400V/20kV + статуси + попъпи за Лимит и График (менюто се крие/показва)
include 'db_connection.php';

/* ========= Помощни функции ========= */
function tableExists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:t");
    $st->execute([':t'=>$table]); return ((int)$st->fetch()['c'])>0;
}
function columnExists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:t AND column_name=:c");
    $st->execute([':t'=>$table, ':c'=>$column]); return ((int)$st->fetch()['c'])>0;
}

/* ========= Динамичен SELECT ========= */
$hasCustomers    = tableExists($conn, 'Customers');
$hasPVPlants     = tableExists($conn, 'PVPlants');
$hasInverters    = tableExists($conn, 'Inverters');
$hasInvData      = tableExists($conn, 'InverterData');
$hasGlobalEvents = tableExists($conn, 'GlobalEvents');

if (!$hasCustomers) { http_response_code(500); die('Липсва таблица Customers в активната база.'); }

$parts = [];
$parts[] = "c.CustomerName";
$parts[] = "c.Representative";
$parts[] = "c.CustomerID";
$parts[] = ($hasInvData && columnExists($conn,'InverterData','TotalPower')) ? "COALESCE(SUM(id.TotalPower),0) AS CurrentProduction" : "0 AS CurrentProduction";
$parts[] = columnExists($conn,'Customers','Alarms') ? "c.Alarms" : "0 AS Alarms";
$parts[] = ($hasGlobalEvents && columnExists($conn,'GlobalEvents','EventMessage')) ? "ge.EventMessage" : "NULL AS EventMessage";

$addInvCol = function(string $col, string $agg, string $alias) use ($conn, $hasInvData) {
    return ($hasInvData && columnExists($conn,'InverterData',$col)) ? "$agg(id.$col) AS $alias" : "NULL AS $alias";
};
$parts[] = $addInvCol('Voltage400V','AVG','V400');
$parts[] = $addInvCol('Current400V','AVG','I400');
$parts[] = $addInvCol('Power400V',  'SUM','P400');
$parts[] = $addInvCol('CosPhi400V', 'AVG','Cos400');
$parts[] = $addInvCol('Voltage20kV','AVG','V20kV');
$parts[] = $addInvCol('Current20kV','AVG','I20kV');
$parts[] = $addInvCol('Power20kV',  'SUM','P20kV');
$parts[] = $addInvCol('CosPhi20kV', 'AVG','Cos20kV');

$parts[] = ($hasPVPlants && columnExists($conn,'PVPlants','BreakerStatus')) ? "COALESCE(MAX(p.BreakerStatus),0) AS BreakerStatus" : "NULL AS BreakerStatus";
$parts[] = ($hasPVPlants && columnExists($conn,'PVPlants','RelayStatus'))   ? "COALESCE(MAX(p.RelayStatus),0)   AS RelayStatus"   : "NULL AS RelayStatus";
$parts[] = columnExists($conn,'Customers','TelemechanicsEnabled') ? "c.TelemechanicsEnabled" : "NULL AS TelemechanicsEnabled";

$select = implode(",\n                    ", $parts);

$from = "FROM Customers c";
if ($hasPVPlants)  $from .= " LEFT JOIN PVPlants p ON c.CustomerID = p.CustomerID";
if ($hasInverters) $from .= " LEFT JOIN Inverters i ON ".($hasPVPlants ? "p.PlantID" : "0")." = i.PlantID";
if ($hasInvData)   $from .= " LEFT JOIN InverterData id ON i.InverterID = id.InverterNr";
if ($hasGlobalEvents) $from .= " LEFT JOIN GlobalEvents ge ON c.CustomerID = ge.CustomerID";

$groupBy = "GROUP BY c.CustomerName, c.Representative, c.CustomerID";
if ($hasGlobalEvents && columnExists($conn,'GlobalEvents','EventMessage')) $groupBy .= ", ge.EventMessage";

try {
    $sql = "SELECT $select $from $groupBy";
    $stmt = $conn->prepare($sql); $stmt->execute();
} catch (PDOException $e) { $stmt=null; $fatalSqlError=$e->getMessage(); }

$fmt = function($v, $dec=2){ if ($v===null || $v==='') return '—'; if (is_numeric($v)){ $s=number_format((float)$v,$dec,'.',''); return rtrim(rtrim($s,'0'),'.'); } return htmlspecialchars((string)$v); };
$toBool = function($v){ if ($v===null) return null; $s=strtolower(trim((string)$v));
    if (in_array($s, ['1','on','true','yes','вкл','включено','closed','enabled'])) return true;
    if (in_array($s, ['0','off','false','no','изкл','изключено','open','disabled'])) return false;
    if (is_numeric($v)) return ((float)$v)!=0.0; return null; };
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SCADA СИСТЕМА ЗА КОНТРОЛ НА СОЛАРНИ ПАРКОВЕ | ТЕХНОСЪЮЗ ООД</title>

<script>
if (window.innerWidth <= 768) { window.location.href = "index-mobile.php"; }
setTimeout(function(){ window.location.reload(1); }, 10000);

function autoSubmitToggle(el){ if(el && el.form){ el.form.submit(); } }

function openPopup(url, name, w, h){
  // „Киоск" прозорец – без тулбари/менюта, без ресайз, без скрол
  const feats = `width=${w},height=${h},left=${(screen.availWidth-w)/2|0},top=${(screen.availHeight-h)/2|0},`+
                `menubar=0,toolbar=0,location=0,status=0,scrollbars=0,resizable=0`;
  const win = window.open(url, name, feats);
  if (win) { try { win.focus(); } catch(_){} }
  return false;
}

// Скриване/показване на страничното меню + запомняне
function applySavedSidebar(){
  try{
    if (localStorage.getItem('scada_nav_collapsed') === '1') {
      document.body.classList.add('nav-collapsed');
      const btn = document.getElementById('sidebarBtn');
      if (btn) btn.setAttribute('aria-pressed','true');
    }
  }catch(_){}
}
function toggleSidebar(){
  document.body.classList.toggle('nav-collapsed');
  const collapsed = document.body.classList.contains('nav-collapsed');
  try{ localStorage.setItem('scada_nav_collapsed', collapsed ? '1' : '0'); }catch(_){}
  const btn = document.getElementById('sidebarBtn');
  if (btn) btn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
}
document.addEventListener('DOMContentLoaded', applySavedSidebar);
</script>

<style>
/* --- Стари цветове и дизайн --- */
body{font-family:'Verdana',sans-serif;background:#f4f4f9;color:#333;margin:0;padding:0;display:flex;flex-direction:column;}

/* Header + бутон за менюто */
header{
  background:#34495e;color:#fff;padding:1px;text-align:center;
  box-shadow:0 2px 4px rgba(0,0,0,.1);font-size:10px;width:100%;position:relative;
}
.sidebar-toggle{
  position:absolute; left:10px; top:6px; padding:6px 10px; border:0; border-radius:6px;
  background:#2c3e50; color:#fff; font-weight:700; cursor:pointer; font-size:12px;
}
.sidebar-toggle:hover{ filter:brightness(.95); }

/* Sidebar */
nav{
  background:#2c3e50; padding:15px; width:250px; box-sizing:border-box;
  position:fixed; top:0; left:0; height:100%; z-index:1000;
  transition: width .25s ease, padding .25s ease;
  overflow:hidden;
}
nav ul{list-style:none;padding:0;margin:0;}
nav li{margin-bottom:15px;}
nav a{color:#fff;text-decoration:none;font-weight:bold;display:block;padding:10px 15px;border-radius:5px;background:#34495e;transition:.3s;}
nav a:hover{background:#1abc9c;}

/* Основно съдържание */
.container{
  margin-left:250px;padding:20px;flex:1;width:calc(100% - 250px);
  box-sizing:border-box;display:flex;flex-wrap:wrap;gap:20px;justify-content:flex-start;
  transition: margin-left .25s ease, width .25s ease;
}
.client-card{background:#ecf0f1;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1);
  width:340px;height:auto;margin-bottom:20px;position:relative;border:1px solid #7f8c8d;transition:transform .3s,box-shadow .3s;box-sizing:border-box;}
.client-card:hover{transform:translateY(-5px);box-shadow:0 8px 15px rgba(0,0,0,.1);}
.name-box.no-alarms{background:#2c7c31;color:#fff;padding:10px;text-align:center;font-size:18px;font-weight:bold;border-radius:5px;margin:-20px -20px 10px -20px;}
.name-box.with-alarms{background:#e74c3c;color:#fff;padding:10px;text-align:center;font-size:18px;font-weight:bold;border-radius:5px;margin:-20px -20px 10px -20px;}
.client-card p{margin:5px 0;font-size:14px;color:#555;}
@keyframes blink{0%{background:#c0392b;color:#fff;}50%{background:#fff;color:#c0392b;}100%{background:#c0392b;color:#fff;}}
.alarm-box{background:#c0392b;color:#fff;padding:10px;border-radius:5px;margin-top:10px;font-weight:bold;text-align:center;animation:blink 2s infinite;}
.no-alarm-box{background:#34495e;color:#fff;padding:10px;border-radius:5px;margin-top:10px;font-weight:bold;text-align:center;}
.data-box{background:#b2ebf2;color:#333;padding:10px;border-radius:5px;margin-top:10px;text-align:center;font-weight:bold;}
.client-card a{ text-decoration:none;color:#1abc9c;font-weight:bold;font-size:14px;display:block;margin-top:10px;}
.client-card a:hover{text-decoration:underline;}

/* Групи данни */
.group-box{background:#fff;border:1px solid #95a5a6;border-radius:8px;padding:12px;margin-top:10px;}
.group-title{font-weight:bold;text-align:center;background:#34495e;color:#fff;padding:6px 8px;border-radius:6px;margin:-6px -6px 10px -6px;}
.kv-grid{display:grid;grid-template-columns:auto 1fr;row-gap:6px;column-gap:10px;font-size:14px;}
.kv-grid .label{color:#555;font-weight:600;}
.kv-grid .value{color:#222;}

/* Бутони – 2 колони в рамките на визитката */
.btn-row{ margin-top:8px; display:grid; grid-template-columns: 1fr 1fr; gap:6px; }
.btn{
  padding:7px 8px; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:11px; line-height:1.15; min-width:0; white-space:nowrap;
}
.btn-limit{background:#e67e22;color:#fff;}
.btn-schedule{background:#2980b9;color:#fff;}
.btn:hover{filter:brightness(.95);}

/* Статуси */
.status-row{margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.status-badge{padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;color:#fff;}
.green{background:#2ecc71;}
.red{background:#e74c3c;}
.gray{background:#7f8c8d;}

/* HTML5 суич (команда) */
.toggle-wrap{display:flex;align-items:center;gap:8px;margin-left:auto;}
.toggle-label{font-weight:700;font-size:12px;}
.switch{position:relative;width:52px;height:28px;display:inline-block;flex:0 0 auto;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;cursor:pointer;inset:0;background:#e74c3c;transition:.2s;border-radius:999px;}
.slider:before{position:absolute;content:"";height:22px;width:22px;left:3px;top:3px;background:#fff;transition:.2s;border-radius:50%;}
input:checked + .slider{background:#2ecc71;}
input:checked + .slider:before{transform:translateX(24px);}

/* Когато менюто е скрито */
body.nav-collapsed nav{ width:0; padding:0; }
body.nav-collapsed .container{ margin-left:0; width:100%; }

@media (max-width:768px){
  .container{margin-left:0;width:100%;}
  nav{display:none;}
}
</style>
</head>
<body>
<header>
  <button id="sidebarBtn" class="sidebar-toggle" onclick="toggleSidebar()" aria-pressed="false" aria-label="Скрий/покажи меню">☰ Меню</button>
  <h1>ИНФОРМАЦИОНЕН ПАНЕЛ | SCADA СИСТЕМА ЗА КОНТРОЛ НА СОЛАРНИ ПАРКОВЕ | ТЕХНОСЪЮЗ ООД</h1>
</header>

<nav>
  <ul>
    <li><a href="index.php">Дашборд</a></li>
    <li><a href="customers_info.php">Клиенти</a></li>
    <li><a href="events_info.php">Събития</a></li>
    <li><a href="inverters_info.php">Инвертори</a></li>
    <li><a href="users_info.php">Потребители</a></li>
    <li><a href="settings.php">Настройки</a></li>
  </ul>
</nav>

<div class="container">
<?php
if (isset($fatalSqlError)) {
    echo '<p>Грешка при извличане на данни: ' . htmlspecialchars($fatalSqlError) . '</p>';
} else {
    try {
        if ($stmt && $stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $alarms = isset($row['Alarms']) ? (int)$row['Alarms'] : 0;
                $nameBoxClass = $alarms > 0 ? 'with-alarms' : 'no-alarms';

                $breaker = $toBool($row['BreakerStatus'] ?? null);
                $relay   = $toBool($row['RelayStatus']   ?? null);
                $tm      = $toBool($row['TelemechanicsEnabled'] ?? null);

                echo '<div class="client-card">';
                echo '<div class="name-box ' . $nameBoxClass . '">' . htmlspecialchars($row['CustomerName']) . '</div>';

                // 400V
                echo '<div class="group-box">';
                echo '  <div class="group-title">КОНТРОЛ 400V</div>';
                echo '  <div class="kv-grid">';
                echo '    <div class="label">U (напрежение):</div><div class="value">' . $fmt($row['V400'], 2) . ' V</div>';
                echo '    <div class="label">I (ток):</div><div class="value">'       . $fmt($row['I400'], 2) . ' A</div>';
                echo '    <div class="label">P (мощност):</div><div class="value">'   . $fmt($row['P400'], 2) . ' kW</div>';
                echo '    <div class="label">cos &phi;:</div><div class="value">'     . $fmt($row['Cos400'], 3) . '</div>';
                echo '  </div>';
                echo '</div>';

                // 20 kV
                echo '<div class="group-box">';
                echo '  <div class="group-title">КОНТРОЛ 20 kV</div>';
                echo '  <div class="kv-grid">';
                echo '    <div class="label">U (напрежение):</div><div class="value">' . $fmt($row['V20kV'], 0) . ' V</div>';
                echo '    <div class="label">I (ток):</div><div class="value">'        . $fmt($row['I20kV'], 2) . ' A</div>';
                echo '    <div class="label">P (мощност):</div><div class="value">'    . $fmt($row['P20kV'], 2) . ' kW</div>';
                echo '    <div class="label">cos &phi;:</div><div class="value">'      . $fmt($row['Cos20kV'], 3) . '</div>';
                echo '  </div>';
                echo '</div>';

                // Производство
                echo '<div class="data-box"><strong>Производство:</strong> ' . $fmt($row['CurrentProduction'], 2) . ' kW</div>';

                if (!empty($row['Representative'])) {
                    echo '<p><strong>Представител:</strong> ' . htmlspecialchars($row['Representative']) . '</p>';
                }

                if (!empty($row['EventMessage'])) {
                    echo '<div class="alarm-box"><strong>Аларма:</strong> ' . htmlspecialchars($row['EventMessage']) . '</div>';
                } else {
                    echo '<div class="no-alarm-box"><strong>Няма Аларми</strong></div>';
                }

                // Бутони – ПО-ГОЛЕМИ попъпи (за вътрешното меню)
                $custId = (int)$row['CustomerID'];
                echo '<div class="btn-row">';
                echo "  <button class=\"btn btn-limit\" onclick=\"return openPopup('limit_control.php?customer={$custId}','limitWin',520,600);\">РЪЧНО ЛИМИТИРАНЕ</button>";
                echo "  <button class=\"btn btn-schedule\" onclick=\"return openPopup('create_control.php?customer={$custId}','scheduleWin',1150,750);\">СЪЗДАЙ ГРАФИК</button>";
                echo '</div>';

                // Ред: Статуси + Команда
                $breakerText = ($breaker===true)?'ВКЛЮЧЕНО':(($breaker===false)?'ИЗКЛЮЧЕНО':'—');
                $breakerCls  = ($breaker===true)?'green':(($breaker===false)?'red':'gray');
                $relayText   = ($relay===true)?'ВКЛЮЧЕНО':(($relay===false)?'ИЗКЛЮЧЕНО':'—');
                $relayCls    = ($relay===true)?'green':(($relay===false)?'red':'gray');
                $checkedAttr = ($tm===true)?'checked':'';

                echo '<div class="status-row">';
                echo '  <span class="status-badge ' . $breakerCls . '">Прекъсвач: ' . $breakerText . '</span>';
                echo '  <span class="status-badge ' . $relayCls   . '">Релейна: '   . $relayText   . '</span>';

                // >>> ТУК Е ЕДИНСТВЕНАТА ФУНКЦИОНАЛНА ПРОМЯНА: action и redirect <<<
                echo '  <form class="toggle-wrap" method="post" action="telemechanics_toggle_desktop.php">';
                echo '      <span class="toggle-label">ТЕЛЕМЕХАНИКА (URNA)</span>';
                echo '      <input type="hidden" name="customer" value="' . $custId . '">';
                echo '      <input type="hidden" name="redirect" value="' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php', ENT_QUOTES) . '">';
                echo '      <label class="switch" aria-label="Телемеханика (команда)">';
                echo '          <input type="checkbox" name="to" value="1" '.$checkedAttr.' onchange="autoSubmitToggle(this)">';
                echo '          <span class="slider"></span>';
                echo '      </label>';
                echo '  </form>';
                // <<< КРАЙ НА ПРОМЯНАТА

                echo '</div>'; // status-row
                echo '<a href="client_details.php?id=' . $custId . '">Детайли</a>';
                echo '</div>'; // client-card
            }
        } else {
            echo '<p>Няма налични клиенти.</p>';
        }
    } catch (PDOException $e) {
        echo '<p>Грешка при извличане на данни: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
?>
</div>
</body>
</html>