(function($, window, document) {
	'use strict';

	var fallbackI18n = {
		saveApi: 'Simpan',
		testConnection: 'Test',
		activate: 'Aktifkan',
		checkStatus: 'Periksa',
		deactivate: 'Nonaktifkan',
		requestTimeout: 'Permintaan lisensi melewati batas waktu. Silakan coba lagi.',
		enterApiKey: 'Masukkan API Key baru.',
		saving: 'Menyimpan...',
		genericError: 'Terjadi kesalahan.',
		saved: 'Berhasil disimpan!',
		saveFailed: 'Gagal menyimpan.',
		enterLicense: 'Masukkan License Key.',
		activating: 'Mengaktifkan...',
		licenseActive: 'Lisensi aktif!',
		activateFailed: 'Gagal mengaktifkan.',
		checking: 'Memeriksa...',
		statusUpdated: 'Status diperbarui!',
		checkFailed: 'Gagal memeriksa.',
		deactivating: 'Menonaktifkan...',
		deactivated: 'Lisensi dinonaktifkan.',
		deactivateFailed: 'Gagal menonaktifkan.',
		testing: 'Menguji...',
		connectionError: 'Terjadi kesalahan saat menguji koneksi.',
		connectionSuccess: 'Koneksi berhasil!',
		connectionFailed: 'Koneksi gagal.'
	};

	function readConfig() {
		var node = document.querySelector('[data-apeiron-admin-config="license"]');
		if (!node) {
			return {};
		}

		try {
			return JSON.parse(node.getAttribute('data-config') || '{}');
		} catch (error) {
			return {};
		}
	}

	function text(config, key) {
		return (config.i18n && config.i18n[key]) || fallbackI18n[key] || '';
	}

	function initLicense() {
		var config = readConfig();
		var nonce = config.nonce || '';
		var ajaxUrl = config.ajaxUrl || window.ajaxurl || 'admin-ajax.php';
		var requestTimeout = 32000; // Two 15-second backend attempts plus a 2-second buffer.
		var apiKeyRequired = config.apiKeyRequired !== false;
		var currentLicenseKey = config.currentLicenseKey || '';
		var activateIcon = config.activateIcon || 'dashicons-yes';
		var saveIcon = '<span class="apeiron-save-icon" aria-hidden="true"></span>';
		var toastTimers = {};

		if (!$('.apeiron-license-wrap').length) {
			return;
		}

		function refreshDashboard(delay) {
			if (window.ApeironDashboard && typeof window.ApeironDashboard.refreshCurrentTab === 'function') {
				window.ApeironDashboard.refreshCurrentTab({ delay: delay || 0, refreshHeader: true });
				return;
			}

			window.setTimeout(function() { window.location.reload(); }, delay || 0);
		}

		function setButtonLoading($button, label) {
			$button.prop('disabled', true).html(
				'<span class="dashicons dashicons-update apeiron-spin-icon"></span><span>' + label + '</span>'
			);
		}

		function resetButton($button, icon, label) {
			var iconHtml = icon === 'apeiron-save-icon'
				? saveIcon
				: '<span class="dashicons ' + icon + '"></span>';
			$button.prop('disabled', false).html(iconHtml + '<span>' + label + '</span>');
		}

		function showToast(selector, type, message) {
			var $toast = $(selector);

			if (toastTimers[selector]) {
				window.clearTimeout(toastTimers[selector]);
				delete toastTimers[selector];
			}

			$toast
				.removeClass('apeiron-toast--success apeiron-toast--error')
				.addClass('apeiron-toast--' + type)
				.text(message)
				.slideDown(180);

			toastTimers[selector] = window.setTimeout(function() {
				$toast.slideUp(180, function() {
					$toast.removeClass('apeiron-toast--success apeiron-toast--error').text('');
				});
				delete toastTimers[selector];
			}, type === 'error' ? 4500 : 2800);
		}

		function requestLicenseAction(options) {
			setButtonLoading(options.$button, options.loadingText);

			return $.ajax({
				url: ajaxUrl,
				method: 'POST',
				data: $.extend({ nonce: nonce }, options.data || {}),
				dataType: 'json',
				timeout: requestTimeout
			}).done(options.onDone).fail(function(jqXHR, status) {
				resetButton(options.$button, options.resetIcon, options.resetLabel);
				showToast(
					options.toast,
					'error',
					status === 'timeout' ? text(config, 'requestTimeout') : options.failureMessage
				);
			});
		}

		function openModal(selector, triggerSelector, inputSelector) {
			var $modal = $(selector);
			if (!$modal.length) {
				return;
			}
			if (inputSelector) {
				$(inputSelector).val('');
			}
			$modal.css('display', 'flex');
			$modal[0].offsetHeight;
			$modal.addClass('is-visible');
			if (triggerSelector) {
				$(triggerSelector).addClass('is-active').attr('aria-pressed', 'true');
			}
			if (inputSelector) {
				$(inputSelector).trigger('focus');
			}
		}

		function closeModal(selector, triggerSelector, inputSelector) {
			var $modal = $(selector);
			if (!$modal.length) {
				return;
			}
			$modal.removeClass('is-visible');
			if (triggerSelector) {
				$(triggerSelector).removeClass('is-active').attr('aria-pressed', 'false');
			}
			window.setTimeout(function() {
				$modal.css('display', 'none');
				if (inputSelector) {
					$(inputSelector).val('');
				}
			}, 160);
		}

		$(document)
			.off('apeiron:dashboard-tab-unload.apeironLicense')
			.on('apeiron:dashboard-tab-unload.apeironLicense', function() {
				$.each(toastTimers, function(selector, timer) {
					window.clearTimeout(timer);
				});
				toastTimers = {};
				$(document).off('.apeironLicense');
			});

		$('#license-edit-key-btn').on('click', function() {
			openModal('#apeiron-license-key-modal', '#license-edit-key-btn', '#license-key-input');
		});

		$('#api-key-edit-btn').on('click', function() {
			openModal('#apeiron-api-key-modal', '#api-key-edit-btn', '#api-key-input');
		});

		$('#save-api-key-btn').on('click', function() {
			var $button = $(this);
			var apiKey = $('#api-key-input').val().trim();

			if (apiKeyRequired && !apiKey) {
				showToast('#server-config-message', 'error', text(config, 'enterApiKey'));
				return;
			}

			requestLicenseAction({
				$button: $button,
				loadingText: text(config, 'saving'),
				resetIcon: 'apeiron-save-icon',
				resetLabel: text(config, 'saveApi'),
				toast: '#server-config-message',
				failureMessage: text(config, 'genericError'),
				data: { action: 'apeiron_save_server_config', api_key: apiKey },
				onDone: function(response) {
					var data = response && response.data ? response.data : {};
					if (response && response.success) {
						showToast('#server-config-message', 'success', data.message || text(config, 'saved'));
						refreshDashboard(1200);
					} else {
						showToast('#server-config-message', 'error', data.message || text(config, 'saveFailed'));
						resetButton($button, 'apeiron-save-icon', text(config, 'saveApi'));
					}
				}
			});
		});

		$('#license-activate-btn').on('click', function() {
			var $button = $(this);
			var $input = $('#license-key-input');
			var licenseKey = $input.length && $input.is(':visible') ? $input.val().trim() : currentLicenseKey;

			if (!licenseKey) {
				showToast('#license-message', 'error', text(config, 'enterLicense'));
				return;
			}

			requestLicenseAction({
				$button: $button,
				loadingText: text(config, 'activating'),
				resetIcon: activateIcon,
				resetLabel: text(config, 'activate'),
				toast: '#license-message',
				failureMessage: text(config, 'genericError'),
				data: { action: 'apeiron_activate_license', license_key: licenseKey },
				onDone: function(response) {
					var data = response && response.data ? response.data : {};
					if (response && response.success) {
						showToast('#license-message', 'success', data.message || text(config, 'licenseActive'));
						refreshDashboard(1200);
					} else {
						showToast('#license-message', 'error', data.message || text(config, 'activateFailed'));
						resetButton($button, activateIcon, text(config, 'activate'));
					}
				}
			});
		});

		$('#license-check-btn').on('click', function() {
			var $button = $(this);
			requestLicenseAction({
				$button: $button,
				loadingText: text(config, 'checking'),
				resetIcon: 'dashicons-search',
				resetLabel: text(config, 'checkStatus'),
				toast: '#license-message',
				failureMessage: text(config, 'genericError'),
				data: { action: 'apeiron_check_license' },
				onDone: function(response) {
					var data = response && response.data ? response.data : {};
					if (response && response.success) {
						showToast('#license-message', 'success', data.message || text(config, 'statusUpdated'));
						refreshDashboard(1200);
					} else {
						showToast('#license-message', 'error', data.message || text(config, 'checkFailed'));
						resetButton($button, 'dashicons-search', text(config, 'checkStatus'));
					}
				}
			});
		});

		$('#license-deactivate-btn').on('click', function() {
			openModal('#apeiron-license-deactivate-modal', '', '');
			$('#license-deactivate-confirm-btn').trigger('focus');
		});

		$('#license-deactivate-confirm-btn').on('click', function() {
			closeModal('#apeiron-license-deactivate-modal', '', '');
			var $button = $('#license-deactivate-btn');
			requestLicenseAction({
				$button: $button,
				loadingText: text(config, 'deactivating'),
				resetIcon: 'dashicons-no',
				resetLabel: text(config, 'deactivate'),
				toast: '#license-message',
				failureMessage: text(config, 'genericError'),
				data: { action: 'apeiron_deactivate_license' },
				onDone: function(response) {
					var data = response && response.data ? response.data : {};
					if (response && response.success) {
						showToast('#license-message', 'success', data.message || text(config, 'deactivated'));
						refreshDashboard(1200);
					} else {
						showToast('#license-message', 'error', data.message || text(config, 'deactivateFailed'));
						resetButton($button, 'dashicons-no', text(config, 'deactivate'));
					}
				}
			});
		});

		$('#test-server-connection-btn').on('click', function() {
			var $button = $(this);
			requestLicenseAction({
				$button: $button,
				loadingText: text(config, 'testing'),
				resetIcon: 'dashicons-admin-network',
				resetLabel: text(config, 'testConnection'),
				toast: '#server-config-message',
				failureMessage: text(config, 'connectionError'),
				data: { action: 'apeiron_test_server_connection' },
				onDone: function(response) {
					var data = response && response.data ? response.data : {};
					var message;
					if (response && response.success) {
						showToast('#server-config-message', 'success', data.message || text(config, 'connectionSuccess'));
					} else {
						message = data.message || text(config, 'connectionFailed');
						if (data.error_code) {
							message += ' (Error: ' + data.error_code + ')';
						}
						showToast('#server-config-message', 'error', message);
					}
					resetButton($button, 'dashicons-admin-network', text(config, 'testConnection'));
				}
			});
		});

		$('#license-key-modal-cancel, #apeiron-license-key-modal .apeiron-modal__backdrop').on('click', function() {
			closeModal('#apeiron-license-key-modal', '#license-edit-key-btn', '#license-key-input');
		});
		$('#api-key-modal-cancel, #apeiron-api-key-modal .apeiron-modal__backdrop').on('click', function() {
			closeModal('#apeiron-api-key-modal', '#api-key-edit-btn', '#api-key-input');
		});
		$('#license-deactivate-modal-cancel, #apeiron-license-deactivate-modal .apeiron-modal__backdrop').on('click', function() {
			closeModal('#apeiron-license-deactivate-modal', '', '');
		});

		$(document).off('keydown.apeironLicense').on('keydown.apeironLicense', function(event) {
			if (event.key !== 'Escape') {
				return;
			}
			if ($('#apeiron-license-key-modal').hasClass('is-visible')) {
				closeModal('#apeiron-license-key-modal', '#license-edit-key-btn', '#license-key-input');
			}
			if ($('#apeiron-api-key-modal').hasClass('is-visible')) {
				closeModal('#apeiron-api-key-modal', '#api-key-edit-btn', '#api-key-input');
			}
			if ($('#apeiron-license-deactivate-modal').hasClass('is-visible')) {
				closeModal('#apeiron-license-deactivate-modal', '', '');
			}
		});
	}

	window.ApeironLicenseAdmin = window.ApeironLicenseAdmin || {};
	window.ApeironLicenseAdmin.init = initLicense;

	$(document)
		.off('apeiron:dashboard-tab-loaded.apeironLicenseBootstrap')
		.on('apeiron:dashboard-tab-loaded.apeironLicenseBootstrap', initLicense);

	$(initLicense);
})(jQuery, window, document);
