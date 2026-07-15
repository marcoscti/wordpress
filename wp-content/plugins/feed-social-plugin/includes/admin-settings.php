<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=feed-social',
        'Feed Social - Métricas e Usuários',
        'Métricas e Usuários',
        'manage_options',
        'feed-social-metrics',
        'fs_settings_page_callback'
    );
});

function fs_settings_page_callback() {
    global $wpdb;

    $active_tab = 'posts';
    if (isset($_POST['tab']) && in_array(sanitize_key($_POST['tab']), ['posts', 'users'], true)) {
        $active_tab = sanitize_key($_POST['tab']);
    } elseif (isset($_GET['tab']) && in_array(sanitize_key($_GET['tab']), ['posts', 'users'], true)) {
        $active_tab = sanitize_key($_GET['tab']);
    }

    if (isset($_POST['fs_user_update']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'fs_update_user')) {
        $user_id = absint($_POST['user_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if ($user_id && $email) {
            $table = $wpdb->prefix . 'feed_social_users';
            $wpdb->update($table, ['name' => $name, 'email' => $email, 'updated_at' => current_time('mysql')], ['id' => $user_id], ['%s', '%s', '%s'], ['%d']);
        }
    }

    $users_table = $wpdb->prefix . 'feed_social_users';
    $users = $wpdb->get_results("SELECT id, name, email, created_at, updated_at FROM $users_table ORDER BY created_at DESC");

    $posts_query = new WP_Query([
        'post_type' => 'feed-social',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $base_url = add_query_arg('page', 'feed-social-metrics', admin_url('edit.php?post_type=feed-social'));

    echo '<div class="wrap"><h1>Métricas e Usuários do Feed Social</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="' . esc_url(add_query_arg(['tab' => 'posts'], $base_url)) . '" class="nav-tab' . ($active_tab === 'posts' ? ' nav-tab-active' : '') . '">Métricas dos posts</a>';
    echo '<a href="' . esc_url(add_query_arg(['tab' => 'users'], $base_url)) . '" class="nav-tab' . ($active_tab === 'users' ? ' nav-tab-active' : '') . '">Usuários</a>';
    echo '</h2>';

    if ($active_tab === 'users') {
        echo '<h2>Usuários cadastrados</h2><table class="widefat fixed" cellspacing="0"><thead><tr><th>Nome</th><th>Email</th><th>Curtidas</th><th>Comentários</th><th>Ações</th></tr></thead><tbody>';
        if ($users) {
            foreach ($users as $user) {
                echo '<tr><form method="post">';
                echo '<input type="hidden" name="tab" value="users">';
                echo '<input type="hidden" name="user_id" value="' . esc_attr($user->id) . '">';
                wp_nonce_field('fs_update_user');
                echo '<td><input type="text" name="name" value="' . esc_attr($user->name) . '" /></td>';
                echo '<td><input type="email" name="email" value="' . esc_attr($user->email) . '" /></td>';
                echo '<td>' . esc_html(fs_get_user_like_count($user->email)) . '</td>';
                echo '<td>' . esc_html(fs_get_user_comment_count($user->email)) . '</td>';
                echo '<td><button type="submit" name="fs_user_update" class="button button-primary">Salvar</button></td>';
                echo '</form></tr>';
            }
        } else {
            echo '<tr><td colspan="5">Nenhum usuário cadastrado.</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<h2>Métricas dos posts</h2><table class="widefat fixed" cellspacing="0"><thead><tr><th>Título</th><th>Visualizações</th><th>Curtidas</th><th>Comentários</th></tr></thead><tbody>';
        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $post_id = get_the_ID();
                echo '<tr><td>' . esc_html(get_the_title($post_id)) . '</td><td>' . esc_html(fs_get_views_count($post_id)) . '</td><td>' . esc_html(fs_get_likes_count($post_id)) . '</td><td>' . esc_html(fs_get_comments_count($post_id)) . '</td></tr>';
            }
            wp_reset_postdata();
        } else {
            echo '<tr><td colspan="4">Nenhum post encontrado.</td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}