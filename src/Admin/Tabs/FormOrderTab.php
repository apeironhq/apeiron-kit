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
						<button type="button" class="apeiron-form-order-admin__tab" id="order-tab-spreadsheet" role="tab" aria-controls="order-panel-spreadsheet" aria-selected="false" tabindex="-1" data-order-settings-tab="spreadsheet"><?php esc_html_e( 'Penyimpanan', 'apeiron-kit' ); ?></button>
					</div>
					<div class="apeiron-card__body">
						<form id="apeiron-form-order-settings">
							<input type="hidden" id="order-storage-provider" name="storage_provider" value="api">
							<div class="apeiron-form-order-admin__panel" id="order-panel-general" role="tabpanel" aria-labelledby="order-tab-general" data-order-settings-panel="general">
								<fieldset><legend><?php esc_html_e( 'General', 'apeiron-kit' ); ?></legend>
									<p class="apeiron-form-order-admin__intro"><?php esc_html_e( 'Form ditampilkan melalui widget Elementor Form Order. Aktifkan setidaknya satu tujuan pengiriman di bawah.', 'apeiron-kit' ); ?></p>
									<div class="apeiron-form-order-admin__grid">
										<div class="apeiron-form-group">
											<label class="apeiron-label" for="order-delivery-target"><?php esc_html_e( 'Tujuan Pengiriman', 'apeiron-kit' ); ?></label>
											<select class="apeiron-select" id="order-delivery-target" name="delivery_target">
												<option value="whatsapp" <?php selected( $settings['delivery_target'], 'whatsapp' ); ?>><?php esc_html_e( 'WhatsApp', 'apeiron-kit' ); ?></option>
												<option value="spreadsheet" <?php selected( $settings['delivery_target'], 'spreadsheet' ); ?>><?php esc_html_e( 'Penyimpanan', 'apeiron-kit' ); ?></option>
												<option value="both" <?php selected( $settings['delivery_target'], 'both' ); ?>><?php esc_html_e( 'WhatsApp dan Penyimpanan', 'apeiron-kit' ); ?></option>
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
								<fieldset><legend data-order-storage-heading><?php esc_html_e( 'Dashboard Daftar Hadiah', 'apeiron-kit' ); ?></legend>
								<label class="apeiron-form-order-admin__check">
									<span data-order-storage-enable-label><?php esc_html_e( 'Aktifkan Penyimpanan', 'apeiron-kit' ); ?></span>
									<span class="apeiron-toggle"><input type="checkbox" name="spreadsheet_enabled" value="yes" <?php checked( $settings['spreadsheet_enabled'], 'yes' ); ?>><span class="apeiron-toggle__slider" aria-hidden="true"></span></span>
								</label>
								<div class="apeiron-form-order-admin__grid">
									<div class="apeiron-form-order-admin__field--wide" data-order-storage="api">
										<div class="apeiron-form-group">
											<label class="apeiron-label" for="order-api-url"><?php esc_html_e( 'URL API Form Order', 'apeiron-kit' ); ?></label>
											<input class="apeiron-input" id="order-api-url" name="api_url" type="url" value="<?php echo esc_attr( $settings['api_url'] ); ?>" placeholder="<?php echo esc_attr( FormOrderSettings::api_url( [ 'api_url' => '' ] ) ?: 'https://app.example.com/api/form-order' ); ?>">
											<p class="apeiron-hint"><?php esc_html_e( 'Kosongkan untuk URL otomatis. Protokol, host, dan port harus sama dengan koneksi Daftar Hadiah; HTTP boleh untuk lokal.', 'apeiron-kit' ); ?></p>
										</div>
										<p class="apeiron-hint"><?php esc_html_e( 'Token mengikuti koneksi Daftar Hadiah. Data hanya untuk admin.', 'apeiron-kit' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=apeiron-kit&tab=daftar-hadiah' ) ); ?>"><?php esc_html_e( 'Atur koneksi', 'apeiron-kit' ); ?></a></p>
										<p class="apeiron-hint" data-order-api-connection><?php echo esc_html( FormOrderSettings::api_connection()['message'] ); ?></p>
										<details class="apeiron-form-order-admin__setup"><summary><?php esc_html_e( 'Kontrak API Form Order khusus admin', 'apeiron-kit' ); ?></summary>
											<ol>
												<li><?php esc_html_e( 'POST JSON ke /api/form-order dengan Authorization: Bearer Token Website. Tidak mengikuti redirect dan tidak menyediakan operasi baca/export bagi pengguna formulir.', 'apeiron-kit' ); ?></li>
												<li><?php esc_html_e( 'Request: service=apeiron-form-order, schema_version=1, operation=ping/submit, correlation_id, website_id, website_url, visibility=admin_only. Submit menambah post_id, post_title, document_id, element_id, request_id (64 hex), fields, labels. Fields berasal dari validasi Elementor; tanggal tetap ISO dan checkbox berupa array.', 'apeiron-kit' ); ?></li>
												<li><?php esc_html_e( 'Respons wajib mengembalikan service, version=form-order-api-v1, schema_version, operation, correlation_id yang sama, success, data atau error. Data selalu memuat visibility=admin_only dan scope yang sama persis: website_id; untuk submit juga post_id, document_id, element_id.', 'apeiron-kit' ); ?></li>
												<li><?php esc_html_e( 'Ping hanya memverifikasi data.storage_ready=true, tidak membuat pesanan. Submit sukses memuat status=stored, order_id UUID, request_id yang sama, timestamp UTC ISO-8601. Backend wajib memakai idempotensi agar retry tidak membuat pesanan ganda.', 'apeiron-kit' ); ?></li>
												<li><?php esc_html_e( 'Akses baca, pengelolaan, dan ekspor data Form Order harus dibatasi untuk admin platform pada API dan database. Penanda admin_only merupakan kontrak backend; pembatasan akses tersebut tetap harus diterapkan pada web Vercel/Supabase.', 'apeiron-kit' ); ?></li>
											</ol>
										</details>
									</div>
								</div>
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
		<?php $this->render_config_payload( 'form-order', [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( FormOrderHandler::NONCE_ACTION ), 'storageProvider' => $settings['storage_provider'] ] ); ?>
		<?php
	}
}
