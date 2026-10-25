(function (window, document) {
    'use strict';

    var registered = false;

    function registerStylePanel() {
        var editor = window.elementor;
        var panel = editor && editor.panel && editor.panel.currentView;
        var page = panel && panel.getPages('editor');
        if (!page || !page.view || !page.view.extend || page.view.apeironDepthscapeStyle) {
            return;
        }

        var NativeEditor = page.view;
        function isDepthscapeStyle(view) {
            return view.model.get('widgetType') === 'apeiron-depthscape' && view.getOption('tab') === 'style';
        }

        page.view = NativeEditor.extend({
            initCollection: function () {
                if (!isDepthscapeStyle(this)) {
                    return NativeEditor.prototype.initCollection.apply(this, arguments);
                }
                // Merge defaults only for controls that will actually be mounted.
                this.collection = new window.Backbone.Collection(Object.values(this.getOption('controls')));
            },
            getChildView: function (control) {
                if (isDepthscapeStyle(this) && !control.depthscapeDefaultsReady) {
                    control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
                    control.depthscapeDefaultsReady = true;
                }
                return NativeEditor.prototype.getChildView.apply(this, arguments);
            },
            filter: function (control, index, collection) {
                if (!NativeEditor.prototype.filter.call(this, control, index, collection)) {
                    return false;
                }
                if (!isDepthscapeStyle(this)) {
                    return true;
                }
                var owner = this.depthscapePopoverOwners && this.depthscapePopoverOwners.get(control.cid);
                if (owner) { return this.depthscapeLoadedPopovers.has(owner); }
                if (control.get('popover')) { return true; }
                var settings = this.model.get('settings');
                return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
            },
            getControlViewByName: function (name) {
                var control = NativeEditor.prototype.getControlViewByName.call(this, name);
                if (!isDepthscapeStyle(this) || !this.depthscapePopoverOwners) {
                    return control;
                }
                var model = this.getControlModel(name);
                if (!model || model.get('section') !== this.activeSection) {
                    return control;
                }
                var owner = this.depthscapePopoverToggles.has(name) ? name : this.depthscapePopoverOwners.get(model.cid);
                if (owner && !this.depthscapeLoadedPopovers.has(owner)) {
                    this.depthscapeLoadedPopovers.add(owner);
                    if (control && name === owner) {
                        // Let the clicked radio finish changing before replacing its view.
                        this.depthscapePendingPopover = owner;
                        this.scheduleDepthscapePopover();
                    } else {
                        this._renderChildren();
                        control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    }
                }
                return control;
            },
            onRenderCollection: function () {
                NativeEditor.prototype.onRenderCollection.apply(this, arguments);
                if (!isDepthscapeStyle(this)) { return; }
                this.children.each(function (control) {
                    if (control.model.get('type') !== 'popover_toggle' || control.isGlobalActive()) { return; }
                    var popover = control.$el.next('.elementor-controls-popover');
                    var reset = control.$el.find('.elementor-control-popover-toggle-reset-label');
                    if (!popover.length || !reset.length || popover.children('.e-group-control-header').length) { return; }
                    var header = window.jQuery('<div>', { class: 'e-group-control-header' });
                    header.append(window.jQuery('<span>').text(control.model.get('label')));
                    var tools = window.jQuery('<div>', { class: 'e-control-tools' });
                    tools.append(reset.addClass('e-control-tool'));
                    popover.prepend(header.append(tools));
                });
            },
            activateSection: function (section) {
                if (isDepthscapeStyle(this) && this.activeSection !== section && this.depthscapeLoadedPopovers) {
                    this.depthscapeLoadedPopovers.clear();
                    this.depthscapePendingPopover = null;
                    window.cancelAnimationFrame(this.depthscapePopoverFrame);
                    this.depthscapePopoverFrame = null;
                }
                return NativeEditor.prototype.activateSection.apply(this, arguments);
            },
            initialize: function () {
                NativeEditor.prototype.initialize.apply(this, arguments);
                if (!isDepthscapeStyle(this)) {
                    return;
                }

                this.el.classList.add('apeiron-depthscape-style-panel');
                var view = this;
                var previous = null;
                var owner = null;
                this.depthscapePopoverOwners = new Map();
                this.depthscapePopoverToggles = new Set();
                this.depthscapeLoadedPopovers = new Set();
                this.collection.each(function (control) {
                    var popover = control.get('popover');
                    if (popover && popover.start && control.get('tab') === 'style') {
                        owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
                        if (owner) { view.depthscapePopoverToggles.add(owner); }
                    }
                    if (owner) { view.depthscapePopoverOwners.set(control.cid, owner); }
                    if (popover && popover.end) { owner = null; }
                    previous = control;
                });
                this.depthscapeControlSignature = function () {
                    return view.collection.filter(view.filter, view).map(function (control) { return control.cid; }).join('|');
                };
                this.on('render:collection', function () {
                    view.depthscapeVisibleControls = view.depthscapeControlSignature();
                });
                this.listenTo(this.model.get('settings'), 'change', this.scheduleDepthscapePopover);
                this.on('destroy', function () {
                    window.cancelAnimationFrame(view.depthscapePopoverFrame);
                    view.el.classList.remove('apeiron-depthscape-style-panel');
                });
            },
            scheduleDepthscapePopover: function () {
                if (this.depthscapePopoverFrame) { return; }
                var view = this;
                this.depthscapePopoverFrame = window.requestAnimationFrame(function () {
                    view.depthscapePopoverFrame = null;
                    if (view.isDestroyed) { return; }
                    var pending = view.depthscapePendingPopover;
                    view.depthscapePendingPopover = null;
                    if (!pending && view.depthscapeControlSignature() === view.depthscapeVisibleControls) { return; }
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
                        if (!toggle) { return; }
                        if (!toggle.$el.next('.elementor-controls-popover').attr('data-on-hide')) { toggle.onShow(); }
                        toggle.activate();
                    });
                    if (focused && focusIndex >= 0) {
                        var control = NativeEditor.prototype.getControlViewByName.call(view, focused.model.get('name'));
                        var input = control && control.el.querySelectorAll(selector)[focusIndex];
                        if (input) { input.focus({ preventScroll: true }); }
                    }
                });
            },
        });
        page.view.apeironDepthscapeStyle = true;
    }

    function registerLayerRepeater() {
        var editor = window.elementor;
        if (registered || !editor || !editor.addControlView || !editor.getControlView) {
            return;
        }

        var Repeater = editor.getControlView('repeater');
        var NativeRow = Repeater && Repeater.prototype.childView;
        if (!NativeRow || !NativeRow.extend) {
            return;
        }

        function flushEditor(control) {
            var textEditor = control.editor;
            if (control.model.get('type') === 'wysiwyg' && textEditor && textEditor.initialized
                && textEditor.isDirty() && textEditor.getContent() !== control.getControlValue()) {
                control.saveEditor();
            }
        }

        var DeferredRow = NativeRow.extend({
            initialize: function (options) {
                // Closed rows need their title and settings, not a collection of field models.
                NativeRow.prototype.initialize.call(this, Object.assign({}, options, { controlFields: {} }));
            },
            filter: function (control) {
                if (!this.controlsLoaded) {
                    return false;
                }

                var settings = this.getOption('container').settings;
                return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
            },
            openControls: function () {
                if (!this.controlsLoaded) {
                    this.controlsLoaded = true;
                    this.syncingControls = true;
                    try {
                        this.collection.reset(Object.values(editor.mergeControlsSettings(this.getOption('controlFields'))), { silent: true });
                        this.render();
                    } finally {
                        this.syncingControls = false;
                    }
                }
            },
            closeControls: function () {
                if (this.controlsLoaded) {
                    this.controlsLoaded = false;
                    this.children.each(flushEditor);
                    this.collection.reset([], { silent: true });
                    this.render();
                }
            },
            onModelChange: function () {
                NativeRow.prototype.onModelChange.apply(this, arguments);
                if (!this.controlsLoaded || this.syncingControls) {
                    return;
                }

                this.syncingControls = true;
                try {
                    var controls = this.collection.filter(this.filter, this);
                    var active = Object.create(null);
                    var changed = false;
                    controls.forEach(function (control) {
                        active[control.cid] = true;
                    });

                    this.children.filter(function (view) {
                        return !active[view.model.cid];
                    }).forEach(function (view) {
                        flushEditor(view);
                        this.removeChildView(view);
                        changed = true;
                    }, this);

                    // Keep existing inputs alive so typing and keyboard focus survive condition changes.
                    controls.forEach(function (control, index) {
                        if (!this.children.findByModel(control)) {
                            this.addChild(control, this.getChildView(control), index);
                            changed = true;
                        }
                    }, this);

                    if (changed) {
                        NativeRow.prototype.onRender.call(this);
                    }
                } finally {
                    this.syncingControls = false;
                }
            },
        });

        editor.addControlView('apeiron_depthscape_layers', Repeater.extend({
            childView: DeferredRow,
            onRender: function () {
                this.$el.addClass('elementor-control-type-repeater');
                return Repeater.prototype.onRender.apply(this, arguments);
            },
            editRow: function (row) {
                var current = this.currentEditableChild;
                if (current) {
                    Repeater.prototype.editRow.call(this, current);
                }

                this.children.each(function (child) {
                    child.closeControls();
                });

                if (current !== row) {
                    row.openControls();
                    return Repeater.prototype.editRow.call(this, row);
                }
            },
        }));

        registered = true;
        registerStylePanel();
        editor.on('preview:loaded', registerStylePanel);
        editor.once('panel:init', registerStylePanel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerLayerRepeater, { once: true });
    } else {
        registerLayerRepeater();
    }

    if (window.elementor && window.elementor.on) {
        window.elementor.on('panel:init', registerLayerRepeater);
    }
})(window, document);
