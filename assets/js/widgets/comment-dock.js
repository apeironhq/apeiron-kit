(function ($) {
    'use strict';

    const findAll = (root, selector) => {
        const scope = root && root.querySelectorAll ? root : document;
        const items = Array.from(scope.querySelectorAll(selector));

        if (scope !== document && scope.matches && scope.matches(selector)) {
            items.unshift(scope);
        }

        return items;
    };

    const playSafely = (video) => {
        const result = video.play();
        if (result && typeof result.catch === 'function') {
            result.catch(() => {});
        }
    };

    const i18n = (key, fallback) => {
        const dict = (window.ApeironKit && ApeironKit.i18n) || {};
        return dict[key] || fallback;
    };

    // Play comment sticker videos only while on screen. Avoids dozens of
    // <video> elements autoplaying at once on long guestbooks (mobile CPU/battery).
    const LazyVideo = {
        observer: null,
        seen: typeof WeakSet !== 'undefined' ? new WeakSet() : null,
        observed: new Set(),

        ensure() {
            if (this.observer || !('IntersectionObserver' in window)) {
                return;
            }
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    const video = entry.target;
                    if (entry.isIntersecting) {
                        playSafely(video);
                    } else if (!video.paused) {
                        video.pause();
                    }
                });
            }, { threshold: 0.25 });
        },

        observe(root = document) {
            const videos = findAll(root, 'video[data-apeiron-lazy-video]');
            if (!videos.length) {
                return;
            }
            if (!('IntersectionObserver' in window)) {
                videos.forEach(playSafely);
                return;
            }
            this.ensure();
            videos.forEach((video) => {
                if (this.seen && this.seen.has(video)) {
                    return;
                }
                if (this.seen) {
                    this.seen.add(video);
                }
                this.observer.observe(video);
                this.observed.add(video);
            });
        },

        pruneDetached() {
            if (!this.observer) {
                return;
            }
            this.observed.forEach((video) => {
                if (video.isConnected) {
                    return;
                }
                this.observer.unobserve(video);
                video.pause();
                this.observed.delete(video);
                if (this.seen) {
                    this.seen.delete(video);
                }
            });
        },
    };

    // Accessible modal behaviour: trap Tab inside the dialog, close on Escape,
    // and restore focus to the element that opened it.
    const FocusTrap = {
        selector: 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',

		getFocusableElements(modal) {
			return Array.from(modal.querySelectorAll(FocusTrap.selector))
				.filter((el) => el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement);
		},

        activate(modal, closeFn, trigger = document.activeElement) {
            if (!modal) {
                return;
            }
            const onKey = (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
					event.stopPropagation();
                    closeFn();
                    return;
                }
                if (event.key !== 'Tab') {
                    return;
                }
                const focusables = FocusTrap.getFocusableElements(modal);
                if (!focusables.length) {
                    return;
                }
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            };
            modal.addEventListener('keydown', onKey);
            modal._apeironTrap = { onKey, trigger };
        },

        release(modal, restoreFocus = true) {
            const trap = modal && modal._apeironTrap;
            if (!trap) {
                return;
            }
            modal.removeEventListener('keydown', trap.onKey);
            delete modal._apeironTrap;
            if (restoreFocus && trap.trigger && typeof trap.trigger.focus === 'function' && document.contains(trap.trigger)) {
                trap.trigger.focus();
            }
        },
    };

    const StickerPicker = {
        scrollLocks: new Set(),
        scrollLockState: null,
        scrollLockObserver: null,

        getDuration(popover, mode = 'open') {
            if (!popover || !window.getComputedStyle) {
                return 180;
            }

            const property = mode === 'close' ? '--apeiron-sticker-popup-close-duration' : '--apeiron-sticker-popup-duration';
            const raw = window.getComputedStyle(popover).getPropertyValue(property).trim();
            if (!raw) {
                return 180;
            }

            if (raw.endsWith('ms')) {
                return Math.max(0, parseFloat(raw) || 0);
            }

            if (raw.endsWith('s')) {
                return Math.max(0, (parseFloat(raw) || 0) * 1000);
            }

            return Math.max(0, parseFloat(raw) || 180);
        },

        getMediaMarkup(option) {
            const media = option.querySelector('img, video');
            return media ? media.cloneNode(true) : null;
        },

        updatePreview(field, option) {
            const preview = field ? field.querySelector('[data-sticker-preview]') : null;
            const clear = field ? field.querySelector('[data-sticker-clear]') : null;
            if (!preview) {
                return;
            }

            preview.innerHTML = '';
            if (!option) {
                preview.hidden = true;
                if (clear) {
                    clear.hidden = true;
                }
                return;
            }

            const media = StickerPicker.getMediaMarkup(option);
            if (media) {
                if (media.tagName && media.tagName.toLowerCase() === 'video') {
                    media.autoplay = true;
                    media.muted = true;
                    media.loop = true;
                    media.playsInline = true;
					playSafely(media);
                }
                preview.appendChild(media);
            }
            preview.hidden = false;
            if (clear) {
                clear.hidden = false;
            }
        },

        lockScroll(element) {
            if (element) {
                StickerPicker.scrollLocks.add(element);
            }
            if (StickerPicker.scrollLocks.size && !StickerPicker.scrollLockObserver && window.MutationObserver) {
                // Dock observers cannot see removal of the dock or its ancestors.
                StickerPicker.scrollLockObserver = new MutationObserver(() => StickerPicker.pruneDetached());
                StickerPicker.scrollLockObserver.observe(document, { childList: true, subtree: true });
            }
            if (StickerPicker.scrollLockState) {
                return;
            }

            const scrollbarWidth = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
            StickerPicker.scrollLockState = {
                bodyPaddingRight: document.body.style.paddingRight,
                htmlLocked: document.documentElement.classList.contains('apeiron-kit-sticker-lock-scroll'),
                bodyLocked: document.body.classList.contains('apeiron-kit-sticker-lock-scroll'),
            };

            if (scrollbarWidth > 0) {
                const bodyPadding = parseFloat(window.getComputedStyle(document.body).paddingRight) || 0;
                document.body.style.paddingRight = `${bodyPadding + scrollbarWidth}px`;
            }

            document.documentElement.classList.add('apeiron-kit-sticker-lock-scroll');
            document.body.classList.add('apeiron-kit-sticker-lock-scroll');
        },

        unlockScroll(element) {
            if (element) {
                StickerPicker.scrollLocks.delete(element);
            }
            StickerPicker.scrollLocks.forEach((locked) => {
                if (!locked.isConnected) {
                    StickerPicker.scrollLocks.delete(locked);
                    FocusTrap.release(locked, false);
                    if (locked._apeironCloseTimer) {
                        window.clearTimeout(locked._apeironCloseTimer);
                        locked._apeironCloseTimer = null;
                    }
                }
            });
            if (StickerPicker.scrollLocks.size) {
                return;
            }

            if (StickerPicker.scrollLockObserver) {
                StickerPicker.scrollLockObserver.disconnect();
                StickerPicker.scrollLockObserver = null;
            }

            if (StickerPicker.scrollLockState) {
                document.documentElement.classList.toggle('apeiron-kit-sticker-lock-scroll', StickerPicker.scrollLockState.htmlLocked);
                document.body.classList.toggle('apeiron-kit-sticker-lock-scroll', StickerPicker.scrollLockState.bodyLocked);
                document.body.style.paddingRight = StickerPicker.scrollLockState.bodyPaddingRight;
                StickerPicker.scrollLockState = null;
            }
        },

        close(field) {
            const popover = field ? field.querySelector('[data-sticker-popover]') : null;
            if (popover) {
				if (popover._apeironCloseTimer) {
					window.clearTimeout(popover._apeironCloseTimer);
				}
                popover.classList.remove('is-open');
                popover.classList.add('is-closing');
				popover.setAttribute('aria-hidden', 'true');
				FocusTrap.release(popover);
				popover.querySelectorAll('video').forEach((video) => video.pause());
				popover._apeironCloseTimer = window.setTimeout(() => {
					if (popover.classList.contains('is-open')) {
						return;
					}
                    popover.hidden = true;
                    popover.classList.remove('is-closing');
					popover._apeironCloseTimer = null;
                    StickerPicker.unlockScroll(popover);
                }, StickerPicker.getDuration(popover, 'close'));
            }
        },

        pruneDetached() {
            StickerPicker.unlockScroll();
        },

        clearField(field) {
            if (!field) {
                return;
            }

            field.querySelectorAll('.apeiron-kit-sticker-option.is-selected').forEach((active) => {
                active.classList.remove('is-selected');
            });

            const inputSrc = field.querySelector('input[name="sticker_src"]');
            const inputType = field.querySelector('input[name="sticker_type"]');
            if (inputSrc) inputSrc.value = '';
            if (inputType) inputType.value = '';
            StickerPicker.updatePreview(field, null);
        },

        selectOption(option) {
            const picker = option.closest('.apeiron-kit-sticker-picker');
            const field = option.closest('.apeiron-kit-sticker-field');
            const inputSrc = field ? field.querySelector('input[name="sticker_src"]') : null;
            const inputType = field ? field.querySelector('input[name="sticker_type"]') : null;
            if (!picker || !field || !inputSrc || !inputType) {
                return;
            }

            const alreadySelected = option.classList.contains('is-selected');
            field.querySelectorAll('.apeiron-kit-sticker-option.is-selected').forEach((active) => {
                active.classList.remove('is-selected');
            });

            if (alreadySelected) {
                inputSrc.value = '';
                inputType.value = '';
                StickerPicker.updatePreview(field, null);
                return;
            }

            option.classList.add('is-selected');
            inputSrc.value = option.dataset.src || '';
            inputType.value = option.dataset.type || 'image';
            StickerPicker.updatePreview(field, option);
            StickerPicker.close(field);
        },

        init(root = document) {
            findAll(root, '.apeiron-kit-sticker-picker').forEach((picker) => {
                if (picker.dataset.apeironStickerPickerReady === 'yes') return;
                const field = picker.closest('.apeiron-kit-sticker-field');
                if (!field) {
                    return;
                }

                const inputSrc = field.querySelector('input[name="sticker_src"]');
                const inputType = field.querySelector('input[name="sticker_type"]');
                if (!inputSrc || !inputType) {
                    return;
                }

                picker.dataset.apeironStickerPickerReady = 'yes';
				field.querySelectorAll('[data-sticker-tab]').forEach((tab) => {
					const active = tab.classList.contains('is-active');
					tab.setAttribute('aria-selected', active ? 'true' : 'false');
					tab.tabIndex = active ? 0 : -1;
				});
				field.querySelectorAll('[data-sticker-panel]').forEach((panel) => {
					panel.hidden = !panel.classList.contains('is-active');
				});
            });

            // Delegated handlers avoid cloning every sticker option on Elementor re-render.
            if (StickerPicker._delegationInit) return;
            StickerPicker._delegationInit = true;

            document.addEventListener('click', (event) => {
                const openBtn = event.target.closest('[data-sticker-open]');
                if (openBtn) {
                    const field = openBtn.closest('.apeiron-kit-sticker-field');
                    const popover = field ? field.querySelector('[data-sticker-popover]') : null;
                    if (popover) {
                        event.preventDefault();
						const focusOnOpen = event.detail === 0;
						if (popover._apeironCloseTimer) {
							window.clearTimeout(popover._apeironCloseTimer);
							popover._apeironCloseTimer = null;
						}
                        popover.hidden = false;
						popover.setAttribute('aria-hidden', 'false');
						if (popover.dataset?.backgroundScroll !== 'yes') {
							StickerPicker.lockScroll(popover);
						}
                        window.requestAnimationFrame(() => {
                            if (!popover.isConnected) return;
							popover.classList.add('is-open');
							popover.classList.remove('is-closing');
							popover.querySelectorAll('video').forEach(playSafely);
							if (!popover._apeironTrap) FocusTrap.activate(popover, () => StickerPicker.close(field), openBtn);
							const first = focusOnOpen ? FocusTrap.getFocusableElements(popover)[0] : null;
							if (first) first.focus();
                        });
                    }
                    return;
                }

                const closeBtn = event.target.closest('[data-sticker-close]');
                if (closeBtn) {
                    event.preventDefault();
                    StickerPicker.close(closeBtn.closest('.apeiron-kit-sticker-field'));
                    return;
                }

                const clearBtn = event.target.closest('[data-sticker-clear]');
                if (clearBtn) {
                    event.preventDefault();
                    StickerPicker.clearField(clearBtn.closest('.apeiron-kit-sticker-field'));
                    return;
                }

                const tab = event.target.closest('[data-sticker-tab]');
                if (tab) {
                    const field = tab.closest('.apeiron-kit-sticker-field');
                    const key = tab.dataset.stickerTab || '';
                    if (field && key) {
                        event.preventDefault();
                        field.querySelectorAll('[data-sticker-tab]').forEach((item) => {
                            item.classList.toggle('is-active', item === tab);
							item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
							item.tabIndex = item === tab ? 0 : -1;
                        });
                        field.querySelectorAll('[data-sticker-panel]').forEach((panel) => {
                            panel.classList.toggle('is-active', panel.dataset.stickerPanel === key);
							panel.hidden = panel.dataset.stickerPanel !== key;
                        });
                    }
                    return;
                }

                const btn = event.target.closest('.apeiron-kit-sticker-option');
                if (!btn) {
                    return;
                }

                const picker = btn.closest('.apeiron-kit-sticker-picker');
                if (!picker) {
                    return;
                }

                const field = picker.closest('.apeiron-kit-sticker-field');
                const inputSrc = field ? field.querySelector('input[name="sticker_src"]') : null;
                const inputType = field ? field.querySelector('input[name="sticker_type"]') : null;
                if (!inputSrc || !inputType) {
                    return;
                }

                if (!event.defaultPrevented) {
                    event.preventDefault();
                    event.stopPropagation();
                    StickerPicker.selectOption(btn);
                }
            }, true);

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }
                document.querySelectorAll('.apeiron-kit-sticker-popover:not([hidden])').forEach((popover) => {
					StickerPicker.close(popover.closest('.apeiron-kit-sticker-field'));
                });
            });
        },
    };

    const Comments = {
		paginationStates: new WeakMap(),
		itemStates: new WeakMap(),
		nonceRefresh: null,

        parseMap(dock) {
            if (!dock || !dock.dataset.attendanceMap) {
                return {};
            }
            try {
                return JSON.parse(dock.dataset.attendanceMap);
            } catch (e) {
                return {};
            }
        },

        showNotice(form, dock, type, message) {
            if (!form) {
                return;
            }

            const position = form.dataset.noticePosition || 'after_button';
            const notice = document.createElement('div');
            notice.className = `apeiron-kit-comment-notice is-${type} is-position-${position}`;
            notice.setAttribute('role', 'alert');
            notice.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
            notice.textContent = message;

            const scope = dock || form;
            scope.querySelectorAll('.apeiron-kit-comment-notice').forEach((oldNotice) => oldNotice.remove());

            if (position === 'toast' && dock) {
                notice.classList.add('is-toast');
                dock.appendChild(notice);
            } else if (position === 'before_comments' && dock) {
                const list = dock.querySelector('.apeiron-kit-comment-list');
                if (list) {
                    list.parentNode.insertBefore(notice, list);
                } else {
                    form.appendChild(notice);
                }
            } else {
                const submit = form.querySelector('button[type="submit"]');
                if (submit && submit.parentNode === form) {
                    submit.insertAdjacentElement('afterend', notice);
                } else {
                    form.appendChild(notice);
                }
            }

            window.setTimeout(() => notice.remove(), type === 'error' ? 5000 : 4000);
        },

        setSubmitLoading(form, loading, text = i18n('sending', 'Mengirim...')) {
            if (!form) {
                return;
            }

            const submit = form.querySelector('button[type="submit"]');
            if (!submit) {
                return;
            }

            if (loading) {
                if (!submit.dataset.originalText) {
                    submit.dataset.originalText = submit.textContent;
                }
                submit.textContent = text;
                submit.disabled = true;
                submit.setAttribute('aria-disabled', 'true');
                return;
            }

            if (submit.dataset.originalText) {
                submit.textContent = submit.dataset.originalText;
                delete submit.dataset.originalText;
            }
            submit.disabled = false;
            submit.removeAttribute('aria-disabled');
        },

        removeEmptyState(list) {
            if (!list) {
                return;
            }

            list.querySelectorAll(':scope > .apeiron-kit-comment-empty').forEach((item) => item.remove());
            list.classList.remove('is-empty');
			list.hidden = false;
        },

        ensureEmptyState(list) {
            if (!list) {
                return;
            }
            Comments.removeEmptyState(list);
			if (!list.querySelector(':scope > .apeiron-kit-comment-item')) list.hidden = true;
        },

		// Parse CommentRenderer HTML instead of duplicating comment markup in JS.
		itemFromHtml(html, comment = null) {
            if (!html || typeof html !== 'string') {
                return null;
            }
            const template = document.createElement('template');
            template.innerHTML = html.trim();
            const node = template.content.firstElementChild;
			if (!node || !node.classList || !node.classList.contains('apeiron-kit-comment-item')) return null;
			if (comment && typeof comment.rawMessage === 'string') {
				const decoder = document.createElement('textarea');
				decoder.innerHTML = comment.rawMessage;
				Comments.itemStates.set(node, { rawMessage: decoder.value, attendanceValue: comment.attendanceValue || '' });
			} else if (node.dataset.rawMessage !== undefined) {
				Comments.itemStates.set(node, { rawMessage: node.dataset.rawMessage, attendanceValue: node.dataset.attendanceValue || '' });
			}
			return node;
        },

        syncAttendanceFitWidths(root = document, forceReset = false) {
            const docks = findAll(root, '.apeiron-comment-dock.is-attendance-items-fit-equal');

            docks.forEach((dock) => {
                const items = Array.from(dock.querySelectorAll('.apeiron-kit-attendance-summary-item'));
                if (!items.length) {
                    return;
                }

                const currentWidth = dock.style.getPropertyValue('--apeiron-attendance-fit-width');
                if (forceReset) {
                    dock.style.removeProperty('--apeiron-attendance-fit-width');
                    items.forEach((item) => {
                        if (item.style.width) {
                            item.style.removeProperty('width');
                        }
                        if (item.style.flexBasis) {
                            item.style.removeProperty('flex-basis');
                        }
                    });
                }

                let maxWidth = 0;
                items.forEach((item) => {
                    const rectWidth = item.getBoundingClientRect ? item.getBoundingClientRect().width : 0;
                    const scrollWidth = item.scrollWidth || 0;
                    maxWidth = Math.max(maxWidth, Math.ceil(Math.max(rectWidth, scrollWidth)));
                });

                if (maxWidth > 0) {
                    const nextWidth = `${maxWidth}px`;
                    if (forceReset || currentWidth !== nextWidth) {
                        dock.style.setProperty('--apeiron-attendance-fit-width', nextWidth);
                    }
                }
            });
        },

        refreshNonce() {
            if (!window.ApeironKit || !ApeironKit.restUrl) {
                return Promise.reject(new Error('Missing REST config'));
            }

			if (Comments.nonceRefresh) return Comments.nonceRefresh;

            Comments.nonceRefresh = fetch(`${ApeironKit.restUrl}/nonce`, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Unable to refresh nonce');
                    }
                    return response.json();
                })
                .then((result) => {
                    if (!result || !result.nonce) {
                        throw new Error('Invalid nonce response');
                    }
                    ApeironKit.nonce = result.nonce;
                    return result.nonce;
				})
				.finally(() => { Comments.nonceRefresh = null; });
			return Comments.nonceRefresh;
        },

        request(path, options = {}, retried = false) {
            if (!window.ApeironKit || !ApeironKit.restUrl) {
                return Promise.reject(new Error('Missing REST config'));
            }

            const headers = Object.assign({
                'Content-Type': 'application/json',
                'X-WP-Nonce': ApeironKit.nonce,
            }, options.headers || {});

            return fetch(`${ApeironKit.restUrl}${path}`, Object.assign({}, options, {
                credentials: 'same-origin',
                headers,
            })).then((response) => {
				return response.json().catch(() => ({})).then((body) => {
					if (response.status === 403 && !retried && ['rest_cookie_invalid_nonce', 'rest_cookie_expired_nonce'].includes(body.code)) {
						return Comments.refreshNonce().then(() => Comments.request(path, options, true));
					}
					if (!response.ok) {
						const error = new Error(body.message || 'Invalid response');
						error.code = body.code || '';
						throw error;
					}
					return body;
				});
            });
        },

        getRenderContext(dock) {
            const map = Comments.parseMap(dock);
            const display = dock ? dock.dataset.attendanceDisplay : 'icon';
            const renderOptions = {
                showAvatar: !dock || dock.dataset.showAvatar !== 'no',
                showDate: !dock || dock.dataset.showDate !== 'no',
                usePagination: !!dock && dock.dataset.usePagination === 'yes',
                listDisplayMode: dock ? (dock.dataset.listDisplayMode || 'auto') : 'auto',
            };
            const dateFormat = dock ? (dock.dataset.dateFormat || dock.getAttribute('data-date-format') || 'relative') : 'relative';
            return { map, display, renderOptions, dateFormat };
        },

        getPrimaryForm(dock) {
            return dock ? dock.querySelector('[data-apeiron-comment-form]') : null;
        },

		applyReplyTarget(payload, item) {
			if (!payload || !item) return payload;
			payload.parent_id = parseInt(item.dataset.commentId || '0', 10) || 0;
			if (item.dataset.postId) payload.post_id = item.dataset.postId;
			if (item.dataset.elementId) payload.element_id = item.dataset.elementId;
			if (item.dataset.targetToken) payload.target_token = item.dataset.targetToken;
			return payload;
		},

        getInvitedGuestName() {
            try {
                const params = new URLSearchParams(window.location.search || '');
                const name = (params.get('to') || '').trim();
                return name.length > 120 ? name.slice(0, 120) : name;
            } catch (e) {
                return '';
            }
        },

        getGuestRequiredMessage(dock) {
            const message = dock ? (dock.dataset.guestRequiredMessage || '').trim() : '';
            return message || '* Ucapan hanya dapat dikirim oleh tamu yang menerima undangan ini.';
        },

        getReplyPopupTitle(dock) {
            const message = dock ? (dock.dataset.replyPopupTitle || '').trim() : '';
            return message || 'Balas Komentar';
        },

        getReplySuccessMessage(dock) {
            const message = dock ? (dock.dataset.replySuccessMessage || '').trim() : '';
            return message || 'Balasan dikirim.';
        },

        getReplyErrorMessage(dock) {
            const message = dock ? (dock.dataset.replyErrorMessage || '').trim() : '';
            return message || 'Gagal mengirim balasan.';
        },

        applyInvitedGuest(dock) {
            if (!dock) {
                return;
            }

            if (dock.dataset.guestNameFromUrl === 'no') {
                return;
            }

            const guestName = (dock.dataset.invitedGuest || Comments.getInvitedGuestName()).trim();
            const form = Comments.getPrimaryForm(dock);
            if (!guestName) {
                if (form) {
                    form.classList.add('is-guest-name-missing');
                    const submit = form.querySelector('button[type="submit"]');
                    if (submit) {
                        submit.disabled = true;
                        submit.setAttribute('aria-disabled', 'true');
                    }
                    const nameWrap = form.querySelector('input[name="name"]')?.closest('.apeiron-kit-form-field-wrap');
                    if (nameWrap) {
                        nameWrap.hidden = true;
                    }
                    if (!form.querySelector('[data-invited-guest-note]')) {
                        const note = document.createElement('div');
                        note.className = 'apeiron-kit-invited-guest-note is-blocking';
                        note.dataset.invitedGuestNote = '';
                        const noteText = document.createElement('p');
                        noteText.textContent = Comments.getGuestRequiredMessage(dock);
                        note.appendChild(noteText);
                        const anchor = nameWrap || form.firstElementChild;
                        if (anchor && anchor.parentNode === form) {
                            form.insertBefore(note, anchor.nextSibling);
                        } else {
                            form.prepend(note);
                        }
                    }
                }
                return;
            }

            dock.dataset.invitedGuest = guestName;
            const nameInput = form ? form.querySelector('input[name="name"]') : null;
            const hiddenGuestName = form ? form.querySelector('input[name="invited_guest_name"]') : null;
            const hiddenGuestToken = form ? form.querySelector('input[name="invited_guest_token"]') : null;
            if (nameInput) {
                nameInput.value = guestName;
                nameInput.readOnly = true;
                nameInput.setAttribute('aria-readonly', 'true');
            }
            if (hiddenGuestName) {
                hiddenGuestName.value = guestName;
            }
            if (hiddenGuestToken && dock.dataset.invitedGuestToken) {
                hiddenGuestToken.value = dock.dataset.invitedGuestToken;
            }

            const fieldWrap = nameInput ? nameInput.closest('.apeiron-kit-form-field-wrap') : null;
            if (fieldWrap) {
                fieldWrap.querySelectorAll('[data-invited-guest-note]').forEach((note) => note.remove());
            }
        },

        showInlineNotice(item, type, message) {
            const form = item ? item.querySelector('.apeiron-kit-comment-inline-form, .apeiron-kit-comment-edit-form') : null;
            const dock = item ? item.closest('.apeiron-comment-dock') : null;
            Comments.showNotice(form || Comments.getPrimaryForm(dock), dock, type, message);
        },

		closeModals(dock) {
			if (!dock) return;
			dock.querySelectorAll('.apeiron-kit-comment-modal').forEach((modal) => {
				if (modal._apeironClose) modal._apeironClose();
				else modal.remove();
			});
		},

		getRawMessage(item) {
			const state = Comments.itemStates.get(item);
			if (state && typeof state.rawMessage === 'string') return state.rawMessage;
			if (item.dataset.rawMessage !== undefined) return item.dataset.rawMessage;
			const text = item.querySelector(':scope > .apeiron-kit-comment-wrapper .apeiron-kit-comment-text');
			if (!text) return '';
			const readNode = (node) => {
				if (node.nodeType === 3) return node.nodeValue;
				if (node.nodeName === 'BR') return '\n';
				if (node.classList && node.classList.contains('apeiron-kit-comment-sticker')) return '';
				return Array.from(node.childNodes || []).map(readNode).join('');
			};
			const paragraphs = Array.from(text.children).filter((node) => node.matches('p'));
			return (paragraphs.length ? paragraphs : [text]).map(readNode).join('\n\n');
		},

		setRawMessage(item, rawMessage, attendanceValue) {
			const state = Comments.itemStates.get(item) || {};
			state.rawMessage = String(rawMessage || '');
			if (attendanceValue !== undefined) state.attendanceValue = attendanceValue;
			Comments.itemStates.set(item, state);
		},

		getItemAttendance(item) {
			const state = Comments.itemStates.get(item);
			if (state && state.attendanceValue) return state.attendanceValue;
			if (item.dataset.attendanceValue) return item.dataset.attendanceValue;
			const dock = item.closest('.apeiron-comment-dock');
			const pill = item.querySelector(':scope > .apeiron-kit-comment-wrapper .apeiron-kit-comment-pill');
			if (!dock || !pill) return '';
			const match = Array.from(pill.classList).join(' ').match(/status-index-(\d+)/);
			if (!match) return '';
			const entry = Object.entries(Comments.parseMap(dock)).find(([, option]) => String(option.index) === match[1]);
			return entry ? entry[0] : '';
		},

        openReplyForm(item) {
            const dock = item.closest('.apeiron-comment-dock');
            const primaryForm = Comments.getPrimaryForm(dock);
            if (!dock || !primaryForm || item.dataset.canReply !== 'yes') {
                return;
            }

            if ((dock.dataset.commentReplyMode || 'popup') === 'popup') {
                Comments.openReplyModal(item);
                return;
            }

            item.querySelectorAll(':scope > .apeiron-kit-comment-inline-form').forEach((old) => old.remove());
            const form = document.createElement('form');
            form.className = 'apeiron-kit-comment-inline-form';
            form.dataset.inlineType = 'reply';

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.name = 'name';
            nameInput.autocomplete = 'off';
            nameInput.placeholder = dock.dataset.replyNamePlaceholder || 'Nama Lengkap';
			nameInput.setAttribute('aria-label', nameInput.placeholder);
            nameInput.required = true;
            if (dock.dataset.invitedGuest) {
                nameInput.value = dock.dataset.invitedGuest;
                nameInput.readOnly = true;
                nameInput.setAttribute('aria-readonly', 'true');
            }

            const messageInput = document.createElement('textarea');
            messageInput.name = 'message';
            messageInput.rows = 2;
            messageInput.autocomplete = 'off';
            messageInput.placeholder = dock.dataset.replyMessagePlaceholder || 'Tulis balasan...';
			messageInput.setAttribute('aria-label', messageInput.placeholder);
            messageInput.required = true;

            form.appendChild(nameInput);
            form.appendChild(messageInput);

            const preferredStickerMode = (dock.dataset.commentReplyMode || 'popup') === 'popup' ? 'inline' : '';
            const stickerTemplate = preferredStickerMode
                ? dock.querySelector(`template[data-reply-sticker-template="${preferredStickerMode}"]`)
                : dock.querySelector('template[data-reply-sticker-template]');
            if (dock.dataset.replyStickerEnabled === 'yes' && stickerTemplate && stickerTemplate.content) {
                const stickerNode = stickerTemplate.content.cloneNode(true);
                form.appendChild(stickerNode);
            }

            const actions = document.createElement('div');
            actions.className = 'apeiron-kit-comment-inline-actions';
            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.textContent = dock.dataset.replySubmitText || 'Kirim Balasan';
            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.dataset.inlineCancel = '';
            cancelButton.textContent = dock.dataset.replyCancelText || 'Batal';
            actions.appendChild(submitButton);
            actions.appendChild(cancelButton);
            form.appendChild(actions);

            const mainName = primaryForm.querySelector('input[name="name"]');
            const replyName = form.querySelector('input[name="name"]');
            if (mainName && mainName.value && replyName) {
                replyName.value = mainName.value;
                if (mainName.readOnly) {
                    replyName.readOnly = true;
                    replyName.setAttribute('aria-readonly', 'true');
                }
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (form.classList.contains('is-loading')) return;
                if (dock.dataset.guestNameFromUrl === 'yes' && !dock.dataset.invitedGuest) {
                    Comments.showInlineNotice(item, 'error', Comments.getGuestRequiredMessage(dock));
                    return;
                }
                const data = new FormData(primaryForm);
                const payload = Object.fromEntries(data.entries());
                payload.name = form.querySelector('input[name="name"]').value;
                payload.message = form.querySelector('textarea[name="message"]').value;
				Comments.applyReplyTarget(payload, item);
                payload.attendance = '';
                payload.attendance_label = '';
                payload.sticker_src = (form.querySelector('input[name="sticker_src"]') || {}).value || '';
                payload.sticker_type = (form.querySelector('input[name="sticker_type"]') || {}).value || '';

                form.classList.add('is-loading');
                Comments.request('/comments', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                }).then((result) => {
                    if (!result.comment) {
                        Comments.showInlineNotice(item, 'success', result.message || Comments.getReplySuccessMessage(dock));
                        form.remove();
                        return;
                    }
					Comments.invalidatePagination(dock);
                    const replyItem = Comments.itemFromHtml(result.comment.html, result.comment);
                    const replies = item.querySelector(':scope > .apeiron-kit-comment-replies');
                    if (replies && replyItem) {
                        replies.hidden = false;
                        replies.appendChild(replyItem);
                        LazyVideo.observe(replyItem);
                    }
                    form.remove();
                    Comments.showInlineNotice(item, 'success', Comments.getReplySuccessMessage(dock));
                }).catch((error) => {
                    Comments.showInlineNotice(item, 'error', (error && error.message) || Comments.getReplyErrorMessage(dock));
                }).finally(() => form.classList.remove('is-loading'));
            });

            form.querySelector('[data-inline-cancel]').addEventListener('click', () => form.remove());
            const replies = item.querySelector(':scope > .apeiron-kit-comment-replies');
            if (replies) {
                item.insertBefore(form, replies);
            } else {
                item.appendChild(form);
            }
            StickerPicker.init(form);
            form.querySelector('textarea[name="message"]').focus();
        },

        buildReplyFields(dock, primaryForm) {
            const fragment = document.createDocumentFragment();

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.name = 'name';
            nameInput.autocomplete = 'off';
            nameInput.placeholder = dock.dataset.replyNamePlaceholder || 'Nama Lengkap';
			nameInput.setAttribute('aria-label', nameInput.placeholder);
            nameInput.required = true;

            const mainName = primaryForm ? primaryForm.querySelector('input[name="name"]') : null;
            if (mainName && mainName.value) {
                nameInput.value = mainName.value;
                if (mainName.readOnly) {
                    nameInput.readOnly = true;
                    nameInput.setAttribute('aria-readonly', 'true');
                }
            } else if (dock.dataset.invitedGuest) {
                nameInput.value = dock.dataset.invitedGuest;
                nameInput.readOnly = true;
                nameInput.setAttribute('aria-readonly', 'true');
            }

            const messageInput = document.createElement('textarea');
            messageInput.name = 'message';
            messageInput.rows = 3;
            messageInput.autocomplete = 'off';
            messageInput.placeholder = dock.dataset.replyMessagePlaceholder || 'Tulis balasan...';
			messageInput.setAttribute('aria-label', messageInput.placeholder);
            messageInput.required = true;

            fragment.appendChild(nameInput);
            fragment.appendChild(messageInput);

            const stickerTemplate = dock.querySelector('template[data-reply-sticker-template="inline"]')
                || dock.querySelector('template[data-reply-sticker-template]');
            if (dock.dataset.replyStickerEnabled === 'yes' && stickerTemplate && stickerTemplate.content) {
                fragment.appendChild(stickerTemplate.content.cloneNode(true));
            }

            return fragment;
        },

        submitReply(item, form) {
            const dock = item.closest('.apeiron-comment-dock');
            const primaryForm = Comments.getPrimaryForm(dock);
            if (!dock || !primaryForm) {
                return Promise.reject(new Error('Form utama tidak ditemukan.'));
            }
            if (dock.dataset.guestNameFromUrl === 'yes' && !dock.dataset.invitedGuest) {
                return Promise.reject(new Error(Comments.getGuestRequiredMessage(dock)));
            }

            const data = new FormData(primaryForm);
            const payload = Object.fromEntries(data.entries());
            payload.name = form.querySelector('input[name="name"]').value;
            if (dock.dataset.guestNameFromUrl === 'yes' && dock.dataset.invitedGuest) {
                payload.name = dock.dataset.invitedGuest;
                payload.invited_guest_name = dock.dataset.invitedGuest;
                payload.invited_guest_token = dock.dataset.invitedGuestToken || payload.invited_guest_token || '';
            }
            payload.message = form.querySelector('textarea[name="message"]').value;
			Comments.applyReplyTarget(payload, item);
            payload.attendance = '';
            payload.attendance_label = '';
            payload.sticker_src = (form.querySelector('input[name="sticker_src"]') || {}).value || '';
            payload.sticker_type = (form.querySelector('input[name="sticker_type"]') || {}).value || '';

            return Comments.request('/comments', {
                method: 'POST',
                body: JSON.stringify(payload),
            }).then((result) => {
                if (!result.comment) {
                    return result;
                }

				Comments.invalidatePagination(dock);
                const replyItem = Comments.itemFromHtml(result.comment.html, result.comment);
                const replies = item.querySelector(':scope > .apeiron-kit-comment-replies');
                if (replies && replyItem) {
                    replies.hidden = false;
                    replies.appendChild(replyItem);
                    LazyVideo.observe(replyItem);
                }

                return result;
            });
        },

        openReplyModal(item) {
            const dock = item.closest('.apeiron-comment-dock');
            const primaryForm = Comments.getPrimaryForm(dock);
            if (!dock || !primaryForm) {
                return;
            }

			const opener = document.activeElement;
			Comments.closeModals(dock);

            const modal = document.createElement('div');
            modal.className = 'apeiron-kit-comment-modal';
            modal.dataset.popupPosition = dock.dataset.modalPosition || 'center';
            modal.dataset.popupScroll = dock.dataset.modalScrollArea || 'overlay';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');

            const form = document.createElement('form');
            form.className = 'apeiron-kit-comment-dialog apeiron-kit-comment-reply-dialog';

            const title = document.createElement('h3');
            title.className = 'apeiron-kit-comment-modal-title';
			title.id = `apeiron-comment-reply-title-${item.dataset.commentId}`;
            title.textContent = Comments.getReplyPopupTitle(dock);
			modal.setAttribute('aria-labelledby', title.id);
            form.appendChild(title);
            form.appendChild(Comments.buildReplyFields(dock, primaryForm));

            const actions = document.createElement('div');
            actions.className = 'apeiron-kit-comment-inline-actions';
            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.textContent = dock.dataset.replySubmitText || 'Kirim Balasan';
            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.dataset.inlineCancel = '';
            cancelButton.textContent = dock.dataset.replyCancelText || 'Batal';
            actions.appendChild(submitButton);
            actions.appendChild(cancelButton);
            form.appendChild(actions);
            modal.appendChild(form);

            const close = () => {
                FocusTrap.release(modal);
                modal.remove();
                StickerPicker.unlockScroll(modal);
            };
			modal._apeironClose = close;
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    close();
                }
            });
            cancelButton.addEventListener('click', close);

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (form.classList.contains('is-loading')) return;
                form.classList.add('is-loading');
                Comments.setSubmitLoading(form, true);

                Comments.submitReply(item, form).then((result) => {
                    close();
                    const message = result && result.comment
                        ? Comments.getReplySuccessMessage(dock)
                        : ((result && result.message) || Comments.getReplySuccessMessage(dock));
                    Comments.showInlineNotice(item, 'success', message);
                }).catch((error) => {
                    Comments.showInlineNotice(item, 'error', (error && error.message) || Comments.getReplyErrorMessage(dock));
                }).finally(() => {
                    form.classList.remove('is-loading');
                    Comments.setSubmitLoading(form, false);
                });
            });

            dock.appendChild(modal);
            if (dock.dataset.modalBackgroundScroll !== 'yes') {
                StickerPicker.lockScroll(modal);
            }
            StickerPicker.init(modal);
            form.querySelector('textarea[name="message"]').focus();
			FocusTrap.activate(modal, close, opener);
        },

        openEditForm(item) {
            const dock = item.closest('.apeiron-comment-dock');
            if (dock && (dock.dataset.commentEditMode || 'popup') === 'popup') {
                Comments.openEditModal(item);
                return;
            }

            const text = item.querySelector(':scope > .apeiron-kit-comment-wrapper .apeiron-kit-comment-text');
            if (!text || item.querySelector(':scope > .apeiron-kit-comment-edit-form')) {
                return;
            }

            const originalHtml = text.innerHTML;
			const raw = Comments.getRawMessage(item);
            const form = document.createElement('form');
            form.className = 'apeiron-kit-comment-edit-form';
            form.innerHTML = `
                <textarea name="message" rows="3" required></textarea>
                <div class="apeiron-kit-comment-inline-actions">
                    <button type="submit">${i18n('save', 'Simpan')}</button>
                    <button type="button" data-inline-cancel>${i18n('cancel', 'Batal')}</button>
                </div>
            `;
            form.querySelector('textarea').value = raw;
            text.hidden = true;
            text.insertAdjacentElement('afterend', form);

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (form.classList.contains('is-loading')) return;
                form.classList.add('is-loading');

                Comments.request(`/comments/${item.dataset.commentId}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ message: form.querySelector('textarea').value }),
                }).then((result) => {
                    if (result.comment && result.comment.message) {
                        Comments.updateCommentText(item, result.comment);
                    }
					Comments.invalidatePagination(dock);
                    text.hidden = false;
                    form.remove();
                }).catch((error) => {
                    text.hidden = false;
                    text.innerHTML = originalHtml;
                    form.remove();
                    Comments.showInlineNotice(item, 'error', error.message || i18n('updateError', 'Gagal memperbarui komentar.'));
                }).finally(() => form.classList.remove('is-loading'));
            });

            form.querySelector('[data-inline-cancel]').addEventListener('click', () => {
                text.hidden = false;
                form.remove();
            });
            form.querySelector('textarea').focus();
        },

        openEditModal(item) {
            const dock = item.closest('.apeiron-comment-dock');
            const text = item.querySelector(':scope > .apeiron-kit-comment-wrapper .apeiron-kit-comment-text');
            if (!dock || !text) {
                return;
            }

			const opener = document.activeElement;
			Comments.closeModals(dock);

            const originalHtml = text.innerHTML;
            const modal = document.createElement('div');
            modal.className = 'apeiron-kit-comment-modal apeiron-kit-comment-edit-modal';
            modal.dataset.popupPosition = dock.dataset.modalPosition || 'center';
            modal.dataset.popupScroll = dock.dataset.modalScrollArea || 'overlay';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
			modal.setAttribute('aria-labelledby', `apeiron-comment-edit-title-${item.dataset.commentId}`);
            modal.innerHTML = `
                <form class="apeiron-kit-comment-dialog apeiron-kit-comment-edit-dialog">
					<h3 class="apeiron-kit-comment-modal-title" id="apeiron-comment-edit-title-${item.dataset.commentId}">${i18n('editTitle', 'Edit Komentar')}</h3>
                    <textarea name="message" rows="5" required></textarea>
                    <div class="apeiron-kit-comment-inline-actions">
                        <button type="submit">${i18n('save', 'Simpan')}</button>
                        <button type="button" data-inline-cancel>${i18n('cancel', 'Batal')}</button>
                    </div>
                </form>
            `;

            const form = modal.querySelector('form');
            const textarea = modal.querySelector('textarea');
			textarea.setAttribute('aria-label', i18n('editTitle', 'Edit Komentar'));
			textarea.value = Comments.getRawMessage(item);

            const close = () => {
                FocusTrap.release(modal);
                modal.remove();
                StickerPicker.unlockScroll(modal);
            };
			modal._apeironClose = close;
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    close();
                }
            });
            modal.querySelector('[data-inline-cancel]').addEventListener('click', close);

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (form.classList.contains('is-loading')) return;
                form.classList.add('is-loading');

                Comments.request(`/comments/${item.dataset.commentId}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ message: textarea.value }),
                }).then((result) => {
                    if (result.comment && result.comment.message) {
                        Comments.updateCommentText(item, result.comment);
                    }
					Comments.invalidatePagination(dock);
                    close();
                }).catch((error) => {
                    text.innerHTML = originalHtml;
                    close();
                    Comments.showInlineNotice(item, 'error', error.message || i18n('updateError', 'Gagal memperbarui komentar.'));
                }).finally(() => form.classList.remove('is-loading'));
            });

            dock.appendChild(modal);
            if (dock.dataset.modalBackgroundScroll !== 'yes') {
                StickerPicker.lockScroll(modal);
            }
            textarea.focus();
			FocusTrap.activate(modal, close, opener);
        },

        updateCommentText(item, comment) {
            const text = item.querySelector(':scope > .apeiron-kit-comment-wrapper .apeiron-kit-comment-text');
            if (!text || !comment || !comment.message) {
                return;
            }

            const dock = item.closest('.apeiron-comment-dock');
            const baseStickerPosition = dock ? (dock.dataset.commentStickerPosition || 'avatar') : 'avatar';
            const stickerPosition = item.dataset.parentId && item.dataset.parentId !== '0' && baseStickerPosition === 'avatar' ? 'top' : baseStickerPosition;
            text.innerHTML = comment.message;
			if (typeof comment.rawMessage === 'string') {
				const decoder = document.createElement('textarea');
				decoder.innerHTML = comment.rawMessage;
				Comments.setRawMessage(item, decoder.value, comment.attendanceValue);
			}

            if (comment.sticker && comment.sticker.src && (stickerPosition === 'beside_text' || stickerPosition === 'top' || stickerPosition === 'below_text')) {
                const media = Comments.buildStickerMedia(comment.sticker, comment.name, dock);
                if (media) {
                    if (stickerPosition === 'beside_text') {
                        text.prepend(media);
                    } else if (stickerPosition === 'below_text') {
                        text.insertAdjacentElement('afterend', media);
                    } else {
                        const content = item.querySelector(':scope > .apeiron-kit-comment-wrapper .apeiron-kit-comment-content');
                        const info = content ? content.querySelector('.apeiron-kit-comment-info') : null;
                        if (content && info && !content.querySelector(':scope > .apeiron-kit-comment-sticker')) {
                            content.insertBefore(media, info);
                        }
                    }
                }
            }
        },

        deleteItem(item) {
            Comments.openDeleteModal(item);
        },

        openDeleteModal(item) {
            const dock = item.closest('.apeiron-comment-dock');
            if (!dock) {
                return;
            }

			const opener = document.activeElement;
			Comments.closeModals(dock);

            const modal = document.createElement('div');
            modal.className = 'apeiron-kit-comment-modal apeiron-kit-comment-delete-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
			modal.setAttribute('aria-labelledby', `apeiron-comment-delete-title-${item.dataset.commentId}`);
			modal.setAttribute('aria-describedby', `apeiron-comment-delete-description-${item.dataset.commentId}`);
            modal.innerHTML = `
                <div class="apeiron-kit-comment-dialog apeiron-kit-comment-delete-dialog">
					<h3 class="apeiron-kit-comment-modal-title" id="apeiron-comment-delete-title-${item.dataset.commentId}">${i18n('deleteTitle', 'Hapus Komentar?')}</h3>
					<p class="apeiron-kit-comment-modal-description" id="apeiron-comment-delete-description-${item.dataset.commentId}">${i18n('deleteDescription', 'Komentar yang dihapus tidak bisa dikembalikan.')}</p>
                    <div class="apeiron-kit-comment-inline-actions">
                        <button type="button" data-delete-confirm>${i18n('confirmDelete', 'Hapus')}</button>
                        <button type="button" data-inline-cancel>${i18n('cancel', 'Batal')}</button>
                    </div>
                </div>
            `;

            const getCloseDuration = () => {
                if (!window.getComputedStyle) {
                    return 240;
                }
                const raw = window.getComputedStyle(modal).getPropertyValue('--apeiron-delete-modal-close-duration').trim();
                if (!raw) return 240;
                if (raw.endsWith('ms')) return Math.max(0, parseFloat(raw) || 0);
                if (raw.endsWith('s')) return Math.max(0, (parseFloat(raw) || 0) * 1000);
                return Math.max(0, parseFloat(raw) || 240);
            };
            const close = () => {
                if (modal.classList.contains('is-closing')) {
                    return;
                }
                FocusTrap.release(modal);
                modal.classList.remove('is-open');
                modal.classList.add('is-closing');
                window.setTimeout(() => modal.remove(), getCloseDuration());
            };
			modal._apeironClose = close;
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    close();
                }
            });
            modal.querySelector('[data-inline-cancel]').addEventListener('click', close);
            modal.querySelector('[data-delete-confirm]').addEventListener('click', () => {
                const confirmButton = modal.querySelector('[data-delete-confirm]');
                if (confirmButton.disabled) {
                    return;
                }
                confirmButton.disabled = true;

				const attendanceValue = Comments.getItemAttendance(item);
				const hasPagination = !!dock.querySelector('.apeiron-kit-comment-pagination');
				Comments.request(`/comments/${item.dataset.commentId}`, {
					method: 'DELETE',
				}).then(() => {
					if (!hasPagination) {
						Comments.updateAttendanceSummary(dock, { attendanceValue }, -1);
						Comments.removeItemFromList(item);
						return null;
					}
					return Comments.refetchPagination(dock, 'current').then(() => {
						Comments.updateAttendanceSummary(dock, {
							attendanceValue,
						}, -1);
					}, () => {
						const pagination = dock.querySelector('.apeiron-kit-comment-pagination');
						const state = pagination ? Comments.paginationStates.get(pagination) : null;
						if (state) {
							state.total = Math.max(0, state.total - 1);
							state.pages = Math.max(1, Math.ceil(state.total / state.perPage));
							state.cache.clear();
							Comments.updatePaginationControls(state);
						}
						Comments.updateAttendanceSummary(dock, { attendanceValue }, -1);
						Comments.removeItemFromList(item);
					});
				}).then(() => {
                    close();
                }).catch((error) => {
                    confirmButton.disabled = false;
                    close();
                    Comments.showInlineNotice(item, 'error', error.message || i18n('deleteError', 'Gagal menghapus komentar.'));
                });
            });

            dock.appendChild(modal);
            window.requestAnimationFrame(() => modal.classList.add('is-open'));
            const cancelBtn = modal.querySelector('[data-inline-cancel]');
            if (cancelBtn) {
                cancelBtn.focus();
            }
			FocusTrap.activate(modal, close, opener);
        },

        removeItemFromList(item) {
            const dock = item.closest('.apeiron-comment-dock');
            const list = dock ? dock.querySelector('.apeiron-kit-comment-list') : null;
            const parentReplies = item.parentElement && item.parentElement.classList.contains('apeiron-kit-comment-replies') ? item.parentElement : null;
            item.remove();
            if (parentReplies && !parentReplies.children.length) {
                parentReplies.hidden = true;
            }
            Comments.ensureEmptyState(list);
        },

        buildStickerMedia(sticker, name, dockElement) {
            if (!sticker || !sticker.src) {
                return null;
            }

			const stickerWrapper = document.createElement('span');
			stickerWrapper.className = 'apeiron-kit-comment-sticker';

            if (sticker.type === 'video') {
                const video = document.createElement('video');
                video.src = sticker.src;
                video.dataset.apeironLazyVideo = '';
                video.preload = 'none';
                video.loop = true;
                video.muted = true;
                video.playsInline = true;
                video.className = 'apeiron-kit-sticker-avatar';
                stickerWrapper.appendChild(video);
                LazyVideo.observe(video);
            } else {
                const img = document.createElement('img');
                img.src = sticker.src;
                img.alt = name || 'sticker';
                img.className = 'apeiron-kit-sticker-avatar';
                stickerWrapper.appendChild(img);
            }

            return stickerWrapper;
        },

        initPagination(root = document) {
            findAll(root, '.apeiron-kit-comment-pagination').forEach((pagination) => {
                const list = pagination.previousElementSibling;
                if (!list || !list.classList.contains('apeiron-kit-comment-list')) {
                    return;
                }
				if (Comments.paginationStates.has(pagination)) return;

				const initialItems = Array.from(list.children).filter((child) => child.classList && child.classList.contains('apeiron-kit-comment-item'));
                const perPage = parseInt(pagination.dataset.perPage || 5, 10);
				const currentPage = Math.max(1, parseInt(pagination.dataset.current || 1, 10));
				const state = {
					pagination,
					list,
					perPage,
					total: Math.max(0, parseInt(pagination.dataset.total || initialItems.length, 10)),
					pages: 1,
					currentPage,
					cache: new Map([[currentPage, initialItems.slice(0, perPage).map((item) => ({ html: item.outerHTML }))]]),
					controller: null,
					generation: 0,
					pending: null,
					loadingButton: null,
					prefetches: new Map(),
				};
				state.pages = Math.max(1, Math.ceil(state.total / perPage));
				Comments.paginationStates.set(pagination, state);
				pagination.dataset.initialized = 'yes';
				const prevBtn = pagination.querySelector('.apeiron-kit-comment-prev');
				const nextBtn = pagination.querySelector('.apeiron-kit-comment-next');
				if (prevBtn) prevBtn.addEventListener('click', () => Comments.showPaginationPage(state, state.currentPage - 1, false, prevBtn).catch(() => {}));
				if (nextBtn) nextBtn.addEventListener('click', () => Comments.showPaginationPage(state, state.currentPage + 1, false, nextBtn).catch(() => {}));
				Comments.updatePaginationControls(state);
				if (state.pages > 1) {
					window.setTimeout(() => Comments.prefetchPaginationPage(state, 2), 100);
				}
			});
		},

		paginationPath(state, page) {
			const params = new URLSearchParams({
				mode: 'tree',
				post_id: state.pagination.dataset.postId || '0',
				element_id: state.pagination.dataset.elementId || '',
				page: String(page),
				per_page: String(state.perPage),
			});
			return '/comments?' + params.toString();
		},

		prefetchPaginationPage(state, page) {
			if (!state.pagination.isConnected || state.cache.has(page) || state.prefetches.has(page)) return;
			const promise = Comments.request(Comments.paginationPath(state, page), { method: 'GET' })
				.then((result) => {
					state.total = Math.max(0, parseInt(result.total || 0, 10));
					state.pages = Math.max(1, parseInt(result.pages || Math.ceil(state.total / state.perPage), 10));
					state.cache.set(page, (result && result.items) || []);
					Comments.updatePaginationControls(state);
					return result;
				})
				.catch(() => null)
				.finally(() => state.prefetches.delete(page));
			state.prefetches.set(page, promise);
		},

		updatePaginationControls(state) {
			const { pagination } = state;
			const busy = !!state.pending;
			const prev = pagination.querySelector('.apeiron-kit-comment-prev');
			const next = pagination.querySelector('.apeiron-kit-comment-next');
			if (prev) prev.disabled = busy || state.currentPage <= 1;
			if (next) next.disabled = busy || state.currentPage >= state.pages;
			if (prev) prev.classList.toggle('is-loading', busy && state.loadingButton === prev);
			if (next) next.classList.toggle('is-loading', busy && state.loadingButton === next);
			const current = pagination.querySelector('.apeiron-kit-comment-page-current');
			const total = pagination.querySelector('.apeiron-kit-comment-page-total');
			if (current) current.textContent = state.currentPage;
			if (total) total.textContent = state.pages;
			pagination.dataset.current = state.currentPage;
			pagination.dataset.total = state.total;
			pagination.dataset.loading = busy ? 'yes' : 'no';
			pagination.setAttribute('aria-busy', busy ? 'true' : 'false');
			state.list.setAttribute('aria-busy', busy ? 'true' : 'false');
			pagination.style.display = state.total > state.perPage ? '' : 'none';
		},

		renderPaginationEntries(state, entries) {
			const nodes = (entries || []).map((entry) => entry && entry.html ? Comments.itemFromHtml(entry.html, entry) : null).filter(Boolean);
			state.list.replaceChildren(...nodes);
			Comments.ensureEmptyState(state.list);
			nodes.forEach((node) => LazyVideo.observe(node));
		},

		showPaginationPage(state, page, force = false, trigger = null) {
			const targetPage = Math.max(1, Math.min(page, state.pages));
			if (!force && state.cache.has(targetPage)) {
				Comments.renderPaginationEntries(state, state.cache.get(targetPage));
				state.currentPage = targetPage;
				Comments.updatePaginationControls(state);
				return Promise.resolve(state.cache.get(targetPage));
			}
			if (!force && state.prefetches.has(targetPage)) {
				state.loadingButton = trigger;
				const promise = state.prefetches.get(targetPage)
					.then(() => Comments.showPaginationPage(state, targetPage))
					.finally(() => {
						if (state.pending && state.pending.promise === promise) {
							state.pending = null;
							state.loadingButton = null;
							Comments.updatePaginationControls(state);
						}
					});
				state.pending = { page: targetPage, promise };
				Comments.updatePaginationControls(state);
				return promise;
			}
			if (state.pending && state.pending.page === targetPage && !force) return state.pending.promise;
			if (state.controller) state.controller.abort();
			const controller = new AbortController();
			const generation = ++state.generation;
			state.controller = controller;
			state.loadingButton = trigger;
			const promise = Comments.request(Comments.paginationPath(state, targetPage), { method: 'GET', signal: controller.signal })
				.then((result) => {
					if (generation !== state.generation) return result;
					state.total = Math.max(0, parseInt(result.total || 0, 10));
					state.pages = Math.max(1, parseInt(result.pages || Math.ceil(state.total / state.perPage), 10));
					if (targetPage > state.pages) return Comments.showPaginationPage(state, state.pages, true);
					const renderEntries = (entries) => Comments.renderPaginationEntries(state, entries);
					const entries = (result && result.items) || [];
					state.cache.set(targetPage, entries);
					renderEntries((result && result.items) || []);
					state.currentPage = targetPage;
					return result;
				})
				.catch((error) => {
					if (error.name !== 'AbortError') {
						const dock = state.pagination.closest('.apeiron-comment-dock');
						Comments.showNotice(Comments.getPrimaryForm(dock), dock, 'error', error.message || i18n('paginationError', 'Gagal memuat komentar.'));
					}
					throw error;
				})
				.finally(() => {
					if (generation !== state.generation) return;
					state.pending = null;
					state.controller = null;
					state.loadingButton = null;
					Comments.updatePaginationControls(state);
				});
			state.pending = { page: targetPage, promise };
			Comments.updatePaginationControls(state);
			return promise;
		},

		invalidatePagination(dock) {
			const pagination = dock ? dock.querySelector('.apeiron-kit-comment-pagination') : null;
			const state = pagination ? Comments.paginationStates.get(pagination) : null;
			if (state) {
				state.cache.clear();
				state.prefetches.clear();
			}
		},

		refetchPagination(dock, page = 1) {
			const pagination = dock ? dock.querySelector('.apeiron-kit-comment-pagination') : null;
			const state = pagination ? Comments.paginationStates.get(pagination) : null;
			if (!state) return Promise.resolve(null);
			state.cache.clear();
			state.prefetches.clear();
			return Comments.showPaginationPage(state, page === 'current' ? state.currentPage : page, true);
		},

        initLoadMore(root = document) {
            findAll(root, '[data-load-more]').forEach((btn) => {
                if (btn.dataset.lmInit === 'yes') {
                    return;
                }
                btn.dataset.lmInit = 'yes';
                btn.addEventListener('click', () => Comments.loadMore(btn));
            });
        },

        loadMore(btn) {
            if (!btn || btn.dataset.loading === 'yes') {
                return;
            }
            const dock = btn.closest('.apeiron-comment-dock');
            const list = dock ? dock.querySelector('.apeiron-kit-comment-list') : null;
            if (!list) {
                return;
            }

            const page = parseInt(btn.dataset.nextPage || '2', 10);
            const perPage = parseInt(btn.dataset.perPage || '10', 10);
            const params = new URLSearchParams({
                mode: 'tree',
                post_id: btn.dataset.postId || '0',
                element_id: btn.dataset.elementId || '',
                page: String(page),
                per_page: String(perPage),
            });

            btn.dataset.loading = 'yes';
            btn.disabled = true;
			btn.setAttribute('aria-busy', 'true');
			list.setAttribute('aria-busy', 'true');

            Comments.request('/comments?' + params.toString(), { method: 'GET' }).then((result) => {
                const items = (result && result.items) || [];
                items.forEach((entry) => {
                    if (!entry || !entry.html) {
                        return;
                    }
                    // Dedupe in case a freshly posted comment shifted the offset.
                    if (entry.id && list.querySelector('[data-comment-id="' + entry.id + '"]')) {
                        return;
                    }
                    const node = Comments.itemFromHtml(entry.html);
                    if (node) {
                        list.appendChild(node);
                        LazyVideo.observe(node);
                    }
                });

                btn.dataset.nextPage = String(page + 1);
                const total = parseInt((result && result.total) || 0, 10);
                const loaded = list.querySelectorAll(':scope > .apeiron-kit-comment-item').length;
                if (!items.length || (total && loaded >= total)) {
                    btn.remove();
                }
			}).catch((error) => {
				Comments.showNotice(Comments.getPrimaryForm(dock), dock, 'error', error.message || i18n('loadMoreError', 'Gagal memuat komentar lainnya.'));
            }).finally(() => {
                btn.dataset.loading = 'no';
                btn.disabled = false;
				btn.setAttribute('aria-busy', 'false');
				list.setAttribute('aria-busy', 'false');
            });
        },

        updateAttendanceSummary(dock, comment, delta = 1) {
            if (!dock || !comment || !comment.attendanceValue) {
                return;
            }

            const escapedValue = window.CSS && window.CSS.escape ? window.CSS.escape(comment.attendanceValue) : String(comment.attendanceValue).replace(/"/g, '\\"');
            const selector = `.apeiron-kit-attendance-summary-count[data-attendance-value="${escapedValue}"]`;
            const count = dock.querySelector(selector);
            if (!count) {
                return;
            }

            const current = parseInt(count.textContent || '0', 10);
			count.textContent = String(Math.max(0, (isNaN(current) ? 0 : current) + delta));
        },

        init(root = document) {
            if (!Comments._actionDelegationInit) {
                Comments._actionDelegationInit = true;
                document.addEventListener('click', (event) => {
                    const actionButton = event.target.closest('[data-comment-action]');
                    if (!actionButton) {
                        return;
                    }

                    const item = actionButton.closest('.apeiron-kit-comment-item');
                    if (!item) {
                        return;
                    }

                    event.preventDefault();
                    const action = actionButton.dataset.commentAction;
                    if (action === 'reply') {
                        Comments.openReplyForm(item);
                    } else if (action === 'edit') {
                        Comments.openEditForm(item);
                    } else if (action === 'delete') {
                        Comments.deleteItem(item);
                    }
                });
            }

            let initializedForm = false;
            findAll(root, '[data-apeiron-comment-form]').forEach((form) => {
                if (form._apeironCommentInit) return;
                form._apeironCommentInit = true;
                initializedForm = true;

                const dock = form.closest('.apeiron-comment-dock');
                Comments.applyInvitedGuest(dock);
                const renderOptions = Comments.getRenderContext(dock).renderOptions;

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    if (form.classList.contains('is-loading')) return;
                    const select = form.querySelector('select[name="attendance"]');
                    const labelInput = form.querySelector('input[name="attendance_label"]');
                    if (select && labelInput) {
                        const option = select.options[select.selectedIndex];
                        labelInput.value = option ? option.text : '';
                    }

                    const data = new FormData(form);
                    const payload = Object.fromEntries(data.entries());
                    if (dock && dock.dataset.guestNameFromUrl === 'yes' && dock.dataset.invitedGuest) {
                        payload.name = dock.dataset.invitedGuest;
                        payload.invited_guest_name = dock.dataset.invitedGuest;
                        payload.invited_guest_token = dock.dataset.invitedGuestToken || payload.invited_guest_token || '';
                    }
                    payload.parent_id = 0;
                    form.classList.add('is-loading');
                    Comments.setSubmitLoading(form, true);
                    const list = dock ? dock.querySelector('.apeiron-kit-comment-list') : null;

                    Comments.request('/comments', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    })
                        .then((result) => {
                            form.reset();
                            Comments.applyInvitedGuest(dock);
                            const stickerField = form.querySelector('.apeiron-kit-sticker-field');
                            if (stickerField) {
                                StickerPicker.clearField(stickerField);
                            }
							const successMessage = result.comment
								? (form.dataset.successMessage || result.message || 'Terima kasih atas ucapannya!')
								: (result.message || form.dataset.successMessage || 'Ucapan menunggu moderasi.');
                            Comments.showNotice(form, dock, 'success', successMessage);

							if (result.comment && list) {
                                Comments.updateAttendanceSummary(dock, result.comment);
								const pagination = dock ? dock.querySelector('.apeiron-kit-comment-pagination') : null;
				if (pagination) {
					return Comments.refetchPagination(dock, 1).catch(() => null);
				}
								const item = Comments.itemFromHtml(result.comment.html, result.comment);
								if (item) {
									Comments.removeEmptyState(list);
									list.prepend(item);
									LazyVideo.observe(item);
								}

                                const limit = parseInt(dock ? (dock.dataset.commentsLimit || 10) : 10, 10);
                                const allItems = Array.from(list.children).filter((child) => child.classList && child.classList.contains('apeiron-kit-comment-item'));

                                if (!renderOptions.usePagination) {
                                    if (renderOptions.listDisplayMode === 'scroll') {
                                        list.classList.add('is-scrollable');
                                        list.classList.remove('apeiron-kit-comment-list-scrollable');
                                    } else if (limit > 0 && allItems.length > limit && !list.classList.contains('apeiron-kit-comment-list-scrollable')) {
                                        list.classList.add('apeiron-kit-comment-list-scrollable');
                                        list.classList.remove('is-scrollable');
                                    }
                                }

                            }
                        })
                        .catch((error) => {
                            const errorMessage = error && error.message && error.message !== 'Invalid response'
                                ? error.message
                                : (form.dataset.errorMessage || 'Gagal mengirim komentar.');
                            Comments.showNotice(form, dock, 'error', errorMessage);
                        })
                        .finally(() => {
                            form.classList.remove('is-loading');
                            Comments.setSubmitLoading(form, false);
                        });
                });
            });
            if (initializedForm) {
                Comments.syncAttendanceFitWidths(root, true);
            }
        },
    };

    const AttendanceFitSync = {
        timer: null,
        resizeObserver: null,
        mutationObserver: null,
        trackedDocks: [],

        schedule(root = document, forceReset = false) {
            const scope = root || document;
            if (!findAll(scope, '.apeiron-comment-dock.is-attendance-items-fit-equal').length) {
                return;
            }

            window.clearTimeout(AttendanceFitSync.timer);
            AttendanceFitSync.timer = window.setTimeout(() => {
                Comments.syncAttendanceFitWidths(scope, forceReset);
            }, 80);
        },

        observe(root = document) {
            const docks = findAll(root, '.apeiron-comment-dock.is-attendance-items-fit-equal');
            if (!docks.length) {
                AttendanceFitSync.pruneDetached();
                return false;
            }

            let changed = false;
            docks.forEach((dock) => {
                if (!AttendanceFitSync.trackedDocks.includes(dock)) {
                    AttendanceFitSync.trackedDocks.push(dock);
                    changed = true;
                }
            });

            if (changed) {
                AttendanceFitSync.rebuildObservers();
            }
            return changed;
        },

        rebuildObservers() {
            const liveDocks = AttendanceFitSync.trackedDocks.filter((dock) =>
                dock.isConnected && dock.matches('.apeiron-comment-dock.is-attendance-items-fit-equal')
            );
            AttendanceFitSync.trackedDocks = liveDocks;

            if (window.ResizeObserver) {
                if (AttendanceFitSync.resizeObserver) {
                    AttendanceFitSync.resizeObserver.disconnect();
                }
                AttendanceFitSync.resizeObserver = new ResizeObserver(() => {
                    AttendanceFitSync.schedule(document);
                });
                liveDocks.forEach((dock) => {
                    AttendanceFitSync.resizeObserver.observe(dock);
                    dock.querySelectorAll('.apeiron-kit-attendance-summary-item').forEach((item) => {
                        AttendanceFitSync.resizeObserver.observe(item);
                    });
                });
            }

            if (!window.MutationObserver) {
                return;
            }

            if (AttendanceFitSync.mutationObserver) {
                AttendanceFitSync.mutationObserver.disconnect();
            }
            AttendanceFitSync.mutationObserver = new MutationObserver((mutations) => {
                const structureChanged = mutations.some((mutation) =>
                    mutation.type === 'childList' ||
                    (mutation.type === 'attributes' && mutation.attributeName === 'class' && mutation.target.matches('.apeiron-comment-dock'))
                );
                if (structureChanged) {
                    AttendanceFitSync.rebuildObservers();
                } else {
                    AttendanceFitSync.schedule(document);
                }
            });
            liveDocks.forEach((dock) => {
                AttendanceFitSync.mutationObserver.observe(dock, {
                    attributes: true,
                    childList: true,
                    subtree: true,
                    attributeFilter: ['class', 'style'],
                });
            });
        },

        pruneDetached() {
            const liveDocks = AttendanceFitSync.trackedDocks.filter((dock) =>
                dock.isConnected && dock.matches('.apeiron-comment-dock.is-attendance-items-fit-equal')
            );
            if (liveDocks.length === AttendanceFitSync.trackedDocks.length) {
                return;
            }
            AttendanceFitSync.trackedDocks = liveDocks;
            AttendanceFitSync.rebuildObservers();
            AttendanceFitSync.schedule(document, true);
        },
    };

    window.addEventListener('resize', () => AttendanceFitSync.schedule(document));

	const bootstrap = (root = document) => {
		StickerPicker.pruneDetached();
		LazyVideo.pruneDetached();
		StickerPicker.init(root);
		Comments.init(root);
		Comments.initPagination(root);
		Comments.initLoadMore(root);
		if (AttendanceFitSync.observe(root)) {
			AttendanceFitSync.schedule(root, true);
		}
		LazyVideo.observe(root);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => bootstrap(document), { once: true });
	} else {
		bootstrap(document);
	}

	let elementorHookRegistered = false;
	const registerElementorHook = () => {
		if (elementorHookRegistered || typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
			return;
		}
		elementorHookRegistered = true;
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/apeiron-comment-dock.default',
			function ($scope) {
					var el = $scope[0] || $scope;
					if (!el) return;
					bootstrap(el);
			}
		);
	};

	jQuery(window).on('elementor/frontend/init', registerElementorHook);
	registerElementorHook();
}(jQuery));
