jQuery(document).ready(function ($) {
    const $feedContainer = $('#fs-posts-container');
    const $loadingIndicator = $('#fs-loading-indicator');
    const $scrollSentinel = $('#fs-scroll-sentinel');
    const $noMorePosts = $('#fs-no-more-posts');
    const $loadingText = $loadingIndicator.find('.fs-loading-text');
    const $noMorePostsText = $noMorePosts.find('.fs-no-more-posts-text');
    const sentinelEl = $scrollSentinel[0];

    let currentOffset = 0;
    let hasMore = true;
    let isLoading = false;

    function setLoadingVisible(visible) {
        $loadingIndicator.prop('hidden', !visible);
    }

    function updateSentinelVisibility() {
        if (!$scrollSentinel.length) {
            return;
        }

        if (hasMore) {
            $scrollSentinel.show();
            $noMorePosts.prop('hidden', true);
        } else {
            $scrollSentinel.hide();
            $noMorePosts.prop('hidden', false);
        }
    }

    function checkAndLoadMore() {
        if (!hasMore || isLoading || !sentinelEl) {
            return;
        }

        const rect = sentinelEl.getBoundingClientRect();
        if (rect.top <= window.innerHeight + 200) {
            fetchPosts();
        }
    }

    const likedPosts = new Set(JSON.parse(localStorage.getItem('fs_liked_posts') || '[]'));

    function saveLikedPosts() {
        localStorage.setItem('fs_liked_posts', JSON.stringify([...likedPosts]));
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function getUserEmail(promptText) {
        let email = localStorage.getItem('fs_user_email');
        if (email && isValidEmail(email)) {
            return email;
        }

        email = window.prompt(promptText || fs_feed_data.like_prompt);
        if (email && isValidEmail(email)) {
            localStorage.setItem('fs_user_email', email.trim());
            return email.trim();
        }

        return null;
    }

    function getUserName() {
        let name = localStorage.getItem('fs_user_name');
        if (name) {
            return name;
        }

        name = window.prompt(fs_feed_data.comment_name_prompt);
        if (name) {
            localStorage.setItem('fs_user_name', name.trim());
            return name.trim();
        }

        return null;
    }

    function restHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-WP-Nonce': fs_feed_data.rest_nonce,
        };
    }

    function requestNotificationPermission() {
        if (!('Notification' in window) || Notification.permission !== 'default') {
            return;
        }

        Notification.requestPermission();
    }

    function showBrowserNotification(post) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return false;
        }

        const notification = new Notification(fs_feed_data.notification_title, {
            body: post.excerpt || fs_feed_data.notification_body,
            icon: post.thumbnail || undefined,
            tag: 'fs-post-' + post.id,
        });

        notification.onclick = function () {
            window.focus();
            window.location.href = fs_feed_data.feed_page_url;
            notification.close();
        };

        return true;
    }

    function showFeedNotification(post) {
        showBrowserNotification(post);

        const $notification = $(`
            <div class="fs-notification-toast">
                <p>${fs_feed_data.notification_body}</p>
                <div class="fs-notification-content">
                    ${post.thumbnail ? `<img src="${post.thumbnail}" alt="${post.title}" class="fs-notification-thumbnail">` : ''}
                    <div class="fs-notification-text">
                        <p>${post.excerpt || post.title}</p>
                        <a href="${fs_feed_data.feed_page_url}" class="fs-notification-link">Ver agora &rarr;</a>
                    </div>
                </div>
            </div>
        `);

        $('body').append($notification);
        $notification.fadeIn().delay(8000).fadeOut(function () {
            $(this).remove();
        });
    }

    function initSse() {
        if (typeof EventSource === 'undefined' || !fs_feed_data.sse_url) {
            return;
        }

        const feedEvents = new EventSource(fs_feed_data.sse_url);

        feedEvents.addEventListener('new-content-feed', function (event) {
            try {
                const post = JSON.parse(event.data);
                showFeedNotification(post);

                if (fs_feed_data.has_feed && $feedContainer.length) {
                    currentOffset = 0;
                    hasMore = true;
                    $feedContainer.empty();
                    updateSentinelVisibility();
                    fetchPosts();
                }
            } catch (error) {
                console.error('Erro ao processar evento SSE:', error);
            }
        });

        feedEvents.onopen = function () {
            console.log('Feed Social SSE conectado.');
        };

        feedEvents.onerror = function () {
            console.warn('Feed Social SSE reconectando...');
        };
    }

    requestNotificationPermission();

    if (fs_feed_data.has_feed) {
        $loadingText.text(fs_feed_data.loading_text);
        $noMorePostsText.text(fs_feed_data.no_more_posts_text);
    }

    function formatCount(count) {
        return count > 0 ? count : '';
    }

    function updateLikeUI($post, count, liked) {
        const $likes = $post.find('.fs-likes');
        $likes.toggleClass('fs-liked', liked);
        $likes.find('.fs-count').text(formatCount(count));
    }

    function updateCommentUI($post, count) {
        $post.find('.fs-comments .fs-count').text(formatCount(count));
    }

    const $videoModal = $('#fs-video-modal');
    const $videoModalPlayer = $videoModal.find('.fs-video-modal-player');

    function isVideoMedia(media) {
        return media.type && media.type.startsWith('video');
    }

    function getVideoPoster(media, postThumbnail) {
        if (media.poster) {
            return media.poster;
        }
        return postThumbnail || '';
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderMediaItem(media, postTitle, postThumbnail) {
        if (isVideoMedia(media)) {
            const poster = getVideoPoster(media, postThumbnail);

            if (poster) {
                return `
                    <button type="button" class="fs-video-cover" data-video-url="${escapeHtml(media.url)}" aria-label="Reproduzir vídeo">
                        <img src="${escapeHtml(poster)}" alt="${escapeHtml(postTitle)}">
                        <span class="fs-video-play-icon" aria-hidden="true"></span>
                    </button>
                `;
            }

            return `<video src="${escapeHtml(media.url)}" controls muted playsinline></video>`;
        }

        return `<img src="${escapeHtml(media.url)}" alt="${escapeHtml(postTitle)}">`;
    }

    function openVideoModal(videoUrl) {
        if (!$videoModal.length || !videoUrl) {
            return;
        }

        $videoModalPlayer.attr('src', videoUrl);
        $videoModal.prop('hidden', false);
        $('body').addClass('fs-video-modal-open');

        const playPromise = $videoModalPlayer[0].play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () {
                // Autoplay pode ser bloqueado; o usuário inicia pelo controle nativo.
            });
        }
    }

    function closeVideoModal() {
        console.log('Fechando modal de vídeo...');
        if (!$videoModal.length) {
            return;
        }

        const player = $videoModalPlayer[0];
        if (player) {
            player.pause();
            player.removeAttribute('src');
            player.load();
            console.log('Vídeo pausado e src removido.');
        }

        $videoModal.prop('hidden', true);
        $('body').removeClass('fs-video-modal-open');
    }

    function renderPost(post) {
        let mediaHtml = '';
        const mediaGallery = post.media_gallery || [];
        const isLiked = likedPosts.has(post.id);
        const postThumbnail = post.thumbnail || '';

        if (mediaGallery.length > 1) {
            mediaHtml += '<div class="swiper fs-media-carousel">';
            mediaHtml += '<div class="swiper-wrapper">';
            mediaGallery.forEach(function (media) {
                mediaHtml += '<div class="swiper-slide">';
                mediaHtml += renderMediaItem(media, post.title, postThumbnail);
                mediaHtml += '</div>';
            });
            mediaHtml += '</div>';
            mediaHtml += '<div class="swiper-pagination"></div>';
            mediaHtml += '<div class="swiper-button-prev"></div>';
            mediaHtml += '<div class="swiper-button-next"></div>';
            mediaHtml += '</div>';
        } else if (mediaGallery.length === 1) {
            mediaHtml += renderMediaItem(mediaGallery[0], post.title, postThumbnail);
        } else if (postThumbnail) {
            mediaHtml += `<img src="${escapeHtml(postThumbnail)}" alt="${escapeHtml(post.title)}">`;
        }

        const postHtml = `<div class="fs-post-header">
                    <div class="fs-post-author">
                        <div class="fs-post-author-avatar"></div>
                        <span class="fs-post-author-name">Iges+</span>
                    </div>
                </div>
            <article class="fs-post-item" data-post-id="${post.id}">
                
                <div class="fs-post-thumbnail">
                    ${mediaHtml}
                </div>
                <div class="fs-post-meta">
                    <button type="button" class="fs-likes${isLiked ? ' fs-liked' : ''}" aria-label="Curtir">
                        <svg aria-hidden="true" fill="currentColor" height="24" viewBox="0 0 24 24" width="24">
                            <path d="M16.792 3.904A4.989 4.989 0 0 1 21.5 9.122c0 3.072-2.652 4.959-5.197 7.222-2.512 2.243-3.865 3.469-4.303 3.752-.477-.309-2.143-1.823-4.303-3.752C5.141 14.072 2.5 12.167 2.5 9.122a4.989 4.989 0 0 1 4.708-5.218 4.21 4.21 0 0 1 3.675 1.941c.84 1.175.98 1.763 1.12 1.763s.278-.588 1.11-1.766a4.17 4.17 0 0 1 3.679-1.938m0-2a6.04 6.04 0 0 0-4.797 2.127 6.052 6.052 0 0 0-4.787-2.127A6.985 6.985 0 0 0 .5 9.122c0 3.61 2.55 5.827 5.015 7.97.283.246.569.494.853.747l1.027.918a44.998 44.998 0 0 0 3.518 3.018 2 2 0 0 0 2.174 0 45.263 45.263 0 0 0 3.626-3.115l.922-.824c.293-.26.59-.519.885-.774 2.334-2.025 4.98-4.32 4.98-7.94a6.985 6.985 0 0 0-6.708-7.218Z"></path>
                        </svg>
                        <span class="fs-count">${formatCount(post.likes)}</span>
                    </button>
                    <button type="button" class="fs-comments" aria-label="Comentar">
                        <svg aria-hidden="true" fill="currentColor" height="24" viewBox="0 0 24 24" width="24">
                            <path d="M20.656 17.008a9.993 9.993 0 1 0-3.59 3.615L22 22Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <span class="fs-count">${formatCount(post.comments)}</span>
                    </button>
                </div>
                <div class="fs-post-content">${post.content}</div>
                <div class="fs-comments-panel" hidden>
                    <div class="fs-comments-list"></div>
                    <form class="fs-comment-form">
                        <textarea name="comment" rows="3" placeholder="Escreva um comentário..." required></textarea>
                        <button type="submit" class="fs-comment-submit">Enviar</button>
                    </form>
                </div>
            </article>
        `;

        $feedContainer.append(postHtml);

        if (mediaGallery.length > 1 && typeof Swiper !== 'undefined') {
            new Swiper($feedContainer.find('.fs-media-carousel:last')[0], {
                loop: true,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
        }
    }

    async function fetchPosts() {
        if (!$feedContainer.length || isLoading || !hasMore) {
            return;
        }

        const perPage = currentOffset === 0
            ? fs_feed_data.initial_posts
            : fs_feed_data.posts_per_load;

        isLoading = true;
        setLoadingVisible(true);

        try {
            const response = await fetch(`${fs_feed_data.rest_url}?offset=${currentOffset}&per_page=${perPage}`, {
                headers: {
                    'X-WP-Nonce': fs_feed_data.rest_nonce,
                },
            });
            const data = await response.json();

            if (data.posts && data.posts.length > 0) {
                data.posts.forEach(renderPost);
                currentOffset += data.posts.length;
                hasMore = Boolean(data.has_more);
            } else {
                hasMore = false;
            }
        } catch (error) {
            console.error('Erro ao carregar posts:', error);
        } finally {
            isLoading = false;
            setLoadingVisible(false);
            updateSentinelVisibility();
            checkAndLoadMore();
        }
    }

    async function loadComments($post) {
        const postId = $post.data('post-id');
        const $list = $post.find('.fs-comments-list');

        $list.html('<p class="fs-comments-loading">Carregando comentários...</p>');

        try {
            const response = await fetch(`${fs_feed_data.comments_url}?post_id=${postId}`, {
                headers: {
                    'X-WP-Nonce': fs_feed_data.rest_nonce,
                },
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro ao carregar comentários');
            }

            if (!data.comments.length) {
                $list.html('<p class="fs-comments-empty">Nenhum comentário ainda.</p>');
                return;
            }

            const items = data.comments.map(function (item) {
                return `
                    <div class="fs-comment-item">
                        <strong>${item.name}</strong>
                        <p>${item.comment}</p>
                    </div>
                `;
            }).join('');

            $list.html(items);
        } catch (error) {
            $list.html('<p class="fs-comments-error">Não foi possível carregar os comentários.</p>');
            console.error(error);
        }
    }

    async function handleLike($post) {
        const postId = $post.data('post-id');
        const email = getUserEmail(fs_feed_data.like_prompt);

        if (!email) {
            return;
        }

        const $likes = $post.find('.fs-likes');
        $likes.prop('disabled', true);

        try {
            const response = await fetch(fs_feed_data.like_url, {
                method: 'POST',
                headers: restHeaders(),
                body: JSON.stringify({
                    post_id: postId,
                    email: email,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro ao curtir');
            }

            if (data.action === 'liked') {
                likedPosts.add(postId);
            } else {
                likedPosts.delete(postId);
            }

            saveLikedPosts();
            updateLikeUI($post, data.new_count, data.action === 'liked');
        } catch (error) {
            console.error('Erro ao curtir:', error);
            window.alert('Não foi possível registrar a curtida. Tente novamente.');
        } finally {
            $likes.prop('disabled', false);
        }
    }

    async function handleCommentSubmit($post, $form) {
        const postId = $post.data('post-id');
        const name = getUserName();
        const email = getUserEmail(fs_feed_data.comment_email_prompt);
        const comment = $form.find('textarea[name="comment"]').val().trim();

        if (!name || !email || !comment) {
            return;
        }

        const $submit = $form.find('.fs-comment-submit');
        $submit.prop('disabled', true);

        try {
            const response = await fetch(fs_feed_data.comment_url, {
                method: 'POST',
                headers: restHeaders(),
                body: JSON.stringify({
                    post_id: postId,
                    name: name,
                    email: email,
                    comment: comment,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro ao comentar');
            }

            $form.find('textarea[name="comment"]').val('');
            updateCommentUI($post, data.new_count);
            await loadComments($post);
        } catch (error) {
            console.error('Erro ao comentar:', error);
            window.alert('Não foi possível enviar o comentário. Tente novamente.');
        } finally {
            $submit.prop('disabled', false);
        }
    }

    $feedContainer.on('click', '.fs-video-cover', function () {
        openVideoModal($(this).data('video-url'));
    });

    $videoModal.on('click', '.fs-video-modal-backdrop, .fs-video-modal-close', function () {
        closeVideoModal();
    });

    $(document).on('keydown.fsVideoModal', function (event) {
        if (event.key === 'Escape' && $videoModal.length && !$videoModal.prop('hidden')) {
            closeVideoModal();
        }
    });

    $feedContainer.on('click', '.fs-likes', function () {
        handleLike($(this).closest('.fs-post-item'));
    });

    $feedContainer.on('click', '.fs-comments', function () {
        const $post = $(this).closest('.fs-post-item');
        const $panel = $post.find('.fs-comments-panel');
        const isHidden = $panel.prop('hidden');

        $('.fs-comments-panel').prop('hidden', true);

        if (isHidden) {
            $panel.prop('hidden', false);
            loadComments($post);
        }
    });

    $feedContainer.on('submit', '.fs-comment-form', function (event) {
        event.preventDefault();
        const $form = $(this);
        handleCommentSubmit($form.closest('.fs-post-item'), $form);
    });

    if ($feedContainer.length && sentinelEl) {
        const observer = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting && !isLoading && hasMore) {
                fetchPosts();
            }
        }, {
            root: null,
            rootMargin: '200px 0px',
            threshold: 0,
        });

        observer.observe(sentinelEl);
        updateSentinelVisibility();
        fetchPosts();

        $(window).on('scroll.fsFeed resize.fsFeed', checkAndLoadMore);
    }

    initSse();
});
