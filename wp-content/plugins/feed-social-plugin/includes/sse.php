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
    ];

    $event['expires'] = time() + FS_SSE_EVENT_TTL;
    $event_file = fs_get_sse_event_file();

    if (!is_dir(dirname($event_file))) {
        wp_mkdir_p(dirname($event_file));
    }

    file_put_contents($event_file, wp_json_encode($event), LOCK_EX);
}

function fs_get_sse_event_file() {
    $uploads = wp_upload_dir();

    return trailingslashit($uploads['basedir']) . 'feed-social-sse-event.json';
}
