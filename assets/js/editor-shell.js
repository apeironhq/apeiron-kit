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
	if (window.elementor && window.elementor.once) { window.elementor.once('panel:init', bindLivePreviews); }
})(window, document, window.jQuery);
