(function () {
	'use strict';

	var instances = new WeakMap();
	var initialized = new Set();
	var scrollLocks = new Set();
	var scrollEventsBound = false;
	var scrollLockObserver = null;
	var resizeFrame = null;
	var scrollKeys = {
		ArrowDown: true,
		ArrowLeft: true,
		ArrowRight: true,
		ArrowUp: true,
		End: true,
		Home: true,
		PageDown: true,
		PageUp: true,
		' ': true,
		Spacebar: true
	};

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function getInt(el, attr, fallback, min, max, legacy) {
		var value = parseInt(getAttribute(el, attr, legacy), 10);
		if (isNaN(value)) {
			value = fallback;
		}
		return clamp(value, min, max);
	}

	function getAttribute(el, canonical, legacy) {
		var value = el.getAttribute(canonical);
		if (value === null && legacy) {
			value = el.getAttribute(legacy);
		}

		return value;
	}

	function isEditor(el) {
		return getAttribute(el, 'data-apeiron-cover-editor-preview', 'data-editor-preview') === 'yes' ||
			/(?:^|[?&])elementor-preview=/.test(window.location.search || '');
	}

	function isDeviceAllowed(el) {
		var width = window.innerWidth || document.documentElement.clientWidth || 1200;
		if (width >= 1025) {
			return getAttribute(el, 'data-apeiron-cover-show-desktop', 'data-show-desktop') !== 'no';
		}
		if (width >= 768) {
			return getAttribute(el, 'data-apeiron-cover-show-tablet', 'data-show-tablet') !== 'no';
		}
		return getAttribute(el, 'data-apeiron-cover-show-mobile', 'data-show-mobile') !== 'no';
	}

	function getStorage(key) {
		try {
			return window.localStorage ? localStorage.getItem(key) : null;
		} catch (e) {
			return null;
		}
	}

	function setStorage(key, value) {
		try {
			if (window.localStorage) {
				localStorage.setItem(key, value);
			}
		} catch (e) {}
	}

	function preventScrollEvent(event) {
		if (scrollLocks.size > 0) {
			event.preventDefault();
		}
	}

	function preventScrollKey(event) {
		var target = event.target;
		var tagName = target && target.tagName ? target.tagName.toLowerCase() : '';
		var isEditable = target && (
			target.isContentEditable ||
			tagName === 'input' ||
			tagName === 'textarea' ||
			tagName === 'select'
		);

		if (!isEditable && scrollLocks.size > 0 && scrollKeys[event.key]) {
			event.preventDefault();
		}
	}

	function bindScrollEvents() {
		if (scrollEventsBound) {
			return;
		}

		scrollEventsBound = true;
		document.addEventListener('wheel', preventScrollEvent, { passive: false, capture: true });
		document.addEventListener('touchmove', preventScrollEvent, { passive: false, capture: true });
		document.addEventListener('keydown', preventScrollKey, true);
	}

	function unbindScrollEvents() {
		if (!scrollEventsBound || scrollLocks.size > 0) {
			return;
		}

		scrollEventsBound = false;
		document.removeEventListener('wheel', preventScrollEvent, true);
		document.removeEventListener('touchmove', preventScrollEvent, true);
		document.removeEventListener('keydown', preventScrollKey, true);
	}

	function pruneScrollLocks() {
		scrollLocks.forEach(function (el) {
			if (!document.documentElement.contains(el)) {
				scrollLocks.delete(el);
			}
		});

		applyScrollLockState();
	}

	function ensureScrollLockObserver() {
		if (scrollLockObserver || typeof MutationObserver === 'undefined') {
			return;
		}

		scrollLockObserver = new MutationObserver(pruneScrollLocks);
		scrollLockObserver.observe(document.documentElement, {
			childList: true,
			subtree: true
		});
	}

	function disconnectScrollLockObserver() {
		if (!scrollLockObserver || scrollLocks.size > 0) {
			return;
		}

		scrollLockObserver.disconnect();
		scrollLockObserver = null;
	}

	function applyScrollLockState() {
		var locked = scrollLocks.size > 0;
		document.documentElement.classList.toggle('apeiron-cover-scroll-lock', locked);
		if (document.body) {
			document.body.classList.toggle('apeiron-cover-scroll-lock', locked);
		}
		if (locked) {
			bindScrollEvents();
			ensureScrollLockObserver();
		} else {
			unbindScrollEvents();
			disconnectScrollLockObserver();
		}
	}

	function lockScroll(el) {
		if (!el || getAttribute(el, 'data-apeiron-cover-lock-scroll', 'data-lock-scroll') !== 'yes' || isEditor(el)) {
			return;
		}
		scrollLocks.add(el);
		applyScrollLockState();
	}

	function unlockScroll(el) {
		if (!el) {
			return;
		}
		scrollLocks.delete(el);
		applyScrollLockState();
	}

	function getGuestName(el) {
		var fallbackNode = el.querySelector('[data-apeiron-cover-recipient]');
		var fallback = fallbackNode ? fallbackNode.textContent : '';
		var param = getAttribute(el, 'data-apeiron-cover-guest-parameter', 'data-guest-parameter') || 'to';
		var name = '';

		try {
			var params = new URLSearchParams(window.location.search || '');
			name = params.get(param) || '';
		} catch (e) {}

		if (!name) {
			try {
				var marker = '/' + param + '/';
				var path = window.location.pathname || '';
				var idx = path.indexOf(marker);
				if (idx !== -1) {
					name = path.slice(idx + marker.length).split('/')[0] || '';
				}
			} catch (e) {}
		}

		if (name) {
			try {
				name = decodeURIComponent(String(name).replace(/\+/g, ' '));
			} catch (e) {}
			name = name.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '').trim();
		}

		return name || fallback || '';
	}

	function fillRecipient(el) {
		if (getAttribute(el, 'data-apeiron-cover-auto-recipient', 'data-auto-recipient') !== 'yes') {
			return;
		}

		var node = el.querySelector('[data-apeiron-cover-recipient]');
		if (!node) {
			return;
		}

		node.textContent = getGuestName(el);
	}

	function clearTimers(state) {
		if (!state || !state.timers) {
			return;
		}

		state.timers.forEach(function (timer) {
			window.clearTimeout(timer);
		});
		state.timers = [];
		if (state.infoExitFrame && window.cancelAnimationFrame) {
			window.cancelAnimationFrame(state.infoExitFrame);
		}
		state.infoExitFrame = 0;

		if (state.infoTransitionTarget && state.infoTransitionHandler) {
			state.infoTransitionTarget.removeEventListener('transitionend', state.infoTransitionHandler);
		}
		state.infoTransitionTarget = null;
		state.infoTransitionHandler = null;
	}

	function schedule(el, state, callback, delay) {
		state.timers.push(window.setTimeout(function () {
			if (el.isConnected === false) {
				destroy(el);
				return;
			}

			callback();
		}, delay));
	}

	function runAfterOpen(el) {
		var eventName = getAttribute(el, 'data-apeiron-cover-event-name', 'data-event-name') || 'apeiron:cover:opened';
		var clickSelector = (getAttribute(el, 'data-apeiron-cover-click-selector', 'data-click-selector') || '').trim();
		var scrollSelector = (getAttribute(el, 'data-apeiron-cover-scroll-selector', 'data-scroll-selector') || '').trim();
		var scrollBehavior = getAttribute(el, 'data-apeiron-cover-scroll-behavior', 'data-scroll-behavior') || 'smooth';

		try {
			document.dispatchEvent(new CustomEvent(eventName, {
				detail: {
					cover: el,
					id: el.id || ''
				}
			}));
		} catch (e) {}

		if (clickSelector) {
			try {
				var clickTarget = document.querySelector(clickSelector);
				if (clickTarget && typeof clickTarget.click === 'function') {
					clickTarget.click();
				}
			} catch (e) {}
		}

		if (scrollSelector) {
			try {
				var scrollTarget = document.querySelector(scrollSelector);
				if (scrollTarget && typeof scrollTarget.scrollIntoView === 'function') {
					scrollTarget.scrollIntoView({
						behavior: scrollBehavior === 'auto' ? 'auto' : 'smooth',
						block: 'start'
					});
				}
			} catch (e) {}
		}
	}

	function complete(el) {
		var state = instances.get(el);
		if (state) {
			clearTimers(state);
			state.opened = true;
		}

		unlockScroll(el);
		el.setAttribute('aria-hidden', 'true');
		el.classList.add('is-complete');

		if (!isEditor(el) && getAttribute(el, 'data-apeiron-cover-first-visit-only', 'data-first-visit-only') === 'yes') {
			setStorage(getAttribute(el, 'data-apeiron-cover-storage-key', 'data-storage-key') || 'apeiron_cover_opened', '1');
		}

		if (state && state.previousFocus && typeof state.previousFocus.focus === 'function' && document.documentElement.contains(state.previousFocus)) {
			state.previousFocus.focus();
		}

		runAfterOpen(el);
	}

	function startOpeningSequence(el, state) {
		var info = el.querySelector('.apeiron-cover__info');
		var visualParts = info ? info.querySelectorAll('.apeiron-cover__ribbon, .apeiron-cover__pin, .apeiron-cover__recipient-card, .apeiron-cover__art--tail') : [];
		var infoDuration = state.infoDuration;
		var panelPause = state.reducedMotion ? 0 : 500;
		var transitionDone = false;
		var transitionHandler = null;

		var startPanels = function () {
			var releaseAt = state.panelDuration + state.openDelay;

			el.classList.add('is-panels-opening');
			schedule(el, state, function () {
				el.classList.add('is-releasing');
			}, releaseAt);
			schedule(el, state, function () {
				complete(el);
			}, releaseAt + state.exitDuration);
		};

		var finishInfoTransition = function () {
			if (transitionDone) {
				return;
			}

			transitionDone = true;
			if (state.infoExitFrame && window.cancelAnimationFrame) {
				window.cancelAnimationFrame(state.infoExitFrame);
			}
			state.infoExitFrame = 0;
			if (info && transitionHandler) {
				info.removeEventListener('transitionend', transitionHandler);
			}
			state.infoTransitionTarget = null;
			state.infoTransitionHandler = null;

			if (instances.get(el) !== state) {
				return;
			}

			el.classList.add('is-info-hidden');
			schedule(el, state, startPanels, panelPause);
		};
		var watchInfoExit = function () {
			var viewportHeight;
			var hasVisiblePart;

			if (transitionDone || instances.get(el) !== state) {
				return;
			}

			viewportHeight = window.innerHeight || document.documentElement.clientHeight;
			hasVisiblePart = Array.prototype.some.call(visualParts, function (part) {
				var rect = part.getBoundingClientRect();
				return rect.bottom > 0 && rect.top < viewportHeight;
			});

			if (!hasVisiblePart) {
				finishInfoTransition();
				return;
			}

			state.infoExitFrame = window.requestAnimationFrame(watchInfoExit);
		};

		if (!info) {
			finishInfoTransition();
			return;
		}

		transitionHandler = function (event) {
			if (event.target === info && event.propertyName === 'transform') {
				finishInfoTransition();
			}
		};
		state.infoTransitionTarget = info;
		state.infoTransitionHandler = transitionHandler;
		info.addEventListener('transitionend', transitionHandler);

		// Guard against transitionend being suppressed by browser or CSS changes.
		schedule(el, state, finishInfoTransition, infoDuration + 100);
		if (visualParts.length && window.requestAnimationFrame) {
			state.infoExitFrame = window.requestAnimationFrame(watchInfoExit);
		}
	}

	function openCover(el) {
		var state = instances.get(el);
		if (!state || state.opening || state.opened) {
			return;
		}

		state.opening = true;

		// Dispatched synchronously on the click stack so listeners still hold the
		// user activation that browsers require to start audio.
		if (!isEditor(el)) {
			try {
				document.dispatchEvent(new CustomEvent('apeiron:cover:opening', {
					detail: {
						cover: el,
						id: el.id || '',
						openedEventName: getAttribute(el, 'data-apeiron-cover-event-name', 'data-event-name') || 'apeiron:cover:opened'
					}
				}));
			} catch (e) {}
		}

		startOpeningSequence(el, state);
		el.classList.add('is-opening');
	}

	function hideNow(el) {
		unlockScroll(el);
		el.setAttribute('aria-hidden', 'true');
		el.classList.add('is-complete');
	}

	function destroy(el) {
		var state = instances.get(el);
		if (!state) {
			unlockScroll(el);
			return;
		}

		clearTimers(state);
		if (state.button && state.buttonHandler) {
			state.button.removeEventListener('click', state.buttonHandler);
		}
		if (state.escapeHandler) {
			document.removeEventListener('keydown', state.escapeHandler, true);
		}
		if (state.focusHandler) {
			document.removeEventListener('keydown', state.focusHandler, true);
		}
		unlockScroll(el);
		instances.delete(el);
		initialized.delete(el);
	}

	function markReady(el) {
		// Gentle entrance: defer one frame so the initial state can paint.
		if (typeof window.requestAnimationFrame === 'function') {
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(function () {
					el.classList.add('is-ready');
					if (window.ApeironCoverBoot) {
						window.ApeironCoverBoot.release();
					}
				});
			});
		} else {
			el.classList.add('is-ready');
			if (window.ApeironCoverBoot) {
				window.ApeironCoverBoot.release();
			}
		}
	}

	function initElement(el) {
		if (!el || !el.getAttribute || el.getAttribute('data-apeiron-cover') !== 'yes') {
			return;
		}

		// Elementor rerenders replace the element, so drop detached instances
		// before binding new document-level listeners.
		initialized.forEach(function (node) {
			if (!document.documentElement.contains(node)) {
				destroy(node);
			}
		});
		destroy(el);
		fillRecipient(el);

		el.classList.remove('is-ready', 'is-opening', 'is-info-hidden', 'is-panels-opening', 'is-releasing', 'is-complete', 'is-device-hidden');
		el.removeAttribute('aria-hidden');

		if (!isEditor(el)) {
			if (!isDeviceAllowed(el)) {
				el.classList.add('is-device-hidden');
				hideNow(el);
				return;
			}

			if (
				getAttribute(el, 'data-apeiron-cover-first-visit-only', 'data-first-visit-only') === 'yes' &&
				getStorage(getAttribute(el, 'data-apeiron-cover-storage-key', 'data-storage-key') || 'apeiron_cover_opened') === '1'
			) {
				hideNow(el);
				return;
			}
		}

		var state = {
			infoDuration: getInt(el, 'data-apeiron-cover-info-duration', 4100, 20, 5000, 'data-info-duration'),
			panelDuration: getInt(el, 'data-apeiron-cover-panel-duration', 3650, 20, 5000, 'data-panel-duration'),
			exitDuration: getInt(el, 'data-apeiron-cover-exit-duration', 2150, 0, 6000, 'data-exit-duration'),
			openDelay: getInt(el, 'data-apeiron-cover-open-delay', 150, 0, 2000, 'data-open-delay'),
			timers: [],
			infoExitFrame: 0,
			opening: false,
			opened: false,
			reducedMotion: false,
			previousFocus: document.activeElement
		};

		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			state.reducedMotion = true;
			state.infoDuration = 20;
			state.panelDuration = 20;
			state.exitDuration = Math.min(state.exitDuration, 80);
			state.openDelay = 0;
		}

		instances.set(el, state);
		initialized.add(el);
		lockScroll(el);
		markReady(el);

		var button = el.querySelector('.apeiron-cover__open-button');
		if (button) {
			button.disabled = false;

			state.button = button;
			state.buttonHandler = function () {
				openCover(el);
			};
			button.addEventListener('click', state.buttonHandler);
		}

		if (getAttribute(el, 'data-apeiron-cover-close-on-escape', 'data-close-on-escape') === 'yes') {
			state.escapeHandler = function (event) {
				if (event.key === 'Escape') {
					openCover(el);
				}
			};
			document.addEventListener('keydown', state.escapeHandler, true);
		}

		state.focusHandler = function (event) {
			if ('Tab' !== event.key || el.classList.contains('is-complete')) {
				return;
			}

			var focusable = Array.prototype.slice.call(
				el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
			).filter(function (node) {
				return !node.disabled && node.getAttribute('aria-hidden') !== 'true';
			});

			if (focusable.length === 0) {
				event.preventDefault();
				return;
			}

			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (!el.contains(document.activeElement)) {
				event.preventDefault();
				first.focus();
			} else if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		};
		document.addEventListener('keydown', state.focusHandler, true);
	}

	function initScope(scope) {
		var root = scope && scope.jquery ? scope[0] : (scope || document);
		if (!root) {
			return;
		}

		var covers = [];
		if (root.matches && root.matches('[data-apeiron-cover="yes"]')) {
			covers.push(root);
		}
		if (root.querySelectorAll) {
			covers = covers.concat(Array.prototype.slice.call(root.querySelectorAll('[data-apeiron-cover="yes"]')));
		}

		covers.forEach(initElement);
	}

	function handleResize() {
		if (resizeFrame !== null) {
			return;
		}

		var refresh = function () {
			resizeFrame = null;
			document.querySelectorAll('[data-apeiron-cover="yes"]').forEach(function (el) {
				if (isEditor(el)) {
					return;
				}

				if (isDeviceAllowed(el)) {
					if (el.classList.contains('is-device-hidden')) {
						initElement(el);
					}
					return;
				}

				if (!el.classList.contains('is-complete')) {
					destroy(el);
					el.classList.add('is-device-hidden');
					hideNow(el);
				}
			});
		};

		resizeFrame = typeof window.requestAnimationFrame === 'function'
			? window.requestAnimationFrame(refresh)
			: window.setTimeout(refresh, 50);
	}

	var api = {
		init: function () {
			initScope(document);
		},
		initScope: initScope,
		open: openCover,
		destroy: destroy
	};

	window.ApeironCover = api;
	window.addEventListener('resize', handleResize);

	if (window.ApeironCoverTestMode) {
		window.ApeironCoverTestApi = Object.freeze({
			clamp: clamp,
			getAttribute: getAttribute,
			getInt: getInt,
			isDeviceAllowed: isDeviceAllowed,
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', api.init);
	} else {
		api.init();
	}

	document.addEventListener('apeiron:content:loaded', function (event) {
		initScope(event.target || document);
	});

	window.addEventListener('elementor/frontend/init', function () {
		if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
			return;
		}

		elementorFrontend.hooks.addAction(
			'frontend/element_ready/apeiron-cover.default',
			initScope
		);
	});
}());
