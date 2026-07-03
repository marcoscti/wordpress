<?php get_header(); ?>

<div class="container py-5 igesdf-container">
    <?php echo breadcrumb() ?>
    <section class="mb-5">
        <?php if (get_the_archive_description()) : ?>
            <div class="archive-description">
                <?php the_archive_description(); ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (have_posts()) : ?>

        <div class="row g-4">

            <?php while (have_posts()) : the_post(); ?>

                <div class="col-md-6 col-lg-4">

                    <article class="card h-100 shadow-sm">

                        <?php if (has_post_thumbnail()) : ?>

                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail(
                                    'medium',
                                    ['class' => 'card-img-top img-fluid']
                                ); ?>
                            </a>

                        <?php endif; ?>

                        <div class="card-body">

                            <h2 class="h5">
                                <a href="<?php the_permalink(); ?>" class="link-underline-light">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <p>
                                <?php echo wp_trim_words(
                                    get_the_excerpt(),
                                    10
                                ); ?>
                            </p>

                        </div>
                    </article>

                </div>

            <?php endwhile; ?>

        </div>

        <div class="mt-5">

            <?php the_posts_pagination([
                'mid_size' => 2,
                'prev_text' => 'Anterior',
                'next_text' => 'Próximo'
            ]); ?>

        </div>

    <?php else : ?>

        <div class="alert alert-warning">
            Nenhum conteúdo encontrado.
        </div>

    <?php endif; ?>

</div>

<?php get_footer(); ?>