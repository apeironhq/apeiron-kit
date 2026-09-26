(function($, window) {
	'use strict';

	var fallbackI18n = {
		uploading: 'Uploading...',
		uploadSuccess: 'Upload berhasil!',
		uploadFailed: 'Upload gagal',
		uploadError: 'Upload error',
		uploadTimeout: 'Timeout - file terlalu besar',
		confirmDelete: 'Hapus stiker ini?',
		deleteFailed: 'Gagal menghapus.',
		deleteError: 'Terjadi kesalahan saat menghapus stiker.'
	};

	function normalizeConfig(config) {
		config = config || window.ApeironStickerAdminConfig || {};
		config.i18n = config.i18n || {};
		config.ajaxUrl = config.ajaxUrl || window.ajaxurl || 'admin-ajax.php';
		config.folder = config.folder || '';
		config.nonce = config.nonce || '';

		return config;
	}

	function text(config, key) {
		return config.i18n[key] || fallbackI18n[key] || '';
	}

	function readConfig() {
		var node = document.querySelector('[data-apeiron-admin-config="stickers"]');
		if (!node) {
			return {};
		}

		try {
			return JSON.parse(node.getAttribute('data-config') || '{}');
		} catch (error) {
			return {};
		}
	}

	function initStickerAdmin(rawConfig) {
		var config = normalizeConfig(rawConfig);
		var $input = $('#sticker-upload-input');
		var $trigger = $('#sticker-upload-trigger');

		if (!$input.length || !$trigger.length) {
			return;
		}

		$trigger.off('.apeironStickers');
		$input.off('.apeironStickers');
		$('#apeiron-sticker-filter').off('.apeironStickers');

		$(document)
			.off('apeiron:dashboard-tab-unload.apeironStickers')
			.on('apeiron:dashboard-tab-unload.apeironStickers', function() {
				$trigger.off('.apeironStickers');
				$input.off('.apeironStickers');
				$('#apeiron-sticker-filter').off('.apeironStickers');
				$(document).off('.apeironStickers');
			});

		function applyStickerFilters() {
			var typeVal = $('#apeiron-sticker-filter').val();
			var visibleCount = 0;

			$('.apeiron-sticker-item').each(function() {
				var $item = $(this);
				var type = $item.data('type') || '';
				var visible = !typeVal || type === typeVal;

				$item.toggleClass('is-hidden', !visible);
				if (visible) {
					visibleCount++;
				}
			});

			$('#apeiron-sticker-empty').toggle(visibleCount === 0 && $('.apeiron-sticker-item').length > 0);
		}

		function refreshDashboard(delay) {
			if (window.ApeironDashboard && typeof window.ApeironDashboard.refreshCurrentTab === 'function') {
				window.ApeironDashboard.refreshCurrentTab({ delay: delay || 0 });
				return;
			}

			setTimeout(function() {
				window.location.reload();
			}, delay || 0);
		}

		function showToast(selector, type, message) {
			$(selector)
				.removeClass('apeiron-toast--success apeiron-toast--error')
				.addClass('apeiron-toast--' + type)
				.text(message)
				.slideDown(200);
		}

		function doUpload(files) {
			var formData = new FormData();
			var i;

			for (i = 0; i < files.length; i++) {
				formData.append('sticker_files[]', files[i]);
			}

			formData.append('action', 'apeiron_upload_sticker');
			formData.append('folder', config.folder);
			formData.append('allow_video', 'yes');
			formData.append('nonce', config.nonce);

			$('#upload-progress').show();
			$('#progress-fill').css('width', '0%');
			$('#progress-text').text(text(config, 'uploading'));
			$('#upload-message').hide();

			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				timeout: 120000,
				xhr: function() {
					var xhr = new XMLHttpRequest();

					xhr.upload.onprogress = function(event) {
						var pct;

						if (event.lengthComputable) {
							pct = Math.round((event.loaded / event.total) * 100);
							$('#progress-fill').css('width', pct + '%');
							$('#progress-text').text(pct + '%');
						}
					};

					return xhr;
				},
				success: function(response) {
					$('#upload-progress').hide();
					if (response && response.success) {
						showToast('#upload-message', 'success', (response.data && response.data.message) || text(config, 'uploadSuccess'));
						refreshDashboard(1200);
					} else {
						showToast('#upload-message', 'error', (response && response.data && response.data.message) || text(config, 'uploadFailed'));
					}
				},
				error: function(xhr, status) {
					var message = text(config, 'uploadError');

					$('#upload-progress').hide();
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						message = xhr.responseJSON.data.message;
					} else if (status === 'timeout') {
						message = text(config, 'uploadTimeout');
					}
					showToast('#upload-message', 'error', message);
				}
			});

			$input.val('');
		}

		$trigger.on('click.apeironStickers', function(event) {
			event.preventDefault();
			$input.trigger('click');
		});

		$input.on('change.apeironStickers', function() {
			if (this.files.length > 0) {
				doUpload(this.files);
			}
		});

		$('#apeiron-sticker-filter').on('change.apeironStickers', applyStickerFilters);

		$(document)
			.off('click.apeironStickers', '.apeiron-sticker-item__delete')
			.on('click.apeironStickers', '.apeiron-sticker-item__delete', function(event) {
				var $button;
				var $item;
				var path;

				event.stopPropagation();
				event.preventDefault();

				if (!window.confirm(text(config, 'confirmDelete'))) {
					return;
				}

				$button = $(this);
				$item = $button.closest('.apeiron-sticker-item');
				path = $button.data('path');

				$button.prop('disabled', true).html('<span class="dashicons dashicons-update apeiron-sticker-item__spinner"></span>');

				$.ajax({
					url: config.ajaxUrl,
					type: 'POST',
					data: {
						action: 'apeiron_delete_sticker',
						sticker_path: path,
						nonce: config.nonce
					},
					success: function(response) {
						if (response && response.success) {
							$item.fadeOut(300, function() {
								$(this).remove();
								applyStickerFilters();
							});
						} else {
							window.alert((response && response.data && response.data.message) || text(config, 'deleteFailed'));
							$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>');
						}
					},
					error: function() {
						window.alert(text(config, 'deleteError'));
						$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span>');
					}
				});
			});

		applyStickerFilters();
	}

	window.ApeironStickerAdmin = window.ApeironStickerAdmin || {};
	window.ApeironStickerAdmin.init = initStickerAdmin;

	$(document)
		.off('apeiron:dashboard-tab-loaded.apeironStickerBootstrap')
		.on('apeiron:dashboard-tab-loaded.apeironStickerBootstrap', function() {
			initStickerAdmin(readConfig());
		});

	$(function() { initStickerAdmin(readConfig()); });
})(jQuery, window);
