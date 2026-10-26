// Server offset avoids client clock drift; WeakMap state supports safe Elementor re-renders.
(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        root: '[data-apeiron-countdown]',
        part: '[data-apeiron-countdown-part]',
        expiredMessage: '.apeiron-kit-countdown__expired',
        widget: '.elementor-widget-apeiron-countdown, .elementor-widget-apeiron-pulse-countdown',
    });
    var UNIT_SECONDS = Object.freeze({
        days: 86400,
        hours: 3600,
        minutes: 60,
        seconds: 1,
    });
    var DIGIT_PADDING_MIN = 2;
    var DIGIT_PADDING_MAX = 4;
    var states = new WeakMap();

    function clampDigitPadding(raw) {
        var value = parseInt(raw, 10);
        if (!Number.isFinite(value)) {
            return DIGIT_PADDING_MIN;
        }
        if (value < DIGIT_PADDING_MIN) return DIGIT_PADDING_MIN;
        if (value > DIGIT_PADDING_MAX) return DIGIT_PADDING_MAX;
        return value;
    }

    function calculateParts(milliseconds, visibleParts) {
        var remainingSeconds = Math.max(Math.ceil(milliseconds / 1000), 0);
        var values = {};

        visibleParts.forEach(function (part) {
            var unitSeconds = UNIT_SECONDS[part];
            if (!unitSeconds) {
                return;
            }

            values[part] = Math.floor(remainingSeconds / unitSeconds);
            remainingSeconds %= unitSeconds;
        });

        return values;
    }

    function destroy(root) {
        var state = states.get(root);
        if (state && state.timerId !== null) {
            clearTimeout(state.timerId);
        }

        states.delete(root);
        root.classList.remove('is-expired');

        var expiredMessage = root.querySelector(SELECTOR.expiredMessage);
        if (expiredMessage) {
            expiredMessage.hidden = true;
        }
    }

    function expire(root, state) {
        if (state.timerId !== null) {
            clearTimeout(state.timerId);
            state.timerId = null;
        }

        root.classList.add('is-expired');

        var expiredMessage = root.querySelector(SELECTOR.expiredMessage);
        if (expiredMessage) {
            expiredMessage.hidden = false;
        }
    }

    function tick(root) {
        var state = states.get(root);
        if (!state) {
            return;
        }

        if (!root.isConnected) {
            destroy(root);
            return;
        }

        var now = Date.now() + state.serverOffset;
        var remaining = state.targetTimestamp - now;
        var values = calculateParts(remaining, state.visibleParts);
        var digitPadding = state.digitPadding;

        state.partElements.forEach(function (element, part) {
            element.textContent = String(values[part] || 0).padStart(digitPadding, '0');
        });

        if (remaining <= 0) {
            expire(root, state);
            return;
        }

        state.timerId = window.setTimeout(function () {
            tick(root);
        }, Math.min(1000, remaining));
    }

    function initializeRoot(root) {
        destroy(root);

        var target = root.getAttribute('data-apeiron-countdown');
        var targetTimestamp = Number(target);
        if (!target || !Number.isFinite(targetTimestamp)) {
            return;
        }

        var serverNowAttr = root.getAttribute('data-apeiron-server-now');
        var serverNow = Number(serverNowAttr);
        var serverOffset = (serverNowAttr && Number.isFinite(serverNow))
            ? serverNow - Date.now()
            : 0;

        var digitPadding = clampDigitPadding(root.getAttribute('data-apeiron-digit-padding'));

        var partElements = new Map();
        root.querySelectorAll(SELECTOR.part).forEach(function (element) {
            var part = element.getAttribute('data-apeiron-countdown-part');
            if (Object.prototype.hasOwnProperty.call(UNIT_SECONDS, part)) {
                partElements.set(part, element);
            }
        });

        if (partElements.size === 0) {
            return;
        }

        states.set(root, {
            timerId: null,
            partElements: partElements,
            targetTimestamp: targetTimestamp,
            serverOffset: serverOffset,
            digitPadding: digitPadding,
            visibleParts: Array.from(partElements.keys()),
        });

        tick(root);
    }

    function findRoots(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container) {
            return [];
        }

        var roots = container.querySelectorAll
            ? Array.from(container.querySelectorAll(SELECTOR.root))
            : [];

        if (container.matches && container.matches(SELECTOR.root)) {
            roots.unshift(container);
        }

        if (roots.length === 0 && container.closest) {
            var widget = container.closest(SELECTOR.widget);
            return widget ? Array.from(widget.querySelectorAll(SELECTOR.root)) : [];
        }

        return roots;
    }

    function initializeScope(scope) {
        findRoots(scope).forEach(initializeRoot);
    }

    function updateEditorPreview(widget, change) {
        if (!change.supports(['digit_padding'])) { return false; }
        var root = widget.querySelector(SELECTOR.root);
        var state = root && states.get(root);
        if (!state) { return false; }
        state.digitPadding = clampDigitPadding(change.get('digit_padding'));
        root.setAttribute('data-apeiron-digit-padding', String(state.digitPadding));
        if (state.timerId !== null) { clearTimeout(state.timerId); state.timerId = null; }
        tick(root);
        return true;
    }

    function registerEditorControls() {
        var host;
        try {
            host = window.parent;
            if (!host || host === window || !host.elementor || host.__apeironCountdownControls) return;
        } catch (error) {
            return; // The containing page may be on another origin.
        }

        var editor = host.elementor;
        window.__apeironEditorPreviews = window.__apeironEditorPreviews || {};
        window.__apeironEditorPreviews['apeiron-countdown'] = updateEditorPreview;
        window.__apeironEditorPreviews['apeiron-pulse-countdown'] = updateEditorPreview;
        if (!editor.hooks || !editor.helpers || !editor.helpers.isActiveControl) return;
        host.__apeironCountdownControls = true;

        function registerPanel() {
            var panel = editor.panel && editor.panel.currentView;
            var page = panel && panel.getPages('editor');
            if (!page || !page.view || !page.view.extend || page.view.__apeironCountdownControls) return;
            var NativeEditor = page.view;
            function isCountdownPanel(view) {
                return ['apeiron-countdown', 'apeiron-pulse-countdown'].indexOf(view.model.get('widgetType')) !== -1 &&
                    ['content', 'style', 'advanced'].indexOf(view.getOption('tab')) !== -1;
            }

            function hideTooltips(view) {
                view.$el.find('[original-title]').each(function () {
                    var tooltip = host.jQuery(this).data('tipsy');
                    if (tooltip) tooltip.hide();
                });
            }

            function closePendingPopover(view) {
                var name = view.__countdownPendingPopover;
                if (!name) return;
                view.__countdownPendingPopover = null;
                var toggle = NativeEditor.prototype.getControlViewByName.call(view, name);
                if (toggle) toggle.onPopoverHide();
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
                    if (!isCountdownPanel(this)) return NativeEditor.prototype.initCollection.apply(this, arguments);
                    // Unopened controls retain their IDs without merging every native default.
                    this.collection = new host.Backbone.Collection(Object.values(this.getOption('controls')));
                    this.__countdownMergedControls = new Set();
                    this.__countdownControlsByName = new Map(this.collection.map(function (control) { return [control.get('name'), control]; }));
                },
                getControlModel: function (name) {
                    return this.__countdownControlsByName ? this.__countdownControlsByName.get(name) : NativeEditor.prototype.getControlModel.apply(this, arguments);
                },
                getChildView: function (control) {
                    if (isCountdownPanel(this) && !this.__countdownMergedControls.has(control.cid)) {
                        control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
                        this.__countdownMergedControls.add(control.cid);
                    }
                    return NativeEditor.prototype.getChildView.apply(this, arguments);
                },
                buildChildView: function (control) {
                    var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
                    if (isCountdownPanel(this) && control.get('type') === 'color' && child.initPicker) deferColorPicker(child);
                    if (isCountdownPanel(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
                        var getChildren = child.getResponsiveChildrenViews;
                        child.getResponsiveChildrenViews = function () {
                            return getChildren.apply(this, arguments).filter(function (view) { return !!view; });
                        };
                    }
                    return child;
                },
                _renderChildren: function () {
                    if (isCountdownPanel(this)) hideTooltips(this);
                    var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
                    if (isCountdownPanel(this) && this.__countdownActiveTabs) {
                        var view = this;
                        view.__countdownActiveTabs.forEach(function (name) {
                            var tab = NativeEditor.prototype.getControlViewByName.call(view, name);
                            if (tab && !tab.$el.hasClass('e-tab-active')) tab.el.click();
                        });
                    }
                    return result;
                },
                filter: function (control, index, collection) {
                    if (!NativeEditor.prototype.filter.call(this, control, index, collection)) return false;
                    if (!isCountdownPanel(this)) return true;
                    var owner = this.__countdownPopoverOwners && this.__countdownPopoverOwners.get(control.cid);
                    if (owner && !this.__countdownLoadedPopovers.has(owner)) return false;
                    if (!this.__countdownRequestedControls.has(control.cid)) {
                        var responsive = control.get('responsive');
                        var popover = control.get('popover');
                        var deviceControls = this.__countdownResponsiveDevices.get(editor.channels.deviceMode.request('currentMode'));
                        // Preserve the native responsive parent chain and popover boundaries.
                        if (responsive && deviceControls && !deviceControls.has(control.cid) &&
                            !(popover && (popover.start || popover.end))) return false;
                        var innerTab = control.get('inner_tab');
                        if (innerTab && this.__countdownActiveTabs.get(control.get('tabs_wrapper')) !== innerTab) return false;
                    }
                    var settings = this.model.get('settings');
                    return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
                },
                getControlViewByName: function (name) {
                    var control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    if (!isCountdownPanel(this) || !this.__countdownPopoverOwners) return control;
                    var model = this.getControlModel(name);
                    if (!model || model.get('section') !== this.activeSection) return control;
                    var owner = this.__countdownPopoverToggles.has(name) ? name : this.__countdownPopoverOwners.get(model.cid);
                    if (owner && !this.__countdownLoadedPopovers.has(owner)) {
                        this.__countdownLoadedPopovers.add(owner);
                        if (control && name === owner) {
                            // Let Elementor finish the radio change before replacing its input.
                            this.__countdownPendingPopover = owner;
                            this.__countdownScheduleControls();
                        } else {
                            this._renderChildren();
                            control = NativeEditor.prototype.getControlViewByName.call(this, name);
                        }
                    }
                    if (!control) {
                        var requested = model;
                        while (requested) {
                            this.__countdownRequestedControls.add(requested.cid);
                            requested = requested.get('parent') ? this.getControlModel(requested.get('parent')) : null;
                        }
                        this._renderChildren();
                        control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    }
                    return control;
                },
                activateSection: function (section) {
                    if (isCountdownPanel(this) && this.activeSection !== section && this.__countdownLoadedPopovers) {
                        closePendingPopover(this);
                        this.__countdownLoadedPopovers.clear();
                        this.__countdownPendingPopover = null;
                        this.__countdownRequestedControls.clear();
                        this.__countdownActiveTabs = new Map(this.__countdownDefaultTabs);
                    }
                    return NativeEditor.prototype.activateSection.apply(this, arguments);
                },
                initialize: function () {
                    NativeEditor.prototype.initialize.apply(this, arguments);
                    if (!isCountdownPanel(this)) return;
                    var view = this;
                    var settings = view.model.get('settings');
                    function onNavigationClick(event) {
                        var tab = event.target.closest('.elementor-panel-navigation-tab');
                        if (tab && tab.getAttribute('data-tab') !== view.activeTab) closePendingPopover(view);
                    }
                    // Clear the native active-control route before tab navigation copies it.
                    view.el.addEventListener('click', onNavigationClick, true);
                    view.__countdownPopoverOwners = new Map();
                    view.__countdownPopoverToggles = new Set();
                    view.__countdownLoadedPopovers = new Set();
                    view.__countdownRequestedControls = new Set();
                    view.__countdownDefaultTabs = new Map();
                    view.__countdownActiveTabs = new Map();
                    view.__countdownResponsiveDevices = new Map();
                    var previous = null;
                    var owner = null;
                    view.collection.each(function (control) {
                        var responsive = control.get('responsive');
                        if (responsive) {
                            var device = responsive.max || responsive.min;
                            if (!view.__countdownResponsiveDevices.has(device)) view.__countdownResponsiveDevices.set(device, new Set());
                            var parent = control;
                            while (parent) {
                                view.__countdownResponsiveDevices.get(device).add(parent.cid);
                                parent = view.__countdownControlsByName.get(parent.get('parent'));
                            }
                        }
                        if (control.get('type') === 'tab' && !view.__countdownDefaultTabs.has(control.get('tabs_wrapper'))) {
                            view.__countdownDefaultTabs.set(control.get('tabs_wrapper'), control.get('name'));
                            view.__countdownActiveTabs.set(control.get('tabs_wrapper'), control.get('name'));
                        }
                        var popover = control.get('popover');
                        if (popover && popover.start) {
                            owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
                            if (owner) view.__countdownPopoverToggles.add(owner);
                        }
                        if (owner) view.__countdownPopoverOwners.set(control.cid, owner);
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
                        if (view.isDestroyed || !isCountdownPanel(view)) return;
                        var pendingPopover = view.__countdownPendingPopover;
                        view.__countdownPendingPopover = null;
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
                    view.__countdownScheduleControls = function () {
                        if (isCountdownPanel(view) && !refreshFrame) refreshFrame = host.requestAnimationFrame(refreshControls);
                    };
                    view.listenTo(settings, 'change', view.__countdownScheduleControls);
                    view.listenTo(editor.channels.deviceMode, 'change', view.__countdownScheduleControls);
                    view.on('childview:control:tab:clicked', function (tab) {
                        var wrapper = tab.model.get('tabs_wrapper');
                        var name = tab.model.get('name');
                        if (view.__countdownActiveTabs.get(wrapper) === name) return;
                        closePendingPopover(view);
                        view.__countdownActiveTabs.set(wrapper, name);
                        view.__countdownScheduleControls();
                    });
                    view.on('destroy', function () {
                        if (refreshFrame) host.cancelAnimationFrame(refreshFrame);
                    });
                    view.on('before:destroy', function () {
                        view.el.removeEventListener('click', onNavigationClick, true);
                        hideTooltips(view);
                    });
                }
            });
            page.view.__apeironCountdownControls = true;
        }

        editor.hooks.addAction('panel/open_editor/widget/apeiron-countdown', registerPanel);
        editor.hooks.addAction('panel/open_editor/widget/apeiron-pulse-countdown', registerPanel);
        editor.on('preview:loaded', registerPanel);
        if (editor.panel && editor.panel.currentView) registerPanel();
        else editor.once('panel:init', registerPanel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initializeScope(document);
        }, { once: true });
    } else {
        initializeScope(document);
    }

    var elementorHookRegistered = false;
    function registerElementorHooks() {
        registerEditorControls();
        if (elementorHookRegistered || typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }
        elementorHookRegistered = true;

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-countdown.default',
            initializeScope
        );

        // Keep saved legacy widgets initializing.
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-pulse-countdown.default',
            initializeScope
        );
    }

    window.addEventListener('elementor/frontend/init', registerElementorHooks);
    registerElementorHooks();

    if (window.ApeironCountdownTestMode) {
        window.ApeironCountdownTestApi = Object.freeze({
            calculateParts: calculateParts,
            clampDigitPadding: clampDigitPadding,
            destroy: destroy,
            initializeRoot: initializeRoot,
            tick: tick,
        });
    }
}());
