<?php
/*
Plugin Name: IgesDF Countdown
Description: Countdown simples com data inicial e final.
Version: 1.0.0
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

                </table>

                <?php submit_button(); ?>

            </form>

        </div>

        <?php

    }

    public function shortcode(){

        ob_start();

        ?>

        <div class="simple-countdown">
            <div class="igesdf-countdown-background">
                <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/banner_semnumero.png'; ?>" alt="">
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