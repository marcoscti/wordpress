(() => {
  "use strict";

  const roots = document.querySelectorAll("[data-dcdc-ranking]");
  if (!roots.length || typeof DCDC_RANKING_DATA === "undefined") return;

  const escapeHtml = (value) =>
    String(value || "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    })[char]);

  const loadParticipants = async () => {
    const limit = Number(DCDC_RANKING_DATA.limit) || 0;
    const limitQuery = limit > 0 ? `?limit=${encodeURIComponent(limit)}` : "";
    const response = await fetch(
      `${DCDC_RANKING_DATA.restUrl}quiz/${encodeURIComponent(DCDC_RANKING_DATA.campaign)}/ranking${limitQuery}`,
    );
    const body = await response.text();
    let data = {};
    try {
      data = JSON.parse(body);
    } catch (error) {
      data = {};
    }
    if (!response.ok) throw new Error(data.message || "Ranking indisponível.");
    return data.participants || [];
  };

  const initialsFor = (name) => String(name || "?").trim().charAt(0).toUpperCase();

  const render = (root, participants) => {
    const items = root.querySelector("[data-ranking-items]");
    const status = root.querySelector("[data-ranking-status]");
    if (!participants.length) {
      status.textContent = "";
      items.innerHTML = '<p class="dcdc-ranking-shortcode__empty">Ainda não há participações.</p>';
      return;
    }

    status.textContent = `${participants.length} participante${participants.length === 1 ? "" : "s"}`;
    items.innerHTML = participants.map((participant, index) => {
      const position = index + 1;
      const photoUrl = participant.photo_url ? escapeHtml(participant.photo_url) : "";
      const initials = escapeHtml(initialsFor(participant.name));
      const photo = photoUrl
        ? `<img class="dcdc-ranking-shortcode__photo" src="${photoUrl}" alt="" loading="lazy">`
        : `<span class="dcdc-ranking-shortcode__photo dcdc-ranking-shortcode__photo--empty" aria-hidden="true">${initials}</span>`;
      const category = participant.category || {};
      const categoryName = category.name || "Participante";
      const categoryEmoji = category.emoji || "";
      const categoryDescription = category.description || "";
      const medal = position <= 3 ? ["🥇", "🥈", "🥉"][position - 1] : position;
      const ariaLabel = `Ver detalhes de ${participant.name}, ${categoryName}, posição ${position}`;

      return `<article class="dcdc-ranking-shortcode__item dcdc-ranking-shortcode__item--rank-${position}">
        <button type="button" class="dcdc-ranking-shortcode__trigger" data-ranking-participant="${index}" aria-label="${escapeHtml(ariaLabel)}">
          <span class="dcdc-ranking-shortcode__position">${medal}</span>
          ${photo}
          <span class="dcdc-ranking-shortcode__person">
            <strong>${escapeHtml(participant.name)}</strong>
            <small>${escapeHtml(categoryEmoji)} ${escapeHtml(categoryName)}</small>
            <small class="dcdc-ranking-shortcode__unit">${escapeHtml(participant.unit || "Unidade não informada")}</small>
            ${categoryDescription ? `<span class="dcdc-ranking-shortcode__description">${escapeHtml(categoryDescription)}</span>` : ""}
          </span>
          <strong class="dcdc-ranking-shortcode__score">${Number(participant.score) || 0} pts</strong>
        </button>
      </article>`;
    }).join("");
  };

  const setupModal = (root, participants) => {
    const modal = root.querySelector("[data-ranking-modal]");
    const feedback = modal.querySelector("[data-ranking-modal-feedback]");
    const photoWrap = modal.querySelector("[data-ranking-modal-photo-wrap]");
    const downloadButton = modal.querySelector("[data-ranking-modal-download]");
    let activeParticipant = null;
    let activePosition = 0;

    const setFeedback = (message) => {
      feedback.textContent = message;
      feedback.hidden = !message;
    };

    root.querySelector("[data-ranking-items]").addEventListener("click", (event) => {
      const trigger = event.target.closest("[data-ranking-participant]");
      if (!trigger) return;

      activePosition = Number(trigger.dataset.rankingParticipant);
      activeParticipant = participants[activePosition];
      if (!activeParticipant) return;

      const category = activeParticipant.category || {};
      const position = activePosition + 1;
      photoWrap.innerHTML = activeParticipant.photo_url
        ? `<img src="${escapeHtml(activeParticipant.photo_url)}" alt="Foto de ${escapeHtml(activeParticipant.name)}">`
        : `<span class="dcdc-ranking-modal__photo-empty" aria-hidden="true">${escapeHtml(initialsFor(activeParticipant.name))}</span>`;
      modal.querySelector("[data-ranking-modal-position]").textContent = `#${position} no ranking`;
      modal.querySelector("[data-ranking-modal-emoji]").textContent = category.emoji || "🏆";
      modal.querySelector("[data-ranking-modal-name]").textContent = activeParticipant.name || "Participante";
      modal.querySelector("[data-ranking-modal-category]").textContent = category.name || "Participante";
      modal.querySelector("[data-ranking-modal-unit]").textContent = activeParticipant.unit || "Unidade não informada";
      modal.querySelector("[data-ranking-modal-score]").textContent = `${Number(activeParticipant.score) || 0} pontos`;
      modal.querySelector("[data-ranking-modal-description]").textContent = category.description || "";
      setFeedback("");
      modal.showModal();
    });

    modal.querySelector("[data-ranking-modal-close]").addEventListener("click", () => modal.close());
    modal.addEventListener("click", (event) => {
      if (event.target === modal) modal.close();
    });

    downloadButton.addEventListener("click", async () => {
      if (!activeParticipant) return;
      downloadButton.disabled = true;
      setFeedback("");
      try {
        await window.DCDC_Card.downloadCard(activeParticipant, activePosition + 1);
      } catch (error) {
        setFeedback(error.message || "Não foi possível gerar o cartão.");
      } finally {
        downloadButton.disabled = false;
      }
    });
  };

  const setupViewToggle = (root) => {
    const buttons = root.querySelectorAll("[data-ranking-view]");
    buttons.forEach((button) => {
      button.addEventListener("click", () => {
        const view = button.dataset.rankingView === "list" ? "list" : "cards";
        root.classList.toggle("dcdc-ranking-shortcode--cards", view === "cards");
        root.classList.toggle("dcdc-ranking-shortcode--list", view === "list");
        buttons.forEach((item) => {
          const active = item === button;
          item.classList.toggle("is-active", active);
          item.setAttribute("aria-pressed", active ? "true" : "false");
        });
      });
    });
  };

  const init = async (root) => {
    setupViewToggle(root);
    const status = root.querySelector("[data-ranking-status]");
    try {
      const participants = await loadParticipants();
      render(root, participants);
      if (participants.length) setupModal(root, participants);
    } catch (error) {
      status.textContent = "";
      root.querySelector("[data-ranking-items]").innerHTML = '<p class="dcdc-ranking-shortcode__empty">Ranking indisponível no momento.</p>';
    }
  };

  roots.forEach(init);
})();
