<?php get_header(); ?>

<div class="container igesdf-container">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('mb-5'); ?>>
                
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
    <?php endwhile;
    else :
        echo '<p>Nenhum conteúdo encontrado.</p>' . have_posts();
    endif;
    ?>
</div>

<?php get_footer(); ?>