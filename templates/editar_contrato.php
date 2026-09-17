<?php

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['usuario'])) {
  http_response_code(401);
  echo "Sessão não iniciada. Acesse via login.";
  exit;
}

require_once __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  die('ID de contrato inválido.');
}

// busca contrato
$stmt = $poa->prepare("SELECT * FROM novo_contrato WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$contrato = $res ? $res->fetch_assoc() : null;

if (!$contrato) {
  die('Contrato não encontrado.');
}

$nomeUsuario = $_SESSION['usuario']['nome'] ?? 'usuário';

// helpers -----------------------------------------------------------
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function brl_input($v) {
  if ($v === null || $v === '') return 'R$ 0,00';
  $num = brl_to_float($v);
  return 'R$ ' . number_format($num, 2, ',', '.');
}

function selected_val($atual, $opcao) {
  return (trim((string)$atual) === trim((string)$opcao)) ? 'selected' : '';
}

function brl_to_float($v) {
  if ($v === null) return 0.0;

  $v = trim((string)$v);
  if ($v === '') return 0.0;

  // remove R$, espaços e deixa só dígitos, ponto, vírgula e sinal
  $v = str_replace(['R$', ' '], '', $v);
  $v = preg_replace('/[^\d\.,-]/', '', $v);

  // Se tem vírgula, assume pt-BR (1.234,56)
  if (strpos($v, ',') !== false) {
    $v = str_replace('.', '', $v);   // remove milhar
    $v = str_replace(',', '.', $v);  // vírgula vira decimal
    return (float)$v;
  }

  // Se NÃO tem vírgula, assume formato do banco (1234.56) ou número simples
  return (float)$v;
}

function selected_bool($tiny, $label) {
  if ($tiny === null || $tiny === '') return '';
  $tiny = (int)$tiny;
  $label = mb_strtolower($label, 'UTF-8');
  if ($tiny === 1 && $label === 'sim') return 'selected';
  if ($tiny === 0 && ($label === 'não' || $label === 'nao')) return 'selected';
  return '';
}

$vigenciaStr = $contrato['vigencia_fim'] ?? '';
// mapeamento meses
$mesLabels = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];
$mesCampos = ['janeiro','fevereiro','marco','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];

$totalMeses = 0.0;
foreach ($mesCampos as $c) {
  $totalMeses += brl_to_float($contrato[$c] ?? 0);
}

?>

<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar Contrato • POA</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="../assets/css/formulario.css">
</head>

<body class="min-h-screen">
  <!-- Topbar -->
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-9 h-9 rounded-xl bg-blue-600 text-white shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M3 21h18v-2H3v2Zm14-4h2V3h-6v4H5v10h2v-8h4v8h2v-4h4v4Zm-4-6V5h2v6h-2Z"/></svg>
        </div>
        <div class="flex flex-col">
          <span class="text-slate-900 font-semibold leading-none">POA - Planejamento Orçamentário Anual</span>
          <span class="text-xs text-slate-500 leading-none">Editar Contrato</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a href="home.php" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Voltar</a>
      </div>
    </div>
  </header>

  <!-- Form container -->
  <main class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-8">

    <?php if (isset($_GET['ok']) && $_GET['ok'] == '1'): ?>
      <!-- Modal de sucesso -->
      <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-center space-y-4">
          <h2 class="text-lg font-semibold text-slate-900">Contrato atualizado!</h2>
          <p class="text-sm text-slate-600">
            As informações do contrato foram salvas com sucesso.
          </p>
          <div class="flex justify-center gap-3 pt-2">
            <a href="home.php"
               class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
              Voltar para a lista
            </a>
            <a href="editar_contrato.php?id=<?= (int)$id ?>"
               class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
              Continuar editando
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>


    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-8"
          action="atualizar_contrato.php"
          method="post"
          id="formContrato">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slate-900">Formulário POA 2025 - Edição</h1>
        <span class="text-xs text-slate-500">Campos marcados com * são obrigatórios</span>
      </div>

      <section class="grid grid-cols-1 md:grid-cols-12 gap-5">
        <!-- L1: Tema de Custo | Setor | Gestor -->
        <div class="md:col-span-4">
          <label class="label" for="tema_custo">Tema de Custo *</label>
          <select id="tema_custo" name="tema_custo" class="input select" required>
            <option value="">Selecione uma opção</option>
            <option <?= selected_val($contrato['tema_custo'],'01 - Apoio Administrativo - Estagiários') ?>>01 - Apoio Administrativo - Estagiários</option>
            <option <?= selected_val($contrato['tema_custo'],'02 - Combustível/Manutenção de Veículos') ?>>02 - Combustível/Manutenção de Veículos</option>
            <option <?= selected_val($contrato['tema_custo'],'03 - Demandas Judiciais') ?>>03 - Demandas Judiciais</option>
            <option <?= selected_val($contrato['tema_custo'],'04 - Diárias Civil') ?>>04 - Diárias Civil</option>
            <option <?= selected_val($contrato['tema_custo'],'05 - Limpeza e Conservação') ?>>05 - Limpeza e Conservação</option>
            <option <?= selected_val($contrato['tema_custo'],'06 - Locação de Veículos') ?>>06 - Locação de Veículos</option>
            <option <?= selected_val($contrato['tema_custo'],'07 - Manutenção Predial') ?>>07 - Manutenção Predial</option>
            <option <?= selected_val($contrato['tema_custo'],'08 - Material de Expediente/Copa/limpeza/Gráfico') ?>>08 - Material de Expediente/Copa/limpeza/Gráfico</option>
            <option <?= selected_val($contrato['tema_custo'],'09 - Motoristas') ?>>09 - Motoristas</option>
            <option <?= selected_val($contrato['tema_custo'],'10 - Salário de Apenados') ?>>10 - Salário de Apenados</option>
            <option <?= selected_val($contrato['tema_custo'],'11 - Rede Digital Corporativa do Estado') ?>>11 - Rede Digital Corporativa do Estado</option>
            <option <?= selected_val($contrato['tema_custo'],'12 - Serviços de Portaria') ?>>12 - Serviços de Portaria</option>
            <option <?= selected_val($contrato['tema_custo'],'13 - Serviços de Informática') ?>>13 - Serviços de Informática</option>
            <option <?= selected_val($contrato['tema_custo'],'14 - Suprimento Individual') ?>>14 - Suprimento Individual</option>
            <option <?= selected_val($contrato['tema_custo'],'15 - Vigilência Ostensiva') ?>>15 - Vigilência Ostensiva</option>
            <option <?= selected_val($contrato['tema_custo'],'16 - Auxílio Moradia') ?>>16 - Auxílio Moradia</option>
            <option <?= selected_val($contrato['tema_custo'],'17 - Cota Global') ?>>17 - Cota Global</option>
            <option <?= selected_val($contrato['tema_custo'],'18 - Passagnes Aéreas') ?>>18 - Passagnes Aéreas</option>
            <option <?= selected_val($contrato['tema_custo'],'19 - Energia Elétrica') ?>>19 - Energia Elétrica</option>
            <option <?= selected_val($contrato['tema_custo'],'20 - Água e Esgoto') ?>>20 - Água e Esgoto</option>
            <option <?= selected_val($contrato['tema_custo'],'21 - Outros') ?>>21 - Outros</option>
            <option <?= selected_val($contrato['tema_custo'],'22 - Folha de Pessoal') ?>>22 - Folha de Pessoal</option>
            <option <?= selected_val($contrato['tema_custo'],'23 - FGTS') ?>>23 - FGTS</option>
            <option <?= selected_val($contrato['tema_custo'],'24 - INSS') ?>>24 - INSS</option>
            <option <?= selected_val($contrato['tema_custo'],'25 - Ressarcimento Pessoal à Disposição') ?>>25 - Ressarcimento Pessoal à Disposição</option>
            <option <?= selected_val($contrato['tema_custo'],'26 - Obras') ?>>26 - Obras</option>
            <option <?= selected_val($contrato['tema_custo'],'27 - Gerenciamento de Obras') ?>>27 - Gerenciamento de Obras</option>
            <option <?= selected_val($contrato['tema_custo'],'28 - Projetos de Obras') ?>>28 - Projetos de Obras</option>
            <option <?= selected_val($contrato['tema_custo'],'29 - Regularização Fundiária') ?>>29 - Regularização Fundiária</option>
            <option <?= selected_val($contrato['tema_custo'],'30 - Ouvidoria') ?>>30 - Ouvidoria</option>
            <option <?= selected_val($contrato['tema_custo'],'31 - FINHIS') ?>>31 - FINHIS</option>
            <option <?= selected_val($contrato['tema_custo'],'32 - Minha Casa Minha Vida') ?>>32 - Minha Casa Minha Vida</option>
            <option <?= selected_val($contrato['tema_custo'],'33 - Vale Transporte') ?>>33 - Vale Transporte</option>
            <option <?= selected_val($contrato['tema_custo'],'34 - Vale Alimentação') ?>>34 - Vale Alimentação</option>
            <option <?= selected_val($contrato['tema_custo'],'35 - Publicações Oficiais') ?>>35 - Publicações Oficiais</option>
            <option <?= selected_val($contrato['tema_custo'],'36 - Contribuições Patronais da CEHAB') ?>>36 - Contribuições Patronais da CEHAB</option>
            <option <?= selected_val($contrato['tema_custo'],'37 - Encargos com o PIS e o COFINS') ?>>37 - Encargos com o PIS e o COFINS</option>
            <option <?= selected_val($contrato['tema_custo'],'38 - Apoio Administrativo') ?>>38 - Apoio Administrativo</option>
            <option <?= selected_val($contrato['tema_custo'],'39 - Apoio Especializado') ?>>39 - Apoio Especializado</option>
            <option <?= selected_val($contrato['tema_custo'],'39 - Apoio Especializado') ?>>39 - Apoio Especializado</option>
            <option <?= selected_val($contrato['tema_custo'],'40 - Destaque') ?>>40 - Destaque</option>
          </select>
        </div>
        <div class="md:col-span-4">
          <label class="label" for="setor">Setor Responsável *</label>
          <select id="setor" name="setor" class="input select" required>
            <option value="">Selecione um setor</option>

            <option <?= selected_val($contrato['setor'],'DAF') ?>>DAF</option>
            <option <?= selected_val($contrato['setor'],'DOHDU') ?>>DOHDU</option>
            <option <?= selected_val($contrato['setor'],'DED') ?>>DED</option>
            <option <?= selected_val($contrato['setor'],'DIF') ?>>DIF</option>
            <option <?= selected_val($contrato['setor'],'DSU') ?>>DSU</option>
            <option <?= selected_val($contrato['setor'],'DSG') ?>>DSG</option>
            <option <?= selected_val($contrato['setor'],'SPO') ?>>SPO</option>
            <option <?= selected_val($contrato['setor'],'DP (PESSOAL)') ?>>DP (PESSOAL)</option>
            <option <?= selected_val($contrato['setor'],'DPH') ?>>DPH</option>
            <option <?= selected_val($contrato['setor'],'SAJ') ?>>SAJ</option>
          </select>

        </div>
        <div class="md:col-span-4">
          <label class="label" for="gestor">Gestor Responsável</label>
          <input id="gestor" name="gestor" class="input" placeholder="Nome do gestor" value="<?= h($contrato['gestor']) ?>">
        </div>

        <div class="md:col-span-4">
        <label class="label" for="licenca_ambiental_valida">Item de licença ambiental válida</label>
          <select id="licenca_ambiental_valida" name="licenca_ambiental_valida" class="input select" required>
            <option value="">Selecione...</option>
            <option value="Sim" <?= selected_val($contrato['licenca_ambiental_valida'] ?? '', 'Sim') ?>>Sim</option>
            <option value="Não" <?= selected_val($contrato['licenca_ambiental_valida'] ?? '', 'Não') ?>>Não</option>
          </select>
        </div>


        <div class="md:col-span-12">
          <hr class="my-4 border-slate-200">
        </div>

        <!-- L2 + L3 -->
        <div class="md:col-span-4">
          <label class="label" for="objeto">Objeto / Atividade</label>
          <textarea id="objeto" name="objeto" class="input min-h-[189px]" placeholder="Descreva o objeto ou atividade"><?= h($contrato['objeto']) ?></textarea>
        </div>

        <div class="md:col-span-8">
          <div class="grid md:grid-cols-8 gap-x-5 gap-y-1.5">
            <div class="md:col-span-4">
              <label class="label" for="status">Status</label>
              <select id="status" name="status" class="input select" required>
                <option value="">Selecione...</option>
                <option <?= selected_val($contrato['status_contrato'],'Continuidade') ?>>Continuidade</option>
                <option <?= selected_val($contrato['status_contrato'],'Novo') ?>>Novo</option>
              </select>
            </div>
            <div class="md:col-span-4">
              <label class="label" for="numero_contrato">Nº Contrato</label>
              <input id="numero_contrato" name="numero_contrato" class="input" placeholder="0000/0000" value="<?= h($contrato['numero_contrato']) ?>">
            </div>
            <div class="md:col-span-4">
              <label class="label" for="credor">Credor</label>
              <input id="credor" name="credor" class="input" placeholder="Nome do credor" value="<?= h($contrato['credor']) ?>">
            </div>

            <div class="md:col-span-4">
              <label class="label" for="vigencia">Vigência</label>
              <input id="vigencia" name="vigencia" class="input"
                placeholder="Ex: 20/2026 ou 14/01/2026 ou 1401"
                value="<?= h($vigenciaStr) ?>">
            </div>
            <div class="md:col-span-4">
              <label class="label" for="dea">DEA</label>
              <select id="dea" name="dea" class="input select">
                <option value="">Selecione...</option>
                <option <?= selected_bool($contrato['dea'], 'Sim') ?>>Sim</option>
                <option <?= selected_bool($contrato['dea'], 'Não') ?>>Não</option>
              </select>
            </div>
            <div class="md:col-span-4">
              <label class="label" for="reajuste">Reajuste</label>
              <select id="reajuste" name="reajuste" class="input select">
                <option value="">Selecione...</option>
                <option <?= selected_bool($contrato['reajuste'], 'Sim') ?>>Sim</option>
                <option <?= selected_bool($contrato['reajuste'], 'Não') ?>>Não</option>
              </select>
            </div>
          </div>
        </div>

        <div class="md:col-span-12">
  <hr class="my-4 border-slate-200">
</div>

<!-- Observações + Fonte/Grupo/SEI/Valor/Ação/Subação (igual ao formulario.php) -->
<div class="md:col-span-4">
  <label class="label" for="observacoes">Observações</label>
  <textarea id="observacoes" name="observacoes"
            class="input min-h-[189px]"
            placeholder="Observações gerais..."><?= h($contrato['observacoes'] ?? '') ?></textarea>
</div>

<div class="md:col-span-8">
  <div class="grid md:grid-cols-8 gap-x-5 gap-y-1.5">

    <div class="md:col-span-4">
      <label class="label" for="fonte">Fonte</label>
      <select id="fonte" name="fonte" class="input select">
        <option value="">Selecione...</option>
        <option value="0500 - (Tesouro do Estado)" <?= selected_val($contrato['fonte'] ?? '', '0500 - (Tesouro do Estado)') ?>>0500 - (Tesouro do Estado)</option>
        <option value="0700 - (Repasse de Convênio)" <?= selected_val($contrato['fonte'] ?? '', '0700 - (Repasse de Convênio)') ?>>0700 - (Repasse de Convênio)</option>
        <option value="0754 - (Operação de Crédito)" <?= selected_val($contrato['fonte'] ?? '', '0754 - (Operação de Crédito)') ?>>0754 - (Operação de Crédito)</option>
        <option value="XXXX - Destaque" <?= selected_val($contrato['fonte'] ?? '', 'XXXX - Destaque') ?>>XXXX - Destaque</option>
      </select>
    </div>

    <div class="md:col-span-4">
      <label class="label" for="grupo">Grupo</label>
      <select id="grupo" name="grupo" class="input select">
        <option value="">Selecione...</option>
        <option value="1 - Pessoal" <?= selected_val($contrato['grupo_despesa'] ?? '', '1 - Pessoal') ?>>1 - Pessoal</option>
        <option value="3 - Despesa Corrente" <?= selected_val($contrato['grupo_despesa'] ?? '', '3 - Despesa Corrente') ?>>3 - Despesa Corrente</option>
        <option value="4 - Investimentos" <?= selected_val($contrato['grupo_despesa'] ?? '', '4 - Investimentos') ?>>4 - Investimentos</option>
      </select>
    </div>

    <div class="md:col-span-4">
      <label class="label" for="sei">Número do SEI</label>
      <input id="sei" name="sei" class="input"
             placeholder="0000000-00.0000.0.00.0000"
             value="<?= h($contrato['sei'] ?? '') ?>">
    </div>

    <div class="md:col-span-4">
      <label class="label" for="valor_total">Valor Total do Contrato *</label>
      <input id="valor_total" name="valor_total"
             class="input moeda moeda-total"
             placeholder="R$ 0,00" inputmode="numeric"
             value="<?= brl_input($contrato['valor_total'] ?? 0) ?>">
    </div>

    <div class="md:col-span-4">
      <label class="label" for="acao">Ação</label>
      <select id="acao" name="acao" class="input select">
        <option value="">Selecione...</option>

        <option value="XXXX - Destaque" <?= selected_val($contrato['acao'] ?? '', 'XXXX - Destaque') ?>>XXXX - Destaque</option>
        <option value="2904 - Formulação e Promoção da Política de Regularização Fundiária" <?= selected_val($contrato['acao'] ?? '', '2904 - Formulação e Promoção da Política de Regularização Fundiária') ?>>2904 - Formulação e Promoção da Política de Regularização Fundiária</option>
        <option value="2998 - Encargos Gerais da Companhia Estadual de Habitação e Obras - CEHAB" <?= selected_val($contrato['acao'] ?? '', '2998 - Encargos Gerais da Companhia Estadual de Habitação e Obras - CEHAB') ?>>2998 - Encargos Gerais da Companhia Estadual de Habitação e Obras - CEHAB</option>
        <option value="3902 - Fomento e Apoio ao Conselho Estaudal de Habitação de Interesse Social - CEHAB" <?= selected_val($contrato['acao'] ?? '', '3902 - Fomento e Apoio ao Conselho Estaudal de Habitação de Interesse Social - CEHAB') ?>>3902 - Fomento e Apoio ao Conselho Estaudal de Habitação de Interesse Social - CEHAB</option>
        <option value="3927 - Manutenção da Ouvidoria da Companhia Estadual de Habitação e Obras - CEHAB" <?= selected_val($contrato['acao'] ?? '', '3927 - Manutenção da Ouvidoria da Companhia Estadual de Habitação e Obras - CEHAB') ?>>3927 - Manutenção da Ouvidoria da Companhia Estadual de Habitação e Obras - CEHAB</option>
        <option value="4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social" <?= selected_val($contrato['acao'] ?? '', '4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social') ?>>4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social</option>
        <option value="4300 - Execução de Obras de Infraestrutura e de Urbanização" <?= selected_val($contrato['acao'] ?? '', '4300 - Execução de Obras de Infraestrutura e de Urbanização') ?>>4300 - Execução de Obras de Infraestrutura e de Urbanização</option>
        <option value="4301 - Pesquisa e Assessoria Técnica para Habitação de Interesse Social" <?= selected_val($contrato['acao'] ?? '', '4301 - Pesquisa e Assessoria Técnica para Habitação de Interesse Social') ?>>4301 - Pesquisa e Assessoria Técnica para Habitação de Interesse Social</option>
        <option value="4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB" <?= selected_val($contrato['acao'] ?? '', '4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB') ?>>4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB</option>
        <option value="4587 - Contribuições Patronais da CEHAB" <?= selected_val($contrato['acao'] ?? '', '4587 - Contribuições Patronais da CEHAB') ?>>4587 - Contribuições Patronais da CEHAB</option>
        <option value="4613 - Encargos com o PIS e o COFINS da Companhia Estadual de Habitação e Obras - CEHAB" <?= selected_val($contrato['acao'] ?? '', '4613 - Encargos com o PIS e o COFINS da Companhia Estadual de Habitação e Obras - CEHAB') ?>>4613 - Encargos com o PIS e o COFINS da Companhia Estadual de Habitação e Obras - CEHAB</option>
      </select>
    </div>

    <div class="md:col-span-4">
      <label class="label" for="subacao">Subação</label>
      <select id="subacao" name="subacao" class="input select">
        <option value="">Selecione...</option>

        <option value="XXXX - Destaque" <?= selected_val($contrato['acao'],'XXXX - Destaque') ?>>
        <option value="0000 - Outras Medidas" <?= selected_val($contrato['subacao'] ?? '', '0000 - Outras Medidas') ?>>0000 - Outras Medidas</option>

        <option value="0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH) - Conclusão da construção de moradias"
          <?= selected_val($contrato['subacao'] ?? '', '0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH) - Conclusão da construção de moradias') ?>>
          0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH) - Conclusão da construção de moradias
        </option>

        <option value="0865 - Operacionalização do Programa Minha Casa Minha Vida"
          <?= selected_val($contrato['subacao'] ?? '', '0865 - Operacionalização do Programa Minha Casa Minha Vida') ?>>
          0865 - Operacionalização do Programa Minha Casa Minha Vida
        </option>
            <option <?= selected_val($contrato['subacao'],'1163 - Acompanhamento do cadastro de famílias beneficiadas pelo auxílio moradia ') ?>>1163 - Acompanhamento do cadastro de famílias beneficiadas pelo auxílio moradia </option>
            <option <?= selected_val($contrato['subacao'],'1399 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Escorregou Tá Dentro (Afogrados - Recife)') ?>>1399 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Escorregou Tá Dentro (Afogrados - Recife)</option>
            <option <?= selected_val($contrato['subacao'],'1400 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Mulheres de Tejucupapo (Iputinga - Recife)') ?>>1400 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Mulheres de Tejucupapo (Iputinga - Recife)</option>
            <option <?= selected_val($contrato['subacao'],'2067 - Obras e Projetos da Vila Claudete') ?>>2067 - Obras e Projetos da Vila Claudete</option>
            <option <?= selected_val($contrato['subacao'],'2217 - Execução das obras de implantação de adutora de recalque do Loteamento Snta Clara - Barreiros/PE') ?>>2217 - Execução das obras de implantação de adutora de recalque do Loteamento Snta Clara - Barreiros/PE</option>
            <option <?= selected_val($contrato['subacao'],'2409 - Entrada Garantida - Programa Morar Bem') ?>>2409 - Entrada Garantida - Programa Morar Bem</option>
            <option <?= selected_val($contrato['subacao'],'2787 - Contribuições Patronais da CEHAB ao FUNAFIN') ?>>2787 - Contribuições Patronais da CEHAB ao FUNAFIN</option>
            <option <?= selected_val($contrato['subacao'],'2790 - Manutenção da Tecnologia de Informação e Comunicação da CEHAB') ?>>2790 - Manutenção da Tecnologia de Informação e Comunicação da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'2791 - Fornecimento de vale transporte para servidores da CEHAB') ?>>2791 - Fornecimento de vale transporte para servidores da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'2792 - Fornecimento de vale alimentação para servidores da CEHAB') ?>>2792 - Fornecimento de vale alimentação para servidores da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'2793 - Regularização Fundiária e Oferta de Lotes Urbanos com Interesse Social') ?>>2793 - Regularização Fundiária e Oferta de Lotes Urbanos com Interesse Social</option>
            <option <?= selected_val($contrato['subacao'],'2794 - Auxílio Moradia - CEHAB') ?>>2794 - Auxílio Moradia - CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'2885 - Reforma no Lar - PROGRAMA MORAR BEM PE') ?>>2885 - Reforma no Lar - PROGRAMA MORAR BEM PE</option>
            <option <?= selected_val($contrato['subacao'],'3242 - Execução das obras de pavimentação, drenagem e sinalização da estrada Lygia Gomes da Silva - Ouro Preto') ?>>3242 - Execução das obras de pavimentação, drenagem e sinalização da estrada Lygia Gomes da Silva - Ouro Preto</option>
            <option <?= selected_val($contrato['subacao'],'3325 - Obras não incidentes - FAR e FDS') ?>>3325 - Obras não incidentes - FAR e FDS</option>
            <option <?= selected_val($contrato['subacao'],'3324 - Execução das obras de pavimentação') ?>>3324 - Execução das obras de pavimentação</option>
            <option <?= selected_val($contrato['subacao'],'3385 - Construção de obra de arte especial - PONTE, sobre o Rio Una (VPE-147, trecho: Barra do Jardim Altinho/PE)') ?>>3385 - Construção de obra de arte especial - PONTE, sobre o Rio Una (VPE-147, trecho: Barra do Jardim Altinho/PE)</option>
            <option <?= selected_val($contrato['subacao'],'3613 - Execução de obras de infraestrutura e construção de unidades habitacionais na av. Cruz Cabugá (Santo Amaro Recife)') ?>>3613 - Execução de obras de infraestrutura e construção de unidades habitacionais na av. Cruz Cabugá (Santo Amaro Recife)</option>            
            <option <?= selected_val($contrato['subacao'],'3352 - Programa Morar Bem - Construção de Unidades Habitacionais') ?>>3352 - Programa Morar Bem - Construção de Unidades Habitacionais</option>
            <option <?= selected_val($contrato['subacao'],'A386 - Execução de obras de infraestrutura e construção de unidades habitacionais na Bacia do Fregoso II') ?>>A386 - Execução de obras de infraestrutura e construção de unidades habitacionais na Bacia do Fregoso II</option>
            <option <?= selected_val($contrato['subacao'],'A389 - Execução de obras de infraestrutura e construção de unidades habitacionais no Canal do Jordão') ?>>A389 - Execução de obras de infraestrutura e construção de unidades habitacionais no Canal do Jordão</option>
            <option <?= selected_val($contrato['subacao'],'A401 - Execução de obras de infraestrutura e construção de unidades habitacionais em Azeitona(UE11) e Peixinhos (UE12)') ?>>A401 - Execução de obras de infraestrutura e construção de unidades habitacionais em Azeitona(UE11) e Peixinhos (UE12)</option>
            <option <?= selected_val($contrato['subacao'],'B156 - Construção da Via Metropolitana Norte (Fragoso - viaduto da PE-15/revestimento do canal/viário até Janga)') ?>>B156 - Construção da Via Metropolitana Norte (Fragoso - viaduto da PE-15/revestimento do canal/viário até Janga)</option>
            <option <?= selected_val($contrato['subacao'],'B661 - Despesas com taxa de água e esgoto da CEHAB') ?>>B661 - Despesas com taxa de água e esgoto da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'B662 - Despesas com combustível da CEHAB') ?>>B662 - Despesas com combustível da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'B664 - Despesas com tarifa de energia ') ?>>B664 - Despesas com tarifa de energia </option>
            <option <?= selected_val($contrato['subacao'],'B665 - Prestação de serviços de limpeza e conservação da CEHAB') ?>>B665 - Prestação de serviços de limpeza e conservação da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'B666 - Despesas com locação de veículos da CEHAB') ?>>B666 - Despesas com locação de veículos da CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'B667 - Prestação de serviços de motorista na CEHAB') ?>>B667 - Prestação de serviços de motorista na CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'B668 - Despesas com publicação oficiais de CEHAB em diário oficial') ?>>B668 - Despesas com publicação oficiais de CEHAB em diário oficial</option>
            <option <?= selected_val($contrato['subacao'],'B669 - Pagamento de apenados em processo de ressocialização na CEHAB') ?>>B669 - Pagamento de apenados em processo de ressocialização na CEHAB</option>
            <option <?= selected_val($contrato['subacao'],'B670 - Prestação de serviços de segurança pessoal e patrimonial na CEHAB ') ?>>B670 - Prestação de serviços de segurança pessoal e patrimonial na CEHAB </option>
            <option <?= selected_val($contrato['subacao'],'XXXX - Destaque') ?>>XXXX - Destaque</option>
      </select>
    </div>

  </div>
</div>

        <!-- L5: Ficha Financeira | Macro Tema | Priorização | É Prorrogável? -->
        <div class="md:col-span-4">
          <label class="label" for="ficha_financeira">Ficha Financeira</label>
          <select id="ficha_financeira" name="ficha_financeira" class="input">
            <option value="">Selecione...</option>
            <?php
            $fichas = [
              'G3 - Água e Esgoto',
              'G3 - Apoio Administrativo - Estagiários',
              'G3 - Auxílio Funeral',
              'G3 - Auxílio Moradia',
              'G3 - Auxílio Moradia - Operação Prontidão',
              'G3 - Combustíveis/Manutenção Veículos',
              'G3 - Cota Global',
              'G3 - Demandas Judiciais',
              'G4 - Devolução - Recursos do Concedente',
              'G3 - Diárias Civil',
              'G3 - Energia Elétrica',
              'G1 - FGTS',
              'G3 - Fornecimento de Passagens',
              'G1 - INSS',
              'G3 - Limpeza e Conservação',
              'G3 - Locação de Veículos',
              'G3 - Manutenção Predial',
              'G3 - Material de Expediente/Copa/Limpeza/Gráfico',
              'G3 - Motoristas',
              'G4 - Obra',
              'G4 - Operações de Crédito',
              'G4 - Outros',
              'G3 - Outros',
              'G1 - Pessoal e Encargos Sociais',
              'G3 - Publicação Oficiais',
              'G4 - Recursos do Concedente',
              'G3 - Rede Digital Corporativa do Estado',
              'G1 - Ressarcimento Pessoal à Disposição',
              'G3 - Salário de Apenados',
              'G3 - Serviços de Informática',
              'G3 - Serviços de Portaria',
              'G3 - Suprimento Individual',
              'G3 - Vigilância Ostensiva',
              'G4 - Supervisão de Obra',
              'G3 - Apoio Especializado',
              'G4 - Minha Casa Minha Vida',
              'G4 - Contrapartida de Convênio',
              'G4 - Destaque Orçamentário'
            ];
            foreach ($fichas as $f) {
              echo '<option '.selected_val($contrato['ficha_financeira'],$f).'>'.h($f).'</option>';
            }
            ?>
          </select>
        </div>

        <div class="md:col-span-3">
          <label class="label" for="macro_tema">Macro Tema</label>
          <select id="macro_tema" name="macro_tema" class="input select">
            <option value="">Selecione...</option>
            <?php
            $macro = [
              'Suporte a Gestão',
              'Mão de Obra Terceirizada',
              'Outros',
              'Auxílio',
              'Frota',
              'Convênio',
              'Pessoal e Encargos Sociais',
              'Obras',
              'Operações de Crédito',
            ];
            foreach ($macro as $m) {
              echo '<option '.selected_val($contrato['macro_tema'],$m).'>'.h($m).'</option>';
            }
            ?>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="label" for="priorizacao">Grau de Priorização</label>
          <select id="priorizacao" name="priorizacao" class="input select" required>
            <option value="">Selecione...</option>
            <option <?= selected_val($contrato['priorizacao'],'Grau Alto') ?>>Grau Alto</option>
            <option <?= selected_val($contrato['priorizacao'],'Grau Médio/Alto') ?>>Grau Médio/Alto</option>
            <option <?= selected_val($contrato['priorizacao'],'Grau Médio') ?>>Grau Médio</option>
            <option <?= selected_val($contrato['priorizacao'],'Grau Médio/Baixo') ?>>Grau Médio/Baixo</option>
            <option <?= selected_val($contrato['priorizacao'],'Grau Baixo') ?>>Grau Baixo</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="label" for="prorrogavel">É Prorrogável?</label>
          <select id="prorrogavel" name="prorrogavel" class="input select">
            <option value="">Selecione...</option>
            <option <?= selected_bool($contrato['prorrogavel'],'Sim') ?>>Sim</option>
            <option <?= selected_bool($contrato['prorrogavel'],'Não') ?>>Não</option>
          </select>
        </div>
      </section>

      <div class="md:col-span-12">
        <hr class="my-4 border-slate-200">
      </div>

      <!-- Tabela de Meses -->
      <section class="space-y-3">
        <h2 class="text-base font-semibold text-slate-900">Tabela de Meses</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <?php foreach ($mesLabels as $i => $lbl): ?>
            <?php $campo = $mesCampos[$i]; ?>
            <div class="space-y-1">
              <div class="label"><?= $lbl ?></div>
              <input type="text"
                inputmode="numeric"
                name="mes[<?= $i ?>]"
                class="input moeda moeda-mes"
                placeholder="R$ 0,00"
                value="<?= brl_input($contrato[$campo]) ?>">
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3 rounded-xl bg-sky-50 border border-sky-100 px-4 py-3 flex items-center justify-between">
          <span class="text-sm text-slate-700 font-medium">Total:</span>
          <strong class="text-slate-900" id="totalMeses"><?= brl_input($totalMeses) ?></strong>
        </div>
      </section>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" onclick="history.back()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-slate-700 text-sm hover:bg-slate-50">Cancelar</button>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-white text-sm font-medium shadow hover:bg-blue-700">Atualizar Contrato</button>
      </div>
    </form>
  </main>

  <script src="../js/formulario.js" defer></script>
</body>
</html>
