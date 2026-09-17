<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo "Sessão não iniciada. Acesse via index.php?token=...";
  exit;
}

$nomeUsuario  = trim($_SESSION['usuario']['nome']  ?? 'usuário');
$loginUsuario = trim($_SESSION['usuario']['login'] ?? '');
$cargoUsuario = trim($_SESSION['usuario']['cargo'] ?? '');

$nomeCheck   = mb_strtolower($nomeUsuario, 'UTF-8');
$loginCheck  = mb_strtolower($loginUsuario, 'UTF-8');
$cargoCheck  = mb_strtolower($cargoUsuario, 'UTF-8');

$isAdmin = (
    $cargoCheck === 'gestor'
    || $loginCheck === 'bruno.passavante'
    || $nomeCheck === 'bruno passavante de oliveira'
    || $nomeCheck === 'marcos rigel silvestre da silva'
);

$identUsuario = $nomeUsuario;

require_once __DIR__ . '/../config.php';

$setorUsuario = '';
$usuariosDoSetor = [];

$stmtSetor = $cehab->prepare("
    SELECT setor_local
    FROM users
    WHERE u_nome_completo = ?
    LIMIT 1
");

$stmtSetor->bind_param('s', $nomeUsuario);
$stmtSetor->execute();

$resSetor = $stmtSetor->get_result();

if ($dadosUsuario = $resSetor->fetch_assoc()) {
    $setorUsuario = trim($dadosUsuario['setor_local'] ?? '');
}

$stmtSetor->close();

$siglaSetorUsuario = '';

if ($setorUsuario !== '') {
    $partesSetor = explode(' - ', $setorUsuario, 2);
    $siglaSetorUsuario = trim($partesSetor[0] ?? '');
}

if ($setorUsuario !== '') {

    $stmtUsuariosSetor = $cehab->prepare("
        SELECT u_nome_completo
        FROM users
        WHERE setor_local = ?
          AND u_nome_completo IS NOT NULL
          AND TRIM(u_nome_completo) <> ''
    ");

    $stmtUsuariosSetor->bind_param('s', $setorUsuario);
    $stmtUsuariosSetor->execute();

    $resUsuariosSetor = $stmtUsuariosSetor->get_result();

    while ($usuarioSetor = $resUsuariosSetor->fetch_assoc()) {

        $nome = trim($usuarioSetor['u_nome_completo'] ?? '');

        if ($nome !== '') {
            $usuariosDoSetor[] = $nome;
        }
    }

    $stmtUsuariosSetor->close();
}

$busca = trim($_GET['q'] ?? '');

$diretoriaFiltro = trim($_GET['diretoria'] ?? '');

$where  = "1";
$params = [];
$types  = "";
$diretoriasDisponiveis = [];

$usuariosComContrato = [];

$sqlUsuariosPOA = "
    SELECT DISTINCT TRIM(usuario_cehab) AS usuario_cehab
    FROM novo_contrato
    WHERE usuario_cehab IS NOT NULL
      AND TRIM(usuario_cehab) <> ''
";

$resUsuariosPOA = $poa->query($sqlUsuariosPOA);

if ($resUsuariosPOA) {

    while ($rowPOA = $resUsuariosPOA->fetch_assoc()) {

        $nome = trim($rowPOA['usuario_cehab'] ?? '');

        if ($nome !== '') {
            $usuariosComContrato[] = $nome;
        }
    }
}

if (!empty($usuariosComContrato)) {

    $placeholders = implode(
        ',',
        array_fill(0, count($usuariosComContrato), '?')
    );

    $sqlDiretorias = "
        SELECT DISTINCT setor_local
        FROM users
        WHERE u_nome_completo IN ($placeholders)
          AND setor_local IS NOT NULL
          AND TRIM(setor_local) <> ''
    ";

    $stmtDiretorias = $cehab->prepare($sqlDiretorias);

    $typesDiretorias = str_repeat(
        's',
        count($usuariosComContrato)
    );

    $stmtDiretorias->bind_param(
        $typesDiretorias,
        ...$usuariosComContrato
    );

    $stmtDiretorias->execute();

    $resDiretorias = $stmtDiretorias->get_result();

    while ($rowDiretoria = $resDiretorias->fetch_assoc()) {

        $setorCompleto = trim(
            $rowDiretoria['setor_local'] ?? ''
        );

        if ($setorCompleto === '') {
            continue;
        }

        $partes = explode(
            ' - ',
            $setorCompleto,
            2
        );

        $sigla = trim($partes[0] ?? '');

        if ($sigla !== '') {
            $diretoriasDisponiveis[] = $sigla;
        }
    }

    $stmtDiretorias->close();
}

$diretoriasDisponiveis = array_values(
    array_unique($diretoriasDisponiveis)
);

sort($diretoriasDisponiveis);

$isBruno = (
    $loginCheck === 'bruno.passavante'
    || $nomeCheck === 'bruno passavante de oliveira'
    || $nomeCheck === 'marcos rigel silvestre da silva'
);

if (!$isAdmin) {

    if ($siglaSetorUsuario !== '') {

        if (!empty($usuariosDoSetor)) {

            $phSetor = implode(
                ',',
                array_fill(0, count($usuariosDoSetor), '?')
            );

            $where .= " AND (
                setor = ?
                OR usuario_cehab IN ($phSetor)
                OR usuario_cehab = ?
            )";

            $params[] = $siglaSetorUsuario;
            $types .= 's';

            foreach ($usuariosDoSetor as $nome) {
                $params[] = $nome;
                $types .= 's';
            }

            $params[] = $identUsuario;
            $types .= 's';

        } else {

            $where .= " AND (
                setor = ?
                OR usuario_cehab = ?
            )";

            $params[] = $siglaSetorUsuario;
            $types .= 's';

            $params[] = $identUsuario;
            $types .= 's';
        }

    } else {

        $where .= " AND usuario_cehab = ?";

        $params[] = $identUsuario;
        $types .= 's';
    }
}

if ($isBruno && $diretoriaFiltro !== '') {

    if (in_array($diretoriaFiltro, $diretoriasDisponiveis, true)) {

        $usuariosDiretoria = [];

        $prefixoDiretoria = $diretoriaFiltro . ' -%';

        $stmtDiretoria = $cehab->prepare("
            SELECT u_nome_completo
            FROM users
            WHERE setor_local LIKE ?
              AND u_nome_completo IS NOT NULL
              AND TRIM(u_nome_completo) <> ''
        ");

        $stmtDiretoria->bind_param(
            's',
            $prefixoDiretoria
        );

        $stmtDiretoria->execute();

        $resDiretoria = $stmtDiretoria->get_result();

        while ($usuarioDiretoria = $resDiretoria->fetch_assoc()) {

            $nome = trim(
                $usuarioDiretoria['u_nome_completo'] ?? ''
            );

            if ($nome !== '') {
                $usuariosDiretoria[] = $nome;
            }
        }

        $stmtDiretoria->close();

        if (!empty($usuariosDiretoria)) {

            $placeholders = implode(
                ',',
                array_fill(
                    0,
                    count($usuariosDiretoria),
                    '?'
                )
            );

            $where .= "
                AND usuario_cehab IN ($placeholders)
            ";

            foreach ($usuariosDiretoria as $nome) {
                $params[] = $nome;
                $types .= 's';
            }

        } else {

            $where .= " AND 1 = 0";
        }
    }
}

$notifCount = 0;
$notifs = [];

if ($busca !== '') {
    $where .= " AND (numero_contrato LIKE ? OR credor LIKE ? OR objeto LIKE ?)";
    $like   = "%{$busca}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

if ($isBruno) {

    $sql = "
        SELECT
            id,
            codigo_poa,
            usuario_cehab,
            setor,
            objeto,
            status_contrato,
            numero_contrato,
            credor,
            dea,
            reajuste,
            ficha_financeira,
            priorizacao,
            created_at,
            janeiro,
            fevereiro,
            marco,
            abril,
            maio,
            junho,
            julho,
            agosto,
            setembro,
            outubro,
            novembro,
            dezembro
        FROM (

            SELECT
                id,
                codigo_poa       COLLATE utf8mb4_general_ci AS codigo_poa,
                usuario_cehab    COLLATE utf8mb4_general_ci AS usuario_cehab,
                setor            COLLATE utf8mb4_general_ci AS setor,
                objeto           COLLATE utf8mb4_general_ci AS objeto,
                status_contrato  COLLATE utf8mb4_general_ci AS status_contrato,
                numero_contrato  COLLATE utf8mb4_general_ci AS numero_contrato,
                credor           COLLATE utf8mb4_general_ci AS credor,
                dea,
                reajuste,
                ficha_financeira COLLATE utf8mb4_general_ci AS ficha_financeira,
                priorizacao      COLLATE utf8mb4_general_ci AS priorizacao,
                created_at,
                janeiro,
                fevereiro,
                marco,
                abril,
                maio,
                junho,
                julho,
                agosto,
                setembro,
                outubro,
                novembro,
                dezembro
            FROM contrato_modificado

            UNION ALL

            SELECT
                id,
                codigo_poa       COLLATE utf8mb4_general_ci AS codigo_poa,
                usuario_cehab    COLLATE utf8mb4_general_ci AS usuario_cehab,
                setor            COLLATE utf8mb4_general_ci AS setor,
                objeto           COLLATE utf8mb4_general_ci AS objeto,
                status_contrato  COLLATE utf8mb4_general_ci AS status_contrato,
                numero_contrato  COLLATE utf8mb4_general_ci AS numero_contrato,
                credor           COLLATE utf8mb4_general_ci AS credor,
                dea,
                reajuste,
                ficha_financeira COLLATE utf8mb4_general_ci AS ficha_financeira,
                priorizacao      COLLATE utf8mb4_general_ci AS priorizacao,
                created_at,
                janeiro,
                fevereiro,
                marco,
                abril,
                maio,
                junho,
                julho,
                agosto,
                setembro,
                outubro,
                novembro,
                dezembro
            FROM novo_contrato

        ) base

        WHERE $where

        ORDER BY created_at DESC, id DESC
    ";

$sumSql = "
    SELECT
        COALESCE(SUM(janeiro),   0) AS jan,
        COALESCE(SUM(fevereiro), 0) AS fev,
        COALESCE(SUM(marco),     0) AS mar,
        COALESCE(SUM(abril),     0) AS abr,
        COALESCE(SUM(maio),      0) AS mai,
        COALESCE(SUM(junho),     0) AS jun,
        COALESCE(SUM(julho),     0) AS jul,
        COALESCE(SUM(agosto),    0) AS ago,
        COALESCE(SUM(setembro),  0) AS set_,
        COALESCE(SUM(outubro),   0) AS out_,
        COALESCE(SUM(novembro),  0) AS nov,
        COALESCE(SUM(dezembro),  0) AS dez
    FROM (

        SELECT
            usuario_cehab COLLATE utf8mb4_general_ci AS usuario_cehab,
            setor COLLATE utf8mb4_general_ci AS setor,
            numero_contrato COLLATE utf8mb4_general_ci AS numero_contrato,
            credor COLLATE utf8mb4_general_ci AS credor,
            objeto COLLATE utf8mb4_general_ci AS objeto,
            created_at,
            janeiro,
            fevereiro,
            marco,
            abril,
            maio,
            junho,
            julho,
            agosto,
            setembro,
            outubro,
            novembro,
            dezembro
        FROM contrato_modificado

        UNION ALL

        SELECT
            usuario_cehab COLLATE utf8mb4_general_ci AS usuario_cehab,
            setor COLLATE utf8mb4_general_ci AS setor,
            numero_contrato COLLATE utf8mb4_general_ci AS numero_contrato,
            credor COLLATE utf8mb4_general_ci AS credor,
            objeto COLLATE utf8mb4_general_ci AS objeto,
            created_at,
            janeiro,
            fevereiro,
            marco,
            abril,
            maio,
            junho,
            julho,
            agosto,
            setembro,
            outubro,
            novembro,
            dezembro
        FROM novo_contrato

    ) base

    WHERE $where
";

} else {

  $sql = "
    SELECT
      id,
      codigo_poa,
      usuario_cehab,
      objeto,
      status_contrato,
      numero_contrato,
      credor,
      dea,
      reajuste,
      ficha_financeira,
      priorizacao,
      created_at,
      janeiro, fevereiro, marco, abril, maio, junho, julho, agosto, setembro, outubro, novembro, dezembro
    FROM novo_contrato
    WHERE $where
    ORDER BY created_at DESC, id DESC
  ";

  $sumSql = "
    SELECT
      COALESCE(SUM(janeiro),   0) AS jan,
      COALESCE(SUM(fevereiro), 0) AS fev,
      COALESCE(SUM(marco),     0) AS mar,
      COALESCE(SUM(abril),     0) AS abr,
      COALESCE(SUM(maio),      0) AS mai,
      COALESCE(SUM(junho),     0) AS jun,
      COALESCE(SUM(julho),     0) AS jul,
      COALESCE(SUM(agosto),    0) AS ago,
      COALESCE(SUM(setembro),  0) AS set_,
      COALESCE(SUM(outubro),   0) AS out_,
      COALESCE(SUM(novembro),  0) AS nov,
      COALESCE(SUM(dezembro),  0) AS dez
    FROM novo_contrato
    WHERE $where
  ";
}


$stmt = $poa->prepare($sql);
if ($stmt === false) {
  die('Erro ao preparar consulta: ' . $poa->error);
}
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res        = $stmt->get_result();
$contratos  = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$stmtSum = $poa->prepare($sumSql);
if ($params) {
  $stmtSum->bind_param($types, ...$params);
}
$stmtSum->execute();
$resSum   = $stmtSum->get_result();
$totMeses = $resSum ? $resSum->fetch_assoc() : [
  'jan'=>0,'fev'=>0,'mar'=>0,'abr'=>0,'mai'=>0,'jun'=>0,
  'jul'=>0,'ago'=>0,'set_'=>0,'out_'=>0,'nov'=>0,'dez'=>0
];

// soma geral dos meses (soma da coluna Total)
$totalGeralMeses  = array_sum($totMeses);
$totalColunaTotal = (float)$totalGeralMeses;

// --------- GRAVA NAS TABELAS valores_acumulados E soma_total ---------
$mapMeses = [
  'JAN' => 'jan',
  'FEV' => 'fev',
  'MAR' => 'mar',
  'ABR' => 'abr',
  'MAI' => 'mai',
  'JUN' => 'jun',
  'JUL' => 'jul',
  'AGO' => 'ago',
  'SET' => 'set_',
  'OUT' => 'out_',
  'NOV' => 'nov',
  'DEZ' => 'dez',
];

// upsert por mês
$stmtUp = $poa->prepare("
  INSERT INTO valores_acumulados (mes, soma_meses)
  VALUES (?, ?)
  ON DUPLICATE KEY UPDATE soma_meses = VALUES(soma_meses)
");
foreach ($mapMeses as $rotulo => $campo) {
  $valor = (float)($totMeses[$campo] ?? 0);
  $stmtUp->bind_param('sd', $rotulo, $valor);
  $stmtUp->execute();
}

// grava total geral
$stmtTot = $poa->prepare("
  INSERT INTO soma_total (id, soma_total)
  VALUES (1, ?)
  ON DUPLICATE KEY UPDATE soma_total = VALUES(soma_total)
");
$stmtTot->bind_param('d', $totalGeralMeses);
$stmtTot->execute();


// helpers
function bool_ptbr_view($v) {
  if (is_null($v)) return '';
  return (int)$v === 1 ? 'sim' : 'não';
}

function brl_or_empty($v) {
  if ($v === null) return '';
  if ((float)$v == 0.0) return ''; // se for 0, não mostra nada
  return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function brl_val($v) {
  return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function h($v) {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// campos numéricos dos meses (para somar o total de cada contrato)
$MES_CAMPOS = [
  'janeiro','fevereiro','marco','abril','maio','junho',
  'julho','agosto','setembro','outubro','novembro','dezembro'
];

function nome_curto($nomeCompleto) {
    $nomeCompleto = trim((string)$nomeCompleto);

    if ($nomeCompleto === '') {
        return '';
    }

    $partes = preg_split('/\s+/', $nomeCompleto);

    // Se tiver apenas um nome
    if (count($partes) === 1) {
        return $partes[0];
    }

    // Primeiro nome + segundo nome
    return $partes[0] . ' ' . $partes[1];
}
?>

<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>POA - Planejamento Orçamentário Anual</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --bg: #f6f8fb;
    }
    html, body { height: 100%; }
    body {
      background: var(--bg);
      font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
    }
  </style>
</head>
<body class="min-h-screen">
  <!-- Topbar -->
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
  <!-- Topbar -->
<header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

        <!-- ESQUERDA / MARCA -->
        <div class="flex items-center gap-3">

            <div class="grid place-items-center w-9 h-9 rounded-xl bg-blue-600 text-white shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="currentColor"
                     class="w-5 h-5">
                    <path d="M3 21h18v-2H3v2Zm14-4h2V3h-6v4H5v10h2v-8h4v8h2v-4h4v4Zm-4-6V5h2v6h-2Z"/>
                </svg>
            </div>

            <div class="flex flex-col">
                <span class="text-slate-900 font-semibold leading-none">
                    POA - Planejamento Orçamentário Anual
                </span>

                <span class="text-xs text-slate-500 leading-none">
                    Painel inicial
                </span>
            </div>

            <?php if ($isBruno): ?>
                <button
                    id="btnBell"
                    class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50"
                    title="Notificações">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="currentColor"
                         class="w-5 h-5">
                        <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 1 0-14 0v5l-2 2v1h18v-1l-2-2Z"/>
                    </svg>

                    <?php if ($notifCount > 0): ?>
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[11px] leading-[18px] text-center font-semibold">
                            <?= $notifCount > 99 ? '99+' : $notifCount ?>
                        </span>
                    <?php endif; ?>

                </button>
            <?php endif; ?>

        </div>


        <!-- DIREITA / AÇÕES -->
        <div class="flex items-center gap-3">

            <?php if ($isBruno): ?>

                <!-- Arquivados -->
                <a href="notificacoes_arquivadas.php"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                   title="Ver notificações arquivadas">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24"
                         fill="currentColor"
                         class="w-4 h-4">
                        <path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2H3V7Zm0 4h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8Zm6 2v2h6v-2H9Z"/>
                    </svg>

                    Arquivados
                </a>

            <?php endif; ?>


            <!-- Baixar dados -->
            <a href="baixar_dados.php"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-white text-sm font-medium shadow hover:bg-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
               title="Baixar dados">

                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="currentColor"
                     class="w-4 h-4">
                    <path d="M12 16l5-5h-3V4h-4v7H7l5 5Zm-7 2v2h14v-2H5Z"/>
                </svg>

                Baixar Dados
            </a>


            <!-- Novo Contrato -->
            <a href="formulario.php"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-white text-sm font-medium shadow hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">

                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="currentColor"
                     class="w-4 h-4">
                    <path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2h5Z"/>
                </svg>

                Novo Contrato
            </a>


            <!-- Sair -->
            <a href="sair.php"
               class="inline-flex items-center gap-2 rounded-xl border border-red-300 px-4 py-2 text-red-600 bg-white text-sm font-medium hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
               title="Encerrar sessão">

                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="currentColor"
                     class="w-4 h-4">
                    <path d="M16 13v-2H7V8l-5 4 5 4v-3h9Zm3-10H8a2 2 0 0 0-2 2v3h2V5h11v14H8v-3H6v3a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z"/>
                </svg>

                Sair
            </a>


            <!-- Ajuda -->
            <a href="suporte.php"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
               title="Ajuda / Suporte">

                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     class="w-4 h-4">
                    <path d="M4 12a8 8 0 0 1 16 0"/>
                    <path d="M4 12v3a3 3 0 0 0 3 3h1"/>
                    <path d="M20 12v3a3 3 0 0 1-3 3h-1"/>
                    <rect x="3" y="11" width="3" height="5" rx="1"/>
                    <rect x="18" y="11" width="3" height="5" rx="1"/>
                </svg>

                <span>Ajuda</span>
            </a>

        </div>

    </div>
</header>

  <!-- Content -->
  <main class="mx-auto w-full max-w-[2100px] px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- CAIXA 1: CONTRATOS EM ANDAMENTO -->
    <section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
      <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
        <div class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-slate-500"><path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z"/></svg>
          <span class="font-medium">Olá, seja bem-vindo:</span><strong><?= htmlspecialchars($nomeUsuario) ?></strong>
        </div>
      </div>

      <h2 class="mt-4 text-lg sm:text-xl font-semibold text-slate-900">Contratos em Andamento</h2>

      <!-- Filtro de busca -->
      <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-slate-400"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5ZM9.5 14C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
          </span>
          <input
            id="busca"
            type="text"
            class="w-full rounded-xl border border-slate-300 bg-white pl-11 pr-3 py-2.5 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Digite o nº do contrato/processo ou parte do credor"
            value="<?= htmlspecialchars($busca) ?>"
          >
        </div>
        <div class="flex gap-2">
          <button id="btnPesquisar" class="inline-flex items-center justify-center rounded-xl bg-blue-600 text-white text-sm font-medium px-4 py-2.5 shadow hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Pesquisar</button>
          <button id="btnLimpar" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-medium px-4 py-2.5 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">Limpar</button>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-end gap-3">

    <!-- TOTAL -->
    <div class="inline-flex items-center gap-3 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm">
        <span class="text-slate-600 font-medium">
            Total:
        </span>

        <span class="text-slate-900 font-semibold">
            <?= brl_val($totalGeralMeses) ?>
        </span>
    </div>

        <?php if ($isBruno): ?>

    <div>
        <label for="filtroDiretoria"
              class="block mb-1 text-xs font-medium text-slate-500">
            Diretoria
        </label>

        <select
            id="filtroDiretoria"
            class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm
                  text-slate-700 focus:outline-none focus:ring-2
                  focus:ring-blue-500 focus:border-blue-500">

            <option value="">Todas</option>

            <?php foreach ($diretoriasDisponiveis as $sigla): ?>

                <option
                    value="<?= h($sigla) ?>"
                    <?= $diretoriaFiltro === $sigla ? 'selected' : '' ?>
                >
                    <?= h($sigla) ?>
                </option>

            <?php endforeach; ?>

        </select>
    </div>

    <?php endif; ?>
        <!-- FILTRAR -->
        <button
            type="button"
            id="btnFiltrarPeriodo"
            class="inline-flex items-center justify-center rounded-xl
                  bg-blue-600 text-white text-sm font-medium
                  px-4 py-2.5 shadow hover:bg-blue-700
                  focus:outline-none focus-visible:ring-2
                  focus-visible:ring-blue-500">

            Filtrar

        </button>


        <!-- LIMPAR PERÍODO -->
        <?php if ($isBruno && $diretoriaFiltro !== ''): ?>

            <button
                type="button"
                id="btnLimparPeriodo"
                class="inline-flex items-center justify-center rounded-xl
                      border border-slate-300 bg-white text-slate-700
                      text-sm font-medium px-4 py-2.5
                      hover:bg-slate-50">
                Limpar filtro
            </button>
        <?php endif; ?>
    </div>

      <!-- Tabela / Planilha -->
      <?php if (empty($contratos)): ?>
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-8 text-center">
          <p class="text-slate-600 text-sm">
            Nenhum contrato listado no momento.
            Use <span class="font-semibold">Pesquisar</span> ou clique em
            <span class="font-semibold">+ Novo Contrato</span> para cadastrar.
          </p>
        </div>
      <?php else: ?>
        <div class="mt-6 border border-slate-200 rounded-xl overflow-hidden">
          <div class="max-h-[900px] overflow-y-auto overflow-x-auto">
            <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
            <tr class="text-left text-xs font-semibold text-slate-600 uppercase">
              <th class="px-3 py-2 border-b border-slate-200">CÓDIGO POA</th>
              <th class="px-3 py-2 border-b border-slate-200">Responsável</th> 
              <th class="px-3 py-2 border-b border-slate-200">Objeto/Atividade</th>
              <th class="px-3 py-2 border-b border-slate-200">Status</th>
              <th class="px-3 py-2 border-b border-slate-200">Nº do Contrato</th>
              <th class="px-3 py-2 border-b border-slate-200">Credor</th>
              <th class="px-3 py-2 border-b border-slate-200">DEA</th>
              <th class="px-3 py-2 border-b border-slate-200">Reajuste</th>
              <th class="px-3 py-2 border-b border-slate-200">Ficha Financeira</th>
              <th class="px-3 py-2 border-b border-slate-200">Grau de Priorização</th>
              <th class="px-3 py-2 border-b border-slate-200">Total</th>
              <th class="px-3 py-2 border-b border-slate-200">Data de Criação</th>
              <th class="px-3 py-2 border-b border-slate-200 text-center">Ações</th>
            </tr>
          </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($contratos as $c): ?>
                  <?php
                    $dataCriacao = '';
                    if (!empty($c['created_at'])) {
                        $dt = new DateTime($c['created_at']);
                        $dataCriacao = $dt->format('Y-m-d');
                    }

                    // NOVO: soma dos meses deste contrato
                    $totalContrato = 0.0;
                    foreach ($MES_CAMPOS as $campoMes) {
                      $totalContrato += (float)($c[$campoMes] ?? 0);
                    }
                  ?>
                  <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2 text-sky-700 font-semibold whitespace-nowrap">
                      <?= htmlspecialchars($c['codigo_poa'] ?: $c['id']) ?>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= htmlspecialchars(nome_curto($c['usuario_cehab'] ?? '')) ?>
                    </td>
                    <td class="px-3 py-2 max-w-xs truncate" title="<?= htmlspecialchars($c['objeto']) ?>">
                      <?= htmlspecialchars($c['objeto']) ?>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= htmlspecialchars($c['status_contrato']) ?>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= htmlspecialchars($c['numero_contrato']) ?>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= htmlspecialchars($c['credor']) ?>
                    </td>
                    <td class="px-3 py-2 text-center">
                      <?= bool_ptbr_view($c['dea']) ?>
                    </td>
                    <td class="px-3 py-2 text-center">
                      <?= bool_ptbr_view($c['reajuste']) ?>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= h($c['ficha_financeira']) ?>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= htmlspecialchars($c['priorizacao']) ?>
                    </td>

                    <!-- NOVO: coluna Total (deixa vazio se for 0) -->
                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= $totalContrato > 0 ? brl_val($totalContrato) : '' ?>
                    </td>

                    <td class="px-3 py-2 whitespace-nowrap">
                      <?= $dataCriacao ?>
                    </td>

                    <!-- AÇÕES -->
                    <td class="px-3 py-2 whitespace-nowrap">
                      <div class="flex items-center justify-center gap-2 text-slate-400">
                        <!-- Visualizar -->
                        <a href="visualizar_contrato.php?id=<?= (int)$c['id'] ?>"
                           class="hover:text-sky-600"
                           title="Visualizar">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                               fill="none" stroke="currentColor" stroke-width="2"
                               stroke-linecap="round" stroke-linejoin="round"
                               class="w-4 h-4">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                            <circle cx="12" cy="12" r="3"/>
                          </svg>
                        </a>

                        <!-- Editar -->
                        <a href="editar_contrato.php?id=<?= (int)$c['id'] ?>"
                           class="hover:text-amber-500"
                           title="Editar">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                               fill="none" stroke="currentColor" stroke-width="2"
                               stroke-linecap="round" stroke-linejoin="round"
                               class="w-4 h-4">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                          </svg>
                        </a>

                        <!-- Excluir -->
                        <button type="button"
                                class="hover:text-red-500"
                                title="Excluir"
                                onclick="confirmDelete(<?= (int)$c['id'] ?>)">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                               fill="none" stroke="currentColor" stroke-width="2"
                               stroke-linecap="round" stroke-linejoin="round"
                               class="w-4 h-4">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                            <path d="M10 11v6"/>
                            <path d="M14 11v6"/>
                            <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </section>

  </main>

<?php if ($isBruno): ?>
  <div id="notifModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
        <h3 class="text-slate-900 font-semibold">Alterações recentes</h3>
        <button id="btnCloseNotif" class="text-slate-500 hover:text-slate-700">✕</button>
      </div>

      <div class="max-h-[70vh] overflow-y-auto p-4 space-y-3">
        <?php if (empty($notifs)): ?>
          <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600">
            Nenhuma notificação ainda.
          </div>
        <?php else: ?>
          <?php foreach ($notifs as $n): ?>
            <?php
              $dt = !empty($n['created_at']) ? (new DateTime($n['created_at']))->format('d/m/Y H:i') : '';
              $changes = [];
              if (!empty($n['changes_json'])) {
                $tmp = json_decode($n['changes_json'], true);
                if (is_array($tmp)) $changes = $tmp;
              }

              // Montagem do título
              if (!empty($n['codigo_poa'])) {
                $titulo = 'Código POA: ' . $n['codigo_poa'];
              } else {
                $titulo = 'ID ' . $n['contrato_id'];
              }

              $numContrato = !empty($n['numero_contrato']) 
                  ? 'Nº Contrato: ' . $n['numero_contrato'] 
                  : '';
            ?>

            <div class="rounded-xl border border-slate-200 p-4 <?= ((int)$n['lida']===0 ? 'bg-amber-50/40' : 'bg-white') ?>">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900">
                    <?= h($titulo) ?> <?= $numContrato ? ('• ' . h($numContrato)) : '' ?>
                  </div>
                  <div class="text-xs text-slate-600 mt-1">
                    <?= h($dt) ?> • por <strong><?= h($n['editor_nome']) ?></strong> (<?= h($n['editor_login']) ?>)
                    <?= ((int)$n['lida']===0 ? ' • <span class="text-red-600 font-semibold">NOVA</span>' : '') ?>
                  </div>
                </div>
              </div>

              <div class="mt-3 text-sm text-slate-700 space-y-2">
                <?php foreach ($changes as $c): ?>
                  <div class="rounded-lg bg-white border border-slate-200 px-3 py-2">
                    <div class="text-xs text-slate-500 font-semibold uppercase"><?= h($c['campo'] ?? '') ?></div>
                    <div class="text-sm">
                      <span class="text-slate-500">antes:</span> <?= h($c['antes'] ?? '') ?>
                      <span class="mx-2 text-slate-300">→</span>
                      <span class="text-slate-500">depois:</span> <?= h($c['depois'] ?? '') ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="px-5 py-4 border-t border-slate-200 flex items-center justify-end gap-2">
        <button id="btnMarkRead"
          class="rounded-xl bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
          Marcar tudo como lido
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>

</body>

<script>
document.addEventListener('DOMContentLoaded', () => {

// ==================== FILTRO POR DIRETORIA ====================

document.getElementById('btnFiltrarPeriodo')?.addEventListener('click', () => {

    const diretoria =
        document.getElementById('filtroDiretoria')?.value || '';

    const url = new URL(window.location.href);

    if (diretoria) {
        url.searchParams.set('diretoria', diretoria);
    } else {
        url.searchParams.delete('diretoria');
    }

    // Remove filtros antigos, caso ainda estejam na URL
    url.searchParams.delete('mes');
    url.searchParams.delete('ano');

    window.location.href = url.toString();
});


// ==================== LIMPAR DIRETORIA ====================

document.getElementById('btnLimparPeriodo')?.addEventListener('click', () => {

    const url = new URL(window.location.href);

    url.searchParams.delete('diretoria');
    url.searchParams.delete('mes');
    url.searchParams.delete('ano');

    window.location.href = url.toString();
});

// ==================== LIMPAR FILTROS ====================

document.getElementById('btnLimparPeriodo')?.addEventListener('click', () => {

    const url = new URL(window.location.href);

    // Remove todos os filtros
    url.searchParams.delete('mes');
    url.searchParams.delete('ano');
    url.searchParams.delete('diretoria');

    window.location.href = url.toString();
});


  const btnBell = document.getElementById('btnBell');
  const modal = document.getElementById('notifModal');
  const btnClose = document.getElementById('btnCloseNotif');
  const btnMarkRead = document.getElementById('btnMarkRead');

  function openModal() {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  btnBell?.addEventListener('click', openModal);
  btnClose?.addEventListener('click', closeModal);

  modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  btnMarkRead?.addEventListener('click', async () => {
    try {
      const r = await fetch('marcar_notificacoes_lidas.php', { method: 'POST' });
      const j = await r.json();
      if (j.ok) window.location.reload();
    } catch (e) {
      alert('Erro ao marcar como lido.');
    }
  });

  document.getElementById('btnLimpar')?.addEventListener('click', () => {
    window.location.href = 'home.php';
  });

  document.getElementById('btnPesquisar')?.addEventListener('click', () => {
    const i = document.getElementById('busca');
    const q = i ? i.value.trim() : '';
    const url = new URL(window.location.href);
    if (q) url.searchParams.set('q', q);
    else url.searchParams.delete('q');
    window.location.href = url.toString();
  });

  document.getElementById('busca')?.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') document.getElementById('btnPesquisar')?.click();
  });

  // ✅ Dropdown Relatórios (AGORA dentro do DOMContentLoaded)
  const btnRelatorios = document.getElementById('btnRelatorios');
  const menuRelatorios = document.getElementById('menuRelatorios');
  const relatoriosWrap = document.getElementById('relatoriosWrap');

  function closeRelatoriosMenu() {
    menuRelatorios?.classList.add('hidden');
    btnRelatorios?.setAttribute('aria-expanded', 'false');
  }

  btnRelatorios?.addEventListener('click', (e) => {
    e.stopPropagation();
    const isHidden = menuRelatorios.classList.contains('hidden');
    if (isHidden) {
      menuRelatorios.classList.remove('hidden');
      btnRelatorios.setAttribute('aria-expanded', 'true');
    } else {
      closeRelatoriosMenu();
    }
  });

  document.addEventListener('click', (e) => {
    if (!relatoriosWrap?.contains(e.target)) closeRelatoriosMenu();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeRelatoriosMenu();
  });
});

function confirmDelete(id) {
  if (confirm('Deseja realmente excluir este contrato?')) {
    window.location.href = 'excluir_contrato.php?id=' + encodeURIComponent(id);
  }
}
</script>


</html>
