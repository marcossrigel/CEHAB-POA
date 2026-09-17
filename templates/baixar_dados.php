<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit('Sessão não iniciada.');
}

require_once __DIR__ . '/../referencia.php';

$nomeUsuario  = trim($_SESSION['usuario']['nome'] ?? '');
$loginUsuario = trim($_SESSION['usuario']['login'] ?? '');
$cargoUsuario = trim($_SESSION['usuario']['cargo'] ?? '');

$nomeCheck  = mb_strtolower($nomeUsuario, 'UTF-8');
$loginCheck = mb_strtolower($loginUsuario, 'UTF-8');
$cargoCheck = mb_strtolower($cargoUsuario, 'UTF-8');


// ==========================================================
// ADMINISTRADORES
// ==========================================================

$isAdmin = (
    $cargoCheck === 'gestor'
    || $loginCheck === 'bruno.passavante'
    || $nomeCheck === 'bruno passavante de oliveira'
    || $nomeCheck === 'marcos rigel silvestre da silva'
);


// ==========================================================
// MONTA CONSULTA
// ==========================================================

if ($isAdmin) {

    // Marcos e Bruno:
    // baixam TODOS os registros de novo_contrato

    $sql = "
        SELECT *
        FROM novo_contrato
        ORDER BY created_at DESC, id DESC
    ";

    $stmt = $poa->prepare($sql);

} else {

    // ======================================================
    // USUÁRIO NORMAL
    //
    // Descobre o setor_local dele no cehab_online
    // ======================================================

    $stmtSetor = $cehab->prepare("
        SELECT setor_local
        FROM users
        WHERE u_nome_completo = ?
        LIMIT 1
    ");

    $stmtSetor->bind_param('s', $nomeUsuario);
    $stmtSetor->execute();

    $resSetor = $stmtSetor->get_result();

    $setorUsuario = '';

    if ($row = $resSetor->fetch_assoc()) {
        $setorUsuario = trim($row['setor_local'] ?? '');
    }

    $stmtSetor->close();


    // ======================================================
    // SEM SETOR_LOCAL
    //
    // Por segurança, baixa apenas os próprios registros.
    // ======================================================

    if ($setorUsuario === '') {

        $sql = "
            SELECT *
            FROM novo_contrato
            WHERE usuario_cehab = ?
            ORDER BY created_at DESC, id DESC
        ";

        $stmt = $poa->prepare($sql);

        $stmt->bind_param(
            's',
            $nomeUsuario
        );

    } else {

        // ==================================================
        // DESCOBRE TODOS OS USUÁRIOS DO MESMO SETOR
        // ==================================================

        $usuariosDoSetor = [];

        $stmtUsuarios = $cehab->prepare("
            SELECT u_nome_completo
            FROM users
            WHERE setor_local = ?
              AND u_nome_completo IS NOT NULL
              AND TRIM(u_nome_completo) <> ''
        ");

        $stmtUsuarios->bind_param(
            's',
            $setorUsuario
        );

        $stmtUsuarios->execute();

        $resUsuarios = $stmtUsuarios->get_result();

        while ($row = $resUsuarios->fetch_assoc()) {

            $nome = trim(
                $row['u_nome_completo'] ?? ''
            );

            if ($nome !== '') {
                $usuariosDoSetor[] = $nome;
            }
        }

        $stmtUsuarios->close();


        // ==================================================
        // BUSCA OS CONTRATOS DESSES USUÁRIOS
        // ==================================================

        if (!empty($usuariosDoSetor)) {

            $placeholders = implode(
                ',',
                array_fill(
                    0,
                    count($usuariosDoSetor),
                    '?'
                )
            );

            $sql = "
                SELECT *
                FROM novo_contrato
                WHERE usuario_cehab IN ($placeholders)
                ORDER BY created_at DESC, id DESC
            ";

            $stmt = $poa->prepare($sql);

            $types = str_repeat(
                's',
                count($usuariosDoSetor)
            );

            $stmt->bind_param(
                $types,
                ...$usuariosDoSetor
            );

        } else {

            // Caso estranho:
            // setor existe, mas nenhum usuário foi encontrado.
            // Retorna somente os próprios registros.

            $sql = "
                SELECT *
                FROM novo_contrato
                WHERE usuario_cehab = ?
                ORDER BY created_at DESC, id DESC
            ";

            $stmt = $poa->prepare($sql);

            $stmt->bind_param(
                's',
                $nomeUsuario
            );
        }
    }
}


// ==========================================================
// EXECUTA
// ==========================================================

$stmt->execute();

$resultado = $stmt->get_result();


// ==========================================================
// GERA CSV
// ==========================================================

$nomeArquivo =
    'POA_' .
    date('Y-m-d_H-i-s') .
    '.csv';

header('Content-Type: text/csv; charset=UTF-8');

header(
    'Content-Disposition: attachment; filename="' .
    $nomeArquivo .
    '"'
);


// BOM UTF-8
// Ajuda o Excel a reconhecer acentos corretamente.
echo "\xEF\xBB\xBF";

$saida = fopen(
    'php://output',
    'w'
);


// ==========================================================
// CABEÇALHO
// ==========================================================

$primeiraLinha = $resultado->fetch_assoc();

if (!$primeiraLinha) {

    fputcsv(
        $saida,
        ['Nenhum registro encontrado'],
        ';'
    );

    fclose($saida);
    exit;
}


// Nomes das colunas
fputcsv(
    $saida,
    array_keys($primeiraLinha),
    ';'
);


// ==========================================================
// DADOS
// ==========================================================

fputcsv(
    $saida,
    array_values($primeiraLinha),
    ';'
);

while ($linha = $resultado->fetch_assoc()) {

    fputcsv(
        $saida,
        array_values($linha),
        ';'
    );
}


fclose($saida);

$stmt->close();

exit;