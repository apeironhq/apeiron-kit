(function($){
  function initDashboardNav() {
    var shell = document.querySelector('.apeiron-dashboard-shell');
    var header = document.querySelector('.apeiron-dashboard-header-area');
    var content = document.querySelector('.apeiron-tab-content');
    var links = Array.prototype.slice.call(document.querySelectorAll('[data-apeiron-dashboard-tab-link]'));
    var navItems = Array.prototype.slice.call(document.querySelectorAll('.apeiron-dashboard-nav__item'));
    var requestId = 0;
    var pendingRequest = null;
    var tabRequestTimeout = 15000;
    var tabCache = Object.create(null);
    var tabPrefetches = Object.create(null);

    if (!shell || !content || !links.length) {
      return;
    }

    function getUrl(href) {
      try {
        return new URL(href, window.location.href);
      } catch (err) {
        return null;
      }
    }

    function isDashboardUrl(url) {
      return url
        && url.origin === window.location.origin
        && url.pathname === window.location.pathname
        && url.searchParams.get('page') === 'apeiron-kit';
    }

    function isSameView(url) {
      var current = getUrl(window.location.href);
      return current
        && url
        && url.pathname === current.pathname
        && url.search === current.search
        && url.hash === current.hash;
    }

    function getTabFromUrl(url) {
      if (!url) {
        return 'widgets';
      }

      return url.searchParams.get('tab') || 'widgets';
    }

    function findNavItem(url) {
      var target = url ? url.href.replace(/#.*$/, '') : '';
      for (var i = 0; i < navItems.length; i++) {
        if (navItems[i].href.replace(/#.*$/, '') === target) {
          return navItems[i];
        }
      }
      return null;
    }

    function findNavItemByTab(tab) {
      for (var i = 0; i < navItems.length; i++) {
        if (getTabFromUrl(getUrl(navItems[i].href)) === tab) {
          return navItems[i];
        }
      }
      return null;
    }

    function getCurrentNavItem() {
      var current = getUrl(window.location.href);
      return findNavItem(current)
        || document.querySelector('.apeiron-dashboard-nav__item.is-active')
        || navItems[0]
        || null;
    }

    function isCacheableTab(tab) {
      return tab === 'widgets' || tab === 'license';
    }

    function isValidDashboardResponse(response) {
      return !!(
        response
        && response.success
        && response.data
        && typeof response.data.content === 'string'
      );
    }

    function cacheInitialTab() {
      var current = getUrl(window.location.href);
      var tab = getTabFromUrl(current);
      var templates = Array.prototype.slice.call(
        document.querySelectorAll('template[data-apeiron-dashboard-prefetch-tab]')
      );

      if (isCacheableTab(tab)) {
        tabCache[tab] = { content: content.innerHTML };
      }

      templates.forEach(function(template) {
        var templateTab = template.getAttribute('data-apeiron-dashboard-prefetch-tab') || '';

        if (isCacheableTab(templateTab)) {
          tabCache[templateTab] = { content: template.innerHTML };
        }

        template.parentNode && template.parentNode.removeChild(template);
      });
    }

    function cacheDashboardResponse(response, fallbackTab) {
      var tab;

      if (!isValidDashboardResponse(response)) {
        return;
      }

      tab = response.data.tab || fallbackTab;
      if (isCacheableTab(tab)) {
        tabCache[tab] = { content: response.data.content };
      }
    }

    function createTabRequestBody(tab, refreshHeader) {
      var body = new FormData();

      body.append('action', 'apeiron_load_dashboard_tab');
      body.append('nonce', shell.getAttribute('data-apeiron-dashboard-tab-nonce') || '');
      body.append('tab', tab);
      if (refreshHeader) {
        body.append('refresh_header', '1');
      }

      return body;
    }

    function clearPrefetch(tab, entry) {
      clearRequestTimer(entry && entry.request);
      if (tabPrefetches[tab] === entry) {
        delete tabPrefetches[tab];
      }
    }

    function abortPrefetch(tab) {
      var entry = tabPrefetches[tab];

      if (!entry) {
        return;
      }

      clearPrefetch(tab, entry);
      entry.cancelled = true;
      if (entry.request.controller) {
        entry.request.controller.abort();
      }
    }

    function invalidateTab(tab) {
      delete tabCache[tab];
      abortPrefetch(tab);
    }

    function prefetchTab(link) {
      var url = getUrl(link && link.href);
      var tab = getTabFromUrl(url);
      var request;
      var entry;

      if (
        !window.fetch
        || !window.FormData
        || !isDashboardUrl(url)
        || !isCacheableTab(tab)
        || pendingRequest
        || tabCache[tab]
        || tabPrefetches[tab]
      ) {
        return;
      }

      request = {
        id: 0,
        controller: null,
        timeoutId: null,
        timedOut: false
      };
      entry = { request: request, promise: null, cancelled: false };
      tabPrefetches[tab] = entry;

      try {
        entry.promise = fetchDashboardTab(createTabRequestBody(tab, false), request)
          .then(function(response) {
            if (entry.cancelled || !isValidDashboardResponse(response)) {
              return null;
            }

            cacheDashboardResponse(response, tab);
            return response;
          })
          .then(function(response) {
            clearPrefetch(tab, entry);
            return response;
          }, function() {
            clearPrefetch(tab, entry);
            return null;
          });
      } catch (error) {
        clearPrefetch(tab, entry);
      }
    }

    function abortAllPrefetches() {
      Object.keys(tabPrefetches).forEach(abortPrefetch);
    }

    function scheduleLicensePrefetch() {
      var licenseLink = findNavItemByTab('license');
      var connection = window.navigator && (
        window.navigator.connection
        || window.navigator.mozConnection
        || window.navigator.webkitConnection
      );
      var run = function() {
        prefetchTab(licenseLink);
      };

      if (!licenseLink || tabCache.license || (connection && connection.saveData)) {
        return;
      }

      if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(run, { timeout: 2500 });
      } else {
        window.setTimeout(run, 800);
      }
    }

    function getAjaxAction(data) {
      var match;

      if (data && typeof data === 'object' && typeof data.action === 'string') {
        return data.action;
      }

      if (typeof data !== 'string') {
        return '';
      }

      match = data.match(/(?:^|&)action=([^&]+)/);
      return match ? decodeURIComponent(match[1].replace(/\+/g, ' ')) : '';
    }

    function watchCacheInvalidation() {
      $(document)
        .off('ajaxSuccess.apeironDashboardCache')
        .on('ajaxSuccess.apeironDashboardCache', function(event, xhr, settings) {
          var action = getAjaxAction(settings && settings.data);

          if (action === 'apeiron_toggle_widget' || action === 'apeiron_bulk_toggle_widgets') {
            invalidateTab('widgets');
          }

          if (
            action === 'apeiron_activate_license'
            || action === 'apeiron_deactivate_license'
            || action === 'apeiron_check_license'
            || action === 'apeiron_save_server_config'
          ) {
            invalidateTab('license');
          }
        });
    }

    function isCurrentNavItem(link) {
      var active = document.querySelector('.apeiron-dashboard-nav__item.is-active');
      var current = getUrl(window.location.href);
      var target = getUrl(link.href);

      if (active && active !== link) {
        return false;
      }

      return current
        && target
        && isDashboardUrl(current)
        && isDashboardUrl(target)
        && getTabFromUrl(current) === getTabFromUrl(target);
    }

    function setPendingState(link) {
      var url = getUrl(link.href);
      var navItem = link.classList.contains('apeiron-dashboard-nav__item') ? link : findNavItem(url);

      shell.classList.add('is-tab-loading');
      content.setAttribute('aria-busy', 'true');

      navItems.forEach(function(item) {
        item.classList.remove('is-active', 'is-loading');
        item.removeAttribute('aria-current');
      });

      if (navItem) {
        navItem.classList.add('is-active', 'is-loading');
        navItem.setAttribute('aria-current', 'page');
      }
    }

    function clearPendingState() {
      shell.classList.remove('is-tab-loading');
      content.removeAttribute('aria-busy');
      navItems.forEach(function(item) {
        item.classList.remove('is-loading');
      });
    }

    function clearRequestTimer(request) {
      if (request && request.timeoutId) {
        window.clearTimeout(request.timeoutId);
        request.timeoutId = null;
      }
    }

    function isCurrentRequest(request) {
      return pendingRequest === request && request.id === requestId;
    }

    function finishPendingRequest(request) {
      clearRequestTimer(request);

      if (!isCurrentRequest(request)) {
        return;
      }

      pendingRequest = null;
      clearPendingState();
    }

    function abortPendingRequest() {
      var request = pendingRequest;

      if (!request) {
        clearPendingState();
        return;
      }

      ++requestId;
      pendingRequest = null;
      clearPendingState();

      clearRequestTimer(request);
      if (request.controller) {
        request.controller.abort();
      }
    }

    function fetchDashboardTab(body, request) {
      var requestOptions = {
        method: 'POST',
        credentials: 'same-origin',
        body: body
      };
      var fetchPromise;
      var timeoutPromise;

      if (typeof window.AbortController === 'function') {
        request.controller = new window.AbortController();
        requestOptions.signal = request.controller.signal;
      }

      fetchPromise = window.fetch(window.ajaxurl || 'admin-ajax.php', requestOptions)
        .then(function(response) {
          if (!response.ok) {
            throw new Error('Dashboard tab request failed');
          }

          return response.json();
        });

      if (request.controller) {
        request.timeoutId = window.setTimeout(function() {
          request.timedOut = true;
          request.controller.abort();
        }, tabRequestTimeout);

        return fetchPromise;
      }

      timeoutPromise = new Promise(function(resolve, reject) {
        request.timeoutId = window.setTimeout(function() {
          request.timedOut = true;
          reject(new Error('Dashboard tab request timed out'));
        }, tabRequestTimeout);
      });

      return Promise.race([fetchPromise, timeoutPromise]);
    }

    function initPanelScrollChain() {
      var panelSelector = [
        '.apeiron-widget-toggle-section > .apeiron-elements-content',
        '.apeiron-cover-settings-section > .apeiron-elements-content',
        '.apeiron-sp-section > .apeiron-elements-content',
        '.apeiron-sticker-section > .apeiron-elements-content',
        '.apeiron-license-section > .apeiron-elements-content',
        '.apeiron-ut-content'
      ].join(',');
      var sectionSelector = [
        '.apeiron-widget-toggle-section',
        '.apeiron-cover-settings-section',
        '.apeiron-sp-section',
        '.apeiron-sticker-section',
        '.apeiron-license-section',
        '.apeiron-ut-section'
      ].join(',');
      var pageScrollElement = document.scrollingElement || document.documentElement;

      function normalizeWheelDelta(event) {
        var delta = event.deltaY || 0;

        if (event.deltaMode === 1) {
          return delta * 16;
        }

        if (event.deltaMode === 2) {
          return delta * (window.innerHeight || 800);
        }

        return delta;
      }

      function getDirectPanel(section) {
        var children;
        var i;

        if (!section || !section.children) {
          return null;
        }

        children = Array.prototype.slice.call(section.children);
        for (i = 0; i < children.length; i++) {
          if (children[i].matches('.apeiron-elements-content, .apeiron-ut-content')) {
            return children[i];
          }
        }

        return null;
      }

      function canNativeNestedScroll(target, panel, deltaY) {
        var node = target;
        var style;
        var maxTop;
        var overflowY;

        while (node && node !== panel && node !== content) {
          if (node.nodeType === 1) {
            style = window.getComputedStyle(node);
            overflowY = style ? style.overflowY : '';
            maxTop = Math.max(0, node.scrollHeight - node.clientHeight);

            if (maxTop > 1 && (overflowY === 'auto' || overflowY === 'scroll')) {
              if ((deltaY > 0 && node.scrollTop < maxTop - 1) || (deltaY < 0 && node.scrollTop > 1)) {
                return true;
              }
            }
          }

          node = node.parentNode;
        }

        return false;
      }

      function findScrollPanel(target) {
        var panel;
        var section;

        if (!target.closest) {
          return null;
        }

        panel = target.closest(panelSelector);
        if (panel && content.contains(panel)) {
          return panel;
        }

        section = target.closest(sectionSelector);
        if (!section || !content.contains(section)) {
          return null;
        }

        return getDirectPanel(section);
      }

      function scrollPageBy(delta) {
        var dampedDelta;
        var maxStep = 28;

        if (Math.abs(delta) < 0.5) {
          return;
        }

        dampedDelta = delta * 0.24;
        if (Math.abs(dampedDelta) < 1) {
          dampedDelta = Math.sign(delta);
        }
        if (Math.abs(dampedDelta) > maxStep) {
          dampedDelta = maxStep * Math.sign(dampedDelta);
        }

        window.scrollBy({
          top: dampedDelta,
          left: 0,
          behavior: 'smooth'
        });
      }

      content.addEventListener('wheel', function(event) {
        var panel;
        var deltaY;
        var maxScrollTop;
        var currentScrollTop;
        var availablePanelDelta;
        var edgeThreshold = 18;
        var pageDelta = 0;

        if (event.defaultPrevented || event.ctrlKey || !event.target.closest) {
          return;
        }

        panel = findScrollPanel(event.target);
        if (!panel) {
          return;
        }

        deltaY = normalizeWheelDelta(event);
        if (Math.abs(deltaY) < 1) {
          return;
        }

        maxScrollTop = Math.max(0, panel.scrollHeight - panel.clientHeight);
        if (maxScrollTop <= 1) {
          return;
        }

        if (canNativeNestedScroll(event.target, panel, deltaY)) {
          return;
        }

        currentScrollTop = panel.scrollTop;

        if (deltaY > 0) {
          availablePanelDelta = Math.max(0, maxScrollTop - currentScrollTop);

          if (availablePanelDelta > edgeThreshold) {
            return;
          }

          if (availablePanelDelta >= deltaY - 0.5) {
            return;
          }

          panel.scrollTop = maxScrollTop;
          pageDelta = deltaY - availablePanelDelta;
        } else if (deltaY < 0) {
          availablePanelDelta = Math.max(0, currentScrollTop);

          if (availablePanelDelta > edgeThreshold) {
            return;
          }

          if (availablePanelDelta >= Math.abs(deltaY) - 0.5) {
            return;
          }

          panel.scrollTop = 0;
          pageDelta = deltaY + availablePanelDelta;
        } else {
          return;
        }

        if (Math.abs(pageDelta) < 0.5) {
          return;
        }

        event.preventDefault();
        scrollPageBy(pageDelta);
      }, { passive: false });
    }

    function replaceContent(html, tab, url, headerHtml) {
      $(document).trigger('apeiron:dashboard-tab-unload');
      if (header && typeof headerHtml === 'string') {
        header.innerHTML = headerHtml || '';
      }
      content.innerHTML = html || '';
	  $(document).trigger('apeiron:dashboard-tab-loaded', [tab, content]);

      if (url && window.history && window.history.pushState) {
        window.history.pushState({ apeironTab: tab }, '', url.href);
      }

      content.focus && content.focus({ preventScroll: true });
    }

    function loadTab(link, pushState, options) {
      options = options || {};
      var url = getUrl(link.href);
      var tab = getTabFromUrl(url);
      var request;
      var prefetch;
      var tabPromise;

      function fallback() {
        if (options.fallbackReload) {
          window.location.reload();
          return;
        }

        window.location.assign(link.href);
      }

      abortPendingRequest();

      if (!isDashboardUrl(url)) {
        fallback();
        return;
      }

      if (options.forceReload) {
        invalidateTab(tab);
      } else if (tabCache[tab]) {
        setPendingState(link);
        replaceContent(tabCache[tab].content, tab, pushState ? url : null, null);
        clearPendingState();
        return;
      }

      if (!window.fetch || !window.FormData) {
        fallback();
        return;
      }

      request = {
        id: ++requestId,
        controller: null,
        timeoutId: null,
        timedOut: false
      };
      pendingRequest = request;

      try {
        setPendingState(link);
        prefetch = !options.refreshHeader ? tabPrefetches[tab] : null;

        if (prefetch) {
          tabPromise = prefetch.promise.then(function(response) {
            return response || fetchDashboardTab(createTabRequestBody(tab, false), request);
          });
        } else {
          tabPromise = fetchDashboardTab(createTabRequestBody(tab, !!options.refreshHeader), request);
        }
      } catch (error) {
        finishPendingRequest(request);
        fallback();
        return;
      }

      tabPromise
        .then(function(response) {
          if (!isCurrentRequest(request)) {
            return;
          }

          if (!isValidDashboardResponse(response)) {
            throw new Error('Invalid dashboard tab response');
          }

          cacheDashboardResponse(response, tab);
          replaceContent(response.data.content, response.data.tab || tab, pushState ? url : null, response.data.header);
        })
        .catch(function() {
          if (!isCurrentRequest(request)) {
            return;
          }

          finishPendingRequest(request);
          fallback();
        })
        .then(function() {
          finishPendingRequest(request);
        }, function() {
          finishPendingRequest(request);
        });
    }

    function refreshCurrentTab(options) {
      var link = getCurrentNavItem();
      var delay = options && options.delay ? parseInt(options.delay, 10) : 0;

      if (!link) {
        window.location.reload();
        return false;
      }

      window.setTimeout(function() {
        if (!isCurrentNavItem(link)) {
          return;
        }

        loadTab(link, false, {
          fallbackReload: true,
          forceReload: true,
          refreshHeader: !!(options && options.refreshHeader)
        });
      }, Math.max(0, delay || 0));

      return true;
    }

    window.ApeironDashboard = window.ApeironDashboard || {};
    window.ApeironDashboard.refreshCurrentTab = refreshCurrentTab;
    window.ApeironDashboard.invalidateTab = invalidateTab;
    window.ApeironDashboard.refreshTab = function(tab, options) {
      var link = findNavItemByTab(tab);
      var delay = options && options.delay ? parseInt(options.delay, 10) : 0;

      if (!link) {
        window.location.reload();
        return false;
      }

      window.setTimeout(function() {
        if (!isCurrentNavItem(link)) {
          return;
        }

        loadTab(link, false, {
          fallbackReload: true,
          forceReload: true,
          refreshHeader: !!(options && options.refreshHeader)
        });
      }, Math.max(0, delay || 0));

      return true;
    };

    cacheInitialTab();
    watchCacheInvalidation();
    navItems.forEach(function(item) {
      item.addEventListener('mouseenter', function() {
        prefetchTab(item);
      });
      item.addEventListener('focus', function() {
        prefetchTab(item);
      });
    });
    scheduleLicensePrefetch();

    document.addEventListener('click', function(event) {
      var link = event.target.closest('[data-apeiron-dashboard-tab-link]');
      var url;

      if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      if (link.target && link.target !== '_self') {
        return;
      }

      url = getUrl(link.href);
      if (!isDashboardUrl(url)) {
        return;
      }

      if (isSameView(url) && !pendingRequest) {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      loadTab(link, true);
    }, true);

    window.addEventListener('popstate', function() {
      var url = getUrl(window.location.href);
      var navItem = findNavItem(url);
      if (!navItem || !isDashboardUrl(url)) {
        return;
      }

      loadTab(navItem, false);
    });

    window.addEventListener('beforeunload', abortPendingRequest);
    window.addEventListener('beforeunload', abortAllPrefetches);

    initPanelScrollChain();
  }

  $(function(){
    $(document).on('click', '[data-apeiron-license-alert-close]', function(e){
      e.preventDefault();
      var $alert = $(this).closest('[data-apeiron-license-alert]');
      var $shell = $alert.closest('.apeiron-license-alert-shell');
      ($shell.length ? $shell : $alert).slideUp(160);
    });
    initDashboardNav();
  });
})(jQuery);
