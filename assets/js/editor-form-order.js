(function (window, document) {
	'use strict';

	var panel;
	var scheduled = false;
	var activeStepKey = '';
	var currentModel;
	var boundCollections = [];
	var initialized = false;

	function registerStylePanel() {
		var editor = window.elementor;
		var layout = editor.panel && editor.panel.currentView;
		var page = layout && layout.getPages('editor');
		if (!page || !page.view || !page.view.extend || page.view.apeironOrderControls) { return; }
		var NativeEditor = page.view;
		function isOrderStyle(view) {
			return view.model.get('widgetType') === 'apeiron-form-order' && ['style', 'advanced'].indexOf(view.getOption('tab')) !== -1;
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
					button = document.createElement('button');
					button.type = 'button';
					button.className = 'pcr-button';
					button.setAttribute('aria-label', 'toggle color picker dialog');
					placeholder.appendChild(button);
					button.addEventListener('click', function (event) {
						event.stopPropagation();
						child.applySavedValue = applySavedValue;
						child.initPicker();
						var picker = child.colorPicker.picker;
						var destroy = picker.destroyAndRemove;
						var reposition = picker._rePositioningPicker;
						picker.destroyAndRemove = function () {
							var result = destroy.apply(this, arguments);
							// Pickr can retain a positioning frame after a section rebuild destroys it.
							this._rePositioningPicker = function () {
								if (this.options) { return reposition.apply(this, arguments); }
							};
							return result;
						};
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
				if (!isOrderStyle(this)) { return NativeEditor.prototype.initCollection.apply(this, arguments); }
				// Closed sections retain their models without constructing their native control defaults/views.
				this.collection = new window.Backbone.Collection(Object.values(this.getOption('controls')));
				this.orderMergedControls = new Set();
				this.orderControlsByName = new Map(this.collection.map(function (control) { return [control.get('name'), control]; }));
			},
			getControlModel: function (name) {
				return this.orderControlsByName ? this.orderControlsByName.get(name) : NativeEditor.prototype.getControlModel.apply(this, arguments);
			},
			getChildView: function (control) {
				if (isOrderStyle(this) && !this.orderMergedControls.has(control.cid)) {
					control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
					this.orderMergedControls.add(control.cid);
				}
				return NativeEditor.prototype.getChildView.apply(this, arguments);
			},
			buildChildView: function (control) {
				var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
				if (isOrderStyle(this) && control.get('type') === 'color' && child.initPicker) { deferColorPicker(child); }
				if (isOrderStyle(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
					var getChildren = child.getResponsiveChildrenViews;
					child.getResponsiveChildrenViews = function () {
						return getChildren.apply(this, arguments).filter(function (view) { return !!view; });
					};
				}
				return child;
			},
			_renderChildren: function () {
				var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
				if (isOrderStyle(this) && this.orderActiveTabs) {
					var view = this;
					view.orderActiveTabs.forEach(function (name) {
						var tab = NativeEditor.prototype.getControlViewByName.call(view, name);
						if (tab && !tab.$el.hasClass('e-tab-active')) { tab.el.click(); }
					});
				}
				return result;
			},
			filter: function (control, index, collection) {
				if (!NativeEditor.prototype.filter.call(this, control, index, collection)) { return false; }
				if (!isOrderStyle(this)) { return true; }
				var owner = this.orderPopoverOwners && this.orderPopoverOwners.get(control.cid);
				if (owner && !this.orderLoadedPopovers.has(owner)) { return false; }
				var popover = control.get('popover');
				if (!this.orderRequestedControls.has(control.cid)) {
					var responsive = control.get('responsive');
					var deviceControls = this.orderResponsiveDevices.get(editor.channels.deviceMode.request('currentMode'));
					// Preserve native popover boundaries and responsive placeholder parent chains.
					if (responsive && deviceControls && !deviceControls.has(control.cid) && !(popover && (popover.start || popover.end))) { return false; }
					var innerTab = control.get('inner_tab');
					if (innerTab && this.orderActiveTabs.get(control.get('tabs_wrapper')) !== innerTab) { return false; }
				}
				if (popover && (popover.start || popover.end)) { return true; }
				var settings = this.model.get('settings');
				return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
			},
			getControlViewByName: function (name) {
				var control = NativeEditor.prototype.getControlViewByName.call(this, name);
				if (!isOrderStyle(this) || !this.orderPopoverOwners) { return control; }
				var model = this.getControlModel(name);
				if (!model || model.get('section') !== this.activeSection) { return control; }
				var owner = this.orderPopoverToggles.has(name) ? name : this.orderPopoverOwners.get(model.cid);
				if (owner && !this.orderLoadedPopovers.has(owner)) {
					this.orderLoadedPopovers.add(owner);
					if (control && name === owner) {
						// Let the native radio change finish before replacing its clicked input.
						this.orderPendingPopover = owner;
						this.scheduleOrderControls();
					} else {
						this._renderChildren();
						control = NativeEditor.prototype.getControlViewByName.call(this, name);
					}
				}
				if (!control) {
					var requested = model;
					while (requested) {
						this.orderRequestedControls.add(requested.cid);
						requested = requested.get('parent') ? this.getControlModel(requested.get('parent')) : null;
					}
					this._renderChildren();
					control = NativeEditor.prototype.getControlViewByName.call(this, name);
				}
				return control;
			},
			activateSection: function (section) {
				if (isOrderStyle(this) && this.activeSection !== section && this.orderLoadedPopovers) {
					this.orderLoadedPopovers.clear();
					this.orderPendingPopover = null;
					this.orderRequestedControls.clear();
					this.orderActiveTabs = new Map(this.orderDefaultTabs);
				}
				return NativeEditor.prototype.activateSection.apply(this, arguments);
			},
			initialize: function () {
				NativeEditor.prototype.initialize.apply(this, arguments);
				if (!isOrderStyle(this)) { return; }
				var view = this;
				view.orderPopoverOwners = new Map();
				view.orderPopoverToggles = new Set();
				view.orderLoadedPopovers = new Set();
				view.orderRequestedControls = new Set();
				view.orderDefaultTabs = new Map();
				view.orderActiveTabs = new Map();
				view.orderResponsiveDevices = new Map();
				var previous = null;
				var owner = null;
				view.collection.each(function (control) {
					var responsive = control.get('responsive');
					if (responsive) {
						var device = responsive.max || responsive.min;
						if (!view.orderResponsiveDevices.has(device)) { view.orderResponsiveDevices.set(device, new Set()); }
						var parent = control;
						while (parent) {
							view.orderResponsiveDevices.get(device).add(parent.cid);
							parent = view.orderControlsByName.get(parent.get('parent'));
						}
					}
					if (control.get('type') === 'tab' && !view.orderDefaultTabs.has(control.get('tabs_wrapper'))) {
						view.orderDefaultTabs.set(control.get('tabs_wrapper'), control.get('name'));
						view.orderActiveTabs.set(control.get('tabs_wrapper'), control.get('name'));
					}
					var popover = control.get('popover');
					if (popover && popover.start) {
						owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
						if (owner) { view.orderPopoverToggles.add(owner); }
					}
					if (owner) { view.orderPopoverOwners.set(control.cid, owner); }
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
					if (view.isDestroyed || !isOrderStyle(view)) { return; }
					var pending = view.orderPendingPopover;
					view.orderPendingPopover = null;
					if (signature() === visibleControls && !pending) { return; }
					var open = view.children.filter(function (child) {
						return child.model.get('type') === 'popover_toggle' && child.$el.next('.elementor-controls-popover').is(':visible');
					}).map(function (child) { return child.model.get('name'); });
					if (pending && open.indexOf(pending) === -1) { open.push(pending); }
					var selector = 'input, select, textarea, button, [tabindex]';
					var focused = view.children.find(function (child) { return child.el.contains(document.activeElement); });
					var focusIndex = focused ? Array.from(focused.el.querySelectorAll(selector)).indexOf(document.activeElement) : -1;
					view._renderChildren();
					open.forEach(function (name) {
						var toggle = NativeEditor.prototype.getControlViewByName.call(view, name);
						if (toggle) {
							if (!toggle.$el.next('.elementor-controls-popover').attr('data-on-hide')) { toggle.onShow(); }
							toggle.activate();
						}
					});
					if (focused && focusIndex >= 0) {
						var control = NativeEditor.prototype.getControlViewByName.call(view, focused.model.get('name'));
						var input = control && control.el.querySelectorAll(selector)[focusIndex];
						if (input) { input.focus({ preventScroll: true }); }
					}
				}
				view.on('render:collection', function () { visibleControls = signature(); });
				view.scheduleOrderControls = function () {
					if (isOrderStyle(view) && !refreshFrame) { refreshFrame = window.requestAnimationFrame(refreshControls); }
				};
				view.listenTo(view.model.get('settings'), 'change', view.scheduleOrderControls);
				view.listenTo(editor.channels.deviceMode, 'change', view.scheduleOrderControls);
				view.on('childview:control:tab:clicked', function (tab) {
					var wrapper = tab.model.get('tabs_wrapper');
					var name = tab.model.get('name');
					if (view.orderActiveTabs.get(wrapper) === name) { return; }
					view.orderActiveTabs.set(wrapper, name);
					view.scheduleOrderControls();
				});
				view.on('destroy', function () { if (refreshFrame) { window.cancelAnimationFrame(refreshFrame); } });
			}
		});
		page.view.apeironOrderControls = true;
	}

	function registerFieldRepeater() {
		if (!window.elementor || !window.elementor.addControlView) { return; }
		var Repeater = window.elementor.getControlView('repeater');
		var NativeRow = Repeater.prototype.childView;
		var DeferredRow = NativeRow.extend({
			initialize: function () {
				NativeRow.prototype.initialize.apply(this, arguments);
				this.deferredControls = this.collection;
				this.collection = new window.Backbone.Collection();
			},
			openControls: function () {
				if (this.controlsLoaded) { return; }
				this.controlsLoaded = true;
				this.collection.reset(this.deferredControls.models);
			}
		});
		window.elementor.addControlView('apeiron_order_fields', Repeater.extend({
			childView: DeferredRow,
			onRender: function () {
				this.$el.addClass('elementor-control-type-repeater');
				Repeater.prototype.onRender.apply(this, arguments);
				schedule();
			},
			editRow: function (row) {
				row.openControls();
				return Repeater.prototype.editRow.apply(this, arguments);
			}
		}));
	}

	function selectedModel() {
		if (!window.elementor || !window.elementor.panel || !window.elementor.panel.currentView) { return null; }
		var page = window.elementor.panel.currentView.getCurrentPageView();
		var edited = page && page.getOption && page.getOption('editedElementView');
		var model = edited && edited.model;
		return model && model.get('widgetType') === 'apeiron-form-order' ? model : null;
	}

	function bindModel(model) {
		if (currentModel === model) { return; }
		boundCollections.forEach(function (binding) { binding.collection.off(binding.events, schedule); });
		boundCollections = [];
		currentModel = model;
		activeStepKey = '';
		if (!model) { return; }
		var settings = model.get('settings');
		['order_steps', 'order_fields'].forEach(function (name) {
			var collection = settings && settings.get(name);
			var events = name === 'order_steps' ? 'add remove reset sort change:title change:step_key change:kind' : 'add remove reset sort change:step_key';
			if (collection && collection.on) { collection.on(events, schedule); boundCollections.push({ collection: collection, events: events }); }
		});
	}

	function synchronizeModel(model) {
		var settings = model.get('settings');
		var steps = settings && settings.get('order_steps');
		var fields = settings && settings.get('order_fields');
		if (!steps || !steps.each || !fields || !fields.each) { return false; }
		var titles = Object.create(null);
		var available = [];
		steps.each(function (step) {
			var key = String(step.get('step_key') || '').trim();
			if (!key) {
				key = 'step_' + step.get('_id');
				step.set('step_key', key);
				activeStepKey = key;
			}
			titles[key] = String(step.get('title') || 'Step Baru');
			if (step.get('kind') !== 'confirmation') { available.push(key); }
		});
		if (!titles[activeStepKey]) { activeStepKey = available[available.length - 1] || ''; }
		var previous = '';
		var assigned = Object.create(null);
		var metadata = [];
		fields.each(function (field) {
			var key = String(field.get('step_key') || '').trim();
			if (!key && activeStepKey) { key = activeStepKey; field.set('step_key', key); }
			var label = titles[key] || key;
			var header = !!key && key !== previous;
			field.set({ step_label: label, group_header: header ? 'yes' : '' }, { silent: true });
			metadata.push({ label: label, header: header });
			assigned[key] = true;
			previous = key;
		});
		var control = panel.querySelector('.elementor-control-order_fields');
		if (control) {
			control.querySelectorAll('.elementor-repeater-fields-wrapper > .elementor-repeater-fields').forEach(function (row, index) {
				var info = metadata[index];
				var title = row.querySelector('.elementor-repeater-row-item-title');
				if (!info || !title) { return; }
				var header = title.querySelector('.apeiron-form-order-editor-group');
				if (info.header) {
					if (!header) { header = document.createElement('span'); header.className = 'apeiron-form-order-editor-group'; title.prepend(header); }
					if (header.textContent !== info.label) { header.textContent = info.label; }
				} else if (header) { header.remove(); }
			});
			renderEmptySteps(control, available.filter(function (key) { return !assigned[key]; }), titles);
		}
		return true;
	}

	function renderEmptySteps(control, missing, titles) {
		var wrapper = control.querySelector('.elementor-repeater-fields-wrapper');
		if (!wrapper) { return; }
		var empty = control.querySelector('.apeiron-form-order-editor-empty');
		var signature = missing.map(function (key) { return key + ':' + titles[key]; }).join('|');
		if (!missing.length) { if (empty) { empty.remove(); } return; }
		if (!empty) { empty = document.createElement('div'); empty.className = 'apeiron-form-order-editor-empty'; wrapper.after(empty); }
		if (empty.getAttribute('data-step-signature') === signature) { return; }
		empty.setAttribute('data-step-signature', signature);
		empty.replaceChildren();
		missing.forEach(function (key) {
			var heading = document.createElement('div');
			heading.textContent = titles[key];
			var button = document.createElement('button');
			button.type = 'button'; button.textContent = 'Tambah Field';
			button.setAttribute('data-order-editor-select-step', key);
			heading.appendChild(button);
			empty.appendChild(heading);
		});
	}

	function controlInput(row, name) {
		var control = row.querySelector('.elementor-control-' + name);
		return control && control.querySelector('input[data-setting="' + name + '"]');
	}

	function synchronize() {
		scheduled = false;
		if (!panel) { return; }
		var model = selectedModel();
		bindModel(model);
		if (model) { synchronizeModel(model); }
	}

	function schedule() {
		if (!scheduled) {
			scheduled = true;
			window.requestAnimationFrame(synchronize);
		}
	}

	function initialize() {
		if (initialized) { return; }
		registerFieldRepeater();
		panel = document.getElementById('elementor-panel');
		if (!panel) { return; }
		initialized = true;
		registerStylePanel();
		window.elementor.hooks.addFilter('element/view', function (View, model) {
			if (model.get('widgetType') !== 'apeiron-form-order') { return View; }
			return View.extend({
				render: function () {
					if (!this.isDestroyed) { this.getContainer(); }
					return View.prototype.render.apply(this, arguments);
				}
			});
		});
		window.elementor.hooks.addAction('panel/open_editor/widget', schedule);
		window.elementor.on('preview:loaded', registerStylePanel);
		window.elementor.on('document:unload', function () { bindModel(null); });
		panel.addEventListener('click', function (event) {
			var addForStep = event.target.closest('[data-order-editor-select-step]');
			if (addForStep) {
				activeStepKey = addForStep.getAttribute('data-order-editor-select-step');
				var addButton = panel.querySelector('.elementor-control-order_fields .elementor-repeater-add');
				if (addButton) { addButton.click(); }
				return;
			}
			var row = event.target.closest('.elementor-control-order_steps .elementor-repeater-fields');
			if (row) {
				var key = controlInput(row, 'step_key');
				if (key && key.value) { activeStepKey = key.value.trim().toLowerCase(); }
				else if (currentModel) {
					var wrapper = row.parentElement;
					var rows = Array.prototype.slice.call(wrapper.children).filter(function (node) { return node.classList.contains('elementor-repeater-fields'); });
					var steps = currentModel.get('settings').get('order_steps');
					var step = steps && steps.at(rows.indexOf(row));
					if (step) { activeStepKey = step.get('step_key') || ''; }
				}
			}
		});
		schedule();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialize);
	} else {
		initialize();
	}
	if (window.elementor && window.elementor.on) { window.elementor.on('panel:init', initialize); }
})(window, document);
