(() => {
  const cfg = window.cgAdmin || {};
  const i18n = cfg.i18n || {};
  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
  const parse = (src, fallback) => { try { return JSON.parse(src || ''); } catch (_) { return fallback; } };
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const req = (action, q, extra = {}) => {
    const u = new URL(cfg.ajaxUrl || '', window.location.origin);
    u.searchParams.set('action', action); u.searchParams.set('nonce', cfg.nonce || ''); u.searchParams.set('q', q || '');
    Object.entries(extra).forEach(([k, v]) => v && u.searchParams.set(k, v));
    return fetch(u, { credentials: 'same-origin' }).then((r) => r.json());
  };

  document.querySelectorAll('.cg-char-counter').forEach((counter) => {
    const input = document.getElementById(counter.dataset.target); const max = Number(counter.dataset.max || 500);
    if (!input) return;
    const paint = () => {
      const len = input.value.length; counter.textContent = `${len} / ${max}`;
      counter.classList.toggle('is-over', len > max);
    };
    input.addEventListener('input', paint); paint();
  });

  document.querySelectorAll('.cg-autocomplete').forEach((wrap) => {
    const input = wrap.querySelector('.cg-ac-input');
    const list = wrap.querySelector('.cg-ac-results');
    const selectedList = wrap.querySelector('.cg-selected-list');
    const hidden = document.getElementById(wrap.dataset.hidden);
    const isRelated = selectedList?.dataset.kind === 'related';
    let selected = parse(wrap.dataset.selected, []);
    if (!selected.length) selected = parse(hidden?.value, []);
    let results = []; let active = -1;

    const sync = () => {
      if (isRelated) hidden.value = JSON.stringify(selected.map((x) => ({ post_id: Number(x.post_id), custom_title: x.custom_title || '' })));
      else hidden.value = JSON.stringify(selected.map((x) => Number(x.id || x)));
    };
    const remove = (id) => { selected = selected.filter((x) => Number(isRelated ? x.post_id : (x.id || x)) !== Number(id)); renderSelected(); };
    const renderSelected = () => {
      const html = selected.map((item) => {
        const id = Number(isRelated ? item.post_id : (item.id || item));
        const title = esc(item.title || item.post_title || `#${id}`);
        if (!isRelated) return `<li data-id="${id}"><span>${title}</span><button type="button" class="cg-remove">${esc(i18n.remove || '')}</button></li>`;
        const ct = esc(item.custom_title || '');
        return `<li data-id="${id}"><span>${title}</span><input type="text" class="cg-custom-title" value="${ct}" placeholder="${esc(i18n.customTitle || '')}" /><button type="button" class="cg-remove">${esc(i18n.remove || '')}</button></li>`;
      }).join('');
      selectedList.innerHTML = html; sync();
    };

    const renderResults = () => {
      if (!results.length) { list.hidden = true; list.innerHTML = ''; return; }
      list.hidden = false;
      list.innerHTML = results.map((r, idx) => `<li class="${idx === active ? 'is-active' : ''}" data-id="${r.id}">${esc(r.title)}</li>`).join('');
    };

    const pick = (id) => {
      const item = results.find((r) => Number(r.id) === Number(id));
      if (!item) return;
      if (isRelated) {
        if (!selected.some((x) => Number(x.post_id) === Number(item.id))) selected.push({ post_id: Number(item.id), custom_title: '', title: item.title });
      } else if (!selected.some((x) => Number(x.id || x) === Number(item.id))) selected.push({ id: Number(item.id), title: item.title });
      results = []; active = -1; input.value = ''; renderResults(); renderSelected();
    };

    input?.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { active = Math.min(active + 1, results.length - 1); renderResults(); e.preventDefault(); }
      if (e.key === 'ArrowUp') { active = Math.max(active - 1, 0); renderResults(); e.preventDefault(); }
      if (e.key === 'Enter' && active >= 0) { pick(results[active].id); e.preventDefault(); }
      if (e.key === 'Escape') { results = []; active = -1; renderResults(); }
    });

    input?.addEventListener('input', debounce(() => {
      const q = input.value.trim();
      if (q.length < 2) { results = []; active = -1; renderResults(); return; }
      req(wrap.dataset.action, q).then((res) => {
        results = Array.isArray(res) ? res : []; active = results.length ? 0 : -1; renderResults();
      }).catch(() => { results = []; active = -1; renderResults(); });
    }, 250));

    list?.addEventListener('mousedown', (e) => { const li = e.target.closest('li[data-id]'); if (li) pick(li.dataset.id); });
    selectedList?.addEventListener('click', (e) => { if (e.target.classList.contains('cg-remove')) remove(e.target.closest('li')?.dataset.id); });
    selectedList?.addEventListener('input', (e) => {
      if (!e.target.classList.contains('cg-custom-title')) return;
      const li = e.target.closest('li');
      const id = Number(li?.dataset.id);
      selected = selected.map((x) => (Number(x.post_id) === id ? { ...x, custom_title: e.target.value } : x));
      sync();
    });

    renderSelected();
  });
})();
