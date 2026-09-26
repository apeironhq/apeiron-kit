(function($, window) {
	'use strict';

	var fallbackI18n = {
		saving: 'Menyimpan...',
		saveSettings: 'Simpan',
		saved: 'Tersimpan',
		savingSettings: 'Menyimpan pengaturan...',
		savedSuccess: 'Pengaturan berhasil disimpan.',
		saveFailedStatus: 'Gagal tersimpan',
		saveFailed: 'Gagal menyimpan pengaturan.',
		autosaveWaiting: 'Menunggu autosave...'
	};

	function normalizeConfig(config) {
		config = config || window.ApeironUcapanTamuAdminConfig || {};
		config.i18n = config.i18n || {};
		config.ajaxUrl = config.ajaxUrl || window.ajaxurl || 'admin-ajax.php';
		config.nonce = config.nonce || '';

		return config;
	}

	function text(config, key) {
		return config.i18n[key] || fallbackI18n[key] || '';
	}

	function readConfig() {
		var node = document.querySelector('[data-apeiron-admin-config="ucapan-tamu"]');
		if (!node) {
			return {};
		}

		try {
			return JSON.parse(node.getAttribute('data-config') || '{}');
		} catch (error) {
			return {};
		}
	}

	function initUcapanTamuAdmin(rawConfig) {
		var config = normalizeConfig(rawConfig);
		var toastTimer = null;
		var autosaveTimer = null;
		var isSaving = false;
		var pendingSave = false;
		var isReady = false;
		var $form = $('#apeiron-ut-form');
		var $btn = $('#apeiron-ut-save-btn');
		var $status = $('#apeiron-ut-live-status');
		var lastSavedState;
		var saveIcon = '<span class="apeiron-save-icon" aria-hidden="true"></span>';

		if (!$form.length) {
			return;
		}

		$form.off('.apeironUcapan');

		lastSavedState = $form.serialize();

		$(document)
			.off('apeiron:dashboard-tab-unload.apeironUcapan')
			.on('apeiron:dashboard-tab-unload.apeironUcapan', function() {
				window.clearTimeout(toastTimer);
				window.clearTimeout(autosaveTimer);
				$form.off('.apeironUcapan');
				$(document).off('.apeironUcapan');
			});

		function showToast(message, type) {
			var $container = $('#apeiron-ut-toast');
			var icons = {
				success: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#68de7c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
				error: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
				loading: '<span class="apeiron-toast-spinner"></span>'
			};

			if (toastTimer) {
				clearTimeout(toastTimer);
				toastTimer = null;
			}

			$container.html('<div class="apeiron-toast-item">' + (icons[type] || '') + '<span>' + message + '</span></div>');
			$container.removeClass('is-visible is-hiding').addClass('is-visible');

			if (type !== 'loading') {
				toastTimer = setTimeout(function() {
					$container.addClass('is-hiding');
					setTimeout(function() {
						$container.removeClass('is-visible is-hiding').empty();
					}, 200);
				}, type === 'error' ? 4000 : 3000);
			}
		}

		function setLiveStatus(message, state) {
			$status
				.removeClass('is-saved is-saving is-dirty is-error')
				.addClass('is-' + state)
				.text(message);
		}

		function setButtonLoading(loading) {
			$btn.prop('disabled', loading).toggleClass('is-loading', loading);
			if (loading) {
				$btn.html('<span class="dashicons dashicons-update"></span> ' + text(config, 'saving'));
			} else {
				$btn.html(saveIcon + ' ' + text(config, 'saveSettings'));
			}
		}

		function saveSettings(options) {
			var serialized;

			options = options || {};
			serialized = $form.serialize();

			if (serialized === lastSavedState && !options.force) {
				setLiveStatus(text(config, 'saved'), 'saved');
				return;
			}

			if (isSaving) {
				pendingSave = true;
				return;
			}

			isSaving = true;
			pendingSave = false;
			setLiveStatus(text(config, 'saving'), 'saving');
			setButtonLoading(true);

			if (!options.silent) {
				showToast(text(config, 'savingSettings'), 'loading');
			}

			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apeiron_save_ucapan_tamu',
					nonce: config.nonce,
					form_data: serialized
				},
				success: function(response) {
					if (response.success) {
						lastSavedState = serialized;
						setLiveStatus(text(config, 'saved'), 'saved');
						if (!options.silent) {
							showToast((response.data && response.data.message) || text(config, 'savedSuccess'), 'success');
						}
					} else {
						setLiveStatus(text(config, 'saveFailedStatus'), 'error');
						showToast((response.data && response.data.message) || text(config, 'saveFailed'), 'error');
					}
				},
				error: function() {
					setLiveStatus(text(config, 'saveFailedStatus'), 'error');
					showToast(text(config, 'saveFailed'), 'error');
				},
				complete: function() {
					isSaving = false;
					setButtonLoading(false);
					if (pendingSave) {
						window.clearTimeout(autosaveTimer);
						autosaveTimer = window.setTimeout(function() {
							saveSettings({ silent: true });
						}, 250);
					}
				}
			});
		}

		function scheduleAutosave() {
			if (!isReady) {
				return;
			}

			window.clearTimeout(autosaveTimer);
			setLiveStatus(text(config, 'autosaveWaiting'), 'dirty');
			autosaveTimer = window.setTimeout(function() {
				saveSettings({ silent: true });
			}, 800);
		}

		isReady = true;

		$form.on('change.apeironUcapan input.apeironUcapan', ':input', function() {
			scheduleAutosave();
		});

		$form.on('submit.apeironUcapan', function(event) {
			event.preventDefault();
			window.clearTimeout(autosaveTimer);
			saveSettings({ force: true, silent: false });
		});
	}

	window.ApeironUcapanTamuAdmin = window.ApeironUcapanTamuAdmin || {};
	window.ApeironUcapanTamuAdmin.init = initUcapanTamuAdmin;

	$(document)
		.off('apeiron:dashboard-tab-loaded.apeironUcapanBootstrap')
		.on('apeiron:dashboard-tab-loaded.apeironUcapanBootstrap', function() {
			initUcapanTamuAdmin(readConfig());
		});

	$(function() { initUcapanTamuAdmin(readConfig()); });
})(jQuery, window);
