<?php

declare(strict_types=1);

// Caixa de confirmação usada por script.js (confirmar()) no lugar do window.confirm.
?>
<div id="confirm-modal" class="confirmacao" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-text">
    <div class="confirmacao__caixa">
        <p id="confirm-modal-text">Confirma?</p>
        <div class="confirmacao__acoes">
            <button type="button" class="botao" id="confirm-modal-btn-no">Cancelar</button>
            <button type="button" class="botao botao--primario" id="confirm-modal-btn-yes">Confirmar</button>
        </div>
    </div>
</div>
