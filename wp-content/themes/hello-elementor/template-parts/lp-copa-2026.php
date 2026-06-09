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
                <a href="<?php echo get_site_url(); ?>/figurinhas/"><img src="https://igesdf.org.br/wp-content/uploads/2022/09/logo-iges-branca.svg" alt="Logo"></a>
            </div>
            <ul class="nav-links">
                <li><a href="<?php echo get_site_url(); ?>/figurinhas/">Envie a sua</a></li>
                <li><a href="<?php echo get_site_url(); ?>/album-form/">Album</a></li>
            </ul>
            <button class="btnOpenDrawer">
                <div></div>
                <div></div>
                <div></div>
            </button>

            <div class="drawerBackground">
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
                        <li><a href="<?php echo get_site_url(); ?>/figurinhas/">Envie a sua</a></li>
                        <li><a href="<?php echo get_site_url(); ?>/album-form/">Album</a></li>
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
        <p>Desenvolvido pelo núcleo de mídias digitais</p>
    </footer>
    <?php wp_footer(); ?>
    <script src="<?php echo get_template_directory_uri(); ?>/assets/js/lp-copa-2026.js"></script>
</body>

</html>