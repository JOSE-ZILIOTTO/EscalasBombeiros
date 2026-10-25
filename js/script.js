// Comportamento das telas administrativas (painel, bombeiros, edição).
// Cada bloco só age se encontrar seus elementos na página.
document.addEventListener('DOMContentLoaded', () => {
  // ---------------------------------------------------------------------------
  // Rede
  // ---------------------------------------------------------------------------
  const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  async function requisitar(url, opcoes = {}) {
    if ((opcoes.method || 'GET').toUpperCase() === 'POST') {
      opcoes.headers = { 'X-CSRF-Token': tokenCsrf, ...(opcoes.headers || {}) };
    }
    const resposta = await fetch(url, opcoes);
    let json = null;
    try { json = await resposta.json(); } catch (e) { /* tratado abaixo */ }
    if (!json) throw new Error(`Resposta inválida do servidor (HTTP ${resposta.status}).`);
    if (!resposta.ok || json.success === false) throw new Error(json.message || `HTTP ${resposta.status}`);
    return json;
  }

  const enviar = (url, dados = {}) => requisitar(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(dados),
  });

  // ---------------------------------------------------------------------------
  // Utilidades de interface
  // ---------------------------------------------------------------------------
  const escapar = (texto) => String(texto ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));

  const ICONES = {
    remover: '<path d="M18 6 6 18M6 6l12 12"/>',
    restaurar: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
    excecao: '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
  };
  const icone = (nome) => `<svg class="icone" viewBox="0 0 24 24" aria-hidden="true">${ICONES[nome]}</svg>`;

  // mesmas etiquetas de AjudanteDeView::etiquetaTurno (PHP)
  const ETIQUETAS = {
    D: '<span class="turno turno--d" title="Diurno (12h)">D</span>',
    N: '<span class="turno turno--n" title="Noturno (12h)">N</span>',
    I: '<span class="turno turno--i" title="Integral (24h)">24h</span>',
    I_SUB: '<span class="turno turno--sub" title="Integral substituto do Fixo (24h)">SUB</span>',
    FIXO: '<span class="turno turno--fixo" title="Fixo pelo ciclo 24x72">Fixo</span>',
  };

  function avisar(mensagem, tipo = 'info') {
    const area = document.getElementById('toast-container');
    if (!area) return;
    const aviso = document.createElement('div');
    aviso.className = `toast ${tipo}`;
    aviso.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
    aviso.textContent = mensagem;
    area.appendChild(aviso);
    requestAnimationFrame(() => aviso.classList.add('show'));
    setTimeout(() => {
      aviso.classList.remove('show');
      aviso.addEventListener('transitionend', () => aviso.remove(), { once: true });
    }, 4000);
  }

  function confirmar(texto) {
    const caixa = document.getElementById('confirm-modal');
    if (!caixa) return Promise.resolve(window.confirm(texto));
    document.getElementById('confirm-modal-text').textContent = texto;
    caixa.style.display = 'flex';
    const sim = document.getElementById('confirm-modal-btn-yes');
    const nao = document.getElementById('confirm-modal-btn-no');
    sim.focus();
    return new Promise((resolver) => {
      const fechar = (decisao) => {
        caixa.style.display = 'none';
        sim.onclick = null;
        nao.onclick = null;
        resolver(decisao);
      };
      sim.onclick = () => fechar(true);
      nao.onclick = () => fechar(false);
    });
  }

  // ---------------------------------------------------------------------------
  // Bombeiros: confirmar inativação
  // ---------------------------------------------------------------------------
  document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', async (evento) => {
      evento.preventDefault();
      if (await confirmar(form.dataset.confirmar)) form.submit();
    });
  });

  // ---------------------------------------------------------------------------
  // Edição: campos do ciclo só para Fixo e máscaras simples
  // ---------------------------------------------------------------------------
  const seletorTipo = document.getElementById('tipo');
  const camposFixo = document.getElementById('fixo-fields');
  if (seletorTipo && camposFixo) {
    const alternar = () => {
      const ehFixo = seletorTipo.value === 'Fixo';
      camposFixo.classList.toggle('visible', ehFixo);
      camposFixo.querySelectorAll('input, select').forEach((campo) => { campo.required = ehFixo; });
    };
    seletorTipo.addEventListener('change', alternar);
    alternar();
  }

  const MASCARAS = {
    cpf: (d) => d.slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
    cep: (d) => d.slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
    telefone: (d) => {
      d = d.slice(0, 11);
      if (d.length > 10) return d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
      if (d.length > 6) return d.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
      if (d.length > 2) return d.replace(/(\d{2})(\d*)/, '($1) $2');
      return d;
    },
  };
  document.querySelectorAll('[data-mascara]').forEach((campo) => {
    const formatar = MASCARAS[campo.dataset.mascara];
    if (formatar) campo.addEventListener('input', () => { campo.value = formatar(campo.value.replace(/\D/g, '')); });
  });

  // ---------------------------------------------------------------------------
  // Edição: CPF e dados bancários chegam mascarados; o valor real só vem da API
  // quando o administrador pede, e o campo só é enviado depois de "Alterar"
  // ---------------------------------------------------------------------------
  document.querySelectorAll('[data-sigiloso]').forEach((bloco) => {
    const exibicao = bloco.querySelector('[data-sigiloso-valor]');
    const mostrar = bloco.querySelector('[data-sigiloso-mostrar]');
    const alterar = bloco.querySelector('[data-sigiloso-alterar]');
    const entrada = bloco.querySelector('input, textarea');
    if (!exibicao || !mostrar || !alterar || !entrada) return;
    let revelado = null;

    const buscar = async () => {
      if (revelado === null) {
        const resposta = await enviar('api/api_revelar_dado.php', { bombeiro_id: bloco.dataset.bombeiroId, campo: bloco.dataset.sigiloso });
        revelado = resposta.valor;
      }
      return revelado;
    };

    mostrar.addEventListener('click', async () => {
      const rotulo = mostrar.querySelector('span');
      if (exibicao.classList.contains('revelado')) {
        exibicao.textContent = exibicao.dataset.mascaraExibida;
        exibicao.classList.remove('revelado');
        rotulo.textContent = 'Mostrar';
        return;
      }
      try {
        exibicao.textContent = await buscar();
        exibicao.classList.add('revelado');
        rotulo.textContent = 'Ocultar';
      } catch (erro) {
        avisar(erro.message, 'error');
      }
    });

    alterar.addEventListener('click', async () => {
      try {
        entrada.value = await buscar(); // abre com o valor atual para corrigir em vez de redigitar
      } catch (erro) {
        avisar(erro.message, 'error');
        return;
      }
      bloco.querySelector('.sigiloso__atual').hidden = true;
      entrada.hidden = false;
      entrada.disabled = false;
      entrada.focus();
    });
  });

  // ---------------------------------------------------------------------------
  // Painel: modal do dia
  // ---------------------------------------------------------------------------
  const modal = document.getElementById('detailsModal');
  if (!modal) return;

  const tituloModal = document.getElementById('modalDate');
  const listaOcupantes = document.getElementById('modalOcupantesList');
  const secaoRegistro = document.getElementById('modalSelecao');
  const seletorBombeiro = document.getElementById('modalSelectBombeiro');
  const sugestao = document.getElementById('modalSugestao');
  const botoesTurno = {
    D: document.getElementById('modalBtnD'),
    N: document.getElementById('modalBtnN'),
    I: document.getElementById('modalBtnI'),
    I_SUB: document.getElementById('modalBtnISUB'),
  };

  let dataAberta = null;
  let vagas = { D: 0, N: 0, I_SUB: 0 };

  const dataBr = (iso) => iso.split('-').reverse().join('/');

  function fecharModal() {
    modal.style.display = 'none';
    dataAberta = null;
  }

  async function abrirModal(data) {
    dataAberta = data;
    tituloModal.textContent = dataBr(data);
    listaOcupantes.innerHTML = '<li class="vazio">Carregando…</li>';
    secaoRegistro.hidden = true;
    modal.style.display = 'flex';

    try {
      const dia = await requisitar(`api/api_get_details.php?date=${encodeURIComponent(data)}`);
      vagas = dia.vagas || { D: 0, N: 0, I_SUB: 0 };
      renderizarOcupantes(dia, data);
      renderizarRegistro(dia);
    } catch (erro) {
      listaOcupantes.innerHTML = `<li class="vazio">${escapar(erro.message)}</li>`;
    }
  }

  function renderizarOcupantes(dia, data) {
    const itens = [];
    if (dia.fixo_calculado) {
      const fixo = dia.fixo_calculado;
      const acao = fixo.tem_excecao
        ? `<button type="button" class="botao botao--pequeno btn-restore-fixo" data-bombeiro-id="${fixo.id}" data-date="${data}">${icone('restaurar')} Restaurar</button>`
        : `<button type="button" class="botao botao--pequeno botao--perigo btn-add-excecao-fixo" data-bombeiro-id="${fixo.id}" data-date="${data}" title="O Fixo não comparecerá neste dia">${icone('excecao')} Ausência</button>`;
      itens.push(`<li class="${fixo.tem_excecao ? 'tem-excecao' : ''}">${ETIQUETAS.FIXO}
        <span class="ocupante__nome">${escapar(fixo.nome_completo)}</span>
        <span class="ocupante__tipo">${fixo.tem_excecao ? 'ausente' : 'ciclo'}</span>${acao}</li>`);
    }
    (dia.extras || []).forEach((plantao) => {
      itens.push(`<li>${ETIQUETAS[plantao.turno] || ''}
        <span class="ocupante__nome">${escapar(plantao.nome_completo)}</span>
        <span class="ocupante__tipo">${escapar(plantao.tipo)}</span>
        <button type="button" class="botao botao--fantasma botao--icone botao--pequeno btn-remover-plantao" data-plantao-id="${plantao.plantao_id}" aria-label="Remover plantão de ${escapar(plantao.nome_completo)}">${icone('remover')}</button></li>`);
    });
    listaOcupantes.innerHTML = itens.length ? itens.join('') : '<li class="vazio">Ninguém escalado neste dia.</li>';
  }

  function renderizarRegistro(dia) {
    seletorBombeiro.innerHTML = '<option value="">Selecione…</option>';
    (dia.bombeiros_ativos || []).forEach((b) => {
      const opcao = document.createElement('option');
      opcao.value = b.id;
      opcao.textContent = `${b.nome_completo} (${b.tipo})`;
      seletorBombeiro.appendChild(opcao);
    });

    if (dia.proximo_sugerido?.id) {
      sugestao.textContent = `Sugestão pela ordem de escolha: ${dia.proximo_sugerido.nome}`;
      seletorBombeiro.value = String(dia.proximo_sugerido.id);
    } else {
      sugestao.textContent = '';
    }
    secaoRegistro.hidden = false;
    atualizarBotoesTurno();
  }

  function atualizarBotoesTurno() {
    const temBombeiro = seletorBombeiro.value !== '';
    const disponivel = {
      D: vagas.D > 0,
      N: vagas.N > 0,
      I: vagas.D > 0 && vagas.N > 0,
      I_SUB: vagas.I_SUB > 0,
    };
    const texto = {
      D: `${vagas.D} vaga${vagas.D === 1 ? '' : 's'}`,
      N: `${vagas.N} vaga${vagas.N === 1 ? '' : 's'}`,
      I: disponivel.I ? 'disponível' : 'indisponível',
      I_SUB: disponivel.I_SUB ? 'disponível' : 'indisponível',
    };
    Object.entries(botoesTurno).forEach(([turno, botao]) => {
      botao.querySelector('.vagas-restantes').textContent = texto[turno];
      botao.disabled = !(temBombeiro && disponivel[turno]);
    });
  }

  async function registrar(turno) {
    const data = dataAberta;
    if (!seletorBombeiro.value || !data) return;
    Object.values(botoesTurno).forEach((b) => { b.disabled = true; });
    try {
      await enviar('api/api_registrar_plantao.php', { bombeiro_id: seletorBombeiro.value, data, turno });
      avisar('Plantão registrado.', 'success');
      await atualizarDia(data);
    } catch (erro) {
      avisar(erro.message, 'error');
      atualizarBotoesTurno();
    }
  }

  async function atualizarDia(data) {
    fecharModal();
    try {
      renderizarCelula(data, await requisitar(`api/api_get_details.php?date=${encodeURIComponent(data)}`));
    } catch (erro) {
      avisar('Registro salvo, mas não foi possível atualizar o calendário. Recarregue a página.', 'error');
    }
  }

  // mesmo HTML que templates/escala.php gera para a célula do dia
  function renderizarCelula(data, dia) {
    const celula = document.querySelector(`.btn-detalhes[data-date="${data}"]`)?.closest('td');
    if (!celula) return;
    const v = dia.vagas || { D: 0, N: 0, I_SUB: 0 };
    const vaga = (rotulo, livre, titulo) => `<span class="vaga ${livre ? 'vaga--livre' : ''}" title="${titulo}">${rotulo}</span>`;

    celula.querySelector('[data-vagas]').innerHTML =
      vaga(`D ${v.D}`, v.D > 0, 'Vagas de BC no diurno') +
      vaga(`N ${v.N}`, v.N > 0, 'Vagas de BC no noturno') +
      (v.I_SUB > 0 ? vaga('SUB', true, 'Vaga de integral substituto do Fixo') : '');

    const ocupante = (etiqueta, nome) =>
      `<li class="ocupante">${etiqueta}<span class="ocupante__nome" title="${escapar(nome)}">${escapar(nome)}</span></li>`;
    let html = '';
    if (dia.fixo_calculado && !dia.fixo_calculado.tem_excecao) html += ocupante(ETIQUETAS.FIXO, dia.fixo_calculado.nome_completo);
    (dia.extras || []).forEach((p) => { html += ocupante(ETIQUETAS[p.turno] || '', p.nome_completo); });
    celula.querySelector('[data-ocupantes]').innerHTML = html;
  }

  document.querySelectorAll('.btn-detalhes').forEach((botao) => {
    botao.addEventListener('click', () => abrirModal(botao.dataset.date));
  });
  modal.querySelector('.close-button').addEventListener('click', fecharModal);
  modal.addEventListener('click', (evento) => { if (evento.target === modal) fecharModal(); });
  document.addEventListener('keydown', (evento) => { if (evento.key === 'Escape' && modal.style.display === 'flex') fecharModal(); });
  seletorBombeiro.addEventListener('change', atualizarBotoesTurno);
  Object.entries(botoesTurno).forEach(([turno, botao]) => botao.addEventListener('click', () => registrar(turno)));

  // ações dentro da lista de escalados
  listaOcupantes.addEventListener('click', async (evento) => {
    const botao = evento.target.closest('button');
    const data = dataAberta;
    if (!botao || !data) return;

    const acoes = {
      'btn-remover-plantao': ['Remover este plantão?', 'api/api_remover_plantao.php', { plantao_id: botao.dataset.plantaoId }, 'Plantão removido.'],
      'btn-add-excecao-fixo': ['Registrar ausência do Fixo neste dia? A vaga de substituto será liberada.', 'api/api_registrar_excecao_fixo.php', { bombeiro_id: botao.dataset.bombeiroId, data }, 'Ausência registrada.'],
      'btn-restore-fixo': ['Restaurar o Fixo ao ciclo neste dia?', 'api/api_remover_excecao_fixo.php', { bombeiro_id: botao.dataset.bombeiroId, data }, 'Fixo restaurado ao ciclo.'],
    };
    const classe = Object.keys(acoes).find((c) => botao.classList.contains(c));
    if (!classe) return;
    const [pergunta, url, dados, sucesso] = acoes[classe];

    if (!(await confirmar(pergunta))) return;
    try {
      await enviar(url, dados);
      avisar(sucesso, 'success');
      await atualizarDia(data);
    } catch (erro) {
      avisar(erro.message, 'error');
    }
  });

  // ---------------------------------------------------------------------------
  // Painel: ordem de escolha
  // ---------------------------------------------------------------------------
  const exibicaoSugerido = document.getElementById('displayProximoSugerido');

  document.getElementById('btnAvancarOrdem')?.addEventListener('click', async (evento) => {
    const botao = evento.currentTarget;
    botao.disabled = true;
    try {
      const resposta = await enviar('api/api_avancar_ordem.php');
      exibicaoSugerido.textContent = resposta.novo_nome || 'Nenhum';
      avisar(resposta.message || 'Ordem avançada.', 'success');
    } catch (erro) {
      avisar(erro.message, 'error');
    } finally {
      botao.disabled = false;
    }
  });

  document.getElementById('selectInicioOrdem')?.addEventListener('change', async (evento) => {
    const id = evento.currentTarget.value;
    if (!id) return;
    try {
      const resposta = await enviar('api/api_set_inicio_ordem.php', { inicio_id: id });
      exibicaoSugerido.textContent = resposta.proximo_nome || 'Nenhum';
      avisar('Início da ordem atualizado.', 'success');
    } catch (erro) {
      avisar(erro.message, 'error');
    }
  });
});
