<footer class="py-3">
    <div class="container">
        <div class="d-flex justify-content-center mb-3">
            <a href="<?php echo home_url(); ?>">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-branco.png" alt="Logo IgesDF" style="height: 50px;">
            </a>
        </div>
    </div>
    <div class="container">
        <?php
        wp_nav_menu([
            'theme_location' => 'footer_menu',
            'container'      => false,
            'menu_class'     => 'navbar-nav',
            'fallback_cb'    => '__return_false',
        ]);
        ?>
        <p class="m-0 text-center text-white line lh-base">
            Siga nossas redes:<br>
        <ul class="d-flex justify-content-center list-unstyled mb-0 gap-2">
            <li><a href="https://www.facebook.com/igesdfoficial" target="_blank"><img width="30" src="<?php echo get_template_directory_uri(); ?>/assets/images/facebook.png" alt="Facebook" class="lp-copa-2026-image"></a></li>
            <li><a href="https://www.instagram.com/iges_df/" target="_blank"><img width="30" src="<?php echo get_template_directory_uri(); ?>/assets/images/instagram.png" alt="Instagram" class="lp-copa-2026-image"></a></li>
            <li><a href="https://www.youtube.com//@IGESDFoficial" target="_blank"><img width="30" src="<?php echo get_template_directory_uri(); ?>/assets/images/youtube.png" alt="YouTube" class="lp-copa-2026-image"></a></li>
            <li><a href="https://www.linkedin.com/company/igesdf/" target="_blank"><img width="30" src="<?php echo get_template_directory_uri(); ?>/assets/images/linkedin.png" alt="LinkedIn" class="lp-copa-2026-image"></a></li>
        </ul>
        </p>
        <p class="m-0 text-center text-white">Instituto de Gestão Estratégica de Saúde do Distrito Federal IgesDF. <br>Todos os direitos reservados.</p>
    </div>
</footer>
<?php wp_footer(); ?>

</body>

</html>