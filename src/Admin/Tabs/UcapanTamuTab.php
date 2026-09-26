<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Admin\Ajax\UcapanTamuHandler;
use ApeironKit\Support\UcapanTamuSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UcapanTamuTab extends AbstractTab {

	public function get_slug(): string {
		return 'ucapan-tamu';
	}

	public function get_title(): string {
		return __( 'Ucapan Tamu', 'apeiron-kit' );
	}

	public function render(): void {
		$settings = wp_parse_args( UcapanTamuSettings::get_settings(), UcapanTamuSettings::defaults() );
		?>
		<div class="apeiron-ut-wrap">
			<section class="apeiron-elements-section apeiron-ut-section">
				<div class="apeiron-elements-toolbar apeiron-ut-toolbar">
					<div class="apeiron-ut-toolbar__copy">
						<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Ucapan Tamu', 'apeiron-kit' ); ?></h2>
						<p><?php esc_html_e( 'Kebijakan keamanan yang berlaku untuk seluruh widget komentar.', 'apeiron-kit' ); ?></p>
					</div>
					<span class="apeiron-elements-toolbar__spacer"></span>
					<span id="apeiron-ut-live-status" class="apeiron-ut-live-status is-saved" role="status"><?php esc_html_e( 'Tersimpan', 'apeiron-kit' ); ?></span>
					<button type="submit" form="apeiron-ut-form" id="apeiron-ut-save-btn" class="apeiron-btn apeiron-btn--primary apeiron-ut-toolbar__save">
						<?php echo $this->get_save_icon_markup(); ?>
						<?php esc_html_e( 'Simpan', 'apeiron-kit' ); ?>
					</button>
				</div>

				<div class="apeiron-elements-content apeiron-ut-content">
					<form id="apeiron-ut-form" class="apeiron-ut-settings-form" autocomplete="off">
						<div class="apeiron-ut-policy-grid">
							<?php $this->render_moderation( $settings ); ?>
							<?php $this->render_message_limit( $settings ); ?>
							<?php $this->render_toggle( 'prevent_duplicate', __( 'Pesan Duplikat', 'apeiron-kit' ), __( 'Tolak pesan yang sama dari tamu yang sama.', 'apeiron-kit' ), $settings['prevent_duplicate'] ); ?>
							<?php $this->render_toggle( 'enable_rate_limit', __( 'Rate Limit', 'apeiron-kit' ), __( 'Batasi request anonim berulang untuk mengurangi spam.', 'apeiron-kit' ), $settings['enable_rate_limit'] ); ?>
						</div>
					</form>
				</div>
			</section>

			<div id="apeiron-ut-toast" class="apeiron-toast-container"></div>
		</div>
		<?php
		$this->render_runtime_config();
	}

	private function render_moderation( array $settings ): void {
		?>
		<div class="apeiron-ut-policy-item">
			<div class="apeiron-ut-policy-item__copy">
				<label class="apeiron-label" for="apeiron-ut-comment-moderation"><?php esc_html_e( 'Moderasi Komentar', 'apeiron-kit' ); ?></label>
				<p><?php esc_html_e( 'Tentukan apakah komentar langsung tampil atau menunggu tinjauan.', 'apeiron-kit' ); ?></p>
			</div>
			<select id="apeiron-ut-comment-moderation" name="comment_moderation" class="apeiron-select">
				<option value="auto" <?php selected( $settings['comment_moderation'], 'auto' ); ?>><?php esc_html_e( 'Terbit otomatis', 'apeiron-kit' ); ?></option>
				<option value="manual" <?php selected( $settings['comment_moderation'], 'manual' ); ?>><?php esc_html_e( 'Tinjau lebih dulu', 'apeiron-kit' ); ?></option>
			</select>
		</div>
		<?php
	}

	private function render_message_limit( array $settings ): void {
		?>
		<div class="apeiron-ut-policy-item">
			<div class="apeiron-ut-policy-item__copy">
				<label class="apeiron-label" for="apeiron-ut-message-limit"><?php esc_html_e( 'Panjang Pesan', 'apeiron-kit' ); ?></label>
				<p><?php esc_html_e( 'Batas maksimum saat tamu mengirim atau mengedit komentar.', 'apeiron-kit' ); ?></p>
			</div>
			<div class="apeiron-input-group">
				<input id="apeiron-ut-message-limit" type="number" name="max_message_length" value="<?php echo esc_attr( (int) $settings['max_message_length'] ); ?>" min="50" max="5000" class="apeiron-input" />
				<span class="apeiron-input-addon"><?php esc_html_e( 'karakter', 'apeiron-kit' ); ?></span>
			</div>
		</div>
		<?php
	}

	private function render_toggle( string $name, string $label, string $description, string $value ): void {
		$control_id = 'apeiron-ut-' . sanitize_html_class( $name );
		?>
		<div class="apeiron-ut-policy-item">
			<div class="apeiron-ut-policy-item__copy">
				<label class="apeiron-label" for="<?php echo esc_attr( $control_id ); ?>"><?php echo esc_html( $label ); ?></label>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<label class="apeiron-toggle-label" for="<?php echo esc_attr( $control_id ); ?>">
				<span class="apeiron-toggle">
					<input id="<?php echo esc_attr( $control_id ); ?>" type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="yes" <?php checked( 'yes', $value ); ?> />
					<span class="apeiron-toggle__slider"></span>
				</span>
				<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
			</label>
		</div>
		<?php
	}

	private function render_runtime_config(): void {
		$config = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( UcapanTamuHandler::get_nonce_action() ),
			'i18n'    => [
				'saving'           => __( 'Menyimpan...', 'apeiron-kit' ),
				'saveSettings'     => __( 'Simpan', 'apeiron-kit' ),
				'saved'            => __( 'Tersimpan', 'apeiron-kit' ),
				'savingSettings'   => __( 'Menyimpan pengaturan...', 'apeiron-kit' ),
				'savedSuccess'     => __( 'Pengaturan berhasil disimpan.', 'apeiron-kit' ),
				'saveFailedStatus' => __( 'Gagal tersimpan', 'apeiron-kit' ),
				'saveFailed'       => __( 'Gagal menyimpan pengaturan.', 'apeiron-kit' ),
				'autosaveWaiting'  => __( 'Belum tersimpan', 'apeiron-kit' ),
			],
		];
		$this->render_config_payload( 'ucapan-tamu', $config );
	}
}
