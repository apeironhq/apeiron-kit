(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        root: '[data-apeiron-social-proof-entries]',
        legacyRoot: '.apeiron-social-proof-popup[data-entries]',
        image: '.apeiron-social-proof__image, .apeiron-popup-image',
        name: '.apeiron-social-proof__name, .apeiron-popup-name',
        description: '.apeiron-social-proof__description, .apeiron-popup-desc',
        date: '.apeiron-social-proof__date, .apeiron-popup-date',
        close: '.apeiron-social-proof__close, .apeiron-popup-close',
        widget: '.elementor-widget-apeiron-social-proof',
    });
    var CLASS = Object.freeze({
        visible: 'is-visible',
        legacyVisible: 'show',
        placeholder: 'is-placeholder',
        hasImage: 'has-image',
        product: 'apeiron-social-proof__product apeiron-popup-product',
        placeholderIcon: 'apeiron-social-proof__placeholder-icon apeiron-popup-placeholder-icon',
    });
    var DEFAULTS = Object.freeze({
        textTemplate: '{name} telah membeli {product} pada:',
        displayDuration: 3000,
        intervalDuration: 8000,
        initialDelay: 0,
        maxNotifications: 0,
        animationDuration: 400,
    });
    var PLACEHOLDER_ICON =
        '<svg class="' + CLASS.placeholderIcon + '" width="24" height="24" viewBox="0 0 24 24" fill="none"' +
        ' stroke="currentColor" stroke-width="1.5" aria-hidden="true">' +
        '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';

    var states = new WeakMap();
    var tracked = [];
    // Prevent overlapping notifications from multiple widget instances.
    var activeElement = null;

    function prefersReducedMotion() {
        return typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function getAttribute(element, name, legacyName) {
        var value = element.getAttribute(name);
        if (value === null && legacyName) {
            value = element.getAttribute(legacyName);
        }
        return value;
    }

    function readInt(element, name, legacyName, fallback, minimum) {
        var value = parseInt(getAttribute(element, name, legacyName), 10);
        if (Number.isNaN(value)) {
            return fallback;
        }
        return Math.max(typeof minimum === 'number' ? minimum : 0, value);
    }

    function clearTimers(state) {
        if (!state || !state.timers) {
            return;
        }

        window.clearTimeout(state.timers.next);
        window.clearTimeout(state.timers.show);
        window.clearTimeout(state.timers.hide);
        state.timers.next = null;
        state.timers.show = null;
        state.timers.hide = null;
    }

    function untrack(root) {
        var index = tracked.indexOf(root);
        if (index !== -1) {
            tracked.splice(index, 1);
        }
    }

    function hide(root) {
        root.classList.remove(CLASS.visible);
        root.classList.remove(CLASS.legacyVisible);
        if (activeElement === root) {
            activeElement = null;
        }
    }

    function destroy(root) {
        var state = states.get(root);
        clearTimers(state);

        if (state && state.listeners) {
            state.listeners.forEach(function (entry) {
                entry.target.removeEventListener(entry.type, entry.handler);
            });
        }

        states.delete(root);
        untrack(root);
        hide(root);
    }

    // Stop timers and listeners for roots replaced by Elementor.
    function pruneDetached() {
        tracked.slice().forEach(function (root) {
            if (!root.isConnected) {
                destroy(root);
            }
        });

        if (activeElement && !activeElement.isConnected) {
            activeElement = null;
        }
    }

    function on(state, target, type, handler, options) {
        target.addEventListener(type, handler, options);
        state.listeners.push({ target: target, type: type, handler: handler });
    }

    // Visitor session limits must not hide Elementor previews.
    function isEditorContext() {
        if (document.body && document.body.classList.contains('elementor-editor-active')) {
            return true;
        }

        if (
            typeof elementorFrontend !== 'undefined' &&
            typeof elementorFrontend.isEditMode === 'function' &&
            elementorFrontend.isEditMode()
        ) {
            return true;
        }

        return /(?:^|[?&])elementor-preview=/.test((window.location && window.location.search) || '');
    }

    function getSessionValue(key) {
        if (isEditorContext()) {
            return null;
        }

        try {
            return window.sessionStorage ? window.sessionStorage.getItem('apeiron_sp_' + key) : null;
        } catch (error) {
            return null;
        }
    }

    function setSessionValue(key, value) {
        if (isEditorContext()) {
            return;
        }

        try {
            if (window.sessionStorage) {
                window.sessionStorage.setItem('apeiron_sp_' + key, value);
            }
        } catch (error) {
        }
    }

    function removeSessionValue(key) {
        if (isEditorContext()) {
            return;
        }

        try {
            if (window.sessionStorage) {
                window.sessionStorage.removeItem('apeiron_sp_' + key);
            }
        } catch (error) {
        }
    }

    function getSessionCount(instanceId) {
        var value = parseInt(getSessionValue('shown:' + instanceId), 10);
        return Number.isNaN(value) ? 0 : value;
    }

    function hasReachedSessionLimit(instanceId, maxNotifications) {
        return maxNotifications > 0 && getSessionCount(instanceId) >= maxNotifications;
    }

    function getElementKey(root) {
        if (!root.id) {
            root.id = 'apeiron-social-proof-' + Math.random().toString(36).slice(2);
        }
        return root.id;
    }

    function removeNamePlaceholder(template) {
        return String(template || '')
            .replace(/\{name\}/g, '')
            .replace(/^[\s,]+/, '');
    }

    function renderTemplate(container, template, entry) {
        var parts = String(template || '').split(/(\{name\}|\{product\})/g);
        container.textContent = '';

        parts.forEach(function (part) {
            if (part === '{name}' || part === '{product}') {
                var valueNode = document.createElement('span');
                valueNode.className = part === '{product}' ? CLASS.product : 'apeiron-social-proof__inline-name';
                valueNode.textContent = part === '{product}' ? (entry.product || '') : (entry.name || '');
                container.appendChild(valueNode);
                return;
            }

            if (part !== '') {
                container.appendChild(document.createTextNode(part));
            }
        });
    }

    function renderPlaceholder(image) {
        image.classList.remove(CLASS.hasImage);
        image.classList.add(CLASS.placeholder);
        image.innerHTML = PLACEHOLDER_ICON;
    }

    function renderImage(image, entry) {
        image.textContent = '';

        if (!entry.image) {
            renderPlaceholder(image);
            return;
        }

        image.classList.remove(CLASS.placeholder);
        image.classList.add(CLASS.hasImage);

        var imageElement = document.createElement('img');
        imageElement.alt = entry.name || '';
        imageElement.decoding = 'async';
        imageElement.loading = 'lazy';
        imageElement.addEventListener('error', function () {
            // Ignore failures from an image replaced by a newer entry.
            if (imageElement.parentNode === image) {
                renderPlaceholder(image);
            }
        }, { once: true });
        imageElement.src = entry.image;
        image.appendChild(imageElement);
    }

    function getTimezone(date) {
        var offset = -date.getTimezoneOffset() / 60;
        if (offset >= 7 && offset < 8) return 'WIB';
        if (offset >= 8 && offset < 9) return 'WITA';
        if (offset >= 9 && offset < 10) return 'WIT';
        return 'UTC' + (offset >= 0 ? '+' : '') + offset;
    }

    function formatDate(dateString) {
        if (!dateString) {
            return '-';
        }

        var date = new Date(dateString);
        if (Number.isNaN(date.getTime())) {
            return dateString;
        }

        try {
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }) + ' (' + getTimezone(date) + ')';
        } catch (error) {
            return dateString;
        }
    }

    function paint(state, entry) {
        renderImage(state.popupImage, entry);
        var leadingName = /^\s*\{name\}/.test(state.textTemplate);
        state.popupName.hidden = !leadingName;
        state.popupName.textContent = leadingName ? (entry.name || '') : '';
        renderTemplate(
            state.popupDescription,
            leadingName ? state.textTemplate.replace(/^\s*\{name\}/, '').replace(/^[\s,]+/, '') : state.textTemplate,
            entry
        );
        state.popupDate.textContent = formatDate(entry.datetime || '');
    }

    function scheduleNext(root, delay) {
        var state = states.get(root);
        if (!state) {
            return;
        }

        window.clearTimeout(state.timers.next);
        state.timers.next = window.setTimeout(function () {
            showNextEntry(root);
        }, Math.max(0, delay || 0));
    }

    // Elementor may replace a root while its timer is pending.
    function isStale(root, state) {
        if (states.get(root) !== state) {
            return true;
        }
        if (!root.isConnected) {
            destroy(root);
            return true;
        }
        return false;
    }

    function isPaused(state) {
        return state.hovered || state.focusWithin || document.hidden;
    }

    function beginHideCycle(root, state, delay) {
        var wait = typeof delay === 'number' ? delay : state.displayDuration;
        state.displayDeadline = Date.now() + wait;
        state.timers.show = window.setTimeout(function () {
            if (isStale(root, state)) {
                return;
            }

            if (isPaused(state)) {
                beginHideCycle(root, state, 100);
                return;
            }

            root.classList.remove(CLASS.visible);
            root.classList.remove(CLASS.legacyVisible);

            state.timers.hide = window.setTimeout(function () {
                if (isStale(root, state)) {
                    return;
                }

                if (activeElement === root) {
                    activeElement = null;
                }
                state.currentIndex += 1;
                scheduleNext(root, state.intervalDuration);
            }, state.animationDuration);
        }, wait);
    }

    function showNextEntry(root) {
        var state = states.get(root);
        if (!state || isStale(root, state)) {
            return;
        }

        pruneDetached();

        if (hasReachedSessionLimit(state.instanceId, state.maxNotifications)) {
            destroy(root);
            return;
        }

        if (document.hidden) {
            state.waitingForVisibility = true;
            return;
        }

        if (activeElement && activeElement !== root) {
            scheduleNext(root, 500);
            return;
        }

        if (state.currentIndex >= state.entries.length) {
            state.currentIndex = 0;
        }

        paint(state, state.entries[state.currentIndex]);

        activeElement = root;
        // Flush the start state before applying the visible class.
        void root.offsetHeight;
        root.classList.add(CLASS.visible);
        root.classList.add(CLASS.legacyVisible);
        if (state.maxNotifications > 0) {
            setSessionValue('shown:' + state.instanceId, String(getSessionCount(state.instanceId) + 1));
        }

        beginHideCycle(root, state);
    }

    function parseEntries(root) {
        var rawEntries = getAttribute(root, 'data-apeiron-social-proof-entries', 'data-entries');
        try {
            var entries = JSON.parse(rawEntries || '[]');
            return Array.isArray(entries) ? entries.filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    }

    function initializeRoot(root) {
        if (states.has(root)) {
            return;
        }

        var entries = parseEntries(root);
        if (entries.length === 0) {
            return;
        }

        var popupImage = root.querySelector(SELECTOR.image);
        var popupName = root.querySelector(SELECTOR.name);
        var popupDescription = root.querySelector(SELECTOR.description);
        var popupDate = root.querySelector(SELECTOR.date);
        var closeButton = root.querySelector(SELECTOR.close);

        if (!popupImage || !popupName || !popupDescription || !popupDate) {
            return;
        }

        var textTemplate =
            getAttribute(root, 'data-apeiron-social-proof-text-template', 'data-text-template') ||
            DEFAULTS.textTemplate;
        var state = {
            entries: entries,
            instanceId:
                getAttribute(root, 'data-apeiron-social-proof-instance-id', 'data-instance-id') ||
                getElementKey(root),
            textTemplate: textTemplate,
            displayDuration: readInt(root, 'data-apeiron-social-proof-display-duration', 'data-display-duration', DEFAULTS.displayDuration, 1000),
            intervalDuration: readInt(root, 'data-apeiron-social-proof-interval-duration', 'data-interval-duration', DEFAULTS.intervalDuration, 1000),
            maxNotifications: readInt(root, 'data-apeiron-social-proof-max-notifications', 'data-max-notifications', DEFAULTS.maxNotifications, 0),
            animationDuration: readInt(root, 'data-apeiron-social-proof-animation-duration', null, DEFAULTS.animationDuration, 100),
            currentIndex: 0,
            hovered: false,
            focusWithin: false,
            waitingForVisibility: false,
            visibilityRemaining: null,
            displayDeadline: 0,
            popupImage: popupImage,
            popupName: popupName,
            popupDescription: popupDescription,
            popupDate: popupDate,
            timers: { next: null, show: null, hide: null },
            listeners: [],
        };

        if (prefersReducedMotion()) {
            state.animationDuration = 1;
        }

        states.set(root, state);
        tracked.push(root);

        // Remove session keys retained from older releases.
        removeSessionValue('dismissed:' + state.instanceId);
        if (state.maxNotifications === 0) {
            removeSessionValue('shown:' + state.instanceId);
        }

        if (closeButton) {
            on(state, closeButton, 'click', function (event) {
                event.preventDefault();
                if (isEditorContext()) {
                    return;
                }
                clearTimers(state);
                hide(root);
                state.currentIndex = (state.currentIndex + 1) % state.entries.length;
                scheduleNext(root, state.intervalDuration);
            });
        }


        on(state, root, 'pointerenter', function () { state.hovered = true; });
        on(state, root, 'pointerleave', function () { state.hovered = false; });
        on(state, root, 'focusin', function () { state.focusWithin = true; });
        on(state, root, 'focusout', function (event) {
            state.focusWithin = !!(event.relatedTarget && root.contains(event.relatedTarget));
        });
        on(state, document, 'visibilitychange', function () {
            if (document.hidden && root.classList.contains(CLASS.visible)) {
                state.visibilityRemaining = Math.max( 100, state.displayDeadline - Date.now() );
                window.clearTimeout(state.timers.show);
                state.timers.show = null;
                return;
            }

            if (!document.hidden && state.visibilityRemaining !== null) {
                var remaining = state.visibilityRemaining;
                state.visibilityRemaining = null;
                beginHideCycle(root, state, remaining);
            } else if (!document.hidden && state.waitingForVisibility) {
                state.waitingForVisibility = false;
                scheduleNext(root, 0);
            }
        });

        if (isEditorContext()) {
            paint(state, entries[0]);
            activeElement = root;
            root.classList.add(CLASS.visible);
            root.classList.add(CLASS.legacyVisible);
            return;
        }

        if (hasReachedSessionLimit(state.instanceId, state.maxNotifications)) {
            destroy(root);
            return;
        }

        var initialDelay = readInt(root, 'data-apeiron-social-proof-initial-delay', 'data-initial-delay', DEFAULTS.initialDelay, 0);
        state.timers.next = window.setTimeout(function () {
            showNextEntry(root);
        }, initialDelay);
    }

    function findRoots(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container) {
            return [];
        }

        var roots = [];
        var collect = function (node) {
            if (!node || !node.querySelectorAll) {
                return;
            }
            Array.prototype.forEach.call(node.querySelectorAll(SELECTOR.root), function (found) {
                if (roots.indexOf(found) === -1) {
                    roots.push(found);
                }
            });
            Array.prototype.forEach.call(node.querySelectorAll(SELECTOR.legacyRoot), function (found) {
                if (roots.indexOf(found) === -1) {
                    roots.push(found);
                }
            });
        };

        collect(container);

        if (container.matches && (container.matches(SELECTOR.root) || container.matches(SELECTOR.legacyRoot))) {
            if (roots.indexOf(container) === -1) {
                roots.unshift(container);
            }
        }

        if (roots.length === 0 && container.closest) {
            collect(container.closest(SELECTOR.widget));
        }

        return roots;
    }

    function initializeScope(scope) {
        pruneDetached();
        findRoots(scope).forEach(initializeRoot);
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
            'frontend/element_ready/apeiron-social-proof.default',
            initializeScope
        );
    }

    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();

    if (window.ApeironSocialProofTestMode) {
        window.ApeironSocialProofTestApi = Object.freeze({
            destroy: destroy,
            formatDate: formatDate,
            initializeRoot: initializeRoot,
            removeNamePlaceholder: removeNamePlaceholder,
            pruneDetached: pruneDetached,
        });
    }
}());
