(() => {
  const cfg = window.cgGlossary || {};
  const i18n = cfg.i18n || {};
  const key = cfg.themeStorageKey || 'cg-theme';
  const root = document.getElementById('cg-root');
  if (!root) return;
  const wrapper = root.closest('.cg-glossary-wrapper');
  const search = document.getElementById('cg-search');
  const toggle = document.getElementById('cg-theme-toggle');
  const providers = ['aws', 'azure', 'gcp', 'generic'];

  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const groupBy = (arr, fn) => arr.reduce((m, x) => ((m[fn(x)] ||= []).push(x), m), {});

  const applyTheme = (theme) => {
    wrapper?.setAttribute('data-theme', theme);
    toggle.textContent = theme === 'dark' ? (i18n.light || 'Light') : (i18n.dark || 'Dark');
  };
  applyTheme(localStorage.getItem(key) || 'light');
  toggle?.addEventListener('click', () => {
    const next = (wrapper?.getAttribute('data-theme') || 'light') === 'light' ? 'dark' : 'light';
    localStorage.setItem(key, next); applyTheme(next);
  });
  if (search) search.placeholder = i18n.searchPlaceholder || '';

  const skeleton = () => {
    root.innerHTML = '<div class="cg-skeleton cg-raised"></div><div class="cg-skeleton cg-raised"></div><div class="cg-skeleton cg-raised"></div>';
  };

  const makeRows = (services) => {
    const bySlug = Object.fromEntries(services.map((s) => [s.slug, s]));
    const seen = new Set(); const rows = [];
    const walk = (slug, bucket) => {
      if (!slug || seen.has(slug) || !bySlug[slug]) return;
      seen.add(slug); bucket.push(bySlug[slug]);
      (bySlug[slug].equivalents || []).forEach((next) => walk(next, bucket));
    };
    services.forEach((s) => {
      if (seen.has(s.slug)) return;
      const bucket = []; walk(s.slug, bucket);
      if (!bucket.length) bucket.push(s);
      rows.push(bucket.sort((a, b) => (a.order ?? 0) - (b.order ?? 0) || a.title.localeCompare(b.title)));
    });
    return rows;
  };

  const cell = (service, provider) => {
    if (!service) return '<td class="cg-raised"><div class="cg-cell"><div class="cg-muted">-</div></div></td>';
    const posts = service.related_posts || [];
    const visible = posts.slice(0, 4).map((p) => `<li><a href="${esc(p.url)}">${esc(p.title)}</a></li>`).join('');
    const more = posts.length > 4 ? `<li class="cg-muted">${esc((i18n.morePosts || '+%d more').replace('%d', String(posts.length - 4)))}</li>` : '';
    return `<td class="cg-raised"><div class="cg-cell"><div class="cg-cell-top"><span class="cg-icon cg-icon--${esc(provider)}"></span><span class="cg-cell-name">${esc(service.title)}</span><button type="button" class="cg-info" data-id="${service.id}" aria-label="info">ⓘ</button></div><div class="cg-cell-bottom"><ul class="cg-posts">${visible || `<li class="cg-muted">${esc(i18n.noPosts || '')}</li>`}${more}</ul></div></div></td>`;
  };

  const rowMap = (bucket) => {
    const map = { aws: null, azure: null, gcp: null, generic: null };
    bucket.forEach((s) => { if (providers.includes(s.provider)) map[s.provider] = s; });
    return map;
  };

  const render = (services, q = '') => {
    const term = q.trim().toLocaleLowerCase('hu-HU');
    const filtered = !term ? services : services.filter((s) => `${s.title} ${(s.short_description || '')}`.toLocaleLowerCase('hu-HU').includes(term));
    const byCat = groupBy(filtered, (s) => s.category || 'egyeb');
    const cats = Object.keys(byCat).sort((a, b) => a.localeCompare(b));

    root.innerHTML = cats.map((cat, idx) => {
      const rows = makeRows(byCat[cat]);
      const desktopRows = rows.map((bucket) => {
        const m = rowMap(bucket);
        return `<tr>${cell(m.aws, 'aws')}${cell(m.azure, 'azure')}${cell(m.gcp, 'gcp')}${cell(m.generic, 'generic')}</tr>`;
      }).join('');
      const mobile = rows.map((bucket) => {
        const m = rowMap(bucket);
        return providers.map((p) => m[p] ? `<article class="cg-mobile-card cg-raised">${cell(m[p], p).replace(/^<td class="cg-raised">|<\/td>$/g,'')}</article>` : '').join('');
      }).join('');
      return `<section class="cg-accordion cg-raised ${idx === 0 ? 'is-open' : ''}"><button class="cg-acc-head" type="button" aria-expanded="${idx === 0 ? 'true' : 'false'}"><span>${esc(cat)}</span><span class="cg-acc-count">${rows.length}</span></button><div class="cg-acc-panel"><div class="cg-table-wrap"><table class="cg-table"><thead><tr><th>${esc(i18n.providerAws || '')}</th><th>${esc(i18n.providerAzure || '')}</th><th>${esc(i18n.providerGcp || '')}</th><th>${esc(i18n.providerGeneric || '')}</th></tr></thead><tbody>${desktopRows}</tbody></table></div><div class="cg-mobile">${mobile}</div></div></section>`;
    }).join('') || `<div class="cg-empty cg-raised">${esc(i18n.error || '')}</div>`;

    root.querySelectorAll('.cg-acc-head').forEach((btn) => {
      btn.addEventListener('click', () => {
        const sec = btn.closest('.cg-accordion');
        const open = sec.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
  };

  skeleton();
  fetch(root.dataset.endpoint, { credentials: 'same-origin' })
    .then((r) => r.json())
    .then((services) => {
      const all = Array.isArray(services) ? services : [];
      render(all);
      search?.addEventListener('input', debounce((e) => render(all, e.target.value), 300));
    })
    .catch(() => { root.innerHTML = `<div class="cg-error cg-raised">${esc(i18n.error || '')}</div>`; });
})();
