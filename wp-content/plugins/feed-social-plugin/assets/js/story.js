jQuery(document).ready(function ($) {
  let currentStories = [];
  const STORY_DURATION = 30000; // 30 segundos
  let storyTimeout;
  let progressInterval;
  let currentStoryId = null;
  let isPaused = false;
  let timeRemaining = STORY_DURATION;
  let startTime;
  let videoStory = false;
  const modal = $("#fs-story-modal");
  const modalContent = modal.find(".fs-story-modal-content");
  const storyActions = modal.find(".fs-story-actions");
  const closeBtn = modal.find(".fs-story-close");
  const prevBtn = modal.find(".fs-story-prev");
  const nextBtn = modal.find(".fs-story-next");
  const progressBarContainer = modal.find(".fs-story-progress-bar-container");
  const burstContainer = $('<div class="fs-story-like-burst"></div>');
  modal.append(burstContainer);
  const likedStories = new Set(
    JSON.parse(localStorage.getItem("fs_liked_posts") || "[]"),
  );
    // Inicializa os carrosséis
if (typeof Swiper !== "undefined") {

    // Destaques
    new Swiper(".fs-highlight-carousel", {
        slidesPerView: "auto",
        spaceBetween: 15,

        navigation: {
            nextEl: ".fs-highlight-carousel .swiper-button-next",
            prevEl: ".fs-highlight-carousel .swiper-button-prev",
        },

        breakpoints: {
            320: {
                slidesPerView: 4
            },
            480: {
                slidesPerView: 6
            },
            768: {
                slidesPerView: 10
            },
            1024: {
                slidesPerView: 8
            }
        }
    });

}
  function saveLikedStories() {
    localStorage.setItem("fs_liked_posts", JSON.stringify([...likedStories]));
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email || "");
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

  function getUserEmail() {
    const profile = getStoredUserProfile();
    return profile.email && isValidEmail(profile.email) ? profile.email : null;
  }

  function saveUserProfile(name, email) {
    const normalizedName = (name || "").trim();
    const normalizedEmail = (email || "").trim();

    if (!normalizedEmail) {
      return null;
    }

    sessionStorage.setItem("fs_user_name", normalizedName);
    localStorage.setItem("fs_user_name", normalizedName);
    sessionStorage.setItem("fs_user_email", normalizedEmail);
    localStorage.setItem("fs_user_email", normalizedEmail);

    if (fs_story_ajax && fs_story_ajax.ajax_url) {
      return $.post(fs_story_ajax.ajax_url, {
        action: "fs_save_user_profile",
        name: normalizedName,
        email: normalizedEmail,
      });
    }

    return null;
  }

  function formatCount(value) {
    const count = Number(value) || 0;
    if (count >= 1000) {
      return `${(count / 1000).toFixed(count % 1000 === 0 ? 0 : 1)}k`;
    }
    return String(count);
  }

  function renderStoryActions(storyId, likesCount) {
    const isLiked = likedStories.has(String(storyId));
    storyActions.html(`
      <button type="button" class="fs-likes${isLiked ? " fs-liked" : ""}" data-story-id="${storyId}">
        <span aria-hidden="true">♥</span>
        <span class="fs-count">${formatCount(likesCount)}</span>
      </button>
    `);
  }

  function createLikeBurst(button) {
    if (!button || !button.length) {
      return;
    }

    const rect = button[0].getBoundingClientRect();
    const modalRect = modal[0].getBoundingClientRect();
    const startX = rect.left - modalRect.left + rect.width / 2;
    const startY = rect.top - modalRect.top + rect.height / 2;

    for (let i = 0; i < 10; i++) {
      const heart = $('<span class="fs-heart-pop">♥</span>');
      const offsetX = (Math.random() - 0.5) * 80;
      const offsetY = -(50 + Math.random() * 80);
      const rotation = (Math.random() - 0.5) * 30;
      const size = 16 + Math.random() * 12;

      heart.css({
        left: `${startX}px`,
        top: `${startY}px`,
        fontSize: `${size}px`,
        '--x': `${offsetX}px`,
        '--y': `${offsetY}px`,
        '--rotation': `${rotation}deg`,
      });

      burstContainer.append(heart);
      setTimeout(() => heart.remove(), 1100);
    }
  }

  async function handleStoryLike(storyId) {
    const profile = getStoredUserProfile();
    let email = profile.email;
    let name = profile.name;

    if (!email || !isValidEmail(email)) {
      name = window.prompt("Informe seu nome:", profile.name || "") || "";
      email = window.prompt("Informe seu e-mail institucional:", profile.email || "") || "";

      if (!name || !email || !isValidEmail(email)) {
        window.alert("Informe seu nome e um e-mail válido para curtir.");
        return;
      }

      saveUserProfile(name, email);
    }

    const $button = storyActions.find(`.fs-likes[data-story-id="${storyId}"]`);
    if (!$button.length) {
      return;
    }

    $button.prop("disabled", true);

    try {
      const response = await $.ajax({
        url: fs_story_ajax.like_url,
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": fs_story_ajax.rest_nonce || "",
        },
        data: JSON.stringify({
          post_id: storyId,
          email: email,
        }),
      });

      if (response && response.action === "liked") {
        likedStories.add(String(storyId));
        createLikeBurst($button);
      } else {
        likedStories.delete(String(storyId));
      }

      saveLikedStories();
      $button.toggleClass("fs-liked", response && response.action === "liked");
      $button.find(".fs-count").text(formatCount(response && response.new_count ? response.new_count : 0));
    } finally {
      $button.prop("disabled", false);
    }
  }

  function createProgressBars() {
    progressBarContainer.empty();
    if (currentStories.length > 1) {
      currentStories.forEach(() => {
        progressBarContainer.append(
          '<div class="fs-story-progress-segment"><div class="fs-story-progress-bar"></div></div>',
        );
      });
    }
  }

  function resetTimer() {
    clearTimeout(storyTimeout);
    clearInterval(progressInterval);

    const currentIndex = getStoryIndex(currentStoryId);
    // Reset current and subsequent progress bars
    progressBarContainer
      .find(".fs-story-progress-bar")
      .slice(currentIndex)
      .css("width", "0%");
    // Fill previous progress bars
    progressBarContainer
      .find(".fs-story-progress-bar")
      .slice(0, currentIndex)
      .css("width", "100%");

    timeRemaining = STORY_DURATION;
    isPaused = false;
    videoStory = false;
  }

  function startTimer() {
    resetTimer();
    startTime = Date.now();

    storyTimeout = setTimeout(showNextStory, STORY_DURATION);

    const currentProgressBar = progressBarContainer
      .find(".fs-story-progress-bar")
      .eq(getStoryIndex(currentStoryId));

    progressInterval = setInterval(() => {
      if (!isPaused) {
        const elapsedTime = Date.now() - startTime;
        const progress = (elapsedTime / STORY_DURATION) * 100;
        if (currentProgressBar) currentProgressBar.css("width", progress + "%");
      }
    }, 100);
  }

  function pauseTimer() {
    if (!isPaused) {
      clearTimeout(storyTimeout);
      clearInterval(progressInterval);
      isPaused = true;
      timeRemaining -= Date.now() - startTime;
    }
  }

  function resumeTimer() {
    if (isPaused) {
      isPaused = false;
      startTime = Date.now();
      storyTimeout = setTimeout(showNextStory, timeRemaining);

      const currentProgressBar = progressBarContainer
        .find(".fs-story-progress-bar")
        .eq(getStoryIndex(currentStoryId));

      progressInterval = setInterval(() => {
        if (!isPaused) {
          const elapsedTime =
            STORY_DURATION - timeRemaining + (Date.now() - startTime);
          const progress = (elapsedTime / STORY_DURATION) * 100;
          if (currentProgressBar)
            currentProgressBar.css("width", progress + "%");
        }
      }, 100);
    }
  }

  function loadStory(storyId) {
    if (!storyId) return;
    currentStoryId = storyId;

    modalContent.html('<p style="color: #fff;">Carregando...</p>');
    if (!modal.hasClass("fs-story-modal-show")) createProgressBars();
    modal.addClass("fs-story-modal-show");

    $.post(
      fs_story_ajax.ajax_url,
      {
        action: "fs_get_story_content",
        nonce: fs_story_ajax.nonce,
        story_id: storyId,
      },
      function (response) {
        if (response.success) {
          modalContent.html(response.data.content);
          renderStoryActions(storyId, response.data.likes || 0);

          if (response.data.has_video) {
            videoStory = true;
            const video = modalContent.find("video");
            const currentProgressBar = progressBarContainer
              .find(".fs-story-progress-bar")
              .eq(getStoryIndex(currentStoryId));

            video.on("timeupdate", function () {
              const progress = (this.currentTime / this.duration) * 100;
              if (currentProgressBar)
                currentProgressBar.css("width", progress + "%");
            });

            video.on("ended", function () {
              showNextStory();
            });

            // Pausa o timer geral, pois o vídeo controla a progressão
            clearTimeout(storyTimeout);
            clearInterval(progressInterval);

            video.trigger("play");
          } else {
            startTimer();
          }
        } else {
          modalContent.html("<p>Erro ao carregar o story.</p>");
        }
      },
    );
  }

  function getStoryIndex(storyId) {
    if (!storyId || !currentStories.length) {
      return -1;
    }

    return currentStories.map(String).indexOf(String(storyId));
  }

  function showNextStory() {
    const currentIndex = getStoryIndex(currentStoryId);
    if (currentIndex < currentStories.length - 1) {
      loadStory(currentStories[currentIndex + 1]);
    } else {
      closeModal();
    }
  }

  function showPrevStory() {
    const currentIndex = getStoryIndex(currentStoryId);
    if (currentIndex > 0) {
      loadStory(currentStories[currentIndex - 1]);
    }
  }

  // Abrir o modal ao clicar em um story
  $(".fs-story-item").on("click", function (e) {
    e.preventDefault();

    currentStories = fs_story_data.story_ids;

    loadStory($(this).data("story-id"));
  });

  // Fechar o modal
  function closeModal() {
    resetTimer();
    modal.removeClass("fs-story-modal-show");
    const video = modalContent.find("video");
    video.trigger("pause");
    video.removeAttr("src");
  }

  closeBtn.on("click", closeModal);

  // Fechar com a tecla ESC
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" && modal.hasClass("fs-story-modal-show")) {
      closeModal();
    }
  });

  // Navegação manual
  nextBtn.on("click", function (e) {
    e.stopPropagation(); // Impede que o clique feche o modal
    showNextStory();
  });
  prevBtn.on("click", function (e) {
    e.stopPropagation(); // Impede que o clique feche o modal
    showPrevStory();
  });

  // Pausar/retomar timer com a visibilidade da aba
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
      pauseTimer();
    } else {
      resumeTimer();
    }
  });
  $(document).on("click", ".fs-highlight-item", function (e) {
    e.preventDefault();

    currentStories = $(this).data("story-group");

    if (typeof currentStories === "string") {
      currentStories = JSON.parse(currentStories);
    }

    loadStory(currentStories[0]);
  });
  $(document)
    .off("click", ".fs-story-item")
    .on("click", ".fs-story-item", function (e) {
      e.preventDefault();

      currentStories = fs_story_data.story_ids;

      loadStory($(this).data("story-id"));
    });
  $(document)
    .off("click", ".fs-highlight-item")
    .on("click", ".fs-highlight-item", function (e) {
      e.preventDefault();

      currentStories = $(this).data("story-group");

      if (typeof currentStories === "string") {
        currentStories = JSON.parse(currentStories);
      }

      loadStory(currentStories[0]);
    });

  $(document).on("click", "#fs-story-modal .fs-likes", function (e) {
    e.preventDefault();
    const storyId = $(this).data("story-id");
    if (storyId) {
      handleStoryLike(storyId);
    }
  });
});
