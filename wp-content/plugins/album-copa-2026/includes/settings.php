<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Gerencia as configurações do plugin Album Copa 2026.
 *
 * Esta classe é responsável por:
 * - Adicionar uma página de configurações no painel administrativo.
 * - Registrar e gerenciar a chave de API do Remove.bg.
 */
class Album_Copa_2026_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
    }

    /**
     * Adiciona a página de configurações como um submenu sob o CPT 'Album Copa 2026'.
     *
     * @return void
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=figurinhas-copa-2026', // Parent slug (under our CPT menu)
            __( 'Configurações do Álbum', 'album-copa-2026' ), // Page title
            __( 'Configurações', 'album-copa-2026' ), // Menu title
            'manage_options', // Capability required to access the page
            'album-copa-2026-settings', // Menu slug
            array( $this, 'settings_page_content' ) // Callback function to render the page
        );
    }

    /**
     * Registra as configurações do plugin.
     *
     * @return void
     */
    public function register_plugin_settings() {
        register_setting(
            'album_copa_2026_settings_group', // Option group name
            'album_copa_2026_settings_group', // Option name (stored in wp_options)
            array( $this, 'sanitize_settings' ) // Sanitize callback for the entire group
        );

        add_settings_section(
            'album_copa_2026_removebg_section', // ID of the section
            __( 'Configurações Remove.bg API', 'album-copa-2026' ), // Title of the section
            array( $this, 'removebg_section_callback' ), // Callback to render section description
            'album-copa-2026-settings' // Page slug where this section appears
        );

        add_settings_field(
            'removebg_api_key_field', // ID of the setting field
            __( 'Remove.bg API Key', 'album-copa-2026' ), // Title of the field
            array( $this, 'removebg_api_key_callback' ), // Callback to render the field input
            'album-copa-2026-settings', // Page slug
            'album_copa_2026_removebg_section' // Section ID
        );
    }

    /**
     * Sanitize callback for the plugin settings.
     *
     * @param array $input The settings input from the form.
     * @return array The sanitized input.
     */
    public function sanitize_settings( $input ) {
        $new_input = array();
        if ( isset( $input['album_copa_2026_removebg_api_key'] ) ) {
            $new_input['album_copa_2026_removebg_api_key'] = sanitize_text_field( $input['album_copa_2026_removebg_api_key'] );
        }
        return $new_input;
    }

    /**
     * Callback para a seção de configurações da API do Remove.bg.
     *
     * @return void
     */
    public function removebg_section_callback() {
        echo '<p>' . esc_html__( 'Insira sua chave de API do Remove.bg para remover automaticamente o fundo das imagens aprovadas.', 'album-copa-2026' ) . '</p>';
        echo '<p>' . esc_html__( 'Se a chave de API não for fornecida, as imagens serão aprovadas sem a remoção do fundo.', 'album-copa-2026' ) . '</p>';
    }

    /**
     * Callback para renderizar o campo de entrada da chave de API do Remove.bg.
     *
     * @return void
     */
    public function removebg_api_key_callback() {
        $options = get_option( 'album_copa_2026_settings_group', array() );
        $api_key = isset( $options['album_copa_2026_removebg_api_key'] ) ? $options['album_copa_2026_removebg_api_key'] : '';
        echo '<input type="text" id="album_copa_2026_removebg_api_key" name="album_copa_2026_settings_group[album_copa_2026_removebg_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Obtenha sua chave de API em remove.bg/api', 'album-copa-2026' ) . '</p>';
    }

    /**
     * Renderiza o conteúdo completo da página de configurações.
     *
     * @return void
     */
    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Configurações do Álbum Copa 2026', 'album-copa-2026' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Output security fields for the registered setting group
                settings_fields( 'album_copa_2026_settings_group' );
                // Output setting sections and their fields
                do_settings_sections( 'album-copa-2026-settings' );
                // Output save button
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}