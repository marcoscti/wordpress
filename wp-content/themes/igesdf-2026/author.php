<?php get_header(); ?>

<section class="author-page">
    
    <?php
    $author = get_queried_object();
    ?>

    <h1><?php echo esc_html($author->display_name); ?></h1>

    <?php if ($author->description) : ?>
        <p><?php echo esc_html($author->description); ?></p>
    <?php endif; ?>

    <h2>Publicações</h2>

    <?php if (have_posts()) : ?>
        <div class="posts-grid">
            <?php while (have_posts()) : the_post(); ?>
                <article>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                        <h3><?php the_title(); ?></h3>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination(); ?>

    <?php else : ?>
        <p>Nenhuma publicação encontrada.</p>
    <?php endif; ?>

</section>

<?php get_footer(); ?>