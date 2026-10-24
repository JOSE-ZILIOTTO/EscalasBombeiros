// Tema claro/escuro compartilhado por todas as telas. Fica no <head> (sem defer) e marca o
// <html> antes da primeira pintura, para a tela não "piscar" clara antes de ficar escura.
(function () {
  const CHAVE = 'theme';
  const raiz = document.documentElement;

  function preferido() {
    let salvo = null;
    try { salvo = localStorage.getItem(CHAVE); } catch (e) { /* armazenamento bloqueado */ }
    if (salvo === 'dark' || salvo === 'light') return salvo;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  raiz.classList.toggle('dark-mode', preferido() === 'dark');

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-alternar-tema]').forEach((botao) => {
      botao.addEventListener('click', () => {
        const escuro = raiz.classList.toggle('dark-mode');
        try { localStorage.setItem(CHAVE, escuro ? 'dark' : 'light'); } catch (e) { /* segue sem lembrar */ }
      });
    });
  });
})();
