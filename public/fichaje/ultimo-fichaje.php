<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__.'/../includes/init.php';


if (!isset($_SESSION['empleado_id'])) {
  http_response_code(401);
  echo json_encode([]);
  exit;
}
$emp = $_SESSION['empleado_id'];
// Busca última entrada hoy
$sql = "SELECT hora, tipo 
          FROM fichajes 
         WHERE empleado_id=:e AND DATE(hora)=CURDATE()
         ORDER BY hora DESC
         LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['e'=>$emp]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$f) {
  echo json_encode(['tipo'=>'none','hora'=>'--:--','diffMin'=>0]);
  exit;
}
$hora = (new DateTime($f['hora']))->format('H:i');

// calcula minutos desde esa hora hasta ahora
$ini = new DateTime($f['hora']);
$now = new DateTime();
$diffMin = ($now->getTimestamp() - $ini->getTimestamp())/60;

echo json_encode([
  'tipo'    => $f['tipo'],
  'hora'    => $hora,
  'diffMin' => (int)$diffMin
]);
