<?php
if (!defined('ABSPATH')) exit;
?>
<section class="dcdc-ranking-shortcode dcdc-ranking-shortcode--cards" data-dcdc-ranking>
    <div class="dcdc-ranking-shortcode__header">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <div class="dcdc-ranking-shortcode__controls" role="group" aria-label="Modo de exibição do ranking">
            <button type="button" class="dcdc-ranking-shortcode__view-button is-active" data-ranking-view="cards" aria-pressed="true">Cards</button>
            <button type="button" class="dcdc-ranking-shortcode__view-button" data-ranking-view="list" aria-pressed="false">Lista</button>
        </div>
</div>
    <span class="dcdc-ranking-shortcode__status" data-ranking-status aria-live="polite">Carregando...</span>
    <div class="dcdc-ranking-shortcode__items" data-ranking-items></div>
    <dialog class="dcdc-ranking-modal" data-ranking-modal aria-label="Detalhes do participante">
        <div class="dcdc-ranking-modal__content">
            <button type="button" class="dcdc-ranking-modal__close" data-ranking-modal-close aria-label="Fechar detalhes">×</button>
            <div class="dcdc-ranking-modal__photo-wrap" data-ranking-modal-photo-wrap></div>
            <div class="dcdc-ranking-modal__details">
                <span class="dcdc-ranking-modal__position" data-ranking-modal-position></span>
                <span class="dcdc-ranking-modal__category-emoji" data-ranking-modal-emoji aria-hidden="true"></span>
                <h3 data-ranking-modal-name></h3>
                <p class="dcdc-ranking-modal__category" data-ranking-modal-category></p>
                <p class="dcdc-ranking-modal__unit" data-ranking-modal-unit></p>
                <p class="dcdc-ranking-modal__score" data-ranking-modal-score></p>
                <p class="dcdc-ranking-modal__description" data-ranking-modal-description></p>
                <p class="dcdc-ranking-modal__feedback" data-ranking-modal-feedback role="status" hidden></p>
                <button type="button" class="dcdc-ranking-modal__download" data-ranking-modal-download>Baixar cartão como imagem</button>
            </div>
        </div>
    </dialog>
</section>