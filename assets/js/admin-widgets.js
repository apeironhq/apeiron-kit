(function($, window) {
	'use strict';

	var fallbackI18n = {
		usedIn: 'digunakan di',
		pages: 'halaman:',
		checkingUsage: 'Memeriksa penggunaan...',
		processing: 'sedang diproses...',
		enabled: 'berhasil diaktifkan',
		disabled: 'berhasil dinonaktifkan',
		toggleError: 'Gagal mengubah status widget',
		usageCheckError: 'Penggunaan widget tidak dapat diperiksa. Tidak ada perubahan yang disimpan.',
		bulkProcessing: 'Memproses %d widget...',
		bulkEnabled: '%d widget berhasil diaktifkan.',
		bulkDisabled: '%d widget berhasil dinonaktifkan.',
		bulkToggleError: 'Perubahan bulk gagal. Semua toggle dikembalikan ke status sebelumnya.',
		bulkUsageSummary: '%1$d widget yang akan dinonaktifkan digunakan pada %2$d halaman:',
		visibleWidgets: 'widget tampil',
		noChanges: 'Tidak ada perubahan'
	};

	function normalizeConfig(config) {
		config = config || window.ApeironWidgetAdminConfig || {};
		config.i18n = config.i18n || {};
		config.widgetLabels = config.widgetLabels || {};
		config.ajaxUrl = config.ajaxUrl || window.ajaxurl || 'admin-ajax.php';
		config.nonce = config.nonce || '';

		return config;
	}

	function text(config, key) {
		return config.i18n[key] || fallbackI18n[key] || '';
	}

	function formatText(config, key, values) {
		var output = text(config, key);

		$.each(values, function(index, value) {
			output = output.replace(new RegExp('%' + (index + 1) + '\\$[sd]', 'g'), String(value));
			output = output.replace(/%[sd]/, String(value));
		});

		return output;
	}

	function readConfig() {
		var node = document.querySelector('[data-apeiron-admin-config="widgets"]');
		if (!node) {
			return {};
		}

		try {
			return JSON.parse(node.getAttribute('data-config') || '{}');
		} catch (error) {
			return {};
		}
	}

	function initWidgetAdmin(rawConfig) {
		var config = normalizeConfig(rawConfig);
		var widgetLabels = config.widgetLabels;
		var toastTimer = null;
		var $modal = $('#apeiron-usage-modal');
		var modalCb = null;
		var bulkBusy = false;

		if (!$('#apeiron-elements-grid').length) {
			return;
		}

		function hasOwn(object, key) {
			return Object.prototype.hasOwnProperty.call(object, key);
		}

		function showToast(message, type) {
			var $container = $('#apeiron-toast');
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
					dismissToast();
				}, type === 'error' ? 4000 : 3000);
			}
		}

		function dismissToast() {
			var $container = $('#apeiron-toast');

			if (!$container.hasClass('is-visible')) {
				return;
			}

			$container.addClass('is-hiding');
			setTimeout(function() {
				if ($container.hasClass('is-hiding')) {
					$container.removeClass('is-visible is-hiding').empty();
				}
			}, 200);
		}

		function appendUsagePage($list, page, label) {
			var $item = $('<li>');

			if (label) {
				$item.append($('<strong>').text(label + ': '));
			}
			$item.append($('<span>').text(page.title));

			if (page.edit_url) {
				$item.append(
					' ',
					$('<a>', {
						href: page.edit_url,
						target: '_blank',
						rel: 'noopener noreferrer',
						class: 'apeiron-modal__edit-link'
					}).html('<span class="dashicons dashicons-edit" style="font-size:14px;width:14px;height:14px;vertical-align:middle"></span>')
				);
			}

			$list.append($item);
		}

		function openModal() {
			$modal.css('display', 'flex');
			$modal[0].offsetHeight;
			$modal.addClass('is-visible');
		}

		function showModal(widget, count, pages) {
			var label = widgetLabels[widget] || widget;
			var $list = $('#apeiron-modal-pages').empty();

			$('#apeiron-modal-desc').text(label + ' ' + text(config, 'usedIn') + ' ' + count + ' ' + text(config, 'pages'));
			$.each(pages, function(index, page) {
				appendUsagePage($list, page, '');
			});

			openModal();
		}

		function showBulkModal(usage) {
			var $list = $('#apeiron-modal-pages').empty();
			var widgetCount = 0;
			var pageCount = 0;

			$.each(usage, function(widget, item) {
				var label = widgetLabels[widget] || widget;

				widgetCount++;
				pageCount += item.count;
				$.each(item.pages, function(index, page) {
					appendUsagePage($list, page, label);
				});
			});

			$('#apeiron-modal-desc').text(formatText(config, 'bulkUsageSummary', [widgetCount, pageCount]));
			openModal();
		}

		function hideModal() {
			$modal.removeClass('is-visible');
			setTimeout(function() {
				if (!$modal.hasClass('is-visible')) {
					$modal.css('display', 'none');
				}
			}, 200);
		}

		function dismissModal(proceed) {
			var callback = modalCb;

			hideModal();
			modalCb = null;
			if (callback) {
				callback(proceed);
			}
		}

		function setToggleState($toggle, enabled) {
			var $item = $toggle.closest('.apeiron-element-item');

			$toggle.prop('checked', !!enabled);
			$item.toggleClass('is-active', !!enabled);
			$item.toggleClass('is-inactive', !enabled);
		}

		function updateEnableAllState() {
			var $toggles = $('.apeiron-widget-toggle:not(:disabled)');
			var allChecked = $toggles.length > 0;

			$toggles.each(function() {
				if (!$(this).is(':checked')) {
					allChecked = false;
					return false;
				}

				return true;
			});

			$('#apeiron-enable-all-toggle').prop('checked', allChecked);
		}

		function captureStates($toggles) {
			var snapshot = {};

			$toggles.each(function() {
				var $toggle = $(this);
				snapshot[$toggle.data('widget')] = $toggle.is(':checked');
			});

			return snapshot;
		}

		function restoreStates(snapshot) {
			$('.apeiron-widget-toggle').each(function() {
				var $toggle = $(this);
				var widget = $toggle.data('widget');

				if (hasOwn(snapshot, widget)) {
					setToggleState($toggle, snapshot[widget]);
				}
			});
		}

		function isValidDisabledList(disabled) {
			var valid = $.isArray(disabled);
			var seen = {};

			if (!valid) {
				return false;
			}

			$.each(disabled, function(index, widget) {
				if (typeof widget !== 'string' || !hasOwn(widgetLabels, widget) || hasOwn(seen, widget)) {
					valid = false;
					return false;
				}

				seen[widget] = true;
				return true;
			});

			return valid;
		}

		function applyDisabledList(disabled) {
			var disabledLookup = {};

			$.each(disabled, function(index, widget) {
				disabledLookup[widget] = true;
			});

			$('.apeiron-widget-toggle').each(function() {
				var $toggle = $(this);
				setToggleState($toggle, !hasOwn(disabledLookup, $toggle.data('widget')));
			});
		}

		function releaseBulkOperation() {
			bulkBusy = false;
			$('.apeiron-widget-toggle').data('ajax-busy', false);
			$('#apeiron-enable-all-toggle').data('ajax-busy', false);
			updateEnableAllState();
		}

		function rollbackBulk(snapshot, message, type) {
			restoreStates(snapshot);
			releaseBulkOperation();
			showToast(message, type || 'error');
		}

		function rollbackIndividual($toggle, state, message) {
			setToggleState($toggle, state !== 'on');
			showToast(message || text(config, 'toggleError'), 'error');
		}

		function doToggle($toggle, widget, state) {
			var label = widgetLabels[widget] || widget;

			showToast(label + ' ' + text(config, 'processing'), 'loading');

			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apeiron_toggle_widget',
					nonce: config.nonce,
					widget: widget,
					state: state
				},
				success: function(response) {
					if (response && response.success === true && response.data && response.data.state === state) {
						setToggleState($toggle, state === 'on');
						showToast(
							label + ' ' + (state === 'on' ? text(config, 'enabled') : text(config, 'disabled')),
							'success'
						);
					} else {
						rollbackIndividual($toggle, state);
					}
				},
				error: function() {
					rollbackIndividual($toggle, state);
				},
				complete: function() {
					$toggle.data('ajax-busy', false);
					updateEnableAllState();
				}
			});
		}

		function failUsageCheck($toggle) {
			dismissToast();
			setToggleState($toggle, true);
			$toggle.data('ajax-busy', false);
			updateEnableAllState();
			showToast(text(config, 'usageCheckError'), 'error');
		}

		function validateConfirmationUsage(response, requestedWidgets) {
			var requested = {};
			var usage;
			var valid = true;
			var hasUsage = false;

			if (!response || response.success !== true || !response.data || response.data.requires_confirmation !== true) {
				return null;
			}

			usage = response.data.usage;
			if (!usage || typeof usage !== 'object' || $.isArray(usage)) {
				return null;
			}

			$.each(requestedWidgets, function(index, widget) {
				requested[widget] = true;
			});

			$.each(usage, function(widget, item) {
				if (!hasOwn(requested, widget) || !item || typeof item !== 'object' || item.used !== true ||
					typeof item.count !== 'number' || !isFinite(item.count) || item.count <= 0 || Math.floor(item.count) !== item.count ||
					!$.isArray(item.pages) || item.pages.length === 0) {
					valid = false;
					return false;
				}

				$.each(item.pages, function(index, page) {
					if (!page || typeof page.title !== 'string') {
						valid = false;
						return false;
					}
					return true;
				});

				hasUsage = true;
				return valid;
			});

			return valid && hasUsage ? usage : null;
		}

		function isValidBulkSuccess(response, state) {
			var valid = !!(
				response &&
				response.success === true &&
				response.data &&
				response.data.requires_confirmation === false &&
				response.data.state === state &&
				$.isArray(response.data.changed) &&
				isValidDisabledList(response.data.disabled)
			);

			if (!valid || response.data.changed.length === 0) {
				return false;
			}

			$.each(response.data.changed, function(index, widget) {
				if (typeof widget !== 'string' || !hasOwn(widgetLabels, widget)) {
					valid = false;
					return false;
				}
				return true;
			});

			return valid;
		}

		function requestBulkToggle(widgets, state, snapshot, confirmed) {
			showToast(formatText(config, 'bulkProcessing', [widgets.length]), 'loading');

			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apeiron_bulk_toggle_widgets',
					nonce: config.nonce,
					widgets: widgets,
					state: state,
					confirmed: confirmed ? '1' : '0'
				},
				success: function(response) {
					var usage;

					if (response && response.success === true && response.data && response.data.requires_confirmation === true) {
						usage = confirmed ? null : validateConfirmationUsage(response, widgets);
						if (!usage) {
							rollbackBulk(snapshot, text(config, 'bulkToggleError'));
							return;
						}

						dismissToast();
						modalCb = function(proceed) {
							if (proceed) {
								requestBulkToggle(widgets, state, snapshot, true);
							} else {
								rollbackBulk(snapshot, text(config, 'noChanges'), 'success');
							}
						};
						showBulkModal(usage);
						return;
					}

					if (!isValidBulkSuccess(response, state)) {
						rollbackBulk(snapshot, text(config, 'bulkToggleError'));
						return;
					}

					applyDisabledList(response.data.disabled);
					releaseBulkOperation();
					showToast(
						formatText(config, state === 'on' ? 'bulkEnabled' : 'bulkDisabled', [response.data.changed.length]),
						'success'
					);
				},
				error: function() {
					rollbackBulk(snapshot, text(config, 'bulkToggleError'));
				}
			});
		}

		function applyFilters() {
			var searchVal = $('#apeiron-widget-search').val().toLowerCase().trim();
			var filterVal = $('#apeiron-widget-filter').val();
			var visibleCount = 0;

			$('.apeiron-element-item').each(function() {
				var $item = $(this);
				var name = $item.data('name') || '';
				var group = $item.data('group') || '';
				var matchSearch = !searchVal || name.indexOf(searchVal) !== -1;
				var matchFilter = !filterVal || group === filterVal;

				if (matchSearch && matchFilter) {
					$item.removeClass('is-hidden');
					visibleCount++;
				} else {
					$item.addClass('is-hidden');
				}
			});

			$('#apeiron-elements-empty').toggle(visibleCount === 0);
			$('#apeiron-widget-result-count').text(visibleCount + ' ' + text(config, 'visibleWidgets'));
			$('#apeiron-widget-search-clear').toggleClass('is-visible', !!searchVal);
		}

		$('#apeiron-modal-cancel')
			.off('click.apeironWidgetAdmin')
			.on('click.apeironWidgetAdmin', function() {
				dismissModal(false);
			});

		$('#apeiron-modal-confirm')
			.off('click.apeironWidgetAdmin')
			.on('click.apeironWidgetAdmin', function() {
				dismissModal(true);
			});

		$('.apeiron-modal__backdrop')
			.off('click.apeironWidgetAdmin')
			.on('click.apeironWidgetAdmin', function() {
				dismissModal(false);
			});

		$('.apeiron-widget-toggle')
			.off('change.apeironWidgetAdmin')
			.on('change.apeironWidgetAdmin', function() {
				var $toggle = $(this);
				var widget = $toggle.data('widget');
				var isOn = $toggle.is(':checked');

				if (bulkBusy || modalCb || $toggle.data('ajax-busy')) {
					setToggleState($toggle, !isOn);
					updateEnableAllState();
					return;
				}

				if (!isOn) {
					$toggle.data('ajax-busy', true);
					showToast(text(config, 'checkingUsage'), 'loading');

					$.ajax({
						url: config.ajaxUrl,
						type: 'POST',
						data: {
							action: 'apeiron_check_widget_usage',
							nonce: config.nonce,
							widget: widget
						},
						success: function(response) {
							var data = response && response.data;

							dismissToast();
							if (!response || response.success !== true || !data || typeof data.used !== 'boolean' ||
								typeof data.count !== 'number' || !isFinite(data.count) || data.count < 0 || Math.floor(data.count) !== data.count ||
								data.used !== (data.count > 0) ||
								!$.isArray(data.pages)) {
								failUsageCheck($toggle);
								return;
							}

							if (data.count > 0) {
								if (data.pages.length === 0) {
									failUsageCheck($toggle);
									return;
								}

								modalCb = function(proceed) {
									if (proceed) {
										doToggle($toggle, widget, 'off');
									} else {
										setToggleState($toggle, true);
										$toggle.data('ajax-busy', false);
										updateEnableAllState();
									}
								};
								showModal(widget, data.count, data.pages);
							} else {
								doToggle($toggle, widget, 'off');
							}
						},
						error: function() {
							failUsageCheck($toggle);
						}
					});
				} else {
					$toggle.data('ajax-busy', true);
					doToggle($toggle, widget, 'on');
				}

				updateEnableAllState();
			});

		$('#apeiron-widget-search')
			.off('input.apeironWidgetAdmin')
			.on('input.apeironWidgetAdmin', applyFilters);

		$('#apeiron-widget-search-clear')
			.off('click.apeironWidgetAdmin')
			.on('click.apeironWidgetAdmin', function() {
				$('#apeiron-widget-search').val('').trigger('input').trigger('focus');
			});

		$('#apeiron-widget-filter')
			.off('change.apeironWidgetAdmin')
			.on('change.apeironWidgetAdmin', applyFilters);

		$('#apeiron-enable-all-toggle')
			.off('change.apeironWidgetAdmin')
			.on('change.apeironWidgetAdmin', function() {
				var $master = $(this);
				var isOn = $master.is(':checked');
				var $toggles = $('.apeiron-widget-toggle:not(:disabled)');
				var snapshot;
				var widgets = [];
				var hasPendingToggle = false;

				$toggles.each(function() {
					if ($(this).data('ajax-busy')) {
						hasPendingToggle = true;
						return false;
					}
					return true;
				});

				if (bulkBusy || modalCb || $master.data('ajax-busy') || hasPendingToggle) {
					updateEnableAllState();
					return;
				}

				snapshot = captureStates($toggles);
				$toggles.each(function() {
					var $toggle = $(this);

					if ($toggle.is(':checked') !== isOn) {
						widgets.push($toggle.data('widget'));
						setToggleState($toggle, isOn);
					}
				});

				if (widgets.length === 0) {
					updateEnableAllState();
					showToast(text(config, 'noChanges'), 'success');
					return;
				}

				bulkBusy = true;
				$master.data('ajax-busy', true);
				$toggles.data('ajax-busy', true);
				updateEnableAllState();
				requestBulkToggle(widgets, isOn ? 'on' : 'off', snapshot, false);
			});

		applyFilters();
		updateEnableAllState();
	}

	window.ApeironWidgetAdmin = window.ApeironWidgetAdmin || {};
	window.ApeironWidgetAdmin.init = initWidgetAdmin;

	$(document)
		.off('apeiron:dashboard-tab-loaded.apeironWidgetBootstrap')
		.on('apeiron:dashboard-tab-loaded.apeironWidgetBootstrap', function() {
			initWidgetAdmin(readConfig());
		});

	$(function() { initWidgetAdmin(readConfig()); });
})(jQuery, window);
