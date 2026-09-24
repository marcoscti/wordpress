<?php
if (!defined('ABSPATH')) exit;

class DCDC_Quiz {
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'routes'));
    }

    public static function routes() {
        register_rest_route('dcdc/v1', '/quiz/(?P<campaign>[a-zA-Z0-9_-]+)/questions', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'questions'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('dcdc/v1', '/quiz/(?P<campaign>[a-zA-Z0-9_-]+)/result', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'result'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('dcdc/v1', '/quiz/(?P<campaign>[a-zA-Z0-9_-]+)/participant', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'participant'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('dcdc/v1', '/quiz/(?P<campaign>[a-zA-Z0-9_-]+)/ranking', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'ranking'),
            'permission_callback' => '__return_true',
        ));
    }

    public static function questions($request) {
        $campaign = sanitize_key($request['campaign']);
        $questions = DCDC_Questions::get_active($campaign);

        return new WP_REST_Response(array(
            'success' => true,
            'campaign' => $campaign,
            'total' => count($questions),
            'questions' => $questions,
        ), 200);
    }

    public static function result($request) {
        $campaign = sanitize_key($request['campaign']);
        $answers = $request->get_param('answers');

        $result = self::calculate_result($campaign, $answers);
        if (is_wp_error($result)) {
            return $result;
        }

        $category = DCDC_Categories::find_by_score($campaign, $result['score']);

        return new WP_REST_Response(array(
            'success' => true,
            'score' => (int) $result['score'],
            'category' => $category ? array(
                'id' => (int) $category->id,
                'name' => $category->name,
                'emoji' => $category->emoji,
                'description' => $category->description,
            ) : null,
        ), 200);
    }

    public static function participant($request) {
        $campaign = sanitize_key($request['campaign']);

        if (!wp_verify_nonce($request->get_header('X-WP-Nonce') ?: '', 'wp_rest')) {
            return new WP_Error('dcdc_invalid_nonce', 'Nonce inválido.', array('status' => 401));
        }

        $name = sanitize_text_field(wp_unslash($request->get_param('name')));
        $answers = $request->get_param('answers');

        if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 80) {
            return new WP_Error('dcdc_invalid_name', 'Informe um nome válido com 2 a 80 caracteres.', array('status' => 400));
        }

        $result = self::calculate_result($campaign, $answers);
        if (is_wp_error($result)) {
            return $result;
        }

        $category = DCDC_Categories::find_by_score($campaign, $result['score']);
        $photo_id = null;

        $file_params = $request->get_file_params();
        if (!empty($file_params['photo'])) {
            $photo_id = self::upload_photo($file_params['photo']);
            if (is_wp_error($photo_id)) {
                return $photo_id;
            }
        }

        global $wpdb;
        $t = DCDC_DB::tables();

        $inserted = $wpdb->insert($t['participants'], array(
            'campaign' => $campaign,
            'name' => $name,
            'score' => (int) $result['score'],
            'category_id' => $category ? (int) $category->id : null,
            'photo_id' => $photo_id,
            'status' => 'approved',
            'created_at' => current_time('mysql'),
        ));

        if (!$inserted) {
            return new WP_Error('dcdc_participant_insert_failed', 'Não foi possível salvar o participante.', array('status' => 500));
        }

        $participant = self::get_ranking_entry($campaign, (int) $wpdb->insert_id);

        return new WP_REST_Response(array(
            'success' => true,
            'participant' => $participant,
        ), 200);
    }

    public static function ranking($request) {
        $campaign = sanitize_key($request['campaign']);
        $limit = absint($request->get_param('limit')) ?: 10;
        $limit = max(1, min(25, $limit));

        global $wpdb;
        $t = DCDC_DB::tables();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.name AS category_name, c.emoji AS category_emoji, c.description AS category_description
             FROM {$t['participants']} p
             LEFT JOIN {$t['categories']} c ON c.id = p.category_id
             WHERE p.campaign = %s AND p.status = 'approved'
             ORDER BY p.score DESC, p.created_at ASC
             LIMIT %d",
            $campaign,
            $limit
        ));

        $participants = array();
        foreach ($rows as $index => $row) {
            $participants[] = array(
                'id' => (int) $row->id,
                'name' => $row->name,
                'score' => (int) $row->score,
                'category' => $row->category_name ? array(
                    'name' => $row->category_name,
                    'emoji' => $row->category_emoji,
                    'description' => $row->category_description,
                ) : null,
                'photo_url' => $row->photo_id ? wp_get_attachment_image_url((int) $row->photo_id, 'medium') : '',
                'position' => $index + 1,
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'campaign' => $campaign,
            'participants' => $participants,
        ), 200);
    }

    private static function calculate_result($campaign, $answers) {
        if (is_string($answers)) {
            $answers = json_decode(wp_unslash($answers), true);
        }
        if (!is_array($answers) || empty($answers)) {
            return new WP_Error('dcdc_invalid_answers', 'Respostas inválidas.', array('status' => 400));
        }

        $score = 0;
        $answered = 0;

        foreach ($answers as $question_id => $answer_id) {
            $question_id = absint($question_id);
            $answer_id = absint($answer_id);
            if (!$question_id || !$answer_id) {
                continue;
            }

            $valid = self::validate_answer($question_id, $answer_id);
            if ($valid === false) {
                continue;
            }

            $score += (int) $valid;
            $answered++;
        }

        $total_questions = count(DCDC_Questions::get_active($campaign));
        if ($answered !== $total_questions) {
            return new WP_Error(
                'dcdc_incomplete_quiz',
                'Responda todas as perguntas antes de ver o resultado.',
                array('status' => 400)
            );
        }

        return array('score' => (int) $score, 'answered' => $answered);
    }

    private static function validate_answer($question_id, $answer_id) {
        global $wpdb;
        $t = DCDC_DB::tables();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT points FROM {$t['answers']}
             WHERE id = %d AND question_id = %d AND active = 1 LIMIT 1",
            $answer_id,
            $question_id
        ));
        return $row ? (int) $row->points : false;
    }

    public static function upload_photo($file) {
        if (empty($file['name'])) {
            return new WP_Error('dcdc_invalid_photo', 'Arquivo de imagem inválido.', array('status' => 400));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $allowed = array('jpg', 'jpeg', 'png', 'webp');
        $file_type = wp_check_filetype($file['name'], array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'));

        if (empty($file_type['ext']) || !in_array(strtolower($file_type['ext']), $allowed, true)) {
            return new WP_Error('dcdc_invalid_photo_type', 'Formato de imagem inválido. Use JPG, PNG ou WEBP.', array('status' => 400));
        }

        if (!empty($file['size']) && $file['size'] > 2 * 1024 * 1024) {
            return new WP_Error('dcdc_invalid_photo_size', 'A foto deve ter até 2MB.', array('status' => 400));
        }

        $upload = wp_handle_upload($file, array('test_form' => false));
        if (isset($upload['error'])) {
            return new WP_Error('dcdc_upload_failed', $upload['error'], array('status' => 400));
        }

        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }

        $metadata = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $metadata);

        return (int) $attach_id;
    }

    private static function get_ranking_entry($campaign, $participant_id) {
        global $wpdb;
        $t = DCDC_DB::tables();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name AS category_name, c.emoji AS category_emoji, c.description AS category_description
             FROM {$t['participants']} p
             LEFT JOIN {$t['categories']} c ON c.id = p.category_id
             WHERE p.campaign = %s AND p.id = %d",
            $campaign,
            $participant_id
        ));

        if (!$row) {
            return null;
        }

        return array(
            'id' => (int) $row->id,
            'name' => $row->name,
            'score' => (int) $row->score,
            'category' => $row->category_name ? array(
                'name' => $row->category_name,
                'emoji' => $row->category_emoji,
                'description' => $row->category_description,
            ) : null,
            'photo_url' => $row->photo_id ? wp_get_attachment_image_url((int) $row->photo_id, 'medium') : '',
            'status' => $row->status,
        );
    }
}
