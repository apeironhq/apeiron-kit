(function (window, document) {
	'use strict';

	var panel;
	var scheduled = false;
	var activeStepKey = '';
	var currentModel;
	var boundCollections = [];
	var initialized = false;

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
