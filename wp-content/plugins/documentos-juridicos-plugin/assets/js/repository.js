document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('dj-repository');
  if (!root) return;

  const searchInput = document.getElementById('dj-search-input');
  const resultsEl = document.getElementById('dj-results');
  const featuredEl = document.getElementById('dj-featured');
  const paginationEl = document.getElementById('dj-pagination');
  const loadingEl = document.getElementById('dj-loading');
  const countEl = document.getElementById('dj-result-count');
  const categoriesEl = document.getElementById('dj-categories');
  const subjectsEl = document.getElementById('dj-subjects');

  let state = { page: 1, search: '', categoria: '', assunto: '' };
  let timer;

  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
  }[c]));

  const card = item => `
    <article class="dj-card">
      <div class="dj-card-body">
        <h3>${esc(item.title)}</h3>
        <p>${esc(item.excerpt)}</p>
        <div class="dj-tags">
          ${(item.categories || []).map(x => `<span>${esc(x)}</span>`).join('')}
          ${(item.subjects || []).map(x => `<span>${esc(x)}</span>`).join('')}
        </div>
        <div class="dj-card-actions">
          ${item.pdf_url ? `<button type="button" class="dj-view-pdf" data-pdf-url="${esc(item.pdf_url)}">Ver PDF</button>` : ''}
          ${item.pdf_download_url ? `<a href="${esc(item.pdf_download_url)}">Download</a>` : ''}
        </div>
      </div>
    </article>`;

  async function get(path) {
    const response = await fetch(DJRepository.apiUrl + path);
    if (!response.ok) throw new Error('Falha na consulta.');
    return response.json();
  }

  function setLoading(value) {
    loadingEl.hidden = !value;
  }

  async function loadFeatured() {
    try {
      const data = await get('documentos?per_page=6&orderby=views');
      featuredEl.innerHTML = data.data.map(card).join('') || '<p>Nenhum documento encontrado.</p>';
    } catch (e) {
      featuredEl.innerHTML = '<p>Não foi possível carregar os mais acessados.</p>';
    }
  }

  async function loadResults() {
    setLoading(true);
    const params = new URLSearchParams({
      page: state.page,
      per_page: 12,
      search: state.search,
      categoria: state.categoria,
      assunto: state.assunto
    });

    try {
      const data = await get(`documentos?${params}`);
      resultsEl.innerHTML = data.data.map(card).join('') || '<p>Nenhum documento encontrado.</p>';
      countEl.textContent = data.total ? `${data.total} resultado(s)` : '';
      renderPagination(data);
    } catch (e) {
      resultsEl.innerHTML = '<p>Não foi possível carregar os documentos.</p>';
    } finally {
      setLoading(false);
    }
  }

  function renderPagination(data) {
    if (data.last_page <= 1) {
      paginationEl.innerHTML = '';
      return;
    }
    let html = '';
    if (data.current_page > 1) html += `<button data-page="${data.current_page - 1}">Anterior</button>`;
    for (let i = 1; i <= data.last_page; i++) {
      if (i === 1 || i === data.last_page || Math.abs(i - data.current_page) <= 2) {
        html += `<button class="${i === data.current_page ? 'active' : ''}" data-page="${i}">${i}</button>`;
      }
    }
    if (data.current_page < data.last_page) html += `<button data-page="${data.current_page + 1}">Próxima</button>`;
    paginationEl.innerHTML = html;
    paginationEl.querySelectorAll('button').forEach(btn => {
      btn.addEventListener('click', () => {
        state.page = Number(btn.dataset.page);
        loadResults();
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  function buildFilters(items, container, key) {
    const terms = new Map();
    items.forEach(item => (item[key] || []).forEach(name => terms.set(name, name)));
    container.innerHTML = [...terms.keys()].sort().map(name => `
      <label class="dj-check">
        <input type="checkbox" value="${esc(name)}" data-filter="${key}">
        <span>${esc(name)}</span>
      </label>`).join('') || '<p>Nenhum filtro disponível.</p>';

    container.querySelectorAll('input').forEach(input => {
      input.addEventListener('change', () => {
        const selected = [...container.querySelectorAll('input:checked')].map(x => x.value);
        state[key === 'categories' ? 'categoria' : 'assunto'] = selected.join(',');
        state.page = 1;
        loadResults();
      });
    });
  }

  async function initializeFilters() {
    try {
      const data = await get('documentos?per_page=100');
      buildFilters(data.data, categoriesEl, 'categories');
      buildFilters(data.data, subjectsEl, 'subjects');
    } catch (e) {
      categoriesEl.innerHTML = '<p>Não foi possível carregar.</p>';
      subjectsEl.innerHTML = '<p>Não foi possível carregar.</p>';
    }
  }

  searchInput.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      state.search = searchInput.value.trim();
      state.page = 1;
      loadResults();
    }, 400);
  });

  searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(timer);
      state.search = searchInput.value.trim();
      state.page = 1;
      loadResults();
    }
  });

  root.querySelector('.dj-clear').addEventListener('click', () => {
    searchInput.value = '';
    root.querySelectorAll('input[type="checkbox"]').forEach(x => x.checked = false);
    state = { page: 1, search: '', categoria: '', assunto: '' };
    loadResults();
  });

  const modal = document.getElementById('dj-pdf-modal');
  const iframe = document.getElementById('dj-pdf-iframe');
  const closePdfModal = () => {
    iframe.src = '';
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  };

  document.addEventListener('click', event => {
    const viewButton = event.target.closest('.dj-view-pdf');
    if (viewButton) {
      iframe.src = viewButton.dataset.pdfUrl;
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      return;
    }
    if (event.target.closest('[data-dj-pdf-close]')) closePdfModal();
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !modal.hidden) closePdfModal();
  });

  loadFeatured();
  initializeFilters();
  loadResults();
});
