// Tutorial do painel (escala.php): destaca cada parte da tela com uma explicação.
// Abre sozinho no primeiro acesso do administrador; o botão "?" do topo reabre.
(function () {
  const script = document.currentScript;
  const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  const PASSOS = [
    {
      titulo: 'Bem-vindo à Escala de Plantões',
      texto: 'Este tour rápido mostra como o painel funciona. Leva cerca de um minuto. Você pode pular agora e rever quando quiser pelo botão de ajuda (?) no topo.',
    },
    {
      alvo: '[data-tutorial="nav"]',
      titulo: 'Menu principal',
      texto: '<b>Escala</b> é este painel. Em <b>Bombeiros</b> você cadastra as pessoas; em <b>Usuários</b>, os logins de acesso.',
    },
    {
      alvo: '[data-tutorial="mes"]',
      titulo: 'Meses',
      texto: 'Use as setas para ver e montar a escala de outros meses.',
    },
    {
      alvo: '[data-tutorial="legenda"]',
      titulo: 'Como ler o calendário',
      texto: '<b>Fixo</b> trabalha sozinho pelo ciclo 24x72. <b>D</b> é diurno e <b>N</b> noturno (12h cada); <b>24h</b> é integral. <b>SUB</b> é o integral que cobre a ausência do Fixo.',
    },
    {
      alvo: () => document.querySelector('.calendario td.hoje') || document.querySelector('.calendario td:not(.fora-do-mes)'),
      titulo: 'Cada dia',
      texto: 'Mostra quem está escalado e as vagas de BC livres (<b>D 1</b> = uma vaga no diurno). Clique no <b>+</b> do dia para registrar ou remover plantões e marcar a ausência do Fixo.',
    },
    {
      alvo: '[data-tutorial="ordem"]',
      titulo: 'Ordem de escolha',
      texto: 'Define qual BC escolhe o próximo plantão. Escolha quem começa a ordem e clique em <b>Avançar</b> quando a pessoa da vez terminar de escolher.',
    },
    {
      alvo: '[data-tutorial="planilha"]',
      titulo: 'Planilha e Excel',
      texto: '<b>Planilha</b> abre a planilha do mês para imprimir ou salvar em PDF (lá também se edita a assinatura do rodapé). <b>Excel</b> baixa a mesma planilha em .xlsx.',
    },
    {
      alvo: '[data-tutorial="nav-bombeiros"]',
      titulo: 'Bombeiros',
      texto: 'Cadastre BCs e Fixos. No Fixo, preencha na edição a <b>data de referência do ciclo</b>: sem ela o sistema não sabe os dias de serviço dele.',
    },
    {
      alvo: '[data-tutorial="nav-usuarios"]',
      titulo: 'Usuários',
      texto: 'Crie os logins. Ligue cada bombeiro ao seu login para que ele veja os próprios plantões na página <b>Minha escala</b>.',
    },
    {
      alvo: '[data-tutorial="acoes"]',
      titulo: 'Ajuda e tema',
      texto: 'O <b>?</b> reabre este tutorial quando quiser, e a lua alterna entre tema claro e escuro. Pronto, é isso!',
    },
  ];

  let indice = 0;
  let elementos = null;
  let alvoAtual = null;

  function criar() {
    const bloqueio = document.createElement('div');
    bloqueio.className = 'tutorial__bloqueio';

    const foco = document.createElement('div');
    foco.className = 'tutorial__foco';

    const cartao = document.createElement('div');
    cartao.className = 'tutorial__cartao';
    cartao.setAttribute('role', 'dialog');
    cartao.setAttribute('aria-modal', 'true');
    cartao.setAttribute('aria-labelledby', 'tutorial-titulo');
    cartao.innerHTML = `
      <div class="tutorial__progresso" aria-hidden="true"></div>
      <p class="tutorial__contador"></p>
      <h2 id="tutorial-titulo" class="tutorial__titulo"></h2>
      <p class="tutorial__texto"></p>
      <div class="tutorial__acoes">
        <button type="button" class="botao botao--fantasma botao--pequeno" data-acao="pular">Pular tutorial</button>
        <span class="tutorial__navegacao">
          <button type="button" class="botao botao--pequeno" data-acao="voltar">Voltar</button>
          <button type="button" class="botao botao--primario botao--pequeno" data-acao="proximo">Próximo</button>
        </span>
      </div>`;

    cartao.addEventListener('click', (evento) => {
      const acao = evento.target.closest('[data-acao]')?.dataset.acao;
      if (acao === 'pular') encerrar();
      if (acao === 'voltar') mostrar(indice - 1);
      if (acao === 'proximo') indice === PASSOS.length - 1 ? encerrar() : mostrar(indice + 1);
    });

    document.body.append(bloqueio, foco, cartao);
    return { bloqueio, foco, cartao };
  }

  function alvoDo(passo) {
    if (!passo.alvo) return null;
    const elemento = typeof passo.alvo === 'function' ? passo.alvo() : document.querySelector(passo.alvo);
    // item escondido (ex.: menu recolhido no celular) vira passo sem destaque
    return elemento && elemento.getClientRects().length ? elemento : null;
  }

  function mostrar(novoIndice) {
    indice = Math.max(0, Math.min(PASSOS.length - 1, novoIndice));
    const passo = PASSOS[indice];
    const { cartao } = elementos;

    cartao.querySelector('.tutorial__contador').textContent = `${indice + 1} de ${PASSOS.length}`;
    cartao.querySelector('.tutorial__titulo').textContent = passo.titulo;
    cartao.querySelector('.tutorial__texto').innerHTML = passo.texto; // textos fixos deste arquivo
    cartao.querySelector('.tutorial__progresso').style.setProperty('--progresso', `${((indice + 1) / PASSOS.length) * 100}%`);
    cartao.querySelector('[data-acao="voltar"]').hidden = indice === 0;
    cartao.querySelector('[data-acao="proximo"]').textContent = indice === 0 ? 'Começar' : indice === PASSOS.length - 1 ? 'Concluir' : 'Próximo';

    alvoAtual = alvoDo(passo);
    if (alvoAtual) alvoAtual.scrollIntoView({ block: 'center', inline: 'nearest' });
    requestAnimationFrame(posicionar);
    cartao.querySelector('[data-acao="proximo"]').focus({ preventScroll: true });
  }

  function posicionar() {
    if (!elementos) return;
    const { foco, cartao } = elementos;
    const margem = 16;
    const folga = 6;
    const largura = window.innerWidth;
    const altura = window.innerHeight;

    if (!alvoAtual) {
      // passo sem destaque: escurece tudo e centraliza o cartão
      foco.style.cssText = `top:${altura / 2}px;left:${largura / 2}px;width:0;height:0;`;
      cartao.style.top = `${Math.max(margem, (altura - cartao.offsetHeight) / 2)}px`;
      cartao.style.left = `${Math.max(margem, (largura - cartao.offsetWidth) / 2)}px`;
      return;
    }

    const r = alvoAtual.getBoundingClientRect();
    foco.style.cssText = `top:${r.top - folga}px;left:${r.left - folga}px;width:${r.width + folga * 2}px;height:${r.height + folga * 2}px;`;

    const abaixo = r.bottom + folga + 12;
    const cabeAbaixo = abaixo + cartao.offsetHeight <= altura - margem;
    const topo = cabeAbaixo ? abaixo : Math.max(margem, r.top - folga - 12 - cartao.offsetHeight);
    const esquerda = Math.min(Math.max(margem, r.left), largura - cartao.offsetWidth - margem);
    cartao.style.top = `${topo}px`;
    cartao.style.left = `${Math.max(margem, esquerda)}px`;
  }

  function aoTeclar(evento) {
    if (evento.key === 'Escape') encerrar();
    if (evento.key === 'ArrowRight') mostrar(indice + 1);
    if (evento.key === 'ArrowLeft') mostrar(indice - 1);
    if (evento.key === 'Tab') {
      // mantém o foco dentro do cartão enquanto o tutorial estiver aberto
      const botoes = [...elementos.cartao.querySelectorAll('button:not([hidden])')];
      const posicao = botoes.indexOf(document.activeElement);
      const proximo = evento.shiftKey ? posicao - 1 : posicao + 1;
      evento.preventDefault();
      botoes[(proximo + botoes.length) % botoes.length].focus();
    }
  }

  function iniciar() {
    if (elementos) return;
    elementos = criar();
    document.addEventListener('keydown', aoTeclar);
    window.addEventListener('resize', posicionar);
    window.addEventListener('scroll', posicionar, { passive: true });
    mostrar(0);
  }

  // pular ou concluir: não abre sozinho de novo para este administrador
  function encerrar() {
    if (!elementos) return;
    Object.values(elementos).forEach((elemento) => elemento.remove());
    elementos = null;
    alvoAtual = null;
    document.removeEventListener('keydown', aoTeclar);
    window.removeEventListener('resize', posicionar);
    window.removeEventListener('scroll', posicionar);

    if (new URLSearchParams(location.search).has('tutorial')) {
      const url = new URL(location.href);
      url.searchParams.delete('tutorial');
      history.replaceState(null, '', url);
    }
    fetch('api/api_concluir_tutorial.php', { method: 'POST', headers: { 'X-CSRF-Token': tokenCsrf } })
      .catch((erro) => console.error('Tutorial:', erro));
  }

  document.addEventListener('DOMContentLoaded', () => {
    // já está no painel: o "?" abre aqui mesmo, sem recarregar
    document.querySelectorAll('[data-abrir-tutorial]').forEach((link) => link.addEventListener('click', (evento) => {
      evento.preventDefault();
      iniciar();
    }));
    if (script && script.hasAttribute('data-abrir-sozinho')) iniciar();
  });
})();
