<?php get_header(); ?>
<div class="container py-5 igesdf-container">
    <?php while (have_posts()) : the_post(); ?>
        <article>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <?php echo breadcrumb() ?>
                <p class="text-muted text-sm m-0">
                    Atualizado em
                    <?php echo get_the_date('d/m/Y') . ' às ' . get_the_date('H:i'); ?>
                    por
                    <?php the_author(); ?>
                </p>
            </div>
            <div class="content">
                <h1 class="mb-4 fs-1"><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </div>
            <hr>
            <div class="d-flex justify-content-between mt-4">
                <div class="navigation-item px-2 py-2 border rounded">
                    <?php previous_post_link(
                        '%link',
                        '<i class="fa fa-arrow-circle-left" aria-hidden="true"></i> Anterior'
                    ); ?>
                </div>
                <div class="navigation-item px-2 py-2 border rounded">
                    <?php next_post_link(
                        '%link',
                        'Próximo <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>'
                    ); ?>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?>