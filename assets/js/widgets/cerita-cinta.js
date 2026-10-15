(function () {
    'use strict';

    if (window.__apeironCeritaCintaLoaded) return;
    window.__apeironCeritaCintaLoaded = true;

    var ROOT = '[data-apeiron-cerita-cinta]';
    var states = new Map();
    var owners = new WeakMap();
    var visible = new Set();
    var frame = null;
    var slideshowTimer = null;
    var intersectionObserver = null;
    var resizeObserver = null;
    var detachObserver = null;
    var listening = false;
    var hookRegistered = false;
    var editorSkinWaiting = false;
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function schedule() {
        if (frame === null && states.size) {
            frame = window.requestAnimationFrame(update);
        }
    }

    function invalidate() {
        states.forEach(function (state) { state.dirty = true; });
        schedule();
        refreshSlideshowTimer();
    }

    function refreshSlideshowTimer() {
        var now = Date.now();
        var next = Infinity;
        states.forEach(function (state, root) {
            state.galleries.forEach(function (gallery) {
                if (motion.matches || document.hidden || !visible.has(root) || !root.isConnected || !gallery.autoplay || gallery.finished) {
                    gallery.nextAt = null;
                    return;
                }
                if (gallery.nextAt === null) gallery.nextAt = now + gallery.duration;
                next = Math.min(next, gallery.nextAt);
            });
        });
        if (slideshowTimer !== null) window.clearTimeout(slideshowTimer);
        slideshowTimer = null;
        if (next !== Infinity) {
            slideshowTimer = window.setTimeout(function () {
                slideshowTimer = null;
                var current = Date.now();
                states.forEach(function (state, root) {
                    if (!visible.has(root) || !root.isConnected || motion.matches || document.hidden) return;
                    state.galleries.forEach(function (gallery) {
                        if (gallery.nextAt !== null && gallery.nextAt <= current + 5) {
                            changeSlide(gallery, 1);
                            gallery.nextAt = current + gallery.duration;
                        }
                    });
                });
                refreshSlideshowTimer();
            }, Math.max(0, next - now));
        }
    }

    function changeSlide(gallery, direction) {
        var next = gallery.index + direction;
        if (gallery.loop) next = (next + gallery.slides.length) % gallery.slides.length;
        else next = Math.max(0, Math.min(gallery.slides.length - 1, next));
        gallery.slides[gallery.index].classList.remove('is-current');
        gallery.slides[gallery.index].setAttribute('aria-hidden', 'true');
        gallery.index = next;
        gallery.slides[next].classList.add('is-current');
        gallery.slides[next].setAttribute('aria-hidden', 'false');
        gallery.finished = !gallery.loop && next === gallery.slides.length - 1;
        gallery.buttons.forEach(function (button) {
            button.disabled = !gallery.loop && (button.dataset.ccSlide === 'previous' ? next === 0 : next === gallery.slides.length - 1);
        });
    }

    function syncSkin(state) {
        var style = window.getComputedStyle(state.root);
        var skin = style.getPropertyValue('--cc-skin').trim();
        if (!skin) skin = style.getPropertyValue('--cc-story-layout').trim() || state.root.getAttribute('data-apeiron-cerita-cinta-skin');
        state.skin = ['scrapbook-collage', 'scrapbook_collage', 'collage'].indexOf(skin) !== -1 ? 'scrapbook-collage' : 'scrapbook-story';
        state.collage = state.skin === 'scrapbook-collage';
        var side = style.getPropertyValue('--cc-collage-side').trim();
        state.root.classList.toggle('apeiron-cerita-cinta--skin-scrapbook-story', !state.collage);
        state.root.classList.toggle('apeiron-cerita-cinta--skin-scrapbook-collage', state.collage);
        state.root.setAttribute('data-apeiron-cerita-cinta-skin', state.skin);
        // Keep the legacy hook available for saved custom CSS and integrations.
        state.root.classList.toggle('is-collage', state.collage);
        state.root.classList.toggle('collage-photo-right', state.collage && side === 'right');
        state.root.classList.toggle('collage-photo-alternate', state.collage && side === 'alternate');
    }

    function destroy(root) {
        var state = states.get(root);
        if (!state) return;

        if (intersectionObserver) intersectionObserver.unobserve(root);
        if (resizeObserver) {
            state.observed.forEach(function (element) {
                resizeObserver.unobserve(element);
                owners.delete(element);
            });
        }
        states.delete(root);
        visible.delete(root);
        state.galleries.forEach(function (gallery) {
            gallery.buttons.forEach(function (button) { button.removeEventListener('click', gallery.onClick); });
            gallery.slides.forEach(function (slide, index) {
                slide.classList.toggle('is-current', index === 0);
                slide.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');
            });
        });
        root.classList.remove('is-enhanced', 'is-journey-started');
        refreshSlideshowTimer();

        if (!states.size && listening) {
            window.removeEventListener('scroll', schedule, true);
            window.removeEventListener('resize', invalidate);
            motion.removeEventListener('change', invalidate);
            document.removeEventListener('visibilitychange', refreshSlideshowTimer);
            if (intersectionObserver) intersectionObserver.disconnect();
            if (resizeObserver) resizeObserver.disconnect();
            if (detachObserver) detachObserver.disconnect();
            intersectionObserver = resizeObserver = detachObserver = null;
            listening = false;
            if (frame !== null) window.cancelAnimationFrame(frame);
            frame = null;
        }
    }

    function start() {
        if (listening) return;
        listening = true;

        // One passive listener and one scheduled frame serve all instances.
        window.addEventListener('scroll', schedule, { passive: true, capture: true });
        window.addEventListener('resize', invalidate, { passive: true });
        motion.addEventListener('change', invalidate);
        document.addEventListener('visibilitychange', refreshSlideshowTimer);

        if (window.IntersectionObserver) {
            intersectionObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.target.isConnected) {
                        destroy(entry.target);
                    } else if (entry.isIntersecting) {
                        visible.add(entry.target);
                    } else {
                        visible.delete(entry.target);
                    }
                });
                schedule();
                refreshSlideshowTimer();
            }, { rootMargin: '150px 0px' });
        }

        if (window.ResizeObserver) {
            resizeObserver = new ResizeObserver(function (entries) {
                var changed = new Set();
                entries.forEach(function (entry) {
                    var state = owners.get(entry.target);
                    if (state) changed.add(state);
                });
                changed.forEach(function (state) { syncSkin(state); state.dirty = true; });
                schedule();
            });
        }

        // Observe removals only; our class/SVG updates never trigger this observer.
        if (window.MutationObserver) {
            detachObserver = new MutationObserver(function (records) {
                if (!records.some(function (record) { return record.removedNodes.length; })) return;
                states.forEach(function (state, root) {
                    if (!root.isConnected) destroy(root);
                });
            });
            detachObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    function measure(state) {
        var rect = state.track.getBoundingClientRect();
        var points = state.dots.map(function (dot) {
            var box = dot.getBoundingClientRect();
            return { x: box.left - rect.left + box.width / 2, y: box.top - rect.top + box.height / 2 };
        });
        var path = points.length ? 'M ' + points[0].x + ' ' + points[0].y : '';
        var bend = points.length && points[0].x < rect.width / 3 ? 8 : 24;
        var segments = [];
        var scale = rect.width / (state.track.offsetWidth || rect.width || 1);
        var rows = state.collage ? state.items.map(function (item) {
            var box = item.getBoundingClientRect();
            return {
                left: box.left - rect.left, right: box.right - rect.left, top: box.top - rect.top, bottom: box.bottom - rect.top, width: box.width,
            };
        }) : [];

        if (!state.collage && points.length) {
            var style = window.getComputedStyle(state.items[0]);
            var columns = style.gridTemplateColumns.split(' ');
            var rail = columns.length === 2
                ? parseFloat(columns[0]) / 2 + (parseFloat(style.columnGap) || 0)
                : parseFloat(columns[1]) / 2;
            if (!Number.isFinite(rail)) rail = 0;
            // All Timeline skins share the original S curve; only their stroke/marker differs.
            var narrow = columns.length === 2;
            var space = Math.max(0, rail * scale - 4 * scale);
            bend = Math.min((narrow ? 8 : 24) * scale, space / (Math.sqrt(3) / 6));
        }

        function appendSegment(start, end, control1, control2) {
            var gap = (end.y - start.y) / 3;
            segments.push({ start: start, end: end, control1: control1, control2: control2 });
            path += ' C ' + control1 + ' ' + (start.y + gap)
                + ' ' + control2 + ' ' + (end.y - gap)
                + ' ' + end.x + ' ' + end.y;
        }

        function appendCollageCurve(start, end, control1, control2) {
            segments.push({ start: start, end: end, control1: control1.x, control2: control2.x, control1Y: control1.y, control2Y: control2.y });
            path += ' C ' + control1.x + ' ' + control1.y
                + ' ' + control2.x + ' ' + control2.y
                + ' ' + end.x + ' ' + end.y;
        }

        function appendCollageTurn(start, end) {
            var direction = end.x > start.x ? 1 : -1;
            var radius = Math.min(18 * scale, (end.y - start.y) / 3, Math.abs(end.x - start.x) / 3);
            if (radius < 1) {
                appendSegment(start, end, start.x, end.x);
                return;
            }
            var handle = radius * 0.55228475;
            var first = { x: start.x + direction * radius, y: start.y + radius };
            var last = { x: end.x - direction * radius, y: end.y - radius };
            var across = (last.x - first.x) / 3;
            // Rounded corners join a gently sloping middle; Y stays monotonic for the moving marker.
            appendCollageCurve(start, first, { x: start.x, y: start.y + handle }, { x: first.x - direction * handle, y: first.y });
            appendCollageCurve(first, last, { x: first.x + across, y: first.y }, { x: last.x - across, y: last.y });
            appendCollageCurve(last, end, { x: last.x + direction * handle, y: last.y }, { x: end.x, y: end.y - handle });
        }

        for (var i = 1; i < points.length; i++) {
            var previous = points[i - 1];
            var current = points[i];
            if (state.collage) {
                var row = rows[i];
                var turnStart = { x: previous.x, y: Math.max(previous.y + 1, Math.min(current.y - 2, rows[i - 1].bottom)) };
                var turnEnd = { x: current.x, y: Math.max(turnStart.y + 1, Math.min(current.y - 1, row.top + 16 * scale)) };
                appendSegment(previous, turnStart, previous.x, previous.x);
                appendCollageTurn(turnStart, turnEnd);
                appendSegment(turnEnd, current, current.x, current.x);
                continue;
            }
            var offset = (i % 2 ? 1 : -1) * bend;
            var control1 = previous.x + offset;
            var control2 = current.x - offset;
            appendSegment(previous, current, control1, control2);
        }

        return { rect: rect, points: points, path: path, segments: segments, rows: rows };
    }

    function pointOnPath(layout, progress) {
        var first = layout.points[0];
        var last = layout.points[layout.points.length - 1];
        if (progress <= first.y) return first;
        if (progress >= last.y) return last;

        for (var i = 0; i < layout.segments.length; i++) {
            var segment = layout.segments[i];
            if (progress > segment.end.y) continue;
            // Y control points are evenly spaced, so t follows scroll directly.
            var t = (progress - segment.start.y) / (segment.end.y - segment.start.y || 1);
            if (segment.control1Y !== undefined) {
                // The Collage turn has vertical tangents; solve its monotonic Y before sampling X.
                var low = 0;
                var high = 1;
                for (var step = 0; step < 14; step++) {
                    t = (low + high) / 2;
                    var rest = 1 - t;
                    var y = rest * rest * rest * segment.start.y
                        + 3 * rest * rest * t * segment.control1Y
                        + 3 * rest * t * t * segment.control2Y
                        + t * t * t * segment.end.y;
                    if (y < progress) low = t; else high = t;
                }
                t = (low + high) / 2;
            }
            var inverse = 1 - t;
            return {
                x: inverse * inverse * inverse * segment.start.x
                    + 3 * inverse * inverse * t * segment.control1
                    + 3 * inverse * t * t * segment.control2
                    + t * t * t * segment.end.x,
                y: progress,
            };
        }
        return last;
    }

    function update() {
        frame = null;
        var height = window.innerHeight || document.documentElement.clientHeight;
        var updates = [];

        // Batch layout reads before SVG/class writes. Geometry is cached between resizes.
        states.forEach(function (state, root) {
            if (!root.isConnected) {
                destroy(root);
                return;
            }
            if (!state.dirty && !visible.has(root)) return;

            var layout = state.dirty ? measure(state) : state.layout;
            var rect = state.dirty ? layout.rect : state.track.getBoundingClientRect();
            updates.push({ state: state, layout: layout, top: rect.top, dirty: state.dirty });
        });

        updates.forEach(function (entry) {
            var state = entry.state;
            if (entry.dirty) {
                state.layout = entry.layout;
                state.dirty = false;
                state.svg.setAttribute('viewBox', '0 0 ' + Math.max(1, entry.layout.rect.width) + ' ' + Math.max(1, entry.layout.rect.height));
                state.base.setAttribute('d', entry.layout.path);
                state.active.setAttribute('d', entry.layout.path);
                if (state.stitch) state.stitch.setAttribute('d', entry.layout.path);
            }

            var progress = Math.max(0, Math.min(state.layout.rect.height, height * 0.65 - entry.top));
            if (entry.dirty || progress !== state.progress) {
                state.clip.setAttribute('height', String(progress));
                if (state.marker && !motion.matches) {
                    var position = pointOnPath(state.layout, progress);
                    state.marker.style.setProperty('--cc-marker-x', position.x / Math.max(1, state.layout.rect.width) * 100 + '%');
                    state.marker.style.setProperty('--cc-marker-y', position.y / Math.max(1, state.layout.rect.height) * 100 + '%');
                }
                state.progress = progress;
            }
            if (state.marker) state.root.classList.toggle('is-journey-started', !motion.matches && progress >= state.layout.points[0].y);

            state.items.forEach(function (item, index) {
                var point = state.layout.points[index];
                item.classList.toggle('is-active', point.y <= progress);
                var revealPoint = state.collage ? state.layout.rows[index].top : point.y;
                if (motion.matches || revealPoint <= height * 0.9 - entry.top) item.classList.add('is-revealed');
            });
            state.root.classList.add('is-enhanced');
        });
    }

    function initializeRoot(root) {
        var track = root.querySelector('.apeiron-cerita-cinta__track');
        var items = Array.from(root.querySelectorAll('.apeiron-cerita-cinta__item'));
        var existing = states.get(root);

        if (existing && existing.track === track && existing.items.length === items.length
            && existing.items.every(function (item, index) { return item === items[index]; })) {
            existing.dirty = true;
            syncSkin(existing);
            schedule();
            return;
        }
        destroy(root);
        if (!track || !items.length) return;

        var state = {
            root: root,
            track: track,
            items: items,
            dots: items.map(function (item) { return item.querySelector('.apeiron-cerita-cinta__dot'); }),
            svg: track.querySelector('.apeiron-cerita-cinta__timeline'),
            base: track.querySelector('.apeiron-cerita-cinta__line-base'),
            active: track.querySelector('.apeiron-cerita-cinta__line-active'),
            stitch: track.querySelector('.apeiron-cerita-cinta__ribbon-stitch'),
            marker: track.querySelector('.apeiron-cerita-cinta__journey-marker'),
            skinMeasure: track.querySelector('.apeiron-cerita-cinta__skin-measure, .apeiron-cerita-cinta__layout-measure'),
            compositions: items.map(function (item) { return item.querySelector('.apeiron-cerita-cinta__composition'); }),
            galleries: Array.from(root.querySelectorAll('[data-apeiron-cerita-cinta-slideshow]')).map(function (element) {
                var slides = Array.from(element.querySelectorAll('.apeiron-cerita-cinta__slide'));
                var speed = Math.max(0, Math.min(3000, Number(element.dataset.speed) || 0));
                var gallery = {
                    slides: slides, index: Math.max(0, slides.findIndex(function (slide) { return slide.classList.contains('is-current'); })),
                    duration: Math.max(speed + 100, Math.max(1000, Math.min(60000, Number(element.dataset.duration) || 4000))),
                    autoplay: element.dataset.autoplay !== 'no', loop: element.dataset.loop !== 'no', nextAt: null, finished: false,
                    buttons: Array.from(element.parentElement.querySelectorAll('[data-cc-slide]')),
                };
                gallery.onClick = function (event) {
                    changeSlide(gallery, event.currentTarget.dataset.ccSlide === 'previous' ? -1 : 1);
                    gallery.nextAt = Date.now() + gallery.duration;
                    refreshSlideshowTimer();
                };
                changeSlide(gallery, 0);
                gallery.buttons.forEach(function (button) { button.addEventListener('click', gallery.onClick); });
                return gallery;
            }).filter(function (gallery) { return gallery.slides.length > 1; }),
            collage: false,
            timeline: root.getAttribute('data-apeiron-cerita-cinta-timeline') || 'doodle-path',
            clip: track.querySelector('.apeiron-cerita-cinta__progress'),
            progress: null,
            layout: null,
            dirty: true,
            observed: [],
        };

        states.set(root, state);
        syncSkin(state);
        visible.add(root);
        start();
        if (intersectionObserver) intersectionObserver.observe(root);
        if (resizeObserver) {
            state.observed = [root, track].concat(state.dots);
            if (state.marker) state.observed.push(state.marker);
            if (state.skinMeasure) state.observed.push(state.skinMeasure);
            items.forEach(function (item) {
                var positionMeasure = item.querySelector('.apeiron-cerita-cinta__card-position-measure');
                if (positionMeasure) state.observed.push(positionMeasure);
            });
            state.compositions.forEach(function (composition) { if (composition) state.observed.push(composition); });
            state.observed.forEach(function (element) {
                owners.set(element, state);
                resizeObserver.observe(element);
            });
        }
        schedule();
        refreshSlideshowTimer();
    }

    function initializeScope(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container || !container.querySelectorAll) return;
        if (container.matches && container.matches(ROOT)) initializeRoot(container);
        container.querySelectorAll(ROOT).forEach(initializeRoot);
    }

    function registerEditorSkinVisibility() {
        var host;
        try {
            host = window.parent;
            if (host === window || !host.elementor || host.__apeironCeritaCintaSkinVisibility) return;
        } catch (error) {
            return; // A frontend embedded on another origin has no Elementor editor panel.
        }

        var editor = host.elementor;
        var panelView = editor.panel && editor.panel.currentView;
        if (!panelView) {
            if (!editorSkinWaiting) {
                editorSkinWaiting = true;
                editor.once('panel:init', registerEditorSkinVisibility);
                window.addEventListener('pagehide', function () {
                    editor.off('panel:init', registerEditorSkinVisibility);
                }, { once: true });
            }
            return;
        }
        editor.off('panel:init', registerEditorSkinVisibility);
        var panel = host.document.getElementById('elementor-panel');
        if (!panel || !panelView || !editor.channels || !editor.channels.deviceMode) return;
        host.__apeironCeritaCintaSkinVisibility = true;

        var style = host.document.createElement('style');
        var prefix = '#elementor-panel[data-apeiron-cerita-cinta-active-skin="scrapbook-collage"] ';
        var hidden = ['timeline_card_width', 'photo_ratio', 'story_spacing', 'timeline_spacing', 'card_position_x', 'card_position_y'];
        style.id = 'apeiron-cerita-cinta-editor-skins';
        style.textContent = '#elementor-panel[data-apeiron-cerita-cinta-active-skin="scrapbook-story"] .elementor-control-section_style_collage,'
            + '#elementor-panel[data-apeiron-cerita-cinta-active-skin="scrapbook-story"] .elementor-control[class*="elementor-control-collage_"]{display:none;}'
            + hidden.map(function (name) { return prefix + '.elementor-control[class*="elementor-control-' + name + '"]'; }).join(',')
            + '{display:none;}';
        var cardPanel = '#elementor-panel [data-apeiron-cerita-cinta-card-styles] ';
        style.textContent += '#elementor-panel[data-apeiron-cerita-cinta-active-skin] .elementor-control-per_card_editor{padding:0;}'
            + cardPanel + '.elementor-control-stories{padding:0;}'
            + cardPanel + '.elementor-repeater-fields{margin:0;border:0;}'
            + cardPanel + '.elementor-repeater-row-controls{padding:0;border:0;}'
            + cardPanel + '.apeiron-card-choice .elementor-control-input-wrapper{display:flex;align-items:center;gap:8px;}'
            + cardPanel + '.apeiron-card-choice select{flex:1;min-width:0;}'
            + cardPanel + '.apeiron-card-choice button{display:inline-flex;align-items:center;justify-content:center;flex:0 0 26px;height:26px;margin:0;padding:0;}'
            + cardPanel + '.apeiron-card-tabs{display:flex;margin:0 20px 16px;}'
            + cardPanel + '.apeiron-card-tabs button{flex:1;border:var(--e-a-border-bold);font:inherit;line-height:25px;color:inherit;background:var(--e-a-bg-default);}'
            + cardPanel + '.apeiron-card-tabs button+button{border-inline-start:0;}'
            + cardPanel + '.apeiron-card-tabs button[aria-selected="true"]{background:var(--e-a-bg-active-bold);color:var(--e-a-color-txt-accent);}'
            + cardPanel + '.apeiron-card-tools{margin:0 20px 16px;}'
            + cardPanel + '.apeiron-card-tools .apeiron-card-tabs{margin:0;}'
            + cardPanel + '.apeiron-card-tools .apeiron-card-text-target{padding:16px 0 0;}'
            + cardPanel + '.apeiron-card-text-target[hidden]{display:none;}';
        host.document.head.appendChild(style);

        var controls = editor.config.widgets['apeiron-cerita-cinta'].controls;
        var NativeRepeater = editor.getControlView('repeater');
        var NativeRow = NativeRepeater.prototype.childView;
        var selectedCardId = null;
        var selectedCardTab = 'card';
        var selectedTextPart = 'title';
        var controlViews = {};
        function isCardStyle(name) {
            return name === 'custom_style' || /^(item_|card_position_|tape_image_size)/.test(name);
        }
        function cardOverrides(settings) {
            var flags = settings.get('item_style_overrides') || [];
            return Array.isArray(flags) ? flags : Object.keys(flags).filter(function (name) { return flags[name] === 'yes'; });
        }
        function isPrimaryColour(name) {
            return name === 'item_card_color' || /^item_(title|date|description)_color$/.test(name);
        }
        function renderCardTools(row) {
            if (!row.cardTools || !row.cardTools.isConnected) {
                var tools = host.document.createElement('div');
                tools.className = 'apeiron-card-tools';
                tools.innerHTML = '<div class="elementor-control-type-tabs apeiron-card-tabs" role="tablist" aria-label="Pengaturan cerita"><button type="button" class="elementor-control-type-tab" role="tab" data-card-tab="card">Card</button><button type="button" class="elementor-control-type-tab" role="tab" data-card-tab="text">Teks</button></div><div class="elementor-control elementor-control-type-select elementor-label-inline apeiron-card-text-target"><div class="elementor-control-content"><div class="elementor-control-field"><label class="elementor-control-title">Elemen Teks</label><div class="elementor-control-input-wrapper"><select aria-label="Elemen Teks"><option value="title">Judul</option><option value="date">Tanggal</option><option value="description">Deskripsi</option></select></div></div></div></div>';
                tools.querySelector('.apeiron-card-tabs').addEventListener('click', function (event) {
                    var button = event.target.closest('[data-card-tab]');
                    if (!button || selectedCardTab === button.dataset.cardTab) return;
                    selectedCardTab = button.dataset.cardTab;
                    row.refreshControls();
                });
                tools.querySelector('select').addEventListener('change', function (event) {
                    selectedTextPart = event.target.value;
                    row.refreshControls();
                });
                row.el.querySelector('[data-card-tools]').appendChild(tools);
                row.cardTools = tools;
            }
            row.cardTools.querySelector('.apeiron-card-text-target').hidden = selectedCardTab !== 'text';
            row.cardTools.querySelector('select').value = selectedTextPart;
            row.cardTools.querySelectorAll('[data-card-tab]').forEach(function (tab) {
                tab.setAttribute('aria-selected', String(selectedCardTab === tab.dataset.cardTab));
            });
            var primary = row.el.querySelector('[data-card-colours]');
            row.children.each(function (control) {
                if (isPrimaryColour(control.model.get('name'))) primary.appendChild(control.el);
            });
        }
        function deferredRow(styleOnly) {
            return NativeRow.extend({
                template: styleOnly ? function () { return '<div data-card-colours></div><div data-card-tools></div><div class="elementor-repeater-row-controls editable"></div>'; } : NativeRow.prototype.template,
                initialize: function (options) {
                    var fields = {};
                    Object.keys(options.controlFields).forEach(function (name) {
                        if (isCardStyle(name) !== styleOnly || (styleOnly && (name === 'custom_style' || name === 'item_style_overrides'))) return;
                        var field = Object.assign({}, options.controlFields[name]);
                        if (styleOnly && name.indexOf('item_') === 0) {
                            field.condition = Object.assign({}, field.condition);
                            delete field.condition.custom_style;
                            delete field.conditions;
                            if (/_typography_typography$/.test(name)) field.label = 'Tipografi';
                        }
                        fields[name] = field;
                    });
                    NativeRow.prototype.initialize.call(this, Object.assign({}, options, { controlFields: fields }));
                    this.deferredControls = this.collection;
                    this.collection = new host.Backbone.Collection();
                    if (styleOnly) {
                        this.listenTo(options.container.globals, 'change', function (globals) {
                            var flags = cardOverrides(this.model).slice();
                            var count = flags.length;
                            Object.keys(globals.changed).forEach(function (name) {
                                if (name.indexOf('item_') !== 0 || !globals.get(name)) return;
                                var settings = {};
                                settings[name] = globals.get(name);
                                Object.values(options.container.getGroupRelatedControls(settings)).forEach(function (control) {
                                    if (flags.indexOf(control.name) === -1) flags.push(control.name);
                                });
                            });
                            if (flags.length !== count) host.$e.run('document/elements/settings', { container: options.container, settings: { item_style_overrides: flags } });
                        });
                        this.listenTo(this.model, 'change:item_border_border', function () {
                            if (this.controlsLoaded) this.refreshControls();
                        });
                        this.el.addEventListener('click', function (event) {
                            var label = event.target.closest('.elementor-control-popover-toggle-toggle-label');
                            if (!label) return;
                            var element = label.closest('.elementor-control');
                            var toggle = this.children.find(function (control) { return control.el === element; });
                            var prefix = toggle && toggle.model.get('groupPrefix');
                            if (!prefix || this.loadedPopovers[prefix]) return;
                            this.loadedPopovers[prefix] = true;
                            var models = this.deferredControls.filter(function (control) {
                                return control.get('type') !== 'popover_toggle' && control.get('name').indexOf(prefix) === 0;
                            });
                            this.collection.add(models, { at: this.collection.indexOf(toggle.model) + 1 });
                            NativeRow.prototype.onRender.call(this);
                            var row = this;
                            host.requestAnimationFrame(function () {
                                if (row.isDestroyed) return;
                                row.children.each(function (control) { control.toggleControlVisibility(); });
                            });
                        }.bind(this), true);
                    }
                },
                openControls: function () {
                    if (this.controlsLoaded) return;
                    this.controlsLoaded = true;
                    this.refreshControls();
                },
                refreshControls: function () {
                    if (this.popovers) {
                        this.popovers.forEach(function (popover) { popover.destroy(); });
                        this.popovers = [];
                    }
                    var models = this.deferredControls.models;
                    if (styleOnly) {
                        this.loadedPopovers = {};
                        this.cardSkin = panel.getAttribute('data-apeiron-cerita-cinta-active-skin');
                        var story = this.model;
                        var collage = this.cardSkin === 'scrapbook-collage';
                        models = this.deferredControls.filter(function (control) {
                            var name = control.get('name');
                            if (isPrimaryColour(name)) return true;
                            if (name.indexOf('card_position_') === 0 && collage) return false;
                            if (name.indexOf('tape_image_size') === 0 && story.get('tape_type') !== 'image') return false;
                            if (/^item_border_(width|color)/.test(name) && (!story.get('item_border_border') || story.get('item_border_border') === 'none')) return false;
                            var text = /^item_(title|date|description)_/.test(name);
                            if (selectedCardTab === 'card') return !text && (name.indexOf('item_shadow_') !== 0 || control.get('type') === 'popover_toggle');
                            return name === 'item_' + selectedTextPart + '_typography_typography';
                        });
                        models = models.filter(function (control) { return isPrimaryColour(control.get('name')); })
                            .concat(models.filter(function (control) { return !isPrimaryColour(control.get('name')); }));
                    }
                    if (styleOnly) {
                        this.collection.remove(this.collection.filter(function (control) { return models.indexOf(control) === -1; }));
                        var collection = this.collection;
                        models.forEach(function (control, index) {
                            if (!collection.get(control)) collection.add(control, { at: index });
                        });
                    } else {
                        this.collection.reset(models);
                    }
                    NativeRow.prototype.onRender.call(this);
                    if (styleOnly) renderCardTools(this);
                },
                getChildView: function (control) {
                    var View = NativeRow.prototype.getChildView.call(this, control);
                    if (!styleOnly || control.get('name').indexOf('item_') !== 0) return View;
                    var type = control.get('type');
                    if (!controlViews[type]) controlViews[type] = View.extend({
                        getControlValue: function () {
                            var name = this.model.get('name');
                            var flags = cardOverrides(this.container.settings);
                            var legacy = this.container.settings.get('custom_style') === 'yes';
                            if (name.indexOf('item_shadow') === 0 || legacy || flags.indexOf(name) !== -1) return View.prototype.getControlValue.apply(this, arguments);
                            var value = this.model.get('default');
                            return arguments.length && value && typeof value === 'object' ? value[arguments[0]] : value;
                        },
                        setSettingsModel: function (value) {
                            var name = this.model.get('name');
                            var flags = cardOverrides(this.container.settings).slice();
                            if (flags.indexOf(name) === -1) flags.push(name);
                            var settings = { item_style_overrides: flags };
                            settings[name] = value;
                            host.$e.run('document/elements/settings', { container: this.container, settings: settings });
                            this.triggerMethod('settings:change');
                        },
                    });
                    return controlViews[type];
                },
            });
        }
        var ContentRepeater = NativeRepeater.extend({
            childView: deferredRow(false),
            editRow: function (row) {
                row.openControls();
                return NativeRepeater.prototype.editRow.apply(this, arguments);
            },
        });
        var StyleRepeater = ContentRepeater.extend({
            getTemplate: function () { return function () { return '<div class="elementor-repeater-fields-wrapper" role="list"></div>'; }; },
            childView: deferredRow(true),
            filter: function (model) { return model.get('_id') === selectedCardId; },
            childViewOptions: function (model) {
                return NativeRepeater.prototype.childViewOptions.call(this, model, this.collection.indexOf(model));
            },
            onRenderCollection: function () {
                var row = this.children.findByIndex(0);
                if (row) {
                    row.openControls();
                    row.getChildViewContainer(row).addClass('editable');
                    row.$el.find('.elementor-repeater-row-tools').hide();
                }
            },
            onRender: function () {
                NativeRepeater.prototype.onRender.apply(this, arguments);
                this.$el.removeClass('elementor-control-type-repeater');
            },
        });
        var currentPage = null;
        var cardRegion = null;
        var cardMount = null;
        var cardCollection = null;
        function refreshCardChoice() {
            if (!cardMount || !cardCollection || !cardRegion || !cardRegion.currentView) return;
            var select = cardMount.querySelector('select');
            if (!cardCollection.findWhere({ _id: selectedCardId })) selectedCardId = cardCollection.length ? cardCollection.at(0).get('_id') : null;
            select.replaceChildren();
            cardCollection.each(function (model, index) {
                var option = host.document.createElement('option');
                option.value = model.get('_id');
                option.textContent = model.get('title') || 'Cerita ' + (index + 1);
                select.appendChild(option);
            });
            select.value = selectedCardId || '';
            var row = cardRegion.currentView.children.findByIndex(0);
            if (!row || row.model.get('_id') !== selectedCardId) cardRegion.currentView._renderChildren();
        }
        function clearCardPanel() {
            if (cardCollection) cardCollection.off('add remove reset sort change:title', refreshCardChoice);
            if (cardRegion) cardRegion.destroy();
            cardRegion = cardMount = cardCollection = null;
        }
        function configureCardPanel(page, edited) {
            if (page !== currentPage) {
                clearCardPanel();
                if (currentPage) {
                    currentPage.off('render:collection', schedulePanel);
                    currentPage.el.removeEventListener('click', currentPage.__apeironLoadStylePopover, true);
                    currentPage.getChildView = currentPage.__apeironCardControlView;
                    currentPage.getControlViewByName = currentPage.__apeironCardControlByName;
                    currentPage.filter = currentPage.__apeironStyleFilter;
                    currentPage._renderChildren = currentPage.__apeironStyleRender;
                    delete currentPage.__apeironCardControlView;
                    delete currentPage.__apeironCardControlByName;
                    delete currentPage.__apeironStyleFilter;
                    delete currentPage.__apeironStyleRender;
                    delete currentPage.__apeironLoadStylePopover;
                }
                currentPage = page;
                if (page) {
                    page.__apeironCardControlView = page.getChildView;
                    page.__apeironCardControlByName = page.getControlViewByName;
                    page.__apeironStyleFilter = page.filter;
                    page.__apeironStyleRender = page._renderChildren;
                    var popoverFields = {};
                    var opener = null;
                    var inPopover = false;
                    var loadedPopovers = {};
                    var popoverSection = null;
                    page.collection.each(function (control) {
                        if (control.get('tab') !== 'style') return;
                        if (control.get('type') === 'popover_toggle') opener = control.get('name');
                        var popover = control.get('popover');
                        if (popover && popover.start) inPopover = true;
                        if (opener && inPopover) popoverFields[control.get('name')] = opener;
                        if (popover && popover.end) { inPopover = false; opener = null; }
                    });
                    page.filter = function (control) {
                        var opener = popoverFields[control.get('name')];
                        return this.__apeironStyleFilter.apply(this, arguments) && (!opener || loadedPopovers[opener]);
                    };
                    page._renderChildren = function () {
                        var section = this.activeTab + ':' + this.activeSection;
                        if (section !== popoverSection) { loadedPopovers = {}; popoverSection = section; }
                        return this.__apeironStyleRender.apply(this, arguments);
                    };
                    page.__apeironLoadStylePopover = function (event) {
                        var label = event.target.closest('.elementor-control-popover-toggle-toggle-label');
                        if (!label) return;
                        var element = label.closest('.elementor-control');
                        var toggle = page.children.find(function (control) { return control.el === element; });
                        if (!toggle || toggle.model.get('tab') !== 'style') return;
                        var name = toggle.model.get('name');
                        if (loadedPopovers[name] || Object.keys(popoverFields).every(function (field) { return popoverFields[field] !== name; })) return;
                        event.preventDefault();
                        event.stopPropagation();
                        loadedPopovers[name] = true;
                        page._renderChildren();
                        page.getControlViewByName(name).ui.popoverToggle[0].click();
                    };
                    page.el.addEventListener('click', page.__apeironLoadStylePopover, true);
                    page.getChildView = function (control) {
                        return control.get('name') === 'stories' ? ContentRepeater : this.__apeironCardControlView.apply(this, arguments);
                    };
                    page.getControlViewByName = function (name) {
                        if (name === 'stories' && cardRegion && cardRegion.currentView) return cardRegion.currentView;
                        return this.__apeironCardControlByName.apply(this, arguments);
                    };
                    page.on('render:collection', schedulePanel);
                    if (page.getControlViewByName('stories') || page.children.some(function (control) { return popoverFields[control.model.get('name')]; })) page._renderChildren();
                }
            }
            var mount = page && edited.model.get('settings').get('show_story_styles') === 'yes' && panel.querySelector('[data-apeiron-cerita-cinta-card-styles]');
            if (!mount) {
                clearCardPanel();
                return;
            }
            var container = edited.getContainer();
            if (cardMount === mount && cardRegion.currentView.collection === container.settings.get('stories')) return;
            clearCardPanel();
            // The Style panel edits the original stories collection: no duplicated data or migration.
            var model = page.getControlModel('stories').clone();
            model.set({ label: 'Pilih Cerita', title_field: '', tab: 'style', item_actions: { add: false, duplicate: false, remove: false, sort: false } });
            var repeater = new StyleRepeater(Object.assign({}, page.childViewOptions(), { model: model }));
            cardMount = mount;
            cardCollection = repeater.collection;
            if (!cardCollection.findWhere({ _id: selectedCardId })) selectedCardId = cardCollection.length ? cardCollection.at(0).get('_id') : null;
            var choice = host.document.createElement('div');
            choice.className = 'elementor-control elementor-control-type-select elementor-label-block apeiron-card-choice';
            var content = host.document.createElement('div');
            content.className = 'elementor-control-content';
            var field = host.document.createElement('div');
            field.className = 'elementor-control-field';
            var label = host.document.createElement('label');
            label.className = 'elementor-control-title';
            label.textContent = 'Pilih Cerita';
            var input = host.document.createElement('div');
            input.className = 'elementor-control-input-wrapper';
            var select = host.document.createElement('select');
            select.setAttribute('aria-label', 'Pilih Cerita');
            select.id = 'apeiron-card-choice-' + container.id;
            label.htmlFor = select.id;
            var reset = host.document.createElement('button');
            reset.type = 'button';
            reset.className = 'elementor-control-unit-1 e-control-tool';
            reset.title = 'Reset cerita ke Style global';
            reset.setAttribute('aria-label', reset.title);
            reset.innerHTML = '<i class="eicon-undo" aria-hidden="true"></i>';
            input.append(select, reset);
            field.append(label, input);
            content.appendChild(field);
            choice.appendChild(content);
            var region = host.document.createElement('div');
            mount.replaceChildren(choice, region);
            select.addEventListener('change', function () {
                selectedCardId = select.value;
                cardRegion.currentView._renderChildren();
            });
            reset.addEventListener('click', function () {
                var story = cardCollection.findWhere({ _id: selectedCardId });
                if (!story) return;
                var index = cardCollection.indexOf(story);
                var child = container.repeaters.stories.children[index];
                var settings = { custom_style: '', item_style_overrides: [] };
                Object.keys(child.controls).forEach(function (name) {
                    if (name.indexOf('item_') === 0 && name !== 'item_style_overrides') settings[name] = child.controls[name].default;
                });
                var globals = Object.assign({}, child.globals.toJSON());
                Object.keys(globals).forEach(function (name) { if (name.indexOf('item_') === 0) delete globals[name]; });
                settings.__globals__ = globals;
                host.$e.run('document/elements/settings', { container: child, settings: settings });
                cardRegion.currentView._renderChildren();
            });
            cardRegion = new host.Marionette.Region({ el: region });
            repeater.on('childview:childview:settings:change', function (row, control) {
                page.onChildviewSettingsChange(control);
            });
            cardRegion.show(repeater);
            cardRegion.currentView._parent = page;
            cardCollection.on('add remove reset sort change:title', refreshCardChoice);
            refreshCardChoice();
        }
        var skinEvents = Object.keys(controls).filter(function (name) {
            return name === 'skin' || (name.indexOf('skin_') === 0 && controls[name].parent);
        }).map(function (name) { return 'change:' + name; }).join(' ') + ' change:stories change:show_story_styles';
        var currentSettings = null;
        var pending = null;
        function schedulePanel() {
            if (pending !== null) return;
            pending = host.requestAnimationFrame(updatePanel);
        }
        function updatePanel() {
            pending = null;
            var view = panelView.getCurrentPageView();
            var edited = view && view.getOption && view.getOption('editedElementView');
            var model = edited && edited.model;
            var settings = model && model.get('widgetType') === 'apeiron-cerita-cinta' ? model.get('settings') : null;
            if (settings !== currentSettings) {
                if (currentSettings) currentSettings.off(skinEvents, schedulePanel);
                currentSettings = settings;
                if (settings) settings.on(skinEvents, schedulePanel);
            }
            if (!settings) {
                configureCardPanel(null, null);
                panel.removeAttribute('data-apeiron-cerita-cinta-active-skin');
                return;
            }
            var device = editor.channels.deviceMode.request('currentMode') || 'desktop';
            var key = device === 'desktop' ? 'skin' : 'skin_' + device;
            var value = '';
            while (key) {
                value = settings.get(key);
                if (value) break;
                key = controls[key] && controls[key].parent || (key !== 'skin' ? 'skin' : null);
            }
            var active = ['scrapbook-collage', 'scrapbook_collage', 'collage'].indexOf(value) !== -1 ? 'scrapbook-collage' : 'scrapbook-story';
            panel.setAttribute('data-apeiron-cerita-cinta-active-skin', active);
            configureCardPanel(view, edited);
            var row = cardRegion && cardRegion.currentView && cardRegion.currentView.children.findByIndex(0);
            if (row && row.cardSkin !== active) row.refreshControls();
        }
        function resetPanel() {
            if (currentSettings) currentSettings.off(skinEvents, schedulePanel);
            currentSettings = null;
            configureCardPanel(null, null);
            panel.removeAttribute('data-apeiron-cerita-cinta-active-skin');
        }
        panelView.on('set:page', schedulePanel);
        editor.channels.deviceMode.on('change', schedulePanel);
        editor.on('document:unload', resetPanel);
        window.addEventListener('pagehide', function () {
            panelView.off('set:page', schedulePanel);
            editor.channels.deviceMode.off('change', schedulePanel);
            editor.off('document:unload', resetPanel);
            if (pending !== null) host.cancelAnimationFrame(pending);
            resetPanel();
            style.remove();
            delete host.__apeironCeritaCintaSkinVisibility;
        }, { once: true });
        schedulePanel();
    }

    function registerElementorHook() {
        registerEditorSkinVisibility();
        if (hookRegistered || !window.elementorFrontend || !window.elementorFrontend.hooks) return;
        hookRegistered = true;
        window.elementorFrontend.hooks.addAction('frontend/element_ready/apeiron-cerita-cinta.default', initializeScope);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initializeScope(document); }, { once: true });
    } else {
        initializeScope(document);
    }
    window.addEventListener('elementor/frontend/init', registerElementorHook);
    registerElementorHook();
}());
