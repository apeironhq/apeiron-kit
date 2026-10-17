(function ($) {
	'use strict';
	function init() {
		var form = $('#apeiron-form-order-settings');
		var payload = document.querySelector('[data-apeiron-admin-config="form-order"]');
		if (!form.length || !payload) { return; }
		var config;
		try { config = JSON.parse(payload.getAttribute('data-config') || '{}'); } catch (error) { return; }
		var feedback = $('#apeiron-form-order-feedback');
		function showFeedback(message, persistent) {
			window.clearTimeout(feedback.data('apeironFormOrderFeedbackTimer'));
			feedback.removeData('apeironFormOrderFeedbackTimer').text(message);
			if (message && !persistent) {
				feedback.data('apeironFormOrderFeedbackTimer', window.setTimeout(function () {
					feedback.empty().removeData('apeironFormOrderFeedbackTimer');
				}, 5000));
			}
		}
		var tabs = form.closest('.apeiron-form-order-admin').find('[data-order-settings-tab]');
		var panels = form.find('[data-order-settings-panel]');
		var savedApiUrl = form.find('[name="api_url"]').val();
		var apiMessage = form.find('[data-order-api-connection]').text();
		var busy = false;
		function storageChanged() { return form.find('[name="api_url"]').val() !== savedApiUrl; }
		function updateStorage() {
			form.find('[data-order-storage] :input').prop('disabled', busy);
			$('#apeiron-form-order-test').prop('disabled', busy || storageChanged());
			form.find('[data-order-api-connection]').text(storageChanged() ? 'Simpan Pengaturan terlebih dahulu, lalu Test Connection.' : apiMessage);
		}
		function selectTab(name, focus) {
			tabs.each(function () {
				var active = $(this).attr('data-order-settings-tab') === name;
				$(this).toggleClass('is-active', active).attr('aria-selected', active ? 'true' : 'false').attr('tabindex', active ? '0' : '-1');
				if (active && focus) { this.focus(); }
			});
			panels.each(function () { this.hidden = $(this).attr('data-order-settings-panel') !== name; });
		}
		tabs.off('.apeironFormOrder').on('click.apeironFormOrder', function () {
			selectTab($(this).attr('data-order-settings-tab'), false);
		}).on('keydown.apeironFormOrder', function (event) {
			var index = tabs.index(this);
			var target = event.key === 'ArrowRight' ? (index + 1) % tabs.length
				: event.key === 'ArrowLeft' ? (index - 1 + tabs.length) % tabs.length
				: event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : -1;
			if (target === -1) { return; }
			event.preventDefault();
			selectTab(tabs.eq(target).attr('data-order-settings-tab'), true);
		});
		function request(action, data, button) {
			if (busy) { return; }
			busy = true;
			var controls = form.find('[name="storage_provider"], [data-order-storage] :input, [type="submit"], #apeiron-form-order-test').map(function () { var disabled = this.disabled; this.disabled = true; return { element: this, disabled: disabled }; }).get();
			showFeedback('Memproses...', true);
			$.post(config.ajaxUrl, $.extend({ action: action, nonce: config.nonce }, data)).done(function (response) {
				showFeedback(response && response.data ? response.data.message : 'Permintaan gagal.');
				if (response && response.data && response.data.api_connection) { apiMessage = response.data.api_connection.message; }
				if (response && response.success && action === 'apeiron_save_form_order') {
					savedApiUrl = form.find('[name="api_url"]').val();
				}
			}).fail(function (xhr) {
				showFeedback(xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Koneksi gagal. Coba lagi.');
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.api_connection) { apiMessage = xhr.responseJSON.data.api_connection.message; }
			}).always(function () { busy = false; controls.forEach(function (entry) { entry.element.disabled = entry.disabled; }); updateStorage(); });
		}
		form.find('[name="api_url"]').off('.apeironFormOrder').on('input.apeironFormOrder', updateStorage);
		form.off('.apeironFormOrder').on('submit.apeironFormOrder', function (event) {
			event.preventDefault();
			request('apeiron_save_form_order', { form_data: form.serialize() }, form.find('[type="submit"]'));
		});
		$('#apeiron-form-order-test').off('.apeironFormOrder').on('click.apeironFormOrder', function () {
			if (storageChanged()) { showFeedback('Simpan Pengaturan terlebih dahulu.'); return; }
			request('apeiron_test_form_order', {}, $(this));
		});
		updateStorage();
	}
	$(init);
	$(document).on('apeiron:dashboard-tab-loaded', init);
}(jQuery));
