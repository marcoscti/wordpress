<?php
if (!defined('ABSPATH')) exit;

function fs_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabela de Likes
    $table_likes = $wpdb->prefix . 'feed_social_likes';
    $sql_likes = "CREATE TABLE $table_likes (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        email varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_like (post_id, email)
    ) $charset_collate;";

    // Tabela de Comentários
    $table_comments = $wpdb->prefix . 'feed_social_comments';
    $sql_comments = "CREATE TABLE $table_comments (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        comment text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_likes);
    dbDelta($sql_comments);
    
    add_option('fs_db_version', FS_DB_VERSION);
}