<?php get_header(); ?>

<div class="container py-5 igesdf-container">

    <h1 class="mb-4">
        Resultados para: "<?php echo get_search_query(); ?>"
    </h1>

    <?php if (have_posts()) : ?>

        <div class="row">

            <?php while (have_posts()) : the_post(); ?>

                <div class="col-12 mb-4">

                    <article class="card">
                        <div class="card-body d-flex gap-3">
                            <div class="card-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('thumbnail', ['class' => 'border']); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h2 class="h4">
                                <a href="<?php the_permalink(); ?>" class="link-underline-light">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <p>
                                <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                            </p>
                            </div>
                        </div>
                    </article>

                </div>

            <?php endwhile; ?>

        </div>

        <div class="mt-4">
            <?php the_posts_pagination(); ?>
        </div>

    <?php else : ?>

        <div class="alert alert-warning">
            Nenhum resultado encontrado.
        </div>

    <?php endif; ?>

</div>

<?php get_footer(); ?>