document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll(
    "form.homenagem-pais-form, form#homenagem-form",
  );
  if (!forms || forms.length === 0) return;

  forms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const nonceField = form.querySelector('[name="security"]');
      const nonce = nonceField
        ? nonceField.value
        : window.HomenagemPais && window.HomenagemPais.nonce;

      const name = form.querySelector('[name="h_name"]').value.trim();
      const unit = form.querySelector('[name="h_unit"]').value.trim();
      const message = form.querySelector('[name="h_message"]').value.trim();
      const mediaInput = form.querySelector('[name="h_media"]');
      const file =
        mediaInput && mediaInput.files.length ? mediaInput.files[0] : null;
      const submitButton = form.querySelector(
        '[type="submit"], button[type="submit"]',
      );

      if (!name || !message) {
        showToast("Preencha Nome e Mensagem", {
          autohide: true,
          delay: 3000,
          className: "bg-danger text-white",
        });
        return;
      }

      function doUpload() {
        const fd = new FormData();
        fd.append("action", "hp_submit_homenagem");
        fd.append("security", nonce);
        fd.append("h_name", name);
        fd.append("h_unit", unit);
        fd.append("h_message", message);
        if (file) fd.append("h_media", file);

        const originalText = submitButton ? submitButton.textContent : "";
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.textContent = "Enviando...";
        }

        fetch(window.HomenagemPais.ajax_url, {
          method: "POST",
          body: fd,
        })
          .then((r) => r.json())
          .then((json) => {
            if (json.success) {
              showToast(json.data.message || "Enviado com sucesso", {
                autohide: true,
                delay: 3000,
                className: "bg-success text-white",
              });
              form.reset();
            } else {
              showToast(
                json.data && json.data.message
                  ? json.data.message
                  : "Erro no envio",
                {
                  autohide: true,
                  delay: 4000,
                  className: "bg-danger text-white",
                },
              );
            }
          })
          .catch((err) => {
            console.error(err);
            showToast("Erro na requisição", {
              autohide: true,
              delay: 4000,
              className: "bg-danger text-white",
            });
          })
          .finally(() => {
            if (submitButton) {
              submitButton.disabled = false;
              submitButton.textContent = originalText;
            }
          });
      }

      if (file && file.type.indexOf("video") === 0) {
        const url = URL.createObjectURL(file);
        const v = document.createElement("video");
        v.preload = "metadata";
        v.src = url;
        v.onloadedmetadata = function () {
          URL.revokeObjectURL(url);
          if (v.duration && v.duration > 40) {
            showToast("O vídeo deve ter no máximo 40 segundos", {
              autohide: true,
              delay: 4000,
              className: "bg-danger text-white",
            });
            return;
          }
          doUpload();
        };
        v.onerror = function () {
          showToast("Não foi possível ler o vídeo", {
            autohide: true,
            delay: 4000,
            className: "bg-danger text-white",
          });
        };
      } else {
        doUpload();
      }
    });
  });

  function getPersistedLikedIds() {
    try {
      const stored = localStorage.getItem("hp_liked_homenagens") || "[]";
      return new Set(JSON.parse(stored));
    } catch (err) {
      return new Set();
    }
  }

  function persistLikedIds(ids) {
    try {
      localStorage.setItem("hp_liked_homenagens", JSON.stringify([...ids]));
    } catch (err) {
      // ignore storage failures
    }
  }

  function applyPersistedLikedState() {
    const likedIds = getPersistedLikedIds();
    if (!likedIds.size) return;

    document.querySelectorAll(".btn-like[data-id]").forEach(function (btn) {
      const id = btn.getAttribute("data-id");
      if (likedIds.has(id)) {
        btn.classList.add("liked");
        setLikeButtonCount(btn, btn.getAttribute("data-likes") || "");
      }
    });
  }

  function markLiked(button) {
    const id = button.getAttribute("data-id");
    if (!id) return;
    const likedIds = getPersistedLikedIds();
    likedIds.add(id);
    persistLikedIds(likedIds);
  }

  function unmarkLiked(button) {
    const id = button.getAttribute("data-id");
    if (!id) return;
    const likedIds = getPersistedLikedIds();
    likedIds.delete(id);
    persistLikedIds(likedIds);
  }

  applyPersistedLikedState();

  // Delegation for open and like buttons
  document.addEventListener("click", function (e) {
    const likeBtn = e.target.closest(".btn-like");
    const openBtn = e.target.closest(".btn-open");

    if (likeBtn) {
      const id = likeBtn.getAttribute("data-id");
      if (!id) return;
      likeBtn.disabled = true;
      const originalHtml = likeBtn.innerHTML;
      const wasLiked = likeBtn.classList.contains("liked");

      const fd = new FormData();
      fd.append("action", "hp_like_homenagem");
      fd.append("id", id);
      fd.append("security", window.HomenagemPais.nonce_like);
      fd.append("type", wasLiked ? "unlike" : "like");

      fetch(window.HomenagemPais.ajax_url, { method: "POST", body: fd })
        .then((r) => r.json())
        .then((json) => {
          if (!json.success) {
            showToast(
              json.data && json.data.message ? json.data.message : "Erro",
              {
                autohide: true,
                delay: 3000,
                className: "bg-danger text-white",
              },
            );
            return;
          }
          const likes = json.data.likes;
          setLikeButtonCount(likeBtn, likes);
          if (json.data.liked) {
            likeBtn.classList.add("liked");
            markLiked(likeBtn);
          } else {
            likeBtn.classList.remove("liked");
            unmarkLiked(likeBtn);
          }
        })
        .catch((err) => {
          console.error(err);
          likeBtn.innerHTML = originalHtml;
          if (wasLiked) {
            likeBtn.classList.add("liked");
          } else {
            likeBtn.classList.remove("liked");
          }
          showToast("Erro ao curtir", {
            autohide: true,
            delay: 3000,
            className: "bg-danger text-white",
          });
        })
        .finally(() => {
          likeBtn.disabled = false;
        });
    }

    if (openBtn && !likeBtn) {
      const id = openBtn.getAttribute("data-id");
      if (!id) return;

      openBtn.classList.add("is-loading");
      openBtn.setAttribute("aria-busy", "true");
      if (!openBtn.querySelector(".hp-card-loader")) {
        openBtn.insertAdjacentHTML(
          "beforeend",
          `
                    <div class="hp-card-loader" aria-hidden="true">
                        <svg viewBox="0 0 50 50" class="hp-card-loader-svg">
                            <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4"></circle>
                        </svg>
                    </div>
                `,
        );
      }

      const fd = new FormData();
      fd.append("action", "hp_get_homenagem");
      fd.append("id", id);

      fetch(window.HomenagemPais.ajax_url, { method: "POST", body: fd })
        .then((r) => r.json())
        .then((json) => {
          if (!json.success) {
            showToast(
              json.data && json.data.message
                ? json.data.message
                : "Erro ao carregar",
              {
                autohide: true,
                delay: 3000,
                className: "bg-danger text-white",
              },
            );
            return;
          }
          const d = json.data;
          showHomenagemModal(d);
        })
        .catch((err) => {
          console.error(err);
          showToast("Erro ao carregar", {
            autohide: true,
            delay: 3000,
            className: "bg-danger text-white",
          });
        })
        .finally(() => {
          openBtn.classList.remove("is-loading");
          openBtn.removeAttribute("aria-busy");
          const loader = openBtn.querySelector(".hp-card-loader");
          if (loader) {
            loader.remove();
          }
        });
    }

    const loadMoreBtn = e.target.closest(".btn-load-more");
    if (loadMoreBtn) {
      const page = parseInt(loadMoreBtn.getAttribute("data-page"), 10) || 1;
      loadMoreBtn.disabled = true;
      const originalText = loadMoreBtn.textContent;
      loadMoreBtn.textContent = "Carregando...";

      const fd = new FormData();
      fd.append("action", "hp_load_more_homenagens");
      fd.append("page", page + 1);

      fetch(window.HomenagemPais.ajax_url, { method: "POST", body: fd })
        .then((r) => r.json())
        .then((json) => {
          if (!json.success) {
            showToast(
              json.data && json.data.message
                ? json.data.message
                : "Erro ao carregar mais",
              {
                autohide: true,
                delay: 3000,
                className: "bg-danger text-white",
              },
            );
            return;
          }

          const grid = document.getElementById("hp-homenagem-grid");
          if (grid) {
            grid.insertAdjacentHTML("beforeend", json.data.html);
          }

          if (json.data.has_more) {
            loadMoreBtn.setAttribute("data-page", json.data.next_page);
            loadMoreBtn.textContent = "Carregar mais";
          } else {
            loadMoreBtn.remove();
          }

          applyPersistedLikedState();
        })
        .catch((err) => {
          console.error(err);
          showToast("Erro ao carregar mais", {
            autohide: true,
            delay: 3000,
            className: "bg-danger text-white",
          });
        })
        .finally(() => {
          if (document.body.contains(loadMoreBtn)) {
            loadMoreBtn.disabled = false;
            loadMoreBtn.textContent = originalText;
          }
        });
    }
  });

  function showHomenagemModal(data) {
    let modal = document.getElementById("hp-homenagem-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "hp-homenagem-modal";
      modal.className = "modal fade";
      modal.tabIndex = -1;
      modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable hp-homenagem-modal-dialog">
              <div class="modal-content hp-homenagem-modal-content">
                <div class="modal-header">
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body hp-homenagem-modal-body"> </div>
              </div>
            </div>`;
      document.body.appendChild(modal);
    }

    const body = modal.querySelector(".modal-body");
    let mediaHtml = "";
    const mediaType = data.media_type || "";
    const isVideo =
      mediaType === "video" ||
      (data.media_url &&
        (data.media_url.match(/\.(mp4|webm|ogg)(\?|$)/i) ||
          data.media_url.indexOf("video") !== -1));

    if (data.media_url && isVideo) {
      mediaHtml = `<div class="hp-modal-media"><video controls playsinline controlsList="nodownload" preload="metadata"><source src="${data.media_url}"></video></div>`;
    } else if (data.media_url) {
      const highResUrl = data.media_url.replace(
        /-\d+x\d+(?=\.[a-zA-Z0-9]+$)/,
        "",
      );
      mediaHtml = `<div class="hp-modal-media"><img src="${highResUrl}" alt="${escapeHtml(data.name || data.title)}" class="img-fluid" /></div>`;
    } else {
      mediaHtml = `<div class="hp-modal-media hp-modal-media--empty"><span class="dashicons dashicons-format-video" style="font-size:2.2rem"></span></div>`;
    }

    body.innerHTML = `
            <div class="hp-modal-shell">
                <div class="hp-modal-media-column">${mediaHtml}</div>
                <div class="hp-modal-content-column">
                    <h4 class="fw-bold mb-0">${escapeHtml(data.name || data.title)} <button class="btn btn-sm btn-outline-primary btn-like rounded" data-id="${data.id}" data-likes="${data.likes}"><svg height="15" viewBox="0 0 34 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M28.9181 4.24906C28.2054 3.53604 27.3592 2.97043 26.4279 2.58452C25.4965 2.19862 24.4983 2 23.4902 2C22.4821 2 21.4838 2.19862 20.5525 2.58452C19.6211 2.97043 18.7749 3.53604 18.0623 4.24906L16.5832 5.72813L15.1041 4.24906C13.6646 2.80949 11.7121 2.00075 9.67622 2.00075C7.64036 2.00075 5.68788 2.80949 4.24831 4.24906C2.80874 5.68863 2 7.64111 2 9.67697C2 11.7128 2.80874 13.6653 4.24831 15.1049L16.5832 27.4398L28.9181 15.1049C29.6311 14.3922 30.1967 13.546 30.5826 12.6147C30.9685 11.6833 31.1671 10.6851 31.1671 9.67697C31.1671 8.66884 30.9685 7.6706 30.5826 6.73926C30.1967 5.80792 29.6311 4.96174 28.9181 4.24906Z" stroke="#0094c6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg> ${data.likes}</button></h4>
                    <small class="text-muted mb-3">${escapeHtml(data.unit || "")}</small>
                    <div class="hp-modal-message">${data.message}</div>
                </div>
            </div>
        `;

    applyPersistedLikedState();

    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
  }

  function escapeHtml(str) {
    if (!str) return "";
    return str.replace(/[&<>\"]+/g, function (s) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[s];
    });
  }

  function setLikeButtonCount(button, likes) {
    button.setAttribute("data-likes", likes);
    const svg = button.querySelector("svg");

    if (svg) {
      let next = svg.nextSibling;
      while (next) {
        const current = next;
        next = next.nextSibling;
        current.remove();
      }
      svg.insertAdjacentText("afterend", " " + likes);
    } else {
      button.textContent = likes;
    }
  }

  // Toast helper using Bootstrap toasts when available
  function showToast(message, opts) {
    opts = opts || {};
    const autohide = opts.autohide !== undefined ? opts.autohide : true;
    const delay = opts.delay || 3000;
    const className = opts.className || "";

    // create container
    let container = document.getElementById("hp-toast-container");
    if (!container) {
      container = document.createElement("div");
      container.id = "hp-toast-container";
      container.style.position = "fixed";
      container.style.bottom = "1rem";
      container.style.right = "1rem";
      container.style.zIndex = 1080;
      document.body.appendChild(container);
    }

    const toastEl = document.createElement("div");
    toastEl.className = "toast " + className;
    toastEl.role = "status";
    toastEl.ariaLive = "polite";
    toastEl.ariaAtomic = "true";
    toastEl.innerHTML = `<div class="toast-body">${message}</div>`;
    container.appendChild(toastEl);

    if (window.bootstrap && window.bootstrap.Toast) {
      const t = new bootstrap.Toast(toastEl, {
        autohide: autohide,
        delay: delay,
      });
      t.show();
      toastEl.addEventListener("hidden.bs.toast", function () {
        toastEl.remove();
      });
    } else {
      // fallback
      setTimeout(function () {
        toastEl.remove();
      }, delay);
    }
    return toastEl;
  }
});
