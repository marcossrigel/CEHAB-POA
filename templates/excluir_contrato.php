<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo "Sessão não iniciada. Acesse via login.";
  exit;
}

require_once __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
  header('Location: home.php?erro=ID_INVAL');
  exit;
}

$stmt = $poa->prepare("DELETE FROM novo_contrato WHERE id = ?");
if (!$stmt) {
  header('Location: home.php?erro=PREP_DEL');
  exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  header('Location: home.php?msg=EXCLUIDO');
} else {
  header('Location: home.php?erro=NAO_ENCONTRADO');
}
exit;
