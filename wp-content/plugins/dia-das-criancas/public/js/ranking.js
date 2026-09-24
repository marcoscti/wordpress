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
    const response = await fetch(
      `${DCDC_RANKING_DATA.restUrl}quiz/${encodeURIComponent(DCDC_RANKING_DATA.campaign)}/ranking?limit=${DCDC_RANKING_DATA.limit}`,
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
      const initials = escapeHtml(String(participant.name || "?").trim().charAt(0).toUpperCase());
      const photo = photoUrl
        ? `<img class="dcdc-ranking-shortcode__photo" src="${photoUrl}" alt="Foto de ${escapeHtml(participant.name)}" loading="lazy">`
        : `<span class="dcdc-ranking-shortcode__photo dcdc-ranking-shortcode__photo--empty" aria-hidden="true">${initials}</span>`;
      const category = participant.category || {};
      const categoryName = category.name || "Participante";
      const categoryEmoji = category.emoji || "";
      const categoryDescription = category.description || "";
      const medal = position <= 3 ? ["🥇", "🥈", "🥉"][position - 1] : position;

      return `<article class="dcdc-ranking-shortcode__item dcdc-ranking-shortcode__item--rank-${position}">
        <span class="dcdc-ranking-shortcode__position">${medal}</span>
        ${photo}
        <div class="dcdc-ranking-shortcode__person">
          <strong>${escapeHtml(participant.name)}</strong>
          <small>${escapeHtml(categoryEmoji)} ${escapeHtml(categoryName)}</small>
          ${categoryDescription ? `<p>${escapeHtml(categoryDescription)}</p>` : ""}
        </div>
        <strong class="dcdc-ranking-shortcode__score">${Number(participant.score) || 0} pts</strong>
      </article>`;
    }).join("");
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
      render(root, await loadParticipants());
    } catch (error) {
      status.textContent = "";
      root.querySelector("[data-ranking-items]").innerHTML = '<p class="dcdc-ranking-shortcode__empty">Ranking indisponível no momento.</p>';
    }
  };

  roots.forEach(init);
})();