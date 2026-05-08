<?php get_header(); ?>

<main class="container mx-auto py-10">
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

            <article class="mb-8">
                <?= the_post_thumbnail('medium'); ?>
                <h2 class="text-2xl font-bold mb-2"><?php the_title(); ?></h2>
                <div class="text-gray-700">
                    <?php the_excerpt(); ?>
                </div>
            </article>

    <?php endwhile;
    endif; ?>

</main>

<?php get_footer(); ?>