(function (window, document) {
    'use strict';

    var registered = false;
    var WysiwygView;

    function isCurrentDeviceControl(control) {
        // Group boundaries must stay mounted even when their responsive field is hidden.
        var popover = control.get('popover');
        if (popover && (popover.start || popover.end)) { return true; }
        var responsive = control.get('responsive');
        if (!responsive || (responsive.min && responsive.max)) { return true; }
        var device = responsive.max || responsive.min;
        return !device || device === window.elementor.channels.deviceMode.request('currentMode');
    }

    function orderControlViews(view, controls) {
        var ordered = Object.create(null);
        var index = 0;
        controls.forEach(function (control) {
            var child = view.children.findByModel(control);
            if (child) { child._index = index++; ordered[child.cid] = child; }
        });
        // Marionette's findByIndex reads insertion order, including for popover ownership.
        view.children._views = ordered;
    }

    function imagePreviewAttributes(value) {
        if (!value || typeof value !== 'object' || typeof value.url !== 'string') { return null; }
        var id = Math.floor(Number(value.id) || 0);
        if (!value.url) { return id > 0 ? null : { src: '' }; }
        function allowedUrl(url) {
            if (typeof url !== 'string' || !url) { return false; }
            try { return ['http:', 'https:'].indexOf(new window.URL(url, document.baseURI).protocol) >= 0; }
            catch (error) { return false; }
        }
        if (!allowedUrl(value.url)) { return null; }
        var attributes = { src: value.url, alt: '', loading: 'eager', decoding: 'async', draggable: 'false' };
        if (id <= 0) { return attributes; }
        var attachment = window.wp && window.wp.media && typeof window.wp.media.attachment === 'function' && window.wp.media.attachment(id);
        if (!attachment || attachment.get('url') !== value.url) { return null; }
        var width = Number(attachment.get('width'));
        var height = Number(attachment.get('height'));
        attributes.alt = String(attachment.get('alt') || '').replace(/<[^>]*>/g, '').trim();
        if (!(width > 0 && height > 0)) { return attributes; }
        attributes.width = width;
        attributes.height = height;
        return attributes;
    }

    function registerLivePreview(editor) {
        var LiveView;
        editor.hooks.addFilter('element/view', function (View, model) {
            if (model.get('widgetType') !== 'apeiron-depthscape') { return View; }
            if (!LiveView) {
                LiveView = View.extend({
                    renderOnChange: function (settings) {
                        var changes = settings.changedAttributes && settings.changedAttributes();
                        var root = this.el.querySelector('[data-apeiron-depthscape]');
                        if (this.allowRender && !this.isRendering && root && !this.$el.hasClass('elementor-loading')) {
                            var owner = this.getEditModel().get('settings');
                            var scope = 'root';
                            var index = -1;
                            var pattern = /^(perspective|depth_compensation|editor_preview|motion_style|glide_smoothing|smoothness|snap|snap_ease|stay_fixed|cover_autoplay|idle_enabled|wind_strength|camera_breath|pointer_tilt|gyro_tilt|tilt_strength|particle_(count|style|color|size)|show_hint|hint_(mode|text)|cam_start_(x|y|z|rx|ry|rz))$/;
                            if (settings !== owner) {
                                var layers = owner.get('layers');
                                var scenes = owner.get('scenes');
                                index = layers ? layers.indexOf(settings) : -1;
                                if (index >= 0) {
                                    scope = 'layer';
                                    pattern = /^(image|depth|anchor|layer_key|layer_space|fit|video_loop|eyebrow|title|subtitle|button_text|idle_(type|amount|speed)|layer_wind|reveal_(split|style)|start_(x|y|scale|rotate|opacity|blur))$/;
                                } else {
                                    scope = 'scene';
                                    index = scenes ? scenes.indexOf(settings) : -1;
                                    pattern = /^(scene_(label|length|ease)|cam_(x|y|z|rx|ry|rz))$/;
                                    if (index < 0) {
                                        var keyframes = owner.get('keyframes');
                                        index = keyframes ? keyframes.indexOf(settings) : -1;
                                        scope = 'keyframe';
                                        pattern = /^kf_(layer|scene|at|duration|ease|x|y|scale|rotate|opacity|blur|reveal)$/;
                                    }
                                }
                            }
                            var values = {};
                            var renderStyles = false;
                            var dynamic = settings.get('__dynamic__') || {};
                            if (dynamic.toJSON) { dynamic = dynamic.toJSON(); }
                            var knownScope = scope === 'root' || index >= 0;
                            if (knownScope && (!changes || !Object.keys(changes).length) && root.classList.contains('is-ready') && !Object.keys(dynamic).length) { return; }
                            var supported = knownScope && changes && Object.keys(changes).every(function (name) {
                                if (dynamic[name]) { return false; }
                                if (pattern.test(name)) { values[name] = changes[name]; return true; }
                                var control = settings.getControl(name);
                                if (control && control.render_type !== 'template' && control.selectors && Object.keys(control.selectors).length) {
                                    renderStyles = true;
                                    return true;
                                }
                                return false;
                            });
                            if (supported && Object.keys(values).length) {
                                var image = Object.prototype.hasOwnProperty.call(values, 'image') ? imagePreviewAttributes(values.image) : null;
                                if (Object.prototype.hasOwnProperty.call(values, 'image') && !image) { return View.prototype.renderOnChange.apply(this, arguments); }
                                if (scope === 'layer' && Object.prototype.hasOwnProperty.call(values, 'layer_space')) { values.depth = settings.get('depth'); }
                                var event = new root.ownerDocument.defaultView.CustomEvent('apeiron:depthscape:preview-change', {
                                    bubbles: true, cancelable: true, detail: { scope: scope, index: index, values: values, settings: settings, css: renderStyles, rows: scope === 'keyframe' ? owner.get('keyframes').toJSON() : null, image: image },
                                });
                                // The engine acknowledges only updates it can apply to the existing DOM.
                                if (!root.dispatchEvent(event)) {
                                    if (renderStyles) { this.renderUI(); }
                                    return;
                                }
                            }
                        }
                        return View.prototype.renderOnChange.apply(this, arguments);
                    },
                });
            }
            return LiveView;
        });
    }

    function registerStylePanel() {
        var editor = window.elementor;
        var panel = editor && editor.panel && editor.panel.currentView;
        var page = panel && panel.getPages('editor');
        if (!page || !page.view || !page.view.extend || page.view.apeironDepthscapeStyle) {
            return;
        }

        var NativeEditor = page.view;
        function isDepthscapeStyle(view) {
            return view.model.get('widgetType') === 'apeiron-depthscape';
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
                if (isDepthscapeStyle(this) && control.get('type') === 'repeater' && /^(scenes|keyframes)$/.test(control.get('name'))) {
                    return editor.getControlView('apeiron_depthscape_layers');
                }
                if (isDepthscapeStyle(this) && control.get('type') === 'wysiwyg') { return WysiwygView; }
                return NativeEditor.prototype.getChildView.apply(this, arguments);
            },
            filter: function (control, index, collection) {
                if (!NativeEditor.prototype.filter.call(this, control, index, collection)) {
                    return false;
                }
                if (!isDepthscapeStyle(this)) {
                    return true;
                }
                if (!isCurrentDeviceControl(control)) { return false; }
                var owner = this.depthscapePopoverOwners && this.depthscapePopoverOwners.get(control.cid);
                if (owner) { return this.depthscapeLoadedPopovers.has(owner); }
                var popover = control.get('popover');
                if (popover && (popover.start || popover.end)) { return true; }
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
                        this.syncDepthscapeControls();
                        control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    }
                }
                return control;
            },
            onRenderCollection: function () {
                NativeEditor.prototype.onRenderCollection.apply(this, arguments);
                if (!isDepthscapeStyle(this)) { return; }
                this.addDepthscapePopoverHeaders();
            },
            addDepthscapePopoverHeaders: function () {
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
            syncDepthscapeControls: function () {
                var view = this;
                var controls = this.collection.filter(this.filter, this);
                var active = new Set(controls.map(function (control) { return control.cid; }));
                this.children.filter(function (child) { return !active.has(child.model.cid); }).forEach(function (child) {
                    view.removeChildView(child);
                });
                orderControlViews(this, controls);
                var owners = new Set();
                controls.forEach(function (control, index) {
                    if (!view.children.findByModel(control)) {
                        view.addChild(control, view.getChildView(control), index);
                        orderControlViews(view, controls);
                        var owner = view.depthscapePopoverOwners.get(control.cid);
                        if (owner) { owners.add(owner); }
                    }
                });
                owners.forEach(function (owner) {
                    var members = view.children.filter(function (child) { return view.depthscapePopoverOwners.get(child.model.cid) === owner; });
                    var popover = (view.popovers || []).find(function (item) { return item.popoverToggleView.model.get('name') === owner; });
                    if (popover) {
                        members.forEach(function (child) { popover.addChild(child); });
                    } else {
                        var group = { children: { each: function (callback) { members.forEach(callback); } } };
                        window.elementorModules.editor.views.ControlsStack.handlePopovers(group);
                        view.popovers = (view.popovers || []).concat(group.popovers);
                        popover = group.popovers[0];
                    }
                    if (popover) { popover.$popover.insertAfter(popover.popoverToggleView.$el); }
                });
                this.addDepthscapePopoverHeaders();
                this.depthscapeVisibleControls = this.depthscapeControlSignature();
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
            onDeviceModeChange: function () {
                NativeEditor.prototype.onDeviceModeChange.apply(this, arguments);
                if (isDepthscapeStyle(this)) { this.scheduleDepthscapePopover(); }
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
                    if (popover && popover.start && control.get('tab') === view.getOption('tab')) {
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
                    view.syncDepthscapeControls();
                    open.forEach(function (name) {
                        var toggle = NativeEditor.prototype.getControlViewByName.call(view, name);
                        if (!toggle) { return; }
                        var popover = toggle.$el.next('.elementor-controls-popover');
                        if (!popover.attr('data-on-hide')) { toggle.onShow(); }
                        if (!popover.is(':visible')) { toggle.activate(); }
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

        var NativeWysiwyg = editor.getControlView('wysiwyg');
        WysiwygView = NativeWysiwyg.extend({
            onBeforeDestroy: function () {
                flushEditor(this);
                var id = this.editorID;
                var quicktags = window.QTags && window.QTags.instances[id];
                NativeWysiwyg.prototype.onBeforeDestroy.apply(this, arguments);
                if (quicktags && !quicktags.theButtons) {
                    // WordPress schedules toolbar initialization after ready; let it finish before disposal.
                    window.QTags.instances[id] = quicktags;
                    var event = 'quicktags-init.apeironDepthscape' + id;
                    window.jQuery(document).on(event, function (e, initialized) {
                        if (initialized !== quicktags) { return; }
                        delete window.QTags.instances[id];
                        window.jQuery(document).off(event);
                    });
                }
            },
        });

        var DeferredRow = NativeRow.extend({
            initialize: function (options) {
                // Closed rows need their title and settings, not a collection of field models.
                NativeRow.prototype.initialize.call(this, Object.assign({}, options, { controlFields: {} }));
                this.listenTo(editor.channels.deviceMode, 'change', this.onModelChange);
            },
            filter: function (control) {
                if (!this.controlsLoaded) {
                    return false;
                }
                if (!isCurrentDeviceControl(control)) { return false; }

                var settings = this.getOption('container').settings;
                return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
            },
            getChildView: function (control) {
                if (!control.depthscapeDefaultsReady) {
                    control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
                    control.depthscapeDefaultsReady = true;
                }
                if (control.get('type') === 'wysiwyg') { return WysiwygView; }
                return NativeRow.prototype.getChildView.apply(this, arguments);
            },
            openControls: function () {
                if (!this.controlsLoaded) {
                    this.controlsLoaded = true;
                    this.syncingControls = true;
                    try {
                        this.collection.reset(Object.values(this.getOption('controlFields')), { silent: true });
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
                            orderControlViews(this, controls);
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
        registerLivePreview(editor);
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
