/**
 * Root/config/state names and public instance methods are compatibility
 * contracts for saved Elementor documents and third-party integrations.
 */
(function () {
    'use strict';

    var CIRCUMFERENCE = 2 * Math.PI * 20; // radius = 20
    var END_THRESHOLD_PX = 2;
    var SPEED_MULTIPLIER = 3;
    var SPEED_BASE = 0.3;
    var BASE_FRAME_RATE = 60;
    var MAX_FRAME_DELTA_MS = 64;
    var MIN_VELOCITY = 0.02;
    var HEIGHT_REFRESH_FALLBACK_MS = 1000;
    var PROGRESS_UPDATE_MIN_DELTA = 0.001;
    var PRESS_ANIM_MS = 200;

    var SCROLL_TOP_DELAY_MS = 300;
    var SCROLL_TOP_PROGRESS_MS = 500;
    var SPEED_STEP = 5;
    var SPEED_VALUE_ANIM_MS = 220;
    var SPEED_ARROW_ACTIVE_MS = 160;
    var DEFAULT_IDLE_RESUME_MS = 1200;
    var STEP_SCROLL_INTERVAL_MS = 2000;
    var STEP_SCROLL_ANIMATION_MS = 1250;
    var STEP_SCROLL_RANGE_PX = 275;
    var STEP_SCROLL_BASE_SPEED = 30;
    var HOLD_THRESHOLD_MS = 200;
    var PROGRAMMATIC_SCROLL_TOLERANCE_PX = 2;
    var PROGRAMMATIC_SCROLL_WINDOW_MS = 120;
    var SCROLL_STATE_IDLE = 'idle';
    var SCROLL_STATE_RUNNING = 'running';
    var SCROLL_STATE_PAUSED = 'paused';
    var SCROLL_STATE_COMPLETED = 'completed';
    var SCROLL_STATE_STOPPED = 'stopped';
    var SCROLL_INTENT_KEYS = {
        ArrowDown: true,
        ArrowUp: true,
        PageDown: true,
        PageUp: true,
        Home: true,
        End: true,
        Space: true,
        ' ': true
    };
    var FORM_INTERACTION_SELECTOR = 'input, textarea, select, [contenteditable]:not([contenteditable="false"])';
    var INTERACTIVE_SELECTOR = 'a[href], button, input, textarea, select, label, summary, audio[controls], video[controls], [contenteditable]:not([contenteditable="false"]), [role="button"], [role="link"], [role="tab"], [role="checkbox"], [role="radio"], [role="switch"], [role="slider"], [role="combobox"], [role="menuitem"], [tabindex]:not([tabindex="-1"])';
    var activeAutoScrollInstance = null;
    var initializedContainers = [];

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function finiteNumber(value, fallback) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function enumValue(value, allowed, fallback) {
        return allowed.indexOf(value) >= 0 ? value : fallback;
    }

    function closestMatching(target, selector) {
        var element = target && target.nodeType === 1 ? target : (target && target.parentElement);
        return element && element.closest ? element.closest(selector) : null;
    }

    function isFormInteractionTarget(target) {
        return !!closestMatching(target, FORM_INTERACTION_SELECTOR);
    }

    function isInteractiveTarget(target) {
        return !!closestMatching(target, INTERACTIVE_SELECTOR);
    }

    function isPageScrollKeyEvent(event) {
        return !!event && !!SCROLL_INTENT_KEYS[event.key] && !event.defaultPrevented &&
            !event.altKey && !event.ctrlKey && !event.metaKey && !isFormInteractionTarget(event.target);
    }

    function normalizeConfig(rawConfig) {
        var config = rawConfig && typeof rawConfig === 'object' ? rawConfig : {};
        var normalized = Object.assign({}, config);

        normalized.scrollEngine = enumValue(config.scrollEngine, ['apeiron', 'step'], 'apeiron');
        normalized.mode = enumValue(config.mode, ['auto', 'manual', 'both'], 'auto');
        normalized.userInteractionBehavior = enumValue(config.userInteractionBehavior, ['continue', 'stop_on_interaction'], 'continue');
        normalized.direction = enumValue(config.direction, ['down', 'up'], 'down');
        normalized.smoothness = enumValue(config.smoothness, ['normal', 'smooth', 'ultra'], 'ultra');
        normalized.motionProfile = enumValue(config.motionProfile, ['legacy', 'steady', 'kinetic', 'hand'], 'steady');
        normalized.easing = enumValue(config.easing, ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'], 'linear');
        normalized.activeAnimation = enumValue(config.activeAnimation, ['none', 'pulse-soft', 'scale-breathing', 'micro-bounce', 'smooth-rotate', 'glow-ring', 'orbit-ring', 'ripple-wave'], 'pulse-soft');
        normalized.speedValueAnimation = enumValue(config.speedValueAnimation, ['none', 'pulse', 'fade', 'slide', 'bounce'], 'pulse');
        normalized.buttonAppearAnimation = enumValue(config.buttonAppearAnimation, ['none', 'fade', 'slide', 'scale', 'bounce', 'zoom', 'flip', 'elastic'], 'fade');

        normalized.speedControlAppearAnimation = enumValue(config.speedControlAppearAnimation, ['fade', 'slide', 'scale', 'bounce', 'zoom', 'flip', 'elastic', 'slide-up', 'slide-down'], 'scale');
        normalized.speedControlDisappearAnimation = enumValue(config.speedControlDisappearAnimation, ['fade', 'slide', 'scale', 'bounce', 'zoom', 'flip', 'elastic', 'slide-up', 'slide-down'], 'scale');
        normalized.progressAnimation = enumValue(config.progressAnimation, ['none', 'linear-clean', 'smooth-fill', 'wave-stroke', 'rotating-stroke', 'elastic-stroke'], 'none');
        normalized.speed = Math.round(clamp(finiteNumber(config.speed, 30), 1, 100));
        normalized.motionGlide = clamp(finiteNumber(config.motionGlide, 48), 0, 100);
        normalized.motionResponsiveness = clamp(finiteNumber(config.motionResponsiveness, 78), 0, 100);
        normalized.stepScrollRange = Math.round(clamp(finiteNumber(config.stepScrollRange, STEP_SCROLL_RANGE_PX), 60, 2000));
        normalized.stepScrollInterval = Math.round(clamp(finiteNumber(config.stepScrollInterval, STEP_SCROLL_INTERVAL_MS), 160, 30000));
        normalized.stepScrollDuration = Math.round(clamp(finiteNumber(config.stepScrollDuration, STEP_SCROLL_ANIMATION_MS), 120, 10000));
        normalized.autoStartDelay = Math.round(clamp(finiteNumber(config.autoStartDelay, 0), 0, 60000));
        normalized.resumeIdleDelay = Math.round(clamp(finiteNumber(config.resumeIdleDelay, DEFAULT_IDLE_RESUME_MS), 150, 60000));
        normalized.scrollTopShowAfter = clamp(finiteNumber(config.scrollTopShowAfter, 0.2), 0, 1);
        normalized.buttonAppearDelay = Math.round(clamp(finiteNumber(config.buttonAppearDelay, 0), 0, 60000));
        normalized.speedControlAnimationDuration = Math.round(clamp(finiteNumber(config.speedControlAnimationDuration, 400), 0, 10000));
        normalized.speedControlShowAnimation = config.speedControlShowAnimation === 'no' ? 'no' : 'yes';
        normalized.buttonStartLabel = String(config.buttonStartLabel || 'Mulai Auto Scroll');
        normalized.buttonStopLabel = String(config.buttonStopLabel || 'Berhenti Scroll');
        normalized.pausedLabel = String(config.pausedLabel || 'Auto Scroll dijeda');

        normalized.iconStart = String(config.iconStart || '');
        normalized.iconStop = String(config.iconStop || '');

        var booleanDefaults = {
            autoStart: true,
            pauseOnInteraction: true,
            resumeAfterIdle: true,
            pauseOnHover: true,
            loopScroll: false,
            disableOnIOS: false,
            showSpeedControl: false,
            speedDraggable: false,
            showScrollTop: true,

            rippleEnabled: false,
            isEditor: false
        };
        Object.keys(booleanDefaults).forEach(function (key) {
            if (config[key] === undefined || config[key] === null) {
                normalized[key] = booleanDefaults[key];
                return;
            }
            normalized[key] = config[key] === true || config[key] === 'yes' || config[key] === 'true' || config[key] === 1;
        });

        delete normalized.showTooltip;
        delete normalized.tooltipStart;
        delete normalized.tooltipStop;

        return normalized;
    }

    function findContainers(scope) {
        var root = scope && scope.jquery ? scope[0] : scope;
        var selector = '.apeiron-autoscroll-wrap[data-config]';
        var containers = [];

        if (!root) return containers;
        if (root.matches && root.matches(selector)) containers.push(root);
        if (root.querySelectorAll) {
            Array.prototype.forEach.call(root.querySelectorAll(selector), function (container) {
                if (containers.indexOf(container) < 0) containers.push(container);
            });
        }

        return containers;
    }

    function shouldAutoStart(config, prefersReducedMotion) {
        return config.autoStart && config.mode !== 'manual' && !config.isEditor && !prefersReducedMotion;
    }

    function getPressReleaseAction(mode, wasHolding, cancelled) {
        if (cancelled) return 'none';
        if (wasHolding) return 'stop';
        return mode === 'both' ? 'toggle' : 'none';
    }

    var PageMetrics = (function () {
        var subscribers = [];
        var height = 0;
        var dirty = true;
        var updateRAF = null;
        var resizeObserver = null;
        var mutationObserver = null;
        var listening = false;

        function measure() {
            if (!document.body || !document.documentElement) return window.innerHeight || 0;

            return Math.max(
                document.body.scrollHeight,
                document.documentElement.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.offsetHeight,
                document.body.clientHeight,
                document.documentElement.clientHeight
            );
        }

        function read(force) {
            if (force || dirty || !height) {
                height = measure();
                dirty = false;
            }
            return height;
        }

        function flush() {
            updateRAF = null;
            var nextHeight = read(true);
            subscribers.slice().forEach(function (callback) {
                callback(nextHeight);
            });
        }

        function invalidate() {
            dirty = true;
            if (!updateRAF) updateRAF = requestAnimationFrame(flush);
        }

        function start() {
            if (listening) return;
            listening = true;
            window.addEventListener('resize', invalidate, { passive: true });

            if (typeof ResizeObserver !== 'undefined') {
                resizeObserver = new ResizeObserver(invalidate);
                resizeObserver.observe(document.documentElement);
                if (document.body) resizeObserver.observe(document.body);
            }

            if (typeof MutationObserver !== 'undefined' && document.body) {
                mutationObserver = new MutationObserver(invalidate);
                mutationObserver.observe(document.body, { childList: true, subtree: true });
            }
        }

        function stop() {
            if (!listening || subscribers.length) return;
            listening = false;
            window.removeEventListener('resize', invalidate);
            if (resizeObserver) resizeObserver.disconnect();
            if (mutationObserver) mutationObserver.disconnect();
            resizeObserver = null;
            mutationObserver = null;
            if (updateRAF) cancelAnimationFrame(updateRAF);
            updateRAF = null;
        }

        return {
            read: read,
            needsPolling: function () {
                return typeof ResizeObserver === 'undefined' && typeof MutationObserver === 'undefined';
            },
            subscribe: function (callback) {
                if (subscribers.indexOf(callback) < 0) subscribers.push(callback);
                start();
                callback(read(true));

                return function () {
                    subscribers = subscribers.filter(function (item) { return item !== callback; });
                    stop();
                };
            }
        };
    }());

    var AutoScroll = {
        init: function (scope) {
            AutoScroll.pruneDetached();
            findContainers(scope || document).forEach(function (container) {
                AutoScroll.initWidget(container);
            });
        },

        pruneDetached: function () {
            initializedContainers.slice().forEach(function (container) {
                if (!container.isConnected) AutoScroll.destroyWidget(container);
            });
        },

        destroyWidget: function (container) {
            var instance = container._apeironAutoScroll;
            if (instance) {
                if (instance.destroy) {
                    instance.destroy();
                } else {
                    if (instance.autoStartTimer) {
                        clearTimeout(instance.autoStartTimer);
                    }
                    instance.stop(false);
                    if (instance.controller) {
                        instance.controller.abort();
                    }
                    if (instance.bodyObserver) {
                        instance.bodyObserver.disconnect();
                    }
                }
                instance = null;
            }
            container._apeironAutoScroll = null;
            delete container.dataset.apeironAutoInit;
            initializedContainers = initializedContainers.filter(function (item) {
                return item !== container;
            });
        },

        initWidget: function (container) {
            if (container.dataset.apeironAutoInit === 'yes') return;

            var configRaw = container.getAttribute('data-config');
            if (!configRaw) return;

            var config;
            try {
                config = normalizeConfig(JSON.parse(configRaw));
            } catch (e) {
                return;
            }

            var btnContainer = container.querySelector('.apeiron-btn-container');
            var btn = container.querySelector('.apeiron-scroll-btn');
            if (!btn) return;
            container.dataset.apeironAutoInit = 'yes';
            initializedContainers.push(container);

            var btnIcon = btn.querySelector('.btn-icon');
            var speedControl = container.querySelector('.apeiron-speed-control');
            var speedSlider = container.querySelector('.apeiron-speed-slider');
            var speedValue = container.querySelector('.speed-value');
            var scrollTopBtn = container.querySelector('.apeiron-scroll-top-btn');
            var progressRing = container.querySelector('.apeiron-progress-ring .progress');
            var progressRingSvg = progressRing ? progressRing.closest('.apeiron-progress-ring') : null;
            var progressBar = container.querySelector('.apeiron-progress-bar .bar-fill');
            var speedMinus = container.querySelector('.speed-minus');
            var speedPlus = container.querySelector('.speed-plus');

            var isScrolling = false;
            var scrollRAF = null;
            var scrollUpdateRAF = null;
            var lastTime = 0;
            var lastRenderTime = 0;
            var lastHeightRefresh = 0;
            var scrollStartTime = 0;
            var currentVelocity = 0;
            var currentSpeed = clamp(parseInt(config.speed, 10) || 30, 1, 100);
            var targetSpeed = currentSpeed;
            var smoothedSpeed = currentSpeed;
            var virtualScrollY = window.scrollY || 0;
            var lastProgress = -1;
            var scrollCompleted = false;
            var pageHeight = 0;
            var isAutoScrolling = false;
            var isPaused = false;
            var scrollState = SCROLL_STATE_IDLE;
            var pauseReasons = {};
            var idleResumeTimer = null;
            var autoStartTimer = null;
            var scrollTopShowTimer = null;
            var scrollTopRAF = null;
            var managedTimers = [];
            var speedUiRAF = null;
            var speedValueAnimRAF = null;
            var speedValueAnimTimer = null;
            var speedArrowActiveTimer = null;
            var speedControlAnimationTimer = null;
            var stepScrollInterval = null;
            var stepScrollRAF = null;
            var stepScrollAnimationStart = 0;
            var stepScrollAnimationDuration = STEP_SCROLL_ANIMATION_MS;
            var stepScrollStartY = 0;
            var stepScrollTargetY = 0;
            var bodyObserver = null;
            var pageMetricsCleanup = null;
            var listenerCleanups = [];
            var lastProgrammaticScrollY = null;
            var lastProgrammaticScrollTime = 0;
            var supportsInstantScroll = null;
            var scrollOptions = { top: 0, left: 0, behavior: 'instant' };
            var startedAutomatically = false;
            var autoStartPending = false;
            var keyboardFocusIntent = false;
            var reducedMotionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
            var prefersReducedMotion = reducedMotionQuery ? reducedMotionQuery.matches : false;
            var reducedMotionCleanup = null;
            var motionSettings = resolveMotionSettings();

            var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

            function addListener(target, type, handler, options) {
                if (!target || !target.addEventListener) return;
                var safeOptions = options || false;

                if (safeOptions && typeof safeOptions === 'object' && safeOptions.signal) {
                    safeOptions = Object.assign({}, safeOptions);
                    delete safeOptions.signal;
                }

                target.addEventListener(type, handler, safeOptions);
                listenerCleanups.push(function () {
                    target.removeEventListener(type, handler, safeOptions);
                });
            }

            function isIOS() {
                return config.disableOnIOS && (/iPad|iPhone|iPod/.test(navigator.userAgent) ||
                    (navigator.userAgent.includes('Macintosh') && navigator.maxTouchPoints > 1));
            }

            if (isIOS()) {
                container.classList.add('is-hidden');
                storeInstance();
                return;
            }

            function setManagedTimeout(callback, delay) {
                var timer = setTimeout(function () {
                    managedTimers = managedTimers.filter(function (item) {
                        return item !== timer;
                    });
                    callback();
                }, delay);

                managedTimers.push(timer);
                return timer;
            }

            function clearManagedTimeout(timer) {
                if (!timer) return;

                clearTimeout(timer);
                managedTimers = managedTimers.filter(function (item) {
                    return item !== timer;
                });
            }

            function clearManagedTimers() {
                managedTimers.forEach(function (timer) {
                    clearTimeout(timer);
                });
                managedTimers = [];
                idleResumeTimer = null;
                autoStartTimer = null;
                scrollTopShowTimer = null;
                speedValueAnimTimer = null;
                speedArrowActiveTimer = null;
                speedControlAnimationTimer = null;
                if (speedUiRAF) {
                    cancelAnimationFrame(speedUiRAF);
                    speedUiRAF = null;
                }
                if (speedValueAnimRAF) {
                    cancelAnimationFrame(speedValueAnimRAF);
                    speedValueAnimRAF = null;
                }
                clearStepScrollLoop();
                cancelScrollTopAnimation();
            }

            if (btnContainer && config.buttonAppearAnimation && config.buttonAppearAnimation !== 'none') {
                btnContainer.classList.add('await-animation');
                requestAnimationFrame(function () {
                    btnContainer.classList.add('anim-' + config.buttonAppearAnimation);
                });
                if (config.buttonAppearDelay > 0) {
                    setManagedTimeout(function () {
                        btnContainer.style.setProperty('--ak-button-appear-delay', '0s');
                    }, config.buttonAppearDelay);
                }
            }

            function refreshPageHeight(force) {
                pageHeight = PageMetrics.read(force === true);
                virtualScrollY = clamp(virtualScrollY, 0, getMaxScrollTop());
            }

            pageMetricsCleanup = PageMetrics.subscribe(function (nextHeight) {
                pageHeight = nextHeight;
                virtualScrollY = clamp(virtualScrollY, 0, getMaxScrollTop());
                if (!isAutoScrolling) updateProgress(true);
                if (autoStartPending && getMaxScrollTop() > END_THRESHOLD_PX) attemptAutoStart();
            });

            function refreshPageHeightIfNeeded(timestamp) {
                if (PageMetrics.needsPolling() && (!lastHeightRefresh || timestamp - lastHeightRefresh >= HEIGHT_REFRESH_FALLBACK_MS)) {
                    refreshPageHeight(true);
                    lastHeightRefresh = timestamp;
                }
            }

            function smoothStep(value) {
                var t = clamp(value, 0, 1);
                return t * t * (3 - 2 * t);
            }

            function isStepScrollEngine() {
                return config.scrollEngine === 'step';
            }

            function getAutoStartDelay() {
                var delay = parseInt(config.autoStartDelay, 10);
                return Number.isFinite(delay) ? Math.max(0, delay) : 0;
            }

            function easeInOutQuad(value) {
                var t = clamp(value, 0, 1);
                return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
            }

            function getStepScrollRange() {
                var baseRange = finiteNumber(config.stepScrollRange, STEP_SCROLL_RANGE_PX);
                return Math.max(60, Math.round(baseRange));
            }

            function getStepSpeedFactor() {
                return clamp(currentSpeed / STEP_SCROLL_BASE_SPEED, 0.25, 3);
            }

            function getStepScrollInterval() {
                var interval = finiteNumber(config.stepScrollInterval, STEP_SCROLL_INTERVAL_MS);
                return Math.max(160, Math.round(interval / getStepSpeedFactor()));
            }

            function getStepScrollDuration() {
                var duration = finiteNumber(config.stepScrollDuration, STEP_SCROLL_ANIMATION_MS);
                return Math.max(120, Math.round(duration / getStepSpeedFactor()));
            }

            function getMaxScrollTop() {
                return Math.max(0, pageHeight - window.innerHeight);
            }

            function syncVirtualScrollPosition() {
                virtualScrollY = clamp(window.scrollY || 0, 0, getMaxScrollTop());
            }

            function getNow() {
                return window.performance && performance.now ? performance.now() : Date.now();
            }

            function writeScrollPosition(top) {
                var target = clamp(top, 0, getMaxScrollTop());
                lastProgrammaticScrollY = target;
                lastProgrammaticScrollTime = getNow();

                if (supportsInstantScroll === false) {
                    window.scrollTo(0, target);
                    return;
                }

                scrollOptions.top = target;
                try {
                    window.scrollTo(scrollOptions);
                    supportsInstantScroll = true;
                } catch (error) {
                    supportsInstantScroll = false;
                    window.scrollTo(0, target);
                }
            }

            function isProgrammaticScrollEvent() {
                if (lastProgrammaticScrollY === null) return false;

                var isRecent = getNow() - lastProgrammaticScrollTime <= PROGRAMMATIC_SCROLL_WINDOW_MS;
                var isExpectedPosition = Math.abs((window.scrollY || 0) - lastProgrammaticScrollY) <= PROGRAMMATIC_SCROLL_TOLERANCE_PX;

                if (!isRecent || !isExpectedPosition) {
                    lastProgrammaticScrollY = null;
                    return false;
                }

                return true;
            }

            function clearAutoStartTimer() {
                if (autoStartTimer) {
                    clearManagedTimeout(autoStartTimer);
                    autoStartTimer = null;
                }
                autoStartPending = false;
            }

            function hasTemporaryDocumentLock() {
                var html = document.documentElement;
                var body = document.body;
                var lockClasses = [
                    'apeiron-cover-scroll-lock',
                    'apeiron-page-loader-scroll-lock',
                    'apeiron-page-loader-booting'
                ];

                return lockClasses.some(function (className) {
                    return html.classList.contains(className) || (body && body.classList.contains(className));
                });
            }

            function attemptAutoStart() {
                if (!shouldAutoStart(config, prefersReducedMotion)) {
                    autoStartPending = false;
                    return;
                }

                if (hasTemporaryDocumentLock()) {
                    autoStartPending = true;
                    return;
                }

                refreshPageHeight(true);
                if (getMaxScrollTop() <= END_THRESHOLD_PX) {
                    autoStartPending = true;
                    return;
                }

                autoStartPending = false;
                startScroll(true);
            }

            function clearIdleResumeTimer() {
                if (idleResumeTimer) {
                    clearManagedTimeout(idleResumeTimer);
                    idleResumeTimer = null;
                }
            }

            function clearScrollTopShowTimer() {
                if (scrollTopShowTimer) {
                    clearManagedTimeout(scrollTopShowTimer);
                    scrollTopShowTimer = null;
                }
            }

            function cancelScrollFrame() {
                if (scrollRAF) {
                    cancelAnimationFrame(scrollRAF);
                    scrollRAF = null;
                }
            }

            function cancelScrollTopAnimation() {
                if (scrollTopRAF) {
                    cancelAnimationFrame(scrollTopRAF);
                    scrollTopRAF = null;
                }
            }

            function hasPauseReasons() {
                return Object.keys(pauseReasons).length > 0;
            }

            function getIdleResumeDelay() {
                var delay = parseInt(config.resumeIdleDelay, 10);
                return Number.isFinite(delay) ? Math.max(150, delay) : DEFAULT_IDLE_RESUME_MS;
            }

            function canResumeAfterIdle() {
                return config.resumeAfterIdle && (config.mode === 'auto' || config.mode === 'both') && !scrollCompleted;
            }

            function queueScrollFrame() {
                var canRenderPausedGlide = isPaused && Math.abs(currentVelocity) >= MIN_VELOCITY;
                if (!scrollRAF && isScrolling && (!isPaused || canRenderPausedGlide) && !document.hidden) {
                    scrollRAF = requestAnimationFrame(smoothScroll);
                }
            }

            function getStartLabel() {
                return config.buttonStartLabel || 'Mulai Auto Scroll';
            }

            function getStopLabel() {
                return config.buttonStopLabel || 'Berhenti Scroll';
            }

            function setScrollState(nextState) {
                scrollState = nextState;
                container.dataset.scrollState = nextState;
                container.classList.toggle('is-paused', nextState === SCROLL_STATE_PAUSED);
            }

            function updateButtonState(nextState) {
                var isActiveState = nextState === SCROLL_STATE_RUNNING || nextState === SCROLL_STATE_PAUSED;
                var isPausedState = nextState === SCROLL_STATE_PAUSED;
                var label = isPausedState ? config.pausedLabel : (isActiveState ? getStopLabel() : getStartLabel());

                btn.classList.toggle('is-active', isActiveState);
                btn.classList.toggle('is-paused', isPausedState);
                btn.setAttribute('aria-pressed', isActiveState ? 'true' : 'false');
                btn.setAttribute('aria-label', label);

                if (btnIcon) {
                    btnIcon.innerHTML = isActiveState ? config.iconStop : config.iconStart;
                }
            }

            function removeButtonAnimationClasses() {
                btn.className = btn.className.replace(/\banim-[a-z-]+\b/g, '').replace(/\s+/g, ' ').trim();
            }

            function addActiveButtonAnimation() {
                if (!prefersReducedMotion && config.activeAnimation !== 'none') {
                    btn.classList.add('anim-' + config.activeAnimation);
                }
            }

            function handleReducedMotionChange(e) {
                prefersReducedMotion = !!e.matches;
                motionSettings = resolveMotionSettings();
                if (prefersReducedMotion) {
                    clearAutoStartTimer();
                    currentVelocity = 0;
                    smoothedSpeed = targetSpeed;
                    removeButtonAnimationClasses();
                    if (startedAutomatically && isScrolling) stopScroll(false);
                } else if (scrollState === SCROLL_STATE_RUNNING) {
                    addActiveButtonAnimation();
                }
            }

            if (reducedMotionQuery) {
                if (reducedMotionQuery.addEventListener) {
                    addListener(reducedMotionQuery, 'change', handleReducedMotionChange);
                } else if (reducedMotionQuery.addListener) {
                    reducedMotionQuery.addListener(handleReducedMotionChange);
                    reducedMotionCleanup = function () {
                        reducedMotionQuery.removeListener(handleReducedMotionChange);
                    };
                }
            }

            addListener(document, 'visibilitychange', function () {
                if (document.hidden && isScrolling) {
                    cancelScrollFrame();
                    if (isStepScrollEngine()) {
                        clearStepScrollLoop();
                    }
                } else if (!document.hidden && isScrolling) {
                    lastTime = 0;
                    lastRenderTime = 0;
                    if (isStepScrollEngine()) {
                        if (!isPaused) {
                            startStepScrollLoop();
                        }
                    } else {
                        queueScrollFrame();
                    }
                }
            });

            function getScrollProgress() {
                var scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
                var docHeight = pageHeight - window.innerHeight;
                if (docHeight <= 0) return 0;
                return Math.min(Math.max(scrollTop / docHeight, 0), 1);
            }

            function getEasingMultiplier(t) {
                switch (config.easing) {
                    case 'ease': return 0.85 + Math.sin(t * Math.PI) * 0.3;
                    case 'ease-in': return 0.5 + t * 0.5;
                    case 'ease-out': return 1.5 - t * 0.5;
                    case 'ease-in-out': return 0.65 + Math.sin(t * Math.PI) * 0.7;
                    default: return 1;
                }
            }

            function isAtEnd() {
                var scrollTop = isAutoScrolling ? virtualScrollY : window.scrollY;
                if (config.direction === 'down') {
                    return (window.innerHeight + scrollTop) >= pageHeight - END_THRESHOLD_PX;
                }
                return scrollTop <= END_THRESHOLD_PX;
            }

            function getBoundaryDamping() {
                if (config.loopScroll) return 1;

                var currentTop = isAutoScrolling ? virtualScrollY : window.scrollY;
                var distance = config.direction === 'down' ? getMaxScrollTop() - currentTop : currentTop;
                var dampingDistance = motionSettings.boundaryDampingDistance || 240;
                var minScale = motionSettings.minBoundaryScale || 0.18;

                if (distance >= dampingDistance) return 1;
                return clamp(distance / dampingDistance, minScale, 1);
            }

            function getFrameInterval() {
                if (prefersReducedMotion) {
                    return 1000 / 45;
                }

                switch (config.smoothness) {
                    case 'normal': return 1000 / 45;
                    case 'smooth': return 1000 / 60;
                    case 'ultra':
                    default: return 0;
                }
            }

            function resolveMotionSettings() {
                var profile = config.motionProfile || 'steady';
                var responsiveness = clamp(finiteNumber(config.motionResponsiveness, 78) / 100, 0.05, 1);
                var glide = clamp(finiteNumber(config.motionGlide, 48) / 100, 0, 1);
                var settings = {
                    targetScale: 1,
                    acceleration: 7,
                    deceleration: 5,
                    speedResponse: 9,
                    startRampMs: 420,
                    glideAmplitude: 0,
                    glideCycleMs: 2200,
                    boundaryDampingDistance: 240,
                    minBoundaryScale: 0.18
                };

                if (profile === 'legacy') {
                    settings.targetScale = 1;
                    settings.acceleration = 36;
                    settings.deceleration = 36;
                    settings.speedResponse = 30;
                    settings.startRampMs = 0;
                    settings.glideAmplitude = 0;
                    settings.boundaryDampingDistance = 180;
                    settings.minBoundaryScale = 0.24;
                } else if (profile === 'steady') {
                    settings.targetScale = 0.96;
                    settings.acceleration = 6.5 + responsiveness * 7.5;
                    settings.deceleration = 6 + (1 - glide) * 5.5;
                    settings.speedResponse = 7.5 + responsiveness * 7;
                    settings.startRampMs = 360 + glide * 220;
                    settings.glideAmplitude = 0;
                    settings.boundaryDampingDistance = 260 + glide * 140;
                    settings.minBoundaryScale = 0.18;
                } else if (profile === 'kinetic') {
                    settings.targetScale = 1.02;
                    settings.acceleration = 3.2 + responsiveness * 4.4;
                    settings.deceleration = 1.35 + (1 - glide) * 2.8;
                    settings.speedResponse = 4.4 + responsiveness * 4.8;
                    settings.startRampMs = 680 + glide * 420;
                    settings.glideAmplitude = 0.018 + glide * 0.032;
                    settings.glideCycleMs = 1800 - responsiveness * 280 + glide * 460;
                    settings.boundaryDampingDistance = 360 + glide * 260;
                    settings.minBoundaryScale = 0.12;
                } else {
                    settings.targetScale = 0.98;
                    settings.acceleration = 2.6 + responsiveness * 3.8;
                    settings.deceleration = 1.15 + (1 - glide) * 2.6;
                    settings.speedResponse = 3.8 + responsiveness * 4.6;
                    settings.startRampMs = 720 + glide * 360;
                    settings.glideAmplitude = 0.012 + glide * 0.024;
                    settings.glideCycleMs = 2100 - responsiveness * 360 + glide * 380;
                    settings.boundaryDampingDistance = 420 + glide * 260;
                    settings.minBoundaryScale = 0.1;
                }

                if (prefersReducedMotion) {
                    settings.targetScale = Math.min(settings.targetScale, 0.72);
                    settings.acceleration = Math.max(settings.acceleration, 14);
                    settings.deceleration = Math.max(settings.deceleration, 14);
                    settings.speedResponse = Math.max(settings.speedResponse, 18);
                    settings.startRampMs = 0;
                    settings.glideAmplitude = 0;
                }

                return settings;
            }

            function getStartRampMultiplier(timestamp) {
                if (!motionSettings.startRampMs || !scrollStartTime) return 1;

                var elapsed = Math.max(0, timestamp - scrollStartTime);
                if (elapsed >= motionSettings.startRampMs) return 1;

                return 0.2 + smoothStep(elapsed / motionSettings.startRampMs) * 0.8;
            }

            function getNaturalGlideMultiplier(timestamp) {
                if (!motionSettings.glideAmplitude || !scrollStartTime) return 1;

                var cycle = Math.max(900, motionSettings.glideCycleMs || 2200);
                var elapsed = Math.max(0, timestamp - scrollStartTime);
                var phase = (elapsed % cycle) / cycle;
                var primary = Math.sin(phase * Math.PI * 2 - Math.PI / 2);
                var secondary = Math.sin(phase * Math.PI * 4 + Math.PI / 6) * 0.25;
                var wave = (primary * 0.75) + secondary;
                var amplitude = motionSettings.glideAmplitude;

                return clamp(1 + wave * amplitude, 1 - amplitude, 1 + amplitude);
            }

            function getMotionMultiplier(timestamp) {
                return getStartRampMultiplier(timestamp) * getNaturalGlideMultiplier(timestamp);
            }

            function interpolateSpeed(deltaSeconds) {
                if (smoothedSpeed === targetSpeed) return;

                var blend = 1 - Math.exp(-motionSettings.speedResponse * deltaSeconds);
                smoothedSpeed += (targetSpeed - smoothedSpeed) * blend;

                if (Math.abs(smoothedSpeed - targetSpeed) < 0.02) {
                    smoothedSpeed = targetSpeed;
                }
            }

            function getTargetVelocity(timestamp) {
                var frameStep = (smoothedSpeed / 100) * SPEED_MULTIPLIER + SPEED_BASE;
                var velocity = frameStep * BASE_FRAME_RATE * motionSettings.targetScale;

                velocity *= getMotionMultiplier(timestamp);

                if (config.easing !== 'linear') {
                    velocity *= getEasingMultiplier(getScrollProgress());
                }

                velocity *= getBoundaryDamping();

                return velocity * (config.direction === 'down' ? 1 : -1);
            }

            function interpolateVelocity(targetVelocity, deltaSeconds) {
                var movingTowardZero = Math.abs(targetVelocity) < Math.abs(currentVelocity);
                var response = movingTowardZero ? motionSettings.deceleration : motionSettings.acceleration;
                var blend = 1 - Math.exp(-response * deltaSeconds);

                currentVelocity += (targetVelocity - currentVelocity) * blend;

                if (Math.abs(currentVelocity) < MIN_VELOCITY && Math.abs(targetVelocity) < MIN_VELOCITY) {
                    currentVelocity = 0;
                }
            }

            function finishAtBoundary() {
                currentVelocity = 0;
                updateProgress(true);

                if (config.loopScroll && config.direction === 'down') {
                    virtualScrollY = 0;
                    writeScrollPosition(0);
                    updateProgress(true);
                    lastTime = 0;
                    lastRenderTime = 0;
                    scrollStartTime = 0;
                    return false;
                }

                stopScroll(true);
                showScrollTopButton();
                return true;
            }

            function applyScrollDelta(scrollDelta) {
                var maxScroll = getMaxScrollTop();
                var nextTop = clamp(virtualScrollY + scrollDelta, 0, maxScroll);

                if (config.direction === 'down' && nextTop >= maxScroll - END_THRESHOLD_PX) {
                    virtualScrollY = maxScroll;
                    writeScrollPosition(maxScroll);
                    return finishAtBoundary();
                }

                if (config.direction !== 'down' && nextTop <= END_THRESHOLD_PX) {
                    virtualScrollY = 0;
                    writeScrollPosition(0);
                    return finishAtBoundary();
                }

                virtualScrollY = nextTop;

                if (Math.abs(nextTop - window.scrollY) >= 0.001) {
                    writeScrollPosition(nextTop);
                }

                updateProgress();
                return false;
            }

            function smoothScroll(timestamp) {
                scrollRAF = null;
                if (!isScrolling) return;

                var frameInterval = getFrameInterval();
                if (frameInterval && lastRenderTime && timestamp - lastRenderTime < frameInterval) {
                    queueScrollFrame();
                    return;
                }

                refreshPageHeightIfNeeded(timestamp);

                if (!lastTime) lastTime = timestamp;
                if (!scrollStartTime) scrollStartTime = timestamp;
                var delta = clamp(timestamp - lastTime, 0, MAX_FRAME_DELTA_MS);
                lastTime = timestamp;
                lastRenderTime = timestamp;

                if (delta <= 0) {
                    queueScrollFrame();
                    return;
                }

                var deltaSeconds = delta / 1000;
                interpolateSpeed(deltaSeconds);
                var targetVelocity = isPaused ? 0 : getTargetVelocity(timestamp);
                interpolateVelocity(targetVelocity, deltaSeconds);

                if (!isPaused && isAtEnd()) {
                    if (finishAtBoundary()) return;
                } else if (currentVelocity !== 0) {
                    if (applyScrollDelta(currentVelocity * deltaSeconds)) return;
                } else {
                    updateProgress();
                }

                if (isPaused && currentVelocity === 0) {
                    return;
                }

                queueScrollFrame();
            }

            function clearStepScrollLoop() {
                clearStepScrollTimer();

                if (stepScrollRAF) {
                    cancelAnimationFrame(stepScrollRAF);
                    stepScrollRAF = null;
                }

                stepScrollAnimationStart = 0;
            }

            function clearStepScrollTimer() {
                if (stepScrollInterval) {
                    clearManagedTimeout(stepScrollInterval);
                    stepScrollInterval = null;
                }
            }

            function scheduleNextStepScrollStep() {
                if (!isScrolling || isPaused || document.hidden) return;
                if (stepScrollInterval) return;

                stepScrollInterval = setManagedTimeout(function () {
                    stepScrollInterval = null;
                    runStepScrollStep();
                }, getStepScrollInterval());
            }

            function startStepScrollLoop() {
                clearStepScrollLoop();
                runStepScrollStep();
            }

            function runStepScrollStep() {
                if (!isScrolling || isPaused || document.hidden) return;

                refreshPageHeight(false);
                syncVirtualScrollPosition();

                if (isAtEnd()) {
                    finishAtBoundary();
                    return;
                }

                var direction = config.direction === 'down' ? 1 : -1;
                var step = getStepScrollRange() * direction;
                var maxScroll = getMaxScrollTop();
                var currentTop = window.scrollY || 0;
                var targetTop = clamp(currentTop + step, 0, maxScroll);

                if (config.direction === 'down' && targetTop >= maxScroll - END_THRESHOLD_PX) {
                    targetTop = maxScroll;
                }

                if (config.direction !== 'down' && targetTop <= END_THRESHOLD_PX) {
                    targetTop = 0;
                }

                stepScrollStartY = currentTop;
                stepScrollTargetY = targetTop;
                stepScrollAnimationDuration = getStepScrollDuration();
                stepScrollAnimationStart = 0;

                if (prefersReducedMotion) {
                    virtualScrollY = targetTop;
                    writeScrollPosition(targetTop);
                    updateProgress(true);
                    refreshPageHeight(false);
                    if (isAtEnd()) finishAtBoundary();
                    else scheduleNextStepScrollStep();
                    return;
                }

                if (stepScrollRAF) {
                    cancelAnimationFrame(stepScrollRAF);
                    stepScrollRAF = null;
                }

                stepScrollRAF = requestAnimationFrame(animateStepScrollStep);
            }

            function animateStepScrollStep(timestamp) {
                if (!isScrolling || isPaused) {
                    stepScrollRAF = null;
                    return;
                }

                if (!stepScrollAnimationStart) {
                    stepScrollAnimationStart = timestamp;
                }

                var progress = clamp((timestamp - stepScrollAnimationStart) / stepScrollAnimationDuration, 0, 1);
                var easedProgress = easeInOutQuad(progress);
                var nextTop = stepScrollStartY + ((stepScrollTargetY - stepScrollStartY) * easedProgress);

                virtualScrollY = nextTop;
                writeScrollPosition(nextTop);
                updateProgress();

                if (progress < 1) {
                    stepScrollRAF = requestAnimationFrame(animateStepScrollStep);
                    return;
                }

                stepScrollRAF = null;
                virtualScrollY = stepScrollTargetY;
                writeScrollPosition(stepScrollTargetY);
                updateProgress(true);
                refreshPageHeight(false);

                if (isAtEnd()) {
                    finishAtBoundary();
                } else {
                    scheduleNextStepScrollStep();
                }
            }

            function pauseScroll(reason, resumeAfterIdle) {
                if (!isScrolling) return;

                var pauseReason = reason || 'interaction';

                pauseReasons[pauseReason] = true;
                isPaused = true;
                isAutoScrolling = false;
                currentVelocity = 0;
                lastTime = 0;
                lastRenderTime = 0;
                scrollStartTime = 0;
                clearStepScrollLoop();
                syncVirtualScrollPosition();
                setScrollState(SCROLL_STATE_PAUSED);
                updateButtonState(SCROLL_STATE_PAUSED);
                removeButtonAnimationClasses();
                updateProgress(true);
                cancelScrollFrame();

                if (resumeAfterIdle && canResumeAfterIdle()) {
                    clearIdleResumeTimer();
                    idleResumeTimer = setManagedTimeout(function () {
                        idleResumeTimer = null;
                        resumeScroll(pauseReason);
                    }, getIdleResumeDelay());
                }
            }

            function resumeScroll(reason) {
                if (!isScrolling) return;

                if (reason) {
                    delete pauseReasons[reason];
                } else {
                    pauseReasons = {};
                }

                isPaused = hasPauseReasons();
                if (isPaused) return;

                clearIdleResumeTimer();
                isAutoScrolling = true;
                syncVirtualScrollPosition();
                lastTime = 0;
                lastRenderTime = 0;
                scrollStartTime = 0;
                setScrollState(SCROLL_STATE_RUNNING);
                updateButtonState(SCROLL_STATE_RUNNING);
                addActiveButtonAnimation();
                if (isStepScrollEngine()) {
                    startStepScrollLoop();
                } else {
                    queueScrollFrame();
                }
            }

            function handleUserScrollIntent(e) {
                var target = e.target;
                if (target && target.nodeType && container.contains(target)) {
                    return;
                }

                if (!isScrolling) return;

                if (config.userInteractionBehavior === 'stop_on_interaction') {
                    if (!e.isTrusted || closestMatching(target, '.apeiron-autoscroll-wrap')) return;
                    if (scrollState === SCROLL_STATE_RUNNING) {
                        stopScroll(false);
                        return;
                    }
                }

                if (!config.pauseOnInteraction) return;

                if (config.resumeAfterIdle) {
                    pauseScroll('interaction', true);
                } else {
                    stopScroll(false);
                }
            }

            function stopForInteractiveEvent(e) {
                if (config.userInteractionBehavior !== 'stop_on_interaction' || !e.isTrusted ||
                    !isScrolling || scrollState !== SCROLL_STATE_RUNNING ||
                    closestMatching(e.target, '.apeiron-autoscroll-wrap')) {
                    return false;
                }

                stopScroll(false);
                return true;
            }

            function handleInteractivePointer(e) {
                if (isInteractiveTarget(e.target)) stopForInteractiveEvent(e);
            }

            function handleInteractionKeydown(e) {
                if (config.userInteractionBehavior === 'stop_on_interaction') {
                    if (e.isTrusted && e.key === 'Tab') {
                        keyboardFocusIntent = true;
                        return;
                    }

                    if (closestMatching(e.target, '.apeiron-autoscroll-wrap')) return;

                    var activatesControl = (e.key === 'Enter' || e.key === ' ' || e.key === 'Space') && isInteractiveTarget(e.target);
                    if (isFormInteractionTarget(e.target) || activatesControl || isPageScrollKeyEvent(e)) {
                        if (stopForInteractiveEvent(e)) return;
                    }
                }

                if (config.pauseOnInteraction && SCROLL_INTENT_KEYS[e.key]) {
                    handleUserScrollIntent(e);
                }
            }

            function handleInteractionFocus(e) {
                if (!keyboardFocusIntent) return;
                keyboardFocusIntent = false;
                if (isInteractiveTarget(e.target)) stopForInteractiveEvent(e);
            }

            function resetSpeedControlAnimationClasses() {
                if (!speedControl) return;

                speedControl.classList.remove(
                    'appear-fade', 'appear-slide', 'appear-scale', 'appear-bounce',
                    'appear-zoom', 'appear-flip', 'appear-elastic', 'appear-slide-up', 'appear-slide-down',
                    'disappear-fade', 'disappear-slide', 'disappear-scale', 'disappear-bounce',
                    'disappear-zoom', 'disappear-flip', 'disappear-elastic', 'disappear-slide-up', 'disappear-slide-down',
                    'no-motion'
                );
            }

            function getSpeedControlAnimationDuration() {
                var duration = parseInt(config.speedControlAnimationDuration, 10);
                return Number.isFinite(duration) ? Math.max(0, duration) : 0;
            }

            function clearSpeedControlAnimationTimer() {
                if (speedControlAnimationTimer) {
                    clearManagedTimeout(speedControlAnimationTimer);
                    speedControlAnimationTimer = null;
                }
            }

            function shouldAnimateSpeedControl() {
                return !prefersReducedMotion && config.speedControlShowAnimation === 'yes';
            }

            function positionAutoSpeedControl() {
                if (!speedControl || !speedControl.classList.contains('pos-auto')) return;

                speedControl.classList.remove('auto-below');
                var panelHeight = speedControl.getBoundingClientRect().height;
                var buttonRect = btn.getBoundingClientRect();
                var containerStyle = getComputedStyle(container);
                var gapValue = containerStyle.getPropertyValue('--ak-speed-control-gap').trim();
                var gap = parseFloat(gapValue);
                if (gapValue.endsWith('em')) gap *= parseFloat(containerStyle.fontSize) || 16;
                if (!Number.isFinite(gap)) gap = 10;
                var viewportTop = window.visualViewport ? window.visualViewport.offsetTop : 0;
                var viewportBottom = viewportTop + (window.visualViewport ? window.visualViewport.height : window.innerHeight);
                var spaceAbove = buttonRect.top - viewportTop - gap;
                var spaceBelow = viewportBottom - buttonRect.bottom - gap;

                speedControl.classList.toggle('auto-below', spaceAbove < panelHeight && spaceBelow >= panelHeight);
            }

            function showSpeedControl() {
                if (!speedControl || !config.showSpeedControl) return;

                clearSpeedControlAnimationTimer();
                resetSpeedControlAnimationClasses();
                speedControl.classList.remove('is-hiding');
                positionAutoSpeedControl();
                if (shouldAnimateSpeedControl()) {
                    speedControl.classList.add('appear-' + (config.speedControlAppearAnimation || 'scale'));
                    speedControlAnimationTimer = setManagedTimeout(function () {
                        resetSpeedControlAnimationClasses();
                        speedControlAnimationTimer = null;
                    }, getSpeedControlAnimationDuration());
                } else {
                    speedControl.classList.add('no-motion');
                }
                speedControl.classList.add('show');
            }

            function hideSpeedControl() {
                if (!speedControl) return;

                if (speedControl.contains(document.activeElement)) btn.focus();

                clearSpeedControlAnimationTimer();
                resetSpeedControlAnimationClasses();
                if (shouldAnimateSpeedControl()) {
                    speedControl.classList.add('is-hiding');
                    speedControl.classList.add('disappear-' + (config.speedControlDisappearAnimation || 'scale'));
                    speedControlAnimationTimer = setManagedTimeout(function () {
                        speedControl.classList.remove('show');
                        speedControl.classList.remove('is-hiding');
                        resetSpeedControlAnimationClasses();
                        speedControlAnimationTimer = null;
                    }, getSpeedControlAnimationDuration());
                } else {
                    speedControl.classList.add('is-hiding');
                    speedControl.classList.add('no-motion');
                    speedControlAnimationTimer = setManagedTimeout(function () {
                        speedControl.classList.remove('is-hiding');
                        speedControl.classList.remove('no-motion');
                        speedControlAnimationTimer = null;
                    }, 0);
                    speedControl.classList.remove('show');
                }
            }

            function startScroll(fromAutoStart) {
                if (isScrolling) return;
                if (fromAutoStart && prefersReducedMotion) return;

                if (activeAutoScrollInstance && activeAutoScrollInstance.container !== container && activeAutoScrollInstance.stop) {
                    activeAutoScrollInstance.stop(false, true);
                }
                storeInstance();
                activeAutoScrollInstance = container._apeironAutoScroll;
                clearAutoStartTimer();
                cancelScrollTopAnimation();
                clearScrollTopShowTimer();
                refreshPageHeight(true);

                if (isAtEnd() && !config.loopScroll) {
                    scrollCompleted = !fromAutoStart;
                    setScrollState(fromAutoStart ? SCROLL_STATE_IDLE : SCROLL_STATE_COMPLETED);
                    updateButtonState(SCROLL_STATE_STOPPED);
                    if (activeAutoScrollInstance && activeAutoScrollInstance.container === container) {
                        activeAutoScrollInstance = null;
                    }
                    if (!fromAutoStart) {
                        showScrollTopButton();
                    }
                    return;
                }

                startedAutomatically = fromAutoStart === true;
                isScrolling = true;
                isAutoScrolling = true;
                isPaused = false;
                pauseReasons = {};
                clearIdleResumeTimer();
                scrollCompleted = false;
                lastTime = 0;
                lastRenderTime = 0;
                lastHeightRefresh = 0;
                scrollStartTime = 0;
                currentVelocity = 0;
                smoothedSpeed = targetSpeed;
                syncVirtualScrollPosition();
                setScrollState(SCROLL_STATE_RUNNING);

                if (scrollTopBtn) scrollTopBtn.classList.remove('show');

                btn.classList.add('btn-pressed');
                setManagedTimeout(function () { btn.classList.remove('btn-pressed'); }, PRESS_ANIM_MS);

                updateButtonState(SCROLL_STATE_RUNNING);
                addActiveButtonAnimation();

                if (config.rippleEnabled) {
                    var ripples = container.querySelectorAll('.ak-ripple-ring');
                    ripples.forEach(function (r) { r.classList.add('ripple-active'); });
                }

                container.classList.add('is-scrolling');
                container.classList.toggle('is-step-engine', isStepScrollEngine());
                showSpeedControl();

                if (isStepScrollEngine()) {
                    startStepScrollLoop();
                } else {
                    queueScrollFrame();
                }
            }

            function stopScroll(completed, skipUiAnimation) {
                isScrolling = false;
                isAutoScrolling = false;
                isPaused = false;
                startedAutomatically = false;
                pauseReasons = {};
                currentVelocity = 0;
                lastTime = 0;
                lastRenderTime = 0;
                scrollStartTime = 0;
                clearAutoStartTimer();
                clearIdleResumeTimer();
                clearScrollTopShowTimer();
                cancelScrollTopAnimation();
                clearStepScrollLoop();
                scrollCompleted = completed === true;
                cancelScrollFrame();
                setScrollState(scrollCompleted ? SCROLL_STATE_COMPLETED : SCROLL_STATE_STOPPED);

                if (activeAutoScrollInstance && activeAutoScrollInstance.container === container) {
                    activeAutoScrollInstance = null;
                }

                if (skipUiAnimation) {
                    removeButtonAnimationClasses();
                    btn.classList.remove('is-active', 'is-paused', 'btn-pressed', 'btn-released');
                    updateButtonState(SCROLL_STATE_STOPPED);
                    if (config.rippleEnabled) {
                        container.querySelectorAll('.ak-ripple-ring').forEach(function (r) {
                            r.classList.remove('ripple-active');
                        });
                    }
                    if (speedControl) {
                        resetSpeedControlAnimationClasses();
                        speedControl.classList.remove('show');
                    }
                    container.classList.remove('is-scrolling', 'is-step-engine');
                    return;
                }

                btn.classList.add('btn-released');
                setManagedTimeout(function () { btn.classList.remove('btn-released'); }, PRESS_ANIM_MS);

                removeButtonAnimationClasses();
                btn.classList.remove('is-active', 'is-paused');
                updateButtonState(SCROLL_STATE_STOPPED);

                if (config.rippleEnabled) {
                    var ripples = container.querySelectorAll('.ak-ripple-ring');
                    ripples.forEach(function (r) { r.classList.remove('ripple-active'); });
                }

                container.classList.remove('is-scrolling', 'is-step-engine');
                hideSpeedControl();
            }

            function toggleScroll() {
                isScrolling ? stopScroll(false) : startScroll(false);
            }

            function showScrollTopButton() {
                if (!scrollTopBtn || !config.showScrollTop) return;
                clearScrollTopShowTimer();
                var delay = speedControl && speedControl.classList.contains('show') && shouldAnimateSpeedControl()
                    ? Math.max(SCROLL_TOP_DELAY_MS, getSpeedControlAnimationDuration())
                    : SCROLL_TOP_DELAY_MS;
                scrollTopShowTimer = setManagedTimeout(function () {
                    scrollTopShowTimer = null;
                    scrollTopBtn.classList.add('show');
                }, delay);
            }

            function hideScrollTopButton() {
                clearScrollTopShowTimer();
                if (scrollTopBtn) scrollTopBtn.classList.remove('show');
            }

            function updateProgress(force) {
                var progress = getScrollProgress();
                if (!force && lastProgress >= 0 && Math.abs(progress - lastProgress) < PROGRESS_UPDATE_MIN_DELTA) {
                    return;
                }
                lastProgress = progress;

                if (progressRing) {
                    var offset = CIRCUMFERENCE - (progress * CIRCUMFERENCE);
                    progressRing.style.strokeDashoffset = offset;

                    if (progressRingSvg && config.progressAnimation !== 'none') {
                        if (!progressRingSvg.classList.contains('progress-anim-' + config.progressAnimation)) {
                            progressRingSvg.className.baseVal = progressRingSvg.className.baseVal.replace(/\bprogress-anim-[a-z-]+\b/g, '').trim();
                            progressRingSvg.classList.add('progress-anim-' + config.progressAnimation);
                        }
                    }
                }

                if (progressBar) {
                    progressBar.style.transform = 'scaleX(' + progress + ')';
                }
            }

            function handleScrollToTop() {
                hideScrollTopButton();
                if (isScrolling) stopScroll(false);
                animateScrollToTop();
            }

            function animateScrollToTop() {
                cancelScrollTopAnimation();

                var startTop = window.scrollY || 0;
                if (startTop <= 0 || prefersReducedMotion) {
                    writeScrollPosition(0);
                    syncVirtualScrollPosition();
                    updateProgress(true);
                    return;
                }

                var startTime = 0;

                function tick(timestamp) {
                    if (!startTime) startTime = timestamp;

                    var progress = clamp((timestamp - startTime) / SCROLL_TOP_PROGRESS_MS, 0, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    var nextTop = Math.round(startTop * (1 - eased));

                    writeScrollPosition(nextTop);

                    if (progress < 1) {
                        scrollTopRAF = requestAnimationFrame(tick);
                        return;
                    }

                    scrollTopRAF = null;
                    syncVirtualScrollPosition();
                    updateProgress(true);
                }

                scrollTopRAF = requestAnimationFrame(tick);
            }

            function updateSliderProgress() {
                if (speedSlider) {
                    var value = parseInt(speedSlider.value);
                    var min = parseInt(speedSlider.min) || 1;
                    var max = parseInt(speedSlider.max) || 100;
                    var percent = ((value - min) / (max - min)) * 100;
                    speedSlider.style.setProperty('--slider-progress', percent + '%');
                }
            }

            function scheduleSliderProgressUpdate() {
                if (!speedSlider || speedUiRAF) return;

                speedUiRAF = requestAnimationFrame(function () {
                    speedUiRAF = null;
                    updateSliderProgress();
                });
            }

            function updateLimitState() {
                if (!speedSlider) return;
                var min = parseInt(speedSlider.min) || 1;
                var max = parseInt(speedSlider.max) || 100;
                if (speedMinus) {
                    speedMinus.classList.toggle('is-limit', currentSpeed <= min);
                }
                if (speedPlus) {
                    speedPlus.classList.toggle('is-limit', currentSpeed >= max);
                }
            }

            function updateSpeedFn(newSpeed, triggerElement) {
                newSpeed = parseInt(newSpeed, 10);
                if (!Number.isFinite(newSpeed)) return;

                newSpeed = clamp(newSpeed, 1, 100);
                currentSpeed = newSpeed;
                targetSpeed = newSpeed;

                if (speedSlider) {
                    if (parseInt(speedSlider.value, 10) !== currentSpeed) {
                        speedSlider.value = currentSpeed;
                    }
                    speedSlider.setAttribute('aria-valuenow', String(currentSpeed));
                    scheduleSliderProgressUpdate();
                }

                if (speedValue) {
                    speedValue.textContent = currentSpeed;
                    var animType = config.speedValueAnimation || 'pulse';
                    if (!prefersReducedMotion && animType !== 'none') {
                        if (speedValueAnimTimer) {
                            clearManagedTimeout(speedValueAnimTimer);
                        }
                        if (speedValueAnimRAF) {
                            cancelAnimationFrame(speedValueAnimRAF);
                        }
                        speedValue.classList.remove('anim-pulse', 'anim-fade', 'anim-slide', 'anim-bounce');
                        speedValueAnimRAF = requestAnimationFrame(function () {
                            speedValueAnimRAF = null;
                            speedValue.classList.add('anim-' + animType);
                        });
                        speedValueAnimTimer = setManagedTimeout(function () {
                            speedValue.classList.remove('anim-' + animType);
                            speedValueAnimTimer = null;
                        }, SPEED_VALUE_ANIM_MS);
                    }
                }

                updateLimitState();

                if (triggerElement) {
                    if (speedArrowActiveTimer) {
                        clearManagedTimeout(speedArrowActiveTimer);
                    }
                    if (speedMinus) speedMinus.classList.remove('active');
                    if (speedPlus) speedPlus.classList.remove('active');
                    triggerElement.classList.add('active');
                    speedArrowActiveTimer = setManagedTimeout(function () {
                        triggerElement.classList.remove('active');
                        speedArrowActiveTimer = null;
                    }, SPEED_ARROW_ACTIVE_MS);
                }

                if (isScrolling && !isPaused && !isStepScrollEngine()) {
                    queueScrollFrame();
                } else if (isScrolling && !isPaused && isStepScrollEngine() && stepScrollInterval && !stepScrollRAF) {
                    clearStepScrollTimer();
                    scheduleNextStepScrollStep();
                }
            }

            function changeSpeedBy(delta, triggerElement) {
                updateSpeedFn(currentSpeed + delta, triggerElement);
            }

            var suppressClick = false;
            var clickSuppressionTimer = null;
            var activePressId = null;
            var holdTimer = null;
            var holdActivated = false;

            function suppressCompatibilityClick() {
                suppressClick = true;
                if (clickSuppressionTimer) clearManagedTimeout(clickSuppressionTimer);
                clickSuppressionTimer = setManagedTimeout(function () {
                    suppressClick = false;
                    clickSuppressionTimer = null;
                }, 500);
            }

            function beginPress(id) {
                if (activePressId !== null) return;
                activePressId = id;
                holdActivated = false;
                holdTimer = setManagedTimeout(function () {
                    holdTimer = null;
                    holdActivated = true;
                    startScroll(false);
                }, HOLD_THRESHOLD_MS);
            }

            function endPress(id, cancelled) {
                if (activePressId !== id) return;
                clearManagedTimeout(holdTimer);
                holdTimer = null;
                var action = getPressReleaseAction(config.mode, holdActivated, cancelled);

                if (action === 'stop') stopScroll(false);
                else if (action === 'toggle') toggleScroll();

                if (!cancelled) suppressCompatibilityClick();
                activePressId = null;
                holdActivated = false;
            }

            function cancelActivePress() {
                if (activePressId !== null) endPress(activePressId, true);
            }

            addListener(btn, 'click', function () {
                if (suppressClick) {
                    suppressClick = false;
                    clearManagedTimeout(clickSuppressionTimer);
                    clickSuppressionTimer = null;
                    return;
                }
                if (config.mode === 'auto' || config.mode === 'both') {
                    toggleScroll();
                }
            });

            if (config.mode === 'manual' || config.mode === 'both') {
                if (window.PointerEvent) {
                    addListener(btn, 'pointerdown', function (e) {
                        if (e.button !== undefined && e.button !== 0) return;
                        if (e.pointerType !== 'mouse') e.preventDefault();
                        beginPress(e.pointerId);
                        if (btn.setPointerCapture) btn.setPointerCapture(e.pointerId);
                    }, { passive: false });

                    addListener(btn, 'pointerup', function (e) {
                        endPress(e.pointerId, false);
                        if (btn.hasPointerCapture && btn.hasPointerCapture(e.pointerId)) btn.releasePointerCapture(e.pointerId);
                    });
                    addListener(btn, 'pointercancel', function (e) { endPress(e.pointerId, true); });
                    addListener(btn, 'lostpointercapture', function (e) { endPress(e.pointerId, true); });
                } else {
                    addListener(btn, 'mousedown', function (e) {
                        if (e.button !== 0) return;
                        beginPress('mouse');
                    });
                    addListener(window, 'mouseup', function () { endPress('mouse', false); });
                    addListener(btn, 'touchstart', function (e) {
                        e.preventDefault();
                        beginPress('touch');
                    }, { passive: false });
                    addListener(btn, 'touchend', function () { endPress('touch', false); });
                    addListener(btn, 'touchcancel', function () { endPress('touch', true); });
                }

                addListener(window, 'blur', cancelActivePress);
            }

            addListener(btn, 'keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (e.repeat) return;
                    if (config.mode === 'auto' || config.mode === 'both') {
                        toggleScroll();
                    } else if (config.mode === 'manual') {
                        startScroll(false);
                    }
                }
            });

            if (config.mode === 'manual') {
                addListener(btn, 'keyup', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        stopScroll(false);
                    }
                });
            }

            if (speedSlider) {
                speedSlider.setAttribute('aria-valuenow', String(currentSpeed));
                updateSliderProgress();
                updateLimitState();

                var handleSpeedInput = function (e) {
                    var newSpeed = parseInt(e.target.value);
                    updateSpeedFn(newSpeed, null);
                };

                addListener(speedSlider, 'input', handleSpeedInput);
                addListener(speedSlider, 'change', handleSpeedInput);
                addListener(speedSlider, 'pointermove', function (e) {
                    if (e.buttons === 1) {
                        scheduleSliderProgressUpdate();
                    }
                });
            }

            if (speedMinus) {
                addListener(speedMinus, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentSpeed > 1) changeSpeedBy(-SPEED_STEP, this);
                });
            }

            if (speedPlus) {
                addListener(speedPlus, 'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentSpeed < 100) changeSpeedBy(SPEED_STEP, this);
                });
            }

            if (speedControl) {
                addListener(document, 'keydown', function (e) {
                    if (e.key === 'Escape' && speedControl.classList.contains('show')) {
                        stopScroll(false);
                    }
                });
            }

            if (config.speedDraggable && speedControl) {
                var isDragging = false;
                var dragPointerId = null;
                var dragStartX = 0;
                var dragStartY = 0;
                var controlStartX = 0;
                var controlStartY = 0;
                var dragHandle = speedControl.querySelector('.speed-drag-handle');
                var dragTarget = dragHandle || speedControl;

                function getViewportBounds() {
                    var viewport = window.visualViewport;
                    return {
                        left: (viewport ? viewport.offsetLeft : 0) + 8,
                        top: (viewport ? viewport.offsetTop : 0) + 8,
                        right: (viewport ? viewport.offsetLeft + viewport.width : window.innerWidth) - 8,
                        bottom: (viewport ? viewport.offsetTop + viewport.height : window.innerHeight) - 8
                    };
                }

                function positionSpeedControl(left, top) {
                    var bounds = getViewportBounds();
                    var rect = speedControl.getBoundingClientRect();
                    var containerRect = container.getBoundingClientRect();
                    var nextLeft = clamp(left, bounds.left, Math.max(bounds.left, bounds.right - rect.width));
                    var nextTop = clamp(top, bounds.top, Math.max(bounds.top, bounds.bottom - rect.height));

                    speedControl.style.left = (nextLeft - containerRect.left) + 'px';
                    speedControl.style.top = (nextTop - containerRect.top) + 'px';
                    speedControl.style.right = 'auto';
                    speedControl.style.bottom = 'auto';
                    speedControl.style.setProperty('--ak-speed-control-x', '0px');
                    speedControl.style.setProperty('--ak-speed-control-y', '0px');
                    speedControl.style.transform = 'none';
                }

                function beginDrag(clientX, clientY, pointerId) {
                    isDragging = true;
                    dragPointerId = pointerId;
                    pauseScroll('control', false);
                    speedControl.classList.add('is-dragging');
                    dragStartX = clientX;
                    dragStartY = clientY;
                    var rect = speedControl.getBoundingClientRect();
                    controlStartX = rect.left;
                    controlStartY = rect.top;
                }

                function moveDrag(clientX, clientY) {
                    if (!isDragging) return;
                    positionSpeedControl(
                        controlStartX + clientX - dragStartX,
                        controlStartY + clientY - dragStartY
                    );
                }

                function endDrag(pointerId) {
                    if (!isDragging || (dragPointerId !== null && pointerId !== dragPointerId)) return;
                    isDragging = false;
                    dragPointerId = null;
                    speedControl.classList.remove('is-dragging');
                    resumeScroll('control');
                }

                function reclampSpeedControl() {
                    if (!speedControl.style.left && !speedControl.style.top) return;
                    var rect = speedControl.getBoundingClientRect();
                    positionSpeedControl(rect.left, rect.top);
                }

                if (window.PointerEvent) {
                    addListener(dragTarget, 'pointerdown', function (e) {
                        if (e.button !== undefined && e.button !== 0) return;
                        if (e.target.closest('.apeiron-speed-slider') || e.target.closest('.speed-arrow')) return;
                        e.preventDefault();
                        beginDrag(e.clientX, e.clientY, e.pointerId);
                        if (dragTarget.setPointerCapture) dragTarget.setPointerCapture(e.pointerId);
                    }, { passive: false });
                    addListener(dragTarget, 'pointermove', function (e) {
                        if (e.pointerId === dragPointerId) moveDrag(e.clientX, e.clientY);
                    });
                    addListener(dragTarget, 'pointerup', function (e) { endDrag(e.pointerId); });
                    addListener(dragTarget, 'pointercancel', function (e) { endDrag(e.pointerId); });
                    addListener(dragTarget, 'lostpointercapture', function (e) { endDrag(e.pointerId); });
                } else {
                    addListener(dragTarget, 'mousedown', function (e) {
                        if (e.button !== 0 || e.target.closest('.apeiron-speed-slider') || e.target.closest('.speed-arrow')) return;
                        e.preventDefault();
                        beginDrag(e.clientX, e.clientY, 'mouse');
                    });
                    addListener(document, 'mousemove', function (e) { moveDrag(e.clientX, e.clientY); });
                    addListener(document, 'mouseup', function () { endDrag('mouse'); });
                    addListener(dragTarget, 'touchstart', function (e) {
                        if (e.target.closest('.apeiron-speed-slider') || e.target.closest('.speed-arrow')) return;
                        e.preventDefault();
                        beginDrag(e.touches[0].clientX, e.touches[0].clientY, 'touch');
                    }, { passive: false });
                    addListener(document, 'touchmove', function (e) {
                        if (!isDragging) return;
                        e.preventDefault();
                        moveDrag(e.touches[0].clientX, e.touches[0].clientY);
                    }, { passive: false });
                    addListener(document, 'touchend', function () { endDrag('touch'); });
                    addListener(document, 'touchcancel', function () { endDrag('touch'); });
                }

                addListener(window, 'resize', reclampSpeedControl, { passive: true });
                if (window.visualViewport) {
                    addListener(window.visualViewport, 'resize', reclampSpeedControl, { passive: true });
                    addListener(window.visualViewport, 'scroll', reclampSpeedControl, { passive: true });
                }
            }

            if (scrollTopBtn) {
                addListener(scrollTopBtn, 'click', handleScrollToTop);
            }

            if (config.pauseOnHover) {
                var hoverPauseTarget = btnContainer || btn;

                addListener(hoverPauseTarget, 'pointerenter', function () {
                    pauseScroll('hover', false);
                });

                addListener(hoverPauseTarget, 'pointerleave', function () {
                    resumeScroll('hover');
                });
            }

            if (config.pauseOnInteraction || config.userInteractionBehavior === 'stop_on_interaction') {
                addListener(window, 'wheel', handleUserScrollIntent, { passive: true });
                addListener(window, 'touchmove', handleUserScrollIntent, { passive: true });
                addListener(window, 'keydown', handleInteractionKeydown);
            }

            if (config.userInteractionBehavior === 'stop_on_interaction') {
                if (window.PointerEvent) {
                    addListener(document, 'pointerdown', handleInteractivePointer, { capture: true, passive: true });
                } else {
                    addListener(document, 'mousedown', handleInteractivePointer, { capture: true, passive: true });
                    addListener(document, 'touchstart', handleInteractivePointer, { capture: true, passive: true });
                }
                addListener(document, 'focusin', handleInteractionFocus);
                addListener(window, 'keyup', function (e) {
                    if (e.key === 'Tab') keyboardFocusIntent = false;
                });
                addListener(window, 'blur', function () {
                    keyboardFocusIntent = false;
                });
            }

            var pendingExternalScroll = false;
            addListener(window, 'scroll', function () {
                var isOwnedScroll = isProgrammaticScrollEvent();
                if (isOwnedScroll && isAutoScrolling) return;
                if (!isOwnedScroll) pendingExternalScroll = true;
                if (scrollUpdateRAF) return;

                scrollUpdateRAF = requestAnimationFrame(function () {
                    scrollUpdateRAF = null;

                    if (pendingExternalScroll) {
                        pendingExternalScroll = false;
                        syncVirtualScrollPosition();
                        updateProgress(true);
                        lastTime = 0;
                        lastRenderTime = 0;

                        if (isScrolling && config.pauseOnInteraction) {
                            if (config.resumeAfterIdle) pauseScroll('interaction', true);
                            else stopScroll(false);
                        }
                    } else if (!isAutoScrolling) {
                        syncVirtualScrollPosition();
                        updateProgress(true);
                    }

                    if (scrollTopBtn && config.showScrollTop && config.scrollTopShowAfter > 0) {
                        var progress = getScrollProgress();
                        if (progress >= config.scrollTopShowAfter && !isScrolling) {
                            scrollTopBtn.classList.add('show');
                        } else if (progress < config.scrollTopShowAfter && !scrollCompleted) {
                            scrollTopBtn.classList.remove('show');
                        }
                    }
                });
            }, { passive: true });

            function isElementorEditor() {
                return config.isEditor ||
                    (document.body && document.body.classList.contains('elementor-editor-active')) ||
                    /(?:^|[?&])elementor-preview=/.test(window.location.search || '');
            }

            if (typeof MutationObserver !== 'undefined' && document.body) {
                bodyObserver = new MutationObserver(function () {
                    if (isElementorEditor()) {
                        if (isScrolling) stopScroll(false);
                    } else if (autoStartPending) {
                        attemptAutoStart();
                    }
                });
                bodyObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
            }

            if (shouldAutoStart(config, prefersReducedMotion) && !isElementorEditor()) {
                addListener(document, 'apeiron:cover:opened', function () {
                    if (autoStartPending) attemptAutoStart();
                });
                addListener(document, 'apeiron:content:loaded', function () {
                    if (autoStartPending) attemptAutoStart();
                });
                autoStartTimer = setManagedTimeout(function () {
                    autoStartTimer = null;
                    attemptAutoStart();
                }, getAutoStartDelay());
            }

            if (progressRing) progressRing.style.strokeDasharray = CIRCUMFERENCE;
            setScrollState(SCROLL_STATE_IDLE);
            updateButtonState(SCROLL_STATE_IDLE);
            updateProgress(true);

            function destroyInstance() {
                cancelActivePress();
                stopScroll(false, true);
                clearManagedTimers();

                if (scrollRAF) {
                    cancelAnimationFrame(scrollRAF);
                    scrollRAF = null;
                }

                if (scrollUpdateRAF) {
                    cancelAnimationFrame(scrollUpdateRAF);
                    scrollUpdateRAF = null;
                }

                if (controller) {
                    controller.abort();
                }

                listenerCleanups.splice(0).forEach(function (cleanup) {
                    cleanup();
                });

                if (bodyObserver) {
                    bodyObserver.disconnect();
                    bodyObserver = null;
                }

                if (pageMetricsCleanup) {
                    pageMetricsCleanup();
                    pageMetricsCleanup = null;
                }

                if (reducedMotionCleanup) {
                    reducedMotionCleanup();
                    reducedMotionCleanup = null;
                }

            }

            function storeInstance() {
                container._apeironAutoScroll = {
                    container: container,
                    destroy: destroyInstance,
                    start: startScroll,
                    stop: stopScroll,
                    pause: pauseScroll,
                    resume: resumeScroll,
                    getState: function () {
                        return scrollState;
                    },
                    controller: controller,
                    autoStartTimer: autoStartTimer,
                    bodyObserver: bodyObserver,
                    contentObserver: null,
                    resizeObserver: null
                };
            }
            storeInstance();
        }
    };

    var elementorHookRegistered = false;
    var detachObserver = null;
    var pruneRAF = null;

    function registerElementorHook() {
        if (elementorHookRegistered) return;
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }

        elementorHookRegistered = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-autoscroll.default',
            function (scope) {
                findContainers(scope).forEach(function (container) {
                    AutoScroll.destroyWidget(container);
                });
                AutoScroll.init(scope);
            }
        );
    }

    function observeDetachments() {
        if (detachObserver || typeof MutationObserver === 'undefined' || !document.body) return;

        detachObserver = new MutationObserver(function (records) {
            var hasRemovedNodes = records.some(function (record) {
                return record.removedNodes && record.removedNodes.length > 0;
            });
            if (!hasRemovedNodes || pruneRAF) return;

            pruneRAF = requestAnimationFrame(function () {
                pruneRAF = null;
                AutoScroll.pruneDetached();
            });
        });
        detachObserver.observe(document.body, { childList: true, subtree: true });
    }

    function bootstrap(scope) {
        AutoScroll.init(scope || document);
        observeDetachments();
        registerElementorHook();
    }

    if (window.ApeironAutoScrollTestMode) {
        window.ApeironAutoScrollTestApi = {
            findContainers: findContainers,
            getPressReleaseAction: getPressReleaseAction,
            normalizeConfig: normalizeConfig,
            shouldAutoStart: shouldAutoStart
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bootstrap(document); }, { once: true });
    } else {
        bootstrap(document);
    }

    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();
}());
