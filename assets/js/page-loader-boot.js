(function (window, document) {
  'use strict';

  var config = window.ApeironPageLoaderBootConfig || {};
  var root = document.documentElement;
  var storageKey = String(config.storageKey || 'apeiron_page_loader_seen');
  var maximumTimeout = Math.max(2000, Math.min(20000, Number(config.maximumTimeout) || 8000));
  var hardTimer = 0;

  function storageHasSeen() {
    if (!config.firstVisitOnly) {
      return false;
    }

    try {
      return window.sessionStorage.getItem(storageKey) === '1';
    } catch (error) {
      return false;
    }
  }

  function matchesDevice() {
    var width = Math.max(root.clientWidth || 0, window.innerWidth || 0);
    if (width <= 767) {
      return config.showMobile !== false;
    }
    if (width <= 1024) {
      return config.showTablet !== false;
    }
    return config.showDesktop !== false;
  }

  function isEditorRequest() {
    var params;

    try {
      params = new window.URLSearchParams(window.location.search);
      return params.has('elementor-preview') || params.get('action') === 'elementor';
    } catch (error) {
      return false;
    }
  }

  function clearBootStyles() {
    root.style.removeProperty('--ap-page-loader-boot-bg');
    root.style.removeProperty('--ap-page-loader-boot-primary');
    root.style.removeProperty('--ap-page-loader-boot-track');
    root.style.removeProperty('--ap-page-loader-boot-opacity');
  }

  function removeBootShell(keepLock) {
    root.classList.remove('apeiron-page-loader-booting');
    if (!keepLock) {
      root.classList.remove('apeiron-page-loader-scroll-lock');
    }
    root.removeAttribute('data-apeiron-page-loader-boot');
    clearBootStyles();
  }

  function release(reason) {
    var state = window.ApeironPageLoaderBoot;

    if (hardTimer) {
      window.clearTimeout(hardTimer);
      hardTimer = 0;
    }

    removeBootShell(false);

    if (state) {
      state.active = false;
      state.releaseReason = reason || 'released';
    }
  }

  function handoff(onTimeout) {
    var state = window.ApeironPageLoaderBoot;

    if (!state || !state.active) {
      return;
    }

    state.onTimeout = typeof onTimeout === 'function' ? onTimeout : null;
    state.handedOff = true;
    removeBootShell(state.lockScroll);
  }

  if (!config.bootEnabled || storageHasSeen() || !matchesDevice() || isEditorRequest()) {
    return;
  }

  var startedAt = Date.now();
  var state = {
    active: true,
    startedAt: startedAt,
    deadline: startedAt + maximumTimeout,
    elementId: String(config.elementId || ''),
    lockScroll: config.lockScroll === true,
    release: release,
    handoff: handoff,
    onTimeout: null,
    handedOff: false
  };

  window.ApeironPageLoaderBoot = state;
  root.classList.add('apeiron-page-loader-booting');
  if (state.lockScroll) {
    root.classList.add('apeiron-page-loader-scroll-lock');
  }
  root.setAttribute('data-apeiron-page-loader-boot', String(config.id || 'active'));
  root.style.setProperty('--ap-page-loader-boot-bg', String(config.background || '#f7f7fb'));
  root.style.setProperty('--ap-page-loader-boot-primary', String(config.primary || '#083c57'));
  root.style.setProperty('--ap-page-loader-boot-track', String(config.track || '#e5e7ef'));
  root.style.setProperty('--ap-page-loader-boot-opacity', String(config.opacity == null ? 0.98 : config.opacity));

  hardTimer = window.setTimeout(function () {
    if (state.active && typeof state.onTimeout === 'function') {
      state.onTimeout();
      return;
    }
    release('timeout');
  }, maximumTimeout);

  document.addEventListener('DOMContentLoaded', function () {
    var target = state.elementId ? document.getElementById(state.elementId) : null;

    if (!target || window.getComputedStyle(target).display === 'none') {
      release(target ? 'hidden' : 'missing');
    }
  }, { once: true });
}(window, document));
