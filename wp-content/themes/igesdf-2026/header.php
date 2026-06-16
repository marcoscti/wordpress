<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <header class="py-3 mb-4">
        <div class="container">

            <div class="header-content">

                <button class="btn btn-lg btn-igesdf-main"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasExample"
                    aria-controls="offcanvasExample">
                    <i class="fa fa-th" aria-hidden="true"></i> Menu
                </button>

                <div class="logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/logo-branco.png" alt="IgesDF">
                    </a>
                </div>

                <div class="search-input">
                    <?php echo do_shortcode('[busca_noticias placeholder="Buscar na Intranet"]'); ?>
                </div>

            </div>

        </div>
    </header>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-white" id="offcanvasExampleLabel">Intranet IgesDF</h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php
            wp_nav_menu([
                'theme_location' => 'header_menu',
                'container'      => false,
                'menu_class'     => 'navbar-nav',
                'fallback_cb'    => '__return_false',
            ]);
            ?>

            <ul class="nav navbar-nav" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">

                <li class=""><a href="https://login.live.com/login.srf?wa=wsignin1.0&rpsnv=13&ct=1644433464&rver=7.0.6737.0&wp=MBI_SSL&wreply=https%3a%2f%2foutlook.live.com%2fowa%2f%3fnlp%3d1%26RpsCsrfState%3d667ebf32-d3d8-f1f3-8349-99ff8fb10b20&id=292841&aadredir=1&CBCXT=out&lw=1&fl=dob%2cflname%2cwld&cobrandid=90015" class="color-primary" target="_blank"><i class="fa fa-envelope" aria-hidden="true"></i> Acessar o webmail</a></li>

                <li class=""><a href="https://igesdf.org.br/telefones-e-ramais-unidades-igesdf/" class="color-primary" target="_blank"><i class="fa fa-phone" aria-hidden="true"></i> Contatos</a></li>

                <li class=""><a href="https://institutodegestaoestrategica.app.questorpublico.com.br" class="color-primary" target="_blank"><i class="fa fa-money" aria-hidden="true"></i> Contracheque</a></li>

                <li class=""><a href="http://igesdf/intranet/coronavirus/" class="color-primary"><i class="fa fa-heartbeat" aria-hidden="true"></i> COVID-19</a></li>

                <li class=""><a href="http://igesdf/uptodate/" class="color-primary"><i class="fa fa-refresh" aria-hidden="true"></i> Up to date</a></li>
                <?php
                if (is_user_logged_in()):
                ?>
                    <li class=""><a href="http://igesdf/intranet/wp-admin" class="color-primary"><i class="fa fa-lock" aria-hidden="true"></i> Admin</a></li>
                <?php else: ?>
                    <li class=""><a href="http://igesdf/intranet/wp-login.php" class="color-primary"><i class="fa fa-user" aria-hidden="true"></i> Login</a></li>
                <?php
                endif;
                ?>
            </ul>

        </div>
    </div>