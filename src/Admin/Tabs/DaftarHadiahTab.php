<?php

declare( strict_types=1 );

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Admin\Ajax\DaftarHadiahHandler;
use ApeironKit\Support\DaftarHadiahCatalog;
use ApeironKit\Support\DaftarHadiahGateway;
use ApeironKit\Support\DaftarHadiahSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DaftarHadiahTab extends AbstractTab {
	public function get_slug(): string { return 'daftar-hadiah'; }
	public function get_title(): string { return __( 'Daftar Hadiah', 'apeiron-kit' ); }

	public function render(): void {
		$settings = DaftarHadiahSettings::get();
		$connection = DaftarHadiahSettings::connection( 'api' );
		$invitations = DaftarHadiahCatalog::invitations();
		$connection_title = __( 'Koneksi', 'apeiron-kit' );
		$setup_title = __( 'Panduan Koneksi', 'apeiron-kit' );
		$script = <<<'JAVASCRIPT'
/*
 * PANDUAN PENGISIAN — BACA SEBELUM MENEKAN JALANKAN
 *
 * Tempel seluruh kode ini ke file Code.gs, lalu simpan.
 * Spreadsheet ID dan Secret Key DIISI DI SCRIPT PROPERTIES, bukan di fungsi
 * atau komentar dalam kode ini. Mengubah komentar ini tidak mengisi pengaturan.
 *
 * Buka ikon roda gigi: Setelan project (Project Settings)
 * → Properti skrip (Script properties) → Tambahkan properti.
 * Tambahkan DUA properti berikut pada project Apps Script yang sama:
 *
 * [WAJIB DIISI 1] SPREADSHEET TUJUAN
 *   Kolom Property / Properti: APEIRON_DAFTAR_HADIAH_SPREADSHEET_ID
 *   Kolom Value / Nilai: TEMPEL ID SPREADSHEET ANDA di kolom ini.
 *   Contoh URL: https://docs.google.com/spreadsheets/d/ID_SPREADSHEET/edit
 *   Ambil hanya bagian ID_SPREADSHEET, bukan seluruh URL.
 *
 * [WAJIB DIISI 2] SECRET KEY
 *   Kolom Property / Properti: APEIRON_DAFTAR_HADIAH_SECRET
 *   Kolom Value / Nilai: TEMPEL SECRET KEY BUATAN ANDA di kolom ini.
 *   Buat melalui generator password, minimal 24 karakter acak (disarankan 32+).
 *   Gunakan nilai yang SAMA PERSIS di ApeironKit → Daftar Hadiah → Koneksi GAS
 *   → kolom Secret Key. Satu key untuk koneksi website, bukan per undangan.
 *
 * Klik SIMPAN PROPERTI SKRIP setelah kedua nilai diisi.
 * Lalu kembali ke Editor, pilih setupDaftarHadiah pada dropdown fungsi,
 * klik Jalankan SATU KALI dan izinkan akses Spreadsheet.
 * Hasilnya: tab Apeiron_Registries, Apeiron_Gifts, Apeiron_Reservations.
 * Fungsi lain (termasuk registryFail dan doPost) berjalan otomatis.
 *
 * Deploy sebagai Web app: Execute as = Me, Who has access = Anyone.
 * Salin URL /exec ke ApeironKit, isi Secret Key yang sama, lalu:
 * Simpan Pengaturan → Uji Koneksi → Aktifkan Reservasi → Simpan Pengaturan.
 * Setelah koneksi aktif, setiap widget/undangan diproses otomatis.
 */
const APEIRON_REGISTRY_SERVICE = 'apeiron-daftar-hadiah';
const APEIRON_REGISTRY_VERSION = 'registry-v1';
const REGISTRY_HEADERS = ['website_id','invitation_id','invitation_title','registry_id','document_id','element_id','catalog_version','catalog_hash','updated_at'];
const GIFT_HEADERS = ['website_id','invitation_id','registry_id','gift_id','gift_name','description','image_url','purchase_url','initial_quantity','capacity','manual_status','active','updated_at'];
const RESERVATION_HEADERS = ['website_id','invitation_id','invitation_title','registry_id','gift_id','gift_name','reservation_id','request_id','payload_hash','quantity','guest_id','guest_name','whatsapp','message','created_at','status','payload_json'];

function registryFail(code) { throw new Error(code); }
function registryText(value) { return String(value == null ? '' : value); }
function registryCell(value) {
  const text = registryText(value);
  return /^(?:\s*[=+\-@]|0\d|\d{16,}$)/.test(text) ? "'" + text : text;
}
function registryRead(value) {
  const text = registryText(value);
  return /^'(?:\s*[=+\-@]|\d)/.test(text) ? text.slice(1) : text;
}
function registryTimestamp(value) { return value instanceof Date ? value.toISOString() : registryRead(value); }
function registryInteger(value, min, max) { return Number.isSafeInteger(value) && value >= min && value <= max; }
function registryDigest(value) {
  return Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, value, Utilities.Charset.UTF_8)
    .map(byte => ('0' + ((byte + 256) % 256).toString(16)).slice(-2)).join('');
}
function registryScope(data) {
  if (!/^site-[a-f0-9]{32}$/.test(data.website_id || '') || !registryInteger(data.invitation_id, 1, Number.MAX_SAFE_INTEGER)
      || !/^registry-[1-9][0-9]*-[A-Za-z0-9_-]{1,100}$/.test(data.registry_id || '')) registryFail('invalid_request');
  return { website_id: data.website_id, invitation_id: data.invitation_id, registry_id: data.registry_id };
}
function registryMatches(row, data) {
  return registryRead(row.website_id) === data.website_id && Number(row.invitation_id) === data.invitation_id && registryRead(row.registry_id) === data.registry_id;
}
function registryBook() {
  // PENANDA SPREADSHEET: nilai ID dibaca dari Script properties.
  // Teks di dalam getProperty(...) adalah NAMA properti; jangan diganti dengan ID.
  const id = PropertiesService.getScriptProperties().getProperty('APEIRON_DAFTAR_HADIAH_SPREADSHEET_ID');
  if (!id) registryFail('not_initialized');
  return SpreadsheetApp.openById(id);
}
function registryTable(book, name, headers, create) {
  let sheet = book.getSheetByName(name);
  if (!sheet && create) {
    sheet = book.insertSheet(name);
    sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
    sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold').setBackground('#f3f7fa');
    sheet.setFrozenRows(1);
  }
  if (!sheet) registryFail('not_initialized');
  const current = sheet.getRange(1, 1, 1, headers.length).getValues()[0];
  if (headers.some((header, i) => current[i] !== header)) registryFail('sheet_conflict');
  return { sheet, headers };
}
function registryTables(create) {
  const book = registryBook();
  return {
    registries: registryTable(book, 'Apeiron_Registries', REGISTRY_HEADERS, create),
    gifts: registryTable(book, 'Apeiron_Gifts', GIFT_HEADERS, create),
    reservations: registryTable(book, 'Apeiron_Reservations', RESERVATION_HEADERS, create)
  };
}
function registryRows(table) {
  if (table.sheet.getLastRow() < 2) return [];
  return table.sheet.getRange(2, 1, table.sheet.getLastRow() - 1, table.headers.length).getValues().map((values, i) => {
    const row = { row: i + 2 };
    table.headers.forEach((header, column) => { row[header] = values[column]; });
    return row;
  });
}
function registryWrite(table, data, row) {
  const values = table.headers.map(header => typeof data[header] === 'number' ? data[header] : registryCell(data[header]));
  if (row) table.sheet.getRange(row, 1, 1, values.length).setValues([values]);
  else table.sheet.appendRow(values);
}

// JALANKAN MANUAL HANYA FUNGSI INI, satu kali setelah dua Script properties disimpan.
// Pilih setupDaftarHadiah pada dropdown → Jalankan. Isi sheet yang sudah ada tetap dipertahankan.
function setupDaftarHadiah() {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(10000)) registryFail('busy');
  try { registryTables(true); SpreadsheetApp.flush(); }
  finally { lock.releaseLock(); }
}

function registrySync(tables, data) {
  registryScope(data);
  if (!registryInteger(data.document_id, 1, Number.MAX_SAFE_INTEGER) || !/^[A-Za-z0-9_-]{1,100}$/.test(data.element_id || '')
      || data.registry_id !== 'registry-' + data.document_id + '-' + data.element_id
      || !/^[0-9]{13,16}$/.test(data.catalog_version || '') || !Number.isSafeInteger(Number(data.catalog_version))
      || !/^[a-f0-9]{64}$/.test(data.catalog_hash || '') || !Array.isArray(data.gifts) || data.gifts.length > 200
      || typeof data.invitation_title !== 'string' || data.invitation_title.length > 1000) registryFail('invalid_catalog');
  const seen = new Set();
  data.gifts.forEach(gift => {
    if (!gift || !/^gift-[A-Za-z0-9_-]{1,100}$/.test(gift.gift_id || '') || seen.has(gift.gift_id)
        || typeof gift.name !== 'string' || !gift.name.trim() || gift.name.length > 600
        || typeof gift.description !== 'string' || gift.description.length > 20000
        || typeof gift.image_url !== 'string' || gift.image_url.length > 2048
        || typeof gift.purchase_url !== 'string' || gift.purchase_url.length > 2048
        || !registryInteger(gift.initial_quantity, 0, 1000000)
        || !registryInteger(gift.available_quantity, 0, gift.initial_quantity)
        || !['available','unavailable'].includes(gift.availability)) registryFail('invalid_catalog');
    seen.add(gift.gift_id);
  });
  const registries = registryRows(tables.registries).filter(row => registryMatches(row, data));
  if (registries.length > 1) registryFail('invalid_catalog');
  const old = registries[0];
  if (old && (Number(old.catalog_version) > Number(data.catalog_version)
      || (Number(old.catalog_version) === Number(data.catalog_version) && registryRead(old.catalog_hash) !== data.catalog_hash))) registryFail('catalog_stale');
  const rows = registryRows(tables.gifts).filter(row => registryMatches(row, data));
  const byId = new Map();
  rows.forEach(row => { const id = registryRead(row.gift_id); if (byId.has(id)) registryFail('invalid_catalog'); byId.set(id, row); });
  if (old && Number(old.catalog_version) === Number(data.catalog_version) && registryRead(old.invitation_title) === data.invitation_title) return rows;
  const now = new Date().toISOString();
  data.gifts.forEach(gift => {
    const existing = byId.get(gift.gift_id);
    registryWrite(tables.gifts, {
      ...registryScope(data), gift_id: gift.gift_id, gift_name: gift.name, description: gift.description,
      image_url: gift.image_url, purchase_url: gift.purchase_url, initial_quantity: gift.initial_quantity,
      capacity: gift.available_quantity, manual_status: gift.availability, active: 'yes', updated_at: now
    }, existing && existing.row);
  });
  rows.filter(row => !seen.has(registryRead(row.gift_id))).forEach(row => {
    row.active = 'no'; row.updated_at = now; registryWrite(tables.gifts, row, row.row);
  });
  registryWrite(tables.registries, {
    ...registryScope(data), invitation_title: data.invitation_title, document_id: data.document_id,
    element_id: data.element_id, catalog_version: data.catalog_version, catalog_hash: data.catalog_hash, updated_at: now
  }, old && old.row);
  SpreadsheetApp.flush();
  return registryRows(tables.gifts).filter(row => registryMatches(row, data));
}
function registryAvailability(data, gifts, reservations) {
  return gifts.filter(gift => registryRead(gift.active) === 'yes').map(gift => {
    const id = registryRead(gift.gift_id), total = Number(gift.initial_quantity), capacity = Number(gift.capacity);
    if (!registryInteger(total, 0, 1000000) || !registryInteger(capacity, 0, total)) registryFail('invalid_catalog');
    let reserved = 0;
    reservations.filter(row => registryMatches(row, data) && registryRead(row.gift_id) === id && registryRead(row.status) === 'confirmed').forEach(row => {
      const quantity = Number(row.quantity);
      if (!registryInteger(quantity, 1, 1000000)) registryFail('script_error');
      reserved += quantity;
    });
    const available = registryRead(gift.manual_status) === 'available' ? Math.max(0, capacity - reserved) : 0;
    return { gift_id: id, initial_quantity: total, available_quantity: available, availability: available > 0 ? 'available' : 'unavailable' };
  });
}
function registryReservation(data, tables, gifts, rows) {
  if (!/^[a-f0-9]{64}$/.test(data.request_id || '') || !/^gift-[A-Za-z0-9_-]{1,100}$/.test(data.gift_id || '')
      || data.quantity !== 1 || !data.guest || typeof data.guest.name !== 'string' || !data.guest.name.trim() || data.guest.name.length > 100
      || !/^[1-9][0-9]{7,14}$/.test(data.guest.whatsapp || '') || typeof data.message !== 'string' || data.message.length > 1000) registryFail('invalid_request');
  const payloadHash = registryDigest(JSON.stringify([data.website_id,data.invitation_id,data.registry_id,data.gift_id,1,data.guest.name,data.guest.whatsapp,data.message]));
  const receipt = rows.find(row => registryMatches(row, data) && registryRead(row.request_id) === data.request_id);
  if (receipt && registryRead(receipt.payload_hash) !== payloadHash) registryFail('request_conflict');
  const guestId = 'guest-' + registryDigest(data.website_id + '|' + data.invitation_id + '|' + data.guest.whatsapp);
  const duplicate = receipt || rows.find(row => registryMatches(row, data) && registryRead(row.gift_id) === data.gift_id && registryRead(row.guest_id) === guestId && registryRead(row.status) === 'confirmed');
  if (duplicate) {
    if (registryRead(duplicate.status) !== 'confirmed') registryFail('request_conflict');
    return { status: 'confirmed', reservation_id: registryRead(duplicate.reservation_id), gift_id: data.gift_id, quantity: 1, timestamp: registryTimestamp(duplicate.created_at), already_reserved: true };
  }
  const gift = gifts.find(row => registryRead(row.gift_id) === data.gift_id && registryRead(row.active) === 'yes');
  const availability = registryAvailability(data, gifts, rows).find(item => item.gift_id === data.gift_id);
  if (!gift || !availability || availability.available_quantity < 1) registryFail('gift_unavailable');
  const reservation = {
    ...registryScope(data), invitation_title: data.invitation_title, gift_id: data.gift_id, gift_name: registryRead(gift.gift_name),
    reservation_id: Utilities.getUuid(), request_id: data.request_id, payload_hash: payloadHash, quantity: 1,
    guest_id: guestId, guest_name: data.guest.name, whatsapp: "'" + data.guest.whatsapp, message: data.message,
    created_at: new Date().toISOString(), status: 'confirmed'
  };
  // JSON preserves literal guest text while human-readable cells are formula-safe.
  reservation.payload_json = JSON.stringify({ name: data.guest.name, whatsapp: data.guest.whatsapp, message: data.message, gift_name: reservation.gift_name });
  registryWrite(tables.reservations, reservation);
  SpreadsheetApp.flush();
  return { status: 'confirmed', reservation_id: reservation.reservation_id, gift_id: data.gift_id, quantity: 1, timestamp: reservation.created_at, already_reserved: false };
}
function registryAdminRows(data, rows) {
  const offset = data.offset == null ? 0 : data.offset;
  if (!registryInteger(offset, 0, 10000000)) registryFail('invalid_request');
  const scoped = rows.filter(row => registryMatches(row, data)).reverse();
  const reservations = scoped.slice(offset, offset + 30).map(row => {
    const payload = JSON.parse(registryText(row.payload_json));
    return { reservation_id: registryRead(row.reservation_id), gift_id: registryRead(row.gift_id), gift_name: payload.gift_name,
      quantity: Number(row.quantity), guest: { id: registryRead(row.guest_id), name: payload.name, whatsapp: payload.whatsapp },
      message: payload.message, timestamp: registryTimestamp(row.created_at), status: registryRead(row.status) };
  });
  return { reservations, total: scoped.length, next_offset: offset + 30 < scoped.length ? offset + 30 : null };
}
function registryReply(data, success, result, error) {
  return ContentService.createTextOutput(JSON.stringify({ service: APEIRON_REGISTRY_SERVICE, version: APEIRON_REGISTRY_VERSION,
    schema_version: 1, operation: data.operation, correlation_id: data.correlation_id, success, data: result || null, error: error || null }))
    .setMimeType(ContentService.MimeType.JSON);
}
function doPost(event) {
  let data = {}, lock;
  try {
    if (!event || !event.postData || event.postData.contents.length > 300000) registryFail('invalid_request');
    data = JSON.parse(event.postData.contents);
    if (!data || Array.isArray(data) || data.service !== APEIRON_REGISTRY_SERVICE || data.schema_version !== 1
        || !['ping','sync_catalog','availability','reserve','reservations'].includes(data.operation)
        || typeof data.correlation_id !== 'string' || data.correlation_id.length > 64) registryFail('invalid_request');
    // PENANDA SECRET KEY: tempel nilainya di Script properties, bukan pada baris ini.
    // Pertahankan NAMA properti di getProperty(...); nilainya harus sama dengan Secret Key di ApeironKit.
    const secret = PropertiesService.getScriptProperties().getProperty('APEIRON_DAFTAR_HADIAH_SECRET');
    if (!secret) registryFail('secret_missing');
    if (data.secret !== secret) registryFail('secret_mismatch');
    const tables = registryTables(false);
    // Health checks never write catalogues or reservation rows.
    if (data.operation === 'ping') return registryReply(data, true, { spreadsheet_ready: true });
    const scope = registryScope(data);
    lock = LockService.getScriptLock();
    if (!lock.tryLock(10000)) registryFail('busy');
    if (data.operation === 'reservations') return registryReply(data, true, { scope, ...registryAdminRows(data, registryRows(tables.reservations)) });
    const gifts = registrySync(tables, data);
    let reservations = registryRows(tables.reservations), result = { scope };
    if (data.operation === 'reserve') {
      result = { ...result, ...registryReservation(data, tables, gifts, reservations) };
      reservations = registryRows(tables.reservations);
    }
    result.gifts = registryAvailability(data, gifts, reservations);
    return registryReply(data, true, result);
  } catch (error) {
    const known = ['invalid_request','secret_missing','secret_mismatch','not_initialized','sheet_conflict','invalid_catalog','catalog_stale','gift_unavailable','request_conflict','busy'];
    return registryReply(data || {}, false, null, known.includes(error.message) ? error.message : 'script_error');
  } finally {
    if (lock && lock.hasLock()) lock.releaseLock();
  }
}
JAVASCRIPT;
		?>
		<section class="apeiron-elements-section apeiron-daftar-hadiah-admin">
			<div class="apeiron-elements-toolbar">
				<div class="apeiron-daftar-hadiah-admin__heading"><h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Daftar Hadiah', 'apeiron-kit' ); ?></h2><p class="apeiron-daftar-hadiah-admin__description"><?php esc_html_e( 'Atur koneksi dan reservasi hadiah.', 'apeiron-kit' ); ?></p></div>
			</div>
			<div class="apeiron-elements-content apeiron-daftar-hadiah-admin__content">
				<div class="apeiron-card">
					<div class="apeiron-daftar-hadiah-admin__tabs" role="tablist" aria-label="<?php echo esc_attr( __( 'Pengaturan Daftar Hadiah', 'apeiron-kit' ) ); ?>">
						<button type="button" class="apeiron-daftar-hadiah-admin__tab is-active" id="hadiah-tab-connection" role="tab" aria-controls="hadiah-panel-connection" aria-selected="true" tabindex="0" data-hadiah-settings-tab="connection" data-hadiah-connection-title><?php echo esc_html( $connection_title ); ?></button>
						<button type="button" class="apeiron-daftar-hadiah-admin__tab" id="hadiah-tab-catalog" role="tab" aria-controls="hadiah-panel-catalog" aria-selected="false" tabindex="-1" data-hadiah-settings-tab="catalog"><?php esc_html_e( 'Reservasi', 'apeiron-kit' ); ?></button>
						<button type="button" class="apeiron-daftar-hadiah-admin__tab" id="hadiah-tab-setup" role="tab" aria-controls="hadiah-panel-setup" aria-selected="false" tabindex="-1" data-hadiah-settings-tab="setup" data-hadiah-setup-title><?php echo esc_html( $setup_title ); ?></button>
					</div>
					<div class="apeiron-card__body">
						<div class="apeiron-daftar-hadiah-admin__panel" id="hadiah-panel-connection" role="tabpanel" aria-labelledby="hadiah-tab-connection" data-hadiah-settings-panel="connection">
							<form id="apeiron-daftar-hadiah-settings" autocomplete="off">
								<input type="hidden" id="hadiah-provider" name="provider" value="api">
								<fieldset><legend data-hadiah-connection-title><?php echo esc_html( $connection_title ); ?></legend>
									<p class="apeiron-daftar-hadiah-admin__intro"><strong><?php esc_html_e( 'Status uji koneksi terakhir:', 'apeiron-kit' ); ?></strong> <span data-hadiah-connection><?php echo esc_html( $connection['message'] ); ?></span></p>
									<label class="apeiron-daftar-hadiah-admin__toggle"><?php esc_html_e( 'Aktifkan Reservasi', 'apeiron-kit' ); ?><span class="apeiron-toggle"><input type="checkbox" name="enabled" value="yes" <?php checked( $settings['enabled'], 'yes' ); ?>><span class="apeiron-toggle__slider" aria-hidden="true"></span></span></label>
									<div class="apeiron-daftar-hadiah-admin__grid">
										<div class="apeiron-form-group apeiron-daftar-hadiah-admin__wide"><label class="apeiron-label" for="hadiah-api-url"><?php esc_html_e( 'URL API', 'apeiron-kit' ); ?></label><input class="apeiron-input" id="hadiah-api-url" name="api_url" type="url" value="<?php echo esc_attr( $settings['api_url'] ); ?>" placeholder="https://app.example.com/api/daftar-hadiah"></div>
										<div class="apeiron-form-group apeiron-daftar-hadiah-admin__wide"><label class="apeiron-label" for="hadiah-api-token"><?php esc_html_e( 'Token Website', 'apeiron-kit' ); ?></label><input class="apeiron-input" id="hadiah-api-token" name="api_token" type="password" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( DaftarHadiahSettings::has_api_token() ? __( 'Tersimpan — kosongkan untuk mempertahankan', 'apeiron-kit' ) : __( 'Token website dari dashboard Daftar Hadiah', 'apeiron-kit' ) ); ?>"></div>
									</div>
								</fieldset>
								<div class="apeiron-actions apeiron-daftar-hadiah-admin__actions"><button class="apeiron-btn apeiron-btn--primary" type="submit"><?php echo $this->get_save_icon_markup(); ?> <?php esc_html_e( 'Simpan Pengaturan', 'apeiron-kit' ); ?></button><button class="apeiron-btn apeiron-btn--secondary" type="button" data-hadiah-test><?php esc_html_e( 'Uji Koneksi', 'apeiron-kit' ); ?></button></div>
								<p data-hadiah-feedback="settings" class="apeiron-daftar-hadiah-admin__feedback" role="status" aria-live="polite"></p>
							</form>
						</div>
						<div class="apeiron-daftar-hadiah-admin__panel" id="hadiah-panel-catalog" role="tabpanel" aria-labelledby="hadiah-tab-catalog" data-hadiah-settings-panel="catalog" hidden>
							<form id="apeiron-daftar-hadiah-catalog">
								<fieldset><legend><?php esc_html_e( 'Reservasi', 'apeiron-kit' ); ?></legend>
									<div class="apeiron-daftar-hadiah-admin__grid">
										<div class="apeiron-form-group apeiron-daftar-hadiah-admin__wide">
											<label class="apeiron-label" for="hadiah-invitation"><?php esc_html_e( 'Undangan', 'apeiron-kit' ); ?></label>
											<select class="apeiron-select" id="hadiah-invitation" name="invitation_id" required <?php disabled( empty( $invitations ) ); ?>>
												<option value=""><?php echo esc_html( $invitations ? __( 'Pilih undangan', 'apeiron-kit' ) : __( 'Belum ada undangan terdeteksi', 'apeiron-kit' ) ); ?></option>
												<?php foreach ( $invitations as $invitation ) : ?>
													<option value="<?php echo esc_attr( (string) $invitation['id'] ); ?>"><?php echo esc_html( $invitation['title'] ); ?></option>
												<?php endforeach; ?>
											</select>
											<p class="apeiron-hint"><?php esc_html_e( 'Pilih undangan untuk melihat reservasinya. Pilihan ini hanya memfilter laporan; widget bekerja tanpa membuka dashboard ini.', 'apeiron-kit' ); ?></p>
										</div>
										<div class="apeiron-form-group" hidden><label class="apeiron-label" for="hadiah-registry"><?php esc_html_e( 'Daftar Hadiah', 'apeiron-kit' ); ?></label><select class="apeiron-select" id="hadiah-registry" name="registry_id" disabled><option value=""><?php esc_html_e( 'Pilih undangan terlebih dahulu', 'apeiron-kit' ); ?></option></select></div>
									</div>
									<p class="apeiron-hint" data-hadiah-invitation-title></p>
								</fieldset>
								<div class="apeiron-actions apeiron-daftar-hadiah-admin__actions"><button type="submit" class="apeiron-btn apeiron-btn--secondary" <?php disabled( empty( $invitations ) ); ?>><?php esc_html_e( 'Muat Ulang', 'apeiron-kit' ); ?></button></div>
								<p data-hadiah-feedback="catalog" class="apeiron-daftar-hadiah-admin__feedback" role="status" aria-live="polite"></p>
							</form>
							<div class="apeiron-daftar-hadiah-admin__table" data-hadiah-results hidden></div>
						</div>
						<div class="apeiron-daftar-hadiah-admin__panel" id="hadiah-panel-setup" role="tabpanel" aria-labelledby="hadiah-tab-setup" data-hadiah-settings-panel="setup" hidden>
							<fieldset><legend data-hadiah-setup-title><?php echo esc_html( $setup_title ); ?></legend>
								<details class="apeiron-daftar-hadiah-admin__setup"><summary><?php esc_html_e( 'Panduan koneksi Daftar Hadiah', 'apeiron-kit' ); ?></summary>
									<ol>
										<li><?php esc_html_e( 'Deploy backend Nuxt/Vercel dan siapkan database Supabase. Endpoint harus mengikuti kontrak registry-api-v1 di bawah; web dan database disiapkan terpisah dari plugin ini.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Ambil URL API dan Token Website dari dashboard Daftar Hadiah. HTTPS untuk server publik; HTTP boleh untuk localhost, 127.0.0.1, atau domain .local/.test, termasuk port lokal seperti :3000. Kunci rahasia Supabase tetap di server.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Pada tab Koneksi, isi URL API dan Token Website, lalu Simpan Pengaturan → Uji Koneksi. Setelah backend terverifikasi, aktifkan reservasi dan simpan lagi. Katalog tetap berasal dari widget/Dynamic Tags dan dikirim otomatis.', 'apeiron-kit' ); ?></li>
									</ol>
								</details>
								<details class="apeiron-daftar-hadiah-admin__setup"><summary><?php esc_html_e( 'Kontrak API registry-api-v1', 'apeiron-kit' ); ?></summary>
									<ul>
										<li><?php esc_html_e( 'Transport: POST JSON ke URL API yang disimpan, Authorization: Bearer Token Website, tanpa redirect. Token tidak dikirim ke browser atau ke body JSON.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Setiap request: service=apeiron-daftar-hadiah, schema_version=1, operation, correlation_id, website_id, website_url. Respons wajib mengembalikan service, version=registry-api-v1, schema_version, operation, correlation_id yang sama, success, serta data atau error.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Operasi: ping, availability, reserve, sync_catalog, reservations. Ping berhasil hanya jika data.backend_ready=true. Availability dan reserve wajib mendaftarkan/memperbarui katalog otomatis tanpa tindakan admin per undangan.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Selain ping, request membawa invitation_id (integer), invitation_title, registry_id, document_id, element_id, catalog_version, catalog_hash, gifts, dan owner_id. Setiap gift membawa gift_id, name, description, image_url, purchase_url, initial_quantity, available_quantity, availability.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Kepemilikan belum ditentukan: owner_id bernilai null sampai integrasi server mengisinya melalui filter apeiron_daftar_hadiah_owner_id (nilai awal null, argumen kedua ID post undangan). Nilai null tidak memberikan akses laporan kepada akun pelanggan. Identitas dari formulir tamu tidak digunakan sebagai pemilik.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Respons selain ping wajib memiliki data.scope dengan website_id, invitation_id, registry_id, owner_id yang sama persis dengan request. Respons stok memuat data.gifts: gift_id, initial_quantity, available_quantity (integer), availability=available/unavailable.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Reserve menambah gift_id, quantity=1, request_id (64 karakter hex), guest{name,whatsapp}, message. Backend melakukan transaksi stok atomik dan idempotensi. Respons sukses: status=confirmed, reservation_id UUID, gift_id, quantity=1, timestamp UTC ISO-8601, gifts, serta already_reserved bila permintaan sudah tercatat.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Reservations menerima offset dan mengembalikan reservations (maksimal 30), total, next_offset (integer atau null). Tiap baris: reservation_id, gift_id, gift_name, quantity, guest{id,name,whatsapp}, message, timestamp, status. API wajib memisahkan data sesuai token website dan scope.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Error terstruktur memakai success=false dan error, misalnya gift_unavailable, catalog_stale, request_conflict, busy, invalid_catalog, invalid_request. HTTP 401/403 menandakan token ditolak; 429 menandakan batas permintaan. Respons sukses HTTP 2xx tetap harus lolos pemeriksaan kontrak.', 'apeiron-kit' ); ?></li>
									</ul>
									<label class="apeiron-label" for="apeiron-daftar-hadiah-api-contract"><?php esc_html_e( 'Contoh respons availability', 'apeiron-kit' ); ?></label>
									<textarea id="apeiron-daftar-hadiah-api-contract" readonly spellcheck="false" rows="16"><?php echo esc_textarea( (string) wp_json_encode( [
										'service' => DaftarHadiahGateway::SERVICE, 'version' => DaftarHadiahGateway::API_VERSION, 'schema_version' => 1,
										'operation' => 'availability', 'correlation_id' => 'salin-correlation-id-dari-request', 'success' => true,
										'data' => [ 'scope' => [ 'website_id' => 'site-' . str_repeat( 'a', 32 ), 'invitation_id' => 1, 'registry_id' => 'registry-1-widget', 'owner_id' => null ], 'gifts' => [ [ 'gift_id' => 'gift-example', 'initial_quantity' => 5, 'available_quantity' => 4, 'availability' => 'available' ] ] ],
										'error' => null,
									], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
								</details>
							</fieldset>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
		$this->render_config_payload( 'daftar-hadiah', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( DaftarHadiahHandler::NONCE_ACTION ), 'provider' => $settings['provider'] ] );
	}
}
