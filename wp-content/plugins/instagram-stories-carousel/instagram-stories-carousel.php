<?php
/**
 * Plugin Name:       Instagram Stories Carousel
 * Plugin URI:        https://gemini.google.com/
 * Description:       Mostra um carrossel de stories do Instagram usando a API do Facebook.
 * Version:           1.1.0
 * Author:            Gemini
 * Author URI:        https://gemini.google.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       instagram-stories-carousel
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Adiciona a página do plugin no menu de Configurações
function isc_add_admin_menu() {
    add_options_page(
        'Instagram Stories Carousel',
        'Instagram Stories',
        'manage_options',
        'instagram_stories_carousel',
        'isc_options_page_html'
    );
}
add_action('admin_menu', 'isc_add_admin_menu');

// Adiciona um link de Configurações na página de plugins
function isc_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=instagram_stories_carousel">' . __('Configurações') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'isc_add_settings_link');


// Renderiza o HTML da página de opções
function isc_options_page_html() {
    if ( ! current_user_can('manage_options') ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div style="background: #fff; padding: 1px 15px; border: 1px solid #ccd0d4; margin-top: 15px;">
            <h2>Como Configurar e Usar</h2>
            <p><strong>Passo 1: Obtenha suas credenciais</strong></p>
            <p>Para usar este plugin, você precisa de um <strong>ID de Usuário de Negócios do Instagram</strong> e um <strong>Token de Acesso de Longa Duração</strong>. Você pode obtê-los no <a href="https://developers.facebook.com/apps/" target="_blank">Portal de Desenvolvedores do Facebook</a>, criando um aplicativo e usando a ferramenta Graph API Explorer.</p>
            
            <p><strong>Passo 2: Salve as credenciais</strong></p>
            <p>Insira o ID de Usuário e o Token de Acesso nos campos abaixo e clique em "Salvar Configurações".</p>

            <p><strong>Passo 3: Exiba o carrossel</strong></p>
            <p>Para exibir o carrossel de stories em qualquer página, post ou widget, basta adicionar o seguinte shortcode ao seu conteúdo:</p>
            <p><code>[instagram_stories_carousel]</code></p>
        </div>

        <form action="options.php" method="post" style="margin-top: 20px;">
            <?php
            settings_fields('isc_options_group');
            do_settings_sections('instagram_stories_carousel');
            submit_button('Salvar Configurações');
            ?>
        </form>
    </div>
    <?php
}


// Registra as configurações, seções e campos
function isc_settings_init() {
    register_setting('isc_options_group', 'isc_settings');

    add_settings_section(
        'isc_settings_section',
        'Configurações da API do Instagram',
        'isc_settings_section_callback',
        'instagram_stories_carousel'
    );

    add_settings_field(
        'isc_user_id_field',
        'ID do Usuário do Instagram',
        'isc_user_id_field_render',
        'instagram_stories_carousel',
        'isc_settings_section'
    );

    add_settings_field(
        'isc_access_token_field',
        'Token de Acesso',
        'isc_access_token_field_render',
        'instagram_stories_carousel',
        'isc_settings_section'
    );
}
add_action('admin_init', 'isc_settings_init');

function isc_settings_section_callback() {
    echo 'Insira suas credenciais da API do Facebook/Instagram abaixo.';
}

function isc_user_id_field_render() {
    $options = get_option('isc_settings');
    ?>
    <input type='text' name='isc_settings[isc_user_id_field]' value='<?php echo isset($options['isc_user_id_field']) ? esc_attr($options['isc_user_id_field']) : ''; ?>' class='regular-text'>
    <?php
}

function isc_access_token_field_render() {
    $options = get_option('isc_settings');
    ?>
    <input type='password' name='isc_settings[isc_access_token_field]' value='<?php echo isset($options['isc_access_token_field']) ? esc_attr($options['isc_access_token_field']) : ''; ?>' class='regular-text'>
    <?php
}

function isc_enqueue_styles() {
    wp_enqueue_style(
        'isc-style',
        plugin_dir_url(__FILE__) . 'assets/css/isc-style.css',
        [],
        '1.1.0'
    );
}
add_action('wp_enqueue_scripts', 'isc_enqueue_styles');

function isc_shortcode($atts) {
    $options = get_option('isc_settings');
    $user_id = isset($options['isc_user_id_field']) ? $options['isc_user_id_field'] : '';
    $access_token = isset($options['isc_access_token_field']) ? $options['isc_access_token_field'] : '';

    if (empty($user_id) || empty($access_token)) {
        if (current_user_can('manage_options')) {
            return "<!-- Instagram Stories Carousel: Configure o ID do Usuário e o Token de Acesso nas configurações do plugin. -->";
        }
        return '';
    }

    $cached_data = get_transient('isc_stories_cache');

    if (false === $cached_data) {
        $api_url = "https://graph.facebook.com/v18.0/{$user_id}/stories?fields=media_url,thumbnail_url,permalink,media_type&access_token={$access_token}";
        $response = wp_remote_get($api_url);

        if (is_wp_error($response)) {
            if (current_user_can('manage_options')) {
                return "<!-- Erro na requisição: " . $response->get_error_message() . " -->";
            }
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            if (current_user_can('manage_options')) {
                return "<!-- Erro da API: " . esc_html($data['error']['message']) . " -->";
            }
            return '';
        }
        
        set_transient('isc_stories_cache', $data, 15 * MINUTE_IN_SECONDS);
    } else {
        $data = $cached_data;
    }

    if (empty($data['data'])) {
        return "<!-- Nenhum story encontrado. -->";
    }

    ob_start();
    ?>
    <div class="isc-carousel-container">
        <?php foreach ($data['data'] as $story) : ?>
            <?php if (isset($story['media_type']) && ($story['media_type'] === 'IMAGE' || $story['media_type'] === 'VIDEO')) : ?>
                <div class="isc-story-item">
                    <a href="<?php echo esc_url($story['permalink']); ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url(isset($story['thumbnail_url']) ? $story['thumbnail_url'] : $story['media_url']); ?>" alt="Instagram Story">
                    </a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('instagram_stories_carousel', 'isc_shortcode');

?>