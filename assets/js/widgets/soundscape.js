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

            function coverGoAudible() {
                coverSilent = false;

                function reveal() {
                    if (audio.readyState > 0) {
                        audio.currentTime = startSec;
                    }
                    audio.muted = coverMuted;
                    audio.volume = 0;

                    var steps = 12;
                    var step = 0;
                    coverFade = window.setInterval(function () {
                        step += 1;
                        audio.volume = Math.min(1, coverVolume * (step / steps));
                        if (step >= steps) {
                            window.clearInterval(coverFade);
                            coverFade = 0;
                            audio.volume = coverVolume;
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

                if (!audio.paused) {
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
                if (!audio.paused) {
                    return;
                }

                coverVolume = audio.volume;
                coverMuted = audio.muted;
                // Silence must land before play() so nothing leaks through.
                audio.volume = 0;
                audio.muted = true;
                coverSilent = true;

                var promise;
                try {
                    promise = audio.play();
                } catch (error) {
                    coverSilent = false;
                    audio.volume = coverVolume;
                    audio.muted = coverMuted;
                    return;
                }

                if (promise && typeof promise.then === 'function') {
                    promise.then(function () {
                        player._apeironAutoplayUnlocked = true;
                    }).catch(function () {
                        coverSilent = false;
                        audio.volume = coverVolume;
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
                SoundscapePlayer.setPaused(player);
            }, { signal: signal });

            audio.addEventListener('ended', function () {
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
                SoundscapePlayer.setPaused(player);
                SoundscapePlayer.setStatus(player, 'error', errorMessage);
            }, { signal: signal });

            player.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (player.classList.contains('is-empty')) {
                    return;
                }

                player._apeironAutoplayUnlocked = true;
                if (audio.paused) {
                    playAudio(true, false).catch(function () {});
                } else {
                    pauseAudio();
                }
            }, { signal: signal });

            if (pauseHidden) {
                var wasPlayingOnHide = false;
                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'hidden') {
                        wasPlayingOnHide = !audio.paused;
                        if (!audio.paused) {
                            pauseAudio();
                        }
                    } else if (document.visibilityState === 'visible' && wasPlayingOnHide) {
                        playAudio(false, true).catch(function () {});
                        wasPlayingOnHide = false;
                    }
                }, { signal: signal });
            }

            if (autoplay) {
                playAudio(false, true).then(function () {
                    player._apeironAutoplayUnlocked = true;
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

            // Cover handoff: the Buka Undangan click is the only user gesture that
            // can unlock playback, so start at volume 0 there and raise it on opened.
            var coverSilent = false;
            var coverArmed = false;
            var coverPending = false;
            var coverParked = false;
            var coverVolume = 100;
            var coverFade = 0;

            function coverSilentStart() {
                if (!ytPlayer || typeof ytPlayer.playVideo !== 'function') {
                    return false;
                }

                try {
                    if (typeof ytPlayer.getVolume === 'function') {
                        coverVolume = ytPlayer.getVolume();
                    }
                    // Silence must land before playback starts so nothing leaks.
                    ytPlayer.setVolume(0);
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
                if (coverArmed) {
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
                    ytPlayer.setVolume(0);
                    if (!coverParked) {
                        ytPlayer.seekTo(startSec, true);
                    }
                    ytPlayer.playVideo();
                } catch (error) {
                    return;
                }

                var steps = 12;
                var step = 0;
                coverFade = window.setInterval(function () {
                    step += 1;
                    try {
                        ytPlayer.setVolume(Math.min(100, coverVolume * (step / steps)));
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
                    SoundscapePlayer.clearStatus(player);
                    SoundscapePlayer.setPlaying(player);
                } else if (state === stateValue('BUFFERING', 3)) {
                    SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                } else if (
                    state === stateValue('PAUSED', 2)
                    || state === stateValue('ENDED', 0)
                    || state === stateValue('CUED', 5)
                ) {
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
                            if (coverPending) {
                                coverPending = false;
                                coverSilentStart();
                                return;
                            }
                            if (autoplay || player._apeironYTPlayWhenReady) {
                                player._apeironYTPlayWhenReady = false;
                                SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                                event.target.playVideo();
                            } else {
                                SoundscapePlayer.setPaused(player);
                            }
                        },
                        onStateChange: function (event) {
                            applyYTState(event.data);
                        },
                        onError: function () {
                            SoundscapePlayer.setPaused(player);
                            SoundscapePlayer.setStatus(player, 'error', errorMessage);
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
                var wasYTPlayingOnHide = false;
                document.addEventListener('visibilitychange', function () {
                    if (!ytPlayer || !ytPlayer.getPlayerState) {
                        return;
                    }

                    if (document.visibilityState === 'hidden') {
                        var state = ytPlayer.getPlayerState();
                        wasYTPlayingOnHide = state === stateValue('PLAYING', 1) || state === stateValue('BUFFERING', 3);
                        if (wasYTPlayingOnHide) {
                            ytPlayer.pauseVideo();
                            SoundscapePlayer.setPaused(player);
                        }
                    } else if (document.visibilityState === 'visible' && wasYTPlayingOnHide) {
                        SoundscapePlayer.setStatus(player, 'loading', loadingMessage);
                        ytPlayer.playVideo();
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
