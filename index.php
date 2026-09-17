<?php
$manutencao = false; 

if ($manutencao) {
    include __DIR__ . '/manutencao.php';
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__.'/referencia.php';

$token = $_GET['token'] ?? ($_GET['access_dinamic'] ?? '');
if ($token === '') {
  http_response_code(400);
  echo 'Token ausente';
  exit;
}

$sql = "SELECT u.u_nome_completo
        FROM token_sessao t
        JOIN users u ON u.u_rede = t.u_rede
        WHERE t.token = ?";
$stmt = $cehab->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $_SESSION['usuario'] = [
    'nome' => $row['u_nome_completo'],
    'token' => $token
  ];
  header('Location: templates/home.php');
  exit;
} else {
  http_response_code(401);
  echo 'Token inválido/expirado';
}
