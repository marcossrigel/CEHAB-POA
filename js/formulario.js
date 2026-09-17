document.addEventListener('DOMContentLoaded', () => {
  
  // ===================== MOEDA (meses + total) =====================
  const fmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const parseMoeda = (v) => (v || '').replace(/[^0-9]/g, '') / 100 || 0;

  function somar() {
    let total = 0;
    document.querySelectorAll('.moeda-mes').forEach((i) => {
      total += parseMoeda(i.value);
    });

    const totalEl = document.getElementById('totalMeses');
    if (totalEl) totalEl.textContent = fmt.format(total);
  }

  function formatar(e) {
    e.target.value = fmt.format(parseMoeda(e.target.value));
    somar();
  }

  document.querySelectorAll('.moeda-mes').forEach((i) => i.addEventListener('input', formatar));
  document.querySelectorAll('.moeda-total').forEach((i) => i.addEventListener('input', formatar));

  // ===================== UTIL =====================
  function flashSelect(el) {
    if (!el) return;
    el.classList.add('ring-2', 'ring-sky-400', 'bg-sky-50', 'transition-colors', 'duration-700');
    const label = document.querySelector(`label[for="${el.id}"]`);
    if (label) label.classList.add('text-sky-700');
    setTimeout(() => {
      el.classList.remove('ring-2', 'ring-sky-400', 'bg-sky-50');
      if (label) label.classList.remove('text-sky-700');
    }, 1200);
  }

  const norm = (s) =>
    (s || '')
      .toString()
      .normalize('NFD')
      .replace(/\p{Diacritic}/gu, '')
      .toLowerCase()
      .trim();

  function pickOption(selectEl, queries) {
    if (!selectEl) return false;
    const prev = selectEl.value;
    const opts = Array.from(selectEl.options);

    for (const q of (Array.isArray(queries) ? queries : [queries])) {
      const qn = norm(q);

      const found =
        opts.find((o) => norm(o.text) === qn) ||
        opts.find((o) => norm(o.text).startsWith(qn)) ||
        opts.find((o) => norm(o.text).includes(qn));

      if (found) {
        selectEl.value = found.value; // seus <option> usam o próprio texto como value
        const changed = selectEl.value !== prev;
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        if (changed) flashSelect(selectEl);
        return true;
      }
    }
    return false;
  }

  // ===================== SNAPSHOT / FILTRO DE OPTIONS =====================
  const originals = new Map();

  function snapshotOptions(selectEl) {
    if (!selectEl || originals.has(selectEl)) return;
    originals.set(
      selectEl,
      Array.from(selectEl.options).map((o) => ({
        text: o.text,
        value: o.value,
        disabled: o.disabled,
      }))
    );
  }

  function restoreOptions(selectEl) {
    if (!selectEl) return;
    const snap = originals.get(selectEl);
    if (!snap) return;

    const current = selectEl.value;
    selectEl.innerHTML = '';

    for (const o of snap) {
      const opt = new Option(o.text, o.value);
      opt.disabled = !!o.disabled;
      selectEl.add(opt);
    }

    const had = Array.from(selectEl.options).find((o) => o.text === current);
    selectEl.value = had ? current : '';
  }

  function setAllowedOptions(selectEl, allowedTexts) {
    if (!selectEl) return;

    snapshotOptions(selectEl);

    const snap = originals.get(selectEl) || [];
    const allowedNorm = new Set(allowedTexts.map((t) => norm(t)));

    const kept = [];

    if (snap.length && norm(snap[0].text).includes('selecione')) kept.push(snap[0]);

    for (const o of snap) {
      if (norm(o.text).includes('selecione')) continue;
      if (allowedNorm.has(norm(o.text))) kept.push(o);
    }

    selectEl.innerHTML = '';
    for (const o of kept) {
      const opt = new Option(o.text, o.value);
      opt.disabled = !!o.disabled;
      selectEl.add(opt);
    }
    selectEl.value = '';
  }

  // ===================== ELEMENTOS =====================
  const temaSelect = document.getElementById('tema_custo');
  const grupoSelect = document.getElementById('grupo');
  const fonteEl = document.getElementById('fonte');
  const acaoEl = document.getElementById('acao');
  const subEl = document.getElementById('subacao');
  const fichaEl = document.getElementById('ficha_financeira');

  const camposAuto = [fonteEl, grupoSelect, acaoEl, subEl, fichaEl];

  function setCamposDisabled(disabled) {
    camposAuto.forEach((el) => {
      if (!el) return;

      if (!disabled) {
        el.disabled = false;
        el.classList.remove('bg-slate-100', 'cursor-not-allowed');
        return;
      }

      const opt = el.options[el.selectedIndex] || null;
      const text = opt ? opt.text || '' : '';
      const semEscolha = !el.value || norm(text).includes('selecione');

      if (semEscolha) {
        el.disabled = false;
        el.classList.remove('bg-slate-100', 'cursor-not-allowed');
      } else {
        el.disabled = true;
        el.classList.add('bg-slate-100', 'cursor-not-allowed');
      }
    });
  }

  // Libera disabled no submit (pra enviar pro PHP)
  const form = document.getElementById('formContrato');
  form?.addEventListener('submit', () => {
    form
      .querySelectorAll('select:disabled, input:disabled, textarea:disabled')
      .forEach((el) => (el.disabled = false));
  });

  // snapshot inicial (inclui fichaEl também)
  [fonteEl, grupoSelect, acaoEl, fichaEl].forEach(snapshotOptions);

  let autoLock = false;
  const getTemaCodigo = () => ((temaSelect?.value || '').split(' - ')[0] || '').trim();

  // ===================== REGRAS POR TEMA =====================
  function aplicarRegraTema() {
    if (!temaSelect) return;
    const temaCodigo = getTemaCodigo();

    restoreOptions(fonteEl);
    restoreOptions(grupoSelect);
    restoreOptions(acaoEl);
    restoreOptions(fichaEl);

    setCamposDisabled(false);

    if (subEl) {
      subEl.disabled = false;
      subEl.value = '';
    }

    if (!temaCodigo) return;

    // ================== TEMA 21 - OUTROS (livre, mas filtrado) ==================
    if (temaCodigo === '21') {
      autoLock = true;

      setAllowedOptions(fonteEl, [
        '0500 - (Tesouro do Estado)',
        '0500 - Tesouro do Estado',
        '0754 - (Operação de Crédito)',
        '0754 - Operações de Crédito',
        '0754 - Operação de Crédito',
      ]);

      setAllowedOptions(grupoSelect, ['3 - Despesa Corrente', '3 - Despesas Correntes', '4 - Investimentos']);

      setAllowedOptions(acaoEl, [
        '4300 - Execução de Obras de Infraestrutura e de Urbanização',
        '4301 - Pesquisa e Assessoria Técnica para Habitação de Interesse Social',
        '4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB',
      ]);

      setAllowedOptions(fichaEl, ['G3 - Outros', 'G4 - Outros']);

      if (subEl) subEl.value = '';
      if (fichaEl) fichaEl.value = '';

      autoLock = false;

      flashSelect(fonteEl);
      flashSelect(grupoSelect);
      flashSelect(acaoEl);
      return;
    }

        // ================== TEMA 40 - DESTAQUE ==================
    if (temaCodigo === '40') {
      autoLock = true;

      pickOption(fonteEl, ['XXXX - Destaque', 'XXXX']);
      pickOption(grupoSelect, ['4 - Investimentos', '4 -']);
      pickOption(acaoEl, ['XXXX - Destaque', 'XXXX']);
      pickOption(subEl, ['XXXX - Destaque', 'XXXX']);
      pickOption(fichaEl, ['G4 - Destaque Orçamentário', 'G4 - Destaque Orcamentario', 'G4 - Destaque']);

      autoLock = false;

      // trava para não editar (igual outros temas fechados)
      setCamposDisabled(true);

      flashSelect(fonteEl);
      flashSelect(grupoSelect);
      flashSelect(acaoEl);
      flashSelect(subEl);
      flashSelect(fichaEl);

      return;
    }

    // ================== TEMA 26 - OBRAS (limitar AÇÃO) ==================
    if (temaCodigo === '26') {
      autoLock = true;

      // Grupo = 4 - Investimentos
      pickOption(grupoSelect, ['4 - Investimentos', '4 -']);

      // Ficha = G4 - Obra  (tem que bater com o option do HTML)
      pickOption(fichaEl, ['G4 - Obra', 'G4 -']);

      // limita as opções do select AÇÃO
      setAllowedOptions(acaoEl, [
        '4300 - Execução de Obras de Infraestrutura e de Urbanização',
        '4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social',
        'XXXX - Destaque',
      ]);

      // deixa o usuário escolher a ação (ou troque por pickOption(acaoEl, '4300 - ...') se quiser fixar)
      if (acaoEl) acaoEl.value = '';

      autoLock = false;

      flashSelect(grupoSelect);
      flashSelect(fichaEl);
      flashSelect(acaoEl);

      return;
    }

  // ======== Regras já existentes (mantidas) ========
  const THEME_RULES = {
    '01': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Apoio Administrativo - Estagiários' },
    '02': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'B662 - Despesas com combustível da CEHAB', ficha:['G3 - Combustíveis/Manutenção Veículos','G3 - Combustíveis/Manutenção/ Veículos'] },
    '03': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Demandas Judiciais' },
    '04': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Diárias Civil' },
    '05': { acao:'2928 - Conservação do Patrimonio Público na Companhia Estadual de Habitação e Obras - CHEAB', sub:'B665 - Prestação de serviços de limpeza e conservação da CEHAB', ficha:'G3 - Limpeza e Conservação' },
    '06': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'B666 - Despesas com locação de veículos da CEHAB', ficha:'G3 - Locação de Veículos' },
    '07': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Manutenção Predial' },
    '08': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Material de Expediente/Copa/Limpeza/Gráfico' },
    '09': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'B667 - Prestação de serviços de motorista na CEHAB', ficha:'G3 - Motoristas' },
    '10': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'B669 - Pagamento de apenados em processo de ressocialização na CEHAB', ficha:'G3 - Salário de Apenados' },
    '11': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'2790 - Manutenção da Tecnologia de Informação e Comunicação da CEHAB', ficha:'G3 - Rede Digital Corporativa do Estado' },
    '12': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Serviços de Portaria' },
    '13': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Serviços de Informática' },
    '14': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Suprimento Individual' },
    '15': { acao:['2928 - Conservação do Patrimônio Público na Companhia Estadual de Habitação','2928 - Conservação do Patrimônio Público na Companhia Estadual de Habitação e Obras - CEHAB'], sub:'B670 - Prestação de serviços de segurança pessoal e patrimonial na CEHAB', ficha:'G3 - Vigilância Ostensiva' },
    '16': { acao:'4300 - Execução de Obras de Infraestrutura e de Urbanização', sub:'2794 - Auxílio Moradia - CEHAB', ficha: 'G3 - Auxílio Moradia'},
    '17': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Cota Global' },
    '18': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Fornecimento de Passagens' },
    '19': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:['B664 - Despesas com tarifa de energia elétrica da CEHAB','B664 - Despesas com tarifa de energia'], ficha:'G3 - Energia Elétrica' },
    '20': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:['B661 - Despesas com taxa de água e esgoto da CEHAB','B661 - Despesas com taxa de água e esgoto'], ficha:'G3 - Água e Esgoto' },
    '22': { grupo:['1 - Pessoal','1 - Pessoal e Encargos Sociais'], acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G1 - Pessoal e Encargos Sociais' },
    '23': { grupo:['1 - Pessoal','1 - Pessoal e Encargos Sociais'], acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G1 - FGTS' },
    '24': { grupo:['1 - Pessoal','1 - Pessoal e Encargos Sociais'], acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G1 - INSS' },
    '25': { grupo:['1 - Pessoal','1 - Pessoal e Encargos Sociais'], acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G1 - Ressarcimento Pessoal à Disposição' },
    '26': { grupo:['4 - Investimentos'], acao:'4300 - Execução de Obras de Infraestrutura e de Urbanização', ficha: 'G4 - Obra'},
    '30': { acao:'3927 - Manutenção da Ouvidoria da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Outros' },
    '33': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'2791 - Fornecimento de vale transporte para servidores da CEHAB', ficha:['G3 - Vale / Auxílio Transporte','G3 - Vale/Auxílio Transporte','G3 - Vale Transporte'] },
    '34': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'2792 - Fornecimento de vale alimentação para servidores da CEHAB', ficha:['G3 - Vale / Auxílio Alimentação','G3 - Vale/Auxílio Alimentação','G3 - Vale Alimentação'] },
    '35': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'B668 - Despesas com publicação oficiais de CEHAB em diário oficial', ficha:'G3 - Publicações Oficiais' },
    '36': { grupo:['1 - Pessoal','1 - Pessoal e Encargos Sociais'], acao:'4587 - Contribuições Patronais da CEHAB', sub:'2787 - Contribuições Patronais da CEHAB ao FUNAFIN', ficha:'G1 - Pessoal e Encargos Sociais' },
    '37': { acao:'4613 - Encargos com o PIS e o COFINS da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - OUTRAS MEDIDAS', ficha:'G3 - Outros' },
    '38': { acao:'4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB', sub:'0000 - Outras Medidas', ficha:'G3 - Apoio Administrativo'}
  };

  // ================== Tema 29 (regras por GRUPO, mas campos livres) ==================
  if (temaCodigo === '29') {
    setCamposDisabled(false); // usuário ainda mexe em grupo/ação/etc.
    pickOption(fonteEl, ['0500 - Tesouro do Estado','0500 - (Tesouro do Estado)','0500']);
    [acaoEl, subEl, fichaEl].forEach(sel => { if (sel) sel.value = ''; });
    return;
  }

  // ================== 31 - FINHIS ==================
  if (temaCodigo === '31') {
    autoLock = true;
    pickOption(grupoSelect, ['4 - Investimentos','4 -']);
    pickOption(acaoEl,  '4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social');
    pickOption(subEl,   '0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH)');
    if (fichaEl) fichaEl.value = '';
    autoLock = false;
    aplicarRegraFonte();
    setCamposDisabled(true);
    return;
  }

  // ================== 32 - MCMV ==================
  if (temaCodigo === '32') {
    autoLock = true;
    pickOption(grupoSelect, ['4 - Investimentos','4 -']);
    pickOption(acaoEl,  '4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social');
    pickOption(subEl,   '0865 - Operacionalização do Programa Minha Casa Minha Vida');
    if (fichaEl) fichaEl.value = '';
    autoLock = false;
    aplicarRegraFonte();
    setCamposDisabled(true);
    return;
  }

  // ================== 39 - Apoio Especializado ==================
  if (temaCodigo === '39') {
    autoLock = true;
    pickOption(acaoEl, '4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB');
    pickOption(subEl,  '0000 - OUTRAS MEDIDAS');
    if (fichaEl) fichaEl.value = '';
    autoLock = false;
    aplicarRegraFonte();
    setCamposDisabled(true);
    return;
  }

  // ================== 28 - Projetos de Obras ==================
  if (temaCodigo === '28') {
    autoLock = true;
    pickOption(fonteEl, ['0500 - Tesouro do Estado','0500 - (Tesouro do Estado)','0500']);
    pickOption(grupoSelect, ['4 - Investimentos','4 -']);
    pickOption(acaoEl, '4300 - Execução de Obras de Infraestrutura e de Urbanização');
    if (subEl) subEl.value = '';
    pickOption(fichaEl, 'G4 - Projeto de Obra');
    autoLock = false;
    setCamposDisabled(true);
    return;
  }

  // ================== 27 - Gerenciamento de Obras ==================
  if (temaCodigo === '27') {
    autoLock = true;
    pickOption(fonteEl, ['0500 - Tesouro do Estado','0500 - (Tesouro do Estado)','0500']);
    pickOption(grupoSelect, ['4 - Investimentos','4 -']);
    pickOption(acaoEl, '4300 - Execução de Obras de Infraestrutura e de Urbanização');
    if (subEl) subEl.value = '';
    pickOption(fichaEl, 'G4 - Supervisão de Obra');
    autoLock = false;
    setCamposDisabled(true);
    return;
  }

  // ================== Regras default do mapa (demais temas) ==================
  const cfg = THEME_RULES[temaCodigo] || THEME_RULES[String(Number(temaCodigo))];
  if (cfg) {
    autoLock = true;

    // Só força a fonte 0500 se NÃO for Tema 26 - Obras
    if (temaCodigo !== '26') {
      pickOption(fonteEl, ['0500 - Tesouro do Estado','0500 - (Tesouro do Estado)','0500']);
    }

    const toQueries = (v) => {
      if (!v) return [];
      const arr = Array.isArray(v) ? v.filter(Boolean) : [v].filter(Boolean);
      return arr.flatMap(t => [t, t.normalize('NFD').replace(/\p{Diacritic}/gu,'')]);
    };

    const grupoQueries = cfg.grupo
      ? (Array.isArray(cfg.grupo) ? cfg.grupo : [cfg.grupo])
      : ['3 - Despesa Corrente','3 - Despesas Correntes','3 -'];

    pickOption(grupoSelect, grupoQueries.concat(
      grupoQueries.map(t => t.normalize('NFD').replace(/\p{Diacritic}/gu,''))
    ));

    pickOption(acaoEl,  toQueries(cfg.acao));
    pickOption(subEl,   toQueries(cfg.sub));
    pickOption(fichaEl, toQueries(cfg.ficha));

    autoLock = false;
    setCamposDisabled(true); // aqui trava para os temas "fechados"
  }
}


function aplicarRegraFonte() {
  if (autoLock) return;

  const temaCodigo = getTemaCodigo();
  const f = norm(fonteEl?.value || '');
  const is0500 = f.includes('0500');
  const is0700 = f.includes('0700');
  const is0754 = f.includes('0754');

  if (temaCodigo === '31') {
    autoLock = true;
    pickOption(grupoSelect, ['4 - Investimentos','4 -']);
    pickOption(acaoEl, '4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social');
    pickOption(subEl,  '0055 - Programa Minha Casa (Operações Coletivas, CAIC, FNHIS e PSH)');
    if (is0700) pickOption(fichaEl, 'G4 - Recursos do Concedente');
    else if (is0754) pickOption(fichaEl, 'G4 - Operações de Crédito');
    else if (is0500) { if (fichaEl) fichaEl.value = ''; }
    autoLock = false;
    return;
  }

  if (temaCodigo === '32') {
    autoLock = true;
    pickOption(grupoSelect, ['4 - Investimentos','4 -']);
    pickOption(acaoEl, '4058 - Ampliação da Oferta e Requalificação de Habitação de Interesse Social');
    pickOption(subEl,  '0865 - Operacionalização do Programa Minha Casa Minha Vida');
    if (is0700) pickOption(fichaEl, 'G4 - Recursos do Concedente');
    else if (is0500) { if (fichaEl) fichaEl.value = ''; }
    autoLock = false;
    return;
  }

  if (temaCodigo === '39') {
    autoLock = true;
    pickOption(acaoEl, '4354 - Gestão das Atividades da Companhia Estadual de Habitação e Obras - CEHAB');
    pickOption(subEl,  '0000 - OUTRAS MEDIDAS');
    if (is0500) {
      pickOption(grupoSelect, ['3 - Despesa Corrente','3 - Despesas Correntes','3 -']);
      pickOption(fichaEl, 'G3 - Apoio Especializado');
    } else if (is0754) {
      pickOption(grupoSelect, ['4 - Investimentos','4 -']);
      pickOption(fichaEl, 'G4 - Operações de Crédito');
    }
    autoLock = false;
    return;
  }
  // 27 e 28 não dependem da fonte aqui
}

  // ===================== Regra especial por AÇÃO (Tema 21) =====================
  function aplicarRegraAcaoTema21() {
    if (getTemaCodigo() !== '21') return;
    const acao = (acaoEl?.value || '');
    const a = norm(acao);

    // 4301 → só sugere SUBAÇÃO, ficha fica a critério do usuário
    if (a.startsWith('4301 - pesquisa')) {
      pickOption(subEl, '1163 - Acompanhamento do cadastro de famílias beneficiadas pelo auxílio moradia');
      return;
    }

    // 4354 → só sugere SUBAÇÃO
    if (a.startsWith('4354 - gestao') || a.includes('gestão das atividades')) {
      pickOption(subEl, '0000 - OUTRAS MEDIDAS');
      return;
    }

    // 4300 → não força subnem ficha
    if (a.startsWith('4300 - execucao') || a.includes('infraestrutura e de urbanizacao')) {
      // deixa subação e ficha livres
      return;
    }
  }

  // ===================== regra 2 existente: Grupo -> ... (somente Tema 29) =====================
  function aplicarRegraGrupo() {
    if (!grupoSelect || autoLock) return;
    const temaCodigo = getTemaCodigo();
    if (temaCodigo !== '29') return;

    const gNorm = norm(grupoSelect.value || '');

    if (gNorm.startsWith('3 - despesa') || gNorm.startsWith('3 - despesas')) {
      pickOption(acaoEl, ['2904 - Formulação e Promoção da Política de Regularização Fundiária','2904']);
      pickOption(subEl,  ['0000 - OUTRAS MEDIDAS','0000']);
      pickOption(fichaEl,['G3 - Outros','Outros']);
      return;
    }

    if (gNorm.startsWith('4 - investimento')) {
      pickOption(acaoEl, ['2904 - Formulação e Promoção da Política de Regularização Fundiária','2904']);
      pickOption(subEl,  ['2793 - Regularização Fundiária e Oferta de Lotes Urbanos com Interesse Social','2793']);
      pickOption(fichaEl,['G4 - Outros','Outros']);
      return;
    }
  }

  // ===================== listeners =====================
 if (temaSelect)
    temaSelect.addEventListener('change', () => {
      aplicarRegraTema();
    });
  if (grupoSelect) grupoSelect.addEventListener('change', aplicarRegraGrupo);
  if (fonteEl)     fonteEl.addEventListener('change', aplicarRegraFonte);
  if (acaoEl)      acaoEl.addEventListener('change', aplicarRegraAcaoTema21);

  // ===================== inicialização =====================
  aplicarRegraTema();
  aplicarRegraGrupo();
  aplicarRegraFonte(); // caso dados venham preenchidos do servidor
  aplicarRegraAcaoTema21();

  somar(); // total inicial
});