(function(window, document, $) {
	'use strict';

	var config = window.ApeironEditorShellConfig || {};
	var initialDisabled = Array.isArray(config.disabledWidgets) ? config.disabledWidgets.slice() : [];

	function createToggleNotice() {
		if (document.getElementById('apeiron-editor-toggle-notice')) {
			return;
		}

		var notice = document.createElement('div');
		var icon = document.createElement('span');
		var message = document.createElement('span');
		var refresh = document.createElement('button');
		var close = document.createElement('button');

		notice.id = 'apeiron-editor-toggle-notice';
		notice.className = 'apeiron-editor-toggle-notice';
		icon.className = 'dashicons dashicons-warning apeiron-editor-toggle-notice__icon';
		message.textContent = (config.i18n && config.i18n.changed) || 'Pengaturan widget Apeiron telah berubah. Segarkan editor untuk menerapkan perubahan.';
		refresh.type = 'button';
		refresh.className = 'apeiron-editor-toggle-notice__refresh';
		refresh.textContent = (config.i18n && config.i18n.refresh) || 'Segarkan';
		refresh.addEventListener('click', function() {
			window.location.reload();
		});
		close.type = 'button';
		close.className = 'apeiron-editor-toggle-notice__close';
		close.setAttribute('aria-label', (config.i18n && config.i18n.dismiss) || 'Tutup');
		close.textContent = '\u00d7';
		close.addEventListener('click', function() {
			notice.remove();
		});

		notice.appendChild(icon);
		notice.appendChild(message);
		notice.appendChild(refresh);
		notice.appendChild(close);
		document.body.appendChild(notice);
	}

	function bindHeartbeat() {
		if (!window.wp || !window.wp.heartbeat || !$) {
			return;
		}

		window.wp.heartbeat.enqueue('apeiron_editor_active', true);
		$(document)
			.off('heartbeat-tick.apeironEditorShell')
			.on('heartbeat-tick.apeironEditorShell', function(event, data) {
				if (!data.apeiron_disabled_widgets) {
					return;
				}

				var current = JSON.stringify(data.apeiron_disabled_widgets.slice().sort());
				var previous = JSON.stringify(initialDisabled.slice().sort());
				if (current !== previous) {
					initialDisabled = data.apeiron_disabled_widgets.slice();
					createToggleNotice();
				}
			});
	}

	bindHeartbeat();
})(window, document, window.jQuery);
