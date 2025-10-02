<?php
// index-mobile.php — мобилен дашборд (без горните бутони).
// • Динамичен SELECT (проверки за таблици/колони)
// • Агрегати 400V/20kV, статуси, аларми
// • Суич „Телемеханика (КОМАНДА)” → telemechanics_toggle_mobile.php
// • Авто-рефреш и безопасни заглавки

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
require_once __DIR__ . '/db_connection.php';

// ===== Помощни функции =====
function tableExists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:t");
    $st->execute([':t'=>$table]);
    return ((int)$st->fetch()['c'])>0;
}
function columnExists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:t AND column_name=:c");
    $st->execute([':t'=>$table, ':c'=>$column]);
    return ((int)$st->fetch()['c'])>0;
}
$fmt = function($v, $dec=2){
    if ($v===null || $v==='') return '—';
    if (is_numeric($v)) {
        $n = (float)$v;
        $s = number_format($n, $dec, '.', '');
        return htmlspecialchars(rtrim(rtrim($s,'0'),'.'));
    }
    return htmlspecialchars((string)$v);
};
$toBool = function($v){
    if ($v===null) return null;
    $s=strtolower(trim((string)$v));
    if (in_array($s, ['1','on','true','yes','вкл','включено','closed','enabled'])) return true;
    if (in_array($s, ['0','off','false','no','изкл','изключено','open','disabled'])) return false;
    if (is_numeric($v)) return ((float)$v)!=0.0;
    return null;
};

// ===== Динамичен SELECT =====
$hasCustomers    = tableExists($conn, 'Customers');
$hasPVPlants     = tableExists($conn, 'PVPlants');
$hasInverters    = tableExists($conn, 'Inverters');
$hasInvData      = tableExists($conn, 'InverterData');
$hasGlobalEvents = tableExists($conn, 'GlobalEvents');

if (!$hasCustomers) { http_response_code(500); die('Липсва таблица Customers в базата.'); }

$parts = [];
$parts[] = "c.CustomerName";
$parts[] = "c.Representative";
$parts[] = "c.CustomerID";
$parts[] = ($hasInvData && columnExists($conn,'InverterData','TotalPower'))
    ? "COALESCE(SUM(id.TotalPower),0) AS CurrentProduction"
    : "0 AS CurrentProduction";
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

$parts[] = ($hasPVPlants && columnExists($conn,'PVPlants','BreakerStatus'))
    ? "COALESCE(MAX(p.BreakerStatus),0) AS BreakerStatus"
    : "NULL AS BreakerStatus";
$parts[] = ($hasPVPlants && columnExists($conn,'PVPlants','RelayStatus'))
    ? "COALESCE(MAX(p.RelayStatus),0)   AS RelayStatus"
    : "NULL AS RelayStatus";
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
    $stmt = $conn->prepare($sql);
    $stmt->execute();
} catch (PDOException $e) {
    $stmt = null; $fatalSqlError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>СКАДА — Мобилен изглед</title>
<style>
:root{
  --bg:#0e1116; --card:#151a23; --muted:#9aa4b2; --ok:#2ecc71; --warn:#f1c40f; --err:#e74c3c;
}
*{box-sizing:border-box}
body{ margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue",Arial,"Noto Sans",sans-serif; background:var(--bg); color:#eef2f8;}
header{ position:sticky; top:0; z-index:10; background:#11161f; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px; border-bottom:1px solid #1f2a37;}
h1{font-size:18px;margin:0}
.refresh{font-size:12px;opacity:.75}
main{padding:16px; display:grid; grid-template-columns:1fr; gap:16px; max-width:980px; margin:0 auto}
.card{ background:var(--card); border:1px solid #202735; border-radius:14px; padding:14px; display:flex; flex-direction:column; gap:10px; box-shadow:0 5px 16px rgba(0,0,0,.35);}
.card .name{ background:#2c7c31; color:#fff; font-weight:700; padding:10px 12px; border-radius:10px; text-align:center; font-size:16px;}
.kv{ display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px; color:#e5e7eb;}
.kv div{ background:#0f141d; border:1px dashed #253144; padding:10px; border-radius:10px;}
.kv strong{display:block; font-size:12px; color:var(--muted); font-weight:600}
.badges{display:flex; gap:8px; flex-wrap:wrap}
.badge{font-size:12px; padding:6px 10px; border-radius:999px; border:1px solid #2b3648}
.badge.ok{ background:rgba(46,204,113,.12); border-color:#2ecc71;}
.badge.warn{ background:rgba(241,196,15,.12); border-color:#f1c40f;}
.badge.err{ background:rgba(231,76,60,.12); border-color:#e74c3c;}
.actions{display:flex; gap:10px; flex-wrap:wrap}
.actions a, .actions button{ flex:1; min-width:120px; background:#0f1722; border:1px solid #2b3648; color:#fff; padding:10px 12px; border-radius:10px; font-weight:700; text-decoration:none; text-align:center}
.actions a:hover, .actions button:hover{ background:#162033; }
footer{ text-align:center; padding:24px 16px; color:#97a6ba; font-size:12px }
.switch{position:relative; display:inline-block; width:46px; height:28px; vertical-align:middle}
.switch input{display:none}
.slider{position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#7f8c8d; transition:.2s; border-radius:999px}
.slider:before{position:absolute; content:""; height:22px; width:22px; left:3px; bottom:3px; background:white; transition:.2s; border-radius:999px}
.switch input:checked + .slider{ background:#27ae60}
.switch input:checked + .slider:before{ transform:translateX(18px)}
@media (min-width:780px){ main{ grid-template-columns:1fr 1fr; } }
</style>
<script>
// авто-рефреш (10 сек)
setTimeout(function(){ window.location.reload(); }, 10000);
function autoSubmitToggle(el){ if(el && el.form){ el.form.submit(); } }
function openPopup(url, name, w, h){
  const left=(screen.width-w)/2, top=(screen.height-h)/2;
  window.open(url, name, `width=${w},height=${h},top=${top},left=${left},resizable=yes,scrollbars=yes`);
}
</script>
</head>
<body>
<header>
  <h1>СКАДА • Мобилен</h1>
  <a href="index.php" style="color:#9fb4ff;text-decoration:none;font-size:12px">Десктоп изглед</a>
</header>
<main>
  <?php if (isset($fatalSqlError)): ?>
    <div class="card">
      <div class="name" style="background:#b23a3a">SQL грешка</div>
      <div><?= htmlspecialchars($fatalSqlError) ?></div>
    </div>
  <?php endif; ?>

  <?php
  if ($stmt && $stmt->rowCount() > 0) {
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $custId = (int)$row['CustomerID'];
          $breaker = $toBool($row['BreakerStatus']);
          $relay   = $toBool($row['RelayStatus']);
          $telem   = $toBool($row['TelemechanicsEnabled'] ?? null);
          $breakerText = ($breaker===true?'Затворен':($breaker===false?'Отворен':'Н/П'));
          $relayText   = ($relay===true?'Включена':($relay===false?'Изключена':'Н/П'));
          $alarmMsg = trim((string)($row['EventMessage'] ?? ''));

          echo '<article class="card">';
          echo '  <div class="name">'.htmlspecialchars($row['CustomerName']).'</div>';

          echo '  <div class="kv">';
          echo '    <div><strong>Производство (общо)</strong><span>'.$fmt($row['CurrentProduction']).' kW</span></div>';
          echo '    <div><strong>Представител</strong><span>'.htmlspecialchars($row['Representative']).'</span></div>';
          echo '    <div><strong>400V (U/I/P/Cosφ)</strong><span>'.$fmt($row['V400']).' V • '.$fmt($row['I400']).' A • '.$fmt($row['P400']).' kW • '.$fmt($row['Cos400']).'</span></div>';
          echo '    <div><strong>20 kV (U/I/P/Cosφ)</strong><span>'.$fmt($row['V20kV']).' V • '.$fmt($row['I20kV']).' A • '.$fmt($row['P20kV']).' kW • '.$fmt($row['Cos20kV']).'</span></div>';
          echo '  </div>';

          echo '  <div class="badges">';
          $bCls = $breaker===true?'ok':($breaker===false?'err':'warn');
          $rCls = $relay===true?'ok':($relay===false?'err':'warn');
          echo '    <span class="badge '.$bCls.'">Прекъсвач: '.$breakerText.'</span>';
          echo '    <span class="badge '.$rCls.'">Релейна: '.$relayText.'</span>';
          echo      ($alarmMsg!=='') ? '<span class="badge err">Аларма: '.htmlspecialchars($alarmMsg).'</span>' : '<span class="badge ok">Аларма: няма</span>';
          echo '  </div>';

          // превключвател за телемеханика — ИДЕНТИЧНО име към големия index: name="to"
          $checkedAttr = $telem ? 'checked' : '';
          $redir = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index-mobile.php', ENT_QUOTES);
          echo '  <form method="post" action="telemechanics_toggle_mobile.php" style="display:flex; align-items:center; gap:10px">';
          echo '      <input type="hidden" name="customer" value="'.$custId.'">';
          echo '      <input type="hidden" name="redirect" value="'.$redir.'">';
          echo '      <label style="font-size:12px; color:#9aa4b2">Телемеханика</label>';
          echo '      <label class="switch" aria-label="Телемеханика (команда)">';
          echo '          <input type="checkbox" name="to" value="1" '.$checkedAttr.' onchange="autoSubmitToggle(this)">';
          echo '          <span class="slider"></span>';
          echo '      </label>';
          echo '  </form>';

          echo '  <div class="actions">';
          echo '      <a href="detail-mobile.php?id='.$custId.'">ДЕТАЙЛИ</a>';
          echo '      <button onclick="openPopup(\'limit_control.php?customer='.$custId.'\',\'limit'.$custId.'\',520,420)">ЛИМИТИРАНЕ</button>';
          echo '      <button onclick="openPopup(\'create_control.php?customer='.$custId.'\',\'cmd'.$custId.'\',560,460)">ГРАФИК</button>';
          echo '  </div>';

          echo '</article>';
      }
  } else {
      echo '<div class="card"><div class="name">Информация</div><p>Няма налични клиенти.</p></div>';
  }
  ?>
</main>
<footer>TECHNOSUN.BG • КОНТРОЛ НА СОЛАРНИ ПАРКОВЕ</footer>
</body>
</html>
