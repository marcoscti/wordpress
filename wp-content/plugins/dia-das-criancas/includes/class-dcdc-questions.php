<?php
if (!defined('ABSPATH')) exit;

class DCDC_Questions {
    public static function init() {}

    public static function get_active($campaign) {
        global $wpdb;
        DCDC_DB::activate();
        $t = DCDC_DB::tables();
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, question_text, question_type, decade, position
             FROM {$t['questions']}
             WHERE campaign=%s AND active=1
             ORDER BY position ASC, id ASC",
            $campaign
        ));

        if (empty($questions)) {
            DCDC_DB::ensure_seeded();
            $questions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, question_text, question_type, decade, position
                 FROM {$t['questions']}
                 WHERE campaign=%s AND active=1
                 ORDER BY position ASC, id ASC",
                $campaign
            ));
        }

        foreach ($questions as &$q) {
            $q->answers = $wpdb->get_results($wpdb->prepare(
                "SELECT id, answer_text, points, position
                 FROM {$t['answers']}
                 WHERE question_id=%d AND active=1
                 ORDER BY position ASC, id ASC",
                $q->id
            ));
            foreach ($q->answers as &$a) {
                unset($a->points);
            }
        }
        return $questions;
    }

    public static function get_scoring_answers($question_id) {
        global $wpdb;
        $t = DCDC_DB::tables();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, points FROM {$t['answers']}
             WHERE question_id=%d AND active=1",
            $question_id
        ), OBJECT_K);
    }
}
