<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'msg' => 'Sessão não iniciada']);
  exit;
}

$nomeUsuario  = trim($_SESSION['usuario']['nome']  ?? '');
$loginUsuario = trim($_SESSION['usuario']['login'] ?? '');
$cargoUsuario = trim($_SESSION['usuario']['cargo'] ?? '');

$nomeCheck  = mb_strtolower($nomeUsuario, 'UTF-8');
$loginCheck = mb_strtolower($loginUsuario, 'UTF-8');
$cargoCheck = mb_strtolower($cargoUsuario, 'UTF-8');

$isBruno = ($loginCheck === 'bruno.passavante' || $nomeCheck === 'bruno passavante de oliveira');
$isAdmin = ($cargoCheck === 'gestor' || $isBruno);

if (!$isAdmin) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'Sem permissão']);
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
  exit;
}

require_once __DIR__ . '/../config.php';

try {
  $stmt = $poa->prepare("
    UPDATE notificacoes_edicao
    SET lida = 1, lida_em = NOW()
    WHERE lida = 0
  ");
  if (!$stmt) {
    throw new Exception($poa->error);
  }

  $stmt->execute();

  echo json_encode([
    'ok' => true,
    'updated' => $stmt->affected_rows
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
