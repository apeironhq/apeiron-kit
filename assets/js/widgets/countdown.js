// Server offset avoids client clock drift; WeakMap state supports safe Elementor re-renders.
(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        root: '[data-apeiron-countdown]',
        part: '[data-apeiron-countdown-part]',
        expiredMessage: '.apeiron-kit-countdown__expired',
        widget: '.elementor-widget-apeiron-countdown, .elementor-widget-apeiron-pulse-countdown',
    });
    var UNIT_SECONDS = Object.freeze({
        days: 86400,
        hours: 3600,
        minutes: 60,
        seconds: 1,
    });
    var DIGIT_PADDING_MIN = 2;
    var DIGIT_PADDING_MAX = 4;
    var states = new WeakMap();

    function clampDigitPadding(raw) {
        var value = parseInt(raw, 10);
        if (!Number.isFinite(value)) {
            return DIGIT_PADDING_MIN;
        }
        if (value < DIGIT_PADDING_MIN) return DIGIT_PADDING_MIN;
        if (value > DIGIT_PADDING_MAX) return DIGIT_PADDING_MAX;
        return value;
    }

    function calculateParts(milliseconds, visibleParts) {
        var remainingSeconds = Math.max(Math.ceil(milliseconds / 1000), 0);
        var values = {};

        visibleParts.forEach(function (part) {
            var unitSeconds = UNIT_SECONDS[part];
            if (!unitSeconds) {
                return;
            }

            values[part] = Math.floor(remainingSeconds / unitSeconds);
            remainingSeconds %= unitSeconds;
        });

        return values;
    }

    function destroy(root) {
        var state = states.get(root);
        if (state && state.timerId !== null) {
            clearTimeout(state.timerId);
        }

        states.delete(root);
        root.classList.remove('is-expired');

        var expiredMessage = root.querySelector(SELECTOR.expiredMessage);
        if (expiredMessage) {
            expiredMessage.hidden = true;
        }
    }

    function expire(root, state) {
        if (state.timerId !== null) {
            clearTimeout(state.timerId);
            state.timerId = null;
        }

        root.classList.add('is-expired');

        var expiredMessage = root.querySelector(SELECTOR.expiredMessage);
        if (expiredMessage) {
            expiredMessage.hidden = false;
        }
    }

    function tick(root) {
        var state = states.get(root);
        if (!state) {
            return;
        }

        if (!root.isConnected) {
            destroy(root);
            return;
        }

        var now = Date.now() + state.serverOffset;
        var remaining = state.targetTimestamp - now;
        var values = calculateParts(remaining, state.visibleParts);
        var digitPadding = state.digitPadding;

        state.partElements.forEach(function (element, part) {
            element.textContent = String(values[part] || 0).padStart(digitPadding, '0');
        });

        if (remaining <= 0) {
            expire(root, state);
            return;
        }

        state.timerId = window.setTimeout(function () {
            tick(root);
        }, Math.min(1000, remaining));
    }

    function initializeRoot(root) {
        destroy(root);

        var target = root.getAttribute('data-apeiron-countdown');
        var targetTimestamp = Number(target);
        if (!target || !Number.isFinite(targetTimestamp)) {
            return;
        }

        var serverNowAttr = root.getAttribute('data-apeiron-server-now');
        var serverNow = Number(serverNowAttr);
        var serverOffset = (serverNowAttr && Number.isFinite(serverNow))
            ? serverNow - Date.now()
            : 0;

        var digitPadding = clampDigitPadding(root.getAttribute('data-apeiron-digit-padding'));

        var partElements = new Map();
        root.querySelectorAll(SELECTOR.part).forEach(function (element) {
            var part = element.getAttribute('data-apeiron-countdown-part');
            if (Object.prototype.hasOwnProperty.call(UNIT_SECONDS, part)) {
                partElements.set(part, element);
            }
        });

        if (partElements.size === 0) {
            return;
        }

        states.set(root, {
            timerId: null,
            partElements: partElements,
            targetTimestamp: targetTimestamp,
            serverOffset: serverOffset,
            digitPadding: digitPadding,
            visibleParts: Array.from(partElements.keys()),
        });

        tick(root);
    }

    function findRoots(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container) {
            return [];
        }

        var roots = container.querySelectorAll
            ? Array.from(container.querySelectorAll(SELECTOR.root))
            : [];

        if (container.matches && container.matches(SELECTOR.root)) {
            roots.unshift(container);
        }

        if (roots.length === 0 && container.closest) {
            var widget = container.closest(SELECTOR.widget);
            return widget ? Array.from(widget.querySelectorAll(SELECTOR.root)) : [];
        }

        return roots;
    }

    function initializeScope(scope) {
        findRoots(scope).forEach(initializeRoot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initializeScope(document);
        }, { once: true });
    } else {
        initializeScope(document);
    }

    window.addEventListener('elementor/frontend/init', function () {
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-countdown.default',
            initializeScope
        );

        // Keep saved legacy widgets initializing.
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-pulse-countdown.default',
            initializeScope
        );
    });

    if (window.ApeironCountdownTestMode) {
        window.ApeironCountdownTestApi = Object.freeze({
            calculateParts: calculateParts,
            clampDigitPadding: clampDigitPadding,
            destroy: destroy,
            initializeRoot: initializeRoot,
            tick: tick,
        });
    }
}());
