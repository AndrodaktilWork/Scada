<?php
// Мобилен хендлър — връща по подразбиране към index-mobile.php
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }

require_once __DIR__ . '/db_connection.php';

$customerId = isset($_POST['customer']) ? (int)$_POST['customer'] : 0;
$toggleOn = (isset($_POST['to']) && ($_POST['to']==='1'||$_POST['to']==='on'))
         || (isset($_POST['toggle']) && ($_POST['toggle']==='1'||$_POST['toggle']==='on'));
if ($customerId<=0){ http_response_code(400); exit('Невалиден клиент.'); }

try {
    $st=$conn->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='Customers' AND column_name='TelemechanicsEnabled'");
    $st->execute(); if(!(int)$st->fetch()['c']){ http_response_code(500); exit('Липсва Customers.TelemechanicsEnabled'); }

    $ch=$conn->prepare("SELECT COUNT(*) c FROM Customers WHERE CustomerID=:cid");
    $ch->execute([':cid'=>$customerId]); if(!(int)$ch->fetch()['c']){ http_response_code(404); exit('Клиентът не е намерен.'); }

    $u=$conn->prepare("UPDATE Customers SET TelemechanicsEnabled=:v WHERE CustomerID=:cid");
    $u->execute([':v'=>$toggleOn?1:0, ':cid'=>$customerId]);

    // безопасен редирект
    $redir = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index-mobile.php');
    if (preg_match('/^https?:/i',$redir) || !preg_match('/^[A-Za-z0-9_\-\/\.\?\=&%]+$/',$redir)) $redir='index-mobile.php';
    header('Location: '.$redir); exit;
} catch (PDOException $e) {
    http_response_code(500); echo 'Грешка: '.htmlspecialchars($e->getMessage()); exit;
}
