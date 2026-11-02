(function(window, document, $) {
	'use strict';

	var config = window.ApeironEditorShellConfig || {};
	var initialDisabled = Array.isArray(config.disabledWidgets) ? config.disabledWidgets.slice() : [];
	var livePreviewBound = false;

	function bindLivePreviews() {
		var editor = window.elementor;
		if (livePreviewBound || !editor || !editor.hooks) { return; }
		livePreviewBound = true;
		var views = new WeakMap();
		editor.hooks.addFilter('element/view', function(View, model) {
			var type = model.get('widgetType') || '';
			if (type.indexOf('apeiron-') !== 0 || type === 'apeiron-depthscape') { return View; }
			if (!views.has(View)) {
				views.set(View, View.extend({
					renderOnChange: function(settings) {
						var changes = settings.changedAttributes && settings.changedAttributes();
						var host = this.el.ownerDocument.defaultView;
						var widgetType = this.getEditModel().get('widgetType');
						var update = host.__apeironEditorPreviews && host.__apeironEditorPreviews[widgetType];
						if (this.allowRender && !this.isRendering && changes && update && !this.$el.hasClass('elementor-loading')) {
							var keys = Object.keys(changes);
							var ui = [];
							var styles = false;
							var html = false;
							keys.forEach(function(name) {
								var control = settings.getControl(name);
								var css = control && ((control.selectors && Object.keys(control.selectors).length) || control.prefix_class !== undefined);
								styles = styles || !!css;
								if (control && (['ui', 'none'].indexOf(control.render_type) >= 0 || (css && control.render_type !== 'template'))) { ui.push(name); }
								else { html = true; }
							});
							if (keys.length && (html || (update.cssControls && keys.some(function(name) { return update.cssControls.indexOf(name) >= 0; })))) {
								var owner = this.getContainer();
								var scope = settings === owner.settings ? 'root' : '';
								var index = -1;
								Object.keys(owner.repeaters || {}).some(function(name) {
									var rows = owner.settings.get(name);
									var at = rows && rows.indexOf ? rows.indexOf(settings) : -1;
									if (at < 0) { return false; }
									scope = name; index = at; return true;
								});
								var change = {
									scope: scope, index: index, id: settings.get('_id'), values: changes,
									get: function(name) { return settings.get(name); },
									getRoot: function(name) { return owner.settings.get(name); },
									supports: function(fields, group) {
										var allowed = function(name) { return Array.isArray(fields) ? fields.indexOf(name) >= 0 : Object.prototype.hasOwnProperty.call(fields, name); };
										return scope === (group || 'root') && keys.some(allowed) && keys.every(function(name) { return allowed(name) || ui.indexOf(name) >= 0; });
									},
									number: function(name, fallback, min, max) {
										var value = settings.get(name);
										if (value && typeof value === 'object') { value = value.size; }
										var parsed = typeof value === 'number' || (typeof value === 'string' && value.trim() !== '') ? Number(value) : NaN;
										return Math.max(min, Math.min(max, Number.isFinite(parsed) ? parsed : fallback));
									},
									option: function(name, fallback) {
										var control = settings.getControl(name), value = settings.get(name);
										return control && control.options && Object.prototype.hasOwnProperty.call(control.options, value) ? String(value) : fallback;
									},
								};
								if (scope && update(this.el, change) === true) {
									if (styles) { this.renderUI(); }
									return;
								}
							}
						}
						return View.prototype.renderOnChange.apply(this, arguments);
					},
				}));
			}
			return views.get(View);
		});
	}

	// Widgets whose own panel code does not build control defaults or colour pickers on demand yet.
	var lazyControlWidgets = ['apeiron-cover', 'apeiron-autoscroll', 'apeiron-cerita-cinta'];
	var lazyColorWidgets = lazyControlWidgets.concat('apeiron-depthscape');

	function deferColorPicker(child) {
		var applySavedValue = child.applySavedValue;
		var dynamicBehaviors = Object.values(child._behaviors || {}).filter(function(behavior) {
			return typeof behavior.moveDynamicSwitcherToColorPicker === 'function';
		});
		dynamicBehaviors.forEach(function(behavior) {
			var move = behavior.moveDynamicSwitcherToColorPicker;
			behavior.moveDynamicSwitcherToColorPicker = function() {
				if (child.isDestroyed || !this.ui.dynamicSwitcher) { return; }
				if (child.colorPicker) { return move.apply(this, arguments); }
				this.ui.dynamicSwitcher.detach();
			};
		});
		child.applySavedValue = function() {
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
				button.addEventListener('click', function(event) {
					event.stopPropagation();
					child.applySavedValue = applySavedValue;
					child.initPicker();
					dynamicBehaviors.forEach(function(behavior) { behavior.moveDynamicSwitcherToColorPicker(); });
					child.colorPicker.picker.on('init', function() {
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

	function bindPanelControls() {
		var editor = window.elementor;
		var panel = editor && editor.panel && editor.panel.currentView;
		var page = panel && panel.getPages && panel.getPages('editor');
		if (!page || !page.view || !page.view.extend || page.view.__apeironPanelControls) { return; }
		var NativeEditor = page.view;
		var tabSections = new WeakMap();

		function widgetType(view) {
			return view.model.get('widgetType') || '';
		}

		function isApeiron(view) {
			return widgetType(view).indexOf('apeiron-') === 0;
		}

		function isLazyPanel(view) {
			return lazyControlWidgets.indexOf(widgetType(view)) >= 0 && ['content', 'style', 'advanced'].indexOf(view.getOption('tab')) >= 0;
		}

		// AutoScroll's own script already loads its Style tab conditionally.
		function isLazyControl(view, control) {
			return isLazyPanel(view) && !(widgetType(view) === 'apeiron-autoscroll' && control.get('tab') === 'style');
		}

		function bindLazyControls(view) {
			var settings = view.model.get('settings');
			var previous = null;
			var owner = null;
			view.__apeironPopoverOwners = new Map();
			view.__apeironPopoverToggles = new Set();
			view.__apeironLoadedPopovers = new Set();
			view.collection.each(function(control) {
				var popover = control.get('popover');
				if (popover && popover.start && isLazyControl(view, control)) {
					owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
					if (owner) { view.__apeironPopoverToggles.add(owner); }
				}
				if (owner) { view.__apeironPopoverOwners.set(control.cid, owner); }
				if (popover && popover.end) { owner = null; }
				previous = control;
			});

			var refreshFrame = null;
			var visibleControls = '';
			function signature() {
				return view.collection.filter(view.filter, view).map(function(control) { return control.cid; }).join('|');
			}
			function refreshControls() {
				refreshFrame = null;
				if (view.isDestroyed) { return; }
				var pendingPopover = view.__apeironPendingPopover;
				view.__apeironPendingPopover = null;
				if (signature() === visibleControls && !pendingPopover) { return; }
				var openPopovers = view.children.filter(function(child) {
					return child.model.get('type') === 'popover_toggle' && child.$el.next('.elementor-controls-popover').is(':visible');
				}).map(function(child) { return child.model.get('name'); });
				if (pendingPopover && openPopovers.indexOf(pendingPopover) < 0) { openPopovers.push(pendingPopover); }
				var focusSelector = 'input, select, textarea, button, [tabindex]';
				var focused = view.children.find(function(child) { return child.el.contains(document.activeElement); });
				var focusIndex = focused ? Array.from(focused.el.querySelectorAll(focusSelector)).indexOf(document.activeElement) : -1;
				view._renderChildren();
				openPopovers.forEach(function(name) {
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
			view.on('render:collection', function() { visibleControls = signature(); });
			view.__apeironScheduleControls = function() {
				if (!refreshFrame) { refreshFrame = window.requestAnimationFrame(refreshControls); }
			};
			view.listenTo(settings, 'change', view.__apeironScheduleControls);
			view.on('destroy', function() {
				if (refreshFrame) { window.cancelAnimationFrame(refreshFrame); }
			});
		}

		page.view = NativeEditor.extend({
			initCollection: function() {
				if (lazyControlWidgets.indexOf(widgetType(this)) < 0) { return NativeEditor.prototype.initCollection.apply(this, arguments); }
				// Unopened controls keep their IDs without merging every native default.
				this.collection = new window.Backbone.Collection(Object.values(this.getOption('controls')));
			},
			getChildView: function(control) {
				if (lazyControlWidgets.indexOf(widgetType(this)) >= 0 && !control.get('features')) {
					control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
				}
				return NativeEditor.prototype.getChildView.apply(this, arguments);
			},
			buildChildView: function(control) {
				var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
				if (lazyColorWidgets.indexOf(widgetType(this)) >= 0 && control.get('type') === 'color' && child.initPicker) { deferColorPicker(child); }
				if (isLazyPanel(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
					var getChildren = child.getResponsiveChildrenViews;
					child.getResponsiveChildrenViews = function() {
						return getChildren.apply(this, arguments).filter(function(view) { return !!view; });
					};
				}
				return child;
			},
			filter: function(control, index, collection) {
				if (!NativeEditor.prototype.filter.call(this, control, index, collection)) { return false; }
				if (!isLazyControl(this, control)) { return true; }
				var owner = this.__apeironPopoverOwners && this.__apeironPopoverOwners.get(control.cid);
				if (owner) { return this.__apeironLoadedPopovers.has(owner); }
				// Unknown/add-on popover groups retain their native boundaries.
				if (control.get('popover')) { return true; }
				var settings = this.model.get('settings');
				return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
			},
			getControlViewByName: function(name) {
				var control = NativeEditor.prototype.getControlViewByName.call(this, name);
				if (!isLazyPanel(this) || !this.__apeironPopoverOwners) { return control; }
				var model = this.getControlModel(name);
				if (!model || model.get('section') !== this.activeSection) { return control; }
				var owner = this.__apeironPopoverToggles.has(name) ? name : this.__apeironPopoverOwners.get(model.cid);
				if (owner && !this.__apeironLoadedPopovers.has(owner)) {
					// Native Elementor routes activate the toggle before opening its popover.
					this.__apeironLoadedPopovers.add(owner);
					if (control && name === owner) {
						// Finish the native radio change before replacing the clicked input.
						this.__apeironPendingPopover = owner;
						this.__apeironScheduleControls();
					} else {
						this._renderChildren();
						control = NativeEditor.prototype.getControlViewByName.call(this, name);
					}
				}
				return control;
			},
			activateSection: function(section) {
				if (this.activeSection !== section && this.__apeironLoadedPopovers) {
					this.__apeironLoadedPopovers.clear();
					this.__apeironPendingPopover = null;
				}
				return NativeEditor.prototype.activateSection.apply(this, arguments);
			},
			_renderChildren: function() {
				if (!isApeiron(this) || !panel.content) { return NativeEditor.prototype._renderChildren.apply(this, arguments); }
				// Elementor empties the list while rebuilding it, which drops the panel back to the top.
				var scrollTop = panel.content.el.scrollTop;
				var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
				panel.content.el.scrollTop = scrollTop;
				return result;
			},
			onChildviewControlSectionClicked: function(child) {
				if (!isApeiron(this) || !panel.content) { return NativeEditor.prototype.onChildviewControlSectionClicked.apply(this, arguments); }
				// The clicked heading stays under the mouse when the section above it collapses.
				var top = child.el.getBoundingClientRect().top;
				var name = child.model.get('name');
				var result = NativeEditor.prototype.onChildviewControlSectionClicked.apply(this, arguments);
				var heading = NativeEditor.prototype.getControlViewByName.call(this, name);
				if (heading) { panel.content.el.scrollTop += heading.el.getBoundingClientRect().top - top; }
				return result;
			},
			initialize: function() {
				NativeEditor.prototype.initialize.apply(this, arguments);
				if (!isApeiron(this)) { return; }
				if (isLazyPanel(this)) { bindLazyControls(this); }

				// Elementor builds a new view per tab; going back reopens the section being edited.
				var view = this;
				var memory = tabSections.get(view.model) || { sections: {}, switching: false };
				tabSections.set(view.model, memory);
				var section = memory.switching && memory.sections[view.activeTab];
				memory.switching = false;
				var sectionControl = section && view.getControlModel(section);
				if (sectionControl && view.isVisibleSectionControl(sectionControl)) {
					view.activateSection(section);
					view.once('show', function() {
						var heading = NativeEditor.prototype.getControlViewByName.call(view, section);
						var scroller = panel.content && panel.content.el;
						if (!heading || !scroller) { return; }
						var offset = heading.el.getBoundingClientRect().top - scroller.getBoundingClientRect().top;
						if (offset + heading.el.offsetHeight > scroller.clientHeight) { scroller.scrollTop += offset; }
					});
				}
				function onNavigationClick(event) {
					var tab = event.target.closest('.elementor-panel-navigation-tab');
					if (!tab || tab.getAttribute('data-tab') === view.activeTab) { return; }
					memory.sections[view.activeTab] = view.activeSection;
					memory.switching = true;
				}
				view.el.addEventListener('click', onNavigationClick, true);
				view.on('before:destroy', function() {
					view.el.removeEventListener('click', onNavigationClick, true);
				});
			},
		});
		page.view.__apeironPanelControls = true;
	}

	function createToggleNotice() {
		if (document.getElementById('apeiron-editor-toggle-notice')) {
			return;
		}

		var notice = document.createElement('div');
		var icon = document.createElement('span');
		var message = document.createElement('span');
		var refresh = document.createElement('button');
		var close = document.createElement('button');

		notice.id = 'apeiron-editor-toggle-notice';
		notice.className = 'apeiron-editor-toggle-notice';
		icon.className = 'dashicons dashicons-warning apeiron-editor-toggle-notice__icon';
		message.textContent = (config.i18n && config.i18n.changed) || 'Pengaturan widget Apeiron telah berubah. Segarkan editor untuk menerapkan perubahan.';
		refresh.type = 'button';
		refresh.className = 'apeiron-editor-toggle-notice__refresh';
		refresh.textContent = (config.i18n && config.i18n.refresh) || 'Segarkan';
		refresh.addEventListener('click', function() {
			window.location.reload();
		});
		close.type = 'button';
		close.className = 'apeiron-editor-toggle-notice__close';
		close.setAttribute('aria-label', (config.i18n && config.i18n.dismiss) || 'Tutup');
		close.textContent = '\u00d7';
		close.addEventListener('click', function() {
			notice.remove();
		});

		notice.appendChild(icon);
		notice.appendChild(message);
		notice.appendChild(refresh);
		notice.appendChild(close);
		document.body.appendChild(notice);
	}

	// Elementor's hover tooltips (tipsy) stay on screen when the control under the mouse is re-rendered or hidden
	// by a click (typography "Back to default", Normal/Focus tabs): the mouse never "leaves" it, so the tooltip
	// piled up and could cover other buttons. In ApeironKit widget panels a tooltip is dropped as soon as the
	// mouse is over something that has no tooltip of its own.
	function bindStaleTooltipCleanup() {
		document.addEventListener('mouseover', function(event) {
			var tips = document.querySelectorAll('.tipsy');
			if (!tips.length || (event.target.closest && event.target.closest('.tooltip-target, [original-title], .tipsy'))) {
				return;
			}
			var panel = window.elementor && window.elementor.getPanelView && window.elementor.getPanelView();
			var page = panel && panel.getCurrentPageView && panel.getCurrentPageView();
			var type = page && page.model && page.model.get && page.model.get('widgetType');
			if (!type || type.indexOf('apeiron-') !== 0) {
				return;
			}
			Array.prototype.forEach.call(tips, function(tip) {
				tip.remove();
			});
		}, true);
	}

	function bindHeartbeat() {
		if (!window.wp || !window.wp.heartbeat || !$) {
			return;
		}

		window.wp.heartbeat.enqueue('apeiron_editor_active', true);
		$(document)
			.off('heartbeat-tick.apeironEditorShell')
			.on('heartbeat-tick.apeironEditorShell', function(event, data) {
				if (!data.apeiron_disabled_widgets) {
					return;
				}

				var current = JSON.stringify(data.apeiron_disabled_widgets.slice().sort());
				var previous = JSON.stringify(initialDisabled.slice().sort());
				if (current !== previous) {
					initialDisabled = data.apeiron_disabled_widgets.slice();
					createToggleNotice();
				}
			});
	}

	bindHeartbeat();
	bindStaleTooltipCleanup();
	bindLivePreviews();
	bindPanelControls();
	if (window.elementor && window.elementor.once) {
		window.elementor.once('panel:init', bindLivePreviews);
		window.elementor.once('panel:init', bindPanelControls);
	}
})(window, document, window.jQuery);
