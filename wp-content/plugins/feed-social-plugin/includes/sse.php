<?php
if (!defined('ABSPATH')) exit;

define('FS_SSE_REWRITE_VERSION', '1.1.0');
define('FS_SSE_TRANSIENT', 'fs_new_post_event');

add_action('transition_post_status', 'fs_trigger_sse_on_publish', 10, 3);

function fs_trigger_sse_on_publish($new_status, $old_status, $post) {
    if ($new_status !== 'publish' || $old_status === 'publish' || $post->post_type !== 'feed-social') {
        return;
    }

    fs_trigger_sse_notification($post->ID, $post);
}

function fs_trigger_sse_notification($ID, $post) {
    if (is_numeric($post)) {
        $post = get_post($post);
    }

    if (!$post || $post->post_type !== 'feed-social') {
        return;
    }

    $event = [
        'id' => (int) $ID,
        'title' => $post->post_title,
        'url' => get_permalink($ID),
        'thumbnail' => get_the_post_thumbnail_url($ID, 'thumbnail') ?: '',
        'date' => $post->post_date,
        'excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 20, '...'),
    ];

    set_transient(FS_SSE_TRANSIENT, $event, 300);
}

function fs_get_sse_event_fresh_for_stream() {
    global $wpdb;

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        '_transient_' . FS_SSE_TRANSIENT
    ));

    if (!$row) {
        return null;
    }

    $timeout = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        '_transient_timeout_' . FS_SSE_TRANSIENT
    ));

    if (!$timeout || (int) $timeout < time()) {
        return null;
    }

    $event = maybe_unserialize($row->option_value);
    return is_array($event) && !empty($event['id']) ? $event : null;
}

function fs_run_sse_stream() {
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    @ini_set('output_buffering', 0);
    @ini_set('max_execution_time', 0);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    status_header(200);
    nocache_headers();
    header('Content-Type: text/event-stream; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');

    $last_sent_id = 0;
    $started_at = time();
    $max_runtime = 300;

    // Ignora eventos já existentes antes da conexão abrir.
    $existing = fs_get_sse_event_fresh_for_stream();
    if ($existing) {
        $last_sent_id = (int) $existing['id'];
    }

    while ((time() - $started_at) < $max_runtime) {
        if (connection_aborted()) {
            break;
        }

        $event_data = fs_get_sse_event_fresh_for_stream();

        if ($event_data && (int) $event_data['id'] !== $last_sent_id) {
            echo 'id: ' . (int) $event_data['id'] . "\n";
            echo "event: new-content-feed\n";
            echo 'data: ' . wp_json_encode($event_data) . "\n\n";
            $last_sent_id = (int) $event_data['id'];
        }

        echo ": heartbeat\n\n";
        flush();
        sleep(2);
    }

    exit;
}

add_filter('query_vars', function ($vars) {
    $vars[] = 'fs_sse';
    return $vars;
});

add_action('init', function () {
    add_rewrite_rule('^feed-social-sse/?$', 'index.php?fs_sse=1', 'top');

    if (get_option('fs_sse_rewrite_version') !== FS_SSE_REWRITE_VERSION) {
        flush_rewrite_rules(false);
        update_option('fs_sse_rewrite_version', FS_SSE_REWRITE_VERSION);
    }
});

add_action('template_redirect', function () {
    if ((string) get_query_var('fs_sse') !== '1') {
        return;
    }

    fs_run_sse_stream();
});

add_action('rest_api_init', function () {
    register_rest_route('feed-social/v1', '/events', [
        'methods' => 'GET',
        'callback' => '__return_null',
        'permission_callback' => '__return_true',
    ]);
});

add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if ($request->get_route() === '/feed-social/v1/events') {
        fs_run_sse_stream();
    }

    return $result;
}, 10, 3);

function fs_get_sse_url() {
    return get_rest_url(null, 'feed-social/v1/events');
}
