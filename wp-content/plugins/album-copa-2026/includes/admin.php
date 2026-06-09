<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Gerencia as funcionalidades administrativas do plugin Album Copa 2026.
 *
 * Esta classe é responsável por:
 * - Adicionar meta boxes para aprovação e informações do autor no Custom Post Type 'figurinhas-copa-2026'.
 * - Salvar o status de aprovação da figurinha e enviar e-mails de notificação.
 * - Registrar e renderizar colunas personalizadas na lista de posts do Custom Post Type.
 */
class Album_Copa_2026_Admin
{

    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_approval_meta_box'));
        add_action('add_meta_boxes', array($this, 'add_author_info_meta_box'));
        add_action('save_post_figurinhas-copa-2026', array($this, 'save_approval_meta_box'), 10, 2);
        add_filter('manage_figurinhas-copa-2026_posts_columns', array($this, 'register_columns'));
        add_action('manage_figurinhas-copa-2026_posts_custom_column', array($this, 'render_columns'), 10, 2);
    }

    /**
     * Adiciona a meta box de aprovação ao Custom Post Type 'figurinhas-copa-2026'.
     *
     * @return void
     */
    public function add_approval_meta_box()
    {
        add_meta_box(
            'album_copa_2026_approval',
            __('Aprovado', 'album-copa-2026'),
            array($this, 'render_approval_meta_box'),
            'figurinhas-copa-2026',
            'side',
            'high'
        );
    }

    /**
     * Adiciona a meta box de informações do autor ao Custom Post Type 'figurinhas-copa-2026'.
     *
     * @return void
     */
    public function add_author_info_meta_box()
    {
        add_meta_box(
            'album_copa_2026_author_info',
            __('Informações do Autor', 'album-copa-2026'),
            array($this, 'render_author_info_meta_box'),
            'figurinhas-copa-2026',
            'normal',
            'high'
        );
    }

    /**
     * Renderiza o conteúdo da meta box de aprovação.
     * Exibe um checkbox para marcar a figurinha como aprovada.
     *
     * @param WP_Post $post O objeto do post atual.
     * @return void
     */
    public function render_approval_meta_box($post)
    {
        wp_nonce_field('album_copa_2026_save_approval', 'album_copa_2026_approval_nonce');
        $approved = get_post_meta($post->ID, '_album_copa_2026_aprovado', true);
?>
        <label for="album_copa_2026_aprovado">
            <input type="checkbox" name="album_copa_2026_aprovado" id="album_copa_2026_aprovado" value="1" <?php checked($approved, '1'); ?> />
            <?php esc_html_e('Marcar como aprovado', 'album-copa-2026'); ?>
        </label>
    <?php
    }

    /**
     * Renderiza o conteúdo da meta box de informações do autor.
     * Exibe o nome e e-mail do autor da figurinha.
     *
     * @param WP_Post $post O objeto do post atual.
     * @return void
     */
    public function render_author_info_meta_box($post)
    {
        $nome = get_post_meta($post->ID, '_album_copa_2026_nome', true);
        $email = get_post_meta($post->ID, '_album_copa_2026_email', true);
    ?>
        <div style="margin-bottom: 16px;">
            <label for="album_copa_2026_nome_display">
                <strong><?php esc_html_e('Nome do Autor', 'album-copa-2026'); ?></strong>
                <input type="text" id="album_copa_2026_nome_display" value="<?php echo esc_attr($nome); ?>" readonly style="width: 100%; padding: 8px; margin-top: 6px; border: 1px solid #ddd; border-radius: 4px; background-color: #f5f5f5;" />
            </label>
        </div>
        <div>
            <label for="album_copa_2026_email_display">
                <strong><?php esc_html_e('Email do Autor', 'album-copa-2026'); ?></strong>
                <input type="email" id="album_copa_2026_email_display" value="<?php echo esc_attr($email); ?>" readonly style="width: 100%; padding: 8px; margin-top: 6px; border: 1px solid #ddd; border-radius: 4px; background-color: #f5f5f5;" />
            </label>
        </div>
<?php
    }

    /**
     * Salva os dados da meta box de aprovação quando o post é salvo.
     * Altera o status do post para 'publish' ou 'pending' com base na aprovação.
     * Envia um e-mail de notificação ao autor se a figurinha for aprovada.
     *
     * @param int     $post_id O ID do post.
     * @param WP_Post $post    O objeto do post.
     * @return void
     */
    public function save_approval_meta_box($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (! isset($_POST['album_copa_2026_approval_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['album_copa_2026_approval_nonce'])), 'album_copa_2026_save_approval')) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $approved = isset($_POST['album_copa_2026_aprovado']) ? '1' : '0';
        update_post_meta($post_id, '_album_copa_2026_aprovado', $approved);

        if ('1' === $approved && 'publish' !== $post->post_status) {
            remove_action('save_post_figurinhas-copa-2026', array($this, 'save_approval_meta_box'));
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish',
            ));
            add_action('save_post_figurinhas-copa-2026', array($this, 'save_approval_meta_box'), 10, 2);
            
            $email = get_post_meta($post_id, '_album_copa_2026_email', true);
            $name = get_post_meta($post_id, '_album_copa_2026_nome', true);
            $mensagem = "Olá, " . $name . " sua figurinha foi validada e já está disponível na página de homenagem ao dia das mães!.";
            wp_mail($email, "Sua figurinha foi aprovada!", $mensagem);

            // Call Remove.bg API to process the image
            $this->process_image_with_removebg($post_id);
        }

        if ('0' === $approved && 'publish' === $post->post_status) {
            remove_action('save_post_figurinhas-copa-2026', array($this, 'save_approval_meta_box'));
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'pending',
            ));
            add_action('save_post_figurinhas-copa-2026', array($this, 'save_approval_meta_box'), 10, 2);
        }
    }

    /**
     * Registra colunas personalizadas na lista de posts do Custom Post Type 'figurinhas-copa-2026'.
     * Adiciona uma coluna para exibir o status de aprovação.
     *
     * @param array $columns As colunas existentes.
     * @return array As colunas modificadas.
     */
    public function register_columns($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ('title' === $key) {
                $new_columns['album_copa_2026_aprovado'] = __('Aprovado', 'album-copa-2026');
            }
        }

        return $new_columns;
    }

    /**
     * Renderiza o conteúdo das colunas personalizadas.
     * Exibe "Sim" ou "Não" para o status de aprovação.
     *
     * @param string $column O nome da coluna atual.
     * @param int    $post_id O ID do post.
     * @return void
     */
    public function render_columns($column, $post_id)
    {
        if ('album_copa_2026_aprovado' !== $column) {
            return;
        }

        $approved = get_post_meta($post_id, '_album_copa_2026_aprovado', true);

        if ('1' === $approved) {
            esc_html_e('Sim', 'album-copa-2026');
        } else {
            esc_html_e('Não', 'album-copa-2026');
        }
    }

    /**
     * Processa a imagem usando a API do Remove.bg.
     *
     * @param int $post_id O ID do post.
     * @return void
     */
    private function process_image_with_removebg($post_id)
    {
        error_log("Album Copa 2026: Iniciando processamento de imagem para o Post ID: " . $post_id);

        // Garante que as funções de mídia do WordPress estejam disponíveis
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Recupera a chave da API das configurações do plugin
        $options = get_option('album_copa_2026_settings_group', array());
        $api_key = isset($options['album_copa_2026_removebg_api_key']) ? $options['album_copa_2026_removebg_api_key'] : '';

        if (empty($api_key)) {
            error_log('Album Copa 2026: Chave API do Remove.bg não configurada. Pulando remoção de fundo para o post ID: ' . $post_id);
            return;
        }

        // Obtém a imagem destacada original
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            error_log('Album Copa 2026: Nenhuma imagem destacada encontrada para o post ID: ' . $post_id);
            return;
        }

        $original_image_url = wp_get_attachment_url($thumbnail_id);
        if (!$original_image_url) {
            return;
        }

        // Prepara a chamada para a API
        $api_url = 'https://api.remove.bg/v1.0/removebg';
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'X-Api-Key' => $api_key,
            ),
            'body'    => array(
                'image_url' => $original_image_url,
                'size'      => 'auto',
                'type'      => 'person',
            ),
            'timeout' => 60,
        ));

        // Trata erros de conexão
        if (is_wp_error($response)) {
            error_log('Album Copa 2026: Erro na API do Remove.bg: ' . $response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $decoded_response = json_decode($response_body, true);
            $error_message = isset($decoded_response['errors'][0]['title']) ? $decoded_response['errors'][0]['title'] : 'Erro desconhecido';
            error_log('Album Copa 2026: API Remove.bg retornou status ' . $response_code . ': ' . $error_message);
            return;
        }

        // Salva a nova imagem temporariamente
        $upload_dir = wp_upload_dir();
        $image_name = 'removebg-' . basename(parse_url($original_image_url, PHP_URL_PATH));
        
        // Garante que a extensão seja .png já que o remove.bg retorna PNG por padrão
        if (substr(strtolower($image_name), -4) !== '.png') {
            $image_name .= '.png';
        }
        
        $new_filepath = $upload_dir['path'] . '/' . $image_name;

        if (file_put_contents($new_filepath, $response_body) === false) {
            error_log('Album Copa 2026: Falha ao salvar arquivo da API no disco.');
            return;
        }

        // Insere a nova imagem na biblioteca de mídia
        $file_array = array(
            'name'     => $image_name,
            'type'     => 'image/png',
            'tmp_name' => $new_filepath,
            'error'    => 0,
            'size'     => filesize($new_filepath),
        );

        // sideload move o arquivo temporário para o local correto e cria o anexo
        $new_attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($new_attachment_id)) {
            error_log('Album Copa 2026: Erro ao importar imagem processada: ' . $new_attachment_id->get_error_message());
            if (file_exists($new_filepath)) {
                unlink($new_filepath);
            }
            return;
        }

        // Define como nova imagem destacada
        set_post_thumbnail($post_id, $new_attachment_id);

        // Remove a imagem antiga para não entulhar o servidor
        wp_delete_attachment($thumbnail_id, true);

        // Adiciona um log de sucesso
        error_log('Album Copa 2026: Fundo removido com sucesso para o post ID: ' . $post_id);
    }
}
