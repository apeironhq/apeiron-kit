(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        button: '.apeiron-kit-clipboard-tap[data-apeiron-clipboard], .apeiron-kit-clipboard-tap[data-copy-source="current_url"]',
        widget: '.elementor-widget-apeiron-clipboard-tap',
        text: '.apeiron-kit-clipboard-tap__text',
        icon: '.apeiron-kit-clipboard-tap__icon',
        status: '.apeiron-kit-clipboard-tap__status',
    });
    var FEEDBACK_DURATION_MIN = 800;
    var FEEDBACK_DURATION_MAX = 5000;
    var FEEDBACK_DURATION_DEFAULT = 1800;
    var FALLBACK_MESSAGE = Object.freeze({
        success: 'Berhasil disalin',
        empty: 'Tidak ada teks untuk disalin',
        error: 'Gagal menyalin. Silakan coba lagi',
        invalidUrl: 'URL tidak valid',
    });
    var states = new WeakMap();
    // Expando survives duplicate script execution but is not copied by
    // cloneNode(), so Elementor/Swiper clones still receive their own listener.
    var INIT_MARK = '__apeironClipboardInit';

    function clampFeedbackDuration(raw) {
        var value = parseInt(raw, 10);
        if (!Number.isFinite(value)) {
            return FEEDBACK_DURATION_DEFAULT;
        }
        if (value < FEEDBACK_DURATION_MIN) return FEEDBACK_DURATION_MIN;
        if (value > FEEDBACK_DURATION_MAX) return FEEDBACK_DURATION_MAX;
        return value;
    }

    function getCopyText(btn) {
        if (btn.getAttribute('data-copy-source') === 'current_url') {
            return window.location && window.location.href ? window.location.href : '';
        }

        return (btn.getAttribute('data-apeiron-clipboard') || '').trim();
    }

    function isValidUrl(value) {
        try {
            var url = new URL(value);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (err) {
            return false;
        }
    }

    function ensureState(btn) {
        var state = states.get(btn);
        if (!state) {
            state = { timerId: null, originalText: null, previousWidth: null, bound: false, pending: false };
            states.set(btn, state);
        }
        return state;
    }

    function clearTimer(state) {
        if (state.timerId !== null) {
            clearTimeout(state.timerId);
            state.timerId = null;
        }
    }

    function restoreButton(btn) {
        var state = states.get(btn);
        if (!state) {
            return;
        }

        clearTimer(state);

        var textEl = btn.querySelector(SELECTOR.text);
        var iconEl = btn.querySelector(SELECTOR.icon);

        if (state.originalText !== null && textEl) {
            textEl.textContent = state.originalText;
        }
        if (iconEl) {
            iconEl.hidden = false;
        }

        btn.classList.remove('is-copied', 'is-error');
        btn.style.width = state.previousWidth === null ? '' : state.previousWidth;

        state.originalText = null;
        state.previousWidth = null;
    }

    function destroy(btn) {
        restoreButton(btn);
        states.delete(btn);
    }

    // The live region exists from first paint because same-tick regions may not announce.
    function announce(btn, message) {
        var wrapper = btn.parentNode;
        var statusEl = wrapper && wrapper.querySelector ? wrapper.querySelector(SELECTOR.status) : null;
        if (statusEl) {
            statusEl.textContent = message;
        }
    }

    function showFeedback(btn, kind, message) {
        var state = ensureState(btn);
        var textEl = btn.querySelector(SELECTOR.text);
        var iconEl = btn.querySelector(SELECTOR.icon);

        if (state.originalText === null) {
            state.originalText = textEl ? textEl.textContent : '';
            state.previousWidth = btn.style.width;
        }

        clearTimer(state);

        // Preserve width while feedback replaces the label.
        var measuredWidth = btn.getBoundingClientRect ? Math.ceil(btn.getBoundingClientRect().width) : 0;
        if (measuredWidth > 0) {
            btn.style.width = measuredWidth + 'px';
        }

        if (textEl) {
            textEl.textContent = message;
        }
        if (iconEl) {
            iconEl.hidden = true;
        }

        btn.classList.toggle('is-copied', kind === 'success');
        btn.classList.toggle('is-error', kind === 'error');
        announce(btn, message);

        state.timerId = window.setTimeout(function () {
            if (!btn.isConnected) {
                destroy(btn);
                return;
            }
            restoreButton(btn);
            announce(btn, '');
        }, clampFeedbackDuration(btn.getAttribute('data-feedback-duration')));
    }

    function fallbackCopyText(text, onSuccess, onError) {
        var active = document.activeElement;
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.setAttribute('aria-hidden', 'true');
        textarea.style.position = 'fixed';
        textarea.style.top = '0';
        textarea.style.left = '0';
        textarea.style.width = '1px';
        textarea.style.height = '1px';
        textarea.style.padding = '0';
        textarea.style.border = 'none';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        document.body.appendChild(textarea);

        var copied = false;
        try {
            textarea.select();
            if (textarea.setSelectionRange) {
                textarea.setSelectionRange(0, text.length);
            }
            copied = document.execCommand && document.execCommand('copy');
        } catch (err) {
            copied = false;
        } finally {
            document.body.removeChild(textarea);
            // execCommand moves focus into the fallback textarea.
            if (active && active.focus) {
                active.focus();
            }
        }

        if (copied) {
            if (onSuccess) onSuccess();
        } else if (onError) {
            onError();
        }
    }

    function copyText(text, onSuccess, onError) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            try {
                Promise.resolve(navigator.clipboard.writeText(text)).then(onSuccess, function () {
                    fallbackCopyText(text, onSuccess, onError);
                });
            } catch (err) {
                fallbackCopyText(text, onSuccess, onError);
            }
            return;
        }

        fallbackCopyText(text, onSuccess, onError);
    }

    function handleClick(event) {
        var btn = event.currentTarget;
        var state = ensureState(btn);

        if (state.pending) {
            return;
        }

        var text = getCopyText(btn);

        if (!text) {
            showFeedback(btn, 'error', btn.getAttribute('data-empty-message') || FALLBACK_MESSAGE.empty);
            return;
        }

        if (btn.getAttribute('data-copy-source') === 'custom_url' && !isValidUrl(text)) {
            showFeedback(btn, 'error', btn.getAttribute('data-invalid-url-message') || FALLBACK_MESSAGE.invalidUrl);
            return;
        }

        var successMessage = btn.getAttribute('data-copy-message') || FALLBACK_MESSAGE.success;
        var errorMessage = btn.getAttribute('data-error-message') || FALLBACK_MESSAGE.error;

        // Elementor may detach the button before the async write settles.
        state.pending = true;
        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');
        copyText(text, function () {
            state.pending = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
            if (btn.isConnected) showFeedback(btn, 'success', successMessage);
        }, function () {
            state.pending = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
            if (btn.isConnected) showFeedback(btn, 'error', errorMessage);
        });
    }

    function initializeButton(btn) {
        if (btn[INIT_MARK]) {
            if (states.has(btn)) {
                restoreButton(btn);
            }
            return;
        }

        ensureState(btn).bound = true;
        btn[INIT_MARK] = true;
        btn.addEventListener('click', handleClick);
    }

    function findButtons(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container || !container.querySelectorAll) {
            return [];
        }

        var buttons = Array.prototype.slice.call(container.querySelectorAll(SELECTOR.button));

        if (container.matches && container.matches(SELECTOR.button)) {
            buttons.unshift(container);
        }

        return buttons;
    }

    function initializeScope(scope) {
        findButtons(scope).forEach(initializeButton);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initializeScope(document);
        }, { once: true });
    } else {
        initializeScope(document);
    }

    function registerEditorControls() {
        var host;
        try {
            host = window.parent;
            if (!host || host === window || !host.elementor || host.__apeironClipboardControls) return;
        } catch (error) {
            return; // The containing page may be on another origin.
        }
        var editor = host.elementor;
        if (!editor.hooks || !editor.helpers || !editor.helpers.isActiveControl) return;
        host.__apeironClipboardControls = true;

        function registerPanel() {
            var panel = editor.panel && editor.panel.currentView;
            var page = panel && panel.getPages('editor');
            if (!page || !page.view || !page.view.extend || page.view.__apeironClipboardControls) return;
            var NativeEditor = page.view;
            function isClipboardPanel(view) {
                return view.model.get('widgetType') === 'apeiron-clipboard-tap' &&
                    ['content', 'style', 'advanced'].indexOf(view.getOption('tab')) !== -1;
            }

            function deferColorPicker(child) {
                var applySavedValue = child.applySavedValue;
                var dynamicBehaviors = Object.values(child._behaviors || {}).filter(function (behavior) {
                    return typeof behavior.moveDynamicSwitcherToColorPicker === 'function';
                });
                dynamicBehaviors.forEach(function (behavior) {
                    var move = behavior.moveDynamicSwitcherToColorPicker;
                    behavior.moveDynamicSwitcherToColorPicker = function () {
                        if (child.isDestroyed || !this.ui.dynamicSwitcher) return;
                        if (child.colorPicker) return move.apply(this, arguments);
                        this.ui.dynamicSwitcher.detach();
                    };
                });
                child.applySavedValue = function () {
                    if (this.colorPicker) return applySavedValue.apply(this, arguments);
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
                                if (child.isDestroyed) return;
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
                    if (!isClipboardPanel(this)) return NativeEditor.prototype.initCollection.apply(this, arguments);
                    // Merge native defaults only for controls whose views are needed.
                    this.collection = new host.Backbone.Collection(Object.values(this.getOption('controls')));
                    this.__clipboardMergedControls = new Set();
                    this.__clipboardControlsByName = new Map(this.collection.map(function (control) { return [control.get('name'), control]; }));
                },
                getControlModel: function (name) {
                    return this.__clipboardControlsByName ? this.__clipboardControlsByName.get(name) : NativeEditor.prototype.getControlModel.apply(this, arguments);
                },
                getChildView: function (control) {
                    if (isClipboardPanel(this) && !this.__clipboardMergedControls.has(control.cid)) {
                        control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
                        this.__clipboardMergedControls.add(control.cid);
                    }
                    return NativeEditor.prototype.getChildView.apply(this, arguments);
                },
                buildChildView: function (control) {
                    var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
                    if (isClipboardPanel(this) && control.get('type') === 'color' && child.initPicker) {
                        deferColorPicker(child);
                    }
                    if (isClipboardPanel(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
                        var getChildren = child.getResponsiveChildrenViews;
                        child.getResponsiveChildrenViews = function () {
                            return getChildren.apply(this, arguments).filter(function (view) { return !!view; });
                        };
                    }
                    return child;
                },
                _renderChildren: function () {
                    var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
                    if (isClipboardPanel(this) && this.__clipboardActiveTabs) {
                        var view = this;
                        view.__clipboardActiveTabs.forEach(function (name) {
                            var tab = NativeEditor.prototype.getControlViewByName.call(view, name);
                            if (tab && !tab.$el.hasClass('e-tab-active')) tab.el.click();
                        });
                    }
                    return result;
                },
                filter: function (control, index, collection) {
                    if (!NativeEditor.prototype.filter.call(this, control, index, collection)) return false;
                    if (!isClipboardPanel(this)) return true;
                    var owner = this.__clipboardPopoverOwners && this.__clipboardPopoverOwners.get(control.cid);
                    if (owner && !this.__clipboardLoadedPopovers.has(owner)) return false;
                    if (!this.__clipboardRequestedControls.has(control.cid)) {
                        var responsive = control.get('responsive');
                        var popover = control.get('popover');
                        var deviceControls = this.__clipboardResponsiveDevices.get(editor.channels.deviceMode.request('currentMode'));
                        // Keep native popover boundaries and the responsive placeholder parent chain.
                        if (responsive && deviceControls && !deviceControls.has(control.cid) &&
                            !(popover && (popover.start || popover.end))) return false;
                        var innerTab = control.get('inner_tab');
                        if (innerTab && this.__clipboardActiveTabs.get(control.get('tabs_wrapper')) !== innerTab) return false;
                    }
                    var settings = this.model.get('settings');
                    return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
                },
                getControlViewByName: function (name) {
                    var control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    if (!isClipboardPanel(this) || !this.__clipboardPopoverOwners) return control;
                    var model = this.getControlModel(name);
                    if (!model || model.get('section') !== this.activeSection) return control;
                    var owner = this.__clipboardPopoverToggles.has(name) ? name : this.__clipboardPopoverOwners.get(model.cid);
                    if (owner && !this.__clipboardLoadedPopovers.has(owner)) {
                        this.__clipboardLoadedPopovers.add(owner);
                        if (control && name === owner) {
                            // Finish the native radio change before replacing its popover.
                            this.__clipboardPendingPopover = owner;
                            this.__clipboardScheduleControls();
                        } else {
                            this._renderChildren();
                            control = NativeEditor.prototype.getControlViewByName.call(this, name);
                        }
                    }
                    if (!control) {
                        var requested = model;
                        while (requested) {
                            this.__clipboardRequestedControls.add(requested.cid);
                            requested = requested.get('parent') ? this.getControlModel(requested.get('parent')) : null;
                        }
                        this._renderChildren();
                        control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    }
                    return control;
                },
                activateSection: function (section) {
                    if (isClipboardPanel(this) && this.activeSection !== section && this.__clipboardLoadedPopovers) {
                        this.__clipboardLoadedPopovers.clear();
                        this.__clipboardPendingPopover = null;
                        this.__clipboardRequestedControls.clear();
                        this.__clipboardActiveTabs = new Map(this.__clipboardDefaultTabs);
                    }
                    return NativeEditor.prototype.activateSection.apply(this, arguments);
                },
                initialize: function () {
                    NativeEditor.prototype.initialize.apply(this, arguments);
                    if (!isClipboardPanel(this)) return;
                    var view = this;
                    var settings = view.model.get('settings');
                    view.__clipboardPopoverOwners = new Map();
                    view.__clipboardPopoverToggles = new Set();
                    view.__clipboardLoadedPopovers = new Set();
                    view.__clipboardRequestedControls = new Set();
                    view.__clipboardDefaultTabs = new Map();
                    view.__clipboardActiveTabs = new Map();
                    view.__clipboardResponsiveDevices = new Map();
                    var previous = null;
                    var owner = null;
                    view.collection.each(function (control) {
                        var responsive = control.get('responsive');
                        if (responsive) {
                            var device = responsive.max || responsive.min;
                            if (!view.__clipboardResponsiveDevices.has(device)) view.__clipboardResponsiveDevices.set(device, new Set());
                            var parent = control;
                            while (parent) {
                                view.__clipboardResponsiveDevices.get(device).add(parent.cid);
                                parent = view.__clipboardControlsByName.get(parent.get('parent'));
                            }
                        }
                        if (control.get('type') === 'tab' && !view.__clipboardDefaultTabs.has(control.get('tabs_wrapper'))) {
                            view.__clipboardDefaultTabs.set(control.get('tabs_wrapper'), control.get('name'));
                            view.__clipboardActiveTabs.set(control.get('tabs_wrapper'), control.get('name'));
                        }
                        var popover = control.get('popover');
                        if (popover && popover.start) {
                            owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
                            if (owner) view.__clipboardPopoverToggles.add(owner);
                        }
                        if (owner) view.__clipboardPopoverOwners.set(control.cid, owner);
                        if (popover && popover.end) owner = null;
                        previous = control;
                    });

                    var refreshFrame = null;
                    var visibleControls = '';
                    function signature() {
                        return view.collection.filter(view.filter, view).map(function (control) { return control.cid; }).join('|');
                    }
                    function refreshControls() {
                        refreshFrame = null;
                        if (view.isDestroyed || !isClipboardPanel(view)) return;
                        var pendingPopover = view.__clipboardPendingPopover;
                        view.__clipboardPendingPopover = null;
                        if (signature() === visibleControls && !pendingPopover) return;
                        var openPopovers = view.children.filter(function (child) {
                            return child.model.get('type') === 'popover_toggle' && child.$el.next('.elementor-controls-popover').is(':visible');
                        }).map(function (child) { return child.model.get('name'); });
                        if (pendingPopover && openPopovers.indexOf(pendingPopover) === -1) openPopovers.push(pendingPopover);
                        var focusSelector = 'input, select, textarea, button, [tabindex]';
                        var focused = view.children.find(function (child) { return child.el.contains(host.document.activeElement); });
                        var focusIndex = focused ? Array.from(focused.el.querySelectorAll(focusSelector)).indexOf(host.document.activeElement) : -1;
                        view._renderChildren();
                        openPopovers.forEach(function (name) {
                            var toggle = NativeEditor.prototype.getControlViewByName.call(view, name);
                            if (toggle) {
                                if (!toggle.$el.next('.elementor-controls-popover').attr('data-on-hide')) toggle.onShow();
                                toggle.activate();
                            }
                        });
                        if (focused && focusIndex >= 0) {
                            var control = NativeEditor.prototype.getControlViewByName.call(view, focused.model.get('name'));
                            var input = control && control.el.querySelectorAll(focusSelector)[focusIndex];
                            if (input) input.focus({ preventScroll: true });
                        }
                    }
                    view.on('render:collection', function () { visibleControls = signature(); });
                    view.__clipboardScheduleControls = function () {
                        if (isClipboardPanel(view) && !refreshFrame) refreshFrame = host.requestAnimationFrame(refreshControls);
                    };
                    view.listenTo(settings, 'change', view.__clipboardScheduleControls);
                    view.listenTo(editor.channels.deviceMode, 'change', view.__clipboardScheduleControls);
                    view.on('childview:control:tab:clicked', function (tab) {
                        var wrapper = tab.model.get('tabs_wrapper');
                        var name = tab.model.get('name');
                        if (view.__clipboardActiveTabs.get(wrapper) === name) return;
                        view.__clipboardActiveTabs.set(wrapper, name);
                        view.__clipboardScheduleControls();
                    });
                    view.on('destroy', function () {
                        if (refreshFrame) host.cancelAnimationFrame(refreshFrame);
                    });
                }
            });
            page.view.__apeironClipboardControls = true;
        }

        editor.hooks.addAction('panel/open_editor/widget/apeiron-clipboard-tap', registerPanel);
        editor.on('preview:loaded', registerPanel);
        if (editor.panel && editor.panel.currentView) registerPanel();
        else editor.once('panel:init', registerPanel);
    }

    var elementorHookRegistered = false;
    function registerElementorHook() {
        registerEditorControls();
        if (elementorHookRegistered || typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }
        elementorHookRegistered = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-clipboard-tap.default',
            initializeScope
        );
    }

    // Elementor's init event may fire before this footer script loads.
    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();

    if (window.ApeironClipboardTestMode) {
        window.ApeironClipboardTestApi = Object.freeze({
            clampFeedbackDuration: clampFeedbackDuration,
            getCopyText: getCopyText,
            isValidUrl: isValidUrl,
            initializeButton: initializeButton,
            showFeedback: showFeedback,
            restoreButton: restoreButton,
            destroy: destroy,
            findButtons: findButtons,
        });
    }
}());
