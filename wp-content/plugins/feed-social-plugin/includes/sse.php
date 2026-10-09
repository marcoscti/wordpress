<?php
if (!defined('ABSPATH')) exit;

define('FS_SSE_EVENT_TTL', 300);

add_action('transition_post_status', 'fs_trigger_sse_on_publish', 10, 3);
add_action('publish_feed-social', 'fs_trigger_sse_on_publish_action', 10, 2);
add_action('publish_social_story', 'fs_trigger_sse_on_publish_action', 10, 2);

    function fs_trigger_sse_on_publish($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish' || !in_array($post->post_type, ['feed-social', 'social_story'], true)) {
            return;
        }

    fs_trigger_sse_notification($post->ID, $post);
}

function fs_trigger_sse_on_publish_action($post_id, $post) {
    if (!$post || $post->post_status !== 'publish' || !in_array($post->post_type, ['feed-social', 'social_story'], true)) {
        return;
    }

    fs_trigger_sse_notification($post_id, $post);
}

    function fs_trigger_sse_notification($ID, $post) {
        if (is_numeric($post)) {
            $post = get_post($post);
        }

        if (!$post || !in_array($post->post_type, ['feed-social', 'social_story'], true)) {
            return;
        }

        $content_source = !empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content;
        $excerpt = wp_trim_words(wp_strip_all_tags($content_source), 20, '...');

        $event = [
            'id' => (int) $ID,
            'type' => $post->post_type,
            'title' => $post->post_title,
            'url' => get_permalink($ID),
            'thumbnail' => get_the_post_thumbnail_url($ID, 'thumbnail') ?: '',
            'date' => $post->post_date,
            'excerpt' => $excerpt,
            'timestamp' => time(),
        ];

        $event['expires'] = $event['timestamp'] + FS_SSE_EVENT_TTL;
        $event_file = fs_get_sse_event_file();
        $event_dir = dirname($event_file);

        if (!is_dir($event_dir) && !wp_mkdir_p($event_dir)) {
            error_log('Feed Social: não foi possível criar o diretório de eventos SSE.');
            return;
        }

        $lock = fopen($event_file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            error_log('Feed Social: não foi possível bloquear a fila de notificações.');
            return;
        }

        $events = [];
        if (is_readable($event_file)) {
            $stored_data = json_decode(file_get_contents($event_file), true);
            if (isset($stored_data['events']) && is_array($stored_data['events'])) {
                $events = $stored_data['events'];
            } elseif (isset($stored_data['id'])) {
                $events = [$stored_data];
            }
        }

        $now = time();
        $events = array_values(array_filter($events, function ($stored_event) use ($now) {
            return is_array($stored_event)
                && !empty($stored_event['id'])
                && !empty($stored_event['type'])
                && !empty($stored_event['expires'])
                && (int) $stored_event['expires'] > $now;
        }));
        $events = array_values(array_filter($events, function ($stored_event) use ($event) {
            return (int) $stored_event['id'] !== $event['id']
                || $stored_event['type'] !== $event['type'];
        }));
        $events[] = $event;

        $file = fopen($event_file, 'c+');
        if (!$file) {
            flock($lock, LOCK_UN);
            fclose($lock);
            error_log('Feed Social: não foi possível abrir o arquivo de notificações.');
            return;
        }

        $payload = wp_json_encode(['events' => $events]);
        if ($payload === false || !ftruncate($file, 0) || !rewind($file) || fwrite($file, $payload) === false || !fflush($file)) {
            error_log('Feed Social: não foi possível gravar a fila de notificações.');
        }
        fclose($file);
        flock($lock, LOCK_UN);
        fclose($lock);
}

function fs_get_sse_event_file() {
    $uploads = wp_upload_dir();

    return trailingslashit($uploads['basedir']) . 'feed-social-sse-event.json';
}
