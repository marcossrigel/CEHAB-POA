<?php
// templates/sair.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// limpa dados da sessão
$_SESSION = [];

// expira o cookie da sessão (boa prática)
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// destrói a sessão
session_destroy();

// redireciona para a GETIC
$destino = 'https://www.getic.pe.gov.br/?p=home';
if (!headers_sent()) {
  header("Location: $destino");
  exit;
}
?>
<!doctype html>
<html lang="pt-br">
<meta charset="utf-8">
<meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($destino) ?>">
<body>
  <p>Redirecionando… <a href="<?= htmlspecialchars($destino) ?>">clique aqui</a>.</p>
</body>
</html>
