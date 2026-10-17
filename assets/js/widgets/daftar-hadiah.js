(function () {
	'use strict';

	if (window.ApeironDaftarHadiah) {
		window.ApeironDaftarHadiah.initScope(document);
		return;
	}

	var ROOT = '[data-apeiron-daftar-hadiah]';
	var instances = new WeakMap();
	var active = null;
	var hooksRegistered = false;

	function syncCardNavigation(state) {
		if (!state.root.isConnected) {
			destroy(state.root);
			return;
		}
		var track = state.track;
		var maximum = track.scrollWidth - track.clientWidth;
		state.navigation.hidden = getComputedStyle(track).overflowX !== 'auto' || maximum <= 1;
		if (state.navigation.hidden) {
			return;
		}
		var position = Math.abs(track.scrollLeft);
		var index = 0;
		if (position >= maximum - 1) {
			index = state.cards.length - 1;
		} else if (position > 1) {
			var bounds = track.getBoundingClientRect();
			var rtl = getComputedStyle(track).direction === 'rtl';
			var nearest = Infinity;
			state.cards.forEach(function (card, cardIndex) {
				var rect = card.getBoundingClientRect();
				var distance = Math.abs(rtl ? bounds.right - rect.right : rect.left - bounds.left);
				if (distance < nearest) {
					nearest = distance;
					index = cardIndex;
				}
			});
		}
		state.currentCard = index;
		state.navigation.querySelectorAll('[data-gift-card-index]').forEach(function (dot) {
			dot.setAttribute('aria-current', Number(dot.getAttribute('data-gift-card-index')) === index ? 'true' : 'false');
		});
		var prev = state.navigation.querySelector('[data-gift-card-prev]');
		var next = state.navigation.querySelector('[data-gift-card-next]');
		if (prev) {
			prev.disabled = index === 0;
			next.disabled = index === state.cards.length - 1;
		}
	}

	function navigateToCard(state, index) {
		index = Math.max(0, Math.min(state.cards.length - 1, index));
		var trackBounds = state.track.getBoundingClientRect();
		var cardBounds = state.cards[index].getBoundingClientRect();
		var rtl = getComputedStyle(state.track).direction === 'rtl';
		state.track.scrollTo({
			left: state.track.scrollLeft + (rtl ? cardBounds.right - trackBounds.right : cardBounds.left - trackBounds.left),
			behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
		});
	}

	function initCardNavigation(state) {
		if (!state.track || !state.navigation) {
			return;
		}
		state.queueNavigation = function () {
			if (!state.navigationFrame) {
				state.navigationFrame = window.requestAnimationFrame(function () {
					state.navigationFrame = 0;
					syncCardNavigation(state);
				});
			}
		};
		state.track.addEventListener('scroll', state.queueNavigation, { passive: true });
		if (window.ResizeObserver) {
			state.navigationResize = new window.ResizeObserver(state.queueNavigation);
			state.navigationResize.observe(state.track);
			if (state.cards[0]) {
				state.navigationResize.observe(state.cards[0]);
			}
		} else {
			window.addEventListener('resize', state.queueNavigation);
		}
		state.queueNavigation();
	}

	function collectFormData(form) {
		var data = new FormData(form);
		return {
			name: String(data.get('guest_name') || '').trim(),
			whatsapp: String(data.get('whatsapp') || '').trim(),
			message: String(data.get('message') || '').trim(),
		};
	}

	function normalizeWhatsApp(value) {
		if (!/^\+?[0-9\s().-]+$/.test(value)) {
			return '';
		}
		var phone = value.replace(/[\s().-]/g, '').replace(/^\+/, '');
		if (phone.charAt(0) === '0') {
			phone = '62' + phone.slice(1);
		}
		return /^[1-9]\d{7,14}$/.test(phone) ? phone : '';
	}

	function validateFormData(data) {
		var errors = {};
		if (!data.name || data.name.length > 100 || /[\u0000-\u001f\u007f]/.test(data.name)) {
			errors.guest_name = 'name';
		}
		if (!normalizeWhatsApp(data.whatsapp)) {
			errors.whatsapp = 'whatsapp';
		}
		if (data.message.length > 1000) {
			errors.message = 'message';
		}
		return errors;
	}

	/** Client context is informational; WordPress/GAS assign the final receipt. */
	function buildReservation(identity, gift, data) {
		return {
			schema_version: 1,
			website_id: identity.website_id,
			invitation_id: identity.invitation_id,
			registry_id: identity.registry_id,
			gift_id: gift.gift_id,
			gift_name: gift.name,
			quantity: 1,
			guest: { id: null, name: data.name, whatsapp: normalizeWhatsApp(data.whatsapp) },
			message: data.message,
			timestamp: new Date().toISOString(),
			status: 'unsubmitted',
		};
	}

	function integrationRequest(config, operation, data) {
		var integration = config.integration;
		if (!integration || !integration[operation + '_url']) {
			return Promise.reject(new Error(config.messages.error));
		}
		var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
		var timeout = controller ? window.setTimeout(function () { controller.abort(); }, 90000) : null;
		var headers = { 'Content-Type': 'application/json', 'X-Apeiron-Nonce': integration.nonce };
		if (integration.rest_nonce) { headers['X-WP-Nonce'] = integration.rest_nonce; }
		var scope = Object.assign({}, config.identity, { target_token: integration.target_token });
		return window.fetch(integration[operation + '_url'], {
			method: 'POST', credentials: 'same-origin', mode: 'same-origin', headers: headers,
			body: JSON.stringify(Object.assign({}, data || {}, scope)), signal: controller ? controller.signal : undefined,
		}).then(function (response) {
			return response.json().then(function (body) {
				if (!response.ok) {
					var error = new Error(body && typeof body.message === 'string' ? body.message : config.messages.error);
					error.code = body && body.code;
					throw error;
				}
				return body;
			});
		}).catch(function (error) {
			if (error.code) { throw error; }
			throw new Error(config.messages.error);
		}).finally(function () { window.clearTimeout(timeout); });
	}

	/** Always use WordPress; the browser never receives GAS credentials/URLs. */
	function sendReservation(reservation, config) {
		if (!config || !config.submission_configured) { return Promise.resolve({ status: 'not_configured' }); }
		return integrationRequest(config, 'reserve', reservation);
	}

	function updateAvailabilityBadge(state, badge, gift, popup) {
		if (!badge) { return; }
		var available = gift.availability === 'available';
		badge.classList.toggle('apeiron-daftar-hadiah__status--available', available);
		badge.classList.toggle('apeiron-daftar-hadiah__status--unavailable', !available);
		var labels = state.dialog.querySelector('[data-gift-availability]');
		var status = popup ? badge.querySelector('[data-gift-status]') : badge.querySelector(':scope > span:not(.apeiron-daftar-hadiah__quantity)');
		if (status && labels) { status.textContent = labels.getAttribute(available ? 'data-available-label' : 'data-unavailable-label'); }
		var quantity = badge.querySelector('.apeiron-daftar-hadiah__quantity');
		if (quantity) {
			quantity.firstElementChild.textContent = String(gift.available_quantity);
			quantity.lastElementChild.textContent = String(gift.initial_quantity);
		}
		if (state.stockSynced) { badge.title = state.config.messages.stockSynced; }
	}

	function applyAvailability(state, rows) {
		if (!Array.isArray(rows)) { throw new Error(state.config.messages.error); }
		var byId = new Map();
		rows.forEach(function (row) {
			if (!row || typeof row.gift_id !== 'string' || byId.has(row.gift_id) || !Number.isInteger(row.initial_quantity) || !Number.isInteger(row.available_quantity)
				|| row.available_quantity < 0 || row.available_quantity > row.initial_quantity) { throw new Error(state.config.messages.error); }
			byId.set(row.gift_id, row);
		});
		if (state.config.gifts.some(function (gift) { return !byId.has(gift.gift_id); })) { throw new Error(state.config.messages.error); }
		state.stockSynced = true;
		state.stockError = '';
		state.root.setAttribute('data-availability-source', 'gas');
		state.config.availability_source = 'gas';
		state.config.reservation_status = 'connected';
		state.config.gifts.forEach(function (gift) {
			var row = byId.get(gift.gift_id);
			gift.initial_quantity = row.initial_quantity;
			gift.available_quantity = row.available_quantity;
			gift.availability = row.available_quantity > 0 ? 'available' : 'unavailable';
			var card = Array.from(state.cards).find(function (element) { return element.getAttribute('data-gift-id') === gift.gift_id; });
			if (card) {
				updateAvailabilityBadge(state, card.querySelector('.apeiron-daftar-hadiah__status'), gift, false);
				var button = card.querySelector('[data-gift-select]');
				if (button) { button.disabled = gift.availability !== 'available'; }
			}
		});
		if (state.selected && state.dialog.open) {
			updateAvailabilityBadge(state, state.dialog.querySelector('[data-gift-availability]'), state.selected, true);
			setLoading(state, state.busy);
		}
	}

	function refreshAvailability(state, force) {
		if (!state.config.submission_configured) { return Promise.resolve(); }
		if (state.stockPromise && !force) { return state.stockPromise; }
		var sequence = ++state.stockSequence;
		var promise = integrationRequest(state.config, 'availability').then(function (data) {
			if (instances.get(state.root) === state && state.root.isConnected && state.stockSequence === sequence) { applyAvailability(state, data.gifts); }
		}).catch(function (error) {
			if (instances.get(state.root) !== state || !state.root.isConnected || state.stockSequence !== sequence) { return; }
			state.stockError = error.message || state.config.messages.error;
			if (state.dialog.open) { showNotice(state, state.stockError, true); }
		}).finally(function () { if (state.stockPromise === promise) { state.stockPromise = null; } });
		state.stockPromise = promise;
		return promise;
	}

	function requestId() {
		var bytes = new Uint8Array(16);
		window.crypto.getRandomValues(bytes);
		return Array.from(bytes).map(function (byte) { return ('0' + byte.toString(16)).slice(-2); }).join('');
	}

	function hideNotice(state) {
		window.clearTimeout(state.noticeTimer);
		state.noticeTimer = null;
		state.notice.hidden = true;
		state.notice.textContent = '';
		state.notice.classList.remove('is-error');
		state.notice.classList.remove('is-success');
	}

	function showNotice(state, message, error, autoDismiss) {
		hideNotice(state);
		state.notice.textContent = message;
		state.notice.classList.toggle('is-error', !!error);
		state.notice.classList.toggle('is-success', state.confirmed && !error);
		state.notice.hidden = false;
		if (autoDismiss) {
			state.noticeTimer = window.setTimeout(function () { hideNotice(state); }, 5000);
		}
	}

	function showErrors(state, errors) {
		var first = null;
		['guest_name', 'whatsapp', 'message'].forEach(function (key) {
			var input = state.form.elements.namedItem(key);
			var error = state.form.querySelector('[data-gift-error="' + key + '"]');
			var invalid = !!errors[key];
			input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
			error.textContent = invalid ? state.config.messages[errors[key]] : '';
			error.hidden = !invalid;
			if (invalid && !first) {
				first = input;
			}
		});
		return first;
	}

	function setLoading(state, loading) {
		state.busy = loading;
		state.form.setAttribute('aria-busy', loading ? 'true' : 'false');
		state.submit.setAttribute('aria-busy', loading ? 'true' : 'false');
		state.submit.disabled = loading || state.confirmed || !!(state.selected && state.selected.available_quantity < 1);
		state.submit.setAttribute('data-gift-confirmed', state.confirmed ? 'true' : 'false');
		state.submit.textContent = loading ? state.config.messages.loading : (state.confirmed ? state.config.messages.confirmed : state.confirmText);
		['guest_name', 'whatsapp', 'message'].forEach(function (name) {
			state.form.elements.namedItem(name).readOnly = loading || state.confirmed;
		});
	}

	function showSuccess(state, message) {
		hideNotice(state);
		state.scroll.hidden = true;
		state.footer.hidden = true;
		state.success.hidden = false;
		state.success.querySelector('[data-gift-success-message]').textContent = message;
		state.dialog.setAttribute('aria-labelledby', state.success.querySelector('h3').id);
		state.dialog.setAttribute('aria-describedby', state.success.querySelector('[data-gift-success-message]').id);
		state.dialog.querySelector('[data-gift-close]').focus({ preventScroll: true });
		window.clearTimeout(state.successTimer);
		state.successTimer = window.setTimeout(function () {
			state.successTimer = null;
			if (instances.get(state.root) === state && state.root.isConnected && state.dialog.open && state.confirmed) {
				closeDialog(state);
			}
		}, 3000);
	}

	function resetForm(state) {
		state.operation += 1;
		window.clearTimeout(state.successTimer);
		state.successTimer = null;
		state.success.hidden = true;
		state.success.querySelector('[data-gift-success-message]').textContent = '';
		state.scroll.hidden = false;
		state.footer.hidden = false;
		state.dialog.setAttribute('aria-labelledby', state.labelledBy);
		state.dialog.setAttribute('aria-describedby', state.describedBy);
		state.swipeStart = null;
		state.confirmed = false;
		state.requestId = '';
		state.requestSignature = '';
		state.form.reset();
		showErrors(state, {});
		hideNotice(state);
		setLoading(state, false);
	}

	function afterClose(state) {
		state.mobileFooter.removeEventListener('change', state.footerModeChange);
		if (state.observer) {
			state.observer.disconnect();
			state.observer = null;
		}
		resetForm(state);
		state.selected = null;
		if (active === state) {
			active = null;
		}
		if (state.opener && state.opener.isConnected) {
			state.opener.focus({ preventScroll: true });
		}
		state.opener = null;
	}

	function closeDialog(state) {
		if (state.dialog.open) {
			state.dialog.close();
		}
		afterClose(state);
	}

	function setPhoto(state, index) {
		if (!state.photos.length) {
			return;
		}
		state.photoIndex = (index + state.photos.length) % state.photos.length;
		var photo = state.photos[state.photoIndex];
		var image = state.dialog.querySelector('[data-gift-image]');
		image.src = photo.url;
		image.alt = photo.alt || (state.selected ? state.selected.name : '');
		state.dialog.querySelector('[data-gift-photo-count]').textContent = (state.photoIndex + 1) + '/' + state.photos.length;
		state.dialog.querySelectorAll('[data-gift-thumbnail]').forEach(function (button) {
			button.setAttribute('aria-current', Number(button.getAttribute('data-gift-thumbnail')) === state.photoIndex ? 'true' : 'false');
		});
	}

	function setupGallery(state, gift) {
		var gallery = state.dialog.querySelector('[data-gift-gallery]');
		if (!gallery) {
			state.dialog.setAttribute('data-gallery', 'none');
			return;
		}
		state.photos = Array.isArray(gift.images) ? gift.images.slice() : [];
		if (!state.photos.length) {
			state.photos = [{ url: gift.image_url || state.config.placeholder_image, alt: gift.image_alt || gift.name }];
		}
		var multiple = state.photos.length > 1;
		state.dialog.setAttribute('data-gallery', multiple ? 'multiple' : 'single');
		var thumbnails = gallery.querySelector('[data-gift-thumbnails]');
		thumbnails.textContent = '';
		thumbnails.hidden = !multiple;
		gallery.querySelectorAll('[data-gift-gallery-prev], [data-gift-gallery-next], [data-gift-photo-count]').forEach(function (control) {
			control.hidden = !multiple;
		});
		if (multiple) {
			state.photos.forEach(function (photo, index) {
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'apeiron-daftar-hadiah__thumbnail';
				button.setAttribute('data-gift-thumbnail', String(index));
				button.setAttribute('aria-label', state.config.messages.photo.replace('%d', String(index + 1)));
				var image = document.createElement('img');
				image.src = photo.url;
				image.alt = '';
				image.draggable = false;
				button.appendChild(image);
				thumbnails.appendChild(button);
			});
		}
		setPhoto(state, 0);
	}

	function placePopupFooter(state) {
		// Keep desktop actions in the form column; only mobile has a fixed footer.
		var mobile = state.mobileFooter.matches;
		var parent = mobile ? state.dialog : state.form;
		if (state.footer.parentNode !== parent) {
			parent.appendChild(state.footer);
		}
		state.dialog.setAttribute('data-footer-layout', mobile ? 'mobile' : 'inline');
	}

	function openDialog(state, button) {
		var id = button.getAttribute('data-gift-select');
		var gift = state.config.gifts.find(function (item) { return item.gift_id === id; });
		if (!gift || !id || gift.availability !== 'available' || gift.initial_quantity < 1 || button.disabled) {
			return;
		}
		if (typeof state.dialog.showModal !== 'function') {
			return;
		}
		if (active) {
			closeDialog(active);
		}
		resetForm(state);
		state.selected = gift;
		state.opener = button;
		state.dialog.querySelector('[data-gift-name]').textContent = gift.name;
		var category = state.dialog.querySelector('[data-gift-category]');
		category.textContent = gift.category || '';
		category.hidden = !gift.category;
		var description = state.dialog.querySelector('[data-gift-description]');
		if (description) {
			description.textContent = gift.description;
			description.hidden = !gift.description;
		}
		updateAvailabilityBadge(state, state.dialog.querySelector('[data-gift-availability]'), gift, true);
		setupGallery(state, gift);
		var purchase = state.dialog.querySelector('[data-gift-purchase]');
		if (purchase) {
			purchase.hidden = !gift.purchase_url;
			purchase.textContent = gift.purchase_label || purchase.getAttribute('data-default-label');
			purchase.removeAttribute('href');
			purchase.removeAttribute('target');
			if (gift.purchase_url) {
				purchase.href = gift.purchase_url;
				if (gift.purchase_new_tab) {
					purchase.target = '_blank';
				}
			}
			purchase.rel = 'noopener noreferrer' + (gift.purchase_nofollow ? ' nofollow' : '');
		}
		placePopupFooter(state);
		state.mobileFooter.addEventListener('change', state.footerModeChange);
		state.dialog.showModal();
		active = state;
		state.form.elements.namedItem('guest_name').focus({ preventScroll: true });
		state.dialog.querySelector('[data-gift-scroll]').scrollTop = 0;
		state.observer = new MutationObserver(function () {
			if (!state.root.isConnected || !state.root.contains(state.dialog)) {
				destroy(state.root);
			}
		});
		state.observer.observe(document.body, { childList: true, subtree: true });
		if (state.stockError) { showNotice(state, state.stockError, true); }
		refreshAvailability(state);
	}

	function submitForm(state, event) {
		event.preventDefault();
		if (state.busy || state.confirmed || !state.dialog.open) {
			return;
		}
		var data = collectFormData(state.form);
		var invalid = showErrors(state, validateFormData(data));
		if (invalid) {
			showNotice(state, state.config.messages.validation, true);
			invalid.focus();
			return;
		}
		var gift = state.selected;
		if (!gift || !gift.gift_id || gift.availability !== 'available' || gift.initial_quantity < 1) {
			showNotice(state, state.config.messages.unavailable, true);
			return;
		}
		var identity = state.config.identity;
		if (!identity.invitation_id || !identity.registry_id) {
			showNotice(state, state.config.messages.context, false);
			return;
		}
		setLoading(state, true);
		state.stockSequence += 1;
		var operation = ++state.operation;
		Promise.resolve().then(function () {
			if (state.operation !== operation || !state.dialog.open) { return null; }
			var reservation = buildReservation(identity, gift, data);
			if (state.config.submission_configured) {
				var signature = JSON.stringify([gift.gift_id, data.name, normalizeWhatsApp(data.whatsapp), data.message]);
				if (signature !== state.requestSignature || !state.requestId) { state.requestId = requestId(); state.requestSignature = signature; }
				reservation.request_id = state.requestId;
			}
			return sendReservation(reservation, state.config);
		}).then(function (result) {
			if (state.operation === operation && state.dialog.open) {
				if (result && result.status === 'confirmed' && typeof result.reservation_id === 'string') {
					applyAvailability(state, result.gifts);
					state.confirmed = true;
					showSuccess(state, result.message || state.config.messages.confirmedMessage);
					return;
				}
				var notConfigured = result && result.status === 'not_configured';
				showNotice(state, notConfigured ? state.config.messages.notConfigured : state.config.messages.error, !notConfigured, notConfigured);
			}
		}).catch(function (error) {
			if (state.operation === operation && state.dialog.open) {
				showNotice(state, error.message || state.config.messages.error, error.code !== 'not_configured', error.code === 'not_configured');
				if (error.code === 'gift_unavailable') { refreshAvailability(state, true); }
			}
		}).finally(function () {
			if (state.operation === operation) {
				setLoading(state, false);
			}
		});
	}

	function trapFocus(state, event) {
		if (event.key !== 'Tab' || !state.dialog.open) {
			return;
		}
		var controls = Array.from(state.dialog.querySelectorAll('button:not(:disabled), input, textarea, a[href], [tabindex="0"]')).filter(function (element) {
			return element.getClientRects().length > 0;
		});
		var first = controls[0];
		var last = controls[controls.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	function destroy(root) {
		var state = instances.get(root);
		if (!state) {
			return;
		}
		root.removeEventListener('click', state.click);
		root.removeEventListener('pointerdown', state.pointerdown);
		root.removeEventListener('pointerup', state.pointerup);
		root.removeEventListener('pointercancel', state.pointerup);
		state.form.removeEventListener('submit', state.submitHandler);
		state.form.removeEventListener('input', state.input);
		state.dialog.removeEventListener('keydown', state.keydown);
		state.dialog.removeEventListener('close', state.close);
		if (state.queueNavigation) {
			state.track.removeEventListener('scroll', state.queueNavigation);
			window.removeEventListener('resize', state.queueNavigation);
			if (state.navigationResize) {
				state.navigationResize.disconnect();
			}
			window.cancelAnimationFrame(state.navigationFrame);
		}
		closeDialog(state);
		instances.delete(root);
	}

	function initRoot(root) {
		var current = instances.get(root);
		var raw = root.getAttribute('data-apeiron-daftar-hadiah');
		var dialog = root.querySelector('dialog');
		if (current && current.raw === raw && current.dialog === dialog) {
			if (current.queueNavigation) {
				current.queueNavigation();
			}
			return;
		}
		destroy(root);
		var config;
		try {
			config = JSON.parse(raw);
		} catch (error) {
			return;
		}
		if (!dialog || !config || !Array.isArray(config.gifts) || !config.identity || !config.messages) {
			return;
		}
		var form = dialog.querySelector('[data-gift-form]');
		var state = {
			root: root, dialog: dialog, form: form, raw: raw, config: config,
			scroll: dialog.querySelector('[data-gift-scroll]'), success: dialog.querySelector('[data-gift-success]'), successTimer: null,
			labelledBy: dialog.getAttribute('aria-labelledby'), describedBy: dialog.getAttribute('aria-describedby'),
			notice: dialog.querySelector('[data-gift-notice]'), submit: dialog.querySelector('[type="submit"]'),
			selected: null, opener: null, busy: false, operation: 0, observer: null,
			confirmed: false, requestId: '', requestSignature: '', stockSynced: false, stockError: '', stockPromise: null, stockSequence: 0,
			noticeTimer: null,
			photos: [], photoIndex: 0, swipeStart: null,
			track: root.querySelector('.apeiron-daftar-hadiah__grid'), navigation: root.querySelector('[data-gift-card-navigation]'),
			cards: Array.from(root.querySelectorAll('.apeiron-daftar-hadiah__card')), currentCard: 0, navigationFrame: 0,
			footer: dialog.querySelector('.apeiron-daftar-hadiah__popup-footer'), mobileFooter: window.matchMedia('(max-width: 767px)'),
		};
		state.confirmText = state.submit.textContent;
		state.footerModeChange = function () { placePopupFooter(state); };
		state.click = function (event) {
			var cardDot = event.target.closest('[data-gift-card-index]');
			if (cardDot) {
				navigateToCard(state, Number(cardDot.getAttribute('data-gift-card-index')));
				return;
			}
			if (event.target.closest('[data-gift-card-prev], [data-gift-card-next]')) {
				navigateToCard(state, state.currentCard + (event.target.closest('[data-gift-card-prev]') ? -1 : 1));
				return;
			}
			var thumbnail = event.target.closest('[data-gift-thumbnail]');
			if (thumbnail) {
				setPhoto(state, Number(thumbnail.getAttribute('data-gift-thumbnail')));
				return;
			}
			if (event.target.closest('[data-gift-gallery-prev], [data-gift-gallery-next]')) {
				setPhoto(state, state.photoIndex + (event.target.closest('[data-gift-gallery-prev]') ? -1 : 1));
				return;
			}
			var select = event.target.closest('[data-gift-select]');
			if (select && root.contains(select)) {
				openDialog(state, select);
				return;
			}
			if (event.target.closest('[data-gift-close]')) {
				closeDialog(state);
			} else if (event.target === dialog && dialog.open) {
				var bounds = dialog.getBoundingClientRect();
				if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
					closeDialog(state);
				}
			}
		};
		state.pointerdown = function (event) {
			state.swipeStart = event.isPrimary !== false && event.target.closest('[data-gift-photo]') && !event.target.closest('button') ? { x: event.clientX, y: event.clientY } : null;
		};
		state.pointerup = function (event) {
			var start = state.swipeStart;
			state.swipeStart = null;
			if (!start || event.type === 'pointercancel' || !dialog.open || state.photos.length < 2) {
				return;
			}
			var distance = event.clientX - start.x;
			if (Math.abs(distance) > 40 && Math.abs(distance) > Math.abs(event.clientY - start.y)) {
				setPhoto(state, state.photoIndex + (distance < 0 ? 1 : -1));
			}
		};
		state.submitHandler = function (event) { submitForm(state, event); };
		state.input = function () {
			showErrors(state, validateFormData(collectFormData(form)));
			hideNotice(state);
		};
		state.keydown = function (event) {
			if (state.photos.length > 1 && event.target.closest('[data-gift-gallery]') && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
				event.preventDefault();
				setPhoto(state, state.photoIndex + (event.key === 'ArrowLeft' ? -1 : 1));
			} else {
				trapFocus(state, event);
			}
		};
		state.close = function () {
			// A queued close event from a previous opening must not reset a new one.
			if (!dialog.open) {
				afterClose(state);
			}
		};
		root.addEventListener('click', state.click);
		root.addEventListener('pointerdown', state.pointerdown);
		root.addEventListener('pointerup', state.pointerup);
		root.addEventListener('pointercancel', state.pointerup);
		form.addEventListener('submit', state.submitHandler);
		form.addEventListener('input', state.input);
		dialog.addEventListener('keydown', state.keydown);
		dialog.addEventListener('close', state.close);
		instances.set(root, state);
		initCardNavigation(state);
		refreshAvailability(state);
	}

	function initScope(scope) {
		var node = scope && scope.jquery ? scope[0] : scope;
		if (!node) {
			return;
		}
		if (node.matches && node.matches(ROOT)) {
			initRoot(node);
		}
		if (node.querySelectorAll) {
			node.querySelectorAll(ROOT).forEach(initRoot);
		}
	}

	function registerElementorHooks() {
		if (!hooksRegistered && window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/apeiron-daftar-hadiah.default', initScope);
			hooksRegistered = true;
		}
	}

	window.ApeironDaftarHadiah = { initScope: initScope, destroy: destroy };
	function init() {
		initScope(document);
		registerElementorHooks();
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
	window.addEventListener('elementor/frontend/init', registerElementorHooks);
	if (window.jQuery) {
		window.jQuery(window).on('elementor/frontend/init.apeironDaftarHadiah', registerElementorHooks);
	}
	document.addEventListener('apeiron:content:loaded', function (event) { initScope(event.target); });

	if (window.ApeironDaftarHadiahTestMode) {
		window.ApeironDaftarHadiahTestApi = Object.freeze({
			collectFormData: collectFormData, validateFormData: validateFormData,
			normalizeWhatsApp: normalizeWhatsApp, buildReservation: buildReservation, sendReservation: sendReservation,
		});
	}
}());
