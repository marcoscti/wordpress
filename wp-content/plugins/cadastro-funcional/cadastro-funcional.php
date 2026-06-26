<?php
/*
Plugin Name: IgesDF User Data
Description: Gerencia Unidades, Setores e Matrícula dos colaboradores, com intuito de organizar melhor os usuários do site
Version: 1.0
Author: Marcos Cordeiro
*/

if (!defined('ABSPATH')) exit;

/* CPTs */
function cf_register_cpts() {

    register_post_type('cf_unidade', [
        'labels' => ['name' => 'Unidades', 'singular_name' => 'Unidade'],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'users.php',
        'supports' => ['title'],
    ]);

    register_post_type('cf_setor', [
        'labels' => ['name' => 'Setores', 'singular_name' => 'Setor'],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'users.php',
        'supports' => ['title'],
    ]);
}
add_action('init', 'cf_register_cpts');

function cf_user_fields($user) {
    $unidade = get_user_meta($user->ID, 'cf_unidade', true);
    $setor = get_user_meta($user->ID, 'cf_setor', true);
    $matricula = get_user_meta($user->ID, 'cf_matricula', true);

    $unidades = get_posts(['post_type'=>'cf_unidade','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
    $setores = get_posts(['post_type'=>'cf_setor','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
?>
<h2>Dados Funcionais</h2>
<table class="form-table">
<tr>
<th><label>Matrícula</label></th>
<td><input type="text" name="cf_matricula" value="<?php echo esc_attr($matricula); ?>" class="regular-text"></td>
</tr>

<tr>
<th><label>Unidade de Atendimento</label></th>
<td>
<select name="cf_unidade">
<option value="">Selecione</option>
<?php foreach($unidades as $item): ?>
<option value="<?php echo esc_attr($item->ID); ?>" <?php selected($unidade,$item->ID); ?>>
<?php echo esc_html($item->post_title); ?>
</option>
<?php endforeach; ?>
</select>
</td>
</tr>

<tr>
<th><label>Setor</label></th>
<td>
<select name="cf_setor">
<option value="">Selecione</option>
<?php foreach($setores as $item): ?>
<option value="<?php echo esc_attr($item->ID); ?>" <?php selected($setor,$item->ID); ?>>
<?php echo esc_html($item->post_title); ?>
</option>
<?php endforeach; ?>
</select>
</td>
</tr>
</table>
<?php
}

add_action('show_user_profile', 'cf_user_fields');
add_action('edit_user_profile', 'cf_user_fields');

function cf_save_user_fields($user_id){
    if(!current_user_can('edit_user',$user_id)) return;

    update_user_meta($user_id,'cf_matricula',sanitize_text_field($_POST['cf_matricula'] ?? ''));
    update_user_meta($user_id,'cf_unidade',intval($_POST['cf_unidade'] ?? 0));
    update_user_meta($user_id,'cf_setor',intval($_POST['cf_setor'] ?? 0));
}
add_action('personal_options_update','cf_save_user_fields');
add_action('edit_user_profile_update','cf_save_user_fields');

function cf_columns($columns){
    $columns['cf_matricula']='Matrícula';
    $columns['cf_unidade']='Unidade';
    $columns['cf_setor']='Setor';
    return $columns;
}
add_filter('manage_users_columns','cf_columns');

function cf_column_content($value,$column,$user_id){

    if($column==='cf_matricula'){
        return get_user_meta($user_id,'cf_matricula',true);
    }

    if($column==='cf_unidade'){
        $id=get_user_meta($user_id,'cf_unidade',true);
        return $id ? get_the_title($id) : '';
    }

    if($column==='cf_setor'){
        $id=get_user_meta($user_id,'cf_setor',true);
        return $id ? get_the_title($id) : '';
    }

    return $value;
}
add_filter('manage_users_custom_column','cf_column_content',10,3);
