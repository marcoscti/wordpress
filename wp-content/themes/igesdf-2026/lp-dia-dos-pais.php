<?php
/*
Template Name: LP Dia dos Pais
*/
get_header();
?>

<section class="lp-hero mb-5">
    <div class="lp-hero-bg" style="background-image:url('<?php echo get_template_directory_uri(); ?>/assets/images/hero.jpg');"></div>
    <div class="container lp-hero-content">
        <div class="row align-items-center">
            <div class="col-lg-6 text-white">
                
            </div>
            <div class="col-lg-6 text-lg-end mt-4 mt-lg-0">
                <div class="hero-cta-card shadow-sm p-4 bg-white rounded-4 text-dark d-inline-block text-start">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">Enviar Homenagem</h2>
                            <p class="mb-0 text-muted">Conte sua história para nosso painel de pais.</p>
                        </div>
                        <div class="btn btn-primary">Enviar homenagem</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lp-form container my-5">
    <div class="row justify-content-center">
        <div class="col-xl-12">
            <div class="card lp-form-card  p-4 border-0">
                <div class="row gx-4">
                    <div class="col-md-5 pe-md-4 mb-4 mb-md-0">
                        <h2>Compartilhe sua história</h2>
                        <p class="text-muted">Conte para nós: o que é ser pai para você? Sua mensagem pode inspirar outras pessoas.</p>
                    </div>
                    <div class="col-md-7">
                        <form id="homenagem-form" class="homenagem-pais-form" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Seu nome</label>
                                <input type="text" name="h_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Unidade de trabalho</label>
                                <input type="text" name="h_unit" class="form-control" placeholder="Ex: PO 700">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Foto ou vídeo (máx 40s)</label>
                                <input type="file" name="h_media" accept="image/*,video/*" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">O que é ser pai para você?</label>
                                <textarea name="h_message" class="form-control" rows="5" maxlength="1000" required></textarea>
                            </div>
                            <button class="btn btn-primary btn-lg w-100" type="submit">Enviar homenagem</button>
                        </form>
                    </div>
                </div>
            </div>
            <section class="lp-cards container my-5">
                <div class="d-flex align-items-center justify-content-between mb-4 flex-column flex-md-row gap-3">
                    <div>
                        <h3>O que nossos pais dizem</h3>
                        <p class="text-muted mb-0">Histórias reais de carinho e inspiração.</p>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.scrollTo({top: document.getElementById('homenagem-form').offsetTop - 100, behavior:'smooth'})">Enviar homenagem</button>
                </div>
                <div class="row gx-4 gy-4">
                    <?php
                    $q = new WP_Query([
                        'post_type' => 'homenagem',
                        'post_status' => 'publish',
                        'posts_per_page' => 12
                    ]);

                    if ($q->have_posts()) {
                        while ($q->have_posts()) {
                            $q->the_post();
                            $pid = get_the_ID();
                            $name = get_post_meta($pid, 'homenagem_name', true) ?: get_the_title();
                            $unit = get_post_meta($pid, 'homenagem_unit', true);
                            $message = get_post_meta($pid, 'homenagem_message', true) ?: get_the_excerpt();
                            $short = mb_substr(strip_tags($message), 0, 70);
                            $thumb = get_the_post_thumbnail_url($pid, 'thumbnail') ?: get_template_directory_uri() . '/assets/images/default-avatar.png';
                            $likes = intval(get_post_meta($pid, 'homenagem_likes', true));
                    ?>
                            <div class="col-md-4">
                                <div class="card lp-story-card h-100 shadow-sm border-0 rounded-4 p-3 btn-open" data-id="<?php echo $pid; ?>">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <img src="<?php echo esc_url($thumb); ?>" class="avatar rounded-circle" alt="<?php echo esc_attr($name); ?>">
                                        <div>
                                            <h5 class="mb-1"><?php echo esc_html($name); ?></h5>
                                            <p class="text-muted small mb-0"><?php echo esc_html($unit); ?></p>
                                        </div>
                                    </div>
                                    <p class="card-text text-secondary mb-4">“<?php echo esc_html($short); ?>...”</p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <button class="btn btn-sm btn-outline-primary btn-like" data-id="<?php echo $pid; ?>" data-likes="<?php echo $likes; ?>">&#10084; <?php echo $likes; ?></button>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                        wp_reset_postdata();
                    } else {
                        echo '<p>Nenhuma homenagem publicada ainda.</p>';
                    }
                    ?>
                </div>
            </section>
            <?php
            global $wpdb;
            $total = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type='homenagem'");
            $parents = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='homenagem_name'");
            $parents_count = count(array_filter(array_unique($parents)));
            $messages = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID=m.post_id AND m.meta_key='homenagem_message' WHERE post_type='homenagem' AND COALESCE(m.meta_value,'') != ''");
            $units = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='homenagem_unit' AND COALESCE(meta_value,'') != ''");
            $units_count = count(array_unique($units));
            ?>
            <div class="row gx-3 gy-3 lp-stats mt-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                        <div class="stat-icon">&#10084;</div>
                        <h3><?php echo intval($total); ?></h3>
                        <p>Homenagens recebidas</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                        <div class="stat-icon">&#127968;</div>
                        <h3><?php echo intval($parents_count); ?></h3>
                        <p>Pais participantes</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                        <div class="stat-icon">&#128172;</div>
                        <h3><?php echo intval($messages); ?></h3>
                        <p>Mensagens</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                        <div class="stat-icon">&#128214;</div>
                        <h3><?php echo intval($units_count); ?></h3>
                        <p>Unidades representadas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$featured = get_posts([
    'post_type' => 'homenagem',
    'post_status' => 'publish',
    'meta_key' => 'homenagem_featured',
    'meta_value' => '1',
    'posts_per_page' => 6,
]);

if (!empty($featured)) : ?>
    <section class="lp-featured py-5">
        <div class="container">
            <div class="row align-items-center gx-4">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="featured-info text-white">
                        <span class="eyebrow eyebrow-light">Homenagem em destaque</span>
                        <h2>“Ser pai é amar de um jeito que não cabe no peito, mas que transforma tudo ao redor.”</h2>
                        <p class="text-white-50">Descubra como nossas homenagens mostram a força do amor e da presença todos os dias.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div id="homenagemFeaturedCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner rounded-4 overflow-hidden shadow-lg">
                            <?php foreach ($featured as $i => $f) :
                                $fid = $f->ID;
                                $fthumb = get_the_post_thumbnail_url($fid, 'large') ?: get_template_directory_uri() . '/assets/images/default-avatar.png';
                                $fname = get_post_meta($fid, 'homenagem_name', true) ?: get_the_title($fid);
                                $fmessage = get_post_meta($fid, 'homenagem_message', true) ?: $f->post_content;
                            ?>
                                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo esc_url($fthumb); ?>" class="d-block w-100" alt="<?php echo esc_attr($fname); ?>">
                                    <div class="carousel-caption d-none d-md-block text-start bg-dark bg-opacity-50 rounded-4 p-3">
                                        <h5><?php echo esc_html($fname); ?></h5>
                                        <p class="mb-0"><?php echo esc_html(mb_substr(strip_tags($fmessage), 0, 100)); ?>...</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#homenagemFeaturedCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#homenagemFeaturedCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Próximo</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
get_footer();
