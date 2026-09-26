(function (window, document) {
  'use strict';

  var SELECTOR = '.apeiron-page-loader';
  var lockingInstances = [];
  var instancesById = {};
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
      return window.sessionStorage.getItem(root.dataset.storageKey || 'apeiron_page_loader_seen') === '1';
    } catch (error) {
      return false;
    }
  }

  function markSeen(root) {
    if (root.dataset.firstVisitOnly !== 'yes') {
      return;
    }

    try {
      window.sessionStorage.setItem(root.dataset.storageKey || 'apeiron_page_loader_seen', '1');
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
  };

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
    var configuredFinish = this.loaderStyle === 'default' ? sequenceFinish : this.startedAt + this.minimumDuration;
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
