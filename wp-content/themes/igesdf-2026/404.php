<?php get_header(); ?>

<div class="container py-5 text-center igesdf-container">

    <h1 class="display-1">404</h1>
    <h2 class="mb-4">
        Página não encontrada
    </h2>
    <div class="icon-404">
        😵
    </div>
    <p class="lead mb-4">
        O conteúdo que você procura não existe ou foi removido.
    </p>
    <div class="lead mb-4 col-12 col-sm-6 offset-sm-3">
        <?php echo do_shortcode('[busca_noticias placeholder="Buscar na Intranet"]'); ?>
    </div>
    <a href="<?php echo esc_url(home_url('/')); ?>"
        class="btn btn-primary">
        <i class="fa fa-undo" aria-hidden="true"></i>
        Voltar para a página inicial
    </a>

</div>

<?php get_footer(); ?>