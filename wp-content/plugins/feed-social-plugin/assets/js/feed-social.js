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
  let pendingProfileAction = null;
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

  function getUserEmail() {
    const profile = getStoredUserProfile();
    if (profile.email && isValidEmail(profile.email)) {
      return profile.email;
    }

    return null;
  }

  function getStoredUserProfile() {
    const sessionName = sessionStorage.getItem("fs_user_name") || "";
    const sessionEmail = sessionStorage.getItem("fs_user_email") || "";
    const localName = localStorage.getItem("fs_user_name") || "";
    const localEmail = localStorage.getItem("fs_user_email") || "";

    return {
      name: sessionName || localName || "",
      email: sessionEmail || localEmail || "",
    };
  }

  function closeUserProfileModal() {
    const $modal = $("#fs-user-profile-overlay");
    if ($modal.length) {
      $modal.attr("hidden", "hidden");
    }
  }

  function saveUserProfile(name, email, onSuccess) {
    const normalizedName = (name || "").trim();
    const normalizedEmail = (email || "").trim();

    if (!normalizedEmail) {
      return null;
    }

    if (normalizedName) {
      sessionStorage.setItem("fs_user_name", normalizedName);
      localStorage.setItem("fs_user_name", normalizedName);
    } else {
      sessionStorage.removeItem("fs_user_name");
      localStorage.removeItem("fs_user_name");
    }

    sessionStorage.setItem("fs_user_email", normalizedEmail);
    localStorage.setItem("fs_user_email", normalizedEmail);

    return $.ajax({
      url: fs_feed_data.ajax_url,
      type: "POST",
      data: {
        action: "fs_save_user_profile",
        name: normalizedName,
        email: normalizedEmail,
      },
    }).done(function (response) {
      const serverProfile =
        response && response.success && response.data ? response.data : null;

      if (serverProfile && serverProfile.email) {
        const syncedName = serverProfile.name || normalizedName;
        const syncedEmail = serverProfile.email || normalizedEmail;

        sessionStorage.setItem("fs_user_name", syncedName);
        localStorage.setItem("fs_user_name", syncedName);
        sessionStorage.setItem("fs_user_email", syncedEmail);
        localStorage.setItem("fs_user_email", syncedEmail);
      }

      if (typeof onSuccess === "function") {
        onSuccess(
          serverProfile || { name: normalizedName, email: normalizedEmail },
        );
      }
    });
  }

  function displayUserProfile(profile, options) {
    const resolvedOptions = options || {};
    const $overlay = $("#fs-user-profile-overlay");
    if (!$overlay.length) {
      $("body").append(`
        <div id="fs-user-profile-overlay" class="fs-user-profile-overlay" hidden>
          <div class="fs-user-profile-card">
            <button type="button" class="fs-user-profile-close" aria-label="Fechar">×</button>
            <div class="fs-user-profile-summary"></div>
            <form class="fs-user-profile-form">
              <div class="fs-user-profile-fields">
                <input class="fs-user-profile-field" type="text" name="fs_profile_name" placeholder="Seu nome" autocomplete="name">
                <input class="fs-user-profile-field" type="email" name="fs_profile_email" placeholder="Seu e-mail" autocomplete="email" required>
              </div>
              <button type="submit" class="fs-user-profile-submit">Salvar dados</button>
            </form>
          </div>
        </div>
      `);
    }

    const $modal = $("#fs-user-profile-overlay");
    const $summary = $modal.find(".fs-user-profile-summary");
    const $nameInput = $modal.find('input[name="fs_profile_name"]');
    const $emailInput = $modal.find('input[name="fs_profile_email"]');
    const resolvedProfile = profile || getStoredUserProfile();
    const hasEmail = Boolean(
      resolvedProfile.email && isValidEmail(resolvedProfile.email),
    );
    const displayName =
      resolvedProfile.name || resolvedProfile.email || "Seu perfil";

    $summary.html(
      hasEmail
        ? `Você está interagindo como <strong>${escapeHtml(displayName)}</strong>.`
        : "Informe seu nome e e-mail para comentar ou curtir.",
    );
    $nameInput.val(resolvedProfile.name || "");
    $emailInput.val(resolvedProfile.email || "");
    $modal.removeAttr("hidden");

    $modal
      .off("submit", ".fs-user-profile-form")
      .on("submit", ".fs-user-profile-form", function (event) {
        event.preventDefault();

        const name = $(this).find('input[name="fs_profile_name"]').val().trim();
        const email = $(this)
          .find('input[name="fs_profile_email"]')
          .val()
          .trim();

        if (!name) {
          window.alert("Informe seu nome.");
          return;
        }

        if (!email || !isValidEmail(email)) {
          window.alert("Informe um e-mail válido.");
          return;
        }

        const $form = $(this);
        const $submitBtn = $form.find(".fs-user-profile-submit");
        $submitBtn.prop("disabled", true).text("Salvando...");
        $form.find("input").prop("disabled", true);

        const savePromise = saveUserProfile(name, email, function (profile) {
          $submitBtn.text("Carregando...");

          const performReload = function () {
            $("body").addClass("fs-reloading");
            setTimeout(function () {
              window.location.reload();
            }, 600);
          };

          if (pendingProfileAction) {
            const action = pendingProfileAction;
            pendingProfileAction = null;
            const actionResult = action();

            if (actionResult && typeof actionResult.then === "function") {
              actionResult.then(performReload).catch(performReload);
            } else {
              setTimeout(performReload, 1000);
            }
          } else {
            performReload();
          }
        });

        if (savePromise && typeof savePromise.fail === "function") {
          savePromise.fail(function () {
            $submitBtn.prop("disabled", false).text("Salvar dados");
            $form.find("input").prop("disabled", false);
            window.alert(
              "Ocorreu um erro ao salvar o perfil. Tente novamente.",
            );
          });
        }
      });

    $modal
      .off("click", ".fs-user-profile-close")
      .on("click", ".fs-user-profile-close", function (event) {
        event.preventDefault();
        event.stopPropagation();
        $modal.attr("hidden", "hidden");
        if (resolvedOptions.onClose) {
          resolvedOptions.onClose();
        }
      });

    $modal.off("click").on("click", function (event) {
      if (event.target !== this) {
        return;
      }

      $modal.attr("hidden", "hidden");
      if (resolvedOptions.onClose) {
        resolvedOptions.onClose();
      }
    });
  }

  function getUserName() {
    const profile = getStoredUserProfile();
    return profile.name || null;
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

  function getLegendText(post) {
    if (typeof post?.content === "string" && post.content.trim()) {
      return post.content.trim();
    }

    if (typeof post?.content === "string" && post.content.trim()) {
      return String(post.content)
        .replace(/<[^>]+>/g, " ")
        .replace(/\s+/g, " ")
        .trim();
    }

    return "";
  }

  function renderLegend(post) {
    const legendText = getLegendText(post);

    const $legendContainer = $("#fs-post-modal .fs-post-modal-legend");

    if (!legendText) {
      $legendContainer.empty();
      return;
    }

    $legendContainer.html(`
      <div class="fs-post-legend">
        <div class="fs-post-legend-content">${legendText}</div>
      </div>
    `);
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
    const mediaGallery = Array.isArray(post.media_gallery)
      ? post.media_gallery
      : [];
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

    if (
      !hasOpenedPostFromUrl &&
      String(getRequestedPostId()) === String(post.id)
    ) {
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
    if (
      modalSwiperInstance &&
      typeof modalSwiperInstance.destroy === "function"
    ) {
      modalSwiperInstance.destroy(true, true);
    }
    modalSwiperInstance = null;
  }

  function showCopyFeedback() {
    const $feedback = $('<div class="fs-copy-feedback">Link copiado!</div>');
    $("body").append($feedback);
    $feedback
      .fadeIn(120)
      .delay(1800)
      .fadeOut(180, function () {
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
    $modal.find(".fs-post-modal-legend").empty();
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
    const mediaGallery = Array.isArray(post.media_gallery)
      ? post.media_gallery
      : [];
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
      mediaHtml = renderMediaItem(
        mediaGallery[0],
        post.title,
        postThumbnail,
        true,
      );
    } else if (postThumbnail) {
      mediaHtml = `<img src="${escapeHtml(postThumbnail)}" alt="${escapeHtml(post.title)}">`;
    }

    $modal.find(".fs-post-modal-media").html(mediaHtml);
    renderLegend(post);
    $modal
      .find(".fs-post-modal-comments")
      .html(
        '<p class="fs-comments-loading"><svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"   width="40px" height="40px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve">  <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946    s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634    c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/>  <path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0    C22.32,8.481,24.301,9.057,26.013,10.047z">    <animateTransform attributeType="xml"      attributeName="transform"      type="rotate"      from="0 20 20"      to="360 20 20"      dur="0.5s"      repeatCount="indefinite"/>    </path>  </svg></p>',
      );
    $modal.find(".fs-post-modal-actions").html(`
        <button type="button" class="fs-likes${likedPosts.has(post.id) ? " fs-liked" : ""}">
            <span class="fs-action-icon"><svg width="15" height="19" viewBox="0 0 24 21" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.0951 2.67899C20.5631 2.1467 19.9314 1.72445 19.2361 1.43636C18.5408 1.14828 17.7956 1 17.043 1C16.2904 1 15.5452 1.14828 14.8499 1.43636C14.1547 1.72445 13.523 2.1467 12.9909 2.67899L11.8868 3.78315L10.7826 2.67899C9.70792 1.60431 8.25034 1.00056 6.73051 1.00056C5.21069 1.00056 3.75311 1.60431 2.67843 2.67899C1.60375 3.75366 1 5.21124 1 6.73107C1 8.25089 1.60375 9.70847 2.67843 10.7832L11.8868 19.9915L21.0951 10.7832C21.6274 10.2511 22.0496 9.61942 22.3377 8.92415C22.6258 8.22888 22.7741 7.48366 22.7741 6.73107C22.7741 5.97848 22.6258 5.23326 22.3377 4.53799C22.0496 3.84272 21.6274 3.21102 21.0951 2.67899Z" stroke="#e0245e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg></span>
            <span class="fs-count">${formatCount(post.likes || 0)}</span>
        </button>
        <button type="button" class="fs-comments-toggle" aria-expanded="false">
            <span class="fs-action-icon"><svg width="15" height="15" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.5498 13.3C19.5498 13.8525 19.3303 14.3824 18.9396 14.7731C18.5489 15.1638 18.019 15.3833 17.4665 15.3833H4.96647L0.799805 19.55V2.88334C0.799805 2.3308 1.0193 1.8009 1.41 1.4102C1.8007 1.0195 2.3306 0.800003 2.88314 0.800003H17.4665C18.019 0.800003 18.5489 1.0195 18.9396 1.4102C19.3303 1.8009 19.5498 2.3308 19.5498 2.88334V13.3Z" stroke="#C8D400" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
</svg></span>
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
      // If post not loaded yet, try to request it directly from REST API (single post endpoint)
      if (!postId || hasOpenedPostFromUrl) return false;

      if (fs_feed_data.post_url) {
        // Attempt to fetch single post by ID and open when available
        fetch(fs_feed_data.post_url + "/" + encodeURIComponent(postId), {
          headers: {
            "X-WP-Nonce": fs_feed_data.rest_nonce,
          },
        })
          .then(function (resp) {
            return resp.json();
          })
          .then(function (data) {
            if (data && data.id) {
              // Normalize to expected post object and store
              const post = {
                id: data.id,
                title: data.title || "",
                content: data.content || "",
                legend: data.legend || data.content || "",
                thumbnail: data.thumbnail || "",
                media_gallery: Array.isArray(data.media_gallery)
                  ? data.media_gallery
                  : [],
                likes: data.likes || 0,
                comments: data.comments || 0,
                views: data.views || 0,
              };
              loadedPosts[post.id] = post;
              hasOpenedPostFromUrl = true;
              openPostModal(post);
            }
          })
          .catch(function (err) {
            console.error("Erro ao buscar post por ID:", err);
          });
      }

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

    $list.html(
      '<p class="fs-comments-loading"><svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"   width="40px" height="40px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve">  <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946    s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634    c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/>  <path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0    C22.32,8.481,24.301,9.057,26.013,10.047z">    <animateTransform attributeType="xml"      attributeName="transform"      type="rotate"      from="0 20 20"      to="360 20 20"      dur="0.5s"      repeatCount="indefinite"/>    </path>  </svg></p>',
    );

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

      const currentUserEmail = (
        getStoredUserProfile().email || ""
      ).toLowerCase();

      const items = data.comments
        .map(function (item) {
          const canEdit = Boolean(
            item.email &&
            currentUserEmail &&
            String(item.email).toLowerCase() === currentUserEmail,
          );
          return `
                    <div class="fs-comment-item" data-comment-id="${item.id}">
                        <div class="fs-comment-header">
                            <strong>${escapeHtml(item.name)}</strong>
                            ${canEdit ? '<button type="button" class="fs-comment-edit"><span class="dashicons dashicons-edit"></span> Editar</button>' : ""}
                        </div>
                        <p class="fs-comment-text">${escapeHtml(item.comment)}</p>
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
  async function updateComment(commentId, postId, commentText) {
    const $commentItem = $(
      "#fs-post-modal .fs-comment-item[data-comment-id='" + commentId + "']",
    );
    if (!$commentItem.length) {
      return;
    }

    try {
      const response = await fetch(`${fs_feed_data.comment_url}/${commentId}`, {
        method: "PUT",
        headers: restHeaders(),
        body: JSON.stringify({
          post_id: postId,
          email: getStoredUserProfile().email || "",
          comment: commentText,
        }),
      });
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "Erro ao editar comentário");
      }

      await loadComments(postId);
    } catch (error) {
      console.error(error);
      window.alert("Não foi possível editar o comentário.");
    }
  }

  async function handleLike(postId) {
    const email = getUserEmail();

    if (!email) {
      pendingProfileAction = () => handleLike(postId);
      displayUserProfile(getStoredUserProfile(), { onClose: () => {} });
      return;
    }

    saveUserProfile(getUserName() || "", email);

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
    const email = getUserEmail();
    const comment = $form.find('textarea[name="comment"]').val().trim();

    if (!name || !email || !comment) {
      pendingProfileAction = () => handleCommentSubmit($form);
      displayUserProfile(getStoredUserProfile(), { onClose: () => {} });
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
      const $emojiEditor = $form.find(".emoji-editor");
      if ($emojiEditor.length) {
        $emojiEditor.html("");
        $emojiEditor.addClass("emoji-editor-empty");
      }

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

  $(document).on(
    "click",
    "#fs-post-modal .fs-post-modal-footer button[type='submit']",
    function () {
      const $modal = $("#fs-post-modal");
      const isMobile = window.matchMedia("(max-width: 768px)").matches;

      if (!isMobile) {
        return;
      }

      $modal
        .addClass("fs-comments-expanded")
        .removeClass("fs-mobile-content-collapsed");
      $modal.find(".fs-comments-toggle").attr("aria-expanded", "true");
    },
  );

  $(document).on("focus", "#fs-post-modal textarea", function () {
    const $modal = $("#fs-post-modal");
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (!isMobile) {
      return;
    }

    $modal
      .addClass("fs-comments-expanded")
      .removeClass("fs-mobile-content-collapsed");
    $modal.find(".fs-comments-toggle").attr("aria-expanded", "true");
  });

  $(document).on(
    "click",
    "#fs-post-modal .fs-post-modal-media img",
    function (event) {
      const $modal = $("#fs-post-modal");
      const isMobile = window.matchMedia("(max-width: 768px)").matches;

      if (
        !isMobile ||
        $(event.target).closest(
          ".fs-likes, .fs-comments-toggle, .fs-comment-form, textarea",
        ).length
      ) {
        return;
      }

      $modal.toggleClass("fs-mobile-content-collapsed");
      $modal.toggleClass("fs-comments-expanded", false);
      $modal.find(".fs-comments-toggle").attr("aria-expanded", "false");
    },
  );
  $(document).on(
    "keydown",
    "#fs-post-modal .fs-comment-form textarea",
    function (event) {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        $(this).closest("form").trigger("submit");
      }
    },
  );

  $(document).on("submit", "#fs-post-modal .fs-comment-form", function (e) {
    e.preventDefault();

    handleCommentSubmit($(this));
  });

  $(document).on("click", "#fs-post-modal .fs-comment-edit", function () {
    const $item = $(this).closest(".fs-comment-item");
    const commentId = $item.data("comment-id");
    $(".fs-comment-form").css({ opacity: 0 });
    // Clone and replace emoji images with their alt attributes to preserve emojis
    const $clone = $item.find(".fs-comment-text").clone();
    $clone.find("img").each(function () {
      const alt = $(this).attr("alt");
      if (alt) {
        $(this).replaceWith(alt);
      }
    });
    const currentText = $clone.text().trim();

    $item.html(`
      <form class="fs-comment-edit-form" data-emojiarea data-type="css" data-global-picker="false">
        <i class="emoji emoji-smile emoji-button"><svg aria-label="Emoji" class="x1lliihq x1n2onr6 x1roi4f4" fill="#575756" height="24" role="img" viewBox="0 0 24 24" width="24"><title>Emoji</title><path d="M15.83 10.997a1.167 1.167 0 1 0 1.167 1.167 1.167 1.167 0 0 0-1.167-1.167Zm-6.5 1.167a1.167 1.167 0 1 0-1.166 1.167 1.167 1.167 0 0 0 1.166-1.167Zm5.163 3.24a3.406 3.406 0 0 1-4.982.007 1 1 0 1 0-1.557 1.256 5.397 5.397 0 0 0 8.09 0 1 1 0 0 0-1.55-1.263ZM12 .503a11.5 11.5 0 1 0 11.5 11.5A11.513 11.513 0 0 0 12 .503Zm0 21a9.5 9.5 0 1 1 9.5-9.5 9.51 9.51 0 0 1-9.5 9.5Z"></path></svg></i>
        <textarea class="fs-comment-edit-textarea" rows="3">${escapeHtml(currentText)}</textarea>
        <div class="fs-comment-edit-actions">
          <button type="submit" class="fs-comment-edit-submit">Salvar</button>
          <button type="button" class="fs-comment-edit-cancel">Cancelar</button>
        </div>
      </form>
    `);

    // O plugin jquery.emojiarea só se auto-inicializa uma vez, no document ready.
    // Como este formulário é criado dinamicamente (via $item.html acima), ele
    // nunca é "visto" por aquela inicialização automática, então o botão de
    // emoji fica sem funcionalidade. É preciso inicializar manualmente aqui.
    if (typeof $.fn.emojiarea === "function") {
      $item.find(".fs-comment-edit-form").emojiarea();
    }

    $item.find(".fs-comment-edit-form").on("submit", function (event) {
      $(".fs-comment-edit-submit").html(
        `<svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"   width="20px" height="20px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve">  <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946    s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634    c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/>  <path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0    C22.32,8.481,24.301,9.057,26.013,10.047z">    <animateTransform attributeType="xml"      attributeName="transform"      type="rotate"      from="0 20 20"      to="360 20 20"      dur="0.5s"      repeatCount="indefinite"/>    </path>  </svg>`,
      );
      event.preventDefault();
      const updatedText = $(this).find(".fs-comment-edit-textarea").val();

      if (!updatedText) {
        window.alert("O comentário não pode ficar vazio.");
        return;
      }

      updateComment(commentId, currentPostId, updatedText);
      setTimeout(() => {
        $(".fs-comment-form").css({ opacity: 1 });
      }, 2000);
    });

    $item.find(".fs-comment-edit-cancel").on("click", function (event) {
      event.preventDefault();
      loadComments(currentPostId);
      $(".fs-comment-form").css({ opacity: 1 });
    });
  });
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

  // Handle placeholder for the emoji editor
  const $commentForm = $(".fs-comment-form");
  if ($commentForm.length) {
    const $textarea = $commentForm.find('textarea[name="comment"]');
    const $editor = $commentForm.find(".emoji-editor");
    if ($textarea.length && $editor.length) {
      const placeholderText = $textarea.attr("placeholder");
      if (placeholderText) {
        $editor.attr("data-placeholder", placeholderText);
      }

      const updatePlaceholder = function ($el) {
        if ($el.text().trim() === "") {
          $el.addClass("emoji-editor-empty");
        } else {
          $el.removeClass("emoji-editor-empty");
        }
      };

      updatePlaceholder($editor);

      $editor.on("input keyup paste change focus blur", function () {
        updatePlaceholder($(this));
      });
    }
  }

  initSse();
  $(document).on(
    "click",
    "#fs-post-modal .fs-post-modal-copy-link",
    function (e) {
      e.preventDefault();
      copyPostLink(currentPostId);
    },
  );

  $(document).on(
    "click",
    "#fs-post-modal .fs-post-legend-toggle",
    function (e) {
      e.preventDefault();
      const $legend = $(this).closest(".fs-post-legend");
      const isExpanded = $legend.hasClass("is-expanded");

      $legend.toggleClass("is-expanded", !isExpanded);
      $(this).text(isExpanded ? "Leia mais" : "Leia menos");
    },
  );
  $(document).on(
    "click",
    "#fs-post-modal .fs-post-modal-overlay, #fs-post-modal .fs-post-modal-close, .fs-post-modal-container.inactive",
    function (e) {
      e.preventDefault();
      closePostModal();
    },
  );
  $(document).on("click", ".fs-post-modal-container.active", function (e) {
    e.preventDefault();
    closePostModal();
    $(".fs-story-item").click();
  });
  $(document).on("keydown", function (event) {
    if (event.key === "Escape") {
      closePostModal();
    }
  });
});
