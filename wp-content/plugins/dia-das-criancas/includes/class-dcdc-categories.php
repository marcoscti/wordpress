<?php
if (!defined('ABSPATH')) exit;

class DCDC_Categories {
    public static function init() {}

    public static function find_by_score($campaign, $score) {
        global $wpdb;
        $t = DCDC_DB::tables();
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$t['categories']}
             WHERE campaign=%s AND active=1 AND %d BETWEEN min_points AND max_points
             ORDER BY position ASC LIMIT 1",
            $campaign, $score
        ));
    }
}
