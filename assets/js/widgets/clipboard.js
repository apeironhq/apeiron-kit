(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        button: '.apeiron-kit-clipboard-tap[data-apeiron-clipboard], .apeiron-kit-clipboard-tap[data-copy-source="current_url"]',
        widget: '.elementor-widget-apeiron-clipboard-tap',
        text: '.apeiron-kit-clipboard-tap__text',
        icon: '.apeiron-kit-clipboard-tap__icon',
        status: '.apeiron-kit-clipboard-tap__status',
    });
    var FEEDBACK_DURATION_MIN = 800;
    var FEEDBACK_DURATION_MAX = 5000;
    var FEEDBACK_DURATION_DEFAULT = 1800;
    var FALLBACK_MESSAGE = Object.freeze({
        success: 'Berhasil disalin',
        empty: 'Tidak ada teks untuk disalin',
        error: 'Gagal menyalin. Silakan coba lagi',
        invalidUrl: 'URL tidak valid',
    });
    var states = new WeakMap();
    // Expando survives duplicate script execution but is not copied by
    // cloneNode(), so Elementor/Swiper clones still receive their own listener.
    var INIT_MARK = '__apeironClipboardInit';

    function clampFeedbackDuration(raw) {
        var value = parseInt(raw, 10);
        if (!Number.isFinite(value)) {
            return FEEDBACK_DURATION_DEFAULT;
        }
        if (value < FEEDBACK_DURATION_MIN) return FEEDBACK_DURATION_MIN;
        if (value > FEEDBACK_DURATION_MAX) return FEEDBACK_DURATION_MAX;
        return value;
    }

    function getCopyText(btn) {
        if (btn.getAttribute('data-copy-source') === 'current_url') {
            return window.location && window.location.href ? window.location.href : '';
        }

        return (btn.getAttribute('data-apeiron-clipboard') || '').trim();
    }

    function isValidUrl(value) {
        try {
            var url = new URL(value);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (err) {
            return false;
        }
    }

    function ensureState(btn) {
        var state = states.get(btn);
        if (!state) {
            state = { timerId: null, originalText: null, previousWidth: null, bound: false, pending: false };
            states.set(btn, state);
        }
        return state;
    }

    function clearTimer(state) {
        if (state.timerId !== null) {
            clearTimeout(state.timerId);
            state.timerId = null;
        }
    }

    function restoreButton(btn) {
        var state = states.get(btn);
        if (!state) {
            return;
        }

        clearTimer(state);

        var textEl = btn.querySelector(SELECTOR.text);
        var iconEl = btn.querySelector(SELECTOR.icon);

        if (state.originalText !== null && textEl) {
            textEl.textContent = state.originalText;
        }
        if (iconEl) {
            iconEl.hidden = false;
        }

        btn.classList.remove('is-copied', 'is-error');
        btn.style.width = state.previousWidth === null ? '' : state.previousWidth;

        state.originalText = null;
        state.previousWidth = null;
    }

    function destroy(btn) {
        restoreButton(btn);
        states.delete(btn);
    }

    // The live region exists from first paint because same-tick regions may not announce.
    function announce(btn, message) {
        var wrapper = btn.parentNode;
        var statusEl = wrapper && wrapper.querySelector ? wrapper.querySelector(SELECTOR.status) : null;
        if (statusEl) {
            statusEl.textContent = message;
        }
    }

    function showFeedback(btn, kind, message) {
        var state = ensureState(btn);
        var textEl = btn.querySelector(SELECTOR.text);
        var iconEl = btn.querySelector(SELECTOR.icon);

        if (state.originalText === null) {
            state.originalText = textEl ? textEl.textContent : '';
            state.previousWidth = btn.style.width;
        }

        clearTimer(state);

        // Preserve width while feedback replaces the label.
        var measuredWidth = btn.getBoundingClientRect ? Math.ceil(btn.getBoundingClientRect().width) : 0;
        if (measuredWidth > 0) {
            btn.style.width = measuredWidth + 'px';
        }

        if (textEl) {
            textEl.textContent = message;
        }
        if (iconEl) {
            iconEl.hidden = true;
        }

        btn.classList.toggle('is-copied', kind === 'success');
        btn.classList.toggle('is-error', kind === 'error');
        announce(btn, message);

        state.timerId = window.setTimeout(function () {
            if (!btn.isConnected) {
                destroy(btn);
                return;
            }
            restoreButton(btn);
            announce(btn, '');
        }, clampFeedbackDuration(btn.getAttribute('data-feedback-duration')));
    }

    function fallbackCopyText(text, onSuccess, onError) {
        var active = document.activeElement;
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.setAttribute('aria-hidden', 'true');
        textarea.style.position = 'fixed';
        textarea.style.top = '0';
        textarea.style.left = '0';
        textarea.style.width = '1px';
        textarea.style.height = '1px';
        textarea.style.padding = '0';
        textarea.style.border = 'none';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        document.body.appendChild(textarea);

        var copied = false;
        try {
            textarea.select();
            if (textarea.setSelectionRange) {
                textarea.setSelectionRange(0, text.length);
            }
            copied = document.execCommand && document.execCommand('copy');
        } catch (err) {
            copied = false;
        } finally {
            document.body.removeChild(textarea);
            // execCommand moves focus into the fallback textarea.
            if (active && active.focus) {
                active.focus();
            }
        }

        if (copied) {
            if (onSuccess) onSuccess();
        } else if (onError) {
            onError();
        }
    }

    function copyText(text, onSuccess, onError) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            try {
                Promise.resolve(navigator.clipboard.writeText(text)).then(onSuccess, function () {
                    fallbackCopyText(text, onSuccess, onError);
                });
            } catch (err) {
                fallbackCopyText(text, onSuccess, onError);
            }
            return;
        }

        fallbackCopyText(text, onSuccess, onError);
    }

    function handleClick(event) {
        var btn = event.currentTarget;
        var state = ensureState(btn);

        if (state.pending) {
            return;
        }

        var text = getCopyText(btn);

        if (!text) {
            showFeedback(btn, 'error', btn.getAttribute('data-empty-message') || FALLBACK_MESSAGE.empty);
            return;
        }

        if (btn.getAttribute('data-copy-source') === 'custom_url' && !isValidUrl(text)) {
            showFeedback(btn, 'error', btn.getAttribute('data-invalid-url-message') || FALLBACK_MESSAGE.invalidUrl);
            return;
        }

        var successMessage = btn.getAttribute('data-copy-message') || FALLBACK_MESSAGE.success;
        var errorMessage = btn.getAttribute('data-error-message') || FALLBACK_MESSAGE.error;

        // Elementor may detach the button before the async write settles.
        state.pending = true;
        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');
        copyText(text, function () {
            state.pending = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
            if (btn.isConnected) showFeedback(btn, 'success', successMessage);
        }, function () {
            state.pending = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
            if (btn.isConnected) showFeedback(btn, 'error', errorMessage);
        });
    }

    function initializeButton(btn) {
        if (btn[INIT_MARK]) {
            if (states.has(btn)) {
                restoreButton(btn);
            }
            return;
        }

        ensureState(btn).bound = true;
        btn[INIT_MARK] = true;
        btn.addEventListener('click', handleClick);
    }

    function findButtons(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container || !container.querySelectorAll) {
            return [];
        }

        var buttons = Array.prototype.slice.call(container.querySelectorAll(SELECTOR.button));

        if (container.matches && container.matches(SELECTOR.button)) {
            buttons.unshift(container);
        }

        return buttons;
    }

    function initializeScope(scope) {
        findButtons(scope).forEach(initializeButton);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initializeScope(document);
        }, { once: true });
    } else {
        initializeScope(document);
    }

    var elementorHookRegistered = false;
    function registerElementorHook() {
        if (elementorHookRegistered || typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }
        elementorHookRegistered = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-clipboard-tap.default',
            initializeScope
        );
    }

    // Elementor's init event may fire before this footer script loads.
    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();

    if (window.ApeironClipboardTestMode) {
        window.ApeironClipboardTestApi = Object.freeze({
            clampFeedbackDuration: clampFeedbackDuration,
            getCopyText: getCopyText,
            isValidUrl: isValidUrl,
            initializeButton: initializeButton,
            showFeedback: showFeedback,
            restoreButton: restoreButton,
            destroy: destroy,
            findButtons: findButtons,
        });
    }
}());
