(function () {
	'use strict';

	var SELECTOR = Object.freeze({
		root: '[data-apeiron-wa-form]',
		form: 'form',
		notice: '[data-apeiron-signal-notice]',
		widget: '.elementor-widget-apeiron-signal-form',
	});
	var DEFAULT_MESSAGES = Object.freeze({
		success: 'WhatsApp akan terbuka untuk mengirim pesan.',
		validation: 'Lengkapi field yang wajib diisi.',
		invalidTemplate: 'Ada token pesan yang belum cocok dengan field form.',
		invalidPhone: 'Nomor WhatsApp tujuan belum valid.',
		popupBlocked: 'WhatsApp tidak dapat dibuka otomatis. Klik tautan berikut untuk melanjutkan.',
	});
	var instances = new WeakMap();

	function escapeRegExp(value) {
		return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function normalizeMessage(message) {
		return String(message || '')
			.replace(/[ \t]{2,}/g, ' ')
			.replace(/\s+([.,!?;:])/g, '$1')
			.trim();
	}

	function getNotice(wrapper) {
		var notice = wrapper.querySelector(SELECTOR.notice);

		if (!notice) {
			notice = document.createElement('div');
			notice.className = 'apeiron-signal-form__notice';
			notice.setAttribute('data-apeiron-signal-notice', '');
			notice.setAttribute('role', 'status');
			notice.setAttribute('aria-live', 'polite');
			notice.setAttribute('aria-atomic', 'true');
			notice.hidden = true;

			var form = wrapper.querySelector(SELECTOR.form);
			if (form) {
				form.appendChild(notice);
			}
		}

		return notice;
	}

	function showNotice(wrapper, type, message, link) {
		var notice = getNotice(wrapper);
		notice.className = 'apeiron-signal-form__notice is-' + type;
		notice.hidden = false;

		if (link) {
			notice.textContent = '';

			var text = document.createElement('span');
			text.textContent = String(message || '') + ' ';
			var anchor = document.createElement('a');
			anchor.href = link;
			anchor.target = '_blank';
			anchor.rel = 'noopener noreferrer';
			anchor.textContent = 'Buka WhatsApp';
			notice.appendChild(text);
			notice.appendChild(anchor);
			return;
		}

		notice.textContent = String(message || '');
	}

	function hideNotice(wrapper) {
		var notice = getNotice(wrapper);
		notice.hidden = true;
		notice.textContent = '';
		notice.className = 'apeiron-signal-form__notice';
	}

	function parseConfig(wrapper) {
		try {
			var raw = wrapper.getAttribute('data-apeiron-wa-form') || '{}';
			var parsed = JSON.parse(raw);
			return parsed && typeof parsed === 'object' ? parsed : {};
		} catch (error) {
			return {};
		}
	}

	function replaceToken(template, token, value) {
		if (!token) {
			return template;
		}

		return String(template || '').replace(
			new RegExp('\\[' + escapeRegExp(token) + '\\]', 'gi'),
			function () {
				return String(value || '');
			}
		);
	}

	function findTokens(template) {
		return Array.from(
			new Set(
				String(template || '').match(/\[[a-z0-9_.\-\s]+\]/gi) || []
			)
		);
	}

	function buildMessage(form, config) {
		var formData = new FormData(form);
		var template = String(config.template || '');

		formData.forEach(function (value, key) {
			template = replaceToken(template, key, String(value).trim());
		});

		(Array.isArray(config.fields) ? config.fields : []).forEach(function (field) {
			if (!field || typeof field !== 'object') {
				return;
			}

			var key = String(field.key || '');
			var name = String(field.name || '');
			var label = String(field.label || '');
			var value = String(formData.get(key) || '').trim();
			template = replaceToken(template, key, value);
			template = replaceToken(template, name, value);
			template = replaceToken(template, label, value);
		});

		return {
			message: normalizeMessage(template),
			unknownTokens: findTokens(template),
		};
	}

	function isValidWhatsAppPhone(phone) {
		return /^[1-9]\d{7,14}$/.test(phone);
	}

	function isControlValid(control) {
		if (!control.willValidate || !control.checkValidity()) {
			return !control.willValidate;
		}

		var tagName = String(control.tagName || '').toLowerCase();
		var type = String(control.type || '').toLowerCase();
		var isTextControl = tagName === 'textarea' || (
			tagName === 'input' && ['text', 'search', 'email', 'tel', 'url', 'password'].indexOf(type) !== -1
		);

		return !control.required || !isTextControl || String(control.value || '').trim() !== '';
	}

	function syncInvalidState(form) {
		var firstInvalid = null;

		form.querySelectorAll('input, textarea, select').forEach(function (control) {
			if (!isControlValid(control)) {
				control.setAttribute('aria-invalid', 'true');
				firstInvalid = firstInvalid || control;
			} else {
				control.removeAttribute('aria-invalid');
			}
		});

		return firstInvalid;
	}

	function setLoading(wrapper, state, loading) {
		wrapper.classList.toggle('is-loading', loading);
		wrapper.setAttribute('aria-busy', loading ? 'true' : 'false');
		if (state.button) {
			state.button.disabled = loading;
		}
	}

	function handleSubmit(wrapper, state, event) {
		event.preventDefault();
		hideNotice(wrapper);
		state.validationNoticeActive = false;

		var firstInvalid = syncInvalidState(state.form);
		if (firstInvalid) {
			state.validationNoticeActive = true;
			showNotice(wrapper, 'error', state.config.validationMessage || DEFAULT_MESSAGES.validation);
			state.form.reportValidity();
			if (typeof firstInvalid.focus === 'function') {
				firstInvalid.focus();
			}
			return;
		}

		var phone = String(state.config.phone || '').replace(/\D/g, '');
		if (!isValidWhatsAppPhone(phone)) {
			showNotice(wrapper, 'error', state.config.invalidPhoneMessage || DEFAULT_MESSAGES.invalidPhone);
			return;
		}

		var result = buildMessage(state.form, state.config);
		if (result.unknownTokens.length) {
			showNotice(
				wrapper,
				'error',
				(state.config.invalidTemplateMessage || DEFAULT_MESSAGES.invalidTemplate) + ' ' + result.unknownTokens.join(', ')
			);
			return;
		}

		if (!result.message) {
			showNotice(wrapper, 'error', state.config.invalidTemplateMessage || DEFAULT_MESSAGES.invalidTemplate);
			return;
		}

		var waUrl = 'https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(result.message);
		var opened = null;
		setLoading(wrapper, state, true);

		try {
			opened = window.open(waUrl, '_blank', 'noopener,noreferrer');
		} catch (error) {
			opened = null;
		}

		setLoading(wrapper, state, false);

		if (!opened) {
			showNotice(wrapper, 'error', DEFAULT_MESSAGES.popupBlocked, waUrl);
			return;
		}

		try {
			opened.opener = null;
		} catch (error) {
			// Some browsers block access after cross-origin navigation.
		}

		showNotice(wrapper, 'success', state.config.successMessage || DEFAULT_MESSAGES.success);
	}

	function destroyWrapper(wrapper) {
		var state = instances.get(wrapper);
		if (!state) {
			return;
		}

		state.form.removeEventListener('submit', state.submitHandler);
		state.form.removeEventListener('input', state.inputHandler);
		setLoading(wrapper, state, false);
		wrapper.removeAttribute('aria-busy');
		instances.delete(wrapper);
	}

	function initWrapper(wrapper) {
		if (!wrapper || !wrapper.getAttribute || !wrapper.matches || !wrapper.matches(SELECTOR.root)) {
			return;
		}

		var form = wrapper.querySelector(SELECTOR.form);
		if (!form) {
			return;
		}
		var button = form.querySelector('button[type="submit"]');
		var rawConfig = wrapper.getAttribute('data-apeiron-wa-form') || '{}';
		var current = instances.get(wrapper);
		if (current && current.form === form && current.button === button && current.rawConfig === rawConfig) {
			return;
		}

		destroyWrapper(wrapper);
		hideNotice(wrapper);

		var state = {
			form: form,
			button: button,
			config: parseConfig(wrapper),
			rawConfig: rawConfig,
			submitHandler: null,
			inputHandler: null,
			validationNoticeActive: false,
		};
		state.submitHandler = function (event) {
			handleSubmit(wrapper, state, event);
		};
		state.inputHandler = function (event) {
			if (!event.target || !event.target.willValidate) {
				return;
			}

			if (state.validationNoticeActive) {
				if (!syncInvalidState(state.form)) {
					hideNotice(wrapper);
					state.validationNoticeActive = false;
				}
			} else if (isControlValid(event.target)) {
				event.target.removeAttribute('aria-invalid');
			}
		};
		instances.set(wrapper, state);
		setLoading(wrapper, state, false);
		form.addEventListener('submit', state.submitHandler);
		form.addEventListener('input', state.inputHandler);
	}

	function findRoots(scope) {
		var root = scope && scope.jquery ? scope[0] : scope;
		if (!root) {
			return [];
		}

		var roots = [];
		if (root.matches && root.matches(SELECTOR.root)) {
			roots.push(root);
		}
		if (root.querySelectorAll) {
			roots = roots.concat(Array.from(root.querySelectorAll(SELECTOR.root)));
		}
		if (!roots.length && root.closest) {
			var widget = root.closest(SELECTOR.widget);
			if (widget) {
				roots = Array.from(widget.querySelectorAll(SELECTOR.root));
			}
		}

		return roots;
	}

	function initScope(scope) {
		findRoots(scope || document).forEach(initWrapper);
	}

	var api = {
		init: function () {
			initScope(document);
		},
		initScope: initScope,
		destroy: destroyWrapper,
	};

	window.ApeironSignalForm = api;

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
			'frontend/element_ready/apeiron-signal-form.default',
			initScope
		);
	});

	if (window.ApeironSignalFormTestMode) {
		window.ApeironSignalFormTestApi = Object.freeze({
			buildMessage: buildMessage,
			findRoots: findRoots,
			findTokens: findTokens,
			isControlValid: isControlValid,
			isValidWhatsAppPhone: isValidWhatsAppPhone,
			normalizeMessage: normalizeMessage,
			replaceToken: replaceToken,
		});
	}
}());
