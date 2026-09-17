<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_set_cookie_params(['path' => '/']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../config.php';

// valida token (1 envio só)
$token = $_POST['form_token'] ?? '';
if ($token === '' || empty($_SESSION['form_token']) || !hash_equals($_SESSION['form_token'], $token)) {
  http_response_code(400);
  echo "Requisição inválida ou repetida. Volte e tente novamente.";
  exit;
}
unset($_SESSION['form_token']); // consome



function brl_to_decimal(?string $str): float {
  $str = (string)$str;
  $str = preg_replace('/[^0-9,.-]/', '', $str);
  $str = str_replace(['.', ' '], '', $str);
  $str = str_replace(',', '.', $str);
  return is_numeric($str) ? (float)$str : 0.0;
}

function bool_ptbr($v) {
  if ($v === 'Sim') return 1;
  if ($v === 'Não' || $v === 'Nao') return 0;
  return null;
}

$vig_fim = trim((string)($_POST['vigencia'] ?? ''));
if ($vig_fim === '') $vig_fim = null;
$vig_fim = $vig_fim === '' ? null : $vig_fim;

$mes = $_POST['mes'] ?? [];
$toDecimal = fn($i) => brl_to_decimal($mes[$i] ?? '0');

$mesCampos = [
  'janeiro','fevereiro','marco','abril',
  'maio','junho','julho','agosto',
  'setembro','outubro','novembro','dezembro'
];

// ======================= CÓDIGO POA ==========================
$temaSelecionado = $_POST['tema_custo'] ?? null;
$codigo_poa = null;

if (!empty($temaSelecionado)) {
  // pega só o número antes do " - "
  $temaCodigo = '';
  $parts = explode(' - ', $temaSelecionado, 2);
  $temaCodigo = trim($parts[0] ?? '');

  if ($temaCodigo !== '') {
    // conta quantos registros já existem com esse mesmo tema_custo
    $sqlCount = "SELECT COUNT(*) AS qtde FROM novo_contrato WHERE tema_custo = ?";
    if ($stmtCount = $poa->prepare($sqlCount)) {
      $stmtCount->bind_param('s', $temaSelecionado);
      $stmtCount->execute();
      $resCount = $stmtCount->get_result();
      $rowCount = $resCount ? $resCount->fetch_assoc() : ['qtde' => 0];
      $seq = (int)($rowCount['qtde'] ?? 0) + 1; 
      
      $codigo_poa = $temaCodigo . '.' . $seq;
    }
  }
}

$campos = [
  'codigo_poa'       => $codigo_poa,

  'usuario_cehab'    => $_SESSION['usuario']['nome'] ?? null,
  'tema_custo'       => $temaSelecionado,
  'setor'            => $_POST['setor'] ?? null,
  'gestor'           => $_POST['gestor'] ?? null,
  'licenca_ambiental_valida' => $_POST['licenca_ambiental_valida'] ?? null,
  'objeto'           => $_POST['objeto'] ?? null,
  'status_contrato'  => $_POST['status'] ?? null,
  'numero_contrato'  => $_POST['numero_contrato'] ?? null,
  'credor'           => $_POST['credor'] ?? null,
  'vigencia_fim'     => $vig_fim,
  'dea'              => bool_ptbr($_POST['dea'] ?? null),
  'reajuste'         => bool_ptbr($_POST['reajuste'] ?? null),
  'fonte'            => $_POST['fonte'] ?? null,
  'grupo_despesa'    => $_POST['grupo'] ?? null,
  'sei'              => $_POST['sei'] ?? null,
  'valor_total' => brl_to_decimal($_POST['valor_total_contrato'] ?? '0'),
  'acao'             => $_POST['acao'] ?? null,
  'subacao'          => $_POST['subacao'] ?? null,
  'ficha_financeira' => $_POST['ficha_financeira'] ?? null,
  'macro_tema'       => $_POST['macro_tema'] ?? null,
  'priorizacao'      => $_POST['priorizacao'] ?? null,
  'prorrogavel'      => bool_ptbr($_POST['prorrogavel'] ?? null),
  'observacoes'      => $_POST['observacoes'] ?? null,
];

foreach ($mesCampos as $i => $nomeMes) {
  $campos[$nomeMes] = $toDecimal($i);
}

$cols = implode(',', array_map(fn($c) => "`$c`", array_keys($campos)));
$placeholders = rtrim(str_repeat('?,', count($campos)), ',');
$sql = "INSERT INTO `novo_contrato` ($cols) VALUES ($placeholders)";

$stmt = $poa->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo "Erro ao preparar statement: " . $poa->error;
  exit;
}

$types = '';
$values = [];
$numericos = array_merge(['valor_total'], $mesCampos);

foreach ($campos as $k => $v) {
  if (in_array($k, $numericos, true)) {
    $types .= 'd';
    $values[] = (float)$v;
  } elseif (in_array($k, ['dea','reajuste','prorrogavel'], true)) {
    $types .= 'i';
    $values[] = is_null($v) ? null : (int)$v;
  } else {
    $types .= 's';
    $values[] = $v;
  }
}

$stmt->bind_param($types, ...$values);

if ($stmt->execute()) {
  ?>
  <!doctype html>
  <html lang="pt-br">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato Salvo • POA</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
      <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-14 w-14 text-green-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <h1 class="text-xl font-semibold text-slate-800 mb-2">Contrato salvo com sucesso!</h1>
        <p class="text-slate-600 mb-6">As informações do contrato foram registradas corretamente no sistema.</p>
        <button onclick="window.location.href='home.php'"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl shadow-sm">
          OK
        </button>
      </div>
    </div>
  </body>
  </html>
  <?php
  exit;
} else {
  http_response_code(500);
  echo "Erro ao salvar: " . $stmt->error;
}