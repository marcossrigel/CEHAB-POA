<?php

require_once __DIR__ . '/config.php';

$caminhoConfig = 'C:\Users\marcos.rigel.CEHABPE\Documents\configuração\config.php';

if (!file_exists($caminhoConfig)) {
    die('Arquivo de configuração não encontrado.');
}

require_once $caminhoConfig;