(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        root: '[data-apeiron-social-proof-entries]',
        legacyRoot: '.apeiron-social-proof-popup[data-entries]',
        image: '.apeiron-social-proof__image, .apeiron-popup-image',
        name: '.apeiron-social-proof__name, .apeiron-popup-name',
        description: '.apeiron-social-proof__description, .apeiron-popup-desc',
        date: '.apeiron-social-proof__date, .apeiron-popup-date',
        close: '.apeiron-social-proof__close, .apeiron-popup-close',
        widget: '.elementor-widget-apeiron-social-proof',
    });
    var CLASS = Object.freeze({
        visible: 'is-visible',
        legacyVisible: 'show',
        placeholder: 'is-placeholder',
        hasImage: 'has-image',
        product: 'apeiron-social-proof__product apeiron-popup-product',
        placeholderIcon: 'apeiron-social-proof__placeholder-icon apeiron-popup-placeholder-icon',
    });
    var DEFAULTS = Object.freeze({
        textTemplate: '{name} telah membeli {product} pada:',
        displayDuration: 3000,
        intervalDuration: 8000,
        initialDelay: 0,
        maxNotifications: 0,
        animationDuration: 400,
    });
    var PLACEHOLDER_ICON =
        '<svg class="' + CLASS.placeholderIcon + '" width="24" height="24" viewBox="0 0 24 24" fill="none"' +
        ' stroke="currentColor" stroke-width="1.5" aria-hidden="true">' +
        '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';

    var states = new WeakMap();
    var tracked = [];
    // Prevent overlapping notifications from multiple widget instances.
    var activeElement = null;

    function prefersReducedMotion() {
        return typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function getAttribute(element, name, legacyName) {
        var value = element.getAttribute(name);
        if (value === null && legacyName) {
            value = element.getAttribute(legacyName);
        }
        return value;
    }

    function readInt(element, name, legacyName, fallback, minimum) {
        var value = parseInt(getAttribute(element, name, legacyName), 10);
        if (Number.isNaN(value)) {
            return fallback;
        }
        return Math.max(typeof minimum === 'number' ? minimum : 0, value);
    }

    function clearTimers(state) {
        if (!state || !state.timers) {
            return;
        }

        window.clearTimeout(state.timers.next);
        window.clearTimeout(state.timers.show);
        window.clearTimeout(state.timers.hide);
        state.timers.next = null;
        state.timers.show = null;
        state.timers.hide = null;
    }

    function untrack(root) {
        var index = tracked.indexOf(root);
        if (index !== -1) {
            tracked.splice(index, 1);
        }
    }

    function hide(root) {
        root.classList.remove(CLASS.visible);
        root.classList.remove(CLASS.legacyVisible);
        if (activeElement === root) {
            activeElement = null;
        }
    }

    function destroy(root) {
        var state = states.get(root);
        clearTimers(state);

        if (state && state.listeners) {
            state.listeners.forEach(function (entry) {
                entry.target.removeEventListener(entry.type, entry.handler);
            });
        }

        states.delete(root);
        untrack(root);
        hide(root);
    }

    // Stop timers and listeners for roots replaced by Elementor.
    function pruneDetached() {
        tracked.slice().forEach(function (root) {
            if (!root.isConnected) {
                destroy(root);
            }
        });

        if (activeElement && !activeElement.isConnected) {
            activeElement = null;
        }
    }

    function on(state, target, type, handler, options) {
        target.addEventListener(type, handler, options);
        state.listeners.push({ target: target, type: type, handler: handler });
    }

    // Visitor session limits must not hide Elementor previews.
    function isEditorContext() {
        if (document.body && document.body.classList.contains('elementor-editor-active')) {
            return true;
        }

        if (
            typeof elementorFrontend !== 'undefined' &&
            typeof elementorFrontend.isEditMode === 'function' &&
            elementorFrontend.isEditMode()
        ) {
            return true;
        }

        return /(?:^|[?&])elementor-preview=/.test((window.location && window.location.search) || '');
    }

    function getSessionValue(key) {
        if (isEditorContext()) {
            return null;
        }

        try {
            return window.sessionStorage ? window.sessionStorage.getItem('apeiron_sp_' + key) : null;
        } catch (error) {
            return null;
        }
    }

    function setSessionValue(key, value) {
        if (isEditorContext()) {
            return;
        }

        try {
            if (window.sessionStorage) {
                window.sessionStorage.setItem('apeiron_sp_' + key, value);
            }
        } catch (error) {
        }
    }

    function removeSessionValue(key) {
        if (isEditorContext()) {
            return;
        }

        try {
            if (window.sessionStorage) {
                window.sessionStorage.removeItem('apeiron_sp_' + key);
            }
        } catch (error) {
        }
    }

    function getSessionCount(instanceId) {
        var value = parseInt(getSessionValue('shown:' + instanceId), 10);
        return Number.isNaN(value) ? 0 : value;
    }

    function hasReachedSessionLimit(instanceId, maxNotifications) {
        return maxNotifications > 0 && getSessionCount(instanceId) >= maxNotifications;
    }

    function getElementKey(root) {
        if (!root.id) {
            root.id = 'apeiron-social-proof-' + Math.random().toString(36).slice(2);
        }
        return root.id;
    }

    function removeNamePlaceholder(template) {
        return String(template || '')
            .replace(/\{name\}/g, '')
            .replace(/^[\s,]+/, '');
    }

    function renderTemplate(container, template, entry) {
        var parts = String(template || '').split(/(\{name\}|\{product\})/g);
        container.textContent = '';

        parts.forEach(function (part) {
            if (part === '{name}' || part === '{product}') {
                var valueNode = document.createElement('span');
                valueNode.className = part === '{product}' ? CLASS.product : 'apeiron-social-proof__inline-name';
                valueNode.textContent = part === '{product}' ? (entry.product || '') : (entry.name || '');
                container.appendChild(valueNode);
                return;
            }

            if (part !== '') {
                container.appendChild(document.createTextNode(part));
            }
        });
    }

    function renderPlaceholder(image) {
        image.classList.remove(CLASS.hasImage);
        image.classList.add(CLASS.placeholder);
        image.innerHTML = PLACEHOLDER_ICON;
    }

    function renderImage(image, entry) {
        image.textContent = '';

        if (!entry.image) {
            renderPlaceholder(image);
            return;
        }

        image.classList.remove(CLASS.placeholder);
        image.classList.add(CLASS.hasImage);

        var imageElement = document.createElement('img');
        imageElement.alt = entry.name || '';
        imageElement.decoding = 'async';
        imageElement.loading = 'lazy';
        imageElement.addEventListener('error', function () {
            // Ignore failures from an image replaced by a newer entry.
            if (imageElement.parentNode === image) {
                renderPlaceholder(image);
            }
        }, { once: true });
        imageElement.src = entry.image;
        image.appendChild(imageElement);
    }

    function getTimezone(date) {
        var offset = -date.getTimezoneOffset() / 60;
        if (offset >= 7 && offset < 8) return 'WIB';
        if (offset >= 8 && offset < 9) return 'WITA';
        if (offset >= 9 && offset < 10) return 'WIT';
        return 'UTC' + (offset >= 0 ? '+' : '') + offset;
    }

    function formatDate(dateString) {
        if (!dateString) {
            return '-';
        }

        var date = new Date(dateString);
        if (Number.isNaN(date.getTime())) {
            return dateString;
        }

        try {
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }) + ' (' + getTimezone(date) + ')';
        } catch (error) {
            return dateString;
        }
    }

    function paint(state, entry) {
        renderImage(state.popupImage, entry);
        var leadingName = /^\s*\{name\}/.test(state.textTemplate);
        state.popupName.hidden = !leadingName;
        state.popupName.textContent = leadingName ? (entry.name || '') : '';
        renderTemplate(
            state.popupDescription,
            leadingName ? state.textTemplate.replace(/^\s*\{name\}/, '').replace(/^[\s,]+/, '') : state.textTemplate,
            entry
        );
        state.popupDate.textContent = formatDate(entry.datetime || '');
    }

    function scheduleNext(root, delay) {
        var state = states.get(root);
        if (!state) {
            return;
        }

        window.clearTimeout(state.timers.next);
        state.timers.next = window.setTimeout(function () {
            showNextEntry(root);
        }, Math.max(0, delay || 0));
    }

    // Elementor may replace a root while its timer is pending.
    function isStale(root, state) {
        if (states.get(root) !== state) {
            return true;
        }
        if (!root.isConnected) {
            destroy(root);
            return true;
        }
        return false;
    }

    function isPaused(state) {
        return state.hovered || state.focusWithin || document.hidden;
    }

    function beginHideCycle(root, state, delay) {
        var wait = typeof delay === 'number' ? delay : state.displayDuration;
        state.displayDeadline = Date.now() + wait;
        state.timers.show = window.setTimeout(function () {
            if (isStale(root, state)) {
                return;
            }

            if (isPaused(state)) {
                beginHideCycle(root, state, 100);
                return;
            }

            root.classList.remove(CLASS.visible);
            root.classList.remove(CLASS.legacyVisible);

            state.timers.hide = window.setTimeout(function () {
                if (isStale(root, state)) {
                    return;
                }

                if (activeElement === root) {
                    activeElement = null;
                }
                state.currentIndex += 1;
                scheduleNext(root, state.intervalDuration);
            }, state.animationDuration);
        }, wait);
    }

    function showNextEntry(root) {
        var state = states.get(root);
        if (!state || isStale(root, state)) {
            return;
        }

        pruneDetached();

        if (hasReachedSessionLimit(state.instanceId, state.maxNotifications)) {
            destroy(root);
            return;
        }

        if (document.hidden) {
            state.waitingForVisibility = true;
            return;
        }

        if (activeElement && activeElement !== root) {
            scheduleNext(root, 500);
            return;
        }

        if (state.currentIndex >= state.entries.length) {
            state.currentIndex = 0;
        }

        paint(state, state.entries[state.currentIndex]);

        activeElement = root;
        // Flush the start state before applying the visible class.
        void root.offsetHeight;
        root.classList.add(CLASS.visible);
        root.classList.add(CLASS.legacyVisible);
        if (state.maxNotifications > 0) {
            setSessionValue('shown:' + state.instanceId, String(getSessionCount(state.instanceId) + 1));
        }

        beginHideCycle(root, state);
    }

    function parseEntries(root) {
        var rawEntries = getAttribute(root, 'data-apeiron-social-proof-entries', 'data-entries');
        try {
            var entries = JSON.parse(rawEntries || '[]');
            return Array.isArray(entries) ? entries.filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    }

    function initializeRoot(root) {
        if (states.has(root)) {
            return;
        }

        var entries = parseEntries(root);
        if (entries.length === 0) {
            return;
        }

        var popupImage = root.querySelector(SELECTOR.image);
        var popupName = root.querySelector(SELECTOR.name);
        var popupDescription = root.querySelector(SELECTOR.description);
        var popupDate = root.querySelector(SELECTOR.date);
        var closeButton = root.querySelector(SELECTOR.close);

        if (!popupImage || !popupName || !popupDescription || !popupDate) {
            return;
        }

        var textTemplate =
            getAttribute(root, 'data-apeiron-social-proof-text-template', 'data-text-template') ||
            DEFAULTS.textTemplate;
        var state = {
            entries: entries,
            instanceId:
                getAttribute(root, 'data-apeiron-social-proof-instance-id', 'data-instance-id') ||
                getElementKey(root),
            textTemplate: textTemplate,
            displayDuration: readInt(root, 'data-apeiron-social-proof-display-duration', 'data-display-duration', DEFAULTS.displayDuration, 1000),
            intervalDuration: readInt(root, 'data-apeiron-social-proof-interval-duration', 'data-interval-duration', DEFAULTS.intervalDuration, 1000),
            maxNotifications: readInt(root, 'data-apeiron-social-proof-max-notifications', 'data-max-notifications', DEFAULTS.maxNotifications, 0),
            animationDuration: readInt(root, 'data-apeiron-social-proof-animation-duration', null, DEFAULTS.animationDuration, 100),
            currentIndex: 0,
            hovered: false,
            focusWithin: false,
            waitingForVisibility: false,
            visibilityRemaining: null,
            displayDeadline: 0,
            popupImage: popupImage,
            popupName: popupName,
            popupDescription: popupDescription,
            popupDate: popupDate,
            timers: { next: null, show: null, hide: null },
            listeners: [],
        };

        if (prefersReducedMotion()) {
            state.animationDuration = 1;
        }

        states.set(root, state);
        tracked.push(root);

        // Remove session keys retained from older releases.
        removeSessionValue('dismissed:' + state.instanceId);
        if (state.maxNotifications === 0) {
            removeSessionValue('shown:' + state.instanceId);
        }

        if (closeButton) {
            on(state, closeButton, 'click', function (event) {
                event.preventDefault();
                if (isEditorContext()) {
                    return;
                }
                clearTimers(state);
                hide(root);
                state.currentIndex = (state.currentIndex + 1) % state.entries.length;
                scheduleNext(root, state.intervalDuration);
            });
        }


        on(state, root, 'pointerenter', function () { state.hovered = true; });
        on(state, root, 'pointerleave', function () { state.hovered = false; });
        on(state, root, 'focusin', function () { state.focusWithin = true; });
        on(state, root, 'focusout', function (event) {
            state.focusWithin = !!(event.relatedTarget && root.contains(event.relatedTarget));
        });
        on(state, document, 'visibilitychange', function () {
            if (document.hidden && root.classList.contains(CLASS.visible)) {
                state.visibilityRemaining = Math.max( 100, state.displayDeadline - Date.now() );
                window.clearTimeout(state.timers.show);
                state.timers.show = null;
                return;
            }

            if (!document.hidden && state.visibilityRemaining !== null) {
                var remaining = state.visibilityRemaining;
                state.visibilityRemaining = null;
                beginHideCycle(root, state, remaining);
            } else if (!document.hidden && state.waitingForVisibility) {
                state.waitingForVisibility = false;
                scheduleNext(root, 0);
            }
        });

        if (isEditorContext()) {
            paint(state, entries[0]);
            activeElement = root;
            root.classList.add(CLASS.visible);
            root.classList.add(CLASS.legacyVisible);
            return;
        }

        if (hasReachedSessionLimit(state.instanceId, state.maxNotifications)) {
            destroy(root);
            return;
        }

        var initialDelay = readInt(root, 'data-apeiron-social-proof-initial-delay', 'data-initial-delay', DEFAULTS.initialDelay, 0);
        state.timers.next = window.setTimeout(function () {
            showNextEntry(root);
        }, initialDelay);
    }

    function findRoots(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container) {
            return [];
        }

        var roots = [];
        var collect = function (node) {
            if (!node || !node.querySelectorAll) {
                return;
            }
            Array.prototype.forEach.call(node.querySelectorAll(SELECTOR.root), function (found) {
                if (roots.indexOf(found) === -1) {
                    roots.push(found);
                }
            });
            Array.prototype.forEach.call(node.querySelectorAll(SELECTOR.legacyRoot), function (found) {
                if (roots.indexOf(found) === -1) {
                    roots.push(found);
                }
            });
        };

        collect(container);

        if (container.matches && (container.matches(SELECTOR.root) || container.matches(SELECTOR.legacyRoot))) {
            if (roots.indexOf(container) === -1) {
                roots.unshift(container);
            }
        }

        if (roots.length === 0 && container.closest) {
            collect(container.closest(SELECTOR.widget));
        }

        return roots;
    }

    function initializeScope(scope) {
        pruneDetached();
        findRoots(scope).forEach(initializeRoot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initializeScope(document);
        }, { once: true });
    } else {
        initializeScope(document);
    }

    function updateEditorPreview(widget, change) {
        if (!change.supports(['popup_animation_duration', 'animation_type', 'override_position', 'popup_box_shadow_box_shadow_type'])) { return false; }
        var root = widget.querySelector(SELECTOR.root);
        var state = root && states.get(root);
        if (!root && widget.classList.contains('elementor-widget-empty')) { return true; }
        if (!state) { return false; }
        if (Object.prototype.hasOwnProperty.call(change.values, 'popup_animation_duration')) {
            var seconds = change.number('popup_animation_duration', 0.4, 0.1, 2);
            state.animationDuration = prefersReducedMotion() ? 1 : Math.round(seconds * 1000);
            root.setAttribute('data-apeiron-social-proof-animation-duration', String(Math.round(seconds * 1000)));
            root.style.setProperty('--apeiron-sp-duration', seconds + 's');
        }
        if (Object.prototype.hasOwnProperty.call(change.values, 'animation_type')) {
            Array.from(root.classList).forEach(function(name) { if (name.indexOf('apeiron-sp-animation-') === 0) { root.classList.remove(name); } });
            var animation = change.option('animation_type', 'slide-top');
            root.classList.add('apeiron-sp-animation-' + (animation === 'global' ? (root.getAttribute('data-apeiron-social-proof-global-animation') || 'slide-top') : animation));
        }
        if (Object.prototype.hasOwnProperty.call(change.values, 'popup_box_shadow_box_shadow_type')) {
            root.classList.toggle('apeiron-sp-shadow-disabled', change.get('popup_box_shadow_box_shadow_type') !== 'yes');
        }
        return true;
    }

    function registerEditorControls() {
        var host;
        try {
            host = window.parent;
            if (!host || host === window || !host.elementor || host.__apeironSocialProofControls) return;
        } catch (error) {
            return; // The containing page may be on another origin.
        }

        var editor = host.elementor;
        window.__apeironEditorPreviews = window.__apeironEditorPreviews || {};
        window.__apeironEditorPreviews['apeiron-social-proof'] = updateEditorPreview;
        if (!editor.hooks || !editor.helpers || !editor.helpers.isActiveControl) return;
        host.__apeironSocialProofControls = true;

        function registerPanel() {
            var panel = editor.panel && editor.panel.currentView;
            var page = panel && panel.getPages('editor');
            if (!page || !page.view || !page.view.extend || page.view.__apeironSocialProofControls) return;
            var NativeEditor = page.view;
            function isSocialProofPanel(view) {
                return view.model.get('widgetType') === 'apeiron-social-proof' &&
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
                    if (!isSocialProofPanel(this)) return NativeEditor.prototype.initCollection.apply(this, arguments);
                    // Keep every native control ID; merge defaults only when its view is needed.
                    this.collection = new host.Backbone.Collection(Object.values(this.getOption('controls')));
                    this.__spMergedControls = new Set();
                    this.__spControlsByName = new Map(this.collection.map(function (control) { return [control.get('name'), control]; }));
                },
                getControlModel: function (name) {
                    return this.__spControlsByName ? this.__spControlsByName.get(name) : NativeEditor.prototype.getControlModel.apply(this, arguments);
                },
                getChildView: function (control) {
                    if (isSocialProofPanel(this) && !this.__spMergedControls.has(control.cid)) {
                        control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
                        this.__spMergedControls.add(control.cid);
                    }
                    return NativeEditor.prototype.getChildView.apply(this, arguments);
                },
                buildChildView: function (control) {
                    var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
                    if (isSocialProofPanel(this) && control.get('type') === 'color' && child.initPicker) {
                        deferColorPicker(child);
                    }
                    if (isSocialProofPanel(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
                        var getChildren = child.getResponsiveChildrenViews;
                        child.getResponsiveChildrenViews = function () {
                            return getChildren.apply(this, arguments).filter(function (view) { return !!view; });
                        };
                    }
                    return child;
                },
                _renderChildren: function () {
                    var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
                    if (isSocialProofPanel(this) && this.__spActiveTabs) {
                        var view = this;
                        view.__spActiveTabs.forEach(function (name) {
                            var tab = NativeEditor.prototype.getControlViewByName.call(view, name);
                            if (tab && !tab.$el.hasClass('e-tab-active')) tab.el.click();
                        });
                    }
                    return result;
                },
                filter: function (control, index, collection) {
                    if (!NativeEditor.prototype.filter.call(this, control, index, collection)) return false;
                    if (!isSocialProofPanel(this)) return true;
                    var owner = this.__spPopoverOwners && this.__spPopoverOwners.get(control.cid);
                    if (owner && !this.__spLoadedPopovers.has(owner)) return false;
                    if (!this.__spRequestedControls.has(control.cid)) {
                        var responsive = control.get('responsive');
                        var popover = control.get('popover');
                        var deviceControls = this.__spResponsiveDevices.get(editor.channels.deviceMode.request('currentMode'));
                        // Preserve popover boundaries and the responsive placeholder parent chain.
                        if (responsive && deviceControls && !deviceControls.has(control.cid) &&
                            !(popover && (popover.start || popover.end))) return false;
                        var innerTab = control.get('inner_tab');
                        if (innerTab && this.__spActiveTabs.get(control.get('tabs_wrapper')) !== innerTab) return false;
                    }
                    var settings = this.model.get('settings');
                    return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
                },
                getControlViewByName: function (name) {
                    var control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    if (!isSocialProofPanel(this) || !this.__spPopoverOwners) return control;
                    var model = this.getControlModel(name);
                    if (!model || model.get('section') !== this.activeSection) return control;
                    var owner = this.__spPopoverToggles.has(name) ? name : this.__spPopoverOwners.get(model.cid);
                    if (owner && !this.__spLoadedPopovers.has(owner)) {
                        this.__spLoadedPopovers.add(owner);
                        if (control && name === owner) {
                            // Let the native radio change finish before creating the popover children.
                            this.__spPendingPopover = owner;
                            this.__spScheduleControls();
                        } else {
                            this._renderChildren();
                            control = NativeEditor.prototype.getControlViewByName.call(this, name);
                        }
                    }
                    if (!control) {
                        var requested = model;
                        while (requested) {
                            this.__spRequestedControls.add(requested.cid);
                            requested = requested.get('parent') ? this.getControlModel(requested.get('parent')) : null;
                        }
                        this._renderChildren();
                        control = NativeEditor.prototype.getControlViewByName.call(this, name);
                    }
                    return control;
                },
                activateSection: function (section) {
                    if (isSocialProofPanel(this) && this.activeSection !== section && this.__spLoadedPopovers) {
                        this.__spLoadedPopovers.clear();
                        this.__spPendingPopover = null;
                        this.__spRequestedControls.clear();
                        this.__spActiveTabs = new Map(this.__spDefaultTabs);
                    }
                    return NativeEditor.prototype.activateSection.apply(this, arguments);
                },
                initialize: function () {
                    NativeEditor.prototype.initialize.apply(this, arguments);
                    if (!isSocialProofPanel(this)) return;
                    var view = this;
                    var settings = view.model.get('settings');
                    view.__spPopoverOwners = new Map();
                    view.__spPopoverToggles = new Set();
                    view.__spLoadedPopovers = new Set();
                    view.__spRequestedControls = new Set();
                    view.__spDefaultTabs = new Map();
                    view.__spActiveTabs = new Map();
                    view.__spResponsiveDevices = new Map();
                    var previous = null;
                    var owner = null;
                    view.collection.each(function (control) {
                        var responsive = control.get('responsive');
                        if (responsive) {
                            var device = responsive.max || responsive.min;
                            if (!view.__spResponsiveDevices.has(device)) view.__spResponsiveDevices.set(device, new Set());
                            var parent = control;
                            while (parent) {
                                view.__spResponsiveDevices.get(device).add(parent.cid);
                                parent = view.__spControlsByName.get(parent.get('parent'));
                            }
                        }
                        if (control.get('type') === 'tab' && !view.__spDefaultTabs.has(control.get('tabs_wrapper'))) {
                            view.__spDefaultTabs.set(control.get('tabs_wrapper'), control.get('name'));
                            view.__spActiveTabs.set(control.get('tabs_wrapper'), control.get('name'));
                        }
                        var popover = control.get('popover');
                        if (popover && popover.start) {
                            owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
                            if (owner) view.__spPopoverToggles.add(owner);
                        }
                        if (owner) view.__spPopoverOwners.set(control.cid, owner);
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
                        if (view.isDestroyed || !isSocialProofPanel(view)) return;
                        var pendingPopover = view.__spPendingPopover;
                        view.__spPendingPopover = null;
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
                    view.__spScheduleControls = function () {
                        if (isSocialProofPanel(view) && !refreshFrame) refreshFrame = host.requestAnimationFrame(refreshControls);
                    };
                    view.listenTo(settings, 'change', view.__spScheduleControls);
                    view.listenTo(editor.channels.deviceMode, 'change', view.__spScheduleControls);
                    view.on('childview:control:tab:clicked', function (tab) {
                        var wrapper = tab.model.get('tabs_wrapper');
                        var name = tab.model.get('name');
                        if (view.__spActiveTabs.get(wrapper) === name) return;
                        view.__spActiveTabs.set(wrapper, name);
                        view.__spScheduleControls();
                    });
                    view.on('destroy', function () {
                        if (refreshFrame) host.cancelAnimationFrame(refreshFrame);
                    });
                }
            });
            page.view.__apeironSocialProofControls = true;
        }

        editor.hooks.addAction('panel/open_editor/widget/apeiron-social-proof', registerPanel);
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
            'frontend/element_ready/apeiron-social-proof.default',
            initializeScope
        );
    }

    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();

    if (window.ApeironSocialProofTestMode) {
        window.ApeironSocialProofTestApi = Object.freeze({
            destroy: destroy,
            formatDate: formatDate,
            initializeRoot: initializeRoot,
            removeNamePlaceholder: removeNamePlaceholder,
            pruneDetached: pruneDetached,
        });
    }
}());
