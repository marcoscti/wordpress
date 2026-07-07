jQuery(document).ready(function($) {
    const STORY_DURATION = 30000; // 30 segundos
    let storyTimeout;
    let progressInterval;
    let currentStoryId = null;
    let isPaused = false;
    let timeRemaining = STORY_DURATION;
    let startTime;
    let videoStory = false;

    // Inicializa o carrossel de stories
    if (typeof Swiper !== 'undefined') {
        new Swiper('.fs-story-carousel', {
            slidesPerView: 'auto',
            spaceBetween: 15,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                320: { slidesPerView: 2, spaceBetween: 10 },
                480: { slidesPerView: 3, spaceBetween: 15 },
                640: { slidesPerView: 4, spaceBetween: 20 },
                1024: { slidesPerView: 5, spaceBetween: 20 }
            }
        });
    }

    const modal = $('#fs-story-modal');
    const modalContent = modal.find('.fs-story-modal-content');
    const closeBtn = modal.find('.fs-story-close');
    const prevBtn = modal.find('.fs-story-prev');
    const nextBtn = modal.find('.fs-story-next');
    const progressBarContainer = modal.find('.fs-story-progress-bar-container');

    function createProgressBars() {
        progressBarContainer.empty();
        if (fs_story_data.story_ids.length > 1) {
            fs_story_data.story_ids.forEach(() => {
                progressBarContainer.append('<div class="fs-story-progress-segment"><div class="fs-story-progress-bar"></div></div>');
            });
        }
    }

    function resetTimer() {
        clearTimeout(storyTimeout);
        clearInterval(progressInterval);
        
        const currentIndex = getStoryIndex(currentStoryId);
        // Reset current and subsequent progress bars
        progressBarContainer.find('.fs-story-progress-bar').slice(currentIndex).css('width', '0%');
        // Fill previous progress bars
        progressBarContainer.find('.fs-story-progress-bar').slice(0, currentIndex).css('width', '100%');

        timeRemaining = STORY_DURATION;
        isPaused = false;
        videoStory = false;
    }

    function startTimer() {
        resetTimer();
        startTime = Date.now();

        storyTimeout = setTimeout(showNextStory, STORY_DURATION);

        const currentProgressBar = progressBarContainer.find('.fs-story-progress-bar').eq(getStoryIndex(currentStoryId));

        progressInterval = setInterval(() => {
            if (!isPaused) {
                const elapsedTime = Date.now() - startTime;
                const progress = (elapsedTime / STORY_DURATION) * 100;
                if (currentProgressBar) currentProgressBar.css('width', progress + '%');
            }
        }, 100);
    }

    function pauseTimer() {
        if (!isPaused) {
            clearTimeout(storyTimeout);
            clearInterval(progressInterval);
            isPaused = true;
            timeRemaining -= (Date.now() - startTime);
        }
    }

    function resumeTimer() {
        if (isPaused) {
            isPaused = false;
            startTime = Date.now();
            storyTimeout = setTimeout(showNextStory, timeRemaining);
            
            const currentProgressBar = progressBarContainer.find('.fs-story-progress-bar').eq(getStoryIndex(currentStoryId));

            progressInterval = setInterval(() => {
                if (!isPaused) {
                    const elapsedTime = (STORY_DURATION - timeRemaining) + (Date.now() - startTime);
                    const progress = (elapsedTime / STORY_DURATION) * 100;
                    if (currentProgressBar) currentProgressBar.css('width', progress + '%');
                }
            }, 100);
        }
    }

    function loadStory(storyId) {
        if (!storyId) return;
        currentStoryId = storyId;

        modalContent.html('<p style="color: #fff;">Carregando...</p>');
        if (!modal.hasClass('fs-story-modal-show')) createProgressBars();
        modal.addClass('fs-story-modal-show');

        $.post(fs_story_ajax.ajax_url, {
            action: 'fs_get_story_content',
            nonce: fs_story_ajax.nonce,
            story_id: storyId
        }, function(response) {
            if (response.success) {
                modalContent.html(response.data.content);

                if (response.data.has_video) {
                    videoStory = true;
                    const video = modalContent.find('video');
                    const currentProgressBar = progressBarContainer.find('.fs-story-progress-bar').eq(getStoryIndex(currentStoryId));

                    video.on('timeupdate', function() {
                        const progress = (this.currentTime / this.duration) * 100;
                        if (currentProgressBar) currentProgressBar.css('width', progress + '%');
                    });

                    video.on('ended', function() {
                        showNextStory();
                    });

                    // Pausa o timer geral, pois o vídeo controla a progressão
                    clearTimeout(storyTimeout);
                    clearInterval(progressInterval);

                    video.trigger('play');
                } else {
                    startTimer();
                }
            } else {
                modalContent.html('<p>Erro ao carregar o story.</p>');
            }
        });
    }

    function getStoryIndex(storyId) {
        if (!storyId || !fs_story_data || !fs_story_data.story_ids) {
            return -1;
        }
        // Ensure storyId is a string for comparison, as it comes from data attribute.
        return fs_story_data.story_ids.map(String).indexOf(String(storyId));
    }

    function showNextStory() {
        const currentIndex = getStoryIndex(currentStoryId);
        if (currentIndex > -1 && currentIndex < fs_story_data.story_ids.length - 1) {
            loadStory(fs_story_data.story_ids[currentIndex + 1]);
        } else {
            closeModal(); // Fecha se for o último
        }
    }

    function showPrevStory() {
        const currentIndex = getStoryIndex(currentStoryId);
        if (currentIndex > 0) {
            loadStory(fs_story_data.story_ids[currentIndex - 1]);
        }
    }

    // Abrir o modal ao clicar em um story
    $('.fs-story-item').on('click', function(e) {
        e.preventDefault();
        loadStory($(this).data('story-id'));
    });

    // Fechar o modal
    function closeModal() {
        resetTimer();
        modal.removeClass('fs-story-modal-show');
    }

    closeBtn.on('click', closeModal);

    // Fechar com a tecla ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && modal.hasClass('fs-story-modal-show')) {
            closeModal();
        }
    });

    // Navegação manual
    nextBtn.on('click', function(e) {
        e.stopPropagation(); // Impede que o clique feche o modal
        showNextStory();
    });
    prevBtn.on('click', function(e) {
        e.stopPropagation(); // Impede que o clique feche o modal
        showPrevStory();
    });

    // Pausar/retomar timer com a visibilidade da aba
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            pauseTimer();
        } else {
            resumeTimer();
        }
    });
});