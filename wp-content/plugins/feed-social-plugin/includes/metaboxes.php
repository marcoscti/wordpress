<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function ($post_type) {
    if ($post_type === 'feed-social') {
        add_meta_box('fs_media_gallery', 'Galeria de Mídias', 'fs_media_metabox_callback', 'feed-social', 'normal', 'high');
    }

    if ($post_type === 'social_story') {
        add_meta_box('fs_story_options', 'Opções do Story', 'fs_story_options_metabox_callback', 'social_story', 'side', 'default');
        add_meta_box('fs_story_video', 'Vídeo do Story', 'fs_story_video_metabox_callback', 'social_story', 'side', 'default');
    }
});

// Garante que o seletor de mídia do WordPress seja carregado
add_action('admin_enqueue_scripts', function ($hook) {
    global $post;
    if (('post.php' === $hook || 'post-new.php' === $hook) && in_array(($post->post_type ?? ''), ['feed-social', 'social_story'])) {
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

function fs_story_options_metabox_callback($post) {
    wp_nonce_field('fs_save_story_options', 'fs_story_options_nonce');
    $expires = get_post_meta($post->ID, '_fs_story_expires', true);
    ?>
    <p>
        <input type="checkbox" id="fs_story_expires" name="fs_story_expires" value="yes" <?php checked($expires, 'yes'); ?> checked/>
        <label for="fs_story_expires">Expirar em 24 horas</label>
    </p>
    <p class="description">
        Se marcado, este story não será mais exibido 24 horas após a publicação.
    </p>
    <?php
}

function fs_story_video_metabox_callback($post) {
    wp_nonce_field('fs_save_story_video', 'fs_story_video_nonce');
    $video_id = get_post_meta($post->ID, '_fs_story_video_id', true);
    $video_url = $video_id ? wp_get_attachment_url($video_id) : '';
    ?>
    <div id="fs-story-video-container">
        <input type="hidden" name="fs_story_video_id" id="fs_story_video_id" value="<?php echo esc_attr($video_id); ?>">
        <div id="fs-story-video-preview" style="margin-bottom: 10px;">
            <?php if ($video_url): ?>
                <video src="<?php echo esc_url($video_url); ?>" style="max-width:100%; height:auto;" controls></video>
            <?php endif; ?>
        </div>
        <button type="button" class="button" id="fs_upload_story_video_button">Selecionar Vídeo</button>
        <button type="button" class="button" id="fs_remove_story_video_button" style="<?php echo $video_id ? '' : 'display:none;'; ?>">Remover Vídeo</button>
    </div>
    <script>
    jQuery(document).ready(function($){
        var frame;
        $('#fs_upload_story_video_button').on('click', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'Selecionar Vídeo para o Story',
                button: { text: 'Usar este vídeo' },
                library: { type: 'video' },
                multiple: false
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#fs_story_video_id').val(attachment.id);
                $('#fs_story_video_preview').html('<video src="' + attachment.url + '" style="max-width:100%; height:auto;" controls></video>');
                $('#fs_remove_story_video_button').show();
            });
            frame.open();
        });
        $('#fs_remove_story_video_button').on('click', function(e){
            e.preventDefault();
            $('#fs_story_video_id').val('');
            $('#fs_story_video_preview').empty();
            $(this).hide();
        });
    });
    </script>
    <?php
}

add_action('save_post', function ($post_id, $post) {
    if ($post->post_type === 'feed-social') {
        if (!isset($_POST['fs_media_nonce']) || !wp_verify_nonce($_POST['fs_media_nonce'], 'fs_save_media')) return;
        if (isset($_POST['fs_media_ids'])) {
            update_post_meta($post_id, '_fs_media_ids', sanitize_text_field($_POST['fs_media_ids']));
        }
    }

    if ($post->post_type === 'social_story') {
        if (!isset($_POST['fs_story_options_nonce']) || !wp_verify_nonce($_POST['fs_story_options_nonce'], 'fs_save_story_options')) return;
        $expires = isset($_POST['fs_story_expires']) && $_POST['fs_story_expires'] === 'yes' ? 'yes' : 'no';
        update_post_meta($post_id, '_fs_story_expires', $expires);

        if (!isset($_POST['fs_story_video_nonce']) || !wp_verify_nonce($_POST['fs_story_video_nonce'], 'fs_save_story_video')) return;
        $video_id = isset($_POST['fs_story_video_id']) ? intval($_POST['fs_story_video_id']) : 0;
        update_post_meta($post_id, '_fs_story_video_id', $video_id);
    }
}, 10, 2);