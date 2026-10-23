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

	function registerEditorControls() {
		var host;
		try {
			host = window.parent;
			if (!host || host === window || !host.elementor || host.__apeironSignalFormControls) { return; }
		} catch (error) {
			return; // The containing page may be on another origin.
		}
		var editor = host.elementor;
		if (!editor.hooks || !editor.helpers || !editor.helpers.isActiveControl) { return; }
		host.__apeironSignalFormControls = true;

		function registerPanel() {
			var panel = editor.panel && editor.panel.currentView;
			var page = panel && panel.getPages('editor');
			if (!page || !page.view || !page.view.extend || page.view.__apeironSignalFormControls) { return; }
			var NativeEditor = page.view;
			function isFormStyle(view) {
				return view.model.get('widgetType') === 'apeiron-signal-form' && ['style', 'advanced'].indexOf(view.getOption('tab')) !== -1;
			}

			function deferColorPicker(child) {
				var applySavedValue = child.applySavedValue;
				var dynamicBehaviors = Object.values(child._behaviors || {}).filter(function (behavior) {
					return typeof behavior.moveDynamicSwitcherToColorPicker === 'function';
				});
				dynamicBehaviors.forEach(function (behavior) {
					var move = behavior.moveDynamicSwitcherToColorPicker;
					behavior.moveDynamicSwitcherToColorPicker = function () {
						if (child.isDestroyed || !this.ui.dynamicSwitcher) { return; }
						if (child.colorPicker) { return move.apply(this, arguments); }
						this.ui.dynamicSwitcher.detach();
					};
				});
				child.applySavedValue = function () {
					if (this.colorPicker) { return applySavedValue.apply(this, arguments); }
					var value = this.getCurrentValue();
					var placeholder = this.ui.pickerContainer[0];
					var button = placeholder.querySelector('button');
					if (!button) {
						placeholder.classList.add('pickr', 'elementor-control-unit-1', 'elementor-control-tag-area');
						button = host.document.createElement('button');
						button.type = 'button';
						button.className = 'pcr-button';
						button.setAttribute('aria-label', 'toggle color picker dialog');
						placeholder.appendChild(button);
						button.addEventListener('click', function (event) {
							event.stopPropagation();
							child.applySavedValue = applySavedValue;
							child.initPicker();
							dynamicBehaviors.forEach(function (behavior) { behavior.moveDynamicSwitcherToColorPicker(); });
							child.colorPicker.picker.on('init', function () {
								if (child.isDestroyed) { return; }
								child.$pickerButton[0].click();
								child.$pickerButton[0].focus();
							});
						});
					}
					button.classList.toggle('clear', !value);
					button.style.setProperty('--pcr-color', value || 'rgba(0, 0, 0, 0.15)');
					this.$el.toggleClass('e-control-color--no-value', !value);
				};
			}

			page.view = NativeEditor.extend({
				initCollection: function () {
					if (!isFormStyle(this)) { return NativeEditor.prototype.initCollection.apply(this, arguments); }
					// Merge native defaults only for controls whose views are needed.
					this.collection = new host.Backbone.Collection(Object.values(this.getOption('controls')));
					this.__signalMergedControls = new Set();
					this.__signalControlsByName = new Map(this.collection.map(function (control) { return [ control.get('name'), control ]; }));
				},
				getControlModel: function (name) {
					return this.__signalControlsByName ? this.__signalControlsByName.get(name) : NativeEditor.prototype.getControlModel.apply(this, arguments);
				},
				getChildView: function (control) {
					if (isFormStyle(this) && !this.__signalMergedControls.has(control.cid)) {
						control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
						this.__signalMergedControls.add(control.cid);
					}
					return NativeEditor.prototype.getChildView.apply(this, arguments);
				},
				buildChildView: function (control) {
					var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
					if (isFormStyle(this) && control.get('type') === 'color' && child.initPicker) {
						deferColorPicker(child);
					}
					if (isFormStyle(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
						var getChildren = child.getResponsiveChildrenViews;
						child.getResponsiveChildrenViews = function () {
							return getChildren.apply(this, arguments).filter(function (view) { return !!view; });
						};
					}
					return child;
				},
				_renderChildren: function () {
					var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
					if (isFormStyle(this) && this.__signalActiveTabs) {
						var view = this;
						view.__signalActiveTabs.forEach(function (name) {
							var tab = NativeEditor.prototype.getControlViewByName.call(view, name);
							if (tab && !tab.$el.hasClass('e-tab-active')) { tab.el.click(); }
						});
					}
					return result;
				},
				filter: function (control, index, collection) {
					if (!NativeEditor.prototype.filter.call(this, control, index, collection)) { return false; }
					if (!isFormStyle(this)) { return true; }
					var owner = this.__signalPopoverOwners && this.__signalPopoverOwners.get(control.cid);
					if (owner && !this.__signalLoadedPopovers.has(owner)) { return false; }
					if (!this.__signalRequestedControls.has(control.cid)) {
						var responsive = control.get('responsive');
						var popover = control.get('popover');
						var deviceControls = this.__signalResponsiveDevices.get(editor.channels.deviceMode.request('currentMode'));
						// Native popovers and responsive placeholders retain their boundaries/parent chain.
						if (responsive && deviceControls && !deviceControls.has(control.cid) &&
							!(popover && (popover.start || popover.end))) { return false; }
						var innerTab = control.get('inner_tab');
						if (innerTab && this.__signalActiveTabs.get(control.get('tabs_wrapper')) !== innerTab) { return false; }
					}
					var settings = this.model.get('settings');
					return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
				},
				getControlViewByName: function (name) {
					var control = NativeEditor.prototype.getControlViewByName.call(this, name);
					if (!isFormStyle(this) || !this.__signalPopoverOwners) { return control; }
					var model = this.getControlModel(name);
					if (!model || model.get('section') !== this.activeSection) { return control; }
					var owner = this.__signalPopoverToggles.has(name) ? name : this.__signalPopoverOwners.get(model.cid);
					if (owner && !this.__signalLoadedPopovers.has(owner)) {
						this.__signalLoadedPopovers.add(owner);
						if (control && name === owner) {
							// Allow the native radio change to finish before rebuilding its popover.
							this.__signalPendingPopover = owner;
							this.__signalScheduleControls();
						} else {
							this._renderChildren();
							control = NativeEditor.prototype.getControlViewByName.call(this, name);
						}
					}
					if (!control) {
						var requested = model;
						while (requested) {
							this.__signalRequestedControls.add(requested.cid);
							requested = requested.get('parent') ? this.getControlModel(requested.get('parent')) : null;
						}
						this._renderChildren();
						control = NativeEditor.prototype.getControlViewByName.call(this, name);
					}
					return control;
				},
				activateSection: function (section) {
					if (isFormStyle(this) && this.activeSection !== section && this.__signalLoadedPopovers) {
						this.__signalLoadedPopovers.clear();
						this.__signalPendingPopover = null;
						this.__signalRequestedControls.clear();
						this.__signalActiveTabs = new Map(this.__signalDefaultTabs);
					}
					return NativeEditor.prototype.activateSection.apply(this, arguments);
				},
				initialize: function () {
					NativeEditor.prototype.initialize.apply(this, arguments);
					if (!isFormStyle(this)) { return; }
					var view = this;
					var settings = view.model.get('settings');
					view.__signalPopoverOwners = new Map();
					view.__signalPopoverToggles = new Set();
					view.__signalLoadedPopovers = new Set();
					view.__signalRequestedControls = new Set();
					view.__signalDefaultTabs = new Map();
					view.__signalActiveTabs = new Map();
					view.__signalResponsiveDevices = new Map();
					var previous = null;
					var owner = null;
					view.collection.each(function (control) {
						var responsive = control.get('responsive');
						if (responsive) {
							var device = responsive.max || responsive.min;
							if (!view.__signalResponsiveDevices.has(device)) { view.__signalResponsiveDevices.set(device, new Set()); }
							var parent = control;
							while (parent) {
								view.__signalResponsiveDevices.get(device).add(parent.cid);
								parent = view.__signalControlsByName.get(parent.get('parent'));
							}
						}
						if (control.get('type') === 'tab' && !view.__signalDefaultTabs.has(control.get('tabs_wrapper'))) {
							view.__signalDefaultTabs.set(control.get('tabs_wrapper'), control.get('name'));
							view.__signalActiveTabs.set(control.get('tabs_wrapper'), control.get('name'));
						}
						var popover = control.get('popover');
						if (popover && popover.start) {
							owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
							if (owner) { view.__signalPopoverToggles.add(owner); }
						}
						if (owner) { view.__signalPopoverOwners.set(control.cid, owner); }
						if (popover && popover.end) { owner = null; }
						previous = control;
					});

					var refreshFrame = null;
					var visibleControls = '';
					function signature() {
						return view.collection.filter(view.filter, view).map(function (control) { return control.cid; }).join('|');
					}
					function refreshControls() {
						refreshFrame = null;
						if (view.isDestroyed || !isFormStyle(view)) { return; }
						var pendingPopover = view.__signalPendingPopover;
						view.__signalPendingPopover = null;
						if (signature() === visibleControls && !pendingPopover) { return; }
						var openPopovers = view.children.filter(function (child) {
							return child.model.get('type') === 'popover_toggle' && child.$el.next('.elementor-controls-popover').is(':visible');
						}).map(function (child) { return child.model.get('name'); });
						if (pendingPopover && openPopovers.indexOf(pendingPopover) === -1) { openPopovers.push(pendingPopover); }
						var focusSelector = 'input, select, textarea, button, [tabindex]';
						var focused = view.children.find(function (child) { return child.el.contains(host.document.activeElement); });
						var focusIndex = focused ? Array.from(focused.el.querySelectorAll(focusSelector)).indexOf(host.document.activeElement) : -1;
						view._renderChildren();
						openPopovers.forEach(function (name) {
							var toggle = NativeEditor.prototype.getControlViewByName.call(view, name);
							if (toggle) {
								if (!toggle.$el.next('.elementor-controls-popover').attr('data-on-hide')) { toggle.onShow(); }
								toggle.activate();
							}
						});
						if (focused && focusIndex >= 0) {
							var control = NativeEditor.prototype.getControlViewByName.call(view, focused.model.get('name'));
							var input = control && control.el.querySelectorAll(focusSelector)[focusIndex];
							if (input) { input.focus({ preventScroll: true }); }
						}
					}
					view.on('render:collection', function () { visibleControls = signature(); });
					view.__signalScheduleControls = function () {
						if (isFormStyle(view) && !refreshFrame) { refreshFrame = host.requestAnimationFrame(refreshControls); }
					};
					view.listenTo(settings, 'change', view.__signalScheduleControls);
					view.listenTo(editor.channels.deviceMode, 'change', view.__signalScheduleControls);
					view.on('childview:control:tab:clicked', function (tab) {
						var wrapper = tab.model.get('tabs_wrapper');
						var name = tab.model.get('name');
						if (view.__signalActiveTabs.get(wrapper) === name) { return; }
						view.__signalActiveTabs.set(wrapper, name);
						view.__signalScheduleControls();
					});
					view.on('destroy', function () {
						if (refreshFrame) { host.cancelAnimationFrame(refreshFrame); }
					});
				}
			});
			page.view.__apeironSignalFormControls = true;
		}

		editor.hooks.addAction('panel/open_editor/widget/apeiron-signal-form', registerPanel);
		editor.on('preview:loaded', registerPanel);
		if (editor.panel && editor.panel.currentView) { registerPanel(); }
		else { editor.once('panel:init', registerPanel); }
	}

	registerEditorControls();
	window.addEventListener('elementor/frontend/init', function () {
		registerEditorControls();
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
