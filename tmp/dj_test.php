<?php
require __DIR__ . '/../wp-load.php';
$url = 'http://10.84.111.42:8080/wp-content/uploads/2026/08/Parecer-Juridico-Referencial-SEI-GDF-n.o-196462777-2026.pdf';
echo 'DJ_API_KEY:' . (get_option('dj_ai_api_key') ?: '(not set)') . PHP_EOL;
$r = wp_remote_get($url, ['timeout'=>20]);
if (is_wp_error($r)) { echo 'FETCH_ERROR:' . $r->get_error_message() . PHP_EOL; }
else { echo 'FETCH_HTTP:' . wp_remote_retrieve_response_code($r) . ' bodylen=' . strlen(wp_remote_retrieve_body($r)) . PHP_EOL; }
update_post_meta(111,'_dj_ai_status','pending');
do_action('dj_process_ai_document',111);
echo '---STATUS---' . PHP_EOL . get_post_meta(111,'_dj_ai_status',true) . PHP_EOL;
echo '---ERROR---' . PHP_EOL . get_post_meta(111,'_dj_ai_error',true) . PHP_EOL;
echo '---SUMMARY---' . PHP_EOL . get_post_meta(111,'_dj_ai_summary',true) . PHP_EOL;
