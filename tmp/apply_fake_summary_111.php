<?php
require __DIR__ . '/../wp-load.php';
$post_id = 111;
$fake = "Resumo de teste: Parecer sobre diretrizes e análise de conformidade administrativa; apresenta pontos-chave, recomendações e referências legais.";
update_post_meta($post_id, '_dj_ai_summary', $fake);
if (!get_post_meta($post_id, '_dj_original_content', true)) {
    $post = get_post($post_id);
    if ($post) update_post_meta($post_id, '_dj_original_content', $post->post_content);
}
$post = get_post($post_id);
if ($post) {
    $new = $post->post_content . "\n\nResumo IA: " . $fake;
    wp_update_post(['ID' => $post_id, 'post_content' => $new]);
}
update_post_meta($post_id, '_dj_ai_status', 'completed');
update_post_meta($post_id, '_dj_ai_error', '');
echo "Applied fake summary to post $post_id\n";
