// AbortController keeps listeners safe across Elementor re-renders.
(function () {
    'use strict';

    var SELECTOR = Object.freeze({
        root: '[data-apeiron-soundscape]',
        player: '.apeiron-soundscape-player',
        status: '.apeiron-soundscape-status',
        playToggle: '.apeiron-soundscape-toggle.is-play',
        pauseToggle: '.apeiron-soundscape-toggle.is-pause',
        audio: 'audio',
        youtube: '[data-video]',
    });
    var playersById = new Map();
    var videoMonitor = null;

    // One observer per document, used only by Sound Players that opt in.
    function createVideoMonitor() {
        var ac = new AbortController();
        var subscribers = new Set();
        var activeVideos = new Set();
        var frames = new Map();
        var excluded = SELECTOR.root + ', .elementor-background-video-container, '
            + '.elementor-background-video-hosted, .elementor-background-video-embed, '
            + '[role="presentation"], [role="none"], [aria-hidden="true"]';

        function isContent(element) {
            return element.isConnected && !element.closest(excluded);
        }

        function isActiveNative(video) {
            return isContent(video)
                && !(video.autoplay && !video.controls)
                && (video.controls || !!video.closest('.elementor-widget-video, .elementor-video-container'))
                && !video.paused && !video.ended && !video.error
                && (video.readyState >= 2 || activeVideos.has(video))
                && !video.muted && video.volume > 0;
        }

        function notify(wasActive) {
            var active = activeVideos.size > 0;
            if (active !== wasActive) {
                subscribers.forEach(function (sync) { sync.update(active); });
            }
        }

        function updateActivity(element, active) {
            var wasActive = activeVideos.size > 0;
            if (active) {
                activeVideos.add(element);
            } else {
                activeVideos.delete(element);
            }
            notify(wasActive);
        }

        function getFrameInfo(frame) {
            if (!isContent(frame)) {
                return null;
            }
            var url;
            try {
                url = new URL(frame.getAttribute('src') || '', window.location.href);
            } catch (error) {
                return null;
            }
            var host = url.hostname.toLowerCase();
            var youtube = /^(www\.)?youtube(-nocookie)?\.com$/.test(host)
                && url.pathname.indexOf('/embed/') === 0;
            var vimeo = host === 'player.vimeo.com' && url.pathname.indexOf('/video/') === 0;
            if ((!youtube && !vimeo) || url.protocol !== 'https:') {
                return null;
            }
            if (url.searchParams.get('background') === '1'
                || (url.searchParams.get('autoplay') === '1' && url.searchParams.get('controls') === '0')) {
                return null;
            }
            return {
                provider: youtube ? 'youtube' : 'vimeo',
                url: url.href,
                origin: url.origin,
                playing: false,
                muted: null,
                volume: null,
                subscribed: false,
                loaded: false
            };
        }

        function requestVimeo(frame, record, method, value) {
            if (frame.contentWindow && frame.isConnected) {
                var message = { method: method };
                if (value !== undefined) {
                    message.value = value;
                }
                frame.contentWindow.postMessage(message, record.origin);
            }
        }

        function subscribeVimeo(frame, record) {
            // The initial about:blank document still belongs to this page.
            if (frame.contentDocument) {
                return;
            }
            if (!record.subscribed) {
                ['play', 'pause', 'ended', 'volumechange'].forEach(function (eventName) {
                    requestVimeo(frame, record, 'addEventListener', eventName);
                });
                record.subscribed = true;
            }
            requestVimeo(frame, record, 'getPaused');
            requestVimeo(frame, record, 'getVolume');
            requestVimeo(frame, record, 'getMuted');
        }

        function isActiveFrame(frame, record) {
            return isContent(frame) && record.playing && record.muted === false && record.volume > 0;
        }

        function readExistingYouTubeState(frame, record) {
            if (!window.YT || typeof window.YT.get !== 'function' || !frame.id) {
                return;
            }
            var api = window.YT.get(frame.id);
            if (!api || typeof api.getIframe !== 'function' || api.getIframe() !== frame
                || typeof api.getPlayerState !== 'function' || typeof api.getVolume !== 'function'
                || typeof api.isMuted !== 'function') {
                return;
            }
            var state = api.getPlayerState();
            var volume = api.getVolume();
            var muted = api.isMuted();
            record.playing = state === 1 || (state === 3 && record.playing);
            if (typeof volume === 'number' && Number.isFinite(volume)) {
                record.volume = Math.max(0, Math.min(1, volume / 100));
            }
            if (typeof muted === 'boolean') {
                record.muted = muted;
            }
        }

        function reconcile() {
            var wasActive = activeVideos.size > 0;
            var nextActive = new Set();
            frames.forEach(function (record, frame) {
                if (!frame.isConnected) {
                    frames.delete(frame);
                }
            });
            document.querySelectorAll('video, iframe').forEach(function (element) {
                if (element.tagName === 'VIDEO') {
                    if (isActiveNative(element)) {
                        nextActive.add(element);
                    }
                    return;
                }
                var info = getFrameInfo(element);
                var record = frames.get(element);
                if (!info) {
                    frames.delete(element);
                    return;
                }
                if (!record || record.url !== info.url) {
                    record = info;
                    frames.set(element, record);
                    if (record.provider === 'vimeo') {
                        subscribeVimeo(element, record);
                    } else {
                        readExistingYouTubeState(element, record);
                    }
                }
                if (isActiveFrame(element, record)) {
                    nextActive.add(element);
                }
            });
            activeVideos = nextActive;
            notify(wasActive);
        }

        function onMessage(event) {
            var frame = null;
            var record = null;
            frames.forEach(function (candidate, element) {
                if (element.contentWindow === event.source && candidate.origin === event.origin) {
                    frame = element;
                    record = candidate;
                }
            });
            if (!record || !frame.isConnected) {
                return;
            }
            var message = event.data;
            if (typeof message === 'string') {
                try {
                    message = JSON.parse(message);
                } catch (error) {
                    return;
                }
            }
            if (!message || typeof message !== 'object') {
                return;
            }
            if (record.provider === 'youtube') {
                if (record.muted === null || record.volume === null) {
                    readExistingYouTubeState(frame, record);
                }
                var info = message.info;
                var state = message.event === 'onStateChange' ? info : undefined;
                if (message.event === 'infoDelivery' || message.event === 'initialDelivery') {
                    if (info && typeof info === 'object') {
                        state = info.playerState;
                        if (typeof info.muted === 'boolean') {
                            record.muted = info.muted;
                        }
                        if (typeof info.volume === 'number' && Number.isFinite(info.volume)) {
                            record.volume = Math.max(0, Math.min(1, info.volume / 100));
                        }
                    }
                }
                if (state === 1) {
                    record.playing = true;
                } else if (state === 0 || state === 2 || state === 5 || state === -1 || message.event === 'onError') {
                    record.playing = false;
                }
            } else {
                if (message.event === 'ready') {
                    record.subscribed = false;
                    subscribeVimeo(frame, record);
                } else if (message.event === 'play') {
                    record.playing = true;
                    requestVimeo(frame, record, 'getVolume');
                    requestVimeo(frame, record, 'getMuted');
                } else if (message.event === 'pause' || message.event === 'ended') {
                    record.playing = false;
                } else if (message.event === 'volumechange') {
                    var data = message.data || {};
                    if (typeof data.volume === 'number' && Number.isFinite(data.volume)) {
                        record.volume = Math.max(0, Math.min(1, data.volume));
                    }
                    if (typeof data.muted === 'boolean') {
                        record.muted = data.muted;
                    } else {
                        requestVimeo(frame, record, 'getMuted');
                    }
                } else if (message.method === 'getPaused' && typeof message.value === 'boolean') {
                    record.playing = !message.value;
                } else if (message.method === 'getMuted' && typeof message.value === 'boolean') {
                    record.muted = message.value;
                } else if (message.method === 'getVolume'
                    && typeof message.value === 'number' && Number.isFinite(message.value)) {
                    record.volume = Math.max(0, Math.min(1, message.value));
                }
            }
            updateActivity(frame, isActiveFrame(frame, record));
        }

        function hasMedia(node) {
            return node.nodeType === 1 && (node.matches('video, iframe, ' + SELECTOR.root)
                || !!node.querySelector('video, iframe, ' + SELECTOR.root));
        }

        var observer = new MutationObserver(function (records) {
            var relevant = records.some(function (record) {
                if (record.type === 'attributes') {
                    return hasMedia(record.target) || !!record.target.closest('video');
                }
                return Array.from(record.addedNodes).some(hasMedia) || Array.from(record.removedNodes).some(hasMedia);
            });
            if (!relevant) {
                return;
            }
            subscribers.forEach(function (sync) {
                if (!sync.player.isConnected) {
                    SoundscapePlayer.cleanupWidget(sync.player);
                }
            });
            if (subscribers.size) {
                reconcile();
            }
        });
        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['src', 'autoplay', 'controls', 'role', 'aria-hidden']
        });
        ['play', 'playing', 'pause', 'ended', 'volumechange', 'loadeddata', 'emptied', 'error'].forEach(function (eventName) {
            document.addEventListener(eventName, function (event) {
                if (event.target.tagName === 'VIDEO') {
                    updateActivity(event.target, isActiveNative(event.target));
                }
            }, { capture: true, signal: ac.signal });
        });
        document.addEventListener('load', function (event) {
            if (event.target.tagName === 'IFRAME') {
                reconcile();
                var record = frames.get(event.target);
                if (record) {
                    if (record.loaded) {
                        record.playing = false;
                        record.muted = null;
                        record.volume = null;
                        updateActivity(event.target, false);
                    }
                    record.loaded = true;
                    if (record.provider === 'vimeo') {
                        subscribeVimeo(event.target, record);
                    }
                }
            }
        }, { capture: true, signal: ac.signal });
        window.addEventListener('message', onMessage, { signal: ac.signal });
        reconcile();

        return {
            subscribe: function (sync) {
                subscribers.add(sync);
                sync.update(activeVideos.size > 0);
            },
            unsubscribe: function (sync) {
                subscribers.delete(sync);
                if (!subscribers.size) {
                    ac.abort();
                    observer.disconnect();
                    frames.clear();
                    activeVideos.clear();
                    videoMonitor = null;
                    // Do not remove Vimeo's provider subscriptions: another
                    // player/SDK in this document may use the same events.
                }
            }
        };
    }

    function createVideoSync(player, backend, signal) {
        if (player.dataset.videoSyncEnabled !== 'yes') {
            return null;
        }
        var mode = player.dataset.videoSyncMode === 'duck' ? 'duck' : 'pause';
        var level = parseFloat(player.dataset.videoSyncDuckVolume);
        level = Number.isFinite(level) ? Math.max(0, Math.min(100, level)) / 100 : 0.2;
        var pauseHidden = player.dataset.pauseHidden !== 'no';
        var hasVideos = false;
        var wasPlayingBeforeVideo = false;
        var pausedByVideo = false;
        var previousVolume = null;
        var expectedVolume = null;
        var manualOverride = false;
        var resumePending = false;
        var disposed = false;

        function fail() {
            resumePending = false;
            if (!disposed && player.isConnected) {
                if (!backend.isPlaying()) {
                    SoundscapePlayer.setPaused(player);
                }
                SoundscapePlayer.setStatus(player, 'error', SoundscapePlayer.getMessage(player, 'errorMessage', 'Audio gagal diputar.'));
            }
        }

        function restoreVolume() {
            if (previousVolume !== null) {
                var volume = previousVolume;
                previousVolume = null;
                sync.writeVolume(volume, false);
            }
        }

        function tryResume() {
            if (!resumePending || disposed || signal.aborted || !player.isConnected || sync.blocksPlayback()
                || (pauseHidden && document.visibilityState === 'hidden') || !backend.canResume()) {
                return;
            }
            resumePending = false;
            if (backend.isPlaying()) {
                return;
            }
            try {
                var promise = backend.play();
                if (promise && typeof promise.catch === 'function') {
                    promise.catch(fail);
                }
            } catch (error) {
                fail();
            }
        }

        var sync = {
            player: player,
            blocksPlayback: function () {
                return !disposed && hasVideos && mode === 'pause' && !manualOverride;
            },
            hasResumeIntent: function () {
                return !disposed && mode === 'pause' && ((pausedByVideo && wasPlayingBeforeVideo) || resumePending);
            },
            getNormalVolume: function () {
                return previousVolume !== null ? previousVolume
                    : (backend.getNormalVolume ? backend.getNormalVolume() : backend.getVolume());
            },
            writeVolume: function (volume, remember) {
                volume = Math.max(0, Math.min(1, volume));
                if (!disposed && hasVideos && mode === 'duck' && !manualOverride) {
                    if (remember !== false) {
                        previousVolume = volume;
                    }
                    volume = Math.min(volume, level);
                }
                expectedVolume = volume;
                try {
                    backend.setVolume(volume);
                } catch (error) {
                    fail();
                }
            },
            onVolumeChange: function () {
                if (!disposed && previousVolume !== null && !manualOverride) {
                    var volume = backend.getVolume();
                    if (expectedVolume === null || Math.abs(volume - expectedVolume) > 0.001) {
                        sync.writeVolume(volume);
                    }
                }
            },
            update: function (active) {
                if (disposed || hasVideos === active) {
                    return;
                }
                hasVideos = active;
                if (active) {
                    manualOverride = false;
                    wasPlayingBeforeVideo = backend.wasPlaying() || resumePending;
                    pausedByVideo = wasPlayingBeforeVideo;
                    resumePending = false;
                    if (mode === 'pause') {
                        if (backend.isPlaying()) {
                            backend.pause();
                        }
                    } else if (!backend.isReady || backend.isReady()) {
                        previousVolume = sync.getNormalVolume();
                        sync.writeVolume(previousVolume, false);
                    }
                } else {
                    restoreVolume();
                    resumePending = mode === 'pause' && pausedByVideo && wasPlayingBeforeVideo && !manualOverride;
                    pausedByVideo = false;
                    wasPlayingBeforeVideo = false;
                    manualOverride = false;
                    tryResume();
                }
            },
            onPlaying: function () {
                if (disposed || signal.aborted) {
                    return false;
                }
                if (sync.blocksPlayback()) {
                    if (backend.isPlaying()) {
                        pausedByVideo = true;
                        backend.pause();
                    }
                    return false;
                }
                sync.refresh();
                return true;
            },
            manualInteraction: function () {
                manualOverride = hasVideos;
                sync.cancelResume();
                restoreVolume();
            },
            cancelResume: function () {
                wasPlayingBeforeVideo = false;
                pausedByVideo = false;
                resumePending = false;
            },
            refresh: function () {
                if (!disposed) {
                    if (hasVideos && mode === 'duck' && !manualOverride && previousVolume === null
                        && (!backend.isReady || backend.isReady())) {
                        previousVolume = sync.getNormalVolume();
                    }
                    if (previousVolume !== null && !manualOverride) {
                        sync.writeVolume(previousVolume, false);
                    }
                    tryResume();
                }
            }
        };
        player._apeironVideoSync = sync;
        if (!videoMonitor) {
            videoMonitor = createVideoMonitor();
        }
        var monitor = videoMonitor;
        signal.addEventListener('abort', function () {
            disposed = true;
            resumePending = false;
            if (player.isConnected) {
                restoreVolume();
            }
            monitor.unsubscribe(sync);
        }, { once: true });
        document.addEventListener('visibilitychange', tryResume, { signal: signal });
        monitor.subscribe(sync);
        return sync;
    }

    var SoundscapePlayer = {
        initWidget: function (player) {
            if (!player || player.dataset.apeironSoundscapeInit === 'yes') {
                return;
            }
            player.dataset.apeironSoundscapeInit = 'yes';
            if (player.id) {
                playersById.set(player.id, player);
            }

            var ac = new AbortController();
            player._apeironAbort = ac;

            var srcType = player.dataset.srcType || 'upload';
            var autoplay = player.dataset.autoplay === 'yes';
            var loop = player.dataset.loop === 'yes';
            var pauseHidden = player.dataset.pauseHidden !== 'no';
            var startSec = parseFloat(player.dataset.start) || 0;
            var endSec = parseFloat(player.dataset.end) || 0;
            var hasRange = endSec > 0 && endSec > startSec;
            var hasInvalidRange = endSec > 0 && endSec <= startSec;

            SoundscapePlayer.clearStatus(player);

            if (hasInvalidRange) {
                player.setAttribute('aria-disabled', 'true');
                SoundscapePlayer.setStatus(
                    player,
                    'error',
                    SoundscapePlayer.getMessage(player, 'rangeMessage', 'Waktu berhenti harus lebih besar dari waktu mulai.')
                );
                return;
            }

			if (srcType === 'youtube') {
                SoundscapePlayer.initYouTube(player, autoplay, pauseHidden, startSec, endSec, hasRange, ac.signal);
            } else {
                SoundscapePlayer.initAudio(player, autoplay, loop, pauseHidden, startSec, endSec, hasRange, ac.signal);
            }
        },

        getMessage: function (player, key, fallback) {
            return player.dataset[key] || fallback;
        },

        setStatus: function (player, state, message) {
            var status = player.querySelector(SELECTOR.status);

            player.classList.remove('is-loading', 'has-error');
            player.removeAttribute('aria-busy');

            if (state === 'loading') {
                player.classList.add('is-loading');
                player.setAttribute('aria-busy', 'true');
            } else if (state === 'error') {
                player.classList.add('has-error');
            }

            if (status) {
                status.textContent = message || '';
                status.classList.toggle('is-visible', !!message);
            }
        },

        clearStatus: function (player) {
            if (player.classList.contains('is-empty')) {
                SoundscapePlayer.setStatus(player, '', '');
                return;
            }

            SoundscapePlayer.setStatus(player, '', '');
        },

        setPlaying: function (player) {
            if (player._apeironVideoSync && !player._apeironVideoSync.onPlaying()) {
                return;
            }
            var playToggle = player.querySelector(SELECTOR.playToggle);
            var pauseToggle = player.querySelector(SELECTOR.pauseToggle);

            player.classList.add('is-playing');
            player.classList.remove('has-error');

            if (playToggle) {
                playToggle.setAttribute('aria-pressed', 'false');
            }
            if (pauseToggle) {
                pauseToggle.setAttribute('aria-pressed', 'true');
            }
        },

        setPaused: function (player) {
            var playToggle = player.querySelector(SELECTOR.playToggle);
            var pauseToggle = player.querySelector(SELECTOR.pauseToggle);

            player.classList.remove('is-playing');

            if (playToggle) {
                playToggle.setAttribute('aria-pressed', 'false');
            }
            if (pauseToggle) {
                pauseToggle.setAttribute('aria-pressed', 'false');
            }
        },

		initAudio: function (player, autoplay, loop, pauseHidden, startSec, endSec, hasRange, signal) {
            var audio = player.querySelector(SELECTOR.audio);
            var emptyMessage = SoundscapePlayer.getMessage(player, 'emptyMessage', 'Pilih audio terlebih dahulu.');
            var loadingMessage = SoundscapePlayer.getMessage(player, 'loadingMessage', 'Memuat audio...');
            var errorMessage = SoundscapePlayer.getMessage(player, 'errorMessage', 'Audio gagal diputar.');
            var hasStart = startSec > 0;
            var videoSync = null;
            var wasPlayingOnHide = false;
            var playbackStarted = !!audio && !audio.paused && audio.readyState >= 2;

            if (!audio) {
                player.classList.add('is-empty');
                player.setAttribute('aria-disabled', 'true');
                SoundscapePlayer.clearStatus(player);
                player.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    SoundscapePlayer.setStatus(player, 'empty', emptyMessage);
                }, { signal: signal });
                return;
            }

            player.classList.remove('is-empty');
            player.removeAttribute('aria-disabled');

            if (loop) {
                audio.loop = true;
            }

            function resetToStartIfNeeded() {
                if (audio.readyState === 0) {
                    return;
                }

                if (hasRange && (audio.currentTime >= endSec || audio.currentTime < startSec)) {
                    audio.currentTime = startSec;
                } else if (hasStart && audio.currentTime === 0) {
                    audio.currentTime = startSec;
                }
            }

            function playAudio(showLoading, silentFailure) {
                if (videoSync && videoSync.blocksPlayback()) {
                    pauseAudio();
                    return Promise.resolve();
                }
                if (showLoading !== false) {
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                }
                resetToStartIfNeeded();

                var playPromise;
                try {
                    playPromise = audio.play();
                } catch (error) {
                    SoundscapePlayer.setPaused(player);
                    if (silentFailure) {
                        SoundscapePlayer.clearStatus(player);
                    } else {
                        SoundscapePlayer.setStatus(player, 'error', errorMessage);
                    }
                    return Promise.reject(error);
                }

                if (!playPromise || typeof playPromise.then !== 'function') {
                    SoundscapePlayer.clearStatus(player);
                    SoundscapePlayer.setPlaying(player);
                    return Promise.resolve();
                }

                return playPromise.then(function () {
                    SoundscapePlayer.clearStatus(player);
                    SoundscapePlayer.setPlaying(player);
                }).catch(function (error) {
                    if (videoSync && (signal.aborted || (videoSync.blocksPlayback() && error.name === 'AbortError'))) {
                        if (!signal.aborted) {
                            SoundscapePlayer.clearStatus(player);
                        }
                        return;
                    }
                    SoundscapePlayer.setPaused(player);
                    if (silentFailure) {
                        SoundscapePlayer.clearStatus(player);
                    } else {
                        SoundscapePlayer.setStatus(player, 'error', errorMessage);
                    }
                    return Promise.reject(error);
                });
            }

            function pauseAudio() {
                audio.pause();
                SoundscapePlayer.setPaused(player);
                SoundscapePlayer.clearStatus(player);
            }

            // Cover handoff: the Buka Undangan click is the only user gesture that
            // can unlock audio, so start muted there and become audible on opened.
            var coverSilent = false;
            var coverArmed = false;
            var coverVolume = 0;
            var coverMuted = false;
            var coverFade = 0;

            videoSync = createVideoSync(player, {
                isPlaying: function () { return !coverSilent && !audio.paused && !audio.ended; },
                wasPlaying: function () {
                    return !coverSilent && !audio.ended && ((playbackStarted && !audio.paused) || wasPlayingOnHide);
                },
                canResume: function () {
                    return !coverSilent && !audio.error && !audio.ended && !(hasRange && audio.currentTime >= endSec);
                },
                play: function () {
                    wasPlayingOnHide = false;
                    return playAudio(false, false);
                },
                pause: pauseAudio,
                getVolume: function () { return audio.volume; },
                getNormalVolume: function () { return coverSilent ? coverVolume : audio.volume; },
                setVolume: function (volume) { audio.volume = volume; }
            }, signal);

            function setAudioVolume(volume, temporary) {
                if (videoSync) {
                    videoSync.writeVolume(volume, !temporary);
                } else {
                    audio.volume = volume;
                }
            }

            function coverGoAudible() {
                coverSilent = false;

                function reveal() {
                    if (audio.readyState > 0) {
                        audio.currentTime = startSec;
                    }
                    audio.muted = coverMuted;
                    setAudioVolume(0);

                    var steps = 12;
                    var step = 0;
                    coverFade = window.setInterval(function () {
                        step += 1;
                        setAudioVolume(Math.min(1, coverVolume * (step / steps)));
                        if (step >= steps) {
                            window.clearInterval(coverFade);
                            coverFade = 0;
                            setAudioVolume(coverVolume);
                        }
                    }, 25);

                    SoundscapePlayer.clearStatus(player);
                    SoundscapePlayer.setPlaying(player);
                }

                if (audio.readyState === 0) {
                    audio.addEventListener('loadedmetadata', reveal, { once: true, signal: signal });
                    return;
                }

                reveal();
            }

            // "Mulai Musik Saat": play on the click, or stay silent until opened.
            var coverStartMode = player.dataset.coverMusicStart === 'cover_click' ? 'cover_click' : 'cover_opened';

            function coverPlayNow() {
                if (coverArmed || player.classList.contains('is-empty')) {
                    return;
                }
                coverArmed = true;

                if (!audio.paused || (videoSync && videoSync.hasResumeIntent())) {
                    return;
                }

                resetToStartIfNeeded();
                if (!hasStart && audio.readyState > 0) {
                    audio.currentTime = 0;
                }
                player._apeironAutoplayUnlocked = true;
                playAudio(true, false).catch(function () {});
            }

            function coverUnlock() {
                if (coverArmed || player.classList.contains('is-empty')) {
                    return;
                }
                coverArmed = true;

                // An already-playing player (autoplay control) is left untouched.
                if (!audio.paused || (videoSync && videoSync.hasResumeIntent())) {
                    return;
                }

                coverVolume = videoSync ? videoSync.getNormalVolume() : audio.volume;
                coverMuted = audio.muted;
                // Silence must land before play() so nothing leaks through.
                setAudioVolume(0, true);
                audio.muted = true;
                coverSilent = true;

                var promise;
                try {
                    promise = audio.play();
                } catch (error) {
                    coverSilent = false;
                    setAudioVolume(coverVolume);
                    audio.muted = coverMuted;
                    return;
                }

                if (promise && typeof promise.then === 'function') {
                    promise.then(function () {
                        player._apeironAutoplayUnlocked = true;
                    }).catch(function () {
                        coverSilent = false;
                        setAudioVolume(coverVolume);
                        audio.muted = coverMuted;
                        SoundscapePlayer.setPaused(player);
                    });
                } else {
                    player._apeironAutoplayUnlocked = true;
                }
            }

            document.addEventListener('apeiron:cover:opening', function (event) {
                var detail = event.detail || {};

                if (coverStartMode === 'cover_click') {
                    coverPlayNow();
                    return;
                }

                coverUnlock();
                document.addEventListener(detail.openedEventName || 'apeiron:cover:opened', function () {
                    if (coverSilent) {
                        coverGoAudible();
                    }
                }, { once: true, signal: signal });
            }, { signal: signal });

            signal.addEventListener('abort', function () {
                if (coverFade) {
                    window.clearInterval(coverFade);
                    coverFade = 0;
                }
            });

            audio.addEventListener('loadedmetadata', function () {
                if (hasStart && audio.currentTime < startSec) {
                    audio.currentTime = startSec;
                }
            }, { signal: signal });

            if (hasRange) {
                audio.addEventListener('timeupdate', function () {
                    if (audio.currentTime >= endSec) {
                        if (loop) {
                            audio.currentTime = startSec;
                        } else {
                            if (videoSync) {
                                videoSync.cancelResume();
                            }
                            pauseAudio();
                        }
                    }
                }, { signal: signal });
            }

            audio.addEventListener('play', function () {
                if (coverSilent) {
                    return;
                }
                SoundscapePlayer.clearStatus(player);
                SoundscapePlayer.setPlaying(player);
            }, { signal: signal });

            audio.addEventListener('pause', function () {
                playbackStarted = false;
                SoundscapePlayer.setPaused(player);
            }, { signal: signal });

            audio.addEventListener('ended', function () {
                if (videoSync) {
                    videoSync.cancelResume();
                }
                SoundscapePlayer.setPaused(player);
                if (!loop && (hasRange || hasStart)) {
                    audio.currentTime = startSec;
                }
            }, { signal: signal });

            audio.addEventListener('waiting', function () {
                if (!audio.paused) {
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                }
            }, { signal: signal });

            audio.addEventListener('canplay', function () {
                if (!audio.paused) {
                    SoundscapePlayer.clearStatus(player);
                }
            }, { signal: signal });

            audio.addEventListener('error', function () {
                if (videoSync) {
                    videoSync.cancelResume();
                }
                audio.pause();
                SoundscapePlayer.setPaused(player);
                SoundscapePlayer.setStatus(player, 'error', errorMessage);
            }, { capture: true, signal: signal });

            if (videoSync) {
                audio.addEventListener('playing', function () {
                    playbackStarted = true;
                    if (!coverSilent) {
                        SoundscapePlayer.setPlaying(player);
                    }
                }, { signal: signal });
                audio.addEventListener('volumechange', function () {
                    if (!coverSilent) {
                        videoSync.onVolumeChange();
                    }
                }, { signal: signal });
                audio.addEventListener('emptied', videoSync.cancelResume, { signal: signal });
            }

            player.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (player.classList.contains('is-empty')) {
                    return;
                }

                if (videoSync) {
                    videoSync.manualInteraction();
                    wasPlayingOnHide = false;
                }
                player._apeironAutoplayUnlocked = true;
                if (audio.paused) {
                    playAudio(true, false).catch(function () {});
                } else {
                    pauseAudio();
                }
            }, { signal: signal });

            if (pauseHidden) {
                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'hidden') {
                        wasPlayingOnHide = !audio.paused && (!videoSync || playbackStarted);
                        if (!audio.paused) {
                            pauseAudio();
                        }
                    } else if (document.visibilityState === 'visible' && wasPlayingOnHide) {
                        if (!videoSync || !videoSync.blocksPlayback()) {
                            playAudio(false, true).catch(function () {});
                        }
                        wasPlayingOnHide = false;
                    }
                }, { signal: signal });
            }

            if (autoplay) {
                playAudio(false, true).then(function () {
                    if (!videoSync || (!signal.aborted && !audio.paused)) {
                        player._apeironAutoplayUnlocked = true;
                    }
                }).catch(function () {
                    SoundscapePlayer.setPaused(player);
                    SoundscapePlayer.clearStatus(player);
                });
            } else {
                SoundscapePlayer.setPaused(player);
            }
        },

        extractYouTubeID: function (url) {
            if (!url) {
                return null;
            }

            try {
                var parsed = new URL(url, window.location.href);
                var host = parsed.hostname.replace(/^www\./, '');

                if (host === 'youtu.be') {
                    return parsed.pathname.split('/').filter(Boolean)[0] || null;
                }

                var videoParam = parsed.searchParams.get('v');
                if (videoParam) {
                    return videoParam;
                }

                var parts = parsed.pathname.split('/').filter(Boolean);
                var embedIndex = parts.indexOf('embed');
                if (embedIndex !== -1 && parts[embedIndex + 1]) {
                    return parts[embedIndex + 1];
                }

                var shortsIndex = parts.indexOf('shorts');
                if (shortsIndex !== -1 && parts[shortsIndex + 1]) {
                    return parts[shortsIndex + 1];
                }
            } catch (error) {
				// Support pasted partial YouTube URLs.
            }

            var match = String(url).match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([A-Za-z0-9_-]{11})/);
            return match ? match[1] : null;
        },

        initYouTube: function (player, autoplay, pauseHidden, startSec, endSec, hasRange, signal) {
            var ytContainer = player.querySelector(SELECTOR.youtube);
            var emptyMessage = SoundscapePlayer.getMessage(player, 'emptyMessage', 'Pilih audio terlebih dahulu.');
            var loadingMessage = SoundscapePlayer.getMessage(player, 'loadingMessage', 'Memuat audio...');
            var errorMessage = SoundscapePlayer.getMessage(player, 'errorMessage', 'Audio gagal diputar.');

            if (!ytContainer) {
                player.classList.add('is-empty');
                player.setAttribute('aria-disabled', 'true');
                SoundscapePlayer.clearStatus(player);
                player.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    SoundscapePlayer.setStatus(player, 'empty', emptyMessage);
                }, { signal: signal });
                return;
            }

            var videoUrl = ytContainer.dataset.video;
            var videoId = SoundscapePlayer.extractYouTubeID(videoUrl);
            var playerId = ytContainer.id + '-player';

            if (!videoId) {
                SoundscapePlayer.setPaused(player);
                SoundscapePlayer.setStatus(player, 'error', 'URL YouTube tidak valid.');
                return;
            }

            player.classList.remove('is-empty');
            player.removeAttribute('aria-disabled');

            var ytPlayer = null;
            player._apeironYTPlayer = null;
            var ytReady = false;
            var ytPlaybackStarted = false;
            var wasYTPlayingOnHide = false;

            // Cover handoff: the Buka Undangan click is the only user gesture that
            // can unlock playback, so start at volume 0 there and raise it on opened.
            var coverSilent = false;
            var coverArmed = false;
            var coverPending = false;
            var coverParked = false;
            var coverVolume = 100;
            var coverFade = 0;

            var videoSync = createVideoSync(player, {
                isReady: function () { return ytReady; },
                isPlaying: function () {
                    if (!ytReady || coverSilent) {
                        return false;
                    }
                    var state = ytPlayer.getPlayerState();
                    return state === stateValue('PLAYING', 1) || state === stateValue('BUFFERING', 3);
                },
                wasPlaying: function () {
                    if (!ytReady || coverSilent) {
                        return false;
                    }
                    var state = ytPlayer.getPlayerState();
                    return state === stateValue('PLAYING', 1)
                        || (state === stateValue('BUFFERING', 3) && ytPlaybackStarted) || wasYTPlayingOnHide;
                },
                canResume: function () {
                    return ytReady && !coverSilent && !player.classList.contains('has-error')
                        && ytPlayer.getPlayerState() !== stateValue('ENDED', 0);
                },
                play: function () {
                    wasYTPlayingOnHide = false;
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                    ytPlayer.playVideo();
                },
                pause: function () {
                    if (ytReady) {
                        ytPlayer.pauseVideo();
                    }
                    SoundscapePlayer.setPaused(player);
                    SoundscapePlayer.clearStatus(player);
                },
                getVolume: function () { return ytReady ? ytPlayer.getVolume() / 100 : coverVolume / 100; },
                getNormalVolume: function () { return coverSilent ? coverVolume / 100 : (ytReady ? ytPlayer.getVolume() / 100 : 1); },
                setVolume: function (volume) {
                    if (ytReady) {
                        ytPlayer.setVolume(volume * 100);
                    }
                }
            }, signal);

            function setYouTubeVolume(volume, temporary) {
                if (videoSync) {
                    videoSync.writeVolume(volume / 100, !temporary);
                } else {
                    ytPlayer.setVolume(volume);
                }
            }

            function coverSilentStart() {
                if (!ytPlayer || typeof ytPlayer.playVideo !== 'function' || (videoSync && !ytReady)) {
                    return false;
                }

                try {
                    if (typeof ytPlayer.getVolume === 'function') {
                        coverVolume = videoSync ? videoSync.getNormalVolume() * 100 : ytPlayer.getVolume();
                    }
                    // Silence must land before playback starts so nothing leaks.
                    setYouTubeVolume(0, true);
                    coverSilent = true;
                    ytPlayer.playVideo();
                    return true;
                } catch (error) {
                    coverSilent = false;
                    return false;
                }
            }

            // "Mulai Musik Saat": play on the click, or stay silent until opened.
            var coverStartMode = player.dataset.coverMusicStart === 'cover_click' ? 'cover_click' : 'cover_opened';

            function coverPlayNow() {
                if (coverArmed) {
                    return;
                }
                coverArmed = true;

                if (videoSync && videoSync.blocksPlayback()) {
                    return;
                }
                if (!ytPlayer || typeof ytPlayer.playVideo !== 'function') {
                    // Existing intent flag: onReady starts audible playback.
                    player._apeironYTPlayWhenReady = true;
                    return;
                }

                try {
                    ytPlayer.playVideo();
                } catch (error) {
                    SoundscapePlayer.setStatus(player, 'error', errorMessage);
                }
            }

            function coverUnlock() {
                if (coverArmed || (videoSync && videoSync.hasResumeIntent())) {
                    return;
                }
                coverArmed = true;

                if (!coverSilentStart()) {
                    coverPending = true;
                }
            }

            function coverGoAudible() {
                if (!coverArmed || !ytPlayer) {
                    return;
                }
                coverSilent = false;

                try {
                    setYouTubeVolume(0);
                    if (!coverParked) {
                        ytPlayer.seekTo(startSec, true);
                    }
                    if (!videoSync || !videoSync.blocksPlayback()) {
                        ytPlayer.playVideo();
                    }
                } catch (error) {
                    return;
                }

                var steps = 12;
                var step = 0;
                coverFade = window.setInterval(function () {
                    step += 1;
                    try {
                        setYouTubeVolume(Math.min(100, coverVolume * (step / steps)));
                    } catch (error) {
                        window.clearInterval(coverFade);
                        coverFade = 0;
                        return;
                    }
                    if (step >= steps) {
                        window.clearInterval(coverFade);
                        coverFade = 0;
                    }
                }, 25);

                SoundscapePlayer.clearStatus(player);
                SoundscapePlayer.setPlaying(player);
            }

            function stateValue(name, fallback) {
                return window.YT && window.YT.PlayerState && window.YT.PlayerState[name] !== undefined
                    ? window.YT.PlayerState[name]
                    : fallback;
            }

            function applyYTState(state) {
                if (coverSilent) {
                    // The click gesture unlocked playback; park it at startSec so the
                    // cover stays silent and the song still opens from the beginning.
                    if (state === stateValue('PLAYING', 1) && !coverParked) {
                        coverParked = true;
                        try {
                            ytPlayer.pauseVideo();
                            ytPlayer.seekTo(startSec, true);
                        } catch (error) {
                            coverParked = false;
                        }
                    }
                    return;
                }
                if (state === stateValue('PLAYING', 1)) {
                    ytPlaybackStarted = true;
                    SoundscapePlayer.clearStatus(player);
                    SoundscapePlayer.setPlaying(player);
                } else if (state === stateValue('BUFFERING', 3)) {
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                } else if (
                    state === stateValue('PAUSED', 2)
                    || state === stateValue('ENDED', 0)
                    || state === stateValue('CUED', 5)
                ) {
                    ytPlaybackStarted = false;
                    if (videoSync && (state === stateValue('ENDED', 0) || state === stateValue('CUED', 5))) {
                        videoSync.cancelResume();
                    }
                    SoundscapePlayer.clearStatus(player);
                    SoundscapePlayer.setPaused(player);
                }
            }

            function initYT() {
                if (signal.aborted || ytPlayer) {
                    return;
                }

                ytContainer.innerHTML = '<div id="' + playerId + '"></div>';
                ytContainer.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;';

                ytPlayer = new YT.Player(playerId, {
                    height: '1',
                    width: '1',
                    videoId: videoId,
                    playerVars: {
                        autoplay: autoplay ? 1 : 0,
                        loop: 0,
                        start: startSec > 0 ? startSec : undefined,
                        end: hasRange ? endSec : undefined,
                        playsinline: 1
                    },
                    events: {
                        onReady: function (event) {
                            if (videoSync && signal.aborted) {
                                return;
                            }
                            ytReady = true;
                            if (videoSync) {
                                videoSync.refresh();
                                if (videoSync.blocksPlayback()) {
                                    videoSync.onPlaying();
                                }
                            }
                            if (coverPending) {
                                coverPending = false;
                                coverSilentStart();
                                return;
                            }
                            if (autoplay || player._apeironYTPlayWhenReady) {
                                player._apeironYTPlayWhenReady = false;
                                if (!videoSync || !videoSync.blocksPlayback()) {
                                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                                    event.target.playVideo();
                                } else {
                                    SoundscapePlayer.setPaused(player);
                                }
                            } else {
                                SoundscapePlayer.setPaused(player);
                            }
                        },
                        onStateChange: function (event) {
                            if (!videoSync || !signal.aborted) {
                                applyYTState(event.data);
                            }
                        },
                        onError: function () {
                            if (videoSync) {
                                if (signal.aborted) {
                                    return;
                                }
                                videoSync.cancelResume();
                            }
                            SoundscapePlayer.setPaused(player);
                            SoundscapePlayer.setStatus(player, 'error', errorMessage);
                        },
                        onAutoplayBlocked: function () {
                            if (videoSync && !signal.aborted) {
                                videoSync.cancelResume();
                                SoundscapePlayer.setPaused(player);
                                SoundscapePlayer.setStatus(player, 'error', errorMessage);
                            }
                        }
                    }
                });
                player._apeironYTPlayer = ytPlayer;
            }

            function toggleYT() {
                if (!ytPlayer || !ytPlayer.getPlayerState) {
                    player._apeironYTPlayWhenReady = true;
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                    return;
                }

                var state = ytPlayer.getPlayerState();
                if (state === stateValue('PLAYING', 1) || state === stateValue('BUFFERING', 3)) {
                    ytPlayer.pauseVideo();
                    SoundscapePlayer.setPaused(player);
                    SoundscapePlayer.clearStatus(player);
                } else {
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                    ytPlayer.playVideo();
                }
            }

            player.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (videoSync) {
                    videoSync.manualInteraction();
                    wasYTPlayingOnHide = false;
                }
                toggleYT();
            }, { signal: signal });

            document.addEventListener('apeiron:cover:opening', function (event) {
                var detail = event.detail || {};

                if (coverStartMode === 'cover_click') {
                    coverPlayNow();
                    return;
                }

                coverUnlock();
                document.addEventListener(detail.openedEventName || 'apeiron:cover:opened', function () {
                    coverGoAudible();
                }, { once: true, signal: signal });
            }, { signal: signal });

            signal.addEventListener('abort', function () {
                if (coverFade) {
                    window.clearInterval(coverFade);
                    coverFade = 0;
                }
            });

            if (pauseHidden) {
                document.addEventListener('visibilitychange', function () {
                    if (!ytPlayer || !ytPlayer.getPlayerState) {
                        return;
                    }

                    if (document.visibilityState === 'hidden') {
                        var state = ytPlayer.getPlayerState();
                        wasYTPlayingOnHide = state === stateValue('PLAYING', 1)
                            || (state === stateValue('BUFFERING', 3) && (!videoSync || ytPlaybackStarted));
                        if (wasYTPlayingOnHide) {
                            ytPlayer.pauseVideo();
                            SoundscapePlayer.setPaused(player);
                        }
                    } else if (document.visibilityState === 'visible' && wasYTPlayingOnHide) {
                        if (!videoSync || !videoSync.blocksPlayback()) {
                            SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                            ytPlayer.playVideo();
                        }
                        wasYTPlayingOnHide = false;
                    }
                }, { signal: signal });
            }

            if (window.YT && window.YT.Player) {
                initYT();
            } else {
                var originalCallback = window.onYouTubeIframeAPIReady;
                window.onYouTubeIframeAPIReady = function () {
                    if (typeof originalCallback === 'function') {
                        originalCallback();
                    }
                    initYT();
                };

                if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
                    var tag = document.createElement('script');
                    tag.src = 'https://www.youtube.com/iframe_api';
                    document.head.appendChild(tag);
                }
            }

            if (!autoplay) {
                SoundscapePlayer.setPaused(player);
            }
        },

        cleanupWidget: function (player) {
            if (!player) {
                return;
            }

            if (player.id && playersById.get(player.id) === player) {
                playersById.delete(player.id);
            }

            if (player._apeironAbort) {
                player._apeironAbort.abort();
                player._apeironAbort = null;
            }

            var audio = player.querySelector(SELECTOR.audio);
            if (audio) {
                audio.pause();
                audio.currentTime = 0;
            }

            if (player._apeironYTPlayer) {
                try {
                    if (player._apeironYTPlayer.getPlayerState && player._apeironYTPlayer.getPlayerState() === 1) {
                        player._apeironYTPlayer.stopVideo();
                    }
                    player._apeironYTPlayer.destroy();
                } catch (error) {
					// Elementor may remove the iframe before cleanup runs.
                }
                player._apeironYTPlayer = null;
            }

            player.classList.remove('is-playing', 'is-loading', 'has-error');
            player.removeAttribute('aria-busy');
            player._apeironYTPlayWhenReady = false;
            SoundscapePlayer.setPaused(player);

            var status = player.querySelector(SELECTOR.status);
            if (status && !player.classList.contains('is-empty')) {
                status.textContent = '';
                status.classList.remove('is-visible');
            }
        },

        findPlayers: function (scope) {
            var container = scope && scope.jquery ? scope[0] : scope;
            if (!container) {
                return [];
            }

            var players = container.querySelectorAll
                ? Array.from(container.querySelectorAll(SELECTOR.root))
                : [];

            if (container.matches && container.matches(SELECTOR.root)) {
                players.unshift(container);
            }

            return players;
        },

        initializeScope: function (scope) {
            SoundscapePlayer.findPlayers(scope).forEach(function (player) {
                var previous = player.id ? playersById.get(player.id) : null;
                if (previous && previous !== player) {
                    SoundscapePlayer.cleanupWidget(previous);
                }
                if (player.dataset.apeironSoundscapeInit === 'yes') {
                    return;
                }
                SoundscapePlayer.initWidget(player);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        SoundscapePlayer.findPlayers(document).forEach(function (player) {
            SoundscapePlayer.initWidget(player);
        });
    });

    window.addEventListener('elementor/frontend/init', function () {
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-soundscape.default',
            SoundscapePlayer.initializeScope
        );
    });
}());
