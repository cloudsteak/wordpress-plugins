(() => {
  const cfg = window.cgGlossary || {};
  const i18n = cfg.i18n || {};
  const key = cfg.themeStorageKey || 'cg-theme';
  const root = document.getElementById('cg-root');
  if (!root) return;

  const wrapper = root.closest('.cg-glossary-wrapper');
  const search = document.getElementById('cg-search');
  const toggle = document.getElementById('cg-theme-toggle');
  let allServices = [];
  let serviceById = {};

  const debounce = (fn, ms) => {
    let t;
    return (...a) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...a), ms);
    };
  };

  const esc = (s) => String(s ?? '').replace(/[&<>\"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '\"': '&quot;' }[c]));
  const groupBy = (arr, fn) => arr.reduce((m, x) => ((m[fn(x)] ||= []).push(x), m), {});

  const providerLabel = (slug) => {
    if (slug === 'aws') return i18n.providerAws || 'AWS';
    if (slug === 'azure') return i18n.providerAzure || 'Azure';
    if (slug === 'gcp') return i18n.providerGcp || 'GCP';
    return i18n.providerGeneric || '';
  };

  const applyTheme = (theme) => {
    wrapper?.setAttribute('data-theme', theme);
    if (toggle) toggle.textContent = theme === 'dark' ? (i18n.light || '') : (i18n.dark || '');
  };

  applyTheme(localStorage.getItem(key) || 'light');

  toggle?.addEventListener('click', () => {
    const next = (wrapper?.getAttribute('data-theme') || 'light') === 'light' ? 'dark' : 'light';
    localStorage.setItem(key, next);
    applyTheme(next);
  });

  if (search) search.placeholder = i18n.searchPlaceholder || '';

  const skeleton = () => {
    root.innerHTML = '<div class="cg-skeleton cg-raised"></div><div class="cg-skeleton cg-raised"></div><div class="cg-skeleton cg-raised"></div>';
  };

  const postsHtml = (posts) => {
    if (!posts?.length) return `<li class="cg-muted">${esc(i18n.noPosts || '')}</li>`;
    return posts.slice(0, 4).map((p) => `<li><a href="${esc(p.url)}">${esc(p.title)}</a></li>`).join('');
  };

  const providerCell = (service, providerSlug) => {
    const p = service.providers?.[providerSlug] || {};
    if (!p.name) return '<td class="cg-raised"><div class="cg-cell"><div class="cg-muted">-</div></div></td>';

    return `<td class="cg-raised"><div class="cg-cell"><div class="cg-cell-top"><span class="cg-icon cg-icon--${esc(providerSlug)}"></span><span class="cg-cell-name">${esc(p.name)}</span><button type="button" class="cg-info" data-id="${service.id}" data-provider="${esc(providerSlug)}" aria-label="${esc(i18n.info || '')}">ⓘ</button></div><div class="cg-cell-bottom"><ul class="cg-posts">${postsHtml(p.related_posts)}</ul></div></div></td>`;
  };

  const genericCell = (service) => {
    return `<td class="cg-raised"><div class="cg-cell"><div class="cg-cell-top"><span class="cg-cell-name">${esc(service.title)}</span></div><div class="cg-cell-bottom"><div class="cg-muted">${esc(service.description || '')}</div></div></div></td>`;
  };

  const ensureModal = () => {
    let modal = document.getElementById('cg-service-modal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'cg-service-modal';
    modal.className = 'cg-modal';
    modal.hidden = true;
    modal.innerHTML = '<div class="cg-modal__backdrop" data-close="1"></div><div class="cg-modal__dialog cg-raised" role="dialog" aria-modal="true" aria-labelledby="cg-modal-title"><button type="button" class="cg-modal__close" data-close="1">×</button><div class="cg-modal__content"></div></div>';
    document.body.appendChild(modal);

    modal.addEventListener('click', (e) => {
      if (e.target.closest('[data-close="1"]')) modal.hidden = true;
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !modal.hidden) modal.hidden = true;
    });

    return modal;
  };

  const openModal = (serviceId, providerSlug) => {
    const service = serviceById[Number(serviceId)];
    if (!service) return;

    const provider = service.providers?.[providerSlug] || {};
    if (!provider.name) return;

    const modal = ensureModal();
    const content = modal.querySelector('.cg-modal__content');
    const docsHtml = provider.official_docs_url
      ? `<p><a class="cg-docs-link" href="${esc(provider.official_docs_url)}" target="_blank" rel="noopener noreferrer">${esc(i18n.openDocs || '')}</a></p>`
      : '';

    content.innerHTML = `<h3 id="cg-modal-title">${esc(provider.name)}</h3><p class="cg-muted">${esc(providerLabel(providerSlug))} · ${esc(service.title)}</p><p>${esc(provider.short_description || i18n.noDescription || '')}</p>${docsHtml}`;
    modal.hidden = false;
  };

  const render = (services, q = '') => {
    const term = q.trim().toLocaleLowerCase('hu-HU');
    const filtered = !term
      ? services
      : services.filter((s) => {
          const text = [
            s.title,
            s.description,
            s.providers?.aws?.name,
            s.providers?.aws?.short_description,
            s.providers?.azure?.name,
            s.providers?.azure?.short_description,
            s.providers?.gcp?.name,
            s.providers?.gcp?.short_description,
          ].join(' ').toLocaleLowerCase('hu-HU');
          return text.includes(term);
        });

    const byCat = groupBy(filtered, (s) => s.category || 'egyeb');
    const cats = Object.keys(byCat).sort((a, b) => a.localeCompare(b));

    root.innerHTML = cats
      .map((cat, idx) => {
        const rows = (byCat[cat] || []).sort((a, b) => (a.order ?? 0) - (b.order ?? 0) || a.title.localeCompare(b.title));
        const desktopRows = rows
          .map((s) => `<tr>${providerCell(s, 'aws')}${providerCell(s, 'azure')}${providerCell(s, 'gcp')}${genericCell(s)}</tr>`)
          .join('');

        const mobile = rows
          .map((s) => {
            return ['aws', 'azure', 'gcp']
              .map((p) => {
                const cellHtml = providerCell(s, p);
                return cellHtml.includes('cg-muted">-') ? '' : `<article class="cg-mobile-card cg-raised">${cellHtml.replace(/^<td class="cg-raised">|<\/td>$/g, '')}</article>`;
              })
              .join('') + `<article class="cg-mobile-card cg-raised">${genericCell(s).replace(/^<td class="cg-raised">|<\/td>$/g, '')}</article>`;
          })
          .join('');

        return `<section class="cg-accordion cg-raised ${idx === 0 ? 'is-open' : ''}"><button class="cg-acc-head" type="button" aria-expanded="${idx === 0 ? 'true' : 'false'}"><span>${esc(cat)}</span><span class="cg-acc-count">${rows.length}</span></button><div class="cg-acc-panel"><div class="cg-table-wrap"><table class="cg-table"><thead><tr><th>${esc(i18n.providerAws || '')}</th><th>${esc(i18n.providerAzure || '')}</th><th>${esc(i18n.providerGcp || '')}</th><th>${esc(i18n.genericTerm || '')}</th></tr></thead><tbody>${desktopRows}</tbody></table></div><div class="cg-mobile">${mobile}</div></div></section>`;
      })
      .join('') || `<div class="cg-empty cg-raised">${esc(i18n.error || '')}</div>`;

    root.querySelectorAll('.cg-acc-head').forEach((btn) => {
      btn.addEventListener('click', () => {
        const sec = btn.closest('.cg-accordion');
        const open = sec.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
  };

  root.addEventListener('click', (e) => {
    const button = e.target.closest('.cg-info');
    if (!button) return;
    openModal(button.dataset.id, button.dataset.provider);
  });

  skeleton();
  fetch(root.dataset.endpoint, { credentials: 'same-origin' })
    .then((r) => r.json())
    .then((services) => {
      allServices = Array.isArray(services) ? services : [];
      serviceById = Object.fromEntries(allServices.map((s) => [Number(s.id), s]));
      render(allServices);
      search?.addEventListener('input', debounce((e) => render(allServices, e.target.value), 300));
    })
    .catch(() => {
      root.innerHTML = `<div class="cg-error cg-raised">${esc(i18n.error || '')}</div>`;
    });
})();
