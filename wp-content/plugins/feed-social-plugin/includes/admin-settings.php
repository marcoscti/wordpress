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

function fs_settings_page_callback()
{
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
    if (isset($_POST['fs_user_delete']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'fs_update_user')) {
        $user_id = absint($_POST['user_id'] ?? 0);
        if ($user_id) {
            $table = $wpdb->prefix . 'feed_social_users';
            $wpdb->delete($table, ['id' => $user_id], ['%d']);
        }
    }

    $users_table = $wpdb->prefix . 'feed_social_users';
    $users = $wpdb->get_results("SELECT id, name, email, created_at, updated_at FROM $users_table ORDER BY created_at DESC");

    // === CONSULTA COM SQL USANDO TABELAS PERSONALIZADAS ===
    // Pega os parâmetros de ordenação da URL
    $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
    $order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'DESC';

    // Valida os parâmetros permitidos
    $allowed_orderby = ['date', 'views', 'likes', 'comments'];
    if (!in_array($orderby, $allowed_orderby)) {
        $orderby = 'date';
    }
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

    // Tabelas personalizadas
    $posts_table = $wpdb->posts;
    $views_table = $wpdb->prefix . 'feed_social_views';
    $likes_table = $wpdb->prefix . 'feed_social_likes';
    $comments_table = $wpdb->prefix . 'feed_social_comments';

    // Query SQL usando as tabelas personalizadas
    $sql = "SELECT p.ID, p.post_title, p.post_date,
            COALESCE((SELECT COUNT(*) FROM $views_table WHERE post_id = p.ID), 0) as views,
            COALESCE((SELECT COUNT(*) FROM $likes_table WHERE post_id = p.ID), 0) as likes,
            COALESCE((SELECT COUNT(*) FROM $comments_table WHERE post_id = p.ID), 0) as comments
            FROM $posts_table p
            WHERE p.post_type = 'feed-social' 
            AND p.post_status = 'publish'";

    // Adiciona a ordenação
    switch ($orderby) {
        case 'views':
            $sql .= " ORDER BY views $order, p.post_date DESC";
            break;
        case 'likes':
            $sql .= " ORDER BY likes $order, p.post_date DESC";
            break;
        case 'comments':
            $sql .= " ORDER BY comments $order, p.post_date DESC";
            break;
        default: // date
            $sql .= " ORDER BY p.post_date $order";
    }

    $posts = $wpdb->get_results($sql);
    // === FIM DA CONSULTA SQL ===

    $base_url = add_query_arg('page', 'feed-social-metrics', admin_url('edit.php?post_type=feed-social'));

    echo '<div class="wrap"><h1>Métricas e Usuários do Iges+</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="' . esc_url(add_query_arg(['tab' => 'posts'], $base_url)) . '" class="nav-tab' . ($active_tab === 'posts' ? ' nav-tab-active' : '') . '">Métricas dos posts</a>';
    echo '<a href="' . esc_url(add_query_arg(['tab' => 'users'], $base_url)) . '" class="nav-tab' . ($active_tab === 'users' ? ' nav-tab-active' : '') . '">Usuários</a>';
    echo '</h2>';

    if ($active_tab === 'users') {
        echo '<h2>Usuários cadastrados: ' . count($users) . '</h2>';
        echo '<table class="widefat fixed" cellspacing="0" style="margin-top:10px;">
        <thead>
          <tr>
           <th>Nome</th>
           <th>Email</th>
           <th>Curtidas</th>
           <th>Comentários</th>
           <th>Ações</th>
          </tr>
        </thead>
        <tbody>';
        if ($users) {
            foreach ($users as $user) {
                $like_count = fs_get_user_like_count($user->email);
                $comment_count = fs_get_user_comment_count($user->email);

                echo '<tr><form method="post">';
                echo '<input type="hidden" name="tab" value="users">';
                echo '<input type="hidden" name="user_id" value="' . esc_attr($user->id) . '">';
                wp_nonce_field('fs_update_user');
                echo '<td><input type="text" name="name" value="' . esc_attr($user->name) . '" /></td>';
                echo '<td><input type="email" name="email" value="' . esc_attr($user->email) . '" /></td>';
                echo '<td>' . esc_html($like_count) . '</td>';
                echo '<td>' . esc_html($comment_count) . '</td>';
                echo '<td><button type="submit" name="fs_user_update" class="button button-primary"><span class="dashicons dashicons-insert"></span> Salvar</button>&nbsp;
                <button type="submit" name="fs_user_delete" class="button button-danger" style="border-color:red; color:red;" onclick="return confirm(\'Tem certeza que deseja excluir este usuário?\')"><span class="dashicons dashicons-trash"></span> Excluir</button></td>';
                echo '</form></tr>';
            }
        } else {
            echo '<tr><td colspan="5">Nenhum usuário cadastrado.</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        // === TABELA DE POSTS COM LINKS DE ORDENAÇÃO ===
        $current_orderby = $orderby;
        $current_order = $order;

        echo '<h2>Métricas dos posts</h2>';

        // Mostra o filtro atual
        $orderby_label = [
            'date' => 'Data',
            'views' => 'Visualizações',
            'likes' => 'Curtidas',
            'comments' => 'Comentários'
        ];
        $order_label = $order === 'DESC' ? 'decrescente (maior para menor)' : 'crescente (menor para maior)';
        echo '<p>📊 Ordenado por: <strong>' . esc_html($orderby_label[$orderby] ?? $orderby) . '</strong> em ordem <strong>' . esc_html($order_label) . '</strong></p>';

        echo '<table class="widefat fixed" cellspacing="0">
        <thead>
        <tr>
           <th style="width: 30%;">Título</th>
           <th style="width: 17.5%;">
               Visualizações 
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'views', 'order' => 'DESC'], $base_url)) . '" title="Ordenar por visualizações (maior para menor)" style="font-size:18px;padding:5px">↓</a>
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'views', 'order' => 'ASC'], $base_url)) . '" title="Ordenar por visualizações (menor para maior)" style="font-size:18px;padding:5px">↑</a>
               ' . ($current_orderby === 'views' ? '<span style="color:#0073aa;font-size:18px;padding:5px">✓</span>' : '') . '
           </th>
           <th style="width: 17.5%;">
               Curtidas
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'likes', 'order' => 'DESC'], $base_url)) . '" title="Ordenar por curtidas (maior para menor)" style="font-size:18px;padding:5px">↓</a>
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'likes', 'order' => 'ASC'], $base_url)) . '" title="Ordenar por curtidas (menor para maior)" style="font-size:18px;padding:5px">↑</a>
               ' . ($current_orderby === 'likes' ? '<span style="color:#0073aa;">✓</span>' : '') . '
           </th>
           <th style="width: 17.5%;">
               Comentários
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'comments', 'order' => 'DESC'], $base_url)) . '" title="Ordenar por comentários (maior para menor)" style="font-size:18px;padding:5px">↓</a>
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'comments', 'order' => 'ASC'], $base_url)) . '" title="Ordenar por comentários (menor para maior)" style="font-size:18px;padding:5px">↑</a>
               ' . ($current_orderby === 'comments' ? '<span style="color:#0073aa;" style="font-size:18px;padding:5px">✓</span>' : '') . '
           </th>
           <th style="width: 17.5%;">
               Data
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'date', 'order' => 'DESC'], $base_url)) . '" title="Ordenar por data (mais recentes)" style="font-size:18px;padding:5px">↓</a>
               <a href="' . esc_url(add_query_arg(['tab' => 'posts', 'orderby' => 'date', 'order' => 'ASC'], $base_url)) . '" title="Ordenar por data (mais antigos)" style="font-size:18px;padding:5px">↑</a>
               ' . ($current_orderby === 'date' ? '<span style="color:#0073aa;font-size:18px;padding:5px;">✓</span>' : '') . '
           </th>
        </tr>
        </thead>
        <tbody>';

        if ($posts) {
            foreach ($posts as $post) {
                $post_id = $post->ID;
                $views = (int)$post->views;
                $likes = (int)$post->likes;
                $comments = (int)$post->comments;
                $title = $post->post_title ?: '(Sem título)';
                $date = date_i18n('d/m/Y H:i', strtotime($post->post_date));

                echo '<tr>';
                echo '<td><strong>' . esc_html($title) . '</strong></td>';
                echo '<td>' . esc_html(number_format($views)) . '</td>';
                echo '<td>' . esc_html(number_format($likes)) . '</td>';
                echo '<td>' . esc_html(number_format($comments)) . '</td>';
                echo '<td>' . esc_html($date) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">Nenhum post encontrado.</td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}
