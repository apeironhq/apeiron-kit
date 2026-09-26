(function($, window, document) {
	'use strict';

	function readConfig() {
		var node = document.querySelector('[data-apeiron-admin-config="social-proof"]');
		if (!node) {
			return {};
		}

		try {
			return JSON.parse(node.getAttribute('data-config') || '{}');
		} catch (error) {
			return {};
		}
	}

	function initSocialProof() {
		var config = readConfig();
		var nonce = config.nonce || '';
		var ajaxUrl = config.ajaxUrl || window.ajaxurl || 'admin-ajax.php';
		var saveIcon = '<span class="apeiron-save-icon" aria-hidden="true"></span>';
		var pendingBackupJson = '';
		var $backupInput = $('#social-proof-backup-input');
		var $importModal = $('#apeiron-sp-import-modal');
		var $confirmImportBtn = $('#confirm-social-proof-import-btn');
		var $uploadZone = $('#client-photo-upload-zone');
		var $uploadInput = $('#client-photo-upload-input');
		var $imageInput = $('#entry-image');
		var $preview = $('#client-photo-preview');
		var $previewImg = $('#client-photo-preview-img');
		var $removeBtn = $('#remove-client-photo-btn');
		var $progress = $('#client-photo-progress');
		var $progressFill = $('#client-photo-progress-fill');
		var $progressText = $('#client-photo-progress-text');
		var now;
		var dateTime;

		if (!$('#sp-settings-form').length) {
			return;
		}

		$(document)
			.off('apeiron:dashboard-tab-unload.apeironSocialProof')
			.on('apeiron:dashboard-tab-unload.apeironSocialProof', function() {
				$(document).off('.apeironSocialProof');
			});

		now = new Date();
		dateTime = now.getFullYear() + '-'
			+ String(now.getMonth() + 1).padStart(2, '0') + '-'
			+ String(now.getDate()).padStart(2, '0') + 'T'
			+ String(now.getHours()).padStart(2, '0') + ':'
			+ String(now.getMinutes()).padStart(2, '0');
		$('#entry-datetime').val(dateTime);

		function showToast(type, message) {
			var $container = $('#apeiron-toast-container');
			var icons = {
				success: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#68de7c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
				error: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
			};
			var $item = $('<div class="apeiron-toast-item" />');

			if (icons[type]) {
				$item.append(icons[type]);
			}
			$item.append($('<span />').text(message));
			$container.empty().append($item);
			$container.removeClass('is-visible is-hiding').addClass('is-visible');
			window.setTimeout(function() {
				$container.addClass('is-hiding');
				window.setTimeout(function() {
					$container.removeClass('is-visible is-hiding').empty();
				}, 200);
			}, type === 'error' ? 4000 : 3000);
		}

		function restoreSettingsButton($button) {
			$button.prop('disabled', false).html(saveIcon + ' Simpan Pengaturan');
		}

		function restoreAddButton($button) {
			$button.prop('disabled', false).html(
				'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Tambah Aktivitas'
			);
		}

		function getResponseMessage(response, fallback) {
			return response && response.data && response.data.message ? response.data.message : fallback;
		}

		function refreshDashboard(delay) {
			if (window.ApeironDashboard && typeof window.ApeironDashboard.refreshCurrentTab === 'function') {
				window.ApeironDashboard.refreshCurrentTab({ delay: delay || 0 });
				return;
			}

			window.setTimeout(function() { window.location.reload(); }, delay || 0);
		}

		function setButtonLoading($button, label) {
			if (!$button.data('original-html')) {
				$button.data('original-html', $button.html());
			}
			$button.prop('disabled', true).addClass('is-loading').empty()
				.append('<span class="apeiron-toast-spinner"></span>')
				.append($('<span />').text(label));
		}

		function restoreButton($button) {
			var originalHtml = $button.data('original-html');
			$button.prop('disabled', false).removeClass('is-loading');
			if (originalHtml) {
				$button.html(originalHtml);
				$button.removeData('original-html');
			}
		}

		$('#sp-settings-form').on('submit', function(event) {
			event.preventDefault();
			var $button = $(this).find('button[type="submit"]');
			$button.prop('disabled', true).text('Menyimpan...');
			$.post(ajaxUrl, {
				action: 'apeiron_save_social_proof',
				form_data: $(this).serialize(),
				nonce: nonce
			}, function(response) {
				showToast(
					response && response.success ? 'success' : 'error',
					response && response.success ? 'Tersimpan!' : getResponseMessage(response, 'Gagal menyimpan pengaturan')
				);
				restoreSettingsButton($button);
				updatePreview();
			}).fail(function() {
				showToast('error', 'Koneksi gagal. Pengaturan belum tersimpan.');
				restoreSettingsButton($button);
			});
		});

		$('#add-entry-btn').on('click', function() {
			var name = $('#entry-name').val().trim();
			var product = $('#entry-product').val().trim();
			var image = $('#entry-image').val() || '';
			var entryDateTime = $('#entry-datetime').val();
			var $button = $(this);

			if (!name) {
				showToast('error', 'Masukkan nama pelanggan');
				$('#entry-name').trigger('focus');
				return;
			}
			if (!product) {
				showToast('error', 'Masukkan nama produk');
				$('#entry-product').trigger('focus');
				return;
			}
			if (!entryDateTime) {
				showToast('error', 'Pilih tanggal');
				$('#entry-datetime').trigger('focus');
				return;
			}

			$button.prop('disabled', true).text('Menambahkan...');
			$.post(ajaxUrl, {
				action: 'apeiron_save_social_proof',
				form_data: 'add_entry=1&entry_name=' + encodeURIComponent(name)
					+ '&entry_product=' + encodeURIComponent(product)
					+ '&entry_image=' + encodeURIComponent(image)
					+ '&entry_datetime=' + encodeURIComponent(entryDateTime),
				nonce: nonce
			}, function(response) {
				if (response && response.success) {
					showToast('success', 'Berhasil ditambahkan!');
					refreshDashboard(800);
				} else {
					showToast('error', getResponseMessage(response, 'Gagal menambah aktivitas'));
					restoreAddButton($button);
				}
			}).fail(function() {
				showToast('error', 'Koneksi gagal. Aktivitas belum ditambahkan.');
				restoreAddButton($button);
			});
		});

		$(document)
			.off('click.apeironSocialProof', '.delete-entry-btn')
			.on('click.apeironSocialProof', '.delete-entry-btn', function() {
				if (!window.confirm('Hapus aktivitas ini?')) {
					return;
				}
				var $button = $(this);
				$button.prop('disabled', true);
				$.post(ajaxUrl, {
					action: 'apeiron_delete_social_proof_entry',
					entry_index: $button.data('index'),
					nonce: nonce
				}, function(response) {
					if (response && response.success) {
						refreshDashboard(0);
					} else {
						showToast('error', 'Gagal menghapus');
						$button.prop('disabled', false);
					}
				}).fail(function() {
					showToast('error', 'Koneksi gagal. Aktivitas belum dihapus.');
					$button.prop('disabled', false);
				});
			});

		$('#download-social-proof-backup-btn').on('click', function() {
			var $button = $(this);
			setButtonLoading($button, 'Menyiapkan backup...');
			$.post(ajaxUrl, { action: 'apeiron_export_social_proof_backup', nonce: nonce }, function(response) {
				if (response && response.success && response.data && response.data.content) {
					downloadBackupFile(response.data.filename || 'apeiron-social-proof-backup.json', response.data.content);
					showToast('success', 'Backup berhasil dibuat.');
				} else {
					showToast('error', getResponseMessage(response, 'Gagal membuat backup'));
				}
				restoreButton($button);
			}).fail(function() {
				showToast('error', 'Koneksi gagal. Backup belum dibuat.');
				restoreButton($button);
			});
		});

		$('#import-social-proof-backup-btn').on('click', function() {
			$backupInput.val('').trigger('click');
		});

		$backupInput.on('change', function() {
			var file = this.files && this.files[0] ? this.files[0] : null;
			var reader;
			var $importButton;
			if (!file) {
				return;
			}
			if (!/\.json$/i.test(file.name) && file.type !== 'application/json') {
				showToast('error', 'Pilih file backup berformat JSON.');
				$backupInput.val('');
				return;
			}
			if (file.size > 5 * 1024 * 1024) {
				showToast('error', 'Ukuran file backup maksimal 5MB.');
				$backupInput.val('');
				return;
			}

			reader = new window.FileReader();
			$importButton = $('#import-social-proof-backup-btn');
			setButtonLoading($importButton, 'Memvalidasi...');

			reader.onload = function(event) {
				var content = String(event.target.result || '');
				try {
					JSON.parse(content);
				} catch (error) {
					showToast('error', 'Format JSON backup tidak valid.');
					restoreButton($importButton);
					$backupInput.val('');
					return;
				}

				$.post(ajaxUrl, {
					action: 'apeiron_validate_social_proof_backup',
					backup_json: content,
					nonce: nonce
				}, function(response) {
					if (response && response.success) {
						pendingBackupJson = content;
						renderBackupSummary(response.data || {});
						openImportModal();
					} else {
						showToast('error', getResponseMessage(response, 'Backup tidak valid'));
					}
					restoreButton($importButton);
					$backupInput.val('');
				}).fail(function() {
					showToast('error', 'Koneksi gagal. Backup belum divalidasi.');
					restoreButton($importButton);
					$backupInput.val('');
				});
			};

			reader.onerror = function() {
				showToast('error', 'File backup tidak bisa dibaca.');
				restoreButton($importButton);
				$backupInput.val('');
			};
			reader.readAsText(file);
		});

		$confirmImportBtn.on('click', function() {
			var $button = $(this);
			if (!pendingBackupJson) {
				showToast('error', 'Pilih file backup terlebih dahulu.');
				closeImportModal();
				return;
			}

			setButtonLoading($button, 'Mengimpor...');
			$.post(ajaxUrl, {
				action: 'apeiron_import_social_proof_backup',
				backup_json: pendingBackupJson,
				overwrite_confirmed: '1',
				nonce: nonce
			}, function(response) {
				if (response && response.success) {
					showToast('success', getResponseMessage(response, 'Backup berhasil diimport.'));
					pendingBackupJson = '';
					refreshDashboard(900);
				} else {
					showToast('error', getResponseMessage(response, 'Gagal mengimport backup'));
					restoreButton($button);
				}
			}).fail(function() {
				showToast('error', 'Koneksi gagal. Backup belum diimport.');
				restoreButton($button);
			});
		});

		$(document)
			.off('click.apeironSocialProof', '[data-sp-backup-close]')
			.on('click.apeironSocialProof', '[data-sp-backup-close]', function(event) {
				event.preventDefault();
				closeImportModal();
			})
			.off('keydown.apeironSocialProof')
			.on('keydown.apeironSocialProof', function(event) {
				if (event.key === 'Escape' && $importModal.hasClass('is-visible')) {
					closeImportModal();
				}
			});

		function downloadBackupFile(filename, content) {
			var blob = new window.Blob([content], { type: 'application/json;charset=utf-8' });
			var url;
			var link;
			if (window.navigator && window.navigator.msSaveOrOpenBlob) {
				window.navigator.msSaveOrOpenBlob(blob, filename);
				return;
			}
			url = window.URL.createObjectURL(blob);
			link = document.createElement('a');
			link.href = url;
			link.download = filename;
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			window.setTimeout(function() { window.URL.revokeObjectURL(url); }, 1000);
		}

		function renderBackupSummary(data) {
			var rows = [
				['Aktivitas dipulihkan', (data.entries_count || 0) + ' aktivitas'],
				['Data saat ini', (data.current_entries_count || 0) + ' aktivitas akan ditimpa'],
				['Pengaturan', (data.settings_count || 0) + ' pengaturan Aktivitas']
			];
			var $summary = $('#apeiron-sp-backup-summary').empty();

			if (data.source_site) { rows.push(['Sumber backup', data.source_site]); }
			if (data.exported_at) { rows.push(['Waktu export', data.exported_at]); }
			if (data.plugin_version) { rows.push(['Versi plugin', data.plugin_version]); }
			if (data.schema_version) { rows.push(['Skema backup', 'v' + data.schema_version]); }

			rows.forEach(function(row) {
				$('<div class="apeiron-sp-backup-summary__row" />')
					.append($('<span />').text(row[0]))
					.append($('<strong />').text(row[1]))
					.appendTo($summary);
			});
			if (data.warnings && data.warnings.length) {
				var $warnings = $('<div class="apeiron-sp-backup-summary__warnings" />');
				data.warnings.forEach(function(warning) {
					$('<p />').text(warning).appendTo($warnings);
				});
				$summary.append($warnings);
			}
		}

		function openImportModal() {
			$importModal.css('display', 'flex').attr('aria-hidden', 'false');
			window.setTimeout(function() {
				$importModal.addClass('is-visible');
				$confirmImportBtn.trigger('focus');
			}, 10);
		}

		function closeImportModal() {
			if ($confirmImportBtn.hasClass('is-loading')) {
				return;
			}
			pendingBackupJson = '';
			restoreButton($confirmImportBtn);
			$importModal.removeClass('is-visible').attr('aria-hidden', 'true');
			window.setTimeout(function() {
				if (!$importModal.hasClass('is-visible')) {
					$importModal.css('display', 'none');
				}
			}, 160);
		}

		$uploadZone.on('click', function(event) {
			if (event.target !== $uploadInput[0] && !$(event.target).closest('#remove-client-photo-btn').length) {
				$uploadInput.trigger('click');
			}
		});
		$uploadZone.on('keydown', function(event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				$uploadInput.trigger('click');
			}
		});
		$uploadZone.on('dragover dragenter', function(event) {
			event.preventDefault();
			event.stopPropagation();
			$(this).addClass('dragover');
		}).on('dragleave dragend', function(event) {
			event.preventDefault();
			event.stopPropagation();
			$(this).removeClass('dragover');
		}).on('drop', function(event) {
			event.preventDefault();
			event.stopPropagation();
			$(this).removeClass('dragover');
			var files = event.originalEvent.dataTransfer.files;
			if (files.length > 0) {
				uploadClientPhoto(files[0]);
			}
		});

		$uploadInput.on('change', function() {
			if (this.files.length > 0) {
				uploadClientPhoto(this.files[0]);
			}
		});

		function uploadClientPhoto(file) {
			var allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
			var formData;
			if (allowedTypes.indexOf(file.type) === -1) {
				showToast('error', 'Format file tidak diizinkan.');
				return;
			}
			if (file.size > 5 * 1024 * 1024) {
				showToast('error', 'Ukuran file terlalu besar. Maksimal 5MB.');
				return;
			}

			formData = new window.FormData();
			formData.append('action', 'apeiron_upload_client_photo');
			formData.append('client_photo', file);
			formData.append('nonce', nonce);
			$progress.show();
			$progressFill.css('width', '0%');
			$progressText.text('Uploading...');
			$uploadZone.hide();

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				timeout: 60000,
				xhr: function() {
					var request = new window.XMLHttpRequest();
					request.upload.onprogress = function(event) {
						if (event.lengthComputable) {
							var percent = Math.round((event.loaded / event.total) * 100);
							$progressFill.css('width', percent + '%');
							$progressText.text(percent + '%');
						}
					};
					return request;
				},
				success: function(response) {
					$progress.hide();
					if (response && response.success && response.data && response.data.url) {
						$imageInput.val(response.data.url);
						$previewImg.attr('src', response.data.url);
						var filename = file.name.length > 25 ? file.name.substring(0, 22) + '...' : file.name;
						$('#client-photo-filename').text(filename);
						$preview.show();
						$uploadZone.hide();
						updatePreview();
						showToast('success', response.data.message || 'Foto berhasil diupload!');
					} else {
						$uploadZone.show();
						showToast('error', getResponseMessage(response, 'Upload gagal'));
					}
				},
				error: function(request, status) {
					$progress.hide();
					$uploadZone.show();
					showToast('error', status === 'timeout' ? 'Timeout - file terlalu besar' : 'Upload error');
				}
			});
			$uploadInput.val('');
		}

		$removeBtn.on('click', function(event) {
			event.preventDefault();
			event.stopPropagation();
			$imageInput.val('');
			$preview.hide();
			$uploadZone.show();
			updatePreview();
			showToast('success', 'Foto dihapus');
		});

		function updatePreview() {
			var name = $('#entry-name').val() || 'Nama Pelanggan';
			var product = $('#entry-product').val() || 'Tema';
			var image = $imageInput.val();
			var entryDateTime = $('#entry-datetime').val();
			var template = $('input[name="text_template"]').val() || '{name} telah membeli {product} pada:';
			var radius = parseInt($('input[name="image_border_radius"]').val(), 10);
			var $imageContainer = $('#preview-popup .apeiron-sp-preview-popup__img');

			if (isNaN(radius)) {
				radius = 10;
			}
			$('#preview-name').text(name);
			renderPreviewTemplate(template, product);

			if (image) {
				$imageContainer.empty().append(
					$('<img />', { src: image, alt: '' }).css({
						width: '100%',
						height: '100%',
						objectFit: 'cover',
						borderRadius: radius + 'px'
					})
				);
			} else {
				$imageContainer.html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>');
			}
			$imageContainer.css('border-radius', radius + 'px');

			if (entryDateTime) {
				var parsed = new Date(entryDateTime);
				if (!isNaN(parsed.getTime())) {
					$('#preview-date').text(formatPreviewDate(parsed));
				}
			}
		}

		function renderPreviewTemplate(template, product) {
			var $description = $('#preview-desc');
			var cleanTemplate = String(template || '').replace(/\{name\}/g, '').replace(/^[\s,]+/, '');
			$description.empty();
			cleanTemplate.split(/(\{product\})/g).forEach(function(part) {
				if (part === '{product}') {
					$('<em />').text(product).appendTo($description);
				} else {
					$description.append(document.createTextNode(part));
				}
			});
		}

		function formatPreviewDate(date) {
			return date.toLocaleDateString('id-ID', {
				day: 'numeric',
				month: 'short',
				year: 'numeric',
				hour: '2-digit',
				minute: '2-digit'
			}) + ' WIB';
		}

		$('#entry-name, #entry-product, input[name="text_template"], input[name="image_border_radius"]')
			.on('input', updatePreview);
		$('#entry-datetime').on('change', updatePreview);
		updatePreview();
	}

	window.ApeironSocialProofAdmin = window.ApeironSocialProofAdmin || {};
	window.ApeironSocialProofAdmin.init = initSocialProof;

	$(document)
		.off('apeiron:dashboard-tab-loaded.apeironSocialProofBootstrap')
		.on('apeiron:dashboard-tab-loaded.apeironSocialProofBootstrap', initSocialProof);

	$(initSocialProof);
})(jQuery, window, document);
