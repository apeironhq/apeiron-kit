(function ($) {
	'use strict';
	function init() {
		var form = $('#apeiron-form-order-settings');
		var payload = document.querySelector('[data-apeiron-admin-config="form-order"]');
		if (!form.length || !payload) { return; }
		var config;
		try { config = JSON.parse(payload.getAttribute('data-config') || '{}'); } catch (error) { return; }
		var feedback = $('#apeiron-form-order-feedback');
		var tabs = form.closest('.apeiron-form-order-admin').find('[data-order-settings-tab]');
		var panels = form.find('[data-order-settings-panel]');
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
			button.prop('disabled', true);
			feedback.text('Memproses...');
			$.post(config.ajaxUrl, $.extend({ action: action, nonce: config.nonce }, data)).done(function (response) {
				feedback.text(response && response.data ? response.data.message : 'Permintaan gagal.');
				if (response && response.success && action === 'apeiron_save_form_order') { form.find('[name="secret_key"]').val(''); }
			}).fail(function (xhr) {
				feedback.text(xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Koneksi gagal. Coba lagi.');
			}).always(function () { button.prop('disabled', false); });
		}
		form.off('.apeironFormOrder').on('submit.apeironFormOrder', function (event) {
			event.preventDefault();
			request('apeiron_save_form_order', { form_data: form.serialize() }, form.find('[type="submit"]'));
		});
		$('#apeiron-form-order-test').off('.apeironFormOrder').on('click.apeironFormOrder', function () {
			request('apeiron_test_form_order', {}, $(this));
		});
		$('#apeiron-form-order-copy').off('.apeironFormOrder').on('click.apeironFormOrder', function () {
			var code = document.getElementById('apeiron-form-order-code');
			var status = $('#apeiron-form-order-copy-feedback');
			var success = 'Kode Code.gs disalin. Tempel di Code.gs lalu ganti ID Spreadsheet.';
			function fallback() {
				var temporary = document.createElement('textarea');
				temporary.value = code.value;
				temporary.style.position = 'fixed';
				temporary.style.left = '-9999px';
				document.body.appendChild(temporary);
				temporary.select();
				var copied = document.execCommand('copy');
				temporary.remove();
				if (copied) { status.text(success); return; }
				code.closest('details').open = true;
				code.focus();
				code.select();
				status.text('Penyalinan otomatis tidak tersedia. Tekan Ctrl+C pada kode yang dipilih.');
			}
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(code.value).then(function () { status.text(success); }, fallback);
			} else { fallback(); }
		});
	}
	$(init);
	$(document).on('apeiron:dashboard-tab-loaded', init);
}(jQuery));
