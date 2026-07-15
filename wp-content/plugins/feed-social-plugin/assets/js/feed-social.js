jQuery(document).ready(function ($) {
  const $feedContainer = $("#fs-posts-container");
  const $loadingIndicator = $("#fs-loading-indicator");
  const $scrollSentinel = $("#fs-scroll-sentinel");
  const $noMorePosts = $("#fs-no-more-posts");
  const $loadingText = $loadingIndicator.find(".fs-loading-text");
  const $noMorePostsText = $noMorePosts.find(".fs-no-more-posts-text");
  const sentinelEl = $scrollSentinel[0];

  let currentOffset = 0;
  let hasMore = true;
  let isLoading = false;
  const postsPerRow = 4;
  let pendingBatch = [];

  function setLoadingVisible(visible) {
    $loadingIndicator.prop("hidden", !visible);
  }

  function updateSentinelVisibility() {
    if (!$scrollSentinel.length) {
      return;
    }

    if (hasMore) {
      $scrollSentinel.show();
      $noMorePosts.prop("hidden", true);
    } else {
      $scrollSentinel.hide();
      $noMorePosts.prop("hidden", false);
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

  function flushPendingPosts() {
    if (!pendingBatch.length) {
      return;
    }

    const itemsToRender = pendingBatch.splice(0, postsPerRow);
    itemsToRender.forEach(function (post) {
      renderPost(post);
    });
  }

  const likedPosts = new Set(
    JSON.parse(localStorage.getItem("fs_liked_posts") || "[]"),
  );
  const loadedPosts = {};
  let currentPostId = null;
  let modalSwiperInstance = null;
  let hasOpenedPostFromUrl = false;
  function saveLikedPosts() {
    localStorage.setItem("fs_liked_posts", JSON.stringify([...likedPosts]));
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function getUserEmail(promptText) {
    let email = localStorage.getItem("fs_user_email");
    if (email && isValidEmail(email)) {
      return email;
    }

    email = window.prompt(`Informe seu E-mail Institucional:`);
    if (email && isValidEmail(email)) {
      localStorage.setItem("fs_user_email", email.trim());
      return email.trim();
    }

    return null;
  }

  function saveUserProfile(name, email) {
    const normalizedName = (name || "").trim();
    const normalizedEmail = (email || "").trim();

    if (!normalizedEmail) {
      return;
    }

    if (normalizedName) {
      localStorage.setItem("fs_user_name", normalizedName);
    }

    localStorage.setItem("fs_user_email", normalizedEmail);

    $.ajax({
      url: fs_feed_data.ajax_url,
      type: "POST",
      data: {
        action: "fs_save_user_profile",
        name: normalizedName,
        email: normalizedEmail,
      },
    });
  }

  function getUserName() {
    let name = localStorage.getItem("fs_user_name");
    if (name) {
      return name;
    }

    name = window.prompt(`Informe Seu Nome e Setor Ex: Marcos ASCOM`);
    if (name) {
      localStorage.setItem("fs_user_name", name.trim());
      return name.trim();
    }

    return null;
  }

  function restHeaders() {
    return {
      "Content-Type": "application/json",
      "X-WP-Nonce": fs_feed_data.rest_nonce,
    };
  }

  function requestNotificationPermission() {
    if (!("Notification" in window) || Notification.permission !== "default") {
      return;
    }

    Notification.requestPermission();
  }

  function showBrowserNotification(post) {
    if (!("Notification" in window) || Notification.permission !== "granted") {
      return false;
    }

    const notification = new Notification(fs_feed_data.notification_title, {
      body: post.excerpt || fs_feed_data.notification_body,
      icon: post.thumbnail || undefined,
      tag: "fs-post-" + post.id,
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
                    ${post.thumbnail ? `<img src="${post.thumbnail}" alt="${post.title}" class="fs-notification-thumbnail">` : ""}
                    <div class="fs-notification-text">
                        <p>${post.excerpt || post.title}</p>
                        <a href="${fs_feed_data.feed_page_url}" class="fs-notification-link">Ver agora &rarr;</a>
                    </div>
                </div>
            </div>
        `);

    $("body").append($notification);
    $notification
      .fadeIn()
      .delay(8000)
      .fadeOut(function () {
        $(this).remove();
      });
  }

  function initSse() {
    if (typeof EventSource === "undefined" || !fs_feed_data.sse_url) {
      return;
    }

    const feedEvents = new EventSource(fs_feed_data.sse_url);

    feedEvents.addEventListener("new-content-feed", function (event) {
      try {
        const post = JSON.parse(event.data);
        showFeedNotification(post);

        if (fs_feed_data.has_feed && $feedContainer.length) {
          currentOffset = 0;
          hasMore = true;
          pendingBatch = [];
          $feedContainer.empty();
          updateSentinelVisibility();
          fetchPosts();
        }
      } catch (error) {
        console.error("Erro ao processar evento SSE:", error);
      }
    });

    feedEvents.onopen = function () {
      console.log("Feed Social SSE conectado.");
    };

    feedEvents.onerror = function () {
      console.warn("Feed Social SSE reconectando...");
    };
  }

  requestNotificationPermission();

  if (fs_feed_data.has_feed) {
    $loadingText.text(fs_feed_data.loading_text);
    $noMorePostsText.text(fs_feed_data.no_more_posts_text);
  }

  function formatCount(count) {
    return count > 0 ? count : "";
  }

  function isVideoMedia(media) {
    return media.type && media.type.startsWith("video");
  }

  function getVideoPoster(media, postThumbnail) {
    if (media.poster) {
      return media.poster;
    }
    return postThumbnail || "";
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function renderMediaItem(
    media,
    postTitle,
    postThumbnail,
    insideModal = false,
  ) {
    if (!media) {
      return "";
    }

    if (isVideoMedia(media)) {
      const poster = getVideoPoster(media, postThumbnail);

      if (insideModal) {
        return `
            <video
                controls
                autoplay
                playsinline
                preload="metadata"
                poster="${escapeHtml(poster)}"
                src="${escapeHtml(media.url)}">
            </video>
        `;
      }

      return `
            <div class="fs-media-thumb fs-media-thumb-video">
                ${
                  poster
                    ? `<img src="${escapeHtml(poster)}" alt="${escapeHtml(postTitle)}">`
                    : `<div class="fs-media-thumb-placeholder"></div>`
                }
                <span class="fs-media-thumb-play"></span>
            </div>
        `;
    }

    return `<img src="${escapeHtml(media.url)}" alt="${escapeHtml(postTitle)}">`;
  }

  function getRequestedPostId() {
    const params = new URLSearchParams(window.location.search);
    return params.get("fs_post");
  }

  function renderPost(post) {
    loadedPosts[post.id] = post;
    let mediaHtml = "";
    const mediaGallery = Array.isArray(post.media_gallery) ? post.media_gallery : [];
    const postThumbnail = post.thumbnail || "";

    if (mediaGallery.length > 0) {
      mediaHtml += renderMediaItem(mediaGallery[0], post.title, postThumbnail);
      if (mediaGallery.length > 1) {
        mediaHtml += `<span class="fs-media-thumb-count">+${mediaGallery.length - 1}</span>`;
      }
    } else if (postThumbnail) {
      mediaHtml += `<img src="${escapeHtml(postThumbnail)}" alt="${escapeHtml(post.title)}">`;
    }

    const postHtml = `
            <article class="fs-post-item" data-post-id="${post.id}">
                <div class="fs-post-thumbnail">
                    ${mediaHtml}
                </div>
          </article>
        `;

    $feedContainer.append(postHtml);
    $feedContainer
      .find(".fs-post-item:last .fs-post-thumbnail")
      .on("click", function () {
        openPostModal(post);
      });

    if (!hasOpenedPostFromUrl && String(getRequestedPostId()) === String(post.id)) {
      hasOpenedPostFromUrl = true;
      openPostModal(post);
    }
  }

  function pauseModalVideos() {
    $("#fs-post-modal video").each(function () {
      if (this.pause) {
        this.pause();
      }
    });
  }

  function destroyModalSwiper() {
    if (modalSwiperInstance && typeof modalSwiperInstance.destroy === "function") {
      modalSwiperInstance.destroy(true, true);
    }
    modalSwiperInstance = null;
  }

  function showCopyFeedback() {
    const $feedback = $("<div class=\"fs-copy-feedback\">Link copiado!</div>");
    $("body").append($feedback);
    $feedback.fadeIn(120).delay(1800).fadeOut(180, function () {
      $(this).remove();
    });
  }

  function buildPostModalUrl(postId) {
    const baseUrl = window.location.href.split("#")[0];
    const url = new URL(baseUrl, window.location.href);
    url.searchParams.set("fs_post", postId);
    return url.toString();
  }

  function copyPostLink(postId) {
    const link = buildPostModalUrl(postId);

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(link).then(function () {
        showCopyFeedback();
      });
      return;
    }

    const tempInput = document.createElement("input");
    tempInput.value = link;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    showCopyFeedback();
  }

  function closePostModal() {
    const $modal = $("#fs-post-modal");

    if (!$modal.length) {
      return;
    }

    pauseModalVideos();
    destroyModalSwiper();
    $modal.attr("hidden", true);
    $modal.removeClass("fs-comments-expanded");
    $modal.find(".fs-post-modal-media").empty();
    $modal.find(".fs-post-modal-comments").empty();
    $modal.find(".fs-post-modal-actions").empty();
    $("body").removeClass("fs-post-modal-open");
    currentPostId = null;
    hasOpenedPostFromUrl = false;
    const url = new URL(window.location.href);
    url.searchParams.delete("fs_post");
    window.history.replaceState({}, document.title, url.toString());
  }

  function setMobileModalState() {
    const $modal = $("#fs-post-modal");
    if (!$modal.length) {
      return;
    }

    const isMobile = window.matchMedia("(max-width: 768px)").matches;
    if (!isMobile) {
      $modal.removeClass("fs-mobile-content-collapsed");
      $modal.addClass("fs-comments-expanded");
      $modal.find(".fs-comments-toggle").attr("aria-expanded", "true");
      return;
    }

    $modal.removeClass("fs-comments-expanded");
    $modal.removeClass("fs-mobile-content-collapsed");
    $modal.find(".fs-comments-toggle").attr("aria-expanded", "false");
  }

  function openPostModal(post) {
    currentPostId = post.id;

    const $modal = $("#fs-post-modal");

    if (post.id) {
      $.ajax({
        url: fs_feed_data.ajax_url,
        type: "POST",
        data: {
          action: "fs_register_view",
          post_id: post.id,
        },
      });
    }
    const mediaGallery = Array.isArray(post.media_gallery) ? post.media_gallery : [];
    const postThumbnail = post.thumbnail || "";

    let mediaHtml = "";

    if (mediaGallery.length > 1) {
      mediaHtml = '<div class="swiper fs-modal-carousel">';
      mediaHtml += '<div class="swiper-wrapper">';

      mediaGallery.forEach(function (media) {
        mediaHtml += `
                <div class="swiper-slide">
                    ${renderMediaItem(media, post.title, postThumbnail, true)}
                </div>
            `;
      });

      mediaHtml += "</div>";
      mediaHtml += `
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        `;
      mediaHtml += "</div>";
    } else if (mediaGallery.length === 1) {
      mediaHtml = renderMediaItem(mediaGallery[0], post.title, postThumbnail, true);
    } else if (postThumbnail) {
      mediaHtml = `<img src="${escapeHtml(postThumbnail)}" alt="${escapeHtml(post.title)}">`;
    }

    $modal.find(".fs-post-modal-media").html(mediaHtml);
    $modal.find(".fs-post-modal-comments").html('<p class="fs-comments-loading">Carregando comentários...</p>');
    $modal.find(".fs-post-modal-actions").html(`
        <button type="button" class="fs-likes${likedPosts.has(post.id) ? " fs-liked" : ""}">
            <span class="fs-action-icon">♥</span>
            <span class="fs-count">${formatCount(post.likes || 0)}</span>
        </button>
        <button type="button" class="fs-comments-toggle" aria-expanded="false">
            <span class="fs-action-icon">💬</span>
            <span class="fs-count">${formatCount(post.comments || 0)}</span>
        </button>
    `);

    setMobileModalState();
    const url = new URL(window.location.href);
    url.searchParams.set("fs_post", post.id);
    window.history.replaceState({}, document.title, url.toString());

    $modal.removeAttr("hidden");
    $("body").addClass("fs-post-modal-open");

    loadComments(post.id);

    if (mediaGallery.length > 1 && typeof Swiper !== "undefined") {
      destroyModalSwiper();
      modalSwiperInstance = new Swiper(".fs-modal-carousel", {
        loop: true,
        pagination: {
          el: ".swiper-pagination",
          clickable: true,
        },
        navigation: {
          nextEl: ".swiper-button-next",
          prevEl: ".swiper-button-prev",
        },
        on: {
          slideChangeTransitionStart: function () {
            pauseModalVideos();
          },
          slideChange: function () {
            pauseModalVideos();
          },
        },
      });
    }
  }
  function openPostFromUrl() {
    const postId = getRequestedPostId();

    if (!postId || hasOpenedPostFromUrl || !loadedPosts[postId]) {
      return false;
    }

    hasOpenedPostFromUrl = true;
    openPostModal(loadedPosts[postId]);
    return true;
  }

  async function fetchPosts() {
    if (!$feedContainer.length || isLoading || !hasMore) {
      return;
    }

    const perPage =
      currentOffset === 0
        ? fs_feed_data.initial_posts
        : fs_feed_data.posts_per_load;

    isLoading = true;
    setLoadingVisible(true);

    try {
      const response = await fetch(
        `${fs_feed_data.rest_url}?offset=${currentOffset}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": fs_feed_data.rest_nonce,
          },
        },
      );
      const data = await response.json();

      if (data.posts && data.posts.length > 0) {
        pendingBatch = pendingBatch.concat(data.posts);
        while (pendingBatch.length >= postsPerRow) {
          flushPendingPosts();
        }

        currentOffset += data.posts.length;
        hasMore = Boolean(data.has_more);
      } else {
        hasMore = false;
      }
    } catch (error) {
      console.error("Erro ao carregar posts:", error);
    } finally {
      isLoading = false;
      setLoadingVisible(false);
      updateSentinelVisibility();

      if (!hasMore && pendingBatch.length) {
        flushPendingPosts();
      }

      if (!openPostFromUrl()) {
        checkAndLoadMore();
      }
    }
  }

  async function loadComments(postId) {
    const $list = $("#fs-post-modal .fs-post-modal-comments");

    $list.html('<p class="fs-comments-loading">Carregando comentários...</p>');

    try {
      const response = await fetch(
        `${fs_feed_data.comments_url}?post_id=${postId}`,
        {
          headers: {
            "X-WP-Nonce": fs_feed_data.rest_nonce,
          },
        },
      );
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "Erro ao carregar comentários");
      }

      if (!data.comments.length) {
        $list.html('<p class="fs-comments-empty">Nenhum comentário ainda.</p>');
        return;
      }

      const items = data.comments
        .map(function (item) {
          return `
                    <div class="fs-comment-item">
                        <strong>${item.name}</strong>
                        <p>${item.comment}</p>
                    </div>
                `;
        })
        .join("");

      $list.html(items);
    } catch (error) {
      $list.html(
        '<p class="fs-comments-error">Não foi possível carregar os comentários.</p>',
      );
      console.error(error);
    }
  }
  async function handleLike(postId) {
    const email = getUserEmail(fs_feed_data.like_prompt);

    if (!email) return;

    saveUserProfile("", email);

    const $likes = $("#fs-post-modal .fs-likes");

    $likes.prop("disabled", true);

    try {
      const response = await fetch(fs_feed_data.like_url, {
        method: "POST",
        headers: restHeaders(),
        body: JSON.stringify({
          post_id: postId,
          email: email,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error();
      }

      if (data.action === "liked") {
        likedPosts.add(postId);
      } else {
        likedPosts.delete(postId);
      }

      saveLikedPosts();

      loadedPosts[postId].likes = data.new_count;

      $likes.toggleClass("fs-liked", data.action === "liked");

      $likes.find(".fs-count").text(formatCount(data.new_count));
    } finally {
      $likes.prop("disabled", false);
    }
  }
  async function handleCommentSubmit($form) {
    const postId = currentPostId;
    const name = getUserName();
    const email = getUserEmail(fs_feed_data.comment_email_prompt);
    const comment = $form.find('textarea[name="comment"]').val().trim();

    if (!name || !email || !comment) {
      return;
    }

    saveUserProfile(name, email);

    const $submit = $form.find("button[type='submit']");
    $submit.prop("disabled", true);

    try {
      const response = await fetch(fs_feed_data.comment_url, {
        method: "POST",
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
        throw new Error(data.message || "Erro ao comentar");
      }

      $form.find('textarea[name="comment"]').val("");

      loadedPosts[postId].comments = data.new_count;
      await loadComments(postId);
    } catch (error) {
      console.error("Erro ao comentar:", error);
      window.alert("Não foi possível enviar o comentário. Tente novamente.");
    } finally {
      $submit.prop("disabled", false);
    }
  }


  $(document).on("click", "#fs-post-modal .fs-likes", function () {
    handleLike(currentPostId);
  });
  $(document).on("click", "#fs-post-modal .fs-comments-toggle", function () {
    const $modal = $("#fs-post-modal");
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (!isMobile) {
      return;
    }

    const willExpand = !$modal.hasClass("fs-comments-expanded");
    $modal.toggleClass("fs-comments-expanded", willExpand);
    $modal.removeClass("fs-mobile-content-collapsed");
    if (willExpand) {
      loadComments(currentPostId);
    }
    $(this).attr("aria-expanded", willExpand ? "true" : "false");
  });

  $(document).on("click", "#fs-post-modal .fs-post-modal-footer button[type='submit']", function () {
    const $modal = $("#fs-post-modal");
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (!isMobile) {
      return;
    }

    $modal.addClass("fs-comments-expanded").removeClass("fs-mobile-content-collapsed");
    $modal.find(".fs-comments-toggle").attr("aria-expanded", "true");
  });

  $(document).on("focus", "#fs-post-modal textarea", function () {
    const $modal = $("#fs-post-modal");
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (!isMobile) {
      return;
    }

    $modal.addClass("fs-comments-expanded").removeClass("fs-mobile-content-collapsed");
    $modal.find(".fs-comments-toggle").attr("aria-expanded", "true");
  });

  $(document).on("click", "#fs-post-modal .fs-post-modal-media", function (event) {
    const $modal = $("#fs-post-modal");
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (!isMobile || $(event.target).closest(".fs-likes, .fs-comments-toggle, .fs-comment-form, textarea").length) {
      return;
    }

    $modal.toggleClass("fs-mobile-content-collapsed");
    $modal.toggleClass("fs-comments-expanded", false);
    $modal.find(".fs-comments-toggle").attr("aria-expanded", "false");
  });
  $(document).on(
    "submit",
    "#fs-post-modal .fs-comment-form",
    function (e) {
      e.preventDefault();

      handleCommentSubmit($(this));
    },
  );
  if ($feedContainer.length && sentinelEl) {
    const observer = new IntersectionObserver(
      function (entries) {
        if (entries[0].isIntersecting && !isLoading && hasMore) {
          fetchPosts();
        }
      },
      {
        root: null,
        rootMargin: "200px 0px",
        threshold: 0,
      },
    );

    observer.observe(sentinelEl);
    updateSentinelVisibility();
    fetchPosts();

    $(window).on("scroll.fsFeed resize.fsFeed", checkAndLoadMore);
  }

  initSse();
  $(document).on("click", "#fs-post-modal .fs-post-modal-copy-link", function (e) {
    e.preventDefault();
    copyPostLink(currentPostId);
  });
  $(document).on(
    "click",
    "#fs-post-modal .fs-post-modal-overlay, #fs-post-modal .fs-post-modal-close",
    function (e) {
      e.preventDefault();
      closePostModal();
    },
  );
  $(document).on("keydown", function (event) {
    if (event.key === "Escape") {
      closePostModal();
    }
  });
});
