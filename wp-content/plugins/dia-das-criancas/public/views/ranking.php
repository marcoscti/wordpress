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
</section>