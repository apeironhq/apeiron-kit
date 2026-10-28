// Paralaks 2.5D engine: one GSAP timeline per widget, scrubbed by a pinned ScrollTrigger.
// gsap.matchMedia() owns every tween/trigger/split so reduced-motion changes and Elementor re-renders revert cleanly.
// One shared native motion query avoids accumulating query listeners on editor rebuilds.
(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        root: '[data-apeiron-depthscape]',
        widget: '.elementor-widget-apeiron-depthscape',
        stage: '.apeiron-depthscape__stage',
        camera: '.apeiron-depthscape__camera',
        screen: '.apeiron-depthscape__screen',
        inner: '.apeiron-depthscape__inner',
        idle: '.apeiron-depthscape__idle',
        split: '[data-ds-split]',
        hint: '.apeiron-depthscape__hint',
    });
    var BOOT_TIMEOUT = 2500;
    var OPENING_DURATION = 3;
    var SNAP_INTENT = 0.1;
    var MEDIA = Object.freeze({
        reduce: '(prefers-reduced-motion: reduce)',
        motion: '(prefers-reduced-motion: no-preference)',
    });
    var states = new WeakMap();
    var liveRoots = [];
    var pluginsRegistered = false;
    var refreshListenerAdded = false;
    var resizeTimer = null;
    var motionQuery = null;
    var motionWatchBound = false;
    var detachObserver = null;

    function getGsap() {
        return window.gsap || null;
    }

    function hasScrollTrigger() {
        return typeof window.ScrollTrigger !== 'undefined';
    }

    function hasSplitText() {
        return typeof window.SplitText !== 'undefined';
    }

    function registerPlugins(gsap) {
        if (pluginsRegistered) {
            return;
        }

        var plugins = [];
        if (hasScrollTrigger()) {
            plugins.push(window.ScrollTrigger);
        }
        if (hasSplitText()) {
            plugins.push(window.SplitText);
        }
        if (plugins.length) {
            gsap.registerPlugin.apply(gsap, plugins);
        }

        pluginsRegistered = true;
    }

    function isEditMode() {
        return !!(window.elementorFrontend
            && typeof window.elementorFrontend.isEditMode === 'function'
            && window.elementorFrontend.isEditMode());
    }

    function toNumber(value, fallback) {
        var number = parseFloat(value);
        return Number.isFinite(number) ? number : fallback;
    }

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function parseConfig(root) {
        try {
            var config = JSON.parse(root.getAttribute('data-apeiron-depthscape') || '');
            return config && typeof config === 'object' ? config : null;
        } catch (error) {
            return null;
        }
    }

    // Layer state: x/y are % of the stage so motion scales with the screen, opacity uses autoAlpha.
    function layerVars(props, size, useBlur, liveSize) {
        var vars = {};

        if (Object.prototype.hasOwnProperty.call(props, 'x')) {
            var x = toNumber(props.x, 0) / 100;
            vars.x = liveSize ? function () { return x * size.width; } : x * size.width;
        }
        if (Object.prototype.hasOwnProperty.call(props, 'y')) {
            var y = toNumber(props.y, 0) / 100;
            vars.y = liveSize ? function () { return y * size.height; } : y * size.height;
        }
        if (Object.prototype.hasOwnProperty.call(props, 'scale')) {
            vars.scale = toNumber(props.scale, 1);
        }
        if (Object.prototype.hasOwnProperty.call(props, 'rotate')) {
            vars.rotation = toNumber(props.rotate, 0);
        }
        if (Object.prototype.hasOwnProperty.call(props, 'opacity')) {
            vars.autoAlpha = clamp(toNumber(props.opacity, 1), 0, 1);
        }
        if (useBlur && Object.prototype.hasOwnProperty.call(props, 'blur')) {
            vars.filter = 'blur(' + Math.max(toNumber(props.blur, 0), 0) + 'px)';
        }

        return vars;
    }

    // Reduced motion keeps fades only: no travel, zoom, or spin.
    function calmProps(props) {
        var calm = {};
        if (Object.prototype.hasOwnProperty.call(props, 'opacity')) {
            calm.opacity = props.opacity;
        }
        return calm;
    }

    function cameraState(camera) {
        return {
            x: toNumber(camera && camera.x, 0),
            y: toNumber(camera && camera.y, 0),
            z: toNumber(camera && camera.z, 0),
            rx: toNumber(camera && camera.rx, 0),
            ry: toNumber(camera && camera.ry, 0),
            rz: toNumber(camera && camera.rz, 0),
        };
    }

    // Perspective is projected here and applied as plain 2D scale/translate. Browsers rasterise 2D
    // scale at display resolution, whereas a CSS perspective camera magnifies a low-res bitmap.
    // layer: { depth, ax, ay } where ax/ay is the anchor offset from the stage centre at camera rest.
    // Returns null when the layer has passed behind the camera.
    function projectLayer(layer, camera, size, perspective, compensate) {
        var depth = toNumber(layer.depth, 0);
        var rest = perspective / Math.max(perspective - depth, 1);
        var distance = perspective - (depth + camera.z);
        if (distance < 1) {
            return null;
        }

        var magnify = perspective / distance;
        var turnY = camera.ry * Math.PI / 180;
        var turnX = camera.rx * Math.PI / 180;
        // Panning moves the world opposite to where the viewer looks; turning orbits the subject plane
        // (depth 0), so each plane shifts in proportion to its depth.
        var worldX = (compensate ? layer.ax / rest : layer.ax) - camera.x / 100 * size.width + depth * Math.sin(turnY);
        var worldY = (compensate ? layer.ay / rest : layer.ay) - camera.y / 100 * size.height - depth * Math.sin(turnX);
        var screenX = worldX * magnify;
        var screenY = worldY * magnify;

        if (camera.rz) {
            var roll = camera.rz * Math.PI / 180;
            var rolledX = screenX * Math.cos(roll) - screenY * Math.sin(roll);
            screenY = screenX * Math.sin(roll) + screenY * Math.cos(roll);
            screenX = rolledX;
        }

        return {
            x: screenX - layer.ax,
            y: screenY - layer.ay,
            scale: compensate ? magnify / rest : magnify,
            rotation: camera.rz,
        };
    }

    function mergeCamera(current, next) {
        var merged = {};
        var key;

        for (key in current) {
            if (Object.prototype.hasOwnProperty.call(current, key)) {
                merged[key] = current[key];
            }
        }
        for (key in next) {
            if (Object.prototype.hasOwnProperty.call(next, key)) {
                merged[key] = next[key];
            }
        }

        return merged;
    }

    // Idle loops oscillate symmetrically around the rest pose so they never drift away over time.
    function idleRange(idle) {
        var amount = Math.max(toNumber(idle && idle.amount, 10), 0);
        var half = amount / 2;

        switch (idle && idle.type) {
            case 'float':
                return { from: { y: half }, to: { y: -half } };
            case 'sway':
                return { from: { rotation: -half }, to: { rotation: half } };
            case 'breathe':
                return { from: { scale: 1 }, to: { scale: 1 + amount / 100 } };
            case 'drift':
                return { from: { x: -half }, to: { x: half } };
            default:
                return null;
        }
    }

    // Explicit end values keep filter/percent interpolation exact when the timeline is scrubbed backwards.
    function revealVars(style, reduce) {
        if (reduce || style === 'fade') {
            return { from: { autoAlpha: 0, y: reduce ? 0 : 14 }, to: { autoAlpha: 1, y: 0 } };
        }
        if (style === 'blur') {
            return {
                from: { autoAlpha: 0, y: 8, filter: 'blur(10px)' },
                to: { autoAlpha: 1, y: 0, filter: 'blur(0px)' },
            };
        }

        return { from: { autoAlpha: 0, yPercent: 115 }, to: { autoAlpha: 1, yPercent: 0 } };
    }

    // Snap with intent: a short push (beyond SNAP_INTENT of a scene) commits to the next scene in that
    // direction, a smaller nudge settles back. Resting exactly on a scene never moves, so snaps cannot chain.
    function pickSnap(points, value, direction) {
        var lower = points[0];
        var upper = points[points.length - 1];

        for (var i = 0; i < points.length; i++) {
            if (Math.abs(points[i] - value) < 0.0005) {
                return points[i];
            }
            if (points[i] <= value) {
                lower = points[i];
            }
            if (points[i] >= value) {
                upper = points[i];
                break;
            }
        }

        var span = upper - lower;
        if (span <= 0) {
            return lower;
        }

        var travelled = (value - lower) / span;
        if (direction < 0) {
            return travelled < 1 - SNAP_INTENT ? lower : upper;
        }

        return travelled > SNAP_INTENT ? upper : lower;
    }

    function sceneDuration(scene) {
        return Math.max(toNumber(scene && scene.length, 100), 1) / 100;
    }

    function collectFlags(config) {
        var flags = { blur: {}, reveal: {} };

        (config.keyframes || []).forEach(function (keyframe) {
            var props = keyframe.props || {};
            if (Object.prototype.hasOwnProperty.call(props, 'blur')) {
                flags.blur[keyframe.layer] = true;
            }
            if (keyframe.reveal) {
                flags.reveal[keyframe.layer] = true;
            }
        });

        return flags;
    }

    function splitLayer(layer, type, style, reduce) {
        var targets = layer.querySelectorAll(SELECTOR.split);
        if (layer.classList.contains('is-type-container')) {
            if (isEditMode() && layer.classList.contains('is-editing-content')) {
                return null;
            }
            targets = Array.from(layer.querySelectorAll('.apeiron-depthscape__content .elementor-heading-title, .apeiron-depthscape__content .elementor-widget-text-editor p')).filter(function (target) {
                return target.closest('[data-ds-layer]') === layer;
            });
        }
        if (!targets.length || !hasSplitText() || type === 'none') {
            return null;
        }

        var options = {
            type: type === 'chars' ? 'words,chars' : type,
            linesClass: 'ds-split-line',
            wordsClass: 'ds-split-word',
            charsClass: 'ds-split-char',
        };
        if (style === 'rise' && !reduce) {
            options.mask = type;
        }

        var split = window.SplitText.create(targets, options);

        return split[type] && split[type].length ? split[type] : null;
    }

    function measure(stage) {
        return {
            width: stage.clientWidth || window.innerWidth,
            height: stage.clientHeight || window.innerHeight,
        };
    }

    function idleTransform(vars) {
        return 'translate(' + toNumber(vars.x, 0).toFixed(2) + 'px,' + toNumber(vars.y, 0).toFixed(2) + 'px) rotate('
            + toNumber(vars.rotation, 0).toFixed(3) + 'deg) scale(' + toNumber(vars.scale, 1).toFixed(4) + ')';
    }

    // Idle loops use the Web Animations API with concrete transforms, so the compositor runs them:
    // a resting page costs no JavaScript and no style recalculation per frame.
    function startIdleLoop(ctx, el, range, halfCycle) {
        if (typeof el.animate !== 'function') {
            return;
        }

        var duration = halfCycle * 1000;
        var animation = el.animate(
            [{ transform: idleTransform(range.from) }, { transform: idleTransform(range.to) }],
            {
                duration: duration,
                // Negative delay desynchronises loops so several flowers never sway in lockstep.
                delay: -Math.random() * duration * 2,
                direction: 'alternate',
                iterations: Infinity,
                easing: 'ease-in-out',
            }
        );
        ctx.idleLoops.push(animation);
        return animation;
    }

    function stopIdleLoops(ctx) {
        ctx.idleLoops.forEach(function (animation) {
            animation.cancel();
        });
        ctx.idleLoops = [];
    }

    // Ambient videos follow layer fades, camera visibility and viewport intersection.
    function syncVideos(ctx, retry) {
        ctx.videos.forEach(function (record) {
            if (record.disposed) { return; }
            var visible = ctx.active && !ctx.reduce && ctx.root.isConnected && record.inView
                && document.visibilityState !== 'hidden' && record.layer.style.visibility !== 'hidden'
                && record.inner.style.visibility !== 'hidden' && toNumber(record.inner.style.opacity, 1) > 0.01;
            if (!visible) {
                record.wanted = false;
                if (!record.el.paused || record.pending) { record.el.pause(); }
                return;
            }
            if (record.wanted && !(retry && record.blocked)) { return; }
            record.wanted = true;
            if (record.pending || !record.el.paused || (record.blocked && !retry)) { return; }

            var failed = function (error) {
                record.pending = false;
                record.blocked = !!error && error.name === 'NotAllowedError';
                if (!record.disposed && error && !record.blocked && error.name !== 'AbortError') {
                    console.warn('ApeironKit Paralaks 2.5D: video could not be played.', error);
                }
            };
            record.pending = true;
            try {
                var playback = record.el.play();
                if (playback && typeof playback.then === 'function') {
                    playback.then(function () {
                        record.pending = false;
                        record.blocked = false;
                    }, failed);
                } else {
                    record.pending = false;
                    record.blocked = false;
                }
            } catch (error) {
                failed(error);
            }
        });
    }

    function setupVideos(ctx) {
        if (!ctx.videos.length) { return function () {}; }
        var update = function () { syncVideos(ctx); };
        var retry = function () { syncVideos(ctx, true); };
        var observer = typeof window.IntersectionObserver === 'function' ? new window.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var record = ctx.videos.find(function (item) { return item.el === entry.target; });
                if (record) { record.inView = entry.isIntersecting; }
            });
            update();
        }) : null;
        if (observer) {
            ctx.videos.forEach(function (record) { observer.observe(record.el); });
        }
        document.addEventListener('visibilitychange', update);
        document.addEventListener('apeiron:cover:opening', retry);
        ctx.root.addEventListener('pointerup', retry);
        update();

        return function () {
            if (observer) { observer.disconnect(); }
            document.removeEventListener('visibilitychange', update);
            document.removeEventListener('apeiron:cover:opening', retry);
            ctx.root.removeEventListener('pointerup', retry);
            ctx.videos.forEach(function (record) {
                record.disposed = true;
                record.wanted = false;
                record.el.pause();
            });
        };
    }

    function setActive(ctx, active) {
        ctx.active = active;
        ctx.root.classList.toggle('is-idle-paused', !active);
        ctx.idleLoops.forEach(function (animation) {
            if (active) {
                animation.play();
            } else {
                animation.pause();
            }
        });
        ctx.idleTweens.forEach(function (tween) {
            if (active) {
                tween.play();
            } else {
                tween.pause();
            }
        });
        if (ctx.videos.length) { syncVideos(ctx); }
    }

    // Anchor = the point the editor positions (left/top); full-cover layers anchor at their centre.
    function measureWorld(ctx) {
        ctx.world.forEach(function (item) {
            var el = item.el;
            var cover = el.classList.contains('is-fit-cover');
            item.ax = el.offsetLeft + (cover ? el.offsetWidth / 2 : 0) - ctx.size.width / 2;
            item.ay = el.offsetTop + (cover ? el.offsetHeight / 2 : 0) - ctx.size.height / 2;
        });
    }

    // How strongly a plane reacts to scroll wind: far planes barely move, near foreground bends most.
    function windFactor(depth) {
        return clamp(0.35 + (toNumber(depth, 0) + 700) / 1100, 0.15, 1.4);
    }

    // Damped spring toward the target; overshoot is what makes foliage feel springy after a flick.
    function springStep(spring, target, delta) {
        var step = Math.min(Math.max(delta, 0), 4);
        spring.velocity += (target - spring.value) * 0.09 * step;
        spring.velocity *= Math.pow(0.84, step);
        spring.value += spring.velocity * step;

        if (Math.abs(spring.value) < 0.0005 && Math.abs(spring.velocity) < 0.0005 && target === 0) {
            spring.value = 0;
            spring.velocity = 0;
        }

        return spring.value;
    }

    function renderWorld(ctx) {
        var view = ctx.view;
        var breath = ctx.breath;
        var camera = {
            x: view.x + breath.x,
            y: view.y + breath.y,
            z: view.z + breath.z,
            rx: view.rx + ctx.tilt.rx,
            ry: view.ry + ctx.tilt.ry,
            rz: view.rz + breath.rz,
        };
        var gust = ctx.wind.value;
        // Nothing moved since the last frame: skip projection and DOM writes entirely.
        var signature = [camera.x, camera.y, camera.z, camera.rx, camera.ry, camera.rz, gust].map(function (value) {
            return value.toFixed(4);
        }).join('|');
        if (signature === ctx.lastSignature) {
            return;
        }
        ctx.lastSignature = signature;

        ctx.world.forEach(function (item) {
            var projected = projectLayer(item, camera, ctx.size, ctx.perspective, ctx.compensate);
            var bend = item.wind ? gust * ctx.windStrength * windFactor(item.depth) : 0;
            var drift = item.gust ? gust * 46 * windFactor(item.depth) : 0;
            var transform = projected
                ? 'translate3d(' + (projected.x + drift).toFixed(2) + 'px,' + projected.y.toFixed(2) + 'px,0) translate(var(--ds-tx),var(--ds-ty)) rotate(' + projected.rotation.toFixed(3) + 'deg) scale(' + projected.scale.toFixed(4) + ')' + (bend ? ' skewX(' + bend.toFixed(3) + 'deg)' : '')
                : item.transform;
            var visibility = projected ? '' : 'hidden';

            if (transform !== item.transform) {
                item.el.style.transform = transform;
                item.transform = transform;
            }
            if (visibility !== item.visibility) {
                item.el.style.visibility = visibility;
                item.visibility = visibility;
            }
        });
    }

    function prepareLayers(ctx, reduce) {
        var gsap = ctx.gsap;
        var config = ctx.config;
        var flags = collectFlags(config);
        var layers = Array.prototype.slice.call(ctx.camera.children)
            .concat(ctx.screen ? Array.prototype.slice.call(ctx.screen.children) : []);

        layers.forEach(function (layer) {
            var index = parseInt(layer.getAttribute('data-ds-index'), 10);
            var layerConfig = (config.layers || [])[index];
            var inner = layer.querySelector(SELECTOR.inner);
            if (!layerConfig || !inner) {
                return;
            }

            var key = layerConfig.key;
            var start = layerConfig.start || {};
            var record = {
                inner: inner,
                config: layerConfig,
                useBlur: toNumber(start.blur, 0) > 0 || !!flags.blur[key],
                units: null,
            };

            gsap.set(inner, layerVars(reduce ? calmProps(start) : start, ctx.size, record.useBlur && !reduce));

            var video = layerConfig.type === 'video' ? layer.querySelector('.apeiron-depthscape__video') : null;
            if (video) {
                video.muted = true;
                ctx.videos.push({
                    el: video, layer: layer, inner: inner, pending: false, blocked: false,
                    wanted: false, disposed: false, inView: typeof window.IntersectionObserver !== 'function',
                });
            }

            if (flags.reveal[key]) {
                if (isEditMode()) {
                    record.splitContext = gsap.context(function () {
                        record.units = splitLayer(layer, layerConfig.split, layerConfig.reveal, reduce);
                    }, layer);
                } else {
                    record.units = splitLayer(layer, layerConfig.split, layerConfig.reveal, reduce);
                }
            }

            if (layerConfig.type === 'container' && flags.reveal[key] && layerConfig.split !== 'none' && hasSplitText()) {
                var content = layer.querySelector('[data-ds-content-id]');
                if (content) { ctx.state.revealContents.add(content.getAttribute('data-ds-content-id')); }
            }

            var range = !reduce && config.idle ? idleRange(layerConfig.idle) : null;
            var idle = layer.querySelector(SELECTOR.idle);
            if (range && idle) {
                record.idleLoop = startIdleLoop(ctx, idle, range, Math.max(toNumber(layerConfig.idle.speed, 6), 0.5) / 2);
            }

            if (!ctx.layersByKey[key]) {
                ctx.layersByKey[key] = [];
            }
            ctx.layersByKey[key].push(record);

            if (layer.parentNode === ctx.camera) {
                ctx.world.push({ el: layer, depth: toNumber(layerConfig.z, 0), wind: !!layerConfig.wind && !reduce, gust: false, ax: 0, ay: 0, transform: '', visibility: '' });
            }
        });

        measureWorld(ctx);
    }

    function buildTimeline(ctx, reduce, existingTimeline) {
        var gsap = ctx.gsap;
        var config = ctx.config;
        var scenes = config.scenes || [];
        var keyframes = config.keyframes || [];
        var timeline = existingTimeline || gsap.timeline({ paused: true, defaults: { ease: 'none' } });
        if (existingTimeline) { timeline.clear(); }
        var camera = mergeCamera({}, config.camera || {});
        var time = 0;
        var liveSize = isEditMode();

        timeline.addLabel('s0', 0);

        scenes.forEach(function (scene, index) {
            var duration = sceneDuration(scene);
            var target = mergeCamera(camera, scene.camera || {});

            if (!reduce) {
                timeline.to(ctx.view, Object.assign(cameraState(target), {
                    duration: duration,
                    ease: scene.ease || 'power2.inOut',
                }), time);
            }
            camera = target;

            keyframes.forEach(function (keyframe) {
                var records = ctx.layersByKey[keyframe.layer];
                if (keyframe.scene !== index + 1 || !records) {
                    return;
                }

                var position = time + duration * toNumber(keyframe.at, 0);
                var length = Math.max(duration * toNumber(keyframe.duration, 1), 0.001);
                var props = reduce ? calmProps(keyframe.props || {}) : (keyframe.props || {});

                records.forEach(function (record) {
                    var vars = layerVars(props, ctx.size, record.useBlur && !reduce, liveSize);
                    if (Object.keys(vars).length) {
                        timeline.to(record.inner, Object.assign(vars, {
                            duration: length,
                            ease: keyframe.ease || 'power2.inOut',
                        }), position);
                    }

                    if (keyframe.reveal && record.units) {
                        var each = length * 0.55;
                        var reveal = revealVars(record.config.reveal, reduce);
                        timeline.fromTo(record.units, reveal.from, Object.assign(reveal.to, {
                            duration: each,
                            ease: reduce ? 'none' : 'power3.out',
                            stagger: { amount: Math.max(length - each, 0) },
                        }), position);
                    }
                });
            });

            time += duration;
            timeline.addLabel('s' + (index + 1), time);
        });

        if (ctx.hint && scenes.length) {
            if (config.hintMode === 'after_first' && scenes.length > 1) {
                // Like an invitation's "swipe up": hidden on the cover, shown from scene 1 until the last scene.
                var first = sceneDuration(scenes[0]);
                var last = sceneDuration(scenes[scenes.length - 1]);
                gsap.set(ctx.hint, { autoAlpha: 0 });
                timeline.to(ctx.hint, { autoAlpha: 1, duration: first * 0.3 }, first * 0.65);
                timeline.to(ctx.hint, { autoAlpha: 0, duration: last * 0.3 }, time - last);
            } else {
                timeline.to(ctx.hint, {
                    autoAlpha: 0,
                    y: -12,
                    duration: Math.min(sceneDuration(scenes[0]) * 0.25, 0.3),
                }, 0);
            }
        }

        // Keep the timeline exactly as long as the scroll distance so labels line up with snaps.
        if (timeline.duration() < time) {
            timeline.set({}, {}, time);
        }

        return { timeline: timeline, total: time };
    }

    function setupScroll(ctx, built, reduce, editorProgress) {
        var config = ctx.config;
        var ScrollTrigger = window.ScrollTrigger;
        var editorScene = isEditMode() ? toNumber(config.editorScene, -1) : -1;
        var triggers = [];
        var releaseRoot = null;

        if (editorScene >= 0 || !hasScrollTrigger() || built.total <= 0) {
            var label = 's' + Math.min(Math.max(editorScene, 0), (config.scenes || []).length);
            built.timeline.seek(Object.prototype.hasOwnProperty.call(built.timeline.labels, label) ? label : 0);
            setActive(ctx, true);
            return function () { ctx.state.scroll = null; };
        }

        var snapPoints = Object.keys(built.timeline.labels).map(function (name) {
            return built.timeline.labels[name] / built.total;
        }).sort(function (a, b) {
            return a - b;
        });

        var distance = function () {
            var height = window.innerHeight;
            if (!isEditMode() && ScrollTrigger.isTouch === 1) {
                // Browser chrome changes height while scrolling; only a width change resets the mobile reference.
                var frame = document.documentElement;
                var width = frame && frame.clientWidth || window.innerWidth;
                height = frame && frame.clientHeight || height;
                var viewport = ctx.state.scrollViewport;
                if (!viewport || viewport.width !== width) {
                    viewport = ctx.state.scrollViewport = { width: width, height: height };
                }
                height = viewport.height;
            }
            return Math.round(built.total * height);
        };
        var stayFixed = !!config.stayFixed;

        if (stayFixed) {
            // Backdrop mode: the stage stays fixed until the end of the page while the content after the
            // widget scrolls over it. The scene scroll distance is reserved with padding instead of a pin spacer.
            var sizeRoot = function () {
                ctx.root.style.paddingBottom = distance() + 'px';
            };
            sizeRoot();
            ctx.root.classList.add('is-stay-fixed');
            ScrollTrigger.addEventListener('refreshInit', sizeRoot);
            releaseRoot = function () {
                ScrollTrigger.removeEventListener('refreshInit', sizeRoot);
                ctx.root.style.paddingBottom = '';
                ctx.root.classList.remove('is-backdrop', 'is-stay-fixed');
            };

            triggers.push(ScrollTrigger.create({
                trigger: ctx.root,
                start: 'top top',
                end: 'max',
                pin: ctx.stage,
                pinSpacing: false,
                anticipatePin: 1,
            }));
        }

        var glide = config.motion === 'glide' && !reduce;
        var trigger = ScrollTrigger.create({
            trigger: ctx.root,
            start: 'top top',
            end: function () {
                return '+=' + distance();
            },
            pin: stayFixed ? false : ctx.stage,
            pinSpacing: !stayFixed,
            anticipatePin: stayFixed ? 0 : 1,
            // Once the last scene is done the stage drops behind the following content.
            onLeave: function () {
                ctx.root.classList.toggle('is-backdrop', stayFixed);
            },
            onEnterBack: function () {
                ctx.root.classList.remove('is-backdrop');
                // A refresh in backdrop mode can copy its negative stacking into the pin spacer.
                var spacer = ctx.stage.parentNode;
                if (stayFixed && spacer && spacer.classList.contains('pin-spacer') && spacer.style.zIndex === '-1') {
                    spacer.style.zIndex = '';
                }
            },
            // Glide drives the timeline itself (see setupGlide); scrub mode hands it to ScrollTrigger.
            scrub: glide ? false : (reduce || toNumber(config.scrub, 0) <= 0 ? true : toNumber(config.scrub, 1)),
            animation: glide ? null : built.timeline,
            // ScrollTrigger hands snapTo a velocity-projected value; a fast flick would then skip whole
            // scenes. Snapping from the real progress keeps the story one scene per gesture.
            snap: config.snap && !glide ? {
                snapTo: function (value, self) {
                    if (ctx.state.opening) { return self.progress; }
                    return pickSnap(snapPoints, self.progress, self.direction);
                },
                duration: { min: 0.35, max: 1.1 },
                delay: 0.1,
                ease: config.snapEase || 'power2.inOut',
            } : false,
        });

        ctx.state.scroll = { trigger: trigger, points: snapPoints, glide: glide };
        triggers.push(trigger);
        var stopGlide = glide ? setupGlide(ctx, built, trigger, snapPoints, distance, triggers, editorProgress) : null;

        // Idle loops and tilt only run while the stage is on screen (to the end of the page as a backdrop).
        triggers.push(ScrollTrigger.create({
            trigger: ctx.root,
            start: 'top bottom',
            end: stayFixed ? 'max' : 'bottom top',
            onToggle: function (self) {
                setActive(ctx, self.isActive);
            },
        }));
        return function () {
            if (stopGlide) { stopGlide(); }
            if (releaseRoot) { releaseRoot(); }
            triggers.forEach(function (item) { item.kill(true, true); });
            ctx.state.scroll = null;
        };
    }

    // "Meluncur" mode. The page snaps natively to exactly one scene per swipe (CSS scroll snap with
    // snap-stop), while the picture follows the scroll position with exponential smoothing: quick at
    // first, then a long, slow settle. The factor is per 60 fps frame and scaled by real frame time, so
    // 60 Hz and 120 Hz phones glide at the same speed.
    function setupGlide(ctx, built, trigger, snapPoints, distance, triggers, editorProgress) {
        var gsap = ctx.gsap;
        var ScrollTrigger = window.ScrollTrigger;
        var factor = clamp(toNumber(ctx.config.glide, 0.015), 0.003, 0.1);
        var current = typeof editorProgress === 'number' ? editorProgress : trigger.progress;
        var html = document.documentElement;
        var markers = [];

        built.timeline.progress(current, true);

        var tick = function () {
            var target = trigger.progress;
            var gap = target - current;
            if (Math.abs(gap) < 0.00005) {
                if (current !== target) {
                    current = target;
                    built.timeline.progress(current, true);
                }
                return;
            }
            current += gap * (1 - Math.pow(1 - factor, gsap.ticker.deltaRatio(60)));
            built.timeline.progress(current, true);
        };
        gsap.ticker.add(tick);

        // Native snap points: one per scene label plus the widget's end, where the next content starts.
        // The editor keeps free scrolling so the page stays easy to edit.
        var placeMarkers = function () {
            var length = distance();
            markers.forEach(function (marker, index) {
                marker.style.top = index < snapPoints.length ? Math.round(snapPoints[index] * length) + 'px' : '100%';
            });
        };
        if (!isEditMode()) {
            snapPoints.concat([null]).forEach(function () {
                var marker = document.createElement('span');
                marker.className = 'apeiron-depthscape__snap';
                marker.setAttribute('aria-hidden', 'true');
                ctx.root.appendChild(marker);
                markers.push(marker);
            });
            placeMarkers();
            ScrollTrigger.addEventListener('refreshInit', placeMarkers);

            // Snapping is switched on only while the story is on screen; it ends just before the next
            // content so swipes after the last scene scroll the page freely again.
            // The start sits 2px early so the first scene (scroll exactly at the widget top) already snaps.
            triggers.push(ScrollTrigger.create({
                trigger: ctx.root,
                start: 'top top+=2',
                end: 'bottom top+=2',
                onToggle: function (self) {
                    html.classList.toggle('apeiron-depthscape-snap', self.isActive);
                },
            }));
        }

        return function () {
            gsap.ticker.remove(tick);
            ScrollTrigger.removeEventListener('refreshInit', placeMarkers);
            html.classList.remove('apeiron-depthscape-snap');
            markers.forEach(function (marker) {
                if (marker.parentNode) { marker.parentNode.removeChild(marker); }
            });
        };
    }

    function setupTilt(ctx, reduce) {
        var gsap = ctx.gsap;
        var config = ctx.config;
        var strength = toNumber(config.tilt, 0);
        var listeners = [];

        if (reduce || strength <= 0 || (!config.pointer && !config.gyro)) {
            return function () {};
        }

        var turnTo = gsap.quickTo(ctx.tilt, 'ry', { duration: 1.2, ease: 'power3.out' });
        var tiltTo = gsap.quickTo(ctx.tilt, 'rx', { duration: 1.2, ease: 'power3.out' });

        function listen(target, type, handler, options) {
            target.addEventListener(type, handler, options || false);
            listeners.push(function () {
                target.removeEventListener(type, handler, options || false);
            });
        }

        function aim(horizontal, vertical) {
            if (!ctx.active) {
                return;
            }
            turnTo(clamp(horizontal, -1, 1) * strength);
            tiltTo(clamp(vertical, -1, 1) * -strength);
        }

        if (config.pointer && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            listen(window, 'pointermove', function (event) {
                if (event.pointerType && event.pointerType !== 'mouse') {
                    return;
                }
                var rect = ctx.stage.getBoundingClientRect();
                aim(
                    ((event.clientX - rect.left) / Math.max(rect.width, 1)) * 2 - 1,
                    ((event.clientY - rect.top) / Math.max(rect.height, 1)) * 2 - 1
                );
            }, { passive: true });
            listen(ctx.stage, 'pointerleave', function () {
                turnTo(0);
                tiltTo(0);
            });
        }

        if (config.gyro && typeof window.DeviceOrientationEvent !== 'undefined') {
            var startGyro = function () {
                listen(window, 'deviceorientation', function (event) {
                    if (event.gamma === null || event.beta === null) {
                        return;
                    }
                    aim(event.gamma / 25, (event.beta - 45) / 25);
                }, { passive: true });
            };

            if (typeof window.DeviceOrientationEvent.requestPermission === 'function') {
                // iOS only grants sensor access inside a user gesture: the Cover opening or a tap on the stage.
                var asked = false;
                var askPermission = function () {
                    if (asked) {
                        return;
                    }
                    asked = true;
                    window.DeviceOrientationEvent.requestPermission().then(function (permission) {
                        if (permission === 'granted') {
                            startGyro();
                        }
                    }, function () {
                        asked = false;
                    });
                };
                listen(document, 'apeiron:cover:opening', askPermission);
                listen(ctx.stage, 'pointerup', askPermission);
            } else {
                startGyro();
            }
        }

        return function () {
            turnTo.tween.kill();
            tiltTo.tween.kill();
            listeners.forEach(function (remove) {
                remove();
            });
        };
    }

    function randomBetween(min, max) {
        return min + Math.random() * (max - min);
    }

    // Pollen/petals spread through depth: as world layers they inherit the camera's parallax for free,
    // which is what makes a slow dolly read as real 3D space.
    function createParticles(ctx, reduce, extraCount) {
        var options = ctx.config.particles || {};
        var count = reduce ? 0 : Math.round(clamp(toNumber(options.count, 0), 0, 60));
        if (!reduce && typeof extraCount === 'number') { count = extraCount; }
        var created = [];

        for (var i = 0; i < count; i++) {
            var depth = randomBetween(-1400, 320);
            var near = depth > 150;
            var size = toNumber(options.size, 6) * randomBetween(0.55, 1.35) * (near ? 1.7 : 1);
            var el = document.createElement('div');
            var inner = document.createElement('div');
            var dot = document.createElement('div');

            el.className = 'apeiron-depthscape__layer apeiron-depthscape__particle is-style-' + (options.style || 'pollen');
            el.setAttribute('aria-hidden', 'true');
            el.style.setProperty('--ds-left', randomBetween(-8, 108).toFixed(2) + '%');
            el.style.setProperty('--ds-top', randomBetween(4, 96).toFixed(2) + '%');
            el.style.setProperty('--ds-size', size.toFixed(1) + 'px');
            el.style.setProperty('--ds-particle', options.color || '#fff3b0');
            inner.className = 'apeiron-depthscape__inner';
            dot.className = 'apeiron-depthscape__particle-dot';
            inner.appendChild(dot);
            el.appendChild(inner);

            // Keep DOM order = depth order so nearer motes paint over farther layers.
            var index = ctx.world.length;
            for (var j = 0; j < ctx.world.length; j++) {
                if (ctx.world[j].depth > depth) {
                    index = j;
                    break;
                }
            }
            ctx.camera.insertBefore(el, index < ctx.world.length ? ctx.world[index].el : null);
            ctx.world.splice(index, 0, { el: el, depth: depth, wind: false, gust: true, ax: 0, ay: 0, transform: '', visibility: '' });
            created.push(el);

            el._apeironParticleRatio = size / toNumber(options.size, 6);
            el._apeironParticleStyle = options.style || 'pollen';
            el._apeironParticleLoop = startIdleLoop(ctx, inner, {
                from: { x: 0, y: 0, rotation: 0 },
                to: {
                    x: randomBetween(-34, 34),
                    y: -randomBetween(30, 110),
                    rotation: options.style === 'petal' ? randomBetween(-160, 160) : 0,
                },
            }, randomBetween(5, 11));
            dot.style.setProperty('--ds-twinkle', randomBetween(1.4, 3.2).toFixed(2) + 's');
        }

        return created;
    }

    // Handheld camera: three slow, unrelated sine loops so the drift never visibly repeats.
    function setupBreath(ctx, reduce) {
        var amount = reduce ? 0 : clamp(toNumber(ctx.config.breath, 0), 0, 10);
        if (amount <= 0) {
            return;
        }

        // Pan and roll only: changing z would rescale every image each frame and force re-rasterising.
        var loops = [
            { x: amount * 0.25, duration: 4.7 },
            { y: amount * 0.2, duration: 5.9 },
            { rz: amount * 0.08, duration: 7.3 },
        ];
        loops.forEach(function (loop) {
            var key = Object.keys(loop).filter(function (name) { return name !== 'duration'; })[0];
            var from = {};
            from[key] = -loop[key];
            ctx.idleTweens.push(ctx.gsap.fromTo(ctx.breath, from, Object.assign({}, loop, {
                ease: 'sine.inOut',
                repeat: -1,
                yoyo: true,
                paused: true,
            })).progress(Math.random()));
        });
    }

    function setup(state, reduce) {
        var root = state.root;
        var stage = root.querySelector(SELECTOR.stage);
        var camera = stage ? stage.querySelector(SELECTOR.camera) : null;
        if (!stage || !camera) {
            return function () {};
        }

        var gsap = getGsap();
        state.revealContents = new Set();
        state.editingContentId = root.apeironEditingContentId || '';
        var ctx = {
            gsap: gsap,
            state: state,
            root: root,
            config: state.config,
            stage: stage,
            camera: camera,
            screen: stage.querySelector(SELECTOR.screen),
            hint: stage.querySelector(SELECTOR.hint) && !stage.querySelector(SELECTOR.hint).hidden ? stage.querySelector(SELECTOR.hint) : null,
            size: measure(stage),
            view: cameraState(state.config.camera),
            tilt: { rx: 0, ry: 0 },
            breath: { x: 0, y: 0, z: 0, rz: 0 },
            wind: { value: 0, velocity: 0 },
            windStrength: reduce ? 0 : clamp(toNumber(state.config.wind, 0), 0, 20),
            perspective: Math.max(toNumber(state.config.perspective, 1000), 200),
            compensate: state.config.compensate !== false,
            world: [],
            lastSignature: '',
            idleLoops: [],
            cleanups: [],
            layersByKey: {},
            idleTweens: [],
            videos: [],
            reduce: reduce,
            active: false,
        };

        state.size = ctx.size;
        state.scroll = null;
        prepareLayers(ctx, reduce);
        var particles = createParticles(ctx, reduce);
        measureWorld(ctx);
        setupBreath(ctx, reduce);
        renderWorld(ctx);
        var built = buildTimeline(ctx, reduce);
        var stopScroll = setupScroll(ctx, built, reduce);
        var liveTimelineContext = null;
        var stopVideos = setupVideos(ctx);

        var lineResizeTimer = null;
        if (isEditMode()) {
            state.editorPreview = {
                timeline: built.timeline,
                render: function () {
                    ctx.perspective = Math.max(toNumber(ctx.config.perspective, 1000), 200);
                    ctx.compensate = ctx.config.compensate !== false;
                    ctx.windStrength = reduce ? 0 : clamp(toNumber(ctx.config.wind, 0), 0, 20);
                    var reordered = false;
                    ctx.world = ctx.world.filter(function (item) {
                        if (!ctx.config.layers[parseInt(item.el.getAttribute('data-ds-index'), 10)] || item.el.parentNode === ctx.camera) { return true; }
                        item.el.style.transform = '';
                        item.el.style.visibility = '';
                        reordered = true;
                        return false;
                    });
                    Array.from(ctx.camera.children).forEach(function (el) {
                        var layer = ctx.config.layers[parseInt(el.getAttribute('data-ds-index'), 10)];
                        if (layer && !ctx.world.some(function (item) { return item.el === el; })) { ctx.world.push({ el: el, depth: layer.z, wind: layer.wind && !reduce, gust: false, ax: 0, ay: 0, transform: '', visibility: '' }); reordered = true; }
                    });
                    if (reordered) { var order = Array.from(ctx.camera.children); ctx.world.sort(function (a, b) { return order.indexOf(a.el) - order.indexOf(b.el); }); }
                    ctx.world.forEach(function (item) {
                        var layer = ctx.config.layers[parseInt(item.el.getAttribute('data-ds-index'), 10)];
                        if (layer) { item.depth = layer.z; item.wind = layer.wind && !reduce; }
                    });
                    var flags = collectFlags(ctx.config);
                    Object.keys(ctx.layersByKey).forEach(function (key) {
                        ctx.layersByKey[key].forEach(function (record) {
                            record.useBlur = record.useBlur || !!flags.blur[key] || toNumber(record.config.start && record.config.start.blur, 0) > 0;
                        });
                    });
                    measureWorld(ctx);
                    ctx.lastSignature = '';
                    renderWorld(ctx);
                },
                idle: function () {
                    Object.keys(ctx.layersByKey).forEach(function (key) {
                        ctx.layersByKey[key].forEach(function (record) {
                            var range = !reduce && ctx.config.idle ? idleRange(record.config.idle) : null;
                            var animation = record.idleLoop;
                            if (!range) {
                                if (animation) { animation.cancel(); ctx.idleLoops = ctx.idleLoops.filter(function (item) { return item !== animation; }); record.idleLoop = null; }
                                return;
                            }
                            if (animation && animation.effect) {
                                var timing = animation.effect.getTiming();
                                var time = animation.currentTime;
                                var duration = Math.max(toNumber(record.config.idle.speed, 6), 0.5) * 500;
                                animation.effect.setKeyframes([{ transform: idleTransform(range.from) }, { transform: idleTransform(range.to) }]);
                                animation.effect.updateTiming({ duration: duration });
                                if (time !== null) { animation.currentTime = (time - timing.delay) / timing.duration * duration + timing.delay; }
                            } else {
                                var idle = record.inner.querySelector(SELECTOR.idle);
                                if (idle) {
                                    record.idleLoop = startIdleLoop(ctx, idle, range, Math.max(toNumber(record.config.idle.speed, 6), 0.5) / 2);
                                    if (record.idleLoop && !ctx.active) { record.idleLoop.pause(); }
                                }
                            }
                        });
                    });
                },
                particles: function () {
                    var wanted = reduce ? 0 : ctx.config.particles.count;
                    particles.splice(wanted).forEach(function (el) {
                        var animation = el._apeironParticleLoop;
                        if (animation) { animation.cancel(); ctx.idleLoops = ctx.idleLoops.filter(function (item) { return item !== animation; }); }
                        ctx.world = ctx.world.filter(function (item) { return item.el !== el; });
                        el.remove();
                    });
                    if (wanted > particles.length) { particles.push.apply(particles, createParticles(ctx, reduce, wanted - particles.length)); }
                    particles.forEach(function (el) {
                        var options = ctx.config.particles;
                        el.style.setProperty('--ds-size', (el._apeironParticleRatio * options.size).toFixed(1) + 'px');
                        el.style.setProperty('--ds-particle', options.color);
                        if (el._apeironParticleStyle !== options.style) {
                            el.classList.remove('is-style-pollen', 'is-style-petal', 'is-style-bokeh');
                            el.classList.add('is-style-' + options.style);
                            el._apeironParticleStyle = options.style;
                            var animation = el._apeironParticleLoop;
                            if (animation && animation.effect) {
                                var frames = animation.effect.getKeyframes();
                                frames.forEach(function (frame, index) { frame.transform = frame.transform.replace(/rotate\([^)]*\)/, 'rotate(' + (index && options.style === 'petal' ? randomBetween(-160, 160) : 0) + 'deg)'); });
                                animation.effect.setKeyframes(frames);
                            }
                        }
                        if (el._apeironParticleLoop && !ctx.active) { el._apeironParticleLoop.pause(); }
                    });
                    state.editorPreview.render();
                },
                motion: function (keys) {
                    if (keys.some(function (key) { return ['pointer_tilt', 'gyro_tilt', 'tilt_strength'].indexOf(key) >= 0; })) {
                        stopTilt();
                        Object.assign(ctx.tilt, { rx: 0, ry: 0 });
                        stopTilt = setupTilt(ctx, reduce);
                    }
                    if (keys.indexOf('camera_breath') >= 0) {
                        var phases = ctx.idleTweens.map(function (tween) { return tween.totalTime(); });
                        ctx.idleTweens.forEach(function (tween) { tween.kill(); });
                        ctx.idleTweens = [];
                        Object.assign(ctx.breath, { x: 0, y: 0, z: 0, rz: 0 });
                        setupBreath(ctx, reduce);
                        ctx.idleTweens.forEach(function (tween, index) { if (phases[index] !== undefined) { tween.totalTime(phases[index]); } if (ctx.active) { tween.play(); } });
                    }
                    state.editorPreview.render();
                },
                scrub: function () {
                    if (state.scroll && !state.scroll.glide) {
                        var value = reduce || toNumber(ctx.config.scrub, 0) <= 0 ? true : ctx.config.scrub;
                        state.scroll.trigger.vars.scrub = value;
                        state.scroll.trigger.scrubDuration(value);
                    }
                },
                content: function (layer, updateContent) {
                    var records = (ctx.layersByKey[layer.getAttribute('data-ds-layer')] || []).filter(function (record) {
                        return record.inner.parentNode === layer;
                    });
                    records.forEach(function (record) { if (record.splitContext) { record.splitContext.revert(); } });
                    updateContent();
                    records.forEach(function (record) {
                        if (record.splitContext) {
                            record.splitContext.add(function () {
                                record.units = splitLayer(layer, record.config.split, record.config.reveal, reduce);
                            });
                        }
                    });
                    return records.some(function (record) { return !!record.splitContext; });
                },
                timelineUpdate: function (preserve, keepScroll, scrollOnly) {
                    var progress = built.timeline.progress();
                    var active = state.scroll && state.scroll.trigger.isActive;
                    var existingTimeline = keepScroll ? built.timeline : null;
                    if (!keepScroll) { stopScroll(); }
                    var hint = stage.querySelector(SELECTOR.hint);
                    ctx.hint = hint && !hint.hidden ? hint : null;
                    if (ctx.hint) { gsap.set(ctx.hint, { autoAlpha: ctx.config.hintMode === 'after_first' ? 0 : 1, y: 0 }); }
                    if (scrollOnly) {
                        built.timeline.progress(0, true);
                        Object.assign(ctx.view, cameraState(ctx.config.camera));
                        Object.keys(ctx.layersByKey).forEach(function (key) {
                            ctx.layersByKey[key].forEach(function (record) {
                                gsap.set(record.inner, layerVars(reduce ? calmProps(record.config.start) : record.config.start, ctx.size, record.useBlur && !reduce));
                            });
                        });
                        built.timeline.invalidate();
                        stopScroll = setupScroll(ctx, built, reduce, preserve ? progress : undefined);
                    } else {
                        // The replacement timeline resets poses itself; keep split-text styles until then.
                        if (liveTimelineContext) { liveTimelineContext.kill(false); }
                        else if (keepScroll) { built.timeline.getChildren().forEach(function (tween) { tween.kill(); }); }
                        else { built.timeline.kill(); }
                        liveTimelineContext = gsap.context(function () {
                            Object.assign(ctx.view, cameraState(ctx.config.camera));
                            Object.keys(ctx.layersByKey).forEach(function (key) {
                                ctx.layersByKey[key].forEach(function (record) {
                                    gsap.set(record.inner, layerVars(reduce ? calmProps(record.config.start) : record.config.start, ctx.size, record.useBlur && !reduce));
                                });
                            });
                            built = buildTimeline(ctx, reduce, existingTimeline);
                            if (!keepScroll) { stopScroll = setupScroll(ctx, built, reduce, preserve ? progress : undefined); }
                        }, root);
                    }
                    state.editorPreview.timeline = built.timeline;
                    if (!keepScroll && hasScrollTrigger()) { window.ScrollTrigger.sort(); window.ScrollTrigger.refresh(); }
                    if (ctx.config.editorScene >= 0) {
                        built.timeline.seek('s' + Math.min(ctx.config.editorScene, (ctx.config.scenes || []).length), true);
                    } else if (preserve) {
                        if (active && state.scroll && !state.scroll.glide) { var trigger = state.scroll.trigger; trigger.scroll(trigger.start + (trigger.end - trigger.start) * progress); }
                        built.timeline.progress(progress, true);
                    }
                    state.editorPreview.render();
                },
            };
            var needsLineReflow = Object.keys(ctx.layersByKey).some(function (key) {
                return ctx.layersByKey[key].some(function (record) {
                    return record.units && record.config.split === 'lines';
                });
            });

            state.resize = function (size, reflowLines, resetPoses) {
                if (resetPoses || size.width !== ctx.size.width || size.height !== ctx.size.height) {
                    var progress = built.timeline.progress();
                    built.timeline.progress(0, true);
                    ctx.size.width = size.width;
                    ctx.size.height = size.height;
                    Object.assign(ctx.view, cameraState(ctx.config.camera));
                    Object.keys(ctx.layersByKey).forEach(function (key) {
                        ctx.layersByKey[key].forEach(function (record) {
                            var start = record.config.start || {};
                            gsap.set(record.inner, layerVars(reduce ? calmProps(start) : start, ctx.size, record.useBlur && !reduce));
                        });
                    });
                    built.timeline.invalidate().progress(progress, true);
                }
                measureWorld(ctx);
                ctx.lastSignature = '';
                renderWorld(ctx);

                // Line masks need new wrapping, but only after the resize gesture settles.
                if (needsLineReflow && reflowLines !== false) {
                    clearTimeout(lineResizeTimer);
                    lineResizeTimer = setTimeout(function () {
                        if (root.isConnected) {
                            rebuild(root);
                        }
                    }, 120);
                }
            };
        }

        var stopTilt = setupTilt(ctx, reduce);
        var stopNextLinks = setupNextLinks(ctx);
        var tick = function () {
            if (!ctx.active) {
                return;
            }
            // Scroll speed becomes "wind": scrolling down pushes foliage one way, then it springs back.
            var trigger = state.scroll && state.scroll.trigger;
            var target = ctx.windStrength > 0 && trigger && trigger.isActive ? clamp(-trigger.getVelocity() / 2200, -1, 1) : 0;
            springStep(ctx.wind, target, gsap.ticker.deltaRatio(60));
            renderWorld(ctx);
            if (ctx.videos.length) { syncVideos(ctx); }
        };
        gsap.ticker.add(tick);
        renderWorld(ctx);

        return function () {
            gsap.ticker.remove(tick);
            clearTimeout(lineResizeTimer);
            if (state.opening) { state.opening.stop(); }
            stopScroll();
            if (liveTimelineContext) { liveTimelineContext.revert(); }
            ctx.idleTweens.forEach(function (tween) { tween.kill(); });
            state.editorPreview = null;
            state.resize = null;
            state.scroll = null;
            stopTilt();
            stopNextLinks();
            stopVideos();
            stopIdleLoops(ctx);
            ctx.cleanups.forEach(function (cleanup) {
                cleanup();
            });
            // Projection writes inline styles directly, outside GSAP's revert bookkeeping.
            ctx.world.forEach(function (item) {
                item.el.style.transform = '';
                item.el.style.visibility = '';
            });
            particles.forEach(function (el) {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            });
        };
    }

    function playOpening(state, target) {
        var position = { y: window.scrollY };
        var scrollRoot = document.scrollingElement || document.documentElement;
        var previousBehavior = scrollRoot.style.scrollBehavior;
        var tween = null;
        var opening = { stop: stop };

        function cleanup() {
            ['wheel', 'touchstart', 'pointerdown', 'keydown'].forEach(function (type) {
                window.removeEventListener(type, stop);
            });
            if (scrollRoot.style.scrollBehavior === 'auto') {
                scrollRoot.style.scrollBehavior = previousBehavior;
            }
            if (state.opening === opening) { state.opening = null; }
        }

        function stop(event) {
            if (event && event.type === 'keydown') {
                var editing = event.target && (event.target.isContentEditable || (event.target.closest && event.target.closest('input, textarea, select')));
                if (editing || ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'PageUp', 'PageDown', 'Home', 'End', ' ', 'Spacebar', 'Escape'].indexOf(event.key) === -1) {
                    return;
                }
            }
            if (tween) { tween.kill(); }
            cleanup();
        }

        state.opening = opening;
        scrollRoot.style.scrollBehavior = 'auto';
        var snapTween = state.scroll.trigger.getTween(true);
        if (snapTween) { snapTween.kill(); }
        ['wheel', 'touchstart', 'pointerdown', 'keydown'].forEach(function (type) {
            window.addEventListener(type, stop, { passive: true });
        });
        tween = getGsap().to(position, {
            y: Math.round(target),
            duration: OPENING_DURATION,
            ease: 'power2.inOut',
            onUpdate: function () {
                window.scrollTo({ top: position.y, behavior: 'auto' });
            },
            onComplete: cleanup,
            onInterrupt: cleanup,
        });
    }

    // Opening uses a fixed, slow glide; later next-scene links retain native smooth scrolling.
    function glideToNextScene(state) {
        var scroll = state.scroll;
        if (!scroll) {
            return false;
        }

        var trigger = scroll.trigger;
        var next = null;
        for (var i = 0; i < scroll.points.length; i++) {
            if (scroll.points[i] > trigger.progress + 0.001) {
                next = scroll.points[i];
                break;
            }
        }
        if (next === null) {
            return false;
        }

        var target = trigger.start + (trigger.end - trigger.start) * next;
        var reduce = window.matchMedia && window.matchMedia(MEDIA.reduce).matches;
        if (state.opening) { state.opening.stop(); }
        // In glide mode the picture already eases on its own, so the page only needs to land on the scene.
        if (!reduce && next === scroll.points[1] && !scroll.glide) {
            playOpening(state, target);
        } else {
            window.scrollTo({ top: Math.round(target), behavior: reduce ? 'auto' : 'smooth' });
        }

        return true;
    }

    // Mirrors an "Open invitation" button: once the Cover opens, glide through the first scene.
    function playFirstScene(state) {
        var scroll = state.scroll;
        if (!state.config.coverPlay || !scroll) {
            return;
        }

        var trigger = scroll.trigger;
        var nearTop = trigger.start - window.scrollY < window.innerHeight * 0.6;
        if (trigger.progress <= 0.02 && nearTop) {
            glideToNextScene(state);
        }
    }

    // Pressing "#ds-next" on the cover acts like opening the Sampul widget: other widgets (Tombol Audio,
    // gyro permission) listen for these events and need the click's user activation, so dispatch synchronously.
    function announceCoverOpening(ctx) {
        var scroll = ctx.state.scroll;
        if (ctx.coverAnnounced || !scroll || scroll.trigger.progress > 0.02 || typeof window.CustomEvent !== 'function') {
            return;
        }
        ctx.coverAnnounced = true;

        var openedEventName = 'apeiron:depthscape:opened';
        document.dispatchEvent(new window.CustomEvent('apeiron:cover:opening', {
            detail: { cover: ctx.root, id: ctx.root.id || '', openedEventName: openedEventName },
        }));
        setTimeout(function () {
            document.dispatchEvent(new window.CustomEvent(openedEventName, { detail: { cover: ctx.root, id: ctx.root.id || '' } }));
        }, 900);
    }

    // Any link to "#ds-next" inside the stage (e.g. an "Open invitation" button layer) advances one scene.
    function setupNextLinks(ctx) {
        var onClick = function (event) {
            var link = event.target && event.target.closest ? event.target.closest('a[href="#ds-next"]') : null;
            if (!link || !ctx.root.contains(link)) {
                return;
            }
            event.preventDefault();
            announceCoverOpening(ctx);
            glideToNextScene(ctx.state);
        };

        ctx.root.addEventListener('click', onClick);

        return function () {
            ctx.root.removeEventListener('click', onClick);
        };
    }

    function refreshMotionPreference() {
        liveRoots.slice().forEach(function (root) {
            if (root.isConnected) { rebuild(root); } else { destroy(root); }
        });
    }

    function watchLifecycle() {
        if (!motionQuery && window.matchMedia) { motionQuery = window.matchMedia(MEDIA.reduce); }
        if (motionQuery && !motionWatchBound) {
            if (typeof motionQuery.addEventListener === 'function') {
                motionQuery.addEventListener('change', refreshMotionPreference);
                motionWatchBound = true;
            } else if (typeof motionQuery.addListener === 'function') {
                motionQuery.addListener(refreshMotionPreference);
                motionWatchBound = true;
            }
        }
        if (!detachObserver && window.MutationObserver && document.body) {
            detachObserver = new window.MutationObserver(function (records) {
                if (records.some(function (record) { return record.removedNodes.length > 0; })) { sweepDetached(); }
            });
            detachObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    function stopLifecycleWatchers() {
        if (liveRoots.length) { return; }
        if (motionWatchBound) {
            if (typeof motionQuery.removeEventListener === 'function') {
                motionQuery.removeEventListener('change', refreshMotionPreference);
            } else {
                motionQuery.removeListener(refreshMotionPreference);
            }
            motionWatchBound = false;
        }
        if (detachObserver) { detachObserver.disconnect(); detachObserver = null; }
        if (refreshListenerAdded && hasScrollTrigger()) {
            window.ScrollTrigger.removeEventListener('refresh', scheduleSizeCheck);
            refreshListenerAdded = false;
        }
        clearTimeout(resizeTimer);
        resizeTimer = null;
    }

    function build(state) {
        var gsap = getGsap();

        state.mm = gsap.matchMedia();
        state.mm.add({ all: true }, function () {
            return setup(state, !!(motionQuery && motionQuery.matches));
        }, state.root);

        state.root.classList.remove('is-booting');
        state.root.classList.add('is-ready');

        if (hasScrollTrigger()) {
            window.ScrollTrigger.sort();
            window.ScrollTrigger.refresh();
        }
    }

    function destroy(root) {
        var state = states.get(root);
        if (state && state.mm) {
            state.mm.revert();
        }

        states.delete(root);
        liveRoots = liveRoots.filter(function (item) {
            return item !== root;
        });
        root.classList.remove('is-ready');
        stopLifecycleWatchers();
    }

    function rebuild(root, updateContent) {
        var state = states.get(root);
        if (!state || !state.mm) {
            return;
        }

        state.mm.revert();
        if (updateContent) { updateContent(); }
        build(state);
    }

    function contentModeNeedsRebuild(state, nextId) {
        return !!(state.revealContents && (state.revealContents.has(state.editingContentId) || state.revealContents.has(nextId)));
    }

    // Motion is measured in % of the stage, so a real size change (rotation, desktop resize) needs fresh pixels.
    function checkSizes(force) {
        liveRoots.slice().forEach(function (root) {
            var state = states.get(root);
            var stage = root.querySelector(SELECTOR.stage);
            if (!state || !state.size || !stage) {
                return;
            }

            var size = measure(stage);
            if ((force === true && state.resize) || Math.abs(size.width - state.size.width) > 2 || Math.abs(size.height - state.size.height) > state.size.height * 0.2) {
                if (state.resize) {
                    state.resize(size);
                } else {
                    rebuild(root);
                }
            }
        });
    }

    function scheduleSizeCheck() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(checkSizes, 120);
    }

    // In the Elementor editor, style controls (stage width/height…) update CSS without re-rendering the
    // widget, but a pinned stage carries inline px sizes from ScrollTrigger. Re-measure after such edits.
    var editorWatchBound = false;
    var editorRefreshTimer = null;

    function editorImageUpdate(layer, attributes) {
        if (!attributes || !layer || !layer.classList.contains('is-type-image')) { return false; }
        var idle = layer.querySelector(SELECTOR.idle);
        if (!idle) { return false; }
        if (attributes.src) {
            try {
                if (['http:', 'https:'].indexOf(new window.URL(attributes.src, layer.ownerDocument.baseURI).protocol) < 0) { return false; }
            } catch (error) { return false; }
        }
        return function () {
            var image = idle.querySelector('.apeiron-depthscape__img');
            var empty = idle.querySelector('.apeiron-depthscape__empty');
            if (!attributes.src) {
                if (image) { image.remove(); }
                if (!empty) { empty = layer.ownerDocument.createElement('div'); empty.className = 'apeiron-depthscape__empty'; idle.appendChild(empty); }
                empty.textContent = layer.getAttribute('data-ds-layer');
                return;
            }
            if (empty) { empty.remove(); }
            if (!image) { image = layer.ownerDocument.createElement('img'); image.className = 'apeiron-depthscape__img'; idle.appendChild(image); }
            ['width', 'height', 'alt', 'loading', 'decoding', 'draggable', 'sizes', 'srcset', 'src'].forEach(function (name) {
                if (Object.prototype.hasOwnProperty.call(attributes, name)) { image.setAttribute(name, attributes[name]); }
                else { image.removeAttribute(name); }
            });
        };
    }

    function editorTextUpdate(layer, values, settings) {
        var parts = { eyebrow: 'eyebrow', title: 'title', subtitle: 'subtitle', button_text: 'button' };
        var keys = Object.keys(values).filter(function (key) { return Object.prototype.hasOwnProperty.call(parts, key); });
        if (!keys.length) { return null; }
        if (!layer || !layer.classList.contains('is-type-text')) { return false; }
        var link = settings && settings.get('button_link');
        var dynamic = settings && settings.get('__dynamic__');
        if (dynamic && dynamic.toJSON) { dynamic = dynamic.toJSON(); }
        if (keys.indexOf('button_text') >= 0 && !layer.querySelector('.apeiron-depthscape__button')
            && ((link && link.url) || (dynamic && dynamic.button_link))) { return false; }
        return function () {
            var doc = layer.ownerDocument;
            var text = layer.querySelector('.apeiron-depthscape__text');
            var idle = layer.querySelector(SELECTOR.idle);
            var order = ['eyebrow', 'title', 'subtitle', 'body', 'button'];
            keys.forEach(function (key) {
                var value = typeof values[key] === 'string' || typeof values[key] === 'number' ? String(values[key]).trim() : '';
                var name = parts[key];
                var node = text && text.querySelector('.apeiron-depthscape__' + name);
                if (!value) { if (node) { node.remove(); } return; }
                if (!text) {
                    text = doc.createElement('div');
                    text.className = 'apeiron-depthscape__text';
                    idle.appendChild(text);
                }
                if (!node) {
                    node = doc.createElement(name === 'button' ? 'a' : 'span');
                    node.className = 'apeiron-depthscape__' + name;
                    if (name === 'button') { node.setAttribute('role', 'button'); }
                    else { node.setAttribute('data-ds-split', ''); }
                    var next = Array.from(text.children).find(function (child) {
                        return order.indexOf(child.className.replace('apeiron-depthscape__', '')) > order.indexOf(name);
                    });
                    text.insertBefore(node, next || null);
                }
                node.textContent = '';
                (name === 'title' ? value.split(/\r\n|\r|\n/) : [value]).forEach(function (line, index) {
                    if (index) { node.appendChild(doc.createElement('br')); }
                    node.appendChild(doc.createTextNode(line));
                });
            });
            var empty = idle.querySelector('.apeiron-depthscape__empty');
            if (text && text.children.length) { if (empty) { empty.remove(); } }
            else {
                if (text) { text.remove(); }
                if (!empty) { empty = doc.createElement('div'); empty.className = 'apeiron-depthscape__empty'; idle.appendChild(empty); }
                empty.textContent = layer.getAttribute('data-ds-layer');
            }
        };
    }

    function updateEditorPreview(root, update) {
        var state = states.get(root);
        if (!state || !state.editorPreview || !update || !update.values) { return false; }
        var config = state.config;
        var target = config;
        var pattern = /^(perspective|depth_compensation|editor_preview|motion_style|glide_smoothing|smoothness|snap|snap_ease|stay_fixed|cover_autoplay|idle_enabled|wind_strength|camera_breath|pointer_tilt|gyro_tilt|tilt_strength|particle_(count|style|color|size)|show_hint|hint_(mode|text)|cam_start_(x|y|z|rx|ry|rz))$/;
        if (update.scope === 'layer' || update.scope === 'scene') {
            if (!Number.isInteger(update.index) || update.index < 0) { return false; }
            target = (update.scope === 'layer' ? config.layers : config.scenes)[update.index];
            pattern = update.scope === 'layer' ? /^(image|depth|anchor|layer_key|layer_space|fit|video_loop|eyebrow|title|subtitle|button_text|idle_(type|amount|speed)|layer_wind|reveal_(split|style)|start_(x|y|scale|rotate|opacity|blur))$/ : /^(scene_(label|length|ease)|cam_(x|y|z|rx|ry|rz))$/;
        } else if (update.scope === 'keyframe') {
            if (!Array.isArray(update.rows)) { return false; }
            pattern = /^kf_(layer|scene|at|duration|ease|x|y|scale|rotate|opacity|blur|reveal)$/;
        } else if (update.scope !== 'root') { return false; }
        var keys = Object.keys(update.values);
        if (!target || !keys.length || !keys.every(function (key) { return pattern.test(key); })) { return false; }
        var layer = update.scope === 'layer' ? root.querySelector('[data-ds-index="' + update.index + '"]') : null;
        if (update.scope === 'layer' && !layer) { return false; }
        if (keys.indexOf('anchor') >= 0 && ['center', 'top', 'bottom'].indexOf(update.values.anchor) < 0) { return false; }
        var hint = root.querySelector(SELECTOR.hint);
        if ((keys.indexOf('show_hint') >= 0 || keys.indexOf('hint_text') >= 0) && !hint) { return false; }
        var updateText = editorTextUpdate(layer, update.values, update.settings);
        if (updateText === false) { return false; }
        var updateImage = keys.indexOf('image') >= 0 ? editorImageUpdate(layer, update.image) : null;
        if (updateImage === false) { return false; }

        var startLimits = { x: [0, -500, 500], y: [0, -500, 500], scale: [1, 0, 20], rotate: [0, -720, 720], opacity: [1, 0, 1], blur: [0, 0, 40] };
        var cameraLimits = { x: [0, -200, 200], y: [0, -200, 200], z: [0, -3000, 2000], rx: [0, -60, 60], ry: [0, -60, 60], rz: [0, -180, 180] };
        var numeric = { smoothness: ['scrub', 1, 0, 3], glide_smoothing: ['glide', 0.015, 0.003, 0.1], wind_strength: ['wind', 7, 0, 20], camera_breath: ['breath', 0, 0, 10], tilt_strength: ['tilt', 4, 0, 20] };
        var switches = { snap: 'snap', stay_fixed: 'stayFixed', cover_autoplay: 'coverPlay', idle_enabled: 'idle', pointer_tilt: 'pointer', gyro_tilt: 'gyro' };
        var rebuildTimeline = false;
        var scrollChanged = false;
        var fullRebuild = false;
        var idleChanged = false;
        var particlesChanged = false;
        var motionChanged = false;
        var scrubChanged = false;
        var resize = false;
        function rawNumber(value) {
            if (value && typeof value === 'object') { value = value.size; }
            var parsed = typeof value === 'number' || (typeof value === 'string' && value.trim() !== '') ? Number(value) : NaN;
            return Number.isFinite(parsed) ? parsed : null;
        }
        function number(value, limits) {
            var parsed = rawNumber(value);
            return Math.round(clamp(parsed === null ? limits[0] : parsed, limits[1], limits[2]) * 10000) / 10000;
        }
        function option(value, allowed, fallback) { return allowed.indexOf(value) >= 0 ? value : fallback; }
        function ease(value, fallback) { return option(value, ['none', 'sine.inOut', 'power1.inOut', 'power2.inOut', 'power3.inOut', 'power4.inOut', 'expo.inOut', 'circ.inOut', 'power2.out', 'power3.out', 'expo.out', 'power2.in', 'back.out(1.4)', 'back.inOut(1.2)'], fallback); }
        function layerKey(value, index) {
            var key = typeof value === 'string' || typeof value === 'number' ? String(value).trim().replace(/ /g, '-').toLowerCase().replace(/[^a-z0-9_-]/g, '') : '';
            return key || 'layer-' + (index + 1);
        }
        if (update.scope === 'keyframe') {
            // Rebuild the filtered array: empty keyframe rows do not have a runtime index.
            var revealed = Object.keys(collectFlags(config).reveal).sort().join('|');
            config.keyframes = update.rows.map(function (row, index) {
                var props = {};
                Object.keys(startLimits).forEach(function (prop) {
                    if (rawNumber(row['kf_' + prop]) !== null) { props[prop] = number(row['kf_' + prop], startLimits[prop]); }
                });
                var reveal = row.kf_reveal === 'yes';
                if (!Object.keys(props).length && !reveal) { return null; }
                var at = number(row.kf_at, [0, 0, 100]) / 100;
                var duration = Math.min(number(row.kf_duration, [100, 1, 100]) / 100, 1 - at);
                return { layer: layerKey(row.kf_layer, index), scene: Math.trunc(number(row.kf_scene, [1, 1, 99])), at: at, duration: duration > 0 ? duration : 0.01, ease: ease(row.kf_ease, 'none'), props: props, reveal: reveal };
            }).filter(Boolean);
            fullRebuild = revealed !== Object.keys(collectFlags(config).reveal).sort().join('|');
            rebuildTimeline = true;
            keys = [];
        }
        keys.forEach(function (key) {
            if (key === 'image') { return; }
            var value = update.values[key];
            if (/^(eyebrow|title|subtitle|button_text)$/.test(key)) {
                resize = true;
            } else if (key === 'hint_text') {
                var label = hint.querySelector('.apeiron-depthscape__hint-text');
                var text = typeof value === 'string' || typeof value === 'number' ? String(value) : '';
                if (text && !label) { label = root.ownerDocument.createElement('span'); label.className = 'apeiron-depthscape__hint-text'; hint.appendChild(label); }
                if (label) { if (text) { label.textContent = text; } else { label.remove(); } }
            } else if (key === 'depth') {
                target.z = target.space === 'screen' ? 0 : number(value, [0, -3000, 900]);
            } else if (key === 'anchor') {
                layer.classList.remove('is-anchor-center', 'is-anchor-top', 'is-anchor-bottom');
                layer.classList.add('is-anchor-' + value);
                resize = true;
            } else if (key === 'layer_key') {
                target.key = layerKey(value, update.index);
                layer.setAttribute('data-ds-layer', target.key);
                fullRebuild = true;
            } else if (key === 'layer_space') {
                var space = option(value, ['auto', 'world', 'screen'], 'auto');
                target.space = space === 'auto' ? (['text', 'shape', 'container'].indexOf(target.type) >= 0 ? 'screen' : 'world') : space;
                layer.classList.remove('is-space-world', 'is-space-screen');
                layer.classList.add('is-space-' + target.space);
                var parent = root.querySelector(target.space === 'screen' ? SELECTOR.screen : SELECTOR.camera);
                var next = Array.from(parent.children).find(function (child) { return Number(child.getAttribute('data-ds-index')) > update.index; });
                parent.insertBefore(layer, next || null);
            } else if (key === 'fit') {
                var fit = ['image', 'video', 'shape'].indexOf(target.type) >= 0 ? option(value, ['free', 'cover'], 'free') : 'free';
                layer.classList.remove('is-fit-free', 'is-fit-cover');
                layer.classList.add('is-fit-' + fit);
                resize = true;
            } else if (key === 'video_loop') {
                var video = layer.querySelector('video');
                if (video) { video.loop = value === 'yes'; }
            } else if (update.scope === 'layer' && key.indexOf('idle_') === 0) {
                var idle = key.slice(5);
                target.idle[idle] = idle === 'type' ? option(value, ['none', 'float', 'sway', 'breathe', 'drift'], 'none') : number(value, idle === 'speed' ? [6, 0.5, 60] : [10, 0, 200]);
                idleChanged = true;
            } else if (key === 'layer_wind') {
                target.wind = value === 'yes';
            } else if (key === 'reveal_split' || key === 'reveal_style') {
                target[key === 'reveal_split' ? 'split' : 'reveal'] = key === 'reveal_split' ? (['text', 'container'].indexOf(target.type) >= 0 ? option(value, ['none', 'lines', 'words', 'chars'], 'words') : 'none') : option(value, ['rise', 'fade', 'blur'], 'rise');
                fullRebuild = true;
            } else if (key.indexOf('start_') === 0) {
                var prop = key.slice(6);
                target.start[prop] = number(value, startLimits[prop]);
                resize = true;
            } else if (key === 'perspective') {
                config.perspective = number(value, [1000, 200, 4000]);
            } else if (key === 'depth_compensation') {
                config.compensate = value === 'yes';
            } else if (key === 'motion_style') {
                config.motion = option(value, ['scrub', 'glide'], 'scrub');
                scrollChanged = true;
            } else if (key === 'editor_preview') {
                config.editorScene = rawNumber(value) === null ? -1 : Math.trunc(number(value, [0, 0, 99]));
                scrollChanged = true;
            } else if (key === 'scene_label') {
                // The label is editor-only; the timeline uses stable numbered labels.
            } else if (key === 'scene_length' || key === 'scene_ease') {
                target[key === 'scene_length' ? 'length' : 'ease'] = key === 'scene_length' ? number(value, [100, 10, 600]) : ease(value, 'power2.inOut');
                rebuildTimeline = true;
                scrollChanged = scrollChanged || key === 'scene_length';
            } else if (Object.prototype.hasOwnProperty.call(numeric, key)) {
                var definition = numeric[key];
                config[definition[0]] = number(value, definition.slice(1));
                scrubChanged = scrubChanged || key === 'smoothness';
                scrollChanged = scrollChanged || key === 'glide_smoothing';
                motionChanged = motionChanged || key === 'camera_breath' || key === 'tilt_strength';
            } else if (Object.prototype.hasOwnProperty.call(switches, key)) {
                config[switches[key]] = value === 'yes';
                idleChanged = idleChanged || key === 'idle_enabled';
                motionChanged = motionChanged || key === 'pointer_tilt' || key === 'gyro_tilt';
                scrollChanged = scrollChanged || key === 'snap' || key === 'stay_fixed';
            } else if (key === 'snap_ease') {
                config.snapEase = ease(value, 'power2.inOut');
                scrollChanged = true;
            } else if (key.indexOf('particle_') === 0) {
                var part = key.slice(9);
                if (part === 'count') { config.particles.count = Math.trunc(number(value, [0, 0, 60])); }
                else if (part === 'size') { config.particles.size = number(value, [6, 2, 40]); }
                else if (part === 'style') { config.particles.style = option(value, ['pollen', 'petal', 'bokeh'], 'pollen'); }
                else { config.particles.color = typeof value === 'string' && (/^#[0-9a-fA-F]{3,8}$/.test(value) || /^(rgb|hsl)a?\(\s*[0-9.,%\s/]+\)$/.test(value)) ? value : '#fff3b0'; }
                particlesChanged = true;
            } else if (key === 'show_hint' || key === 'hint_mode') {
                if (key === 'show_hint') { hint.hidden = value !== 'yes'; hint.style.display = value === 'yes' ? '' : 'none'; }
                else { config.hintMode = option(value, ['after_first', 'start'], 'after_first'); }
                rebuildTimeline = true;
            } else {
                var name = key.slice(update.scope === 'root' ? 10 : 4);
                if (update.scope === 'scene' && rawNumber(value) === null) {
                    delete target.camera[name];
                } else {
                    target.camera[name] = number(value, cameraLimits[name]);
                }
                rebuildTimeline = true;
            }
        });
        if (update.scope === 'layer' && keys.indexOf('layer_space') >= 0) { target.z = target.space === 'screen' ? 0 : number(update.values.depth, [0, -3000, 900]); }
        if (updateImage) { updateImage(); }
        root.setAttribute('data-apeiron-depthscape', JSON.stringify(config));
        if (fullRebuild) {
            var progress = state.editorPreview.timeline.progress();
            var activeScroll = state.scroll && state.scroll.trigger.isActive;
            rebuild(root, updateText);
            if (config.editorScene < 0 && keys.indexOf('editor_preview') < 0 && state.editorPreview) {
                if (activeScroll && state.scroll) {
                    var trigger = state.scroll.trigger;
                    trigger.scroll(trigger.start + (trigger.end - trigger.start) * progress);
                }
                state.editorPreview.timeline.progress(progress, true);
                state.editorPreview.render();
            }
        } else {
            var keepScroll = !scrollChanged;
            if (updateText) {
                var resplit = state.editorPreview.content(layer, updateText);
                rebuildTimeline = resplit || rebuildTimeline;
            }
            state.editorPreview.render();
            if (idleChanged) { state.editorPreview.idle(); }
            if (particlesChanged) { state.editorPreview.particles(); }
            if (motionChanged) { state.editorPreview.motion(keys); }
            if (scrubChanged) { state.editorPreview.scrub(); }
            if (rebuildTimeline) { state.editorPreview.timelineUpdate(keys.indexOf('editor_preview') < 0, keepScroll); }
            else if (scrollChanged) { state.editorPreview.timelineUpdate(keys.indexOf('editor_preview') < 0, false, true); }
            else if (resize) { state.resize(state.size, false, true); }
        }
        state.lastEditorChange = update.settings && !update.css ? { settings: update.settings, keys: Object.keys(update.settings.changedAttributes() || update.values) } : null;
        return true;
    }

    function isHandledEditorChange(container) {
        var element = container && container.view && container.view.el;
        var root = element && ((element.closest && element.closest(SELECTOR.root)) || (element.querySelector && element.querySelector(SELECTOR.root)));
        var state = root && states.get(root);
        var handled = state && state.lastEditorChange;
        var settings = container && container.settings;
        if (!handled || handled.settings !== settings || !settings.changedAttributes) { return false; }
        var keys = Object.keys(settings.changedAttributes() || {});
        return keys.length === handled.keys.length && keys.every(function (key) { return handled.keys.indexOf(key) >= 0; });
    }

    function isDepthscapeContainer(container) {
        var model = container && container.model;
        if (model && typeof model.get === 'function' && model.get('widgetType') === 'apeiron-depthscape') {
            return true;
        }

        // Ancestor containers resize the stage through CSS without re-rendering the widget.
        var element = container && container.view && container.view.el;
        return !!(element && typeof element.querySelector === 'function'
            && (element.querySelector(SELECTOR.root) || (element.closest && element.closest(SELECTOR.root))));
    }

    function scheduleEditorRefresh() {
        if (editorRefreshTimer !== null) {
            return;
        }
        editorRefreshTimer = requestAnimationFrame(function () {
            editorRefreshTimer = null;
            if (hasScrollTrigger()) {
                window.ScrollTrigger.refresh();
            }
            // CSS-only edits can move layer anchors or reflow text without resizing the stage.
            checkSizes(true);
        });
    }

    function watchEditorChanges() {
        if (editorWatchBound || !isEditMode() || !window.parent || window.parent === window) {
            return;
        }

        var parentWindow;
        try {
            parentWindow = window.parent;
            // Accessing a cross-origin parent throws; the editor is always same-origin.
            void parentWindow.document;
        } catch (error) {
            return;
        }
        editorWatchBound = true;

        if (parentWindow.$e && parentWindow.$e.commands && typeof parentWindow.$e.commands.on === 'function') {
            parentWindow.$e.commands.on('run:after', function (component, command, args) {
                if (command === 'document/elements/settings' && args && isDepthscapeContainer(args.container) && !isHandledEditorChange(args.container)) {
                    scheduleEditorRefresh();
                }
            });
        }

        var channel = parentWindow.elementor && parentWindow.elementor.channels && parentWindow.elementor.channels.editor;
        if (channel && typeof channel.on === 'function') {
            channel.on('change', function (controlView) {
                var container = controlView && (controlView.container || (controlView.options && controlView.options.container));
                if (isDepthscapeContainer(container) && !isHandledEditorChange(container)) {
                    scheduleEditorRefresh();
                }
            });
        }
    }

    function waitForAssets(root) {
        var tasks = [];

        if (document.fonts && document.fonts.ready) {
            tasks.push(document.fonts.ready);
        }

        Array.prototype.forEach.call(root.querySelectorAll('img'), function (img) {
            tasks.push(new Promise(function (resolve) {
                var decode = function () {
                    if (typeof img.decode !== 'function') {
                        resolve();
                        return;
                    }
                    // A failed decode only means the browser will paint it lazily; never block the scene on it.
                    img.decode().then(resolve, resolve);
                };

                if (img.complete) {
                    decode();
                    return;
                }
                img.addEventListener('load', decode, { once: true });
                img.addEventListener('error', resolve, { once: true });
            }));
        });

        return Promise.race([
            Promise.all(tasks),
            new Promise(function (resolve) {
                setTimeout(resolve, BOOT_TIMEOUT);
            }),
        ]);
    }

    function initializeRoot(root) {
        if (states.has(root)) {
            return;
        }

        var gsap = getGsap();
        var config = parseConfig(root);
        if (!gsap || !config) {
            root.classList.remove('is-booting');
            root.classList.add('is-static');
            return;
        }

        registerPlugins(gsap);

        if (hasScrollTrigger() && !refreshListenerAdded) {
            window.ScrollTrigger.addEventListener('refresh', scheduleSizeCheck);
            refreshListenerAdded = true;
        }

        var state = { root: root, config: config, mm: null, size: null };
        states.set(root, state);
        liveRoots.push(root);
        watchLifecycle();

        waitForAssets(root).then(function () {
            if (states.get(root) === state && root.isConnected) {
                build(state);
            }
        });
    }

    function sweepDetached() {
        liveRoots.slice().forEach(function (root) {
            if (!root.isConnected) {
                destroy(root);
            }
        });
    }

    function findRoots(scope) {
        var container = scope && scope.jquery ? scope[0] : scope;
        if (!container || !container.querySelectorAll) {
            return [];
        }

        var roots = Array.from(container.querySelectorAll(SELECTOR.root));
        if (container.matches && container.matches(SELECTOR.root)) {
            roots.unshift(container);
        }

        return roots;
    }

    function initializeScope(scope) {
        sweepDetached();
        findRoots(scope).forEach(initializeRoot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initializeScope(document);
        }, { once: true });
    } else {
        initializeScope(document);
    }

    document.addEventListener('apeiron:content:loaded', function (event) {
        initializeScope(event.target || document);
    });

    document.addEventListener('apeiron:depthscape:preview-change', function (event) {
        if (isEditMode() && updateEditorPreview(event.target, event.detail)) { event.preventDefault(); }
    });

    document.addEventListener('apeiron:depthscape:content-mode', function (event) {
        var root = event.target;
        if (isEditMode() && root.matches && root.matches(SELECTOR.root)) {
            initializeScope(root);
            var state = states.get(root);
            if (!state) { return; }
            var nextId = root.apeironEditingContentId || '';
            var needsRebuild = contentModeNeedsRebuild(state, nextId);
            state.editingContentId = nextId;
            // CSS already stabilizes ordinary content; only SplitText needs its DOM reverted/rebuilt.
            if (needsRebuild) { rebuild(root); }
        }
    });

    // The Cover unlocks page scroll when it opens; pin positions must be re-measured afterwards.
    document.addEventListener('apeiron:cover:opened', function () {
        if (!hasScrollTrigger()) {
            return;
        }

        window.ScrollTrigger.refresh();
        liveRoots.forEach(function (root) {
            var state = states.get(root);
            if (state) {
                playFirstScene(state);
            }
        });
    });

    window.addEventListener('elementor/frontend/init', function () {
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-depthscape.default',
            function (scope) {
                watchEditorChanges();
                initializeScope(scope);
            }
        );
    });

    if (window.ApeironDepthscapeTestMode) {
        window.ApeironDepthscapeTestApi = Object.freeze({
            calmProps: calmProps,
            cameraState: cameraState,
            collectFlags: collectFlags,
            idleRange: idleRange,
            idleTransform: idleTransform,
            layerVars: layerVars,
            mergeCamera: mergeCamera,
            pickSnap: pickSnap,
            projectLayer: projectLayer,
            springStep: springStep,
            windFactor: windFactor,
            revealVars: revealVars,
            sceneDuration: sceneDuration,
            syncVideos: syncVideos,
            setupVideos: setupVideos,
        });
    }
}());
