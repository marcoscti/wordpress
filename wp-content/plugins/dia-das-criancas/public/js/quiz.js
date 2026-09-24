(() => {
  "use strict";

  const root = document.getElementById("dcdc-quiz");
  if (!root || typeof DCDC_DATA === "undefined") return;

  const start = root.querySelector(".dcdc-start");
  const questionScreen = root.querySelector(".dcdc-question-screen");
  const resultScreen = root.querySelector(".dcdc-result-screen");
  const startBtn = root.querySelector(".dcdc-start-btn");
  const nextBtn = root.querySelector(".dcdc-next-btn");
  const restartBtn = root.querySelector(".dcdc-restart-btn");
  const questionEl = root.querySelector(".dcdc-question");
  const decadeEl = root.querySelector(".dcdc-decade");
  const optionsEl = root.querySelector(".dcdc-options");
  const progressLabel = root.querySelector(".dcdc-progress-label");
  const progressCounter = root.querySelector(".dcdc-progress-counter");
  const progressBar = root.querySelector(".dcdc-progress-bar");
  const scoreEl = root.querySelector(".dcdc-score");
  const categoryEl = root.querySelector(".dcdc-category");
  const descriptionEl = root.querySelector(".dcdc-description");
  const emojiEl = root.querySelector(".dcdc-result-emoji");
  const resultCard = root.querySelector(".dcdc-result-card");
  const revealValue = root.querySelector(".dcdc-score-reveal-value");
  const feedbackEl = root.querySelector(".dcdc-feedback");
  const errorEl = root.querySelector(".dcdc-error");
  const questionCard = root.querySelector(".dcdc-question-card");
  const participantForm = root.querySelector(".dcdc-participant-form");
  const participantNameInput = root.querySelector("#dcdc-participant-name");
  const participantPhotoInput = root.querySelector("#dcdc-participant-photo");
  const participantSubmitBtn = root.querySelector(".dcdc-participant-btn");
  const participantFeedback = root.querySelector(".dcdc-participant-feedback");
  const rankingList = root.querySelector(".dcdc-ranking-list");

  const praiseMessages = [
    "✨ Boa lembrança!",
    "🎮 Essa valeu pontos!",
    "🌟 Memória clássica!",
    "🕹️ Você tá no caminho certo!",
    "📼 Essa marcou a infância!",
  ];

  let questions = [];
  let current = 0;
  let answers = {};

  const escapeHtml = (value) =>
    String(value || "").replace(
      /[&<>"']/g,
      (char) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#039;",
        })[char],
    );

  const api = async (path, options = {}) => {
    const headers = {
      ...(options.body instanceof FormData
        ? {}
        : { "Content-Type": "application/json" }),
      "X-WP-Nonce": DCDC_DATA.nonce,
      ...(options.headers || {}),
    };

    const response = await fetch(DCDC_DATA.restUrl + path, {
      ...options,
      headers,
    });

    const responseText = await response.text();
    let data = {};
    try {
      data = JSON.parse(responseText);
    } catch (error) {
      data = {};
    }

    if (!response.ok) {
      throw new Error(data.message || "Não foi possível concluir a operação.");
    }

    return data;
  };

  const show = (screen) => {
    [start, questionScreen, resultScreen].forEach((item) => {
      const isActive = item === screen;
      item.hidden = !isActive;
      item.classList.toggle("is-active", isActive);
    });
  };

  const setFeedback = (message) => {
    feedbackEl.textContent = message;
    feedbackEl.hidden = false;
    feedbackEl.classList.add("is-visible");

    window.clearTimeout(setFeedback.timeoutId);
    setFeedback.timeoutId = window.setTimeout(() => {
      feedbackEl.hidden = true;
      feedbackEl.classList.remove("is-visible");
    }, 1400);
  };

  const setParticipantFeedback = (message, isError = false) => {
    participantFeedback.textContent = message;
    participantFeedback.hidden = false;
    participantFeedback.classList.toggle("is-error", isError);
    participantFeedback.classList.add("is-visible");

    window.clearTimeout(setParticipantFeedback.timeoutId);
    setParticipantFeedback.timeoutId = window.setTimeout(() => {
      participantFeedback.hidden = true;
      participantFeedback.classList.remove("is-visible", "is-error");
    }, 2400);
  };

  const error = (message) => {
    errorEl.textContent = message;
    errorEl.hidden = false;
    errorEl.classList.add("is-visible");
  };

  const animateQuestion = () => {
    questionCard.classList.remove("is-transitioning");
    window.requestAnimationFrame(() => {
      questionCard.classList.add("is-transitioning");
    });
  };

  const renderQuestion = () => {
    const total = questions.length;
    const q = questions[current];

    if (!q) return;

    const progress = ((current + 1) / total) * 100;
    progressLabel.textContent = `Pergunta ${current + 1} de ${total}`;
    progressCounter.textContent = `${current + 1}/${total}`;
    progressBar.style.width = `${progress}%`;
    decadeEl.textContent = q.decade
      ? `MEMÓRIAS DOS ${String(q.decade).toUpperCase()}`
      : "MEMÓRIAS";
    questionEl.textContent = q.question_text;
    optionsEl.innerHTML = "";
    nextBtn.disabled = true;
    nextBtn.textContent = current === total - 1 ? "Ver resultado" : "Continuar";

    q.answers.forEach((answer) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "dcdc-option";
      button.setAttribute("role", "listitem");
      button.setAttribute("aria-pressed", "false");
      button.textContent = answer.answer_text;

      button.addEventListener("click", () => {
        if (!q || !questionEl.textContent) return;

        const optionButtons = optionsEl.querySelectorAll(".dcdc-option");
        optionButtons.forEach((item) => {
          const selected = item === button;
          item.classList.toggle("selected", selected);
          item.setAttribute("aria-pressed", selected ? "true" : "false");
        });

        answers[q.id] = answer.id;
        nextBtn.disabled = false;
        setFeedback(
          praiseMessages[Math.floor(Math.random() * praiseMessages.length)],
        );
      });

      optionsEl.appendChild(button);
    });

    animateQuestion();
  };

  const loadRanking = async () => {
    try {
      const data = await api(
        `quiz/${encodeURIComponent(DCDC_DATA.campaign)}/ranking?limit=8`,
      );
      const participants = data.participants || [];

      rankingList.innerHTML = "";
      if (!participants.length) {
        const item = document.createElement("li");
        item.className = "dcdc-ranking-empty";
        item.textContent = "Seja o primeiro a aparecer no ranking.";
        rankingList.appendChild(item);
        return;
      }

      participants.forEach((participant, index) => {
        const item = document.createElement("li");
        item.className = "dcdc-ranking-item";
        const medal =
          index === 0
            ? "🥇"
            : index === 1
              ? "🥈"
              : index === 2
                ? "🥉"
                : `${index + 1}`;
          const photoUrl = participant.photo_url ? escapeHtml(participant.photo_url) : "";
          const initials = escapeHtml(String(participant.name || "?").trim().charAt(0).toUpperCase());
          const photo = photoUrl
            ? `<img class="dcdc-ranking-photo" src="${photoUrl}" alt="Foto de ${escapeHtml(participant.name)}" loading="lazy">`
            : `<span class="dcdc-ranking-photo dcdc-ranking-photo--empty" aria-hidden="true">${initials}</span>`;

        item.innerHTML = `
                    <span class="dcdc-ranking-position">${medal}</span>
                      ${photo}
                    <div class="dcdc-ranking-main">
                        <strong>${escapeHtml(participant.name)}</strong>
                        <small>${escapeHtml(participant.category ? participant.category.name : "Participante")}</small>
                    </div>
                    <span class="dcdc-ranking-score">${Number(participant.score) || 0} pts</span>
                `;

        rankingList.appendChild(item);
      });
    } catch (e) {
      rankingList.innerHTML =
        '<li class="dcdc-ranking-empty">Ranking indisponível no momento.</li>';
    }
  };

  const loadQuiz = async () => {
    errorEl.hidden = true;
    errorEl.classList.remove("is-visible");
    startBtn.disabled = true;

    try {
      const data = await api(
        `quiz/${encodeURIComponent(DCDC_DATA.campaign)}/questions`,
      );
      questions = data.questions || [];
      current = 0;
      answers = {};

      if (!questions.length) {
        throw new Error("Nenhuma pergunta foi cadastrada para esta campanha.");
      }

      participantNameInput.value = "";
      participantPhotoInput.value = "";
      participantForm.reset();
      show(questionScreen);
      renderQuestion();
    } catch (e) {
      error(e.message || "Não foi possível iniciar o quiz.");
    } finally {
      startBtn.disabled = false;
    }
  };

  const revealScore = (targetScore) => {
    revealValue.textContent = "0";
    resultCard.classList.remove("is-visible");

    const duration = 850;
    const startTime = window.performance.now();
    const animate = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = Math.round(targetScore * eased);
      revealValue.textContent = String(value);

      if (progress < 1) {
        window.requestAnimationFrame(animate);
        return;
      }

      revealValue.textContent = String(targetScore);
      scoreEl.textContent = `${targetScore} pontos`;
      categoryEl.textContent = `${emojiEl.textContent} ${categoryEl.dataset.name || "Resultado"}`;
      descriptionEl.textContent =
        descriptionEl.dataset.description ||
        "Uma infância cheia de boas lembranças.";
      resultCard.classList.add("is-visible");
    };

    window.requestAnimationFrame(animate);
  };

  const finish = async () => {
    nextBtn.disabled = true;

    try {
      const data = await api(
        `quiz/${encodeURIComponent(DCDC_DATA.campaign)}/result`,
        {
          method: "POST",
          body: JSON.stringify({ answers }),
        },
      );

      const score = Number(data.score) || 0;
      const categoryName =
        data.category && data.category.name ? data.category.name : "Resultado";
      const emoji =
        data.category && data.category.emoji ? data.category.emoji : "🎉";
      const description =
        data.category && data.category.description
          ? data.category.description
          : "Uma infância cheia de boas lembranças.";

      emojiEl.textContent = emoji;
      categoryEl.dataset.name = categoryName;
      descriptionEl.dataset.description = description;
      categoryEl.textContent = categoryName;
      descriptionEl.textContent = description;

      show(resultScreen);
      revealScore(score);
      await loadRanking();
    } catch (e) {
      error(e.message || "Não foi possível calcular o resultado.");
      nextBtn.disabled = false;
    }
  };

  const handleParticipantSubmit = async (event) => {
    event.preventDefault();

    const name = participantNameInput.value.trim();
    if (name.length < 2) {
      setParticipantFeedback(
        "Digite um nome válido para entrar no ranking.",
        true,
      );
      participantNameInput.focus();
      return;
    }

    const file = participantPhotoInput.files && participantPhotoInput.files[0];
    if (file) {
      const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
      if (!allowedTypes.includes(file.type)) {
        setParticipantFeedback("Use uma imagem JPG, PNG ou WEBP.", true);
        return;
      }

      if (file.size > 2 * 1024 * 1024) {
        setParticipantFeedback("A foto deve ter até 2MB.", true);
        return;
      }
    }

    participantSubmitBtn.disabled = true;

    try {
      const formData = new FormData();
      formData.append("name", name);
      formData.append("answers", JSON.stringify(answers));
      if (file) {
        formData.append("photo", file);
      }

      const response = await fetch(
        DCDC_DATA.restUrl +
          `quiz/${encodeURIComponent(DCDC_DATA.campaign)}/participant`,
        {
          method: "POST",
          headers: {
            "X-WP-Nonce": DCDC_DATA.nonce,
          },
          body: formData,
        },
      );

      const data = await response.json();
      if (!response.ok) {
        throw new Error(
          data.message || "Não foi possível salvar seu cadastro.",
        );
      }

      participantForm.reset();
      setParticipantFeedback("Seu nome foi salvo no ranking!");
      await loadRanking();
    } catch (e) {
      setParticipantFeedback(
        e.message || "Não foi possível completar o cadastro.",
        true,
      );
    } finally {
      participantSubmitBtn.disabled = false;
    }
  };

  startBtn.addEventListener("click", loadQuiz);

  nextBtn.addEventListener("click", () => {
    if (current < questions.length - 1) {
      current += 1;
      renderQuestion();
      return;
    }

    finish();
  });

  restartBtn.addEventListener("click", () => {
    questions = [];
    current = 0;
    answers = {};
    nextBtn.disabled = true;
    show(start);
    errorEl.hidden = true;
    errorEl.classList.remove("is-visible");
    feedbackEl.hidden = true;
    feedbackEl.classList.remove("is-visible");
    participantForm.reset();
    participantFeedback.hidden = true;
    participantFeedback.classList.remove("is-visible", "is-error");
    revealValue.textContent = "0";
    rankingList.innerHTML = "";
  });

  participantForm.addEventListener("submit", handleParticipantSubmit);
})();
