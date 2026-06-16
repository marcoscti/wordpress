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

// Endpoint SSE
add_action('init', function() {
    if (strpos($_SERVER['REQUEST_URI'], '/feed-social-sse') !== false) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        while (true) {
            $event_data = get_transient('fs_new_post_event');
            
            if ($event_data) {
                echo "event: new-content-feed\n";
                echo "data: " . json_encode($event_data) . "\n\n";
                delete_transient('fs_new_post_event');
            }

            if (connection_aborted()) break;
            
            echo "feed\n\n";
            ob_flush();
            flush();
            sleep(1);
        }
        exit;
    }
});

add_action('init', function() {
    add_rewrite_rule('^feed-social-sse/?$', 'index.php?fs_sse=1', 'top');
});