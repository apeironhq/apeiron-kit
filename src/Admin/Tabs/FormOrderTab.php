<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Admin\Ajax\FormOrderHandler;
use ApeironKit\Support\FormOrderSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormOrderTab extends AbstractTab {
	public function get_slug(): string { return 'form-order'; }
	public function get_title(): string { return __( 'Form Order', 'apeiron-kit' ); }

	public function render(): void {
		$settings = FormOrderSettings::get();
		$script = <<<'JAVASCRIPT'
const SPREADSHEET_ID = 'GANTI_DENGAN_ID_SPREADSHEET';
const APERION_CODE_VERSION = 'compact-v3';

function apeironReply(success, error, details) {
  const result = success ? { success: true, version: APERION_CODE_VERSION } : { success: false, error: error };
  if (details) result.details = details;
  return ContentService.createTextOutput(JSON.stringify(result))
    .setMimeType(ContentService.MimeType.JSON);
}

function apeironCell(value) {
  const text = Array.isArray(value) ? value.join(', ') : String(value == null ? '' : value);
  return /^(?:[=+\-@]|0\d|\d{16,}$)/.test(text) ? "'" + text : text;
}

function doPost(e) {
  let lock;
  try {
    const data = JSON.parse(e.postData.contents);
    const secret = PropertiesService.getScriptProperties().getProperty('APEIRON_FORM_ORDER_SECRET');
    if (!secret) return apeironReply(false, 'secret_missing');
    if (data.secret !== secret) return apeironReply(false, 'secret_mismatch');

    const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = spreadsheet.getSheetByName(data.sheet);
    if (!sheet) {
      const details = data.type === 'test' ? {
        spreadsheet: spreadsheet.getName(),
        sheets: spreadsheet.getSheets().slice(0, 10).map(tab => tab.getName())
      } : null;
      return apeironReply(false, 'sheet_not_found', details);
    }
    if (data.type === 'test') return apeironReply(true);
    if (data.type !== 'order' || !data.fields || typeof data.fields !== 'object' || Array.isArray(data.fields)) {
      return apeironReply(false, 'invalid_fields');
    }

    const requestId = typeof data.request_id === 'string' ? data.request_id : '';
    if (data.request_id && !/^[a-f0-9]{64}$/.test(requestId)) return apeironReply(false, 'invalid_fields');
    lock = LockService.getScriptLock();
    if (!lock.tryLock(5000)) return apeironReply(false, 'busy');
    if (requestId && sheet.getLastRow() > 1) {
      const receipts = sheet.getRange(2, 1, sheet.getLastRow() - 1, 1);
      const savedIds = receipts.getNotes();
      const savedTimes = receipts.getValues();
      if (savedIds.some((row, i) => row[0] === 'apeiron-request:' + requestId && savedTimes[i][0] !== '')) {
        return apeironReply(true);
      }
    }

    const fields = data.fields;
    const keys = Object.keys(fields);
    const labels = data.labels && typeof data.labels === 'object' && !Array.isArray(data.labels) ? data.labels : {};
    const notePrefix = 'apeiron-field:';
    let headers = [];
    let notes = [];
    if (sheet.getLastRow()) {
      const existing = sheet.getRange(1, 1, 1, sheet.getLastColumn());
      headers = existing.getValues()[0].map(String);
      notes = existing.getNotes()[0].map(String);
    }
    if (!headers.length) {
      sheet.appendRow(['Waktu']);
      headers = ['Waktu'];
      notes = [''];
    }

    const layoutProperty = 'APEIRON_FORM_ORDER_LAYOUT_' + SPREADSHEET_ID + '_' + sheet.getSheetId();
    const compactSetup = PropertiesService.getScriptProperties().getProperty(layoutProperty) !== 'compact-v1';
    const columnKeys = headers.map((title, i) => notes[i].startsWith(notePrefix) ? notes[i].slice(notePrefix.length) : title);
    let changed = false;
    Object.keys(labels).forEach(key => {
      const i = columnKeys.indexOf(key);
      if (i === -1) return;
      const label = apeironCell(labels[key] || key);
      if (headers[i] !== label) { headers[i] = label; changed = true; }
      if (notes[i] !== notePrefix + key) { notes[i] = notePrefix + key; changed = true; }
    });
    const firstNewColumn = headers.length + 1;
    keys.forEach(key => {
      if (columnKeys.includes(key)) return;
      columnKeys.push(key);
      headers.push(apeironCell(labels[key] || key));
      notes.push(notePrefix + key);
      changed = true;
    });
    if (sheet.getMaxColumns() < headers.length) {
      sheet.insertColumnsAfter(sheet.getMaxColumns(), headers.length - sheet.getMaxColumns());
    }
    const header = sheet.getRange(1, 1, 1, headers.length);
    if (changed) {
      header.setValues([headers]);
      header.setNotes([notes]);
    }

    if (compactSetup) {
      header.setFontWeight('bold').setBackground('#eaf3f9').setFontColor('#243247').setFontSize(10).setWrap(true).setVerticalAlignment('middle');
      sheet.setFrozenRows(1);
      sheet.setFrozenColumns(2);
      sheet.setColumnWidth(1, 135);
      sheet.setColumnWidths(2, headers.length - 1, 160);
      sheet.setRowHeight(1, 36);
      if (sheet.getLastRow() > 1) sheet.getRange(2, 1, sheet.getLastRow() - 1, headers.length).setWrap(true);
      PropertiesService.getScriptProperties().setProperty(layoutProperty, 'compact-v1');
    } else {
      if (firstNewColumn <= headers.length) {
        sheet.setColumnWidths(firstNewColumn, headers.length - firstNewColumn + 1, 160);
        sheet.getRange(1, firstNewColumn, 1, headers.length - firstNewColumn + 1)
          .setFontWeight('bold').setBackground('#eaf3f9').setFontColor('#243247').setFontSize(10).setWrap(true);
      }
    }
    const orderRow = columnKeys.map(key => {
      if (key === 'Waktu') return new Date();
      return apeironCell(Object.prototype.hasOwnProperty.call(fields, key) ? fields[key] : '');
    });
    if (requestId) {
      const nextRow = sheet.getLastRow() + 1;
      if (nextRow > sheet.getMaxRows()) sheet.insertRowsAfter(sheet.getMaxRows(), 1);
      sheet.getRange(nextRow, 1).setNote('apeiron-request:' + requestId);
      sheet.getRange(nextRow, 1, 1, orderRow.length).setValues([orderRow]);
    } else { sheet.appendRow(orderRow); }
    return apeironReply(true);
  } catch (error) {
    return apeironReply(false, 'script_error');
  } finally {
    if (lock && lock.hasLock()) lock.releaseLock();
  }
}
JAVASCRIPT;
		?>
		<section class="apeiron-elements-section apeiron-form-order-admin">
			<div class="apeiron-elements-toolbar">
				<div class="apeiron-form-order-admin__heading">
					<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Form Order', 'apeiron-kit' ); ?></h2>
					<p class="apeiron-form-order-admin__description"><?php esc_html_e( 'Atur pengiriman pesanan undangan.', 'apeiron-kit' ); ?></p>
				</div>
			</div>
			<div class="apeiron-elements-content">
				<div class="apeiron-card">
					<div class="apeiron-form-order-admin__tabs" role="tablist" aria-label="<?php echo esc_attr( __( 'Pengaturan Form Order', 'apeiron-kit' ) ); ?>">
						<button type="button" class="apeiron-form-order-admin__tab is-active" id="order-tab-general" role="tab" aria-controls="order-panel-general" aria-selected="true" tabindex="0" data-order-settings-tab="general"><?php esc_html_e( 'General', 'apeiron-kit' ); ?></button>
						<button type="button" class="apeiron-form-order-admin__tab" id="order-tab-whatsapp" role="tab" aria-controls="order-panel-whatsapp" aria-selected="false" tabindex="-1" data-order-settings-tab="whatsapp"><?php esc_html_e( 'WhatsApp', 'apeiron-kit' ); ?></button>
						<button type="button" class="apeiron-form-order-admin__tab" id="order-tab-spreadsheet" role="tab" aria-controls="order-panel-spreadsheet" aria-selected="false" tabindex="-1" data-order-settings-tab="spreadsheet"><?php esc_html_e( 'Spreadsheet', 'apeiron-kit' ); ?></button>
					</div>
					<div class="apeiron-card__body">
						<form id="apeiron-form-order-settings">
							<div class="apeiron-form-order-admin__panel" id="order-panel-general" role="tabpanel" aria-labelledby="order-tab-general" data-order-settings-panel="general">
								<fieldset><legend><?php esc_html_e( 'General', 'apeiron-kit' ); ?></legend>
									<p class="apeiron-form-order-admin__intro"><?php esc_html_e( 'Form ditampilkan melalui widget Elementor Form Order. Aktifkan setidaknya satu tujuan pengiriman di bawah.', 'apeiron-kit' ); ?></p>
									<div class="apeiron-form-order-admin__grid">
										<div class="apeiron-form-group">
											<label class="apeiron-label" for="order-delivery-target"><?php esc_html_e( 'Tujuan Pengiriman', 'apeiron-kit' ); ?></label>
											<select class="apeiron-select" id="order-delivery-target" name="delivery_target">
												<option value="whatsapp" <?php selected( $settings['delivery_target'], 'whatsapp' ); ?>><?php esc_html_e( 'WhatsApp', 'apeiron-kit' ); ?></option>
												<option value="spreadsheet" <?php selected( $settings['delivery_target'], 'spreadsheet' ); ?>><?php esc_html_e( 'Spreadsheet', 'apeiron-kit' ); ?></option>
												<option value="both" <?php selected( $settings['delivery_target'], 'both' ); ?>><?php esc_html_e( 'WhatsApp dan Spreadsheet', 'apeiron-kit' ); ?></option>
											</select>
										</div>
										<div class="apeiron-form-group">
											<label class="apeiron-label" for="order-success-message"><?php esc_html_e( 'Pesan Berhasil', 'apeiron-kit' ); ?></label>
											<input class="apeiron-input" id="order-success-message" type="text" name="success_message" value="<?php echo esc_attr( $settings['success_message'] ); ?>">
										</div>
									</div>
								</fieldset>
							</div>
							<div class="apeiron-form-order-admin__panel" id="order-panel-whatsapp" role="tabpanel" aria-labelledby="order-tab-whatsapp" data-order-settings-panel="whatsapp" hidden>
								<fieldset><legend><?php esc_html_e( 'WhatsApp', 'apeiron-kit' ); ?></legend>
								<label class="apeiron-form-order-admin__check">
									<?php esc_html_e( 'Aktifkan WhatsApp', 'apeiron-kit' ); ?>
									<span class="apeiron-toggle"><input type="checkbox" name="whatsapp_enabled" value="yes" <?php checked( $settings['whatsapp_enabled'], 'yes' ); ?>><span class="apeiron-toggle__slider" aria-hidden="true"></span></span>
								</label>
								<div class="apeiron-form-order-admin__grid">
									<div class="apeiron-form-group">
										<label class="apeiron-label" for="order-wa-phone"><?php esc_html_e( 'Nomor WhatsApp Tujuan', 'apeiron-kit' ); ?></label>
										<input class="apeiron-input" id="order-wa-phone" type="tel" name="whatsapp_phone" value="<?php echo esc_attr( $settings['whatsapp_phone'] ); ?>" placeholder="628123456789">
									</div>
									<div class="apeiron-form-group">
										<label class="apeiron-label" for="order-wa-behavior"><?php esc_html_e( 'Perilaku Setelah Submit', 'apeiron-kit' ); ?></label>
										<select class="apeiron-select" id="order-wa-behavior" name="whatsapp_behavior"><option value="new_tab" <?php selected( $settings['whatsapp_behavior'], 'new_tab' ); ?>><?php esc_html_e( 'Buka tab baru', 'apeiron-kit' ); ?></option><option value="same_tab" <?php selected( $settings['whatsapp_behavior'], 'same_tab' ); ?>><?php esc_html_e( 'Buka di tab yang sama', 'apeiron-kit' ); ?></option></select>
									</div>
									<div class="apeiron-form-group apeiron-form-order-admin__field--wide">
										<label class="apeiron-label" for="order-wa-template"><?php esc_html_e( 'Template Pesan', 'apeiron-kit' ); ?></label>
										<textarea class="apeiron-input" id="order-wa-template" name="whatsapp_template" rows="4"><?php echo esc_textarea( $settings['whatsapp_template'] ); ?></textarea>
										<p class="apeiron-hint"><?php esc_html_e( 'Gunakan {details} untuk menyertakan seluruh data pesanan.', 'apeiron-kit' ); ?></p>
									</div>
								</div>
								</fieldset>
							</div>
							<div class="apeiron-form-order-admin__panel" id="order-panel-spreadsheet" role="tabpanel" aria-labelledby="order-tab-spreadsheet" data-order-settings-panel="spreadsheet" hidden>
								<fieldset><legend><?php esc_html_e( 'Google Spreadsheet', 'apeiron-kit' ); ?></legend>
								<label class="apeiron-form-order-admin__check">
									<?php esc_html_e( 'Aktifkan Spreadsheet', 'apeiron-kit' ); ?>
									<span class="apeiron-toggle"><input type="checkbox" name="spreadsheet_enabled" value="yes" <?php checked( $settings['spreadsheet_enabled'], 'yes' ); ?>><span class="apeiron-toggle__slider" aria-hidden="true"></span></span>
								</label>
								<div class="apeiron-form-order-admin__grid">
									<div class="apeiron-form-group apeiron-form-order-admin__field--wide">
										<label class="apeiron-label" for="order-webhook"><?php esc_html_e( 'Google Apps Script Webhook URL', 'apeiron-kit' ); ?></label>
										<input class="apeiron-input" id="order-webhook" type="url" name="webhook_url" value="<?php echo esc_attr( $settings['webhook_url'] ); ?>" placeholder="https://script.google.com/macros/s/.../exec">
									</div>
									<div class="apeiron-form-group">
										<label class="apeiron-label" for="order-secret"><?php esc_html_e( 'Secret Key', 'apeiron-kit' ); ?></label>
										<input class="apeiron-input" id="order-secret" type="password" name="secret_key" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( FormOrderSettings::has_secret() ? __( 'Tersimpan — kosongkan untuk mempertahankan', 'apeiron-kit' ) : __( 'Masukkan Secret Key', 'apeiron-kit' ) ); ?>">
									</div>
									<div class="apeiron-form-group">
										<label class="apeiron-label" for="order-sheet"><?php esc_html_e( 'Nama Sheet (nama tab di bawah Google Sheets)', 'apeiron-kit' ); ?></label>
										<input class="apeiron-input" id="order-sheet" type="text" name="sheet_name" value="<?php echo esc_attr( $settings['sheet_name'] ); ?>">
									</div>
								</div>
								<details class="apeiron-form-order-admin__setup">
									<summary><?php esc_html_e( 'Panduan sekali pasang — salin kode untuk Code.gs', 'apeiron-kit' ); ?></summary>
									<ol>
										<li><?php esc_html_e( 'Buka Google Spreadsheet tujuan, buat tab dengan nama yang sama seperti Nama Sheet di atas, lalu pilih Extensions → Apps Script.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Di file Code.gs, hapus contoh kode bawaan dan tempel kode di bawah. Ganti GANTI_DENGAN_ID_SPREADSHEET dengan bagian di antara /d/ dan /edit pada URL spreadsheet. Simpan.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Di Apps Script buka Project Settings (ikon roda gigi) → Script properties → Add script property. Isi Property: APEIRON_FORM_ORDER_SECRET, Value: Secret Key yang sama dengan dashboard ini. Jika key tersimpan tetapi sudah lupa, buat key baru dan simpan di kedua tempat.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Pilih Deploy → New deployment → Web app. Atur Execute as: Me dan Who has access: Anyone. Izinkan akses, lalu salin URL Web app yang berakhir /exec ke kolom Google Apps Script Webhook URL di atas. Jika kode diubah kemudian, buat versi baru di Deploy → Manage deployments → Edit → New version.', 'apeiron-kit' ); ?></li>
										<li><?php esc_html_e( 'Aktifkan Spreadsheet, klik Simpan Pengaturan, lalu Test Connection. Tes ini tidak menambah baris pesanan. Untuk WhatsApp sekaligus Spreadsheet, pilih keduanya di General.', 'apeiron-kit' ); ?></li>
									</ol>
									<p class="apeiron-hint"><?php esc_html_e( 'Setelah Code.gs versi baru aktif, pesanan berikutnya akan merapikan header lama tanpa mengubah isi baris pesanan yang sudah ada.', 'apeiron-kit' ); ?></p>
									<label class="apeiron-label" for="apeiron-form-order-code"><?php esc_html_e( 'Kode untuk file Code.gs', 'apeiron-kit' ); ?></label>
									<textarea id="apeiron-form-order-code" readonly spellcheck="false" rows="16"><?php echo esc_textarea( $script ); ?></textarea>
									<button type="button" class="apeiron-btn apeiron-btn--secondary" id="apeiron-form-order-copy"><?php esc_html_e( 'Salin kode Code.gs', 'apeiron-kit' ); ?></button>
									<p id="apeiron-form-order-copy-feedback" role="status" aria-live="polite"></p>
								</details>
								<button type="button" class="apeiron-btn apeiron-btn--secondary" id="apeiron-form-order-test"><?php esc_html_e( 'Test Connection', 'apeiron-kit' ); ?></button>
								</fieldset>
							</div>
							<div class="apeiron-actions apeiron-form-order-admin__actions">
								<button type="submit" class="apeiron-btn apeiron-btn--primary"><?php echo $this->get_save_icon_markup(); ?> <?php esc_html_e( 'Simpan Pengaturan', 'apeiron-kit' ); ?></button>
							</div>
							<p id="apeiron-form-order-feedback" role="status" aria-live="polite"></p>
						</form>
					</div>
				</div>
			</div>
		</section>
		<?php $this->render_config_payload( 'form-order', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( FormOrderHandler::NONCE_ACTION ) ] ); ?>
		<?php
	}
}
