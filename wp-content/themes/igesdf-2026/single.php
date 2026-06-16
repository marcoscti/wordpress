<?php get_header(); ?>

<div class="container py-5 igesdf-container">

    <?php while (have_posts()) : the_post(); ?>

        <article>

            <section class="mb-4">
                <p class="text-muted">

                    Atualizado em
                    <?php echo get_the_date('d/m/Y') . ' às ' . get_the_date('H:i'); ?>

                    por

                    <?php the_author(); ?>

                </p>

            </section>

            <?php if (has_post_thumbnail()) : ?>

                <div class="mb-4">

                    <?php the_post_thumbnail(
                        'large',
                        ['class' => 'img-fluid rounded']
                    ); ?>

                </div>

            <?php endif; ?>

            <div class="content">

                <?php the_content(); ?>

            </div>

            <hr>

            <div class="d-flex justify-content-between mt-4">

                <div>
                    <?php previous_post_link(
                        '%link',
                        '&laquo; Post anterior'
                    ); ?>
                </div>

                <div>
                    <?php next_post_link(
                        '%link',
                        'Próximo post &raquo;'
                    ); ?>
                </div>

            </div>

        </article>

    <?php endwhile; ?>

</div>

<?php get_footer(); ?>