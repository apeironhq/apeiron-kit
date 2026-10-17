(function ($) {
	'use strict';
	var instances = new WeakMap();
	function init() {
		var root = document.querySelector('.apeiron-daftar-hadiah-admin');
		var payload = document.querySelector('[data-apeiron-admin-config="daftar-hadiah"]');
		if (!root || !payload || instances.has(root)) { return; }
		var config;
		try { config = JSON.parse(payload.getAttribute('data-config') || '{}'); } catch (error) { return; }
		var form = root.querySelector('#apeiron-daftar-hadiah-settings');
		var catalog = root.querySelector('#apeiron-daftar-hadiah-catalog');
		var input = catalog.elements.namedItem('invitation_id');
		var registry = catalog.elements.namedItem('registry_id');
		var results = root.querySelector('[data-hadiah-results]');
		var provider = form.elements.namedItem('provider');
		var connection = root.querySelector('[data-hadiah-connection]');
		var state = { busy: false, invitationId: 0, savedProvider: config.provider || provider.value, connectionMessage: connection.textContent };
		instances.set(root, state);
		var tabs = Array.from(root.querySelectorAll('[data-hadiah-settings-tab]'));
		var panels = root.querySelectorAll('[data-hadiah-settings-panel]');
		function selectTab(name, focus) {
			tabs.forEach(function (tab) {
				var active = tab.getAttribute('data-hadiah-settings-tab') === name;
				tab.classList.toggle('is-active', active);
				tab.setAttribute('aria-selected', active ? 'true' : 'false');
				tab.setAttribute('tabindex', active ? '0' : '-1');
				if (active && focus) { tab.focus(); }
			});
			panels.forEach(function (panel) { panel.hidden = panel.getAttribute('data-hadiah-settings-panel') !== name; });
		}
		tabs.forEach(function (tab, index) {
			tab.addEventListener('click', function () { selectTab(tab.getAttribute('data-hadiah-settings-tab'), false); });
			tab.addEventListener('keydown', function (event) {
				var target = event.key === 'ArrowRight' ? (index + 1) % tabs.length
					: event.key === 'ArrowLeft' ? (index - 1 + tabs.length) % tabs.length
					: event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : -1;
				if (target === -1 || tabs[target].disabled) { return; }
				event.preventDefault();
				selectTab(tabs[target].getAttribute('data-hadiah-settings-tab'), true);
			});
		});
		function updateActions() {
			var valid = String(state.invitationId) === input.value && !!registry.value;
			registry.disabled = state.busy || !valid;
			root.querySelectorAll('[data-hadiah-sync], [data-hadiah-reservations]').forEach(function (button) { button.disabled = state.busy || !valid; });
		}
		function updateBackend() {
			root.querySelector('[data-hadiah-test]').disabled = state.busy || provider.value !== state.savedProvider;
			connection.textContent = provider.value === state.savedProvider ? state.connectionMessage : 'Simpan pengaturan koneksi terlebih dahulu, lalu Uji Koneksi.';
		}
		function feedback(name, message, error) {
			var node = root.querySelector('[data-hadiah-feedback="' + name + '"]');
			node.textContent = message;
			node.classList.toggle('is-error', !!error);
		}
		function request(action, data, area, done) {
			if (state.busy) { return; }
			state.busy = true;
			var buttons = Array.from(root.querySelectorAll('button')).map(function (button) { var disabled = button.disabled; button.disabled = true; return { button: button, disabled: disabled }; });
			var fields = Array.from(root.querySelectorAll('input, select')).map(function (field) { var disabled = field.disabled; field.disabled = true; return { field: field, disabled: disabled }; });
			feedback(area, 'Memproses...', false);
			return $.ajax({ url: config.ajaxUrl, type: 'POST', dataType: 'json', timeout: 90000, data: $.extend({ action: action, nonce: config.nonce }, data) }).done(function (response) {
				if (!root.isConnected) { return; }
				var output = response && response.data;
				feedback(area, output && output.message ? output.message : 'Permintaan gagal.', !(response && response.success));
				if (output && output.connection) { state.connectionMessage = output.connection.message; connection.textContent = output.connection.message; }
				if (response && response.success && done) { done(output); }
			}).fail(function (xhr) {
				if (!root.isConnected) { return; }
				var output = xhr.responseJSON && xhr.responseJSON.data;
				feedback(area, output && output.message ? output.message : 'Permintaan belum dapat dikonfirmasi. Silakan coba kembali.', true);
				if (output && output.connection) { state.connectionMessage = output.connection.message; connection.textContent = output.connection.message; }
			}).always(function () {
				state.busy = false;
				buttons.forEach(function (entry) { entry.button.disabled = entry.disabled; });
				fields.forEach(function (entry) { entry.field.disabled = entry.disabled; });
				updateActions();
				updateBackend();
			});
		}
		function clearResults() { results.hidden = true; results.textContent = ''; }
		function resetInvitation() {
			state.invitationId = 0;
			registry.textContent = ''; registry.appendChild(new Option('Pilih undangan terlebih dahulu', '')); registry.disabled = true;
			registry.closest('.apeiron-form-group').hidden = true;
			input.closest('.apeiron-form-group').classList.add('apeiron-daftar-hadiah-admin__wide');
			root.querySelector('[data-hadiah-invitation-title]').textContent = '';
			clearResults(); feedback('catalog', '', false); updateActions();
		}
		function table(headers, rows) {
			clearResults(); results.hidden = false;
			var element = document.createElement('table');
			var head = element.createTHead().insertRow();
			headers.forEach(function (label) { var th = document.createElement('th'); th.scope = 'col'; th.textContent = label; head.appendChild(th); });
			var body = element.createTBody();
			rows.forEach(function (values) { var row = body.insertRow(); values.forEach(function (value) { row.insertCell().textContent = String(value == null ? '' : value); }); });
			results.appendChild(element);
			if (!rows.length) { var empty = document.createElement('p'); empty.textContent = 'Belum ada data untuk undangan dan registry ini.'; results.appendChild(empty); }
		}
		function scope() { return { invitation_id: String(state.invitationId), registry_id: registry.value }; }
		function readReservations(offset) {
			if (state.savedProvider !== provider.value) { feedback('catalog', 'Hubungkan dashboard Daftar Hadiah terlebih dahulu untuk memuat reservasi.', true); return; }
			request('apeiron_read_daftar_hadiah_reservations', $.extend(scope(), { offset: String(offset || 0) }), 'catalog', function (data) {
				table(['Hadiah','Nama Tamu','WhatsApp','Jumlah','Pesan','Waktu','Status'], data.reservations.map(function (row) { return [row.gift_name,row.guest.name,row.guest.whatsapp,row.quantity,row.message,row.timestamp,row.status]; }));
				if (data.next_offset !== null) { var next = document.createElement('button'); next.type = 'button'; next.className = 'apeiron-btn apeiron-btn--secondary'; next.textContent = 'Halaman Berikutnya'; next.addEventListener('click', function () { readReservations(data.next_offset); }); results.appendChild(next); }
			});
		}
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			request('apeiron_save_daftar_hadiah', { form_data: $(form).serialize() }, 'settings', function (data) {
				state.savedProvider = provider.value;
				form.elements.namedItem('api_token').value = '';
				if (data.has_api_token) { form.elements.namedItem('api_token').placeholder = 'Tersimpan — kosongkan untuk mempertahankan'; }
			});
		});
		root.querySelector('[data-hadiah-test]').addEventListener('click', function () {
			if (provider.value !== state.savedProvider) { feedback('settings', 'Simpan Pengaturan terlebih dahulu.', true); return; }
			request('apeiron_test_daftar_hadiah', {}, 'settings');
		});
		registry.addEventListener('change', function () { clearResults(); updateActions(); if (registry.value && !state.busy) { readReservations(0); } });
		function loadCatalog() {
			if (state.busy) { return; }
			resetInvitation();
			if (!input.value) { return; }
			var pending = request('apeiron_load_daftar_hadiah_catalogs', { invitation_id: input.value }, 'catalog', function (data) {
				state.invitationId = data.invitation_id;
				root.querySelector('[data-hadiah-invitation-title]').textContent = data.invitation_title;
				registry.textContent = '';
				data.registries.forEach(function (item, index) { registry.appendChild(new Option('Daftar Hadiah ' + (index + 1) + ' — ' + item.count + ' hadiah', item.registry_id)); });
				if (!data.registries.length) { registry.appendChild(new Option('Katalog belum tersedia', '')); }
				registry.disabled = !data.registries.length;
				registry.closest('.apeiron-form-group').hidden = data.registries.length < 2;
				input.closest('.apeiron-form-group').classList.toggle('apeiron-daftar-hadiah-admin__wide', data.registries.length < 2);
			});
			if (pending) { pending.done(function (response) { if (root.isConnected && response && response.success && registry.value) { readReservations(0); } }); }
		}
		input.addEventListener('change', loadCatalog);
		catalog.addEventListener('submit', function (event) { event.preventDefault(); loadCatalog(); });
		var sync = root.querySelector('[data-hadiah-sync]');
		if (sync) { sync.addEventListener('click', function () {
			request('apeiron_sync_daftar_hadiah', scope(), 'catalog', function (data) {
				table(['Hadiah','ID Hadiah','Jumlah Awal','Tersedia','Status'], data.gifts.map(function (gift) { return [gift.name,gift.gift_id,gift.initial_quantity,gift.available_quantity,gift.availability]; }));
			});
		}); }
		var reservations = root.querySelector('[data-hadiah-reservations]');
		if (reservations) { reservations.addEventListener('click', function () { readReservations(0); }); }
		updateActions();
		updateBackend();
	}
	$(init);
	$(document).on('apeiron:dashboard-tab-loaded', init);
}(jQuery));
