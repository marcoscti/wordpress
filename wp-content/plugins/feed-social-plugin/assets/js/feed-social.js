jQuery(document).ready(function($) {
    const $feedContainer = $('#fs-posts-container');
    const $loadingIndicator = $('#fs-loading-indicator');
    const $noMorePosts = $('#fs-no-more-posts');
    const $loadingText = $loadingIndicator.find('.fs-loading-text');
    const $noMorePostsText = $noMorePosts.find('.fs-no-more-posts-text');

    let currentPage = 1;
    let totalPages = 1;
    let isLoading = false;
    let notificationTimeout;

    // SSE Client
    if (typeof EventSource !== 'undefined') {
        const feedEvents = new EventSource('/feed-social-sse');

        feedEvents.addEventListener(
            'new-content-feed',
            function(event) {
                const post = JSON.parse(event.data);
                console.log('New content received via SSE:', post);
                showFeedNotification(post);

            }
        );

        feedEvents.onerror = function(event) {
            console.error('SSE Error:', event);
            // Optionally, try to reconnect after a delay
        };
    } else {
        console.warn('Server-Sent Events not supported by this browser.');
    }

    function showFeedNotification(post) {
        const $notification = $(`
            <div class="fs-notification-toast">
                <p>Acabou de publicar um post</p>
                <div class="fs-notification-content">
                    ${post.thumbnail ? `<img src="${post.thumbnail}" alt="${post.title}" class="fs-notification-thumbnail">` : ''}
                    <div class="fs-notification-text">
                        <p>${post.excerpt}</p>
                        <a href="/feed-social" class="fs-notification-link">Ver agora &rarr;</a>
                    </div>
                </div>
            </div>
        `);

        $('body').append($notification);
        $notification.fadeIn().delay(5000).fadeOut(function() { // Display for 5 seconds
            $(this).remove();
        });
    }

    $loadingText.text(fs_feed_data.loading_text);
    $noMorePostsText.text(fs_feed_data.no_more_posts_text);

    // Function to render a single post
    function renderPost(post) {
        let mediaHtml = '';
        const mediaGallery = post.media_gallery || [];

        if (mediaGallery.length > 1) {
            // Carousel with Swiper.js
            mediaHtml += '<div class="swiper fs-media-carousel">';
            mediaHtml += '<div class="swiper-wrapper">';
            mediaGallery.forEach(media => {
                mediaHtml += '<div class="swiper-slide">';
                if (media.type && media.type.startsWith('video')) {
                    mediaHtml += `<video src="${media.url}" controls muted playsinline></video>`;
                } else {
                    mediaHtml += `<img src="${media.url}" alt="${post.title}">`;
                }
                mediaHtml += '</div>';
            });
            mediaHtml += '</div>';
            mediaHtml += '<div class="swiper-pagination"></div>';
            mediaHtml += '<div class="swiper-button-prev"></div>';
            mediaHtml += '<div class="swiper-button-next"></div>';
            mediaHtml += '</div>';
        } else if (mediaGallery.length === 1) {
            const media = mediaGallery[0];
            // Single media item
            if (media.type && media.type.startsWith('video')) {
                mediaHtml += `<video src="${media.url}" controls muted playsinline></video>`;
            } else {
                mediaHtml += `<img src="${media.url}" alt="${post.title}">`;
            }
        } else if (post.thumbnail) {
            // Fallback to featured image if no gallery media
            mediaHtml += `<img src="${post.thumbnail}" alt="${post.title}">`;
        }

        const postHtml = `
            <article class="fs-post-item" data-post-id="${post.id}">
                <div class="fs-post-thumbnail">
                    ${mediaHtml}
                </div>
                <div class="fs-post-meta">
                    <span class="fs-likes"><svg aria-label="Curtir" class="x1lliihq x1n2onr6 xyb1xck" fill="currentColor" height="24" role="img" viewBox="0 0 24 24" width="24"><title>Curtir</title><path d="M16.792 3.904A4.989 4.989 0 0 1 21.5 9.122c0 3.072-2.652 4.959-5.197 7.222-2.512 2.243-3.865 3.469-4.303 3.752-.477-.309-2.143-1.823-4.303-3.752C5.141 14.072 2.5 12.167 2.5 9.122a4.989 4.989 0 0 1 4.708-5.218 4.21 4.21 0 0 1 3.675 1.941c.84 1.175.98 1.763 1.12 1.763s.278-.588 1.11-1.766a4.17 4.17 0 0 1 3.679-1.938m0-2a6.04 6.04 0 0 0-4.797 2.127 6.052 6.052 0 0 0-4.787-2.127A6.985 6.985 0 0 0 .5 9.122c0 3.61 2.55 5.827 5.015 7.97.283.246.569.494.853.747l1.027.918a44.998 44.998 0 0 0 3.518 3.018 2 2 0 0 0 2.174 0 45.263 45.263 0 0 0 3.626-3.115l.922-.824c.293-.26.59-.519.885-.774 2.334-2.025 4.98-4.32 4.98-7.94a6.985 6.985 0 0 0-6.708-7.218Z"></path></svg> ${post.likes>0 ? post.likes : ''}</span>
                    <span class="fs-comments"><svg aria-label="Comentar" class="x1lliihq x1n2onr6 x5n08af" fill="currentColor" height="24" role="img" viewBox="0 0 24 24" width="24"><title>Comentar</title><path d="M20.656 17.008a9.993 9.993 0 1 0-3.59 3.615L22 22Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="2"></path></svg> ${post.comments > 0 ? post.comments.length : ''}</span>
                </div>
                <div class="fs-post-content">${post.content}</div>
                
            </article>
        `;
        $feedContainer.append(postHtml);

        // Initialize Swiper for new carousels
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

    // Function to fetch posts from the REST API
    async function fetchPosts() {
        if (isLoading || (currentPage > totalPages && currentPage !== 1)) { // Allow initial load even if totalPages is 0
            return;
        }

        isLoading = true;
        $loadingIndicator.show();

        try {
            const response = await fetch(`${fs_feed_data.rest_url}?page=${currentPage}&per_page=${fs_feed_data.posts_per_load}`, {
                headers: {
                    'X-WP-Nonce': fs_feed_data.rest_nonce
                }
            });
            const data = await response.json();

            if (data.posts && data.posts.length > 0) {
                data.posts.forEach(renderPost);
                currentPage++;
                totalPages = data.total_pages;
            } else {
                totalPages = 0; // No more posts
            }
        } catch (error) {
            console.error('Erro ao carregar posts:', error);
        } finally {
            isLoading = false;
            $loadingIndicator.hide();
            if (currentPage > totalPages) {
                $noMorePosts.show();
            }
        }
    }

    // Intersection Observer for infinite scrolling
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isLoading && currentPage <= totalPages) {
            fetchPosts();
        }
    }, { threshold: 0.5 });

    // Observe the loading indicator
    observer.observe($loadingIndicator[0]);

    // Initial load
    fetchPosts();
});