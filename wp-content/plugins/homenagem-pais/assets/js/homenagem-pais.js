document.addEventListener('DOMContentLoaded', function () {

    const forms = document.querySelectorAll('form.homenagem-pais-form, form#homenagem-form');
    if (!forms || forms.length === 0) return;

    forms.forEach(function (form) {

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const nonceField = form.querySelector('[name="security"]');
            const nonce = nonceField ? nonceField.value : (window.HomenagemPais && window.HomenagemPais.nonce);

            const name = form.querySelector('[name="h_name"]').value.trim();
            const unit = form.querySelector('[name="h_unit"]').value.trim();
            const message = form.querySelector('[name="h_message"]').value.trim();
            const mediaInput = form.querySelector('[name="h_media"]');
            const file = mediaInput && mediaInput.files.length ? mediaInput.files[0] : null;
            const submitButton = form.querySelector('[type="submit"], button[type="submit"]');

            if (!name || !message) {
                showToast('Preencha Nome e Mensagem', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                return;
            }

            function doUpload() {
                const fd = new FormData();
                fd.append('action', 'hp_submit_homenagem');
                fd.append('security', nonce);
                fd.append('h_name', name);
                fd.append('h_unit', unit);
                fd.append('h_message', message);
                if (file) fd.append('h_media', file);

                const originalText = submitButton ? submitButton.textContent : '';
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Enviando...';
                }

                fetch(window.HomenagemPais.ajax_url, {
                    method: 'POST',
                    body: fd
                }).then(r => r.json()).then(json => {
                    if (json.success) {
                        showToast(json.data.message || 'Enviado com sucesso', { autohide: true, delay: 3000, className: 'bg-success text-white' });
                        form.reset();
                    } else {
                        showToast(json.data && json.data.message ? json.data.message : 'Erro no envio', { autohide: true, delay: 4000, className: 'bg-danger text-white' });
                    }
                }).catch(err => {
                    console.error(err);
                    showToast('Erro na requisição', { autohide: true, delay: 4000, className: 'bg-danger text-white' });
                }).finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                });
            }

            if (file && file.type.indexOf('video') === 0) {
                const url = URL.createObjectURL(file);
                const v = document.createElement('video');
                v.preload = 'metadata';
                v.src = url;
                v.onloadedmetadata = function () {
                    URL.revokeObjectURL(url);
                    if (v.duration && v.duration > 40) {
                        showToast('O vídeo deve ter no máximo 40 segundos', { autohide: true, delay: 4000, className: 'bg-danger text-white' });
                        return;
                    }
                    doUpload();
                };
                v.onerror = function () {
                    showToast('Não foi possível ler o vídeo', { autohide: true, delay: 4000, className: 'bg-danger text-white' });
                };
            } else {
                doUpload();
            }

        });

    });

    // Delegation for open and like buttons
    document.addEventListener('click', function (e) {
        const likeBtn = e.target.closest('.btn-like');
        const openBtn = e.target.closest('.btn-open');

        if (likeBtn) {
            const id = likeBtn.getAttribute('data-id');
            if (!id) return;
            likeBtn.disabled = true;
            const originalText = likeBtn.textContent;
            likeBtn.textContent = 'Curtir...';

            const fd = new FormData();
            fd.append('action', 'hp_like_homenagem');
            fd.append('id', id);
            fd.append('security', window.HomenagemPais.nonce_like);

            fetch(window.HomenagemPais.ajax_url, { method: 'POST', body: fd })
                .then(r => r.json()).then(json => {
                    if (!json.success) {
                        showToast(json.data && json.data.message ? json.data.message : 'Erro', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                        return;
                    }
                    const likes = json.data.likes;
                    likeBtn.setAttribute('data-likes', likes);
                    likeBtn.textContent = 'Curtir (' + likes + ')';
                }).catch(err => {
                    console.error(err);
                    showToast('Erro ao curtir', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                }).finally(() => {
                    likeBtn.disabled = false;
                    if (!likeBtn.textContent.includes('Curtir (')) {
                        const likes = likeBtn.getAttribute('data-likes') || '0';
                        likeBtn.textContent = 'Curtir (' + likes + ')';
                    }
                });
        }

        if (openBtn && !likeBtn) {
            const id = openBtn.getAttribute('data-id');
            if (!id) return;

            openBtn.disabled = true;
            const originalText = openBtn.textContent;
            openBtn.textContent = 'Carregando...';

            const fd = new FormData();
            fd.append('action', 'hp_get_homenagem');
            fd.append('id', id);

            fetch(window.HomenagemPais.ajax_url, { method: 'POST', body: fd })
                .then(r => r.json()).then(json => {
                    if (!json.success) {
                        showToast(json.data && json.data.message ? json.data.message : 'Erro ao carregar', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                        return;
                    }
                    const d = json.data;
                    showHomenagemModal(d);
                }).catch(err => {
                    console.error(err);
                    showToast('Erro ao carregar', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                }).finally(() => {
                    openBtn.disabled = false;
                    openBtn.textContent = originalText;
                });
        }

        const loadMoreBtn = e.target.closest('.btn-load-more');
        if (loadMoreBtn) {
            const page = parseInt(loadMoreBtn.getAttribute('data-page'), 10) || 1;
            loadMoreBtn.disabled = true;
            const originalText = loadMoreBtn.textContent;
            loadMoreBtn.textContent = 'Carregando...';

            const fd = new FormData();
            fd.append('action', 'hp_load_more_homenagens');
            fd.append('page', page + 1);

            fetch(window.HomenagemPais.ajax_url, { method: 'POST', body: fd })
                .then(r => r.json()).then(json => {
                    if (!json.success) {
                        showToast(json.data && json.data.message ? json.data.message : 'Erro ao carregar mais', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                        return;
                    }

                    const grid = document.getElementById('hp-homenagem-grid');
                    if (grid) {
                        grid.insertAdjacentHTML('beforeend', json.data.html);
                    }

                    if (json.data.has_more) {
                        loadMoreBtn.setAttribute('data-page', json.data.next_page);
                        loadMoreBtn.textContent = 'Carregar mais';
                    } else {
                        loadMoreBtn.remove();
                    }
                }).catch(err => {
                    console.error(err);
                    showToast('Erro ao carregar mais', { autohide: true, delay: 3000, className: 'bg-danger text-white' });
                }).finally(() => {
                    if (document.body.contains(loadMoreBtn)) {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = originalText;
                    }
                });
        }
    });

    function showHomenagemModal(data) {
        let modal = document.getElementById('hp-homenagem-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'hp-homenagem-modal';
            modal.className = 'modal fade';
            modal.tabIndex = -1;
            modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable hp-homenagem-modal-dialog">
              <div class="modal-content hp-homenagem-modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Homenagem</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body hp-homenagem-modal-body"> </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
              </div>
            </div>`;
            document.body.appendChild(modal);
        }

        const body = modal.querySelector('.modal-body');
        let mediaHtml = '';
        if (data.media_url) {
            const isVideo = data.media_url.match(/\.(mp4|webm|ogg)(\?|$)/i) || data.media_url.indexOf('video') !== -1;
            if (isVideo) {
                mediaHtml = `<video controls style="max-width:100%;height:auto"><source src="${data.media_url}"></video>`;
            } else {
                mediaHtml = `<img src="${data.media_url}" style="max-width:100%;height:auto" />`;
            }
        }

        body.innerHTML = `
            <div class="mb-3">${mediaHtml}</div>
            <h5>${escapeHtml(data.name || data.title)}</h5>
            <p class="text-muted">${escapeHtml(data.unit || '')}</p>
            <div>${data.message}</div>
            <div class="mt-3"><button class="btn btn-sm btn-outline-primary btn-like" data-id="${data.id}">Curtir (${data.likes})</button></div>
        `;

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>\"]+/g, function (s) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]); });
    }

    // Toast helper using Bootstrap toasts when available
    function showToast(message, opts) {
        opts = opts || {};
        const autohide = opts.autohide !== undefined ? opts.autohide : true;
        const delay = opts.delay || 3000;
        const className = opts.className || '';

        // create container
        let container = document.getElementById('hp-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'hp-toast-container';
            container.style.position = 'fixed';
            container.style.top = '1rem';
            container.style.right = '1rem';
            container.style.zIndex = 1080;
            document.body.appendChild(container);
        }

        const toastEl = document.createElement('div');
        toastEl.className = 'toast ' + className;
        toastEl.role = 'status';
        toastEl.ariaLive = 'polite';
        toastEl.ariaAtomic = 'true';
        toastEl.innerHTML = `<div class="toast-body">${message}</div>`;
        container.appendChild(toastEl);

        if (window.bootstrap && window.bootstrap.Toast) {
            const t = new bootstrap.Toast(toastEl, { autohide: autohide, delay: delay });
            t.show();
            toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
        } else {
            // fallback
            setTimeout(function () { toastEl.remove(); }, delay);
        }
        return toastEl;
    }

});
