<?php

/**
 * Template Name: Template Copa 2026
 * Description: Um template simples para criar uma landing page sem elementos de navegação ou rodapé.
 * Template Post Type: page
 */

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/lp-copa-2026.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quintessential&display=swap" rel="stylesheet">
    <meta charset="<?php bloginfo('charset'); ?>">
    <title><?php the_title(); ?></title>
    <?php wp_head(); ?>
</head>

<body <?php body_class('lp-copa-2026-body'); ?>>
    <header class="lp-copa-2026-header">
        <nav>
            <div class="logo">
                <a href="<?php echo get_site_url(); ?>/album-copa-igesdf/"><img src="https://igesdf.org.br/wp-content/uploads/2022/09/logo-iges-branca.svg" alt="Logo"></a>
            </div>
            <ul class="nav-links">
                <li><a href="<?php echo get_site_url(); ?>/crie-a-figurinha/">Crie a sua</a></li>
                <li><a href="<?php echo get_site_url(); ?>/album-copa-igesdf/">Album</a></li>
            </ul>
            <button class="btnOpenDrawer" style="display: none;">
                <div></div>
                <div></div>
                <div></div>
            </button>

            <div class="drawerBackground" style="display: none;"></div>
            </div>
            <div class="drawerContent" style="display: none;">
                <div class="drawerHeader">
                    <p></p>
                    <button id="drawerClose">
                        <div class="left"></div>
                        <div class="right"></div>
                    </button>
                </div>
                <nav>
                    <ul>
                        <li><a href="<?php echo get_site_url(); ?>/crie-a-figurinha/">Envie a sua</a></li>
                        <li><a href="<?php echo get_site_url(); ?>/album-copa-igesdf/">Album</a></li>
                    </ul>
                </nav>
            </div>
        </nav>
    </header>
    <?php wp_body_open(); ?>
    <main class="lp-copa-2026-main" id="depoimentos">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                the_content();
            endwhile;
        endif;
        ?>
    </main>
    <footer class="lp-copa-2026-footer">
        <div class="container">
            <div style="margin: 0;" class="social">
                <p style="margin: 0;">Siga-nos nas redes sociais:</p>
                <ul>
                    <li><a href="https://www.facebook.com/igesdfoficial" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/facebook.png" alt="Facebook" class="lp-copa-2026-image"></a></li>
                    <li><a href="https://www.instagram.com/iges_df/" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/instagram.png" alt="Instagram" class="lp-copa-2026-image"></a></li>
                    <li><a href="https://www.youtube.com//@IGESDFoficial" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/youtube.png" alt="YouTube" class="lp-copa-2026-image"></a></li>
                    <li><a href="https://www.linkedin.com/company/igesdf/" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/linkedin.png" alt="LinkedIn" class="lp-copa-2026-image"></a></li>
                </ul>
            </div>
            <p style="margin: 0;">Desenvolvido pela Acessoria de Comunicação (ASCOM) <br> Email: <a href="mailto:ascom@igesdf.org.br" target="_blank">ascom@igesdf.org.br</a> </p>
            <p style="margin: 0;">© <?php echo date('Y'); ?> IgesDF - Instituto de Gestão Estratégica de Saúde do Distrito Federal. Todos os direitos reservados.</p>
        </div>
    </footer>
    <?php wp_footer(); ?>
    <script src="<?php echo get_template_directory_uri(); ?>/assets/js/lp-copa-2026.js"></script>
</body>

</html>