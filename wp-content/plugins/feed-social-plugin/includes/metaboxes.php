<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
    add_meta_box('fs_media_gallery', 'Galeria de Mídias', 'fs_media_metabox_callback', 'feed-social', 'normal', 'high');
});

// Garante que o seletor de mídia do WordPress seja carregado
add_action('admin_enqueue_scripts', function ($hook) {
    global $post;
    if (('post.php' === $hook || 'post-new.php' === $hook) && 'feed-social' === ($post->post_type ?? '')) {
        wp_enqueue_media();
    }
});

function fs_media_metabox_callback($post) {
    $media_ids = get_post_meta($post->ID, '_fs_media_ids', true);
    $ids_array = array_filter(explode(',', $media_ids));
    wp_nonce_field('fs_save_media', 'fs_media_nonce');
    ?>
    <style>
        #fs-media-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
        .fs-preview-item { position: relative; border: 1px solid #ddd; padding: 4px; background: #fff; line-height: 0; }
        .fs-preview-item img, .fs-preview-item video { width: 100%; height: 100px; object-fit: cover; }
        .fs-remove-media { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; border: none; cursor: pointer; font-size: 12px; line-height: 20px; text-align: center; padding: 0; }
    </style>

    <div id="fs-media-container">
        <input type="hidden" name="fs_media_ids" id="fs_media_ids" value="<?php echo esc_attr($media_ids); ?>">
        <button type="button" class="button button-primary" id="fs_upload_button">Adicionar Mídias (Imagens/Vídeos)</button>
        <div id="fs-media-preview">
            <?php 
            if (!empty($ids_array)) {
                foreach ($ids_array as $id) {
                    $url = wp_get_attachment_url($id);
                    $mime = get_post_mime_type($id);
                    echo '<div class="fs-preview-item" data-id="' . $id . '">';
                    echo '<button type="button" class="fs-remove-media">×</button>';
                    if (strpos($mime, 'video') !== false) {
                        echo '<video src="' . $url . '"></video>';
                    } else {
                        echo '<img src="' . $url . '">';
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            var frame;
            var $mediaIdsInput = $('#fs_media_ids');
            var $previewContainer = $('#fs-media-preview');

            $('#fs_upload_button').on('click', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Selecionar Mídias',
                    button: { text: 'Usar estas mídias' },
                    library: { type: ['image', 'video'] },
                    multiple: true
                });

                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    var ids = $mediaIdsInput.val() ? $mediaIdsInput.val().split(',') : [];

                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        if (ids.indexOf(attachment.id.toString()) === -1) {
                            ids.push(attachment.id);
                            
                            var html = '<div class="fs-preview-item" data-id="' + attachment.id + '">';
                            html += '<button type="button" class="fs-remove-media">×</button>';
                            
                            if (attachment.type === 'video') {
                                html += '<video src="' + attachment.url + '"></video>';
                            } else {
                                html += '<img src="' + attachment.url + '">';
                            }
                            html += '</div>';
                            
                            $previewContainer.append(html);
                        }
                    });

                    $mediaIdsInput.val(ids.join(','));
                });

                frame.open();
            });

            // Remover mídia
            $previewContainer.on('click', '.fs-remove-media', function() {
                var $item = $(this).closest('.fs-preview-item');
                var idToRemove = $item.data('id').toString();
                var ids = $mediaIdsInput.val().split(',');

                ids = ids.filter(function(id) {
                    return id !== idToRemove;
                });

                $mediaIdsInput.val(ids.join(','));
                $item.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Tornar a galeria ordenável (opcional, requer jquery-ui-sortable)
            if ($.fn.sortable) {
                $previewContainer.sortable({
                    update: function() {
                        var newIds = [];
                        $previewContainer.find('.fs-preview-item').each(function() {
                            newIds.push($(this).data('id'));
                        });
                        $mediaIdsInput.val(newIds.join(','));
                    }
                });
            }
        });
    </script>
    <?php
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['fs_media_nonce']) || !wp_verify_nonce($_POST['fs_media_nonce'], 'fs_save_media')) return;
    if (isset($_POST['fs_media_ids'])) {
        update_post_meta($post_id, '_fs_media_ids', sanitize_text_field($_POST['fs_media_ids']));
    }
});