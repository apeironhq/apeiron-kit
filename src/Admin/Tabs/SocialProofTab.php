<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Admin\Ajax\SocialProofHandler;
use ApeironKit\Support\SocialProofSettings;

class SocialProofTab extends AbstractTab {

	public function get_slug(): string {
		return 'social-proof';
	}

	public function get_title(): string {
		return __( 'Aktivitas', 'apeiron-kit' );
	}

	public function render(): void {
		$settings = SocialProofSettings::get();
		$entries = $settings['entries'] ?? [];
		$total_entries = count( $entries );
		?>
		<div class="apeiron-sp-wrap">
			<?php $this->render_social_proof_section( $settings, $entries, $total_entries ); ?>
			<?php $this->render_backup_import_modal(); ?>
			<div id="apeiron-toast-container" class="apeiron-toast-container"></div>
		</div>

		<?php $this->render_runtime_config(); ?>
		<?php
	}

	private function render_social_proof_section( array $settings, array $entries, int $total_entries ): void {
		?>
		<section class="apeiron-elements-section apeiron-sp-section">
			<div class="apeiron-elements-toolbar apeiron-sp-toolbar">
				<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Aktivitas', 'apeiron-kit' ); ?></h2>

				<div class="apeiron-sp-toolbar__stats" aria-label="<?php esc_attr_e( 'Statistik Aktivitas', 'apeiron-kit' ); ?>">
					<span class="apeiron-sp-toolbar__stat">
						<strong><?php echo esc_html( number_format_i18n( $total_entries ) ); ?></strong>
						<?php esc_html_e( 'Aktivitas', 'apeiron-kit' ); ?>
					</span>
				</div>

				<span class="apeiron-elements-toolbar__spacer"></span>

				<div class="apeiron-sp-toolbar__backup-actions" aria-label="<?php esc_attr_e( 'Backup dan restore Aktivitas', 'apeiron-kit' ); ?>">
					<button type="button" id="download-social-proof-backup-btn" class="apeiron-btn apeiron-btn--secondary">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
						<span><?php esc_html_e( 'Download Backup', 'apeiron-kit' ); ?></span>
					</button>
					<button type="button" id="import-social-proof-backup-btn" class="apeiron-btn apeiron-btn--primary">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
						<span><?php esc_html_e( 'Import Backup', 'apeiron-kit' ); ?></span>
					</button>
					<input type="file" id="social-proof-backup-input" class="apeiron-sp-backup-input" accept="application/json,.json">
				</div>
			</div>

			<div class="apeiron-elements-content apeiron-sp-content">
				<div class="apeiron-sp-grid">
					<div class="apeiron-sp-main">
						<?php $this->render_settings_card( $settings ); ?>
					</div>
					<div class="apeiron-sp-sidebar">
						<?php $this->render_preview_card( $entries ); ?>
					</div>
				</div>
				<?php $this->render_client_data_card( $entries, $total_entries ); ?>
			</div>
		</section>
		<?php
	}

	private function render_backup_import_modal(): void {
		?>
		<div id="apeiron-sp-import-modal" class="apeiron-modal apeiron-sp-backup-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="apeiron-sp-import-modal-title">
			<div class="apeiron-modal__backdrop" data-sp-backup-close></div>
			<div class="apeiron-modal__dialog" role="document">
				<div class="apeiron-modal__header">
					<div class="apeiron-modal__icon apeiron-sp-backup-modal__icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
					</div>
					<h3 id="apeiron-sp-import-modal-title" class="apeiron-modal__title"><?php esc_html_e( 'Konfirmasi Import Backup', 'apeiron-kit' ); ?></h3>
				</div>
				<p class="apeiron-modal__desc"><?php esc_html_e( 'Apeiron Kit menemukan backup Aktivitas yang valid. Tinjau jumlah data sebelum melanjutkan.', 'apeiron-kit' ); ?></p>
				<div id="apeiron-sp-backup-summary" class="apeiron-sp-backup-summary"></div>
				<div class="apeiron-modal__warning"><?php esc_html_e( 'Import akan menimpa seluruh data Aktivitas saat ini, termasuk aktivitas dan pengaturan notifikasi.', 'apeiron-kit' ); ?></div>
				<div class="apeiron-modal__actions">
					<button type="button" class="apeiron-btn apeiron-btn--secondary" data-sp-backup-close><?php esc_html_e( 'Batalkan', 'apeiron-kit' ); ?></button>
					<button type="button" id="confirm-social-proof-import-btn" class="apeiron-btn apeiron-btn--primary">
						<span><?php esc_html_e( 'Import & Overwrite', 'apeiron-kit' ); ?></span>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_settings_card( array $settings ): void {
		?>
		<div class="apeiron-card apeiron-sp-card--settings">
			<div class="apeiron-card__header">
				<div class="apeiron-card__header-left">
					<div class="apeiron-card__icon apeiron-card__icon--primary">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
					</div>
					<div>
						<h3 class="apeiron-card__title"><?php esc_html_e( 'Perilaku Notifikasi', 'apeiron-kit' ); ?></h3>
						<p class="apeiron-card__subtitle"><?php esc_html_e( 'Atur timing, posisi, dan format pesan', 'apeiron-kit' ); ?></p>
					</div>
				</div>
			</div>
			<div class="apeiron-card__body">
				<form id="sp-settings-form">
					<div class="apeiron-sp-form-row">
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Lama Notifikasi Tampil', 'apeiron-kit' ); ?></label>
							<select name="display_duration" class="apeiron-select">
								<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['display_duration'], $i ); ?>><?php echo esc_html( $i . ' detik' ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Jeda Antar Notifikasi', 'apeiron-kit' ); ?></label>
							<select name="interval_duration" class="apeiron-select">
								<?php for ( $i = 1; $i <= 30; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['interval_duration'], $i ); ?>><?php echo esc_html( $i . ' detik' ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
					</div>

					<div class="apeiron-sp-form-row">
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Jenis Animasi', 'apeiron-kit' ); ?></label>
							<select name="animation_type" class="apeiron-select">
								<option value="fade" <?php selected( $settings['animation_type'], 'fade' ); ?>>Fade</option>
								<option value="slide-top" <?php selected( $settings['animation_type'], 'slide-top' ); ?>>Slide dari Atas</option>
								<option value="slide-bottom" <?php selected( $settings['animation_type'], 'slide-bottom' ); ?>>Slide dari Bawah</option>
								<option value="slide-left" <?php selected( $settings['animation_type'], 'slide-left' ); ?>>Slide dari Kiri</option>
								<option value="slide-right" <?php selected( $settings['animation_type'], 'slide-right' ); ?>>Slide dari Kanan</option>
							</select>
						</div>
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Posisi Popup', 'apeiron-kit' ); ?></label>
							<select name="popup_position" class="apeiron-select">
								<option value="top-right" <?php selected( $settings['popup_position'], 'top-right' ); ?>>Atas Kanan</option>
								<option value="top-left" <?php selected( $settings['popup_position'], 'top-left' ); ?>>Atas Kiri</option>
								<option value="bottom-right" <?php selected( $settings['popup_position'], 'bottom-right' ); ?>>Bawah Kanan</option>
								<option value="bottom-left" <?php selected( $settings['popup_position'], 'bottom-left' ); ?>>Bawah Kiri</option>
							</select>
						</div>
					</div>

					<div class="apeiron-sp-form-row">
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Delay Awal', 'apeiron-kit' ); ?></label>
							<select name="initial_delay" class="apeiron-select">
								<?php for ( $i = 0; $i <= 30; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['initial_delay'] ?? 0, $i ); ?>><?php echo esc_html( 0 === $i ? __( 'Langsung', 'apeiron-kit' ) : $i . ' detik' ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Maksimal Tampil per Sesi', 'apeiron-kit' ); ?></label>
							<select name="max_notifications" class="apeiron-select">
								<option value="0" <?php selected( $settings['max_notifications'] ?? 0, 0 ); ?>><?php esc_html_e( 'Tanpa batas', 'apeiron-kit' ); ?></option>
								<?php for ( $i = 1; $i <= 20; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['max_notifications'] ?? 0, $i ); ?>><?php echo esc_html( $i . ' kali' ); ?></option>
								<?php endfor; ?>
							</select>
						</div>
					</div>

					<div class="apeiron-sp-form-row apeiron-sp-form-row--message">
						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Format Pesan', 'apeiron-kit' ); ?></label>
							<input type="text" name="text_template" class="apeiron-input" value="<?php echo esc_attr( $settings['text_template'] ); ?>" placeholder="{name} telah membeli {product} pada:">
						</div>

						<div class="apeiron-form-group">
							<label class="apeiron-label"><?php esc_html_e( 'Sudut Foto', 'apeiron-kit' ); ?></label>
							<input type="number" name="image_border_radius" class="apeiron-input" value="<?php echo esc_attr( $settings['image_border_radius'] ?? 10 ); ?>" min="0" max="50" step="1">
						</div>
					</div>

					<div class="apeiron-actions apeiron-sp-card-actions">
						<button type="submit" class="apeiron-btn apeiron-btn--primary">
							<?php echo $this->get_save_icon_markup(); ?>
							<span><?php esc_html_e( 'Simpan Pengaturan', 'apeiron-kit' ); ?></span>
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	private function render_client_data_card( array $entries, int $total_entries ): void {
		?>
		<div class="apeiron-card apeiron-sp-card--activity">
			<div class="apeiron-card__header">
				<div class="apeiron-card__header-left">
					<div class="apeiron-card__icon apeiron-card__icon--blue">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
					</div>
					<div>
						<h3 class="apeiron-card__title"><?php esc_html_e( 'Data Aktivitas', 'apeiron-kit' ); ?></h3>
						<p class="apeiron-card__subtitle"><?php printf( esc_html__( '%d aktivitas tersimpan', 'apeiron-kit' ), $total_entries ); ?></p>
					</div>
				</div>
			</div>

			<div class="apeiron-card__body apeiron-card__body--activity">
				<div class="apeiron-sp-activity-grid">
					<div class="apeiron-sp-activity-panel apeiron-sp-activity-panel--form">
						<div class="apeiron-sp-activity-panel__body">
							<?php $this->render_add_entry_form(); ?>
						</div>
					</div>

					<div class="apeiron-sp-activity-panel apeiron-sp-activity-panel--list">
						<div class="apeiron-sp-activity-panel__body apeiron-sp-activity-panel__body--list">
							<?php $this->render_entries_list( $entries ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_add_entry_form(): void {
		?>
		<div class="apeiron-sp-entry-form">
			<div class="apeiron-sp-form-row">
				<div class="apeiron-form-group">
					<label class="apeiron-label"><?php esc_html_e( 'Nama Pelanggan', 'apeiron-kit' ); ?> <span class="apeiron-label__required">*</span></label>
					<input type="text" id="entry-name" class="apeiron-input" placeholder="Contoh: Budi & Sari">
				</div>
				<div class="apeiron-form-group">
					<label class="apeiron-label"><?php esc_html_e( 'Produk / Layanan', 'apeiron-kit' ); ?> <span class="apeiron-label__required">*</span></label>
					<input type="text" id="entry-product" class="apeiron-input" placeholder="Contoh: Tema Elegant">
				</div>
			</div>

			<div class="apeiron-sp-form-row">
				<div class="apeiron-form-group">
					<label class="apeiron-label"><?php esc_html_e( 'Foto Pelanggan (Opsional)', 'apeiron-kit' ); ?></label>

					<div class="apeiron-sp-upload-strip" id="client-photo-upload-zone" role="button" tabindex="0">
						<input type="file" id="client-photo-upload-input" accept=".png,.jpg,.jpeg,.gif,.webp">
						<div class="apeiron-sp-upload-strip__icon">
							<span class="dashicons dashicons-cloud-upload"></span>
						</div>
						<div class="apeiron-sp-upload-strip__text">
							<strong><?php esc_html_e( 'Upload Foto', 'apeiron-kit' ); ?></strong>
						</div>
					</div>
					<p class="apeiron-hint"><?php esc_html_e( 'PNG, JPG, GIF, WebP', 'apeiron-kit' ); ?></p>

					<div id="client-photo-preview">
						<div>
							<img id="client-photo-preview-img" src="" alt="Preview">
							<div id="client-photo-preview-info">
								<div class="preview-label"><?php esc_html_e( 'Foto Pelanggan', 'apeiron-kit' ); ?></div>
								<div class="preview-filename" id="client-photo-filename"><?php esc_html_e( 'Foto terpilih', 'apeiron-kit' ); ?></div>
							</div>
							<button type="button" id="remove-client-photo-btn" title="<?php esc_attr_e( 'Hapus Foto', 'apeiron-kit' ); ?>" aria-label="<?php esc_attr_e( 'Hapus foto pelanggan', 'apeiron-kit' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
							</button>
						</div>
					</div>

					<div class="apeiron-progress" id="client-photo-progress">
						<div class="apeiron-progress__bar">
							<div class="apeiron-progress__fill" id="client-photo-progress-fill"></div>
						</div>
						<div class="apeiron-progress__text" id="client-photo-progress-text">0%</div>
					</div>

					<input type="hidden" id="entry-image" value="">
				</div>
				<div class="apeiron-form-group">
					<label class="apeiron-label"><?php esc_html_e( 'Tanggal & Waktu', 'apeiron-kit' ); ?> <span class="apeiron-label__required">*</span></label>
					<input type="datetime-local" id="entry-datetime" class="apeiron-input">
					<p class="apeiron-hint"><?php esc_html_e( 'Waktu aktivitas yang akan ditampilkan', 'apeiron-kit' ); ?></p>
				</div>
			</div>

			<div class="apeiron-actions apeiron-sp-card-actions">
				<button type="button" id="add-entry-btn" class="apeiron-btn apeiron-btn--primary">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
					<span><?php esc_html_e( 'Tambah Aktivitas', 'apeiron-kit' ); ?></span>
				</button>
			</div>
		</div>
		<?php
	}

	private function render_entries_list( array $entries ): void {
		?>
		<div class="apeiron-sp-entries-scroll" id="entries-list">
			<?php if ( empty( $entries ) ) : ?>
				<div class="apeiron-empty">
					<div class="apeiron-empty__icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
					</div>
					<div class="apeiron-empty__title"><?php esc_html_e( 'Belum ada data aktivitas', 'apeiron-kit' ); ?></div>
					<div class="apeiron-empty__text"><?php esc_html_e( 'Tambahkan aktivitas pelanggan untuk menampilkan notifikasi Aktivitas', 'apeiron-kit' ); ?></div>
				</div>
			<?php else : ?>
				<div class="apeiron-sp-entries">
				<?php foreach ( $entries as $index => $entry ) : 
					$entry_image = $entry['image'] ?? '';
					$entry_product = $entry['product'] ?? 'Apeiron';
					$datetime = ! empty( $entry['datetime'] ) ? date_i18n( 'j M Y, H:i', strtotime( $entry['datetime'] ) ) : '-';
				?>
					<div class="apeiron-sp-entry" data-index="<?php echo esc_attr( $index ); ?>">
						<div class="apeiron-sp-entry__avatar">
							<?php if ( ! empty( $entry_image ) ) : ?>
								<img src="<?php echo esc_url( $entry_image ); ?>" alt="<?php echo esc_attr( $entry['name'] ?? '' ); ?>">
							<?php else : ?>
								<span class="apeiron-sp-entry__avatar-placeholder">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								</span>
							<?php endif; ?>
						</div>
						<div class="apeiron-sp-entry__content">
							<div class="apeiron-sp-entry__name"><?php echo esc_html( $entry['name'] ?? '' ); ?></div>
							<div class="apeiron-sp-entry__meta">
								<span>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
									<?php echo esc_html( $entry_product ); ?>
								</span>
								<span>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
									<?php echo esc_html( $datetime ); ?>
								</span>
							</div>
						</div>
						<div class="apeiron-sp-entry__actions">
							<button type="button" class="apeiron-btn apeiron-btn--danger apeiron-btn--sm delete-entry-btn" data-index="<?php echo esc_attr( $index ); ?>" aria-label="<?php esc_attr_e( 'Hapus aktivitas ini', 'apeiron-kit' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_preview_card( array $entries ): void {
		$preview_image = ! empty( $entries ) && ! empty( $entries[0]['image'] ) ? $entries[0]['image'] : '';
		$preview_name = ! empty( $entries ) ? $entries[0]['name'] ?? 'Apeiron & Partners' : 'Apeiron & Partners';
		$preview_product = ! empty( $entries ) ? $entries[0]['product'] ?? 'Tema Elegant' : 'Tema Elegant';
		$preview_date = ! empty( $entries ) && ! empty( $entries[0]['datetime'] ) 
			? date_i18n( 'l, j F Y H:i (T)', strtotime( $entries[0]['datetime'] ) )
			: date_i18n( 'l, j F Y H:i (T)' );
		?>
		<div class="apeiron-card apeiron-sp-preview-card">
			<div class="apeiron-card__header">
				<div class="apeiron-card__header-left">
					<div class="apeiron-card__icon apeiron-card__icon--amber">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
					</div>
					<div>
						<h3 class="apeiron-card__title"><?php esc_html_e( 'Preview', 'apeiron-kit' ); ?></h3>
						<p class="apeiron-card__subtitle"><?php esc_html_e( 'Tampilan notifikasi', 'apeiron-kit' ); ?></p>
					</div>
				</div>
			</div>
			<div class="apeiron-card__body apeiron-card__body--flush">
				<div class="apeiron-sp-preview-area">
					<div class="apeiron-sp-preview-popup" id="preview-popup">
						<div class="apeiron-sp-preview-popup__img">
							<?php if ( ! empty( $preview_image ) ) : ?>
								<img src="<?php echo esc_url( $preview_image ); ?>" alt="Preview">
							<?php else : ?>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
							<?php endif; ?>
						</div>
						<div class="apeiron-sp-preview-popup__text">
							<div class="apeiron-sp-preview-popup__name" id="preview-name"><?php echo esc_html( $preview_name ); ?></div>
							<div class="apeiron-sp-preview-popup__desc" id="preview-desc">telah membeli <em id="preview-product"><?php echo esc_html( $preview_product ); ?></em> pada:</div>
							<div class="apeiron-sp-preview-popup__date" id="preview-date"><?php echo esc_html( $preview_date ); ?></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_runtime_config(): void {
		$nonce = wp_create_nonce( SocialProofHandler::get_nonce_action() );
		$ajax_url = admin_url( 'admin-ajax.php' );

		$this->render_config_payload(
			'social-proof',
			[
				'ajaxUrl' => $ajax_url,
				'nonce'   => $nonce,
			]
		);
	}
}
