<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  exit("Sessão não iniciada. Acesse via login.");
}

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método inválido.');
}

if (!isset($poa) || !($poa instanceof mysqli)) {
  http_response_code(500);
  exit('Conexão com o banco ($poa) não foi inicializada. Verifique o config.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) exit('ID de contrato inválido.');

function parse_brl($str) {
  if ($str === null) return 0.0;
  $str = trim((string)$str);
  if ($str === '') return 0.0;

  $str = preg_replace('/[^\d\.,-]/', '', $str);
  if ($str === '' || $str === '-' || $str === '.' || $str === ',') return 0.0;

  $neg = false;
  if (strpos($str, '-') !== false) { $neg = true; $str = str_replace('-', '', $str); }

  $lastComma = strrpos($str, ',');
  $lastDot   = strrpos($str, '.');

  $decSep = null;
  if ($lastComma !== false && $lastDot !== false) $decSep = ($lastComma > $lastDot) ? ',' : '.';
  elseif ($lastComma !== false) $decSep = ',';
  elseif ($lastDot !== false) $decSep = '.';

  if ($decSep !== null) {
    $parts = explode($decSep, $str);
    $dec   = preg_replace('/\D/', '', array_pop($parts));
    $int   = preg_replace('/\D/', '', implode('', $parts));

    $dec = substr($dec, 0, 2);
    $dec = str_pad($dec, 2, '0');
    $num = ($int === '' ? '0' : $int) . '.' . $dec;
  } else {
    $num = preg_replace('/\D/', '', $str);
    if ($num === '') $num = '0';
  }

  $val = (float)$num;
  return $neg ? -$val : $val;
}

function bool_from_label($v) {
  $v = trim((string)$v);
  if ($v === '') return null;
  $v = mb_strtolower($v, 'UTF-8');
  if ($v === 'sim') return 1;
  if ($v === 'não' || $v === 'nao') return 0;
  return null;
}

function norm_str($v) {
  $v = (string)($v ?? '');
  $v = trim($v);
  return $v === '' ? null : $v;
}

function norm_num2($v) {
  // aceita string "1234.56" ou float
  if ($v === null || $v === '') return 0.00;
  return round((float)$v, 2);
}

function changed_num($a, $b) {
  return abs(norm_num2($a) - norm_num2($b)) > 0.004; // tolerância centavos
}

function changed_str($a, $b) {
  return norm_str($a) !== norm_str($b);
}

function changed_int($a, $b) {
  return (int)($a ?? 0) !== (int)($b ?? 0);
}

// ------ inputs ------
$tema_custo       = $_POST['tema_custo']        ?? '';
$setor            = $_POST['setor']             ?? '';
$gestor           = $_POST['gestor']            ?? '';
$objeto           = $_POST['objeto']            ?? '';
$status           = $_POST['status']            ?? '';
$numero_contrato  = $_POST['numero_contrato']   ?? '';
$credor           = $_POST['credor']            ?? '';
$vigencia_fim_txt = trim((string)($_POST['vigencia'] ?? '')); 
$dea_label        = $_POST['dea']               ?? '';
$reajuste_label   = $_POST['reajuste']          ?? '';
$fonte            = $_POST['fonte']             ?? '';
$grupo            = $_POST['grupo']             ?? '';
$sei              = $_POST['sei']               ?? '';
$valor_total_str  = $_POST['valor_total'] ?? '0';
$acao             = $_POST['acao']              ?? '';
$subacao          = $_POST['subacao']           ?? '';
$ficha_financeira = $_POST['ficha_financeira']  ?? '';
$macro_tema       = $_POST['macro_tema']        ?? '';
$priorizacao      = $_POST['priorizacao']       ?? '';
$prorrogavel_lbl  = $_POST['prorrogavel']       ?? '';
$observacoes      = $_POST['observacoes']       ?? '';
$mesesPost        = $_POST['mes']               ?? [];

$dea         = bool_from_label($dea_label);
$reajuste    = bool_from_label($reajuste_label);
$prorrogavel = bool_from_label($prorrogavel_lbl);

$valor_total = parse_brl($valor_total_str);
if ($valor_total > 999999999999999.99) exit('Valor total excede o limite permitido.');
$valor_total_db = number_format($valor_total, 2, '.', '');

$mesValsDb = [];
for ($i = 0; $i < 12; $i++) {
  $v = isset($mesesPost[$i]) ? parse_brl($mesesPost[$i]) : 0.0;
  $mesValsDb[$i] = number_format($v, 2, '.', '');
}

// usuário editor
$editorNome  = $_SESSION['usuario']['nome']  ?? 'usuário';
$editorLogin = $_SESSION['usuario']['login'] ?? '';

// carrega estado anterior
$stmtOld = $poa->prepare("SELECT * FROM novo_contrato WHERE id = ?");
$stmtOld->bind_param('i', $id);
$stmtOld->execute();
$oldRes = $stmtOld->get_result();
$old = $oldRes ? $oldRes->fetch_assoc() : null;
if (!$old) exit('Contrato não encontrado para auditoria.');

// monte um array "novo" já no formato do banco (mesmo que você vai salvar)
$new = [
  'tema_custo'       => $tema_custo,
  'setor'            => $setor,
  'gestor'           => $gestor,
  'objeto'           => $objeto,
  'status_contrato'  => $status,
  'numero_contrato'  => $numero_contrato,
  'credor'           => $credor,
  'vigencia_fim'     => ($vigencia_fim_txt === '' ? null : $vigencia_fim_txt),
  'observacoes'      => $observacoes,
  'dea'              => ($dea === null ? 0 : $dea),
  'reajuste'         => ($reajuste === null ? 0 : $reajuste),
  'fonte'            => $fonte,
  'grupo_despesa'    => $grupo,
  'sei'              => $sei,
  'valor_total'      => $valor_total_db,
  'acao'             => $acao,
  'subacao'          => $subacao,
  'ficha_financeira' => $ficha_financeira,
  'macro_tema'       => $macro_tema,
  'priorizacao'      => $priorizacao,
  'prorrogavel'      => ($prorrogavel === null ? 0 : $prorrogavel),
  'janeiro'          => $mesValsDb[0],
  'fevereiro'        => $mesValsDb[1],
  'marco'            => $mesValsDb[2],
  'abril'            => $mesValsDb[3],
  'maio'             => $mesValsDb[4],
  'junho'            => $mesValsDb[5],
  'julho'            => $mesValsDb[6],
  'agosto'           => $mesValsDb[7],
  'setembro'         => $mesValsDb[8],
  'outubro'          => $mesValsDb[9],
  'novembro'         => $mesValsDb[10],
  'dezembro'         => $mesValsDb[11],
];

// compara e gera diffs
$diffs = [];

$cmpStr = ['tema_custo','setor','gestor','objeto','status_contrato','numero_contrato','credor','vigencia_fim','observacoes','fonte','grupo_despesa','sei','acao','subacao','ficha_financeira','macro_tema','priorizacao'];
foreach ($cmpStr as $k) {
  if (changed_str($old[$k] ?? null, $new[$k] ?? null)) {
    $diffs[] = ['campo'=>$k, 'antes'=>$old[$k] ?? null, 'depois'=>$new[$k] ?? null];
  }
}

$cmpInt = ['dea','reajuste','prorrogavel'];
foreach ($cmpInt as $k) {
  if (changed_int($old[$k] ?? 0, $new[$k] ?? 0)) {
    $diffs[] = ['campo'=>$k, 'antes'=>(int)($old[$k] ?? 0), 'depois'=>(int)($new[$k] ?? 0)];
  }
}

if (changed_num($old['valor_total'] ?? 0, $new['valor_total'] ?? 0)) {
  $diffs[] = ['campo'=>'valor_total', 'antes'=>$old['valor_total'] ?? 0, 'depois'=>$new['valor_total'] ?? 0];
}

$cmpMes = ['janeiro','fevereiro','marco','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
foreach ($cmpMes as $k) {
  if (changed_num($old[$k] ?? 0, $new[$k] ?? 0)) {
    $diffs[] = ['campo'=>$k, 'antes'=>$old[$k] ?? 0, 'depois'=>$new[$k] ?? 0];
  }
}

// salva notificação (só se houve mudança)
if (!empty($diffs)) {
  $changesJson = json_encode($diffs, JSON_UNESCAPED_UNICODE);

  $stmtN = $poa->prepare("
    INSERT INTO notificacoes_edicao (contrato_id, editor_login, editor_nome, changes_json)
    VALUES (?, ?, ?, ?)
  ");
  $stmtN->bind_param('isss', $id, $editorLogin, $editorNome, $changesJson);
  $stmtN->execute();
}


$sql = "
  UPDATE novo_contrato
  SET
    tema_custo        = ?,
    setor             = ?,
    gestor            = ?,
    objeto            = ?,
    status_contrato   = ?,
    numero_contrato   = ?,
    credor            = ?,
    vigencia_fim      = ?,
    observacoes       = ?,
    dea               = ?,
    reajuste          = ?,
    fonte             = ?,
    grupo_despesa     = ?,
    sei               = ?,
    valor_total       = ?,
    acao              = ?,
    subacao           = ?,
    ficha_financeira  = ?,
    macro_tema        = ?,
    priorizacao       = ?,
    prorrogavel       = ?,
    janeiro           = ?,
    fevereiro         = ?,
    marco             = ?,
    abril             = ?,
    maio              = ?,
    junho             = ?,
    julho             = ?,
    agosto            = ?,
    setembro          = ?,
    outubro           = ?,
    novembro          = ?,
    dezembro          = ?
  WHERE id = ?
";

$stmt = $poa->prepare($sql);

// 9 strings + 2 ints + 4 strings + 5 strings + 1 int + 12 strings + 1 int
$types =
  "sssssssss" .  // tema..observacoes (9)
  "ii" .         // dea, reajuste
  "ssss" .       // fonte, grupo, sei, valor_total
  "sssss" .      // acao, subacao, ficha, macro, priorizacao
  "i" .          // prorrogavel
  "ssssssssssss" . // 12 meses
  "i";           // id

$params = [
  $tema_custo,
  $setor,
  $gestor,
  $objeto,
  $status,
  $numero_contrato,
  $credor,
  ($vigencia_fim_txt === '' ? null : $vigencia_fim_txt),
  $observacoes,
  ($dea === null ? 0 : $dea),
  ($reajuste === null ? 0 : $reajuste),
  $fonte,
  $grupo,
  $sei,
  $valor_total_db,
  $acao,
  $subacao,
  $ficha_financeira,
  $macro_tema,
  $priorizacao,
  ($prorrogavel === null ? 0 : $prorrogavel),
  ...$mesValsDb,
  $id
];

$stmt->bind_param($types, ...$params);
$stmt->execute();

header("Location: editar_contrato.php?id={$id}&ok=1");
exit;
