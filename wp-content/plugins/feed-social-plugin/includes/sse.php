<?php
if (!defined('ABSPATH')) exit;

// Listener para novos posts
add_action('publish_feed-social', 'fs_trigger_sse_notification', 10, 2);

function fs_trigger_sse_notification($ID, $post) {
    set_transient('fs_new_post_event', [
        'id' => $ID,
        'title' => $post->post_title,
        'url' => get_permalink($ID),
        'thumbnail' => get_the_post_thumbnail_url($ID, 'thumbnail'),
        'date' => $post->post_date,
        'excerpt' => wp_trim_words($post->post_content, 20, '...') // Limita o resumo a 20 palavras
    ], 60);
}

// Registra a variável de consulta para identificar o endpoint
add_filter('query_vars', function($vars) {
    $vars[] = 'fs_sse';
    return $vars;
});

// Adiciona a regra de reescrita para uma URL limpa
add_action('init', function() {
    add_rewrite_rule('^feed-social-sse/?$', 'index.php?fs_sse=1', 'top');
});

// Endpoint SSE
add_action('template_redirect', function() {
    if (get_query_var('fs_sse') !== '1') {
        return;
    }

    // Desativa compressão e buffering que impedem o streaming em tempo real
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no'); // Importante para servidores Nginx
        header('Connection: keep-alive');

    $last_sent_id = 0;

        while (true) {
        if (connection_aborted()) break;

            $event_data = get_transient('fs_new_post_event');
            
        // Verifica se há evento e se ele é diferente do último enviado para este cliente
        if ($event_data && (isset($event_data['id']) && $event_data['id'] !== $last_sent_id)) {
                echo "event: new-content-feed\n";
                echo "data: " . json_encode($event_data) . "\n\n";
            $last_sent_id = $event_data['id'];
            }

        echo ": heartbeat\n\n"; // Envia um comentário para manter a conexão viva
        
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
            flush();
        sleep(2);
        }
        exit;
});