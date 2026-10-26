(function (window, document) {
  'use strict';

  var SELECTOR = '.apeiron-page-loader';
  var lockingInstances = [];
  var instancesById = {};
  var editorPhases = {};
  var TEXT_SEQUENCE_DURATION = 4700;
  var TEXT_EXIT_DURATION = 900;
  var PHASE_PAUSE_DURATION = 400;
  var VISUAL_MIN_DURATION = 3200;
  var FINAL_PROGRESS_DURATION = 1600;
  var COMPLETION_PAUSE_DURATION = 500;

  function clampNumber(value, fallback, minimum, maximum) {
    var parsed = Number(value);
    if (!Number.isFinite(parsed)) {
      parsed = fallback;
    }
    return Math.max(minimum, Math.min(maximum, parsed));
  }

  function isEditorPreview() {
    var params;

    if (document.body && document.body.classList.contains('elementor-editor-active')) {
      return true;
    }

    if (window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode()) {
      return true;
    }

    try {
      params = new window.URLSearchParams(window.location.search);
      return params.has('elementor-preview') || params.get('action') === 'elementor';
    } catch (error) {
      return false;
    }
  }

  function matchesConfiguredDevice(root) {
    var width = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    if (width <= 767) {
      return root.dataset.showMobile !== 'no';
    }
    if (width <= 1024) {
      return root.dataset.showTablet !== 'no';
    }
    return root.dataset.showDesktop !== 'no';
  }

  function isHiddenByLayout(root) {
    var node = root;

    while (node && node !== document.documentElement) {
      if (window.getComputedStyle(node).display === 'none') {
        return true;
      }
      node = node.parentElement;
    }

    return false;
  }

  function storageHasSeen(root) {
    if (root.dataset.firstVisitOnly !== 'yes') {
      return false;
    }

    try {
      return window.localStorage.getItem(root.dataset.storageKey || 'apeiron_page_loader_seen') === '1';
    } catch (error) {
      return false;
    }
  }

  function markSeen(root) {
    if (root.dataset.firstVisitOnly !== 'yes') {
      return;
    }

    try {
      window.localStorage.setItem(root.dataset.storageKey || 'apeiron_page_loader_seen', '1');
    } catch (error) {
      // Storage can be unavailable in privacy-restricted browsing contexts.
    }
  }

  function syncPageLock(instance, shouldLock) {
    var index = lockingInstances.indexOf(instance);

    if (shouldLock && index === -1) {
      lockingInstances.push(instance);
    } else if (!shouldLock && index !== -1) {
      lockingInstances.splice(index, 1);
    }

    document.documentElement.classList.toggle('apeiron-page-loader-scroll-lock', lockingInstances.length > 0);
  }

  function matchingBootState(root) {
    var state = window.ApeironPageLoaderBoot;
    if (!state || (state.elementId && state.elementId !== root.id)) {
      return null;
    }
    return state;
  }

  function frame(callback) {
    return window.requestAnimationFrame ? window.requestAnimationFrame(callback) : window.setTimeout(callback, 16);
  }

  function cancelFrame(id) {
    if (!id) {
      return;
    }
    if (window.cancelAnimationFrame) {
      window.cancelAnimationFrame(id);
    } else {
      window.clearTimeout(id);
    }
  }

  function emit(root, name, detail) {
    if (typeof window.CustomEvent !== 'function') {
      return;
    }
    root.dispatchEvent(new window.CustomEvent(name, { bubbles: true, detail: detail || {} }));
  }

  function PageLoader(root) {
    this.root = root;
    this.editorPreview = root.dataset.editorPreview === 'yes';
    this.overlay = root.querySelector('.apeiron-page-loader__overlay');
    this.percent = root.querySelector('[data-apeiron-page-loader-percent]');
    this.progressBar = root.querySelector('[data-apeiron-page-loader-bar]');
    this.coffeeLiquid = root.querySelector('.apeiron-loader-coffee__liquid');
    this.coffeeSurface = root.querySelector('.apeiron-loader-coffee__surface');
    this.waterLevel = root.querySelector('.apeiron-loader-water__level');
    this.waterDrops = root.querySelector('.apeiron-loader-water__drops');
    this.loaderStyle = root.dataset.loaderStyle || 'default';
    this.minimumDuration = this.editorPreview ? 0 : clampNumber(root.dataset.duration, 7600, 300, 12000);
    this.maximumRuntime = this.editorPreview ? 0 : clampNumber(root.dataset.maxRuntime, 14000, 2000, 20000);
    this.customDelay = this.editorPreview ? 0 : clampNumber(root.dataset.customDelay, 0, 0, 4000);
    this.progressBased = this.editorPreview || root.dataset.progressBased !== 'no';
    this.reducedMotion = this.editorPreview || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    this.boot = this.editorPreview ? null : matchingBootState(root);
    this.startedAt = this.boot && this.boot.active ? this.boot.startedAt : Date.now();
    this.deadline = this.boot && this.boot.active ? this.boot.deadline : this.startedAt + this.maximumRuntime;
    this.progress = 0;
    this.ready = document.readyState === 'complete';
    this.finished = false;
    this.completing = false;
    this.exiting = false;
    this.progressFrame = 0;
    this.completionFrame = 0;
    this.readyTimer = 0;
    this.hardTimer = 0;
    this.exitTimer = 0;
    this.textExitTimer = 0;
    this.textHideTimer = 0;
    this.loadingTimer = 0;
    this.onWindowLoad = this.handleReady.bind(this);
    this.onPageHide = this.handlePageHide.bind(this);
    this.onTransitionEnd = this.handleTransitionEnd.bind(this);
  }

  PageLoader.prototype.init = function () {
    this.root.hidden = false;
    this.root.classList.remove('is-complete', 'is-exiting', 'is-skipped', 'is-editor-preview', 'is-text-exiting', 'is-text-hidden', 'is-loading-visible');

    if (this.boot && this.boot.releaseReason === 'timeout') {
      this.skip('boot-timeout');
      return;
    }

    if (this.editorPreview || isEditorPreview()) {
      this.showEditorPreview();
      return;
    }

    if (!matchesConfiguredDevice(this.root) || isHiddenByLayout(this.root) || storageHasSeen(this.root)) {
      this.skip('ineligible');
      return;
    }

    if (this.reducedMotion) {
      this.minimumDuration = Math.min(this.minimumDuration, 300);
    }

    this.root.classList.add('is-active');
    this.root.classList.add('is-enter-' + (this.root.dataset.entranceAnimation || 'fade_scale'));
    this.root.dataset.apeironPageLoaderState = 'active';
    this.root.setAttribute('aria-hidden', 'false');
    this.root.setAttribute('aria-busy', 'true');
    syncPageLock(this, this.root.dataset.lockScroll === 'yes');
    this.setProgress(0);
    this.startProgress();
    this.revealLoadingPhase();
    this.armDeadline();

    window.addEventListener('pagehide', this.onPageHide, { once: true });
    if (this.ready) {
      this.handleReady();
    } else {
      window.addEventListener('load', this.onWindowLoad, { once: true });
    }

    if (this.boot && this.boot.active) {
      var instance = this;
      frame(function () {
        if (instance.finished || !instance.boot || !instance.boot.active) {
          return;
        }
        if (typeof instance.boot.handoff === 'function') {
          instance.boot.handoff(function () {
            instance.complete('timeout', true);
          });
        } else if (typeof instance.boot.release === 'function') {
          instance.boot.release('handoff');
        }
      });
    }

    emit(this.root, 'apeiron:page-loader:start', { startedAt: this.startedAt });
  };

  PageLoader.prototype.showEditorPreview = function () {
    if (this.boot && this.boot.active && typeof this.boot.release === 'function') {
      this.boot.release('editor-preview');
    }
    syncPageLock(this, false);
    this.root.classList.add('is-active', 'is-editor-preview');
    if (this.loaderStyle !== 'default') {
      this.root.classList.add('is-text-hidden', 'is-loading-visible');
    }
    this.root.dataset.apeironPageLoaderState = 'preview';
    this.root.setAttribute('aria-hidden', 'false');
    this.root.setAttribute('aria-busy', 'false');
    this.setProgress(68);
    if (editorPhases[this.root.id]) {
      setEditorPhase(this.root, editorPhases[this.root.id]);
    }
  };

  function setEditorPhase(root, phase) {
    var loading = phase === 'loading' && root.dataset.loaderStyle !== 'default';
    editorPhases[root.id] = phase;
    root.dataset.editorPhase = loading ? 'loading' : 'text';
    root.classList.toggle('is-text-hidden', loading);
    root.classList.toggle('is-loading-visible', loading);
  }

  PageLoader.prototype.skip = function (reason) {
    if (this.boot && this.boot.active && typeof this.boot.release === 'function') {
      this.boot.release(reason);
    }
    syncPageLock(this, false);
    this.root.classList.add('is-skipped');
    this.root.dataset.apeironPageLoaderState = 'skipped';
    this.root.setAttribute('aria-hidden', 'true');
    this.root.setAttribute('aria-busy', 'false');
    this.root.hidden = true;
  };

  PageLoader.prototype.armDeadline = function () {
    if (this.boot && this.boot.active && typeof this.boot.handoff === 'function') {
      return;
    }

    var remaining = Math.max(0, this.deadline - Date.now());
    var instance = this;
    this.hardTimer = window.setTimeout(function () {
      instance.complete('timeout', true);
    }, remaining);
  };

  PageLoader.prototype.revealLoadingPhase = function () {
    var instance = this;
    var exitDelay = this.reducedMotion ? 0 : Math.max(0, this.startedAt + TEXT_SEQUENCE_DURATION - Date.now());
    var hideDelay = exitDelay + (this.reducedMotion ? 0 : TEXT_EXIT_DURATION);
    var loadingDelay = hideDelay + (this.reducedMotion ? 0 : PHASE_PAUSE_DURATION);

    this.textExitTimer = window.setTimeout(function () {
      instance.root.classList.add('is-text-exiting');
    }, exitDelay);
    this.textHideTimer = window.setTimeout(function () {
      instance.root.classList.add('is-text-hidden');
    }, hideDelay);
    if (this.loaderStyle !== 'default') {
      this.loadingTimer = window.setTimeout(function () {
        instance.root.classList.add('is-loading-visible');
      }, loadingDelay);
    }
  };

  PageLoader.prototype.startProgress = function () {
    var instance = this;

    function tick() {
      if (instance.finished || instance.completing || instance.exiting) {
        return;
      }

      var elapsed = Date.now() - instance.startedAt;
      var stateCeiling = instance.ready ? 96 : 90;
      var progressDuration = instance.loaderStyle === 'default'
        ? TEXT_SEQUENCE_DURATION + TEXT_EXIT_DURATION
        : TEXT_SEQUENCE_DURATION + TEXT_EXIT_DURATION + PHASE_PAUSE_DURATION + VISUAL_MIN_DURATION;
      var ratio = Math.min(1, elapsed / Math.max(progressDuration, instance.minimumDuration, 300));
      var timeTarget;
      if (ratio <= 0.6) {
        timeTarget = 60 * (1 - Math.pow(1 - ratio / 0.6, 1.6));
      } else if (ratio <= 0.9) {
        timeTarget = 60 + 30 * (1 - Math.pow(1 - (ratio - 0.6) / 0.3, 1.4));
      } else {
        timeTarget = 90 + 6 * Math.pow((ratio - 0.9) / 0.1, 1.5);
      }
      var target = instance.progressBased ? Math.min(stateCeiling, timeTarget) : Math.min(94, timeTarget);
      var increment = Math.max(0.035, (target - instance.progress) * 0.035);

      if (target > instance.progress) {
        instance.setProgress(Math.min(target, instance.progress + increment));
      }
      instance.progressFrame = frame(tick);
    }

    this.progressFrame = frame(tick);
  };

  PageLoader.prototype.setProgress = function (value) {
    this.progress = Math.max(0, Math.min(100, value));
    var rounded = Math.round(this.progress);

    if (this.percent) {
      this.percent.textContent = String(rounded);
    }
    if (this.progressBar) {
      this.progressBar.style.transform = 'scaleX(' + (this.progress / 100).toFixed(4) + ')';
    }
    if (this.coffeeLiquid) {
      var coffeeHeight = 34 * this.progress / 100;
      var coffeeY = 130 - coffeeHeight;
      this.coffeeLiquid.setAttribute('y', coffeeY.toFixed(2));
      this.coffeeLiquid.setAttribute('height', Math.max(coffeeHeight, 0.01).toFixed(2));
      this.coffeeSurface.setAttribute('cy', coffeeY.toFixed(2));
      this.coffeeSurface.setAttribute('rx', (13.5 + 6.5 * this.progress / 100).toFixed(2));
      this.coffeeSurface.setAttribute('opacity', this.progress > 4 ? '.9' : '0');
    }
    if (this.waterLevel) {
      this.waterLevel.setAttribute('transform', 'translate(0,' + (120.5 - 41 * this.progress / 100).toFixed(2) + ')');
      this.waterDrops.setAttribute('opacity', this.progress >= 98.5 ? '0' : '1');
    }
  };

  PageLoader.prototype.handleReady = function () {
    if (this.finished || this.readyTimer) {
      return;
    }

    this.ready = true;
    var sequenceDuration = TEXT_SEQUENCE_DURATION + TEXT_EXIT_DURATION;
    if (this.loaderStyle !== 'default') {
      sequenceDuration += PHASE_PAUSE_DURATION + VISUAL_MIN_DURATION - FINAL_PROGRESS_DURATION;
    }
    var sequenceFinish = this.startedAt + (this.reducedMotion ? 0 : sequenceDuration);
    var configuredFinish = this.startedAt + this.minimumDuration;
    var earliestFinish = Math.max(configuredFinish, sequenceFinish, Date.now() + this.customDelay);
    var wait = Math.max(0, Math.min(earliestFinish, this.deadline) - Date.now());
    var instance = this;
    this.readyTimer = window.setTimeout(function () {
      instance.complete('ready', false);
    }, wait);
  };

  PageLoader.prototype.complete = function (reason, immediate) {
    if (this.finished || this.exiting) {
      return;
    }
    if (this.completing) {
      if (!immediate) {
        return;
      }
      cancelFrame(this.completionFrame);
      this.completing = false;
      this.setProgress(100);
      this.beginExit(reason, true);
      return;
    }

    this.completing = true;
    cancelFrame(this.progressFrame);
    this.progressFrame = 0;
    window.clearTimeout(this.readyTimer);
    window.clearTimeout(this.textExitTimer);
    window.clearTimeout(this.textHideTimer);
    window.clearTimeout(this.loadingTimer);
    window.removeEventListener('load', this.onWindowLoad);
    this.root.dataset.apeironPageLoaderState = reason === 'timeout' ? 'timed-out' : 'complete';

    var instance = this;
    if (immediate || this.reducedMotion || this.loaderStyle === 'default') {
      this.setProgress(100);
      this.completing = false;
      if (this.loaderStyle === 'default' && !immediate && !this.reducedMotion) {
        this.beginExit(reason, false);
        return;
      }
      this.exitTimer = window.setTimeout(function () {
        instance.beginExit(reason, immediate);
      }, immediate || this.reducedMotion ? 0 : COMPLETION_PAUSE_DURATION);
      return;
    }

    var progressStart = this.progress;
    var animationStart = Date.now();
    function finishProgress() {
      var elapsed = Date.now() - animationStart;
      var ratio = Math.min(1, elapsed / FINAL_PROGRESS_DURATION);
      var eased = 1 - Math.pow(1 - ratio, 2.2);
      instance.setProgress(progressStart + (100 - progressStart) * eased);
      if (ratio < 1) {
        instance.completionFrame = frame(finishProgress);
        return;
      }
      instance.setProgress(100);
      instance.completing = false;
      instance.exitTimer = window.setTimeout(function () {
        instance.beginExit(reason, false);
      }, COMPLETION_PAUSE_DURATION);
    }
    this.completionFrame = frame(finishProgress);
  };

  PageLoader.prototype.beginExit = function (reason, immediate) {
    if (this.finished) {
      return;
    }

    this.root.classList.add('is-exiting', 'is-exit-' + (this.root.dataset.exitAnimation || 'fade_scale'));
    window.clearTimeout(this.hardTimer);
    this.root.setAttribute('aria-busy', 'false');
    if (immediate || this.reducedMotion || this.root.dataset.exitAnimation === 'none') {
      this.finish(reason);
      return;
    }
    if (this.overlay) {
      this.overlay.addEventListener('transitionend', this.onTransitionEnd, { once: true });
    }

    var instance = this;
    var fallback = immediate || this.reducedMotion ? 80 : 2400;
    this.exitTimer = window.setTimeout(function () {
      instance.finish(reason);
    }, fallback);
  };

  PageLoader.prototype.handleTransitionEnd = function (event) {
    if (event.target === this.overlay) {
      this.finish(this.root.dataset.apeironPageLoaderState === 'timed-out' ? 'timeout' : 'ready');
    }
  };

  PageLoader.prototype.finish = function (reason) {
    if (this.finished) {
      return;
    }

    this.finished = true;
    this.completing = false;
    this.exiting = false;
    window.clearTimeout(this.exitTimer);
    window.clearTimeout(this.textExitTimer);
    window.clearTimeout(this.textHideTimer);
    window.clearTimeout(this.loadingTimer);
    cancelFrame(this.completionFrame);
    if (this.overlay) {
      this.overlay.removeEventListener('transitionend', this.onTransitionEnd);
    }
    this.root.classList.remove('is-active', 'is-exiting');
    this.root.classList.add('is-complete');
    this.root.dataset.apeironPageLoaderState = reason === 'timeout' ? 'timed-out' : 'complete';
    this.root.setAttribute('aria-hidden', 'true');
    this.root.hidden = true;
    syncPageLock(this, false);
    if (this.boot && this.boot.active && typeof this.boot.release === 'function') {
      this.boot.release(reason);
    }
    markSeen(this.root);
    emit(this.root, 'apeiron:page-loader:complete', { reason: reason });
  };

  PageLoader.prototype.handlePageHide = function () {
    syncPageLock(this, false);
    if (this.boot && this.boot.active && typeof this.boot.release === 'function') {
      this.boot.release('pagehide');
    }
  };

  PageLoader.prototype.destroy = function () {
    cancelFrame(this.progressFrame);
    cancelFrame(this.completionFrame);
    window.clearTimeout(this.readyTimer);
    window.clearTimeout(this.hardTimer);
    window.clearTimeout(this.exitTimer);
    window.clearTimeout(this.textExitTimer);
    window.clearTimeout(this.textHideTimer);
    window.clearTimeout(this.loadingTimer);
    window.removeEventListener('load', this.onWindowLoad);
    window.removeEventListener('pagehide', this.onPageHide);
    if (this.overlay) {
      this.overlay.removeEventListener('transitionend', this.onTransitionEnd);
    }
    syncPageLock(this, false);
    this.root.classList.remove('is-text-exiting', 'is-text-hidden', 'is-loading-visible');
    if (this.root.id && instancesById[this.root.id] === this) {
      delete instancesById[this.root.id];
    }
    delete this.root.__apeironPageLoaderInstance;
  };

  function initialize(root) {
    if (!root || root.nodeType !== 1) {
      return;
    }
    if (root.__apeironPageLoaderInstance) {
      return;
    }

    if (root.id && instancesById[root.id] && instancesById[root.id].root !== root) {
      instancesById[root.id].destroy();
    }

    var instance = new PageLoader(root);
    root.__apeironPageLoaderInstance = instance;
    if (root.id) {
      instancesById[root.id] = instance;
    }
    instance.init();
  }

  function initializeWithin(scope) {
    var roots = [];

    if (scope && scope.matches && scope.matches(SELECTOR)) {
      roots.push(scope);
    }
    if (scope && scope.querySelectorAll) {
      roots = roots.concat(Array.prototype.slice.call(scope.querySelectorAll(SELECTOR)));
    }
    roots.forEach(initialize);
  }

  function ready() {
    initializeWithin(document);
    registerEditorControls();
  }

  function updateEditorPreview(widget, change) {
    var numbers = { loader_duration: ['duration', 7600, 300, 12000], maximum_timeout: ['maxRuntime', 14000, 2000, 20000], custom_delay: ['customDelay', 0, 0, 4000] };
    var flags = { progress_based: 'progressBased', first_visit_only: 'firstVisitOnly', show_desktop: 'showDesktop', show_tablet: 'showTablet', show_mobile: 'showMobile' };
    var choices = { entrance_animation: 'entranceAnimation', exit_animation: 'exitAnimation' };
    var allowed = Object.keys(numbers).concat(Object.keys(flags), Object.keys(choices));
    if (!change.supports(allowed)) { return false; }
    var root = widget.querySelector(SELECTOR);
    if (!root || !root.classList.contains('is-editor-preview')) { return false; }
    Object.keys(change.values).forEach(function(name) {
      if (numbers[name]) { var rule = numbers[name]; root.dataset[rule[0]] = String(Math.round(change.number(name, rule[1], rule[2], rule[3]))); }
      else if (flags[name]) { root.dataset[flags[name]] = change.get(name) === 'yes' ? 'yes' : 'no'; }
      else if (choices[name]) { root.dataset[choices[name]] = change.option(name, 'fade_scale'); }
    });
    return true;
  }

  function registerEditorControls() {
    var host;
    try {
      host = window.parent;
      if (!host || host === window || !host.elementor || host.__apeironPageLoaderControls) {
        return;
      }
    } catch (error) {
      return;
    }

    var editor = host.elementor;
    window.__apeironEditorPreviews = window.__apeironEditorPreviews || {};
    window.__apeironEditorPreviews['apeiron-page-loader'] = updateEditorPreview;
    if (!editor.helpers || !editor.helpers.isActiveControl) {
      return;
    }

    function registerPanel() {
      var panel = editor.panel && editor.panel.currentView;
      var page = panel && panel.getPages('editor');
      if (!page || !page.view || !page.view.extend || page.view.__apeironPageLoaderControls) {
        return;
      }
      var NativeEditor = page.view;
      function isLoader(view) {
        return view.model.get('widgetType') === 'apeiron-page-loader';
      }
      function hideTooltips(view) {
        view.$el.find('[original-title]').each(function () {
          var tooltip = host.jQuery(this).data('tipsy');
          if (tooltip) { tooltip.hide(); }
        });
      }
      function updateEditorText(view, settings, values) {
        var editedView = view.getOption('editedElementView');
        var root = editedView && editedView.el.querySelector(SELECTOR);
        if (!root || !root.classList.contains('is-editor-preview')) { return; }
        var changed = values || settings.changedAttributes() || {};
        var dynamic = settings.get('__dynamic__') || {};
        ['intro', 'main', 'loading', 'secondary'].forEach(function (name) {
          var key = name + '_text';
          if (!Object.prototype.hasOwnProperty.call(changed, key) || (dynamic.get ? dynamic.get(key) : dynamic[key])) { return; }
          var value = values ? values[key] : settings.get(key);
          value = typeof value === 'string' || typeof value === 'number' ? String(value).trim() : '';
          var className = 'apeiron-page-loader__' + (name === 'loading' ? 'text' : name);
          var element = root.querySelector('.' + className);
          if (!element && value) {
            var parent = root.querySelector(name === 'intro' ? '.apeiron-page-loader__sequence' : name === 'loading' ? '.apeiron-page-loader__progress' : '.apeiron-page-loader__final');
            if (!parent) { return; }
            if (name === 'main') {
              var mask = root.querySelector('.apeiron-page-loader__mask');
              if (!mask) {
                mask = root.ownerDocument.createElement('div');
                mask.className = 'apeiron-page-loader__mask';
                parent.insertBefore(mask, parent.firstChild);
              }
              parent = mask;
            }
            element = root.ownerDocument.createElement('div');
            element.className = className;
            parent.insertBefore(element, name === 'secondary' ? null : parent.firstChild);
          }
          if (element) {
            element.textContent = value;
            element.hidden = value === '';
          }
          if (name === 'loading' && value) { root.querySelector('.apeiron-page-loader__sr-status').textContent = value; }
        });
      }
      function closePendingPopover(view) {
        var name = view.__loaderPendingPopover;
        if (!name) { return; }
        view.__loaderPendingPopover = null;
        var toggle = NativeEditor.prototype.getControlViewByName.call(view, name);
        if (toggle) { toggle.onPopoverHide(); }
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
          if (!isLoader(this)) { return NativeEditor.prototype.initCollection.apply(this, arguments); }
          this.collection = new host.Backbone.Collection(Object.values(this.getOption('controls')));
          this.__loaderMergedControls = new Set();
          this.__loaderControlsByName = new Map(this.collection.map(function (control) { return [control.get('name'), control]; }));
        },
        getControlModel: function (name) {
          return this.__loaderControlsByName ? this.__loaderControlsByName.get(name) : NativeEditor.prototype.getControlModel.apply(this, arguments);
        },
        getChildView: function (control) {
          if (isLoader(this) && !this.__loaderMergedControls.has(control.cid)) {
            control.set(editor.mergeControlsSettings({ control: control.attributes }).control, { silent: true });
            this.__loaderMergedControls.add(control.cid);
          }
          return NativeEditor.prototype.getChildView.apply(this, arguments);
        },
        buildChildView: function (control) {
          var child = NativeEditor.prototype.buildChildView.apply(this, arguments);
          if (isLoader(this) && control.get('type') === 'color' && child.initPicker) { deferColorPicker(child); }
          if (isLoader(this) && control.get('responsive') && child.getResponsiveChildrenViews) {
            var getChildren = child.getResponsiveChildrenViews;
            child.getResponsiveChildrenViews = function () {
              return getChildren.apply(this, arguments).filter(function (view) { return !!view; });
            };
          }
          return child;
        },
        _renderChildren: function () {
          if (isLoader(this)) { hideTooltips(this); }
          var result = NativeEditor.prototype._renderChildren.apply(this, arguments);
          if (isLoader(this) && this.__loaderActiveTabs) {
            var view = this;
            view.__loaderActiveTabs.forEach(function (name) {
              var tab = NativeEditor.prototype.getControlViewByName.call(view, name);
              if (tab && !tab.$el.hasClass('e-tab-active')) { tab.el.click(); }
            });
          }
          return result;
        },
        filter: function (control, index, collection) {
          if (!NativeEditor.prototype.filter.call(this, control, index, collection)) { return false; }
          if (!isLoader(this)) { return true; }
          var owner = this.__loaderPopoverOwners && this.__loaderPopoverOwners.get(control.cid);
          if (owner && !this.__loaderLoadedPopovers.has(owner)) { return false; }
          if (this.__loaderRequestedControls && !this.__loaderRequestedControls.has(control.cid)) {
            var responsive = control.get('responsive');
            var popover = control.get('popover');
            var deviceControls = this.__loaderResponsiveDevices.get(editor.channels.deviceMode.request('currentMode'));
            if (responsive && deviceControls && !deviceControls.has(control.cid) && !(popover && (popover.start || popover.end))) { return false; }
            var innerTab = control.get('inner_tab');
            if (innerTab && this.__loaderActiveTabs.get(control.get('tabs_wrapper')) !== innerTab) { return false; }
          }
          var settings = this.model.get('settings');
          return editor.helpers.isActiveControl(control, settings.attributes, settings.controls);
        },
        getControlViewByName: function (name) {
          var control = NativeEditor.prototype.getControlViewByName.call(this, name);
          if (!isLoader(this) || !this.__loaderPopoverOwners) { return control; }
          var model = this.getControlModel(name);
          if (!model || model.get('section') !== this.activeSection) { return control; }
          var owner = this.__loaderPopoverToggles.has(name) ? name : this.__loaderPopoverOwners.get(model.cid);
          if (owner && !this.__loaderLoadedPopovers.has(owner)) {
            this.__loaderLoadedPopovers.add(owner);
            if (control && name === owner) {
              this.__loaderPendingPopover = owner;
              this.__loaderScheduleControls();
            } else {
              this._renderChildren();
              control = NativeEditor.prototype.getControlViewByName.call(this, name);
            }
          }
          if (!control) {
            var requested = model;
            while (requested) {
              this.__loaderRequestedControls.add(requested.cid);
              requested = requested.get('parent') ? this.getControlModel(requested.get('parent')) : null;
            }
            this._renderChildren();
            control = NativeEditor.prototype.getControlViewByName.call(this, name);
          }
          return control;
        },
        activateSection: function (section) {
          if (isLoader(this) && this.activeSection !== section && this.__loaderLoadedPopovers) {
            closePendingPopover(this);
            this.__loaderLoadedPopovers.clear();
            this.__loaderRequestedControls.clear();
            this.__loaderActiveTabs = new Map(this.__loaderDefaultTabs);
          }
          return NativeEditor.prototype.activateSection.apply(this, arguments);
        },
        initialize: function () {
          NativeEditor.prototype.initialize.apply(this, arguments);
          if (!isLoader(this)) { return; }
          var view = this;
          var previous = null;
          var owner = null;
          view.__loaderPopoverOwners = new Map();
          view.__loaderPopoverToggles = new Set();
          view.__loaderLoadedPopovers = new Set();
          view.__loaderRequestedControls = new Set();
          view.__loaderDefaultTabs = new Map();
          view.__loaderActiveTabs = new Map();
          view.__loaderResponsiveDevices = new Map();
          view.collection.each(function (control) {
            var responsive = control.get('responsive');
            if (responsive) {
              var device = responsive.max || responsive.min;
              if (!view.__loaderResponsiveDevices.has(device)) { view.__loaderResponsiveDevices.set(device, new Set()); }
              var parent = control;
              while (parent) {
                view.__loaderResponsiveDevices.get(device).add(parent.cid);
                parent = view.__loaderControlsByName.get(parent.get('parent'));
              }
            }
            if (control.get('type') === 'tab' && !view.__loaderDefaultTabs.has(control.get('tabs_wrapper'))) {
              view.__loaderDefaultTabs.set(control.get('tabs_wrapper'), control.get('name'));
              view.__loaderActiveTabs.set(control.get('tabs_wrapper'), control.get('name'));
            }
            var popover = control.get('popover');
            if (popover && popover.start) {
              owner = previous && previous.get('type') === 'popover_toggle' ? previous.get('name') : null;
              if (owner) { view.__loaderPopoverToggles.add(owner); }
            }
            if (owner) { view.__loaderPopoverOwners.set(control.cid, owner); }
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
            if (view.isDestroyed) { return; }
            var pending = view.__loaderPendingPopover;
            view.__loaderPendingPopover = null;
            if (!pending && signature() === visibleControls) { return; }
            var open = view.children.filter(function (child) {
              return child.model.get('type') === 'popover_toggle' && child.$el.next('.elementor-controls-popover').is(':visible');
            }).map(function (child) { return child.model.get('name'); });
            if (pending && open.indexOf(pending) === -1) { open.push(pending); }
            var selector = 'input, select, textarea, button, [tabindex]';
            var focused = view.children.find(function (child) { return child.el.contains(host.document.activeElement); });
            var focusIndex = focused ? Array.from(focused.el.querySelectorAll(selector)).indexOf(host.document.activeElement) : -1;
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
          function onNavigationClick(event) {
            var tab = event.target.closest('.elementor-panel-navigation-tab');
            if (tab && tab.getAttribute('data-tab') !== view.activeTab) { closePendingPopover(view); }
          }
          function previewControl(event) {
            var control = view.children.find(function (child) { return child.el.contains(event.target); });
            if (!control) { return; }
            var name = control.model.get('name');
            var phase = /^(intro|main|secondary)_text/.test(name) || name === 'section_style_text' ? 'text' : null;
            if (/^(loading_text|percentage_|loader_|stroke_width|animation_speed|show_percentage|track_color|surface_spacing)/.test(name) || name === 'section_style_loader') {
              phase = 'loading';
            }
            var editedView = view.getOption('editedElementView');
            var root = editedView && editedView.el.querySelector(SELECTOR);
            if (root && phase) { setEditorPhase(root, phase); }
          }
          function previewTextInput(event) {
            var key = event.target.getAttribute('data-setting');
            if (!/^(intro|main|loading|secondary)_text$/.test(key)) { return; }
            var values = {};
            values[key] = event.target.value;
            updateEditorText(view, view.model.get('settings'), values);
          }
          view.el.addEventListener('click', onNavigationClick, true);
          view.el.addEventListener('click', previewControl, true);
          view.el.addEventListener('focusin', previewControl);
          view.el.addEventListener('input', previewTextInput);
          view.on('render:collection', function () { visibleControls = signature(); });
          view.__loaderScheduleControls = function () {
            if (!refreshFrame) { refreshFrame = host.requestAnimationFrame(refreshControls); }
          };
          view.listenTo(view.model.get('settings'), 'change', view.__loaderScheduleControls);
          view.listenTo(view.model.get('settings'), 'change', function (settings) { updateEditorText(view, settings); });
          view.listenTo(editor.channels.deviceMode, 'change', view.__loaderScheduleControls);
          view.on('childview:control:tab:clicked', function (tab) {
            var wrapper = tab.model.get('tabs_wrapper');
            var name = tab.model.get('name');
            if (view.__loaderActiveTabs.get(wrapper) === name) { return; }
            closePendingPopover(view);
            view.__loaderActiveTabs.set(wrapper, name);
            view.__loaderScheduleControls();
          });
          view.on('before:destroy', function () {
            hideTooltips(view);
            if (refreshFrame) { host.cancelAnimationFrame(refreshFrame); }
            view.el.removeEventListener('click', onNavigationClick, true);
            view.el.removeEventListener('click', previewControl, true);
            view.el.removeEventListener('focusin', previewControl);
            view.el.removeEventListener('input', previewTextInput);
          });
        }
      });
      page.view.__apeironPageLoaderControls = true;
    }

    host.__apeironPageLoaderControls = true;
    editor.on('preview:loaded', registerPanel);
    if (editor.panel && editor.panel.currentView) { registerPanel(); }
    else { editor.once('panel:init', registerPanel); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready, { once: true });
  } else {
    ready();
  }

  if (window.jQuery && !window.__apeironPageLoaderElementorHookRegistered) {
    window.__apeironPageLoaderElementorHookRegistered = true;
    window.jQuery(window).on('elementor/frontend/init', function () {
      if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
        return;
      }
      window.elementorFrontend.hooks.addAction('frontend/element_ready/apeiron-page-loader.default', function ($scope) {
        initializeWithin($scope && $scope[0] ? $scope[0] : $scope);
      });
    });
  }
}(window, document));
