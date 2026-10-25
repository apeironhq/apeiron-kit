(function (window, document) {
    'use strict';

    var TYPE = 'apeiron-depthscape';
    var MARKER = 'apeiron_depthscape_layer';
    var editing = new WeakMap();
    var activeView = null;
    var mutating = false;
    var initialized = false;

    function isDepthscape(container) {
        return !!(container && container.type === 'widget' && container.model && container.model.get('widgetType') === TYPE);
    }

    function owner(container) {
        while (container && !isDepthscape(container)) {
            container = container.parent;
        }
        return container || null;
    }

    function run(command, args) {
        return window.$e.run(command, args);
    }

    function setRow(root, row, values) {
        var rows = root.settings.get('layers');
        run('document/elements/settings', { container: root.repeaters.layers.children[rows.indexOf(row)], settings: values });
    }

    function contentElements(root, row) {
        var elements = [];
        function plainText(value) {
            return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\r?\n/g, '<br>');
        }
        var dynamic = row.get('__dynamic__') || {};
        if (dynamic.toJSON) { dynamic = dynamic.toJSON(); }

        ['eyebrow', 'title', 'subtitle', 'body'].forEach(function (name) {
            var value = row.get(name);
            if (!value && !dynamic[name]) { return; }
            var heading = name === 'eyebrow' || name === 'title';
            var property = heading ? 'title' : 'editor';
            var settings = { align: row.get('layer_text_align') || root.settings.get('text_align') || 'center' };
            settings[property] = name === 'body' ? (value || '') : plainText(value);
            if (heading) { settings.header_size = name === 'title' ? 'h2' : 'span'; }
            settings[heading ? 'title_color' : 'text_color'] = row.get('layer_text_color') || root.settings.get(name + '_color') || '#ffffff';
            var prefix = name + '_typography_';
            Object.keys(root.settings.attributes).forEach(function (key) {
                if (key.indexOf(prefix) === 0) { settings['typography_' + key.slice(prefix.length)] = root.settings.get(key); }
            });
            if (dynamic[name]) { settings.__dynamic__ = {}; settings.__dynamic__[property] = dynamic[name]; }
            elements.push({ elType: 'widget', widgetType: heading ? 'heading' : 'text-editor', settings: settings });
        });

        if (row.get('button_text') || dynamic.button_text) {
            var button = { text: plainText(row.get('button_text')), link: row.get('button_link') || {}, align: row.get('layer_text_align') || 'center' };
            if (dynamic.button_text || dynamic.button_link) {
                button.__dynamic__ = {};
                if (dynamic.button_text) { button.__dynamic__.text = dynamic.button_text; }
                if (dynamic.button_link) { button.__dynamic__.link = dynamic.button_link; }
            }
            elements.push({ elType: 'widget', widgetType: 'button', settings: button });
        }
        elements.forEach(function (element) {
            element.id = window.elementorCommon.helpers.getUniqueId();
            element.elements = [];
        });
        return elements;
    }

    function setMarker(child, row) {
        if (child.settings.get(MARKER) !== row.get('_id')) {
            var values = {}; values[MARKER] = row.get('_id');
            run('document/elements/settings', { container: child, settings: values });
        }
    }

    function syncContent(view) {
        if (mutating || view.isDestroyed) { return; }
        var root = view.getContainer();
        var rows = root.settings.get('layers');
        if (!rows || !rows.each) { return; }
        mutating = true;
        try {
            var children = root.children.slice();
            var used = new Set();
            rows.models.slice().forEach(function (row) {
                var id = row.get('content_id');
                if (row.get('layer_type') !== 'container' && !id) { return; }
                var child = children.find(function (item) { return item.id === id && !used.has(item.id); });
                if (!child) {
                    child = children.find(function (item) { return !used.has(item.id) && item.settings.get(MARKER) === row.get('_id'); });
                }
                if (!child && id && view.removedChildren.has(id)) {
                    run('document/repeater/remove', { container: root, name: 'layers', index: rows.indexOf(row) });
                    return;
                }
                if (!child && id && children.some(function (item) { return item.id === id; })) {
                    var sourceChild = children.find(function (item) { return item.id === id; });
                    child = run('document/elements/create', {
                        container: root,
                        model: sourceChild.model.toJSON(),
                        options: { clone: true, at: root.children.indexOf(sourceChild) + 1, edit: false },
                    });
                    children.push(child);
                }
                if (!child && row.get('layer_type') === 'container') {
                    var settings = { _title: row.get('layer_key') || 'Konten', content_width: 'full' };
                    settings[MARKER] = row.get('_id');
                    child = run('document/elements/create', { container: root, model: { elType: 'container', settings: settings, elements: contentElements(root, row) }, options: { edit: false } });
                    children.push(child);
                }
                if (child) {
                    used.add(child.id);
                    if (id !== child.id) { setRow(root, row, { content_id: child.id }); }
                    setMarker(child, row);
                }
            });

            view.removedContents.forEach(function (id) {
                var child = children.find(function (item) { return item.id === id; });
                if (child && !rows.findWhere({ content_id: id })) {
                    run('document/elements/delete', { container: child });
                    used.add(id);
                }
            });

            root.children.slice().forEach(function (child) {
                if (used.has(child.id) || rows.findWhere({ content_id: child.id })) { return; }
                var source = rows.findWhere({ _id: child.settings.get(MARKER) });
                var values = source ? Object.assign({}, source.attributes) : { layer_key: 'konten-' + child.id, layer_type: 'container', layer_space: 'screen' };
                delete values._id;
                values.content_id = child.id;
                var row = run('document/repeater/insert', { container: root, name: 'layers', model: values });
                setMarker(child, row);
            });

            var linked = rows.filter(function (row) { return !!row.get('content_id'); });
            if (view.syncDirection === 'children') {
                var ordered = root.children.map(function (child) { return rows.findWhere({ content_id: child.id }); }).filter(Boolean);
                var index = 0;
                var desired = rows.map(function (row) { return row.get('content_id') && ordered[index] ? ordered[index++] : row; });
                desired.forEach(function (row, at) {
                    var from = rows.indexOf(row);
                    if (from !== at) { run('document/repeater/move', { container: root, name: 'layers', sourceIndex: from, targetIndex: at }); }
                });
            } else {
                linked.forEach(function (row, at) {
                    var child = root.children.find(function (item) { return item.id === row.get('content_id'); });
                    if (child && root.children.indexOf(child) !== at) {
                        run('document/elements/move', { container: child, target: root, options: { at: at, edit: false } });
                    }
                });
            }
            view.removedChildren.clear();
            view.removedContents.clear();
        } finally {
            mutating = false;
        }
    }

    function queueSync(view, direction) {
        if (mutating || view.isDestroyed) { return; }
        if (direction) { view.syncDirection = direction; }
        if (view.contentFrame) { return; }
        view.contentFrame = window.requestAnimationFrame(function () {
            view.contentFrame = null;
            syncContent(view);
        });
    }

    function editContent(root, id, focus) {
        if (activeView && activeView !== root.view && !activeView.isDestroyed) {
            editing.delete(activeView.model);
            activeView.applyContentEditing();
        }
        activeView = root.view;
        editing.set(root.model, { id: id, focus: !!focus });
        root.view.applyContentEditing();
    }

    function initialize() {
        var editor = window.elementor;
        if (initialized || !editor || !editor.hooks) { return; }
        initialized = true;
        var ContentModel;
        var ContentView;
        var typeRegistered = false;

        editor.hooks.addFilter('element/model', function (Model, attrs) {
            if (attrs.widgetType !== TYPE || !editor.widgetsCache[TYPE].apeiron_nested_content) { return Model; }
            if (!typeRegistered) {
                var type = Object.create(editor.elementsManager.getElementTypeClass(TYPE));
                type.getType = function () { return TYPE; };
                type.getEmptyView = function () { return editor.elementsManager.getElementTypeClass('container').getEmptyView(); };
                editor.elementsManager.registerElementType(type);
                typeRegistered = true;
            }
            if (!ContentModel) {
                ContentModel = Model.extend({ isValidChild: function (child) { return child.get('elType') === 'container'; } });
            }
            return ContentModel;
        });

        editor.hooks.addFilter('element/view', function (View, model) {
            if (model.get('widgetType') !== TYPE || !editor.widgetsCache[TYPE].apeiron_nested_content) { return View; }
            if (!ContentView) {
                ContentView = View.extend({
                    initialize: function () {
                        this.removedChildren = new Set();
                        this.removedContents = new Set();
                        View.prototype.initialize.apply(this, arguments);
                        this.listenTo(this.model.get('elements'), 'add reset sort', function () { queueSync(this, 'children'); });
                        this.listenTo(this.model.get('elements'), 'remove', function (child) {
                            if (!mutating) { this.removedChildren.add(child.id); queueSync(this, 'children'); }
                        });
                    },
                    getChildType: function () { return ['container']; },
                    behaviors: function () {
                        var behaviors = View.prototype.behaviors.apply(this, arguments);
                        // Native child widgets own their inline editors.
                        delete behaviors.InlineEditing;
                        return behaviors;
                    },
                    getEmptyView: function () { return null; },
                    attachBuffer: function () {
                        this.children.each(function (child) {
                            var slot = this.getChildViewContainer(this, child);
                            if (!slot.hasClass('apeiron-depthscape__content-bank')) { slot.empty(); }
                            slot.append(child.el);
                        }, this);
                    },
                    getChildViewContainer: function (parent, child) {
                        var id = child && child.model.id;
                        var slots = this.el.querySelectorAll('[data-ds-content-id]');
                        var slot = Array.from(slots).find(function (el) { return el.getAttribute('data-ds-content-id') === id; });
                        return window.jQuery(slot || this.el.querySelector('.apeiron-depthscape__content-bank'));
                    },
                    attachElContent: function (html) {
                        // Child views must be attached after the remote shell is in the DOM.
                        this.$el.empty().append(this.getHandlesOverlay(), this.getHTMLContent(html));
                        this.bindUIElements();
                        return this;
                    },
                    onClickEdit: function (event) {
                        var closest = event.target.closest('.elementor-element');
                        var content = event.target.closest('[data-ds-content-id]');
                        if (content && this.el.contains(content) && closest !== this.el) { return; }
                        return View.prototype.onClickEdit.apply(this, arguments);
                    },
                    onRender: function () {
                        if (this.isDestroyed) { return; }
                        View.prototype.onRender.apply(this, arguments);
                        var rows = this.model.get('settings').get('layers');
                        if (rows && rows !== this.contentRows) {
                            if (this.contentRows) { this.stopListening(this.contentRows); }
                            this.contentRows = rows;
                            this.listenTo(rows, 'add reset sort change:layer_type change:content_id', function () { queueSync(this, 'layers'); });
                            this.listenTo(rows, 'remove', function (row) {
                                if (!mutating && row.get('content_id')) { this.removedContents.add(row.get('content_id')); }
                                queueSync(this, 'layers');
                            });
                        }
                        queueSync(this);
                        this.applyContentEditing();
                    },
                    applyContentEditing: function () {
                        var state = editing.get(this.model);
                        var shell = this.el.querySelector('[data-apeiron-depthscape]');
                        if (!shell) { return; }
                        Array.from(shell.querySelectorAll('[data-ds-content-id]')).forEach(function (slot) {
                            if (slot.closest('[data-apeiron-depthscape]') !== shell) { return; }
                            var selected = !!state && slot.getAttribute('data-ds-content-id') === state.id;
                            slot.closest('[data-ds-layer]').classList.toggle('is-editing-content', selected);
                        });
                        var editingId = state ? state.id : '';
                        if (shell.apeironEditingContentId !== editingId) {
                            shell.apeironEditingContentId = editingId;
                            shell.dispatchEvent(new CustomEvent('apeiron:depthscape:content-mode', { bubbles: true }));
                        }
                        if (state && state.focus) {
                            var child = this.getContainer().children.find(function (item) { return item.id === state.id; });
                            if (child && child.view.el.closest('[data-ds-content-id]')) {
                                state.focus = false;
                                run('document/elements/select', { container: child });
                                child.model.trigger('request:edit');
                            }
                        }
                    },
                    onBeforeDestroy: function () {
                        window.cancelAnimationFrame(this.contentFrame);
                        if (activeView === this) { activeView = null; }
                        View.prototype.onBeforeDestroy.apply(this, arguments);
                    },
                });
            }
            return ContentView;
        });

        editor.channels.editor.on('apeiron:depthscape:add-content', function (control) {
            var root = owner(control.getOption('container'));
            if (!root) { return; }
            var row = run('document/repeater/insert', { container: root, name: 'layers', model: { layer_key: 'konten-' + window.elementorCommon.helpers.getUniqueId(), layer_type: 'container', layer_space: 'screen' } });
            syncContent(root.view);
            editContent(root, row.get('content_id'), true);
        });
        editor.channels.editor.on('apeiron:depthscape:edit-content', function (control) {
            var rowContainer = control.getOption('container');
            var root = owner(rowContainer);
            if (!root) { return; }
            syncContent(root.view);
            editContent(root, rowContainer.settings.get('content_id'), true);
        });
        editor.channels.editor.on('apeiron:depthscape:preview-content', function (control) {
            var root = owner(control.getOption('container'));
            if (root) { editing.delete(root.model); root.view.applyContentEditing(); root.model.trigger('request:edit'); }
        });
        var openContentEditor = function (panel, model, view) {
            var container = view.getContainer();
            var root = owner(container.parent);
            if (!root) { return; }
            while (container.parent && container.parent !== root) { container = container.parent; }
            editContent(root, container.id, false);
        };
        editor.hooks.addAction('panel/open_editor/widget', openContentEditor);
        editor.hooks.addAction('panel/open_editor/container', openContentEditor);
    }

    initialize();
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initialize); }
    if (window.elementor && window.elementor.on) { window.elementor.on('panel:init', initialize); }
})(window, document);
