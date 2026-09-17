<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo "Sessão não iniciada.";
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
  echo "Sem permissão.";
  exit;
}

require_once __DIR__ . '/../config.php';

$r = $poa->query("
  SELECT n.*, c.codigo_poa, c.numero_contrato
  FROM notificacoes_edicao n
  LEFT JOIN novo_contrato c ON c.id = n.contrato_id
  WHERE n.lida = 1
  ORDER BY COALESCE(n.lida_em, n.created_at) DESC
  LIMIT 200
");
$arquivadas = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Arquivados - Notificações</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="font-semibold text-slate-900">Arquivados</div>
      <a href="home.php" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Voltar
      </a>
    </div>
  </header>

  <main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6">
    <?php if (empty($arquivadas)): ?>
      <div class="rounded-xl bg-white border border-slate-200 p-4 text-sm text-slate-600">
        Nenhuma notificação arquivada ainda.
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($arquivadas as $n): ?>
          <?php
            $dt = !empty($n['created_at']) ? (new DateTime($n['created_at']))->format('d/m/Y H:i') : '';
            $dtLida = !empty($n['lida_em']) ? (new DateTime($n['lida_em']))->format('d/m/Y H:i') : '';
            $changes = [];
            if (!empty($n['changes_json'])) {
              $tmp = json_decode($n['changes_json'], true);
              if (is_array($tmp)) $changes = $tmp;
            }

            $titulo = !empty($n['codigo_poa']) ? ('Código POA: ' . $n['codigo_poa']) : ('ID ' . $n['contrato_id']);
            $numContrato = !empty($n['numero_contrato']) ? ('Nº Contrato: ' . $n['numero_contrato']) : '';
          ?>
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-sm font-semibold text-slate-900">
              <?= h($titulo) ?> <?= $numContrato ? ('• ' . h($numContrato)) : '' ?>
            </div>
            <div class="text-xs text-slate-600 mt-1">
              Criada: <?= h($dt) ?><?= $dtLida ? (' • Lida: ' . h($dtLida)) : '' ?>
              • por <strong><?= h($n['editor_nome']) ?></strong> (<?= h($n['editor_login']) ?>)
            </div>

            <?php if (!empty($changes)): ?>
              <div class="mt-3 text-sm text-slate-700 space-y-2">
                <?php foreach ($changes as $c): ?>
                  <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <div class="text-xs text-slate-500 font-semibold uppercase"><?= h($c['campo'] ?? '') ?></div>
                    <div class="text-sm">
                      <span class="text-slate-500">antes:</span> <?= h($c['antes'] ?? '') ?>
                      <span class="mx-2 text-slate-300">→</span>
                      <span class="text-slate-500">depois:</span> <?= h($c['depois'] ?? '') ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
