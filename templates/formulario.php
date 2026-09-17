<?php
session_set_cookie_params(['path' => '/']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// protege a página
if (empty($_SESSION['usuario'])) {
    $_SESSION['flash_error'] = 'Sessão expirada. Faça login novamente.';
    header('Location: /CEHAB-Sistema-Planejamento-Orcamentario-Anual/index.php');
    exit;
}

$nomeUsuario  = $_SESSION['usuario']['nome']  ?? '';
$loginUsuario = $_SESSION['usuario']['login'] ?? '';

// ✅ limpa flash antigo ao entrar no formulário
unset($_SESSION['flash_error']);

$_SESSION['form_token'] = bin2hex(random_bytes(16));
?>

<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Novo Contrato • POA</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="../assets/css/formulario.css">
</head>

<body class="min-h-screen">
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="grid place-items-center w-9 h-9 rounded-xl bg-blue-600 text-white shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M3 21h18v-2H3v2Zm14-4h2V3h-6v4H5v10h2v-8h4v8h2v-4h4v4Zm-4-6V5h2v6h-2Z"/></svg>
        </div>
        <div class="flex flex-col">
          <span class="text-slate-900 font-semibold leading-none">POA - Planejamento Orçamentário Anual</span>
          <span class="text-xs text-slate-500 leading-none">Novo Contrato</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a href="home.php" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Voltar</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-8">
    <?php
    if (!empty($_SESSION['flash_error'])):
    ?>
      <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 mt-6">
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
          <strong>Erro:</strong> <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
    <?php
      unset($_SESSION['flash_error']);
    endif;
    ?>

    <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-8" action="salvar_contrato.php" method="post" id="formContrato">
      <input type="hidden" name="form_token" value="<?= htmlspecialchars($_SESSION['form_token']) ?>">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-slate-900">Formulário POA 2026</h1>
      </div>

      <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <span class="text-slate-500">Responsável (usuario_cehab):</span>
        <strong class="text-slate-900">
          <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>
        </strong>
      </div>

      <section class="grid grid-cols-1 md:grid-cols-12 gap-5">
        <div class="md:col-span-4">
          <label class="label" for="tema_custo">Tema de Custo</label>
          <select id="tema_custo" name="tema_custo" class="input select" required>
            <option value="">Selecione uma opção</option>
            <option>01 - Apoio Administrativo - Estagiários</option>
            <option>02 - Combustível/Manutenção de Veículos</option>
            <option>03 - Demandas Judiciais</option>
            <option>04 - Diárias Civil</option>
            <option>05 - Limpeza e Conservação</option>
            <option>06 - Locação de Veículos</option>
            <option>07 - Manutenção Predial</option>
            <option>08 - Material de Expediente/Copa/limpeza/Gráfico</option>
            <option>09 - Motoristas</option>
            <option>10 - Salário de Apenados</option>
            <option>11 - Rede Digital Corporativa do Estado</option>
            <option>12 - Serviços de Portaria</option>
            <option>13 - Serviços de Informática</option>
            <option>14 - Suprimento Individual</option>
            <option>15 - Vigilência Ostensiva</option>
            <option>16 - Auxílio Moradia</option>
            <option>17 - Cota Global</option>
            <option>18 - Passagnes Aéreas</option>
            <option>19 - Energia Elétrica</option>
            <option>20 - Água e Esgoto</option>
            <option>21 - Outros</option>
            <option>22 - Folha de Pessoal</option>
            <option>23 - FGTS</option>
            <option>24 - INSS</option>
            <option>25 - Ressarcimento Pessoal à Disposição</option>
            <option>26 - Obras</option>
            <option>27 - Gerenciamento de Obras</option>
            <option>28 - Projetos de Obras</option>
            <option>29 - Regularização Fundiária</option>
            <option>30 - Ouvidoria</option>
            <option>31 - FINHIS</option>
            <option>32 - Minha Casa Minha Vida</option>
            <option>33 - Vale Transporte</option>
            <option>34 - Vale Alimentação</option>
            <option>35 - Publicações Oficiais</option>
            <option>36 - Contribuições Patronais da CEHAB</option>
            <option>37 - Encargos com o PIS e o COFINS</option>
            <option>38 - Apoio Administrativo</option>
            <option>39 - Apoio Especializado</option>
            <option>40 - Destaque</option>
            
          </select>
        </div>
        <div class="md:col-span-4">
          <label class="label" for="setor">Setor Responsável</label>
          <select id="setor" name="setor" class="input select" required>
            <option value="">Selecione um setor</option>
            <option>DAF</option>
            <option>DOHDU</option>
            <option>DED</option>
            <option>DIF</option>
            <option>DSU</option>
            <option>DSG</option>
            <option>SPO</option>
            <option>DP (PESSOAL)</option>
            <option>DPH</option>
            <option>SAJ</option>
          </select>
        </div>
        <div class="md:col-span-4">
          <label class="label" for="gestor">Gestor Responsável</label>
          <input id="gestor" name="gestor" class="input" placeholder="Nome do gestor" required>
        </div>

        <div class="md:col-span-4">
          <label class="label" for="licenca_ambiental_valida">Item de licença ambiental válida</label>
          <select id="licenca_ambiental_valida" name="licenca_ambiental_valida" class="input select" required>
            <option value="">Selecione...</option>
            <option value="Sim">Sim</option>
            <option value="Não">Não</option>
          </select>
        </div>

        <div class="md:col-span-12">
          <hr class="my-4 border-slate-200">
        </div>

        <div class="md:col-span-4">
          <label class="label" for="objeto">Objeto / Atividade</label>
          <textarea id="objeto" name="objeto" class="input min-h-[189px]" placeholder="Descreva o objeto ou atividade" required></textarea>
        </div>

        <div class="md:col-span-8">
          <div class="grid md:grid-cols-8 gap-x-5 gap-y-1.5">
            <div class="md:col-span-4">
              <label class="label" for="status">Status</label>
              <select id="status" name="status" class="input select" required>
                  <option value="">Selecione...</option>
                  <option>Continuidade</option>
                  <option>Novo</option>
              </select>
            </div>
            <div class="md:col-span-4">
              <label class="label" for="numero_contrato">Nº Contrato</label>
              <input id="numero_contrato" name="numero_contrato" class="input" placeholder="0000/0000" required>
            </div>
            <div class="md:col-span-4">
              <label class="label" for="credor">Credor</label>
              <input id="credor" name="credor" class="input" placeholder="Nome do credor" required>
            </div>

            <div class="md:col-span-4">
              <label class="label" for="vigencia">Vigência (data fim)</label>
              <input id="vigencia" name="vigencia" class="input" placeholder="ex: 20/2026" required>
            </div>
            <div class="md:col-span-4">
              <label class="label" for="dea">DEA</label>
              <select id="dea" name="dea" class="input select" required>
                <option value="">Selecione...</option><option>Sim</option><option>Não</option>
              </select>
            </div>

            <div class="md:col-span-4">
              <label class="label" for="reajuste">Reajuste</label>
              <select id="reajuste" name="reajuste" class="input select" required>
                <option value="">Selecione...</option>
                <option>Sim</option>
                <option>Não</option>
              </select>
            </div>
          </div>
        </div>

        <!-- (continua dentro do mesmo <form> ...) -->

<div class="md:col-span-12">
  <hr class="my-4 border-slate-200">
</div>

<!-- Observações + Fonte/Grupo/SEI/Valor/Ação/Subação -->
<div class="md:col-span-4">
  <label class="label" for="observacoes">Observações</label>
  <textarea id="observacoes" name="observacoes"
            class="input min-h-[189px]"
            placeholder="Observações gerais..." required></textarea>
</div>

<div class="md:col-span-8">
  <div class="grid md:grid-cols-8 gap-x-5 gap-y-1.5">

    <div class="md:col-span-4">
      <label class="label" for="fonte">Fonte</label>
      <select id="fonte" name="fonte" class="input select" required>
        <option value="">Selecione...</option>
        <option value="0500 - (Tesouro do Estado)">0500 - (Tesouro do Estado)</option>
        <option value="0700 - (Repasse de Convênio)">0700 - (Repasse de Convênio)</option>
        <option value="0754 - (Operação de Crédito)">0754 - (Operação de Crédito)</option>
        <option value="XXXX - Destaque">XXXX - Destaque</option>
      </select>
    </div>

    <div class="md:col-span-4">
      <label class="label" for="grupo">Grupo</label>
      <select id="grupo" name="grupo" class="input select" required>
        <option value="">Selecione...</option>
        <option value="1 - Pessoal">1 - Pessoal</option>
        <option value="3 - Despesa Corrente">3 - Despesa Corrente</option>
        <option value="4 - Investimentos">4 - Investimentos</option>
      </select>
    </div>

    <div class="md:col-span-4">
      <label class="label" for="sei">Número do SEI</label>
      <input id="sei" name="sei" class="input" placeholder="0000000-00.0000.0.00.0000">
    </div>

    <div class="md:col-span-4">
      <label class="label" for="valor_total_contrato">Valor Total do Contrato</label>
      <input id="valor_total_contrato" name="valor_total_contrato"
             class="input moeda-total" placeholder="R$ 0,00" inputmode="numeric" required>
    </div>

<div class="md:col-span-4">
  <label class="label" for="acao">Ação</label>
  <select id="acao" name="acao" class="input select" required>
    <option value="">Selecione...</option>

    <option value="XXXX - Destaque">XXXX - Destaque</option>
    <option value="2904 - Formulação e Promoção da Política de Regularização Fundiária">
      2904 - Formulação e Promoção da Política de Regularização Fundiária
    </option>

    <option value="2998 - Encargos Gerais da Companhia Estadual de Habitação e Obras - CEHAB">
      2998 - Encargos Gerais da Companhia Estadual de Habitação e Obras - CEHAB
    </option>

    <option value="3902 - Fomento e Apoio ao Conselho Estaudal de Habitação de Interesse Social - CEHAB">
      3902 - Fomento e Apoio ao Conselho Estaudal de Habitação de Interesse Social - CEHAB
    </option>

    <option value="3927 - Manutenção da Ouvidoria da Companhia Estadual de Habitação e Obras - CEHAB">
      3927 - Manutenção da Ouvidoria da Companhia Estadual de Habitação e Obras - CEHAB
    </option>

    <option value="4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social">
      4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social
    </option>

    <option value="4300 - Execução de Obras de Infraestrutura e de Urbanização">
      4300 - Execução de Obras de Infraestrutura e de Urbanização
    </option>

    <option value="4301 - Pesquisa e Assessoria Técnica para Habitação de Interesse Social">
      4301 - Pesquisa e Assessoria Técnica para Habitação de Interesse Social
    </option>

    <option value="4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB">
      4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB
    </option>

    <option value="4587 - Contribuições Patronais da CEHAB">
      4587 - Contribuições Patronais da CEHAB
    </option>

    <option value="4613 - Encargos com o PIS e o COFINS da Companhia Estadual de Habitação e Obras - CEHAB">
      4613 - Encargos com o PIS e o COFINS da Companhia Estadual de Habitação e Obras - CEHAB
    </option>
  </select>
</div>


            <div class="md:col-span-4">
  <label class="label" for="subacao">Subação</label>
  <select id="subacao" name="subacao" class="input select" required>
    <option value="">Selecione...</option>

    <option value="XXXX - Destaque">XXXX - Destaque</option>
    <option value="0000 - Outras Medidas">0000 - Outras Medidas</option>

    <option value="0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH) - Conclusão da construção de moradias">
      0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH) - Conclusão da construção de moradias
    </option>

    <option value="0865 - Operacionalização do Programa Minha Casa Minha Vida">
      0865 - Operacionalização do Programa Minha Casa Minha Vida
    </option>

    <option value="1163 - Acompanhamento do cadastro de famílias beneficiadas pelo auxílio moradia">
      1163 - Acompanhamento do cadastro de famílias beneficiadas pelo auxílio moradia
    </option>

    <option value="1399 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Escorregou Tá Dentro (Afogrados - Recife)">
      1399 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Escorregou Tá Dentro (Afogrados - Recife)
    </option>

    <option value="1400 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Mulheres de Tejucupapo (Iputinga - Recife)">
      1400 - Execução de obras de infraestrutura e construção de unidades habitacionais na comunidade de Mulheres de Tejucupapo (Iputinga - Recife)
    </option>

    <option value="2067 - Obras e Projetos da Vila Claudete">
      2067 - Obras e Projetos da Vila Claudete
    </option>

    <option value="2217 - Execução das obras de implantação de adutora de recalque do Loteamento Snta Clara - Barreiros/PE">
      2217 - Execução das obras de implantação de adutora de recalque do Loteamento Snta Clara - Barreiros/PE
    </option>

    <option value="2409 - Entrada Garantida - Programa Morar Bem">
      2409 - Entrada Garantida - Programa Morar Bem
    </option>

    <option value="2787 - Contribuições Patronais da CEHAB ao FUNAFIN">
      2787 - Contribuições Patronais da CEHAB ao FUNAFIN
    </option>

    <option value="2790 - Manutenção da Tecnologia de Informação e Comunicação da CEHAB">
      2790 - Manutenção da Tecnologia de Informação e Comunicação da CEHAB
    </option>

    <option value="2791 - Fornecimento de vale transporte para servidores da CEHAB">
      2791 - Fornecimento de vale transporte para servidores da CEHAB
    </option>

    <option value="2792 - Fornecimento de vale alimentação para servidores da CEHAB">
      2792 - Fornecimento de vale alimentação para servidores da CEHAB
    </option>

    <option value="2793 - Regularização Fundiária e Oferta de Lotes Urbanos com Interesse Social">
      2793 - Regularização Fundiária e Oferta de Lotes Urbanos com Interesse Social
    </option>

    <option value="2794 - Auxílio Moradia - CEHAB">
      2794 - Auxílio Moradia - CEHAB
    </option>

    <option value="2885 - Reforma no Lar - PROGRAMA MORAR BEM PE">
      2885 - Reforma no Lar - PROGRAMA MORAR BEM PE
    </option>

    <option value="3242 - Execução das obras de pavimentação, drenagem e sinalização da estrada Lygia Gomes da Silva - Ouro Preto">
      3242 - Execução das obras de pavimentação, drenagem e sinalização da estrada Lygia Gomes da Silva - Ouro Preto
    </option>

    <option value="3325 - Obras não incidentes - FAR e FDS">
      3325 - Obras não incidentes - FAR e FDS
    </option>

    <option value="3324 - Execução das obras de pavimentação">
      3324 - Execução das obras de pavimentação
    </option>

    <option value="3385 - Construção de obra de arte especial - PONTE, sobre o Rio Una (VPE-147, trecho: Barra do Jardim Altinho/PE)">
      3385 - Construção de obra de arte especial - PONTE, sobre o Rio Una (VPE-147, trecho: Barra do Jardim Altinho/PE)
    </option>

    <option value="3613 - Execução de obras de infraestrutura e construção de unidades habitacionais na av. Cruz Cabugá (Santo Amaro Recife)">
      3613 - Execução de obras de infraestrutura e construção de unidades habitacionais na av. Cruz Cabugá (Santo Amaro Recife)
    </option>

    <option value="3352 - Programa Morar Bem - Construção de Unidades Habitacionais">
      3352 - Programa Morar Bem - Construção de Unidades Habitacionais
    </option>

    <option value="A386 - Execução de obras de infraestrutura e construção de unidades habitacionais na Bacia do Fregoso II">
      A386 - Execução de obras de infraestrutura e construção de unidades habitacionais na Bacia do Fregoso II
    </option>

    <option value="A389 - Execução de obras de infraestrutura e construção de unidades habitacionais no Canal do Jordão">
      A389 - Execução de obras de infraestrutura e construção de unidades habitacionais no Canal do Jordão
    </option>

    <option value="A401 - Execução de obras de infraestrutura e construção de unidades habitacionais em Azeitona(UE11) e Peixinhos (UE12)">
      A401 - Execução de obras de infraestrutura e construção de unidades habitacionais em Azeitona(UE11) e Peixinhos (UE12)
    </option>

    <option value="B156 - Construção da Via Metropolitana Norte (Fragoso - viaduto da PE-15/revestimento do canal/viário até Janga)">
      B156 - Construção da Via Metropolitana Norte (Fragoso - viaduto da PE-15/revestimento do canal/viário até Janga)
    </option>

    <option value="B661 - Despesas com taxa de água e esgoto da CEHAB">
      B661 - Despesas com taxa de água e esgoto da CEHAB
    </option>

    <option value="B662 - Despesas com combustível da CEHAB">
      B662 - Despesas com combustível da CEHAB
    </option>

    <option value="B664 - Despesas com tarifa de energia">
      B664 - Despesas com tarifa de energia
    </option>

    <option value="B665 - Prestação de serviços de limpeza e conservação da CEHAB">
      B665 - Prestação de serviços de limpeza e conservação da CEHAB
    </option>

    <option value="B666 - Despesas com locação de veículos da CEHAB">
      B666 - Despesas com locação de veículos da CEHAB
    </option>

    <option value="B667 - Prestação de serviços de motorista na CEHAB">
      B667 - Prestação de serviços de motorista na CEHAB
    </option>

    <option value="B668 - Despesas com publicação oficiais de CEHAB em diário oficial">
      B668 - Despesas com publicação oficiais de CEHAB em diário oficial
    </option>

    <option value="B669 - Pagamento de apenados em processo de ressocialização na CEHAB">
      B669 - Pagamento de apenados em processo de ressocialização na CEHAB
    </option>

    <option value="B670 - Prestação de serviços de segurança pessoal e patrimonial na CEHAB">
      B670 - Prestação de serviços de segurança pessoal e patrimonial na CEHAB
    </option>

  </select>
</div>


        </div>
      </div>

        <div class="md:col-span-4">
  <label class="label" for="ficha_financeira">Ficha Financeira</label>
  <select id="ficha_financeira" name="ficha_financeira" class="input select" required>
    <option value="">Selecione...</option>

    <option value="G3 - Água e Esgoto">G3 - Água e Esgoto</option>
    <option value="G3 - Apoio Administrativo">G3 - Apoio Administrativo</option>
    <option value="G3 - Apoio Administrativo - Estagiários">G3 - Apoio Administrativo - Estagiários</option>
    <option value="G3 - Auxílio Funeral">G3 - Auxílio Funeral</option>
    <option value="G3 - Auxílio Moradia">G3 - Auxílio Moradia</option>
    <option value="G3 - Auxílio Moradia - Operação Prontidão">G3 - Auxílio Moradia - Operação Prontidão</option>
    <option value="G3 - Combustíveis/Manutenção Veículos">G3 - Combustíveis/Manutenção Veículos</option>
    <option value="G3 - Cota Global">G3 - Cota Global</option>
    <option value="G3 - Demandas Judiciais">G3 - Demandas Judiciais</option>
    <option value="G4 - Devolução - Recursos do Concedente">G4 - Devolução - Recursos do Concedente</option>
    <option value="G3 - Diárias Civil">G3 - Diárias Civil</option>
    <option value="G3 - Energia Elétrica">G3 - Energia Elétrica</option>
    <option value="G1 - FGTS">G1 - FGTS</option>
    <option value="G3 - Fornecimento de Passagens">G3 - Fornecimento de Passagens</option>
    <option value="G1 - INSS">G1 - INSS</option>
    <option value="G3 - Limpeza e Conservação">G3 - Limpeza e Conservação</option>
    <option value="G3 - Locação de Veículos">G3 - Locação de Veículos</option>
    <option value="G3 - Manutenção Predial">G3 - Manutenção Predial</option>
    <option value="G3 - Material de Expediente/Copa/Limpeza/Gráfico">
      G3 - Material de Expediente/Copa/Limpeza/Gráfico
    </option>
    <option value="G3 - Motoristas">G3 - Motoristas</option>
    <option value="G4 - Obra">G4 - Obra</option>
    <option value="G4 - Operações de Crédito">G4 - Operações de Crédito</option>
    <option value="G4 - Outros">G4 - Outros</option>
    <option value="G3 - Outros">G3 - Outros</option>
    <option value="G1 - Pessoal e Encargos Sociais">G1 - Pessoal e Encargos Sociais</option>
    <option value="G3 - Publicações Oficiais">G3 - Publicações Oficiais</option>
    <option value="G4 - Recursos do Concedente">G4 - Recursos do Concedente</option>
    <option value="G3 - Rede Digital Corporativa do Estado">
      G3 - Rede Digital Corporativa do Estado
    </option>
    <option value="G1 - Ressarcimento Pessoal à Disposição">
      G1 - Ressarcimento Pessoal à Disposição
    </option>
    <option value="G3 - Salário de Apenados">G3 - Salário de Apenados</option>
    <option value="G3 - Serviços de Informática">G3 - Serviços de Informática</option>
    <option value="G3 - Serviços de Portaria">G3 - Serviços de Portaria</option>
    <option value="G3 - Suprimento Individual">G3 - Suprimento Individual</option>
    <option value="G3 - Vigilância Ostensiva">G3 - Vigilância Ostensiva</option>
    <option value="G4 - Supervisão de Obra">G4 - Supervisão de Obra</option>
    <option value="G3 - Apoio Especializado">G3 - Apoio Especializado</option>
    <option value="G4 - Minha Casa Minha Vida">G4 - Minha Casa Minha Vida</option>
    <option value="G4 - Contrapartida de Convênio">G4 - Contrapartida de Convênio</option>
    <option value="G4 - Destaque Orçamentário">G4 - Destaque Orçamentário</option>

  </select>
</div>


        <div class="md:col-span-3">
          <label class="label" for="macro_tema">Macro Tema</label>
          <select id="macro_tema" name="macro_tema" class="input select" required>
            <option value="">Selecione...</option>
            <option>Suporte a Gestão</option>
            <option>Mão de Obra Terceirizada</option>
            <option>Outros</option>
            <option>Auxílio</option>
            <option>Frota</option>
            <option>Convênio</option>
            <option>Pessoal e Encargos Sociais</option>
            <option>Obras</option>
            <option>Operações de Crédito</option>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="label" for="priorizacao">Grau de Priorização</label>
          <select id="priorizacao" name="priorizacao" class="input select" required>
            <option value="">Selecione...</option>
            <option>Grau Alto</option>
            <option>Grau Médio/Alto</option>
            <option>Grau Médio</option>
            <option>Grau Médio/Baixo</option>
            <option>Grau Baixo</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="label" for="prorrogavel">É Prorrogável?</label>
          <select id="prorrogavel" name="prorrogavel" class="input select" required>
            <option value="">Selecione...</option>
            <option>Sim</option>
            <option>Não</option>
          </select>
        </div>
      </section>

      <div class="md:col-span-12">
          <hr class="my-4 border-slate-200">
      </div>

      <section class="space-y-3">
        <h2 class="text-base font-semibold text-slate-900">Tabela de Meses</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3" required>
          <?php $meses=['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ']; foreach($meses as $i=>$m): ?>
            <div class="space-y-1">
              <div class="label"><?= $m ?></div>
              <input type="text" inputmode="numeric" name="mes[<?= $i ?>]" class="input moeda-mes" placeholder="R$ 0,00">
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3 rounded-xl bg-sky-50 border border-sky-100 px-4 py-3 flex items-center justify-between">
          <span class="text-sm text-slate-700 font-medium">Total:</span>
          <strong class="text-slate-900" id="totalMeses">R$ 0,00</strong>
        </div>
      </section>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" onclick="history.back()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-slate-700 text-sm hover:bg-slate-50">Cancelar</button>
        <button id="btnSalvar" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-white text-sm font-medium shadow hover:bg-blue-700">Salvar Contrato</button>
      </div>
    </form>
  </main>

  <script src="../js/condicionais_poa.js" defer></script>
  <?php
    $jsPath = __DIR__ . '/../js/formulario.js';
    $v = file_exists($jsPath) ? filemtime($jsPath) : time();
  ?>
  <script src="../js/formulario.js?v=<?= $v ?>" defer></script>

</body>
</html>
