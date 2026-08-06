<?php
/*
Template Name: LP Dia dos Pais
*/
get_header();
?>

<section class="lp-hero">
    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo get_template_directory_uri(); ?>/assets/images/hero-mobile.jpeg" style="width: 100%; height: auto;">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/hero.png" style="width: 100%; height: auto;" alt="Dia dos Pais" class="img-fluid">
    </picture>
</section>

<section class="lp-form container my-5">
    <div class="row justify-content-center custom">
        <div class="col-xl-12">
            <div class="card lp-form-card  p-4 shadow rounded-4">
                <div class="row gx-4">
                    <div class="col-md-5 pe-md-4 mb-4 mb-md-0">
                        <h2 class="h1 lp-dia-dos-pais-title">Compartilhe sua história</h2>
                        <p class="text-muted"><b>Conte para nós:</b> Qual o maior legado de ser pai?</p>
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/father.png" alt="Pai" class="img-fluid">
                    </div>
                    <div class="col-md-7">
                        <form id="homenagem-form" class="homenagem-pais-form" enctype="multipart/form-data">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label">Seu nome</label>
                                    <input type="text" name="h_name" class="form-control" required placeholder="Digite seu nome">
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label">Unidade de trabalho</label>
                                    <select name="h_unit" class="form-select form-control" required>
                                        <option value="">Selecione uma unidade</option>
                                        <option value="Hospital Cidade do Sol">Hospital Cidade do Sol</option>
                                        <option value="Hospital de Base">Hospital de Base</option>
                                        <option value="Hospital Regional de Santa Maria">Hospital Regional de Santa Maria</option>
                                        <option value="PO 700">PO 700</option>
                                        <option value="SIA">SIA</option>
                                        <option value="UPA Brazlândia">UPA Brazlândia</option>
                                        <option value="UPA Ceilândia I">UPA Ceilândia I</option>
                                        <option value="UPA Ceilândia II">UPA Ceilândia II</option>
                                        <option value="UPA Gama">UPA Gama</option>
                                        <option value="UPA Núcleo Bandeirante">UPA Núcleo Bandeirante</option>
                                        <option value="UPA Paranoá">UPA Paranoá</option>
                                        <option value="UPA Planaltina">UPA Planaltina</option>
                                        <option value="UPA Riacho Fundo II">UPA Riacho Fundo II</option>
                                        <option value="UPA Recanto das Emas">UPA Recanto das Emas</option>
                                        <option value="UPA Samambaia">UPA Samambaia</option>
                                        <option value="UPA São Sebastião">UPA São Sebastião</option>
                                        <option value="UPA Sobradinho">UPA Sobradinho</option>
                                        <option value="UPA Vicente Pires">UPA Vicente Pires</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Seu E-mail Institucional</label>
                                <input type="email" name="h_email" class="form-control" required placeholder="Ex: nome@igesdf.org.br">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Foto ou vídeo (máx 40s)</label>
                                <input type="file" name="h_media" accept="image/*,video/*" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Qual o maior legado de ser pai?</label>
                                <textarea id="h_message" name="h_message" class="form-control" rows="5" maxlength="1000" required></textarea>
                                <div class="form-text text-muted"><span id="h_message_counter">0</span>/1000 caracteres</div>
                            </div>
                            <button class="btn btn-primary btn-lg w-100" type="submit"><span class="dashicons dashicons-upload"></span> Enviar homenagem</button>
                        </form>
                    </div>
                </div>
            </div>
            <section class="lp-cards container my-5">
                <div class="d-flex align-items-center justify-content-between mb-4 flex-column flex-md-row gap-3">
                    <div>
                        <h2 class="h1 lp-dia-dos-pais-title">O que nossos pais dizem</h2>
                        <p class="text-muted mb-0">Histórias reais de carinho e inspiração.</p>
                    </div>

                </div>
                <div class="row gx-4 gy-4" id="hp-homenagem-grid">
                    <?php
                    $posts_per_page = defined('HP_HOMENAGEM_PER_PAGE') ? HP_HOMENAGEM_PER_PAGE : 12;
                    $q = new WP_Query([
                        'post_type' => 'homenagem',
                        'post_status' => 'publish',
                        'posts_per_page' => $posts_per_page,
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ]);

                    if ($q->have_posts()) {
                        while ($q->have_posts()) {
                            $q->the_post();
                            $pid = get_the_ID();
                            $name = get_post_meta($pid, 'homenagem_name', true) ?: get_the_title();
                            $unit = get_post_meta($pid, 'homenagem_unit', true);
                            $message = get_post_meta($pid, 'homenagem_message', true) ?: get_the_excerpt();
                            $short = mb_substr(strip_tags($message), 0, 70);
                            $preview = function_exists('hp_get_homenagem_preview_data') ? hp_get_homenagem_preview_data($pid) : array('type' => 'default', 'url' => get_template_directory_uri() . '/assets/images/default-avatar.png');
                            $likes = intval(get_post_meta($pid, 'homenagem_likes', true));
                    ?>
                            <div class="col-md-4">
                                <div class="card lp-story-card h-100 shadow-sm border rounded-4 p-3 btn-open" data-id="<?php echo $pid; ?>">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <?php if ($preview['type'] === 'video') : ?>
                                            <div class="hp-card-preview hp-card-preview--video" aria-label="Vídeo">
                                                <span class="dashicons dashicons-format-video"></span>
                                            </div>
                                        <?php elseif ($preview['type'] === 'image') : ?>
                                            <img src="<?php echo esc_url($preview['url']); ?>" class="avatar rounded-circle" alt="<?php echo esc_attr($name); ?>">
                                        <?php else : ?>
                                            <div class="avatar rounded-circle" alt="<?php echo esc_attr($name); ?>">
                                                <svg height="60px" width="60px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                                    viewBox="0 0 188.149 188.149" xml:space="preserve">
                                                    <g>
                                                        <g>
                                                            <defs>
                                                                <circle id="SVGID_1_" cx="94.074" cy="94.075" r="94.074" />
                                                            </defs>
                                                            <use xlink:href="#SVGID_1_" style="overflow:visible;fill:#E6E7E2;" />
                                                            <clipPath id="SVGID_2_">
                                                                <use xlink:href="#SVGID_1_" style="overflow:visible;" />
                                                            </clipPath>
                                                            <path style="clip-path:url(#SVGID_2_);fill:#ECC19C;" d="M126.708,153.946h-0.02c-2.041-1.545-4.178-2.919-6.429-4.159
			c-0.058-0.038-0.115-0.076-0.191-0.095c-10.646-5.876-17.857-17.209-17.857-30.239l-16.121-0.077
			c0,13.069-7.269,24.459-18.01,30.315c0,0-0.019,0-0.038,0.019c-2.271,1.24-4.445,2.633-6.506,4.159
			c-13.355,9.94-21.997,25.832-21.997,43.766h109.07C148.61,179.74,140.006,163.885,126.708,153.946z" />
                                                            <path style="clip-path:url(#SVGID_2_);fill:#168EF7;" d="M148.609,197.629H39.538c0-17.934,8.642-33.826,21.997-43.766
			c2.061-1.526,4.235-2.919,6.505-4.159c0.02-0.019,0.039-0.019,0.039-0.019c1.755-0.973,3.434-2.08,4.979-3.339
			c5.342,5.476,12.802,8.872,21.063,8.872c8.242,0,15.683-3.396,21.024-8.853c1.526,1.259,3.187,2.366,4.922,3.32
			c0.076,0.019,0.134,0.057,0.191,0.095c2.251,1.24,4.388,2.614,6.429,4.159h0.02C140.005,163.879,148.609,179.733,148.609,197.629z
			" />
                                                            <path style="clip-path:url(#SVGID_2_);fill:#ECC19C;" d="M52.217,38.091v42.836c0,28.976,25.437,52.465,41.858,52.465
			c16.419,0,41.858-23.489,41.858-52.465V38.091H52.217z" />
                                                            <path style="clip-path:url(#SVGID_2_);fill:#494846;" d="M129.114,30.207c-9.123-11.423-22.972-18.726-38.463-18.726
			c-27.521,0-49.81,22.972-49.81,51.301c0,15.036,6.267,28.556,16.274,37.932c-2.578-6.47-4.018-13.722-4.018-21.38
			c0-12.307,3.74-23.578,9.957-32.246c6.596,2.932,17.286,3.993,29.011,2.376c11.625-1.592,21.531-5.433,27.116-10.007
			c10.185,8.996,16.806,23.502,16.806,39.877c0,8.39-1.719,16.275-4.802,23.199c9.83-4.069,17.058-18.574,17.058-35.835
			C148.243,48.225,139.954,32.987,129.114,30.207z" />
                                                        </g>
                                                    </g>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h5 class="mb-1"><?php echo esc_html($name); ?></h5>
                                            <p class="text-muted small mb-0"><?php echo esc_html($unit); ?></p>
                                        </div>
                                    </div>
                                    <p class="card-text text-secondary mb-0 lp-dia-dos-pais-text">“<?php echo esc_html($short); ?>...”</p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <button class="btn btn-sm btn-outline-primary btn-like" data-id="<?php echo $pid; ?>" data-likes="<?php echo $likes; ?>"><svg height="15" viewBox="0 0 34 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M28.9181 4.24906C28.2054 3.53604 27.3592 2.97043 26.4279 2.58452C25.4965 2.19862 24.4983 2 23.4902 2C22.4821 2 21.4838 2.19862 20.5525 2.58452C19.6211 2.97043 18.7749 3.53604 18.0623 4.24906L16.5832 5.72813L15.1041 4.24906C13.6646 2.80949 11.7121 2.00075 9.67622 2.00075C7.64036 2.00075 5.68788 2.80949 4.24831 4.24906C2.80874 5.68863 2 7.64111 2 9.67697C2 11.7128 2.80874 13.6653 4.24831 15.1049L16.5832 27.4398L28.9181 15.1049C29.6311 14.3922 30.1967 13.546 30.5826 12.6147C30.9685 11.6833 31.1671 10.6851 31.1671 9.67697C31.1671 8.66884 30.9685 7.6706 30.5826 6.73926C30.1967 5.80792 29.6311 4.96174 28.9181 4.24906Z" stroke="#0094c6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg> <?php echo $likes; ?></button>
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
                <?php if ($q->max_num_pages > 1) : ?>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-primary btn-load-more" data-page="1"><span class="dashicons dashicons-image-rotate"></span> Carregar mais</button>
                    </div>
                <?php endif; ?>
            </section>
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
                        <span class="eyebrow eyebrow-light mb-2">Homenagem em destaque</span>
                        <h2 class="text-white lp-dia-dos-pais-title mt-2">“Ser pai é amar de um jeito que não cabe no peito, mas que transforma tudo ao redor.”</h2>

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
<section class="my-4 py-4">
    <div class="container">
        <h2 class="mb-3 h1 lp-dia-dos-pais-title">Onde há cuidado, há resultados</h2>
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
                    <div class="stat-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/coracao.svg" width="60" height="60"></div>
                    <h3 class="lp-dia-dos-pais-text h1"><?php echo intval($total); ?></h3>
                    <p>Homenagens recebidas</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                    <div class="stat-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/pais.svg" width="60" height="60"></div>
                    <h3 class="lp-dia-dos-pais-text h1"><?php echo intval($parents_count); ?></h3>
                    <p>Pais participantes</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                    <div class="stat-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/msg.svg" width="60" height="60"></div>
                    <h3 class="lp-dia-dos-pais-text h1"><?php echo intval($messages); ?></h3>
                    <p>Mensagens</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card rounded-4 p-4 text-center shadow-sm bg-white">
                    <div class="stat-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/unidades.svg" width="60" height="60"></div>
                    <h3 class="lp-dia-dos-pais-text h1"><?php echo intval($units_count); ?></h3>
                    <p>Unidades representadas</p>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var messageField = document.getElementById('h_message');
        var counter = document.getElementById('h_message_counter');
        var maxLength = messageField ? parseInt(messageField.getAttribute('maxlength') || '1000', 10) : 1000;

        if (!messageField || !counter) {
            return;
        }

        function updateCounter() {
            var value = messageField.value || '';
            counter.textContent = value.length;
        }

        messageField.addEventListener('input', updateCounter);
        updateCounter();
    });
</script>
<?php
get_footer();
