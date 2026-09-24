<?php
if (!defined('ABSPATH')) exit;

class DCDC_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
    }

    public static function menu() {
        add_menu_page('Dia das Crianças', 'Dia das Crianças', 'manage_options', 'dcdc', array(__CLASS__, 'dashboard'), 'dashicons-smiley', 30);
        add_submenu_page('dcdc', 'Perguntas', 'Perguntas', 'manage_options', 'dcdc-questions', array(__CLASS__, 'questions'));
        add_submenu_page('dcdc', 'Categorias', 'Categorias', 'manage_options', 'dcdc-categories', array(__CLASS__, 'categories'));
        add_submenu_page('dcdc', 'Participantes', 'Participantes', 'manage_options', 'dcdc-participants', array(__CLASS__, 'participants'));
    }

    public static function dashboard() {
        global $wpdb;
        $t = DCDC_DB::tables();
        $counts = array(
            'Perguntas' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['questions']}"),
            'Categorias' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['categories']}"),
            'Participantes' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['participants']}"),
        );

        echo '<div class="wrap"><h1>Dia das Crianças</h1><p><strong>Quanto de criança ainda existe em você?</strong></p><div style="display:flex;gap:16px;margin-top:20px">';
        foreach ($counts as $label => $value) {
            echo '<div style="background:#fff;border:1px solid #ddd;padding:20px;min-width:160px"><strong style="font-size:28px">'.(int) $value.'</strong><br>'.esc_html($label).'</div>';
        }
        echo '</div><p style="margin-top:25px">Shortcode da campanha: <code>[dcdc_quiz]</code></p></div>';
    }

    private static function notice($message, $type = 'updated') {
        echo '<div class="notice '.esc_attr($type).' is-dismissible"><p>'.esc_html($message).'</p></div>';
    }

    private static function question_form($question = null) {
        global $wpdb;
        $t = DCDC_DB::tables();
        $id = $question ? (int) $question->id : 0;
        $answers = $id ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['answers']} WHERE question_id=%d ORDER BY position,id", $id)) : array();
        echo '<h2>'.($id ? 'Editar pergunta' : 'Nova pergunta').'</h2><form method="post">';
        wp_nonce_field('dcdc_save_question');
        echo '<input type="hidden" name="dcdc_action" value="save_question"><input type="hidden" name="id" value="'.(int) $id.'">';
        echo '<table class="form-table"><tr><th><label for="dcdc-question-text">Pergunta</label></th><td><textarea class="large-text" id="dcdc-question-text" name="question_text" rows="3" required>'.esc_textarea($question ? $question->question_text : '').'</textarea></td></tr>';
        echo '<tr><th><label for="dcdc-question-decade">Década</label></th><td><input id="dcdc-question-decade" name="decade" value="'.esc_attr($question ? $question->decade : '').'"></td></tr>';
        echo '<tr><th><label for="dcdc-question-position">Posição</label></th><td><input type="number" min="0" id="dcdc-question-position" name="position" value="'.esc_attr($question ? $question->position : 0).'" required></td></tr>';
        echo '<tr><th><label for="dcdc-question-active">Status</label></th><td><label><input type="checkbox" id="dcdc-question-active" name="active" value="1" '.checked($question ? $question->active : 1, 1, false).'> Ativa</label></td></tr>';
        echo '<tr><th>Respostas</th><td><p class="description">Uma por linha no formato: texto | pontos</p><textarea class="large-text code" name="answers" rows="8" required>';
        foreach ($answers as $answer) echo esc_textarea($answer->answer_text.' | '.$answer->points)."\n";
        echo '</textarea></td></tr></table><p><button class="button button-primary" type="submit">Salvar pergunta</button> <a class="button" href="'.esc_url(admin_url('admin.php?page=dcdc-questions')).'">Cancelar</a></p></form>';
    }

    public static function questions() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $t = DCDC_DB::tables();
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['dcdc_action'])) {
            check_admin_referer('dcdc_save_question');
            $id = absint($_POST['id'] ?? 0);
            $lines = preg_split('/\r\n|\r|\n/', wp_unslash($_POST['answers'] ?? ''));
            $parsed = array();
            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line, 2));
                if ($parts[0] !== '') $parsed[] = array('text' => sanitize_text_field($parts[0]), 'points' => isset($parts[1]) ? (int) $parts[1] : 0);
            }
            $data = array('question_text' => sanitize_textarea_field(wp_unslash($_POST['question_text'] ?? '')), 'question_type' => 'single', 'decade' => sanitize_text_field(wp_unslash($_POST['decade'] ?? '')), 'position' => absint($_POST['position'] ?? 0), 'active' => isset($_POST['active']) ? 1 : 0, 'updated_at' => current_time('mysql'));
            if (!$data['question_text'] || !$parsed) self::notice('Informe a pergunta e pelo menos uma resposta.', 'error');
            else {
                if ($id) $wpdb->update($t['questions'], $data, array('id' => $id));
                else { $data['campaign'] = 'dia-das-criancas-2026'; $data['created_at'] = current_time('mysql'); $wpdb->insert($t['questions'], $data); $id = (int) $wpdb->insert_id; }
                $wpdb->delete($t['answers'], array('question_id' => $id));
                foreach ($parsed as $position => $answer) $wpdb->insert($t['answers'], array('question_id' => $id, 'answer_text' => $answer['text'], 'points' => $answer['points'], 'position' => $position + 1, 'active' => 1));
                self::notice('Pergunta salva.');
            }
        }
        if (isset($_GET['delete'])) { check_admin_referer('dcdc_delete_question_'.absint($_GET['delete'])); $wpdb->delete($t['answers'], array('question_id' => absint($_GET['delete']))); $wpdb->delete($t['questions'], array('id' => absint($_GET['delete']))); self::notice('Pergunta excluída.'); }
        $edit = isset($_GET['edit']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['questions']} WHERE id=%d", absint($_GET['edit']))) : null;
        echo '<div class="wrap"><h1>Perguntas</h1>';
        self::question_form($edit);
        $rows = $wpdb->get_results("SELECT * FROM {$t['questions']} ORDER BY position,id");
        echo '<h2>Perguntas cadastradas</h2><table class="widefat striped"><thead><tr><th>#</th><th>Pergunta</th><th>Respostas</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $row) { $edit_url = admin_url('admin.php?page=dcdc-questions&edit='.(int) $row->id); $delete_url = wp_nonce_url(admin_url('admin.php?page=dcdc-questions&delete='.(int) $row->id), 'dcdc_delete_question_'.(int) $row->id); echo '<tr><td>'.(int) $row->position.'</td><td>'.esc_html($row->question_text).'</td><td>'.(int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['answers']} WHERE question_id=%d", $row->id)).'</td><td>'.($row->active ? 'Ativa' : 'Inativa').'</td><td><a href="'.esc_url($edit_url).'">Editar</a> | <a href="'.esc_url($delete_url).'" onclick="return confirm(\'Excluir esta pergunta?\')">Excluir</a></td></tr>'; }
        if (!$rows) echo '<tr><td colspan="5">Nenhuma pergunta cadastrada.</td></tr>';
        echo '</tbody></table></div>';
    }

    public static function categories() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $t = DCDC_DB::tables();
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['dcdc_category_action'])) {
            check_admin_referer('dcdc_save_category');
            $id = absint($_POST['id'] ?? 0);
            $data = array('name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')), 'emoji' => sanitize_text_field(wp_unslash($_POST['emoji'] ?? '')), 'min_points' => (int) ($_POST['min_points'] ?? 0), 'max_points' => (int) ($_POST['max_points'] ?? 0), 'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')), 'position' => absint($_POST['position'] ?? 0), 'active' => isset($_POST['active']) ? 1 : 0);
            if (!$data['name'] || $data['min_points'] > $data['max_points']) self::notice('Informe o nome e uma faixa de pontos válida.', 'error');
            else { if ($id) $wpdb->update($t['categories'], $data, array('id' => $id)); else { $data['campaign'] = 'dia-das-criancas-2026'; $wpdb->insert($t['categories'], $data); } self::notice('Categoria salva.'); }
        }
        if (isset($_GET['delete'])) { check_admin_referer('dcdc_delete_category_'.absint($_GET['delete'])); $wpdb->delete($t['categories'], array('id' => absint($_GET['delete']))); self::notice('Categoria excluída.'); }
        $edit = isset($_GET['edit']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['categories']} WHERE id=%d", absint($_GET['edit']))) : null;
        echo '<div class="wrap"><h1>Categorias</h1><h2>'.($edit ? 'Editar categoria' : 'Nova categoria').'</h2><form method="post">'; wp_nonce_field('dcdc_save_category'); echo '<input type="hidden" name="dcdc_category_action" value="save"><input type="hidden" name="id" value="'.(int) ($edit ? $edit->id : 0).'">';
        echo '<table class="form-table"><tr><th>Nome</th><td><input class="regular-text" name="name" required value="'.esc_attr($edit ? $edit->name : '').'"></td></tr><tr><th>Emoji</th><td><input name="emoji" value="'.esc_attr($edit ? $edit->emoji : '').'"></td></tr><tr><th>Faixa de pontos</th><td><input type="number" name="min_points" value="'.esc_attr($edit ? $edit->min_points : 0).'" required> até <input type="number" name="max_points" value="'.esc_attr($edit ? $edit->max_points : 100).'" required></td></tr><tr><th>Descrição</th><td><textarea class="large-text" name="description" rows="3">'.esc_textarea($edit ? $edit->description : '').'</textarea></td></tr><tr><th>Posição</th><td><input type="number" name="position" value="'.esc_attr($edit ? $edit->position : 0).'" required></td></tr><tr><th>Status</th><td><label><input type="checkbox" name="active" value="1" '.checked($edit ? $edit->active : 1, 1, false).'> Ativa</label></td></tr></table><p><button class="button button-primary">Salvar categoria</button></p></form>';
        $rows = $wpdb->get_results("SELECT * FROM {$t['categories']} ORDER BY position,id"); echo '<h2>Categorias cadastradas</h2><table class="widefat striped"><thead><tr><th>Categoria</th><th>Faixa</th><th>Descrição</th><th>Status</th><th></th></tr></thead><tbody>'; foreach ($rows as $row) { $edit_url = admin_url('admin.php?page=dcdc-categories&edit='.(int) $row->id); $delete_url = wp_nonce_url(admin_url('admin.php?page=dcdc-categories&delete='.(int) $row->id), 'dcdc_delete_category_'.(int) $row->id); echo '<tr><td>'.esc_html($row->emoji.' '.$row->name).'</td><td>'.(int) $row->min_points.'–'.(int) $row->max_points.'</td><td>'.esc_html($row->description).'</td><td>'.($row->active ? 'Ativa' : 'Inativa').'</td><td><a href="'.esc_url($edit_url).'">Editar</a> | <a href="'.esc_url($delete_url).'" onclick="return confirm(\'Excluir esta categoria?\')">Excluir</a></td></tr>'; } echo '</tbody></table></div>';
    }

    private static function participant_form($participant) {
        $id = (int) $participant->id;
        $photo_url = $participant->photo_id ? wp_get_attachment_image_url((int) $participant->photo_id, 'thumbnail') : '';
        echo '<h2>Editar participante</h2><form method="post" enctype="multipart/form-data">';
        wp_nonce_field('dcdc_save_participant');
        echo '<input type="hidden" name="dcdc_participant_action" value="save"><input type="hidden" name="id" value="'.$id.'">';
        echo '<table class="form-table"><tr><th><label for="dcdc-participant-name">Nome</label></th><td><input class="regular-text" id="dcdc-participant-name" name="name" maxlength="80" required value="'.esc_attr($participant->name).'">';
        echo '</td></tr><tr><th><label for="dcdc-participant-status">Status</label></th><td><select id="dcdc-participant-status" name="status">';
        foreach (array('approved' => 'Aprovado', 'pending' => 'Pendente', 'rejected' => 'Rejeitado') as $value => $label) echo '<option value="'.esc_attr($value).'" '.selected($participant->status, $value, false).'>'.esc_html($label).'</option>';
        echo '</select></td></tr><tr><th><label for="dcdc-participant-photo">Foto</label></th><td><input type="file" id="dcdc-participant-photo" name="photo" accept="image/jpeg,image/png,image/webp"><p class="description">JPG, PNG ou WEBP até 2MB.</p>';
        if ($photo_url) echo '<p><img src="'.esc_url($photo_url).'" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:50%"><br><label><input type="checkbox" name="remove_photo" value="1"> Remover foto atual</label></p>';
        echo '</td></tr></table><p><button class="button button-primary" type="submit">Salvar participante</button> <a class="button" href="'.esc_url(admin_url('admin.php?page=dcdc-participants')).'">Cancelar</a></p></form>';
    }

    public static function participants() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $t = DCDC_DB::tables();

        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['dcdc_participant_action'])) {
            check_admin_referer('dcdc_save_participant');
            $id = absint($_POST['id'] ?? 0);
            $participant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['participants']} WHERE id=%d", $id));
            $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
            $status = sanitize_key($_POST['status'] ?? 'pending');
            if (!$participant || mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 80 || !in_array($status, array('approved', 'pending', 'rejected'), true)) {
                self::notice('Informe um nome válido e um status permitido.', 'error');
            } else {
                $photo_id = (int) $participant->photo_id;
                $new_photo_id = 0;
                if (!empty($_FILES['photo']['name'])) {
                    $new_photo_id = DCDC_Quiz::upload_photo($_FILES['photo']);
                    if (is_wp_error($new_photo_id)) { self::notice($new_photo_id->get_error_message(), 'error'); $new_photo_id = -1; }
                }
                if ($new_photo_id !== -1) {
                    if ($new_photo_id > 0) $photo_id = $new_photo_id;
                    if (!empty($_POST['remove_photo']) || $new_photo_id > 0) {
                        if ($participant->photo_id) wp_delete_attachment((int) $participant->photo_id, true);
                        if (!empty($_POST['remove_photo']) && $new_photo_id === 0) $photo_id = 0;
                    }
                    $wpdb->update($t['participants'], array('name' => $name, 'status' => $status, 'photo_id' => $photo_id), array('id' => $id));
                    self::notice('Participante atualizado.');
                }
            }
        }

        if (isset($_GET['delete'])) {
            $id = absint($_GET['delete']);
            check_admin_referer('dcdc_delete_participant_'.$id);
            $participant = $wpdb->get_row($wpdb->prepare("SELECT photo_id FROM {$t['participants']} WHERE id=%d", $id));
            if ($participant) {
                if ($participant->photo_id) wp_delete_attachment((int) $participant->photo_id, true);
                $wpdb->delete($t['participants'], array('id' => $id));
                self::notice('Participante excluído.');
            }
        }

        $edit = isset($_GET['edit']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['participants']} WHERE id=%d", absint($_GET['edit']))) : null;
        echo '<div class="wrap"><h1>Participantes</h1>';
        if ($edit) self::participant_form($edit);
        $rows = $wpdb->get_results("SELECT p.*, c.name category_name FROM {$t['participants']} p LEFT JOIN {$t['categories']} c ON c.id=p.category_id ORDER BY p.score DESC,p.id ASC");
        echo '<h2>Cadastros</h2><table class="widefat striped"><thead><tr><th>Nome</th><th>Pontos</th><th>Categoria</th><th>Foto</th><th>Status</th><th></th></tr></thead><tbody>';
        if (!$rows) echo '<tr><td colspan="6">Nenhum participante cadastrado.</td></tr>';
        foreach ($rows as $row) {
            $edit_url = admin_url('admin.php?page=dcdc-participants&edit='.(int) $row->id);
            $delete_url = wp_nonce_url(admin_url('admin.php?page=dcdc-participants&delete='.(int) $row->id), 'dcdc_delete_participant_'.(int) $row->id);
            echo '<tr><td>'.esc_html($row->name).'</td><td>'.(int) $row->score.'</td><td>'.esc_html($row->category_name ?: '-').'</td><td>'.($row->photo_id ? '<a target="_blank" href="'.esc_url(wp_get_attachment_url((int) $row->photo_id)).'">Ver foto</a>' : '-').'</td><td>'.esc_html($row->status).'</td><td><a href="'.esc_url($edit_url).'">Editar</a> | <a href="'.esc_url($delete_url).'" onclick="return confirm(\'Excluir este participante e sua foto?\')">Excluir</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
