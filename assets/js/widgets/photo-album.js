/**
 * Apeiron Kit - Album Foto
 * Slideshow with fade/slide transitions, thumbnails, next-photo peek, swipe, keyboard and autoplay.
 * The layout per breakpoint comes from the --pa-layout CSS variable (written by Elementor) and is
 * mirrored to data-layout so the CSS can switch between the film strip and card layouts.
 */
(function () {
    'use strict';

    if (window.__apeironPhotoAlbumLoaded) return;
    window.__apeironPhotoAlbumLoaded = true;

    var ROOT = '[data-apeiron-photo-album]';
    var LAYOUTS = ['filmstrip', 'card'];
    var SWIPE_DISTANCE = 40;
    var FILMSTRIP_MIN_WIDTH = 760;
    var FIT_TOLERANCE = 1.7;
    var albums = new Map();
    var motion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
    var resizeFrame = null;
    var hookRegistered = false;

    var observer = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            var state = albums.get(entry.target);
            if (!state) return;
            state.visible = entry.isIntersecting;
            schedule(state);
        });
    }, { threshold: 0.2 }) : null;

    // Column resizes in the editor and breakpoint changes do not always resize the window.
    var resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(function (entries) {
        entries.forEach(function (entry) {
            var state = albums.get(entry.target);
            if (state) syncLayout(state);
        });
    }) : null;

    function pad(number) {
        return number < 10 ? '0' + number : String(number);
    }

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function wrap(state, index) {
        if (state.loop) return (index % state.total + state.total) % state.total;
        return Math.max(0, Math.min(state.total - 1, index));
    }

    function photoRatio(slide) {
        var ratio = parseFloat(slide.getAttribute('data-ratio'));
        if (ratio > 0) return ratio;
        var image = slide.querySelector('.apeiron-photo-album__image');
        return image && image.naturalWidth && image.naturalHeight ? image.naturalWidth / image.naturalHeight : 0;
    }

    // Auto: show the whole photo when its shape is far from the frame (e.g. portrait in a wide frame).
    function syncFit(state) {
        var mode = window.getComputedStyle(state.root).getPropertyValue('--pa-fit').trim() || 'auto';
        var viewport = state.viewport;
        var frame = viewport && viewport.clientHeight ? viewport.clientWidth / viewport.clientHeight : 0;
        state.slides.forEach(function (slide) {
            var contain = mode === 'contain';
            if (mode === 'auto' && frame > 0) {
                var ratio = photoRatio(slide);
                contain = ratio > 0 && Math.max(ratio / frame, frame / ratio) > FIT_TOLERANCE;
            }
            slide.classList.toggle('is-contain', contain);
        });
    }

    function syncLayout(state) {
        var value = window.getComputedStyle(state.root).getPropertyValue('--pa-layout').trim();
        if (LAYOUTS.indexOf(value) === -1) {
            syncFit(state);
            return;
        }
        // Without room for thumbnails, photo and counter side by side, the film strip runs below the photo.
        var compact = value === 'filmstrip' && state.root.clientWidth > 0 && state.root.clientWidth < FILMSTRIP_MIN_WIDTH ? 'yes' : 'no';
        var compactChanged = state.root.getAttribute('data-compact') !== compact;
        if (compactChanged) state.root.setAttribute('data-compact', compact);
        if (state.root.getAttribute('data-layout') !== value || compactChanged) {
            state.root.setAttribute('data-layout', value);
            revealThumb(state, false);
        }
        // The frame shape depends on the layout, so decide the photo fit after it.
        syncFit(state);
    }

    function revealThumb(state, smooth) {
        var box = state.thumbsBox;
        var thumb = state.thumbs[state.index];
        if (!box || !thumb) return;
        var horizontal = state.root.getAttribute('data-layout') === 'card' || state.root.getAttribute('data-compact') === 'yes';
        var options = { behavior: smooth && !motion.matches ? 'smooth' : 'auto' };
        // Scroll only the strip itself so the page never jumps.
        if (horizontal) {
            options.left = thumb.offsetLeft - (box.clientWidth - thumb.offsetWidth) / 2;
        } else {
            options.top = thumb.offsetTop - (box.clientHeight - thumb.offsetHeight) / 2;
        }
        if (typeof box.scrollTo === 'function') {
            box.scrollTo(options);
        } else if (horizontal) {
            box.scrollLeft = options.left;
        } else {
            box.scrollTop = options.top;
        }
    }

    function swapText(state, element, text) {
        if (!element || element.textContent === text) return;
        if (motion.matches || state.speed === 0) {
            element.textContent = text;
            return;
        }
        element.classList.add('is-swapping');
        window.clearTimeout(element.__apeironSwap);
        element.__apeironSwap = window.setTimeout(function () {
            element.textContent = text;
            element.classList.remove('is-swapping');
        }, state.speed * 0.5);
    }

    function swapPeek(state) {
        var image = state.peekImage;
        if (!image) return;
        var nextIndex = state.index + 1;
        var hidden = nextIndex >= state.total && !state.loop;
        state.peek.style.visibility = hidden ? 'hidden' : '';
        if (hidden) return;
        var slide = state.slides[wrap(state, nextIndex)];
        var src = slide.getAttribute('data-src') || '';
        var srcset = slide.getAttribute('data-srcset') || '';
        if (image.getAttribute('src') === src) return;
        var apply = function () {
            if (srcset) image.setAttribute('srcset', srcset); else image.removeAttribute('srcset');
            image.setAttribute('src', src);
            image.classList.remove('is-swapping');
        };
        if (motion.matches || state.speed === 0) {
            apply();
            return;
        }
        image.classList.add('is-swapping');
        window.clearTimeout(image.__apeironSwap);
        image.__apeironSwap = window.setTimeout(apply, state.speed * 0.6);
    }

    function updateMeta(state, smooth) {
        var index = state.index;
        if (state.current) state.current.textContent = pad(index + 1);
        swapText(state, state.caption, state.slides[index].getAttribute('data-caption') || '');
        if (state.progress) state.progress.style.width = ((index + 1) / state.total * 100) + '%';

        state.thumbs.forEach(function (thumb, thumbIndex) {
            var active = thumbIndex === index;
            thumb.classList.toggle('is-active', active);
            if (active) thumb.setAttribute('aria-current', 'true'); else thumb.removeAttribute('aria-current');
        });
        revealThumb(state, smooth);
        swapPeek(state);

        var atStart = !state.loop && index === 0;
        var atEnd = !state.loop && index === state.total - 1;
        state.prevButtons.forEach(function (button) { button.disabled = atStart; });
        state.nextButtons.forEach(function (button) {
            if (button.classList.contains('apeiron-photo-album__button')) button.disabled = atEnd;
        });
    }

    function setActive(slide, active) {
        slide.classList.toggle('is-active', active);
        if (active) slide.removeAttribute('aria-hidden'); else slide.setAttribute('aria-hidden', 'true');
        var link = slide.querySelector('.apeiron-photo-album__link');
        if (link) {
            if (active) link.removeAttribute('tabindex'); else link.setAttribute('tabindex', '-1');
        }
    }

    function goTo(state, target, direction) {
        if (state.total < 2) return;
        var index = wrap(state, target);
        var previous = state.index;
        if (index === previous) return;
        direction = direction || (index > previous ? 1 : -1);
        state.index = index;

        var incoming = state.slides[index];
        var outgoing = state.slides[previous];

        if (state.effect === 'slide' && !motion.matches && state.speed > 0) {
            state.slides.forEach(function (slide) {
                if (slide !== incoming && slide !== outgoing) {
                    slide.classList.remove('is-leaving');
                    slide.style.transition = 'none';
                    slide.style.transform = '';
                }
            });
            incoming.classList.remove('is-leaving');
            incoming.style.transition = 'none';
            incoming.style.transform = 'translateX(' + (direction * 100) + '%)';
            void incoming.offsetWidth;
            incoming.style.transition = '';
            setActive(incoming, true);
            incoming.style.transform = 'translateX(0)';

            setActive(outgoing, false);
            outgoing.classList.add('is-leaving');
            outgoing.style.transition = '';
            outgoing.style.transform = 'translateX(' + (-direction * 100) + '%)';
            window.clearTimeout(outgoing.__apeironLeave);
            outgoing.__apeironLeave = window.setTimeout(function () {
                outgoing.classList.remove('is-leaving');
            }, state.speed + 60);
        } else {
            state.slides.forEach(function (slide) {
                slide.style.transform = '';
                slide.classList.remove('is-leaving');
                setActive(slide, slide === incoming);
            });
        }

        updateMeta(state, true);
        schedule(state);
    }

    function step(state, delta) {
        goTo(state, state.index + delta, delta);
    }

    function schedule(state) {
        window.clearTimeout(state.timer);
        state.timer = null;
        if (!state.root.isConnected) {
            destroy(state);
            return;
        }
        if (!state.autoplay || motion.matches || document.hidden || !state.visible) return;
        if (state.pauseOnHover && (state.hovered || state.focused)) return;
        if (!state.loop && state.index >= state.total - 1) return;
        state.timer = window.setTimeout(function () {
            state.timer = null;
            if (!state.root.isConnected) {
                destroy(state);
                return;
            }
            step(state, 1);
        }, state.duration);
    }

    function destroy(state) {
        window.clearTimeout(state.timer);
        if (observer) observer.unobserve(state.root);
        if (resizeObserver) resizeObserver.unobserve(state.root);
        albums.delete(state.root);
    }

    function bind(state) {
        var root = state.root;

        root.addEventListener('click', function (event) {
            // Elementor's editor can create preview nodes in another window realm, so avoid instanceof.
            var target = event.target && typeof event.target.closest === 'function' ? event.target : null;
            if (!target) return;
            var thumb = target.closest('[data-apeiron-photo-album-thumb]');
            if (thumb && root.contains(thumb)) {
                var index = parseInt(thumb.getAttribute('data-apeiron-photo-album-thumb'), 10) || 0;
                goTo(state, index, index > state.index ? 1 : -1);
                return;
            }
            if (target.closest('[data-apeiron-photo-album-next]')) {
                step(state, 1);
                return;
            }
            if (target.closest('[data-apeiron-photo-album-prev]')) {
                step(state, -1);
            }
        });

        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                step(state, 1);
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                step(state, -1);
            }
        });

        root.addEventListener('pointerenter', function (event) {
            if (event.pointerType !== 'mouse') return;
            state.hovered = true;
            schedule(state);
        });
        root.addEventListener('pointerleave', function () {
            state.hovered = false;
            schedule(state);
        });
        root.addEventListener('focusin', function () {
            state.focused = true;
            schedule(state);
        });
        root.addEventListener('focusout', function (event) {
            if (event.relatedTarget && root.contains(event.relatedTarget)) return;
            state.focused = false;
            schedule(state);
        });

        // Swipe on the photo. A swipe must not also open the lightbox.
        var viewport = state.viewport;
        if (!viewport) return;
        var start = null;
        viewport.addEventListener('pointerdown', function (event) {
            if (event.button !== undefined && event.button !== 0) return;
            start = { x: event.clientX, y: event.clientY, id: event.pointerId };
            state.swiped = false;
        });
        viewport.addEventListener('pointerup', function (event) {
            if (!start || start.id !== event.pointerId) return;
            var dx = event.clientX - start.x;
            var dy = event.clientY - start.y;
            start = null;
            if (Math.abs(dx) >= SWIPE_DISTANCE && Math.abs(dx) > Math.abs(dy) * 1.2) {
                state.swiped = true;
                if (state.pauseOnHover) {
                    state.hovered = false;
                }
                step(state, dx < 0 ? 1 : -1);
            }
        });
        viewport.addEventListener('pointercancel', function () {
            start = null;
        });
        viewport.addEventListener('click', function (event) {
            if (!state.swiped) return;
            state.swiped = false;
            event.preventDefault();
            event.stopPropagation();
        }, true);
        viewport.addEventListener('dragstart', function (event) {
            event.preventDefault();
        });
    }

    function init(root) {
        if (!root || root.nodeType !== 1 || albums.has(root)) return;
        var slides = toArray(root.querySelectorAll('.apeiron-photo-album__slide'));
        if (!slides.length) return;

        var speed = parseInt(root.getAttribute('data-speed'), 10);
        var state = {
            root: root,
            slides: slides,
            total: slides.length,
            index: 0,
            effect: root.getAttribute('data-effect') === 'slide' ? 'slide' : 'fade',
            autoplay: root.getAttribute('data-autoplay') === 'yes',
            duration: Math.max(1500, parseInt(root.getAttribute('data-duration'), 10) || 5000),
            speed: isNaN(speed) ? 700 : Math.max(0, speed),
            loop: root.getAttribute('data-loop') !== 'no',
            pauseOnHover: root.getAttribute('data-pause-on-hover') !== 'no',
            viewport: root.querySelector('.apeiron-photo-album__viewport'),
            peek: root.querySelector('.apeiron-photo-album__peek'),
            peekImage: root.querySelector('.apeiron-photo-album__peek-image'),
            current: root.querySelector('[data-apeiron-photo-album-current]'),
            caption: root.querySelector('[data-apeiron-photo-album-caption]'),
            progress: root.querySelector('[data-apeiron-photo-album-progress]'),
            thumbsBox: root.querySelector('[data-apeiron-photo-album-thumbs]'),
            thumbs: toArray(root.querySelectorAll('[data-apeiron-photo-album-thumb]')),
            nextButtons: toArray(root.querySelectorAll('[data-apeiron-photo-album-next]')),
            prevButtons: toArray(root.querySelectorAll('[data-apeiron-photo-album-prev]')),
            visible: !observer,
            hovered: false,
            focused: false,
            swiped: false,
            timer: null
        };

        albums.set(root, state);
        syncLayout(state);
        slides.forEach(function (slide) {
            if (slide.getAttribute('data-ratio')) return;
            var image = slide.querySelector('.apeiron-photo-album__image');
            if (image && !image.complete) image.addEventListener('load', function () { syncFit(state); }, { once: true });
        });
        bind(state);
        updateMeta(state, false);
        if (observer) observer.observe(root);
        if (resizeObserver) resizeObserver.observe(root);
        if (root.getAttribute('data-editor') === 'yes') watchEditorStyles();
        schedule(state);
    }

    function initScope(scope) {
        var element = scope && scope.jquery ? scope[0] : scope;
        if (!element) return;
        if (element.matches && element.matches(ROOT)) {
            init(element);
            return;
        }
        if (element.querySelectorAll) toArray(element.querySelectorAll(ROOT)).forEach(init);
    }

    function onResize() {
        if (resizeFrame !== null) return;
        resizeFrame = window.requestAnimationFrame(function () {
            resizeFrame = null;
            albums.forEach(function (state) {
                if (!state.root.isConnected) {
                    destroy(state);
                    return;
                }
                syncLayout(state);
            });
        });
    }

    function refreshAll() {
        albums.forEach(schedule);
    }

    function syncAllLayouts() {
        albums.forEach(function (state) {
            if (state.root.isConnected) syncLayout(state);
        });
    }

    // In the Elementor editor a Layout change only rewrites the widget CSS. Watch the style
    // elements so the new layout shows on the next frame instead of waiting for a re-render.
    var styleWatchFrame = null;
    function watchEditorStyles() {
        if (!('MutationObserver' in window) || !document.documentElement) return;
        if (!document.querySelector('[data-apeiron-photo-album][data-editor="yes"]')) return;
        if (window.__apeironPhotoAlbumStyleWatch) return;
        window.__apeironPhotoAlbumStyleWatch = true;
        new MutationObserver(function (mutations) {
            var styleChanged = mutations.some(function (mutation) {
                var node = mutation.target;
                if (node.nodeType === 3) node = node.parentNode;
                if (node && (node.nodeName === 'STYLE' || node.nodeName === 'LINK')) return true;
                return Array.prototype.some.call(mutation.addedNodes, function (added) {
                    return added.nodeName === 'STYLE' || added.nodeName === 'LINK';
                });
            });
            if (!styleChanged || styleWatchFrame !== null) return;
            styleWatchFrame = window.requestAnimationFrame(function () {
                styleWatchFrame = null;
                syncAllLayouts();
            });
        }).observe(document.documentElement, { childList: true, subtree: true, characterData: true });
    }

    function registerElementorHook() {
        if (hookRegistered || !window.elementorFrontend || !window.elementorFrontend.hooks) return;
        hookRegistered = true;
        window.elementorFrontend.hooks.addAction('frontend/element_ready/apeiron-photo-album.default', initScope);
    }

    window.addEventListener('resize', onResize);
    document.addEventListener('visibilitychange', refreshAll);
    if (motion.addEventListener) motion.addEventListener('change', refreshAll);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initScope(document); }, { once: true });
    } else {
        initScope(document);
    }
    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();
}());
