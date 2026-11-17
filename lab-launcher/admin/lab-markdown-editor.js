(function ($) {
    'use strict';

    function debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function insertAtCursor(textarea, text) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const value = textarea.value;
        textarea.value = value.substring(0, start) + text + value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + text.length;
        textarea.focus();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function initMarkdownPreviewCopyButtons(root) {
        const container = root || document;
        container.querySelectorAll('.lab-md-preview pre').forEach(function (pre) {
            if (pre.closest('.lab-code-block-wrapper')) {
                return;
            }

            const codeEl = pre.querySelector('code');
            const wrapper = document.createElement('div');
            wrapper.className = 'lab-code-block-wrapper';

            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lab-code-copy-btn';
            btn.title = 'Parancs másolása';
            btn.setAttribute('aria-label', 'Parancs másolása');
            btn.textContent = 'Másolás';

            const feedback = document.createElement('span');
            feedback.className = 'copy-feedback lab-code-copy-feedback';
            feedback.textContent = 'Másolva!';

            wrapper.appendChild(btn);
            wrapper.appendChild(feedback);

            btn.addEventListener('click', function () {
                const text = ((codeEl ? codeEl.textContent : pre.textContent) || '').trim();
                navigator.clipboard.writeText(text).then(function () {
                    feedback.style.display = 'inline';
                    window.setTimeout(function () {
                        feedback.style.display = 'none';
                    }, 1500);
                }).catch(function () {
                    // No-op: silently ignore copy failures.
                });
            });
        });
    }

    function initMarkdownEditor() {
        const $editor = $('.lab-md-editor');
        if (!$editor.length) {
            return;
        }

        const $textarea = $editor.find('.lab-md-textarea');
        const $preview = $editor.find('.lab-md-preview');
        const $tabs = $editor.find('.lab-md-tab');
        const previewUrl = $editor.data('preview-url');
        let previewRequest = null;

        const updatePreview = debounce(function () {
            const markdown = $textarea.val();

            if (!markdown.trim()) {
                $preview.html('<p class="lab-md-preview-loading">Nincs tartalom.</p>');
                return;
            }

            $preview.html('<p class="lab-md-preview-loading">Előnézet betöltése...</p>');

            if (previewRequest) {
                previewRequest.abort();
            }

            previewRequest = $.ajax({
                url: previewUrl,
                method: 'POST',
                contentType: 'application/json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', $editor.data('nonce'));
                },
                data: JSON.stringify({ markdown: markdown }),
            })
                .done(function (response) {
                    $preview.html(response.html || '');
                    initMarkdownPreviewCopyButtons($preview.get(0));
                })
                .fail(function () {
                    $preview.html('<p class="lab-md-preview-loading">Az előnézet betöltése sikertelen.</p>');
                })
                .always(function () {
                    previewRequest = null;
                });
        }, 350);

        $tabs.on('click', function () {
            const mode = $(this).data('mode');

            $tabs.removeClass('is-active');
            $(this).addClass('is-active');

            if (mode === 'code') {
                $textarea.show();
                $preview.removeClass('is-visible');
            } else {
                $textarea.hide();
                $preview.addClass('is-visible');
                updatePreview();
            }
        });

        $textarea.on('input', function () {
            if ($preview.hasClass('is-visible')) {
                updatePreview();
            }
        });

        $editor.find('.lab-md-insert-image').on('click', function (e) {
            e.preventDefault();

            const frame = wp.media({
                title: 'Kép kiválasztása a leíráshoz',
                button: { text: 'Beszúrás' },
                multiple: false,
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                const alt = attachment.alt || attachment.title || '';
                const width = attachment.width ? Math.min(attachment.width, 800) : 800;
                const markdown = `![${alt}](${attachment.url}){width=${width}px}`;
                insertAtCursor($textarea.get(0), `\n${markdown}\n`);
            });

            frame.open();
        });

        $editor.find('.lab-md-insert-pagebreak').on('click', function (e) {
            e.preventDefault();
            insertAtCursor($textarea.get(0), '\n<!-- pagebreak -->\n');
        });
    }

    function initDescriptionModeToggle() {
        const $checkbox = $('#use_markdown_description');
        const $htmlEditor = $('.lab-description-html-editor');
        const $mdEditor = $('.lab-description-md-editor');

        if (!$checkbox.length) {
            return;
        }

        function updateVisibility() {
            const useMarkdown = $checkbox.is(':checked');
            $htmlEditor.toggleClass('is-hidden', useMarkdown);
            $mdEditor.toggleClass('is-hidden', !useMarkdown);
        }

        $checkbox.on('change', updateVisibility);
        updateVisibility();
    }

    function initSharedPartSelect() {
        const root = document.querySelector('.lab-shared-select');
        if (!root) {
            return;
        }

        const toggle = root.querySelector('.lab-shared-select-toggle');
        const label = root.querySelector('.lab-shared-select-label');
        const menu = root.querySelector('.lab-shared-select-menu');
        const input = root.querySelector('input[name="shared_part_id"]');
        const options = Array.from(menu.querySelectorAll('[role="option"]'));

        function closeMenu() {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            options.forEach(function (option) {
                option.classList.remove('is-active');
            });
        }

        function openMenu() {
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            const selected = menu.querySelector('[aria-selected="true"]') || options[0];
            if (selected) {
                selected.classList.add('is-active');
                selected.focus();
            }
        }

        function choose(option) {
            options.forEach(function (item) {
                item.setAttribute('aria-selected', item === option ? 'true' : 'false');
            });
            input.value = option.getAttribute('data-value') || '';
            label.textContent = option.textContent;
            closeMenu();
            toggle.focus();
        }

        toggle.addEventListener('click', function () {
            if (menu.hidden) {
                openMenu();
            } else {
                closeMenu();
            }
        });

        options.forEach(function (option) {
            option.addEventListener('click', function () {
                choose(option);
            });
        });

        menu.addEventListener('keydown', function (event) {
            const current = menu.querySelector('.is-active') || menu.querySelector('[aria-selected="true"]');
            const index = Math.max(0, options.indexOf(current));

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const next = event.key === 'ArrowDown'
                    ? Math.min(options.length - 1, index + 1)
                    : Math.max(0, index - 1);
                options.forEach(function (option) {
                    option.classList.remove('is-active');
                });
                options[next].classList.add('is-active');
                options[next].focus();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (current) {
                    choose(current);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeMenu();
                toggle.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closeMenu();
            }
        });

        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openMenu();
            }
        });
    }

    $(document).ready(function () {
        initMarkdownEditor();
        initDescriptionModeToggle();
        initSharedPartSelect();
    });
})(jQuery);
