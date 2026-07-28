<?php
/*
Plugin Name: IgesDF Countdown
Description: Countdown simples com data inicial e final.
Version: 1.0.1
Author: Marcos Cordeiro
*/

if (!defined('ABSPATH')) exit;

class SimpleCountdown {

    private $option = 'simple_countdown_settings';

    public function __construct() {

        add_action('admin_menu', [$this,'menu']);
        add_action('admin_init', [$this,'register']);

        add_shortcode('simple_countdown',[$this,'shortcode']);

        add_action('wp_enqueue_scripts',[$this,'assets']);
        add_action('admin_enqueue_scripts', [$this,'admin_assets']);
    }

    public function assets(){

        wp_enqueue_style(
            'simple-countdown',
            plugin_dir_url(__FILE__) . 'igesdf-countdown-style.css'
        );

        wp_enqueue_script(
            'simple-countdown',
            plugin_dir_url(__FILE__) . 'igesdf-countdown-script.js',
            [],
            false,
            true
        );

        $settings = get_option($this->option);

        wp_localize_script(
            'simple-countdown',
            'SimpleCountdown',
            [
                'start' => !empty($settings['start']) ? $settings['start'] : '',
                'end'   => !empty($settings['end']) ? $settings['end'] : ''
            ]
        );
    }

    public function admin_assets($hook_suffix){

        if ($hook_suffix !== 'settings_page_simple-countdown') {
            return;
        }

        wp_enqueue_media();

    }

    public function menu(){

        add_options_page(
            'IgesDF Countdown',
            'IgesDF Countdown',
            'manage_options',
            'simple-countdown',
            [$this,'page']
        );

    }

    public function register(){

        register_setting(
            'simple_countdown_group',
            $this->option
        );

    }

    public function page(){

        $settings = get_option($this->option);
        $background_image = !empty($settings['background_image'])
            ? $settings['background_image']
            : plugin_dir_url(__FILE__) . 'assets/images/banner_semnumero.png';

        ?>

        <div class="wrap">

            <h1>Countdown</h1>

            <form method="post" action="options.php">

                <?php settings_fields('simple_countdown_group'); ?>

                <table class="form-table">

                    <tr>

                        <th>Data de início</th>

                        <td>

                            <input
                                type="datetime-local"
                                name="<?php echo $this->option; ?>[start]"
                                value="<?php echo esc_attr($settings['start'] ?? ''); ?>"
                            >

                        </td>

                    </tr>

                    <tr>

                        <th>Data de término</th>

                        <td>

                            <input
                                type="datetime-local"
                                name="<?php echo $this->option; ?>[end]"
                                value="<?php echo esc_attr($settings['end'] ?? ''); ?>"
                            >

                        </td>

                    </tr>

                    <tr>

                        <th>Imagem de fundo</th>

                        <td>

                            <input
                                type="text"
                                id="simple-countdown-background-image"
                                name="<?php echo $this->option; ?>[background_image]"
                                value="<?php echo esc_attr($settings['background_image'] ?? ''); ?>"
                                class="regular-text"
                            >
                            <button type="button" class="button" id="select-background-image">Selecionar imagem</button>
                            <p class="description">Use a mídia nativa do WordPress. Se nenhuma imagem for escolhida, será usada a imagem padrão do plugin.</p>

                            <div style="margin-top: 10px;">
                                <img
                                    id="simple-countdown-background-preview"
                                    src="<?php echo esc_url($background_image); ?>"
                                    alt="Pré-visualização da imagem de fundo"
                                    style="max-width: 300px; height: auto;"
                                >
                            </div>

                        </td>

                    </tr>

                </table>

                <?php submit_button(); ?>

            </form>

        </div>

        <script>
        jQuery(function($){
            var frame;

            $('#select-background-image').on('click', function(e){
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Selecionar imagem de fundo',
                    button: { text: 'Usar imagem' },
                    library: { type: 'image' },
                    multiple: false
                });

                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#simple-countdown-background-image').val(attachment.url);
                    $('#simple-countdown-background-preview').attr('src', attachment.url).show();
                });

                frame.open();
            });
        });
        </script>

        <?php

    }

    public function shortcode(){

        ob_start();

        $settings = get_option($this->option);
        $background_image = !empty($settings['background_image'])
            ? $settings['background_image']
            : plugin_dir_url(__FILE__) . 'assets/images/banner_semnumero.png';

        ?>

        <div class="simple-countdown">
            <div class="igesdf-countdown-background">
                <img src="<?php echo esc_url($background_image); ?>" alt="">
            </div>
            <div class="igesdf-countdown-container">
                <div>
                <span id="sc-days">00</span>
                <small>Dias</small>
            </div>
                <div class="divider"><span>:</span></div>
            <div>
                <span id="sc-hours">00</span>
                <small>Horas</small>
            </div>
                <div class="divider"><span>:</span></div>
            <div>
                <span id="sc-minutes">00</span>
                <small>Minutos</small>
            </div>
                <div class="divider"><span>:</span></div>
            <div>
                <span id="sc-seconds">00</span>
                <small>Segundos</small>
            </div>
            </div>

        </div>

        <?php

        return ob_get_clean();

    }

}

new SimpleCountdown();