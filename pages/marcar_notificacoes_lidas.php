<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  exit('Sem sessão');
}

$login = mb_strtolower(trim($_SESSION['usuario']['login'] ?? ''), 'UTF-8');
$nome  = mb_strtolower(trim($_SESSION['usuario']['nome'] ?? ''), 'UTF-8');

$isBruno = ($login === 'bruno.passavante' || $nome === 'bruno passavante de oliveira');
if (!$isBruno) {
  http_response_code(403);
  exit('Sem permissão');
}

require_once __DIR__ . '/../config.php';

$poa->query("UPDATE notificacoes_edicao SET lida = 1, lida_em = NOW() WHERE lida = 0");

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true]);
