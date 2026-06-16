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
                <p>Novo conteúdo publicado</p>
                <div class="fs-notification-content">
                    ${post.thumbnail ? `<img src="${post.thumbnail}" alt="${post.title}" class="fs-notification-thumbnail">` : ''}
                    <div class="fs-notification-text">
                        <h4>${post.title}</h4>
                        <p>${post.excerpt}</p>
                        <a href="${post.url}" class="fs-notification-link">Ver agora &rarr;</a>
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
                <div class="fs-post-content"><strong>Numid</strong> ${post.content}</div>
                <div class="fs-post-meta">
                    <span class="fs-likes">❤ ${post.likes} Curtidas</span>
                    <span class="fs-comments">💬 ${post.comments} Comentários</span>
                </div>
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