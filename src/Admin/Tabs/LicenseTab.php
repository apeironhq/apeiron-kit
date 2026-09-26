<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Core\LicenseManager;

/**
 * License management tab.
 */
class LicenseTab extends AbstractTab {

	private LicenseManager $license_manager;

	public function __construct( ?LicenseManager $license_manager = null ) {
		$this->license_manager = $license_manager ?? LicenseManager::instance();
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'license';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title(): string {
		return __( 'Lisensi', 'apeiron-kit' );
	}

	/**
	 * @inheritDoc
	 */
	public function render(): void {
		$license          = $this->license_manager->get_license();
		$status_display   = $this->license_manager->get_status_display();
		$has_api_key      = $this->license_manager->has_api_key();
		$api_key_auto     = defined( 'APEIRON_KIT_LICENSE_API_KEY' ) && ! empty( APEIRON_KIT_LICENSE_API_KEY );
		$is_lifetime      = ! empty( $license['key'] ) && ! empty( $status_display['is_valid'] ) && empty( $status_display['expires'] );
		$is_active        = ! empty( $license['key'] ) && ! empty( $status_display['is_valid'] );
		$status_state     = $this->get_status_state( $license, $status_display );
		$license_key_mask = $this->get_license_key_display( $license, $is_active );
		?>
		<div class="apeiron-license-wrap">
			<section class="apeiron-elements-section apeiron-license-section">
				<?php $this->render_toolbar( $status_state, $license, $status_display ); ?>

				<div class="apeiron-elements-content apeiron-license-content">
					<div class="apeiron-license-grid">
						<div class="apeiron-license-main">
							<?php $this->render_api_key_card( $api_key_auto, $has_api_key ); ?>
							<?php $this->render_license_key_card( $license, $is_active, $license_key_mask ); ?>
						</div>

						<?php $this->render_status_card( $license, $status_display, $is_active, $is_lifetime, $license_key_mask, $status_state ); ?>

					</div>
				</div>
			</section>
		</div>

		<?php $this->render_runtime_config( $api_key_auto, $license ); ?>
		<?php
	}

	/**
	 * Get masked License Key for display.
	 */
	private function get_license_key_display( array $license, bool $is_license_active ): string {
		if ( empty( $license['key'] ) ) {
			return '';
		}

		if ( $is_license_active ) {
			$key_parts = explode( '-', $license['key'] );
			if ( count( $key_parts ) > 1 ) {
				$last_part = end( $key_parts );
				return 'XXXX-XXXX-XXXX-' . substr( $last_part, -4 );
			}

			return 'XXXX-' . substr( $license['key'], -4 );
		}

		return $license['key'];
	}

	/**
	 * Normalize the license state for shared admin status colors.
	 */
	private function get_status_state( array $license, array $status_display ): string {
		if ( ! empty( $license['key'] ) && ! empty( $status_display['is_valid'] ) ) {
			return 'active';
		}

		if ( ! empty( $license['key'] ) ) {
			return 'warning';
		}

		return 'inactive';
	}

	/**
	 * Human-readable status label.
	 */
	private function get_status_label( array $license, array $status_display ): string {
		if ( empty( $license['key'] ) ) {
			return __( 'Belum Aktif', 'apeiron-kit' );
		}

		if ( ! empty( $status_display['is_valid'] ) ) {
			return __( 'Aktif', 'apeiron-kit' );
		}

		return $status_display['info']['label'] ?? __( 'Perlu Validasi', 'apeiron-kit' );
	}

	/**
	 * Short status copy for the license status card.
	 */
	private function get_status_description( array $license, array $status_display ): string {
		if ( empty( $license['key'] ) ) {
			return __( 'Masukkan License Key untuk mengaktifkan Apeiron Kit.', 'apeiron-kit' );
		}

		if ( ! empty( $status_display['is_valid'] ) ) {
			return __( 'Lisensi siap digunakan pada domain terdaftar.', 'apeiron-kit' );
		}

		return __( 'Periksa status atau aktifkan ulang lisensi untuk memastikan akses tetap berjalan.', 'apeiron-kit' );
	}

	/**
	 * Registered domain display.
	 */
	private function get_domain_display( array $license ): string {
		if ( empty( $license['key'] ) ) {
			return __( 'Belum terdaftar', 'apeiron-kit' );
		}

		$site_url = ! empty( $license['site_url'] ) ? $license['site_url'] : home_url();
		$parts    = (array) wp_parse_url( $site_url );
		$host     = $parts['host'] ?? '';

		if ( empty( $host ) ) {
			return $site_url;
		}

		// Subfolder installs share a host, so the port and path are what tell one
		// registered installation apart from another.
		if ( ! empty( $parts['port'] ) ) {
			$host .= ':' . $parts['port'];
		}

		return $host . untrailingslashit( $parts['path'] ?? '' );
	}

	/**
	 * License expiration display.
	 */
	private function get_expiration_display( array $license, array $status_display ): string {
		if ( empty( $license['key'] ) ) {
			return __( 'Belum tersedia', 'apeiron-kit' );
		}

		if ( empty( $status_display['expires'] ) && empty( $status_display['is_valid'] ) ) {
			return __( 'Belum tersedia', 'apeiron-kit' );
		}

		if ( empty( $status_display['expires'] ) ) {
			return __( 'Lifetime', 'apeiron-kit' );
		}

		$expires = strtotime( $status_display['expires'] );

		if ( false === $expires ) {
			return __( 'Tanggal tidak valid', 'apeiron-kit' );
		}

		return date_i18n( get_option( 'date_format' ), $expires );
	}

	/**
	 * Activation usage display.
	 */
	private function get_activation_display( array $license, array $status_display ): string {
		if ( empty( $license['key'] ) ) {
			return __( 'Belum aktif', 'apeiron-kit' );
		}

		if ( (int) $status_display['limit'] > 0 ) {
			return sprintf(
				/* translators: 1: current activations, 2: activation limit */
				__( '%1$s dari %2$s', 'apeiron-kit' ),
				number_format_i18n( (int) $status_display['activations'] ),
				number_format_i18n( (int) $status_display['limit'] )
			);
		}

		return __( 'Tanpa batas', 'apeiron-kit' );
	}

	/**
	 * Last check display.
	 */
	private function get_last_check_display( array $status_display ): string {
		if ( empty( $status_display['last_check'] ) ) {
			return __( 'Belum pernah', 'apeiron-kit' );
		}

		return wp_date( 'j F Y, H.i \W\I\B', (int) $status_display['last_check'], $this->get_license_display_timezone() );
	}

	/**
	 * License timestamps use WIB by default so admin time matches the local product audience.
	 */
	private function get_license_display_timezone(): \DateTimeZone {
		$timezone = apply_filters( 'apeiron_kit_license_display_timezone', 'Asia/Jakarta' );

		if ( $timezone instanceof \DateTimeZone ) {
			return $timezone;
		}

		if ( is_string( $timezone ) && '' !== $timezone ) {
			try {
				return new \DateTimeZone( $timezone );
			} catch ( \Exception $e ) {
				return wp_timezone();
			}
		}

		return wp_timezone();
	}

	/**
	 * Plugin version display.
	 */
	private function get_plugin_version(): string {
		return defined( 'APEIRON_KIT_VERSION' ) ? APEIRON_KIT_VERSION : '1.0.0';
	}

	/**
	 * Render section toolbar.
	 */
	private function render_toolbar( string $status_state, array $license, array $status_display ): void {
		$status_icon = 'active' === $status_state ? 'dashicons-yes-alt' : ( 'warning' === $status_state ? 'dashicons-warning' : 'dashicons-lock' );
		?>
		<div class="apeiron-elements-toolbar apeiron-license-toolbar">
			<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Lisensi', 'apeiron-kit' ); ?></h2>

			<div class="apeiron-license-toolbar__stats" aria-label="<?php esc_attr_e( 'Ringkasan lisensi', 'apeiron-kit' ); ?>">
				<span class="apeiron-license-toolbar__stat is-<?php echo esc_attr( $status_state ); ?>">
					<span class="dashicons <?php echo esc_attr( $status_icon ); ?>"></span>
					<?php echo esc_html( $this->get_status_label( $license, $status_display ) ); ?>
				</span>
				<span class="apeiron-license-toolbar__stat">
					<strong><?php echo esc_html( $this->get_plugin_version() ); ?></strong>
					<?php esc_html_e( 'Versi', 'apeiron-kit' ); ?>
				</span>
			</div>

			<span class="apeiron-elements-toolbar__spacer"></span>
		</div>
		<?php
	}

	/**
	 * Render license status card.
	 */
	private function render_status_card( array $license, array $status_display, bool $is_license_active, bool $is_lifetime, string $license_key_display, string $status_state ): void {
		$status_label   = $this->get_status_label( $license, $status_display );
		$status_icon    = $is_license_active ? 'dashicons-yes-alt' : ( ! empty( $license['key'] ) ? 'dashicons-warning' : 'dashicons-lock' );
		$summary_text   = ! empty( $license_key_display ) ? $license_key_display : __( 'License key belum terhubung', 'apeiron-kit' );
		$expiration     = $this->get_expiration_display( $license, $status_display );
		$activation_use = $this->get_activation_display( $license, $status_display );
		?>
		<div class="apeiron-card apeiron-license-status-card is-<?php echo esc_attr( $status_state ); ?>">
			<div class="apeiron-card__header">
				<div class="apeiron-license-card-heading">
					<div class="apeiron-card__icon apeiron-card__icon--primary">
						<span class="dashicons dashicons-awards"></span>
					</div>
					<div>
						<h3 class="apeiron-card__title"><?php esc_html_e( 'Ringkasan Lisensi', 'apeiron-kit' ); ?></h3>
						<p class="apeiron-card__subtitle"><?php echo esc_html( $this->get_status_description( $license, $status_display ) ); ?></p>
					</div>
				</div>
			</div>

			<?php
			/*
			 * The summary block below states the same status with the same icon at a
			 * larger size, so a chip in the card header only repeated it.
			 */
			?>
			<div class="apeiron-license-summary">
				<div class="apeiron-license-summary__main">
					<div class="apeiron-license-summary__icon is-<?php echo esc_attr( $status_state ); ?>">
						<span class="dashicons <?php echo esc_attr( $status_icon ); ?>"></span>
					</div>
					<div class="apeiron-license-summary__text">
						<h3><?php echo esc_html( $status_label ); ?></h3>
						<p><?php echo esc_html( $summary_text ); ?></p>
					</div>
				</div>

				<?php if ( ! empty( $license['key'] ) ) : ?>
					<div class="apeiron-license-summary__badges">
						<span class="apeiron-license-status-chip is-neutral">
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<?php echo esc_html( $this->get_domain_display( $license ) ); ?>
						</span>
						<span class="apeiron-license-status-chip <?php echo $is_lifetime ? 'is-active' : 'is-neutral'; ?>">
							<span class="dashicons dashicons-calendar-alt"></span>
							<?php echo esc_html( $expiration ); ?>
						</span>
					</div>
				<?php endif; ?>
			</div>

			<dl class="apeiron-license-detail-grid">
				<div class="apeiron-license-detail">
					<dt><?php esc_html_e( 'Status Aktivasi', 'apeiron-kit' ); ?></dt>
					<dd><span class="apeiron-license-value is-<?php echo esc_attr( $status_state ); ?>"><?php echo esc_html( $status_label ); ?></span></dd>
				</div>
				<div class="apeiron-license-detail">
					<dt><?php esc_html_e( 'Domain Terdaftar', 'apeiron-kit' ); ?></dt>
					<dd><?php echo esc_html( $this->get_domain_display( $license ) ); ?></dd>
				</div>
				<div class="apeiron-license-detail">
					<dt><?php esc_html_e( 'Masa Berlaku', 'apeiron-kit' ); ?></dt>
					<dd><?php echo esc_html( $expiration ); ?></dd>
				</div>
				<div class="apeiron-license-detail">
					<dt><?php esc_html_e( 'Pemakaian Aktivasi', 'apeiron-kit' ); ?></dt>
					<dd><?php echo esc_html( $activation_use ); ?></dd>
				</div>
				<div class="apeiron-license-detail">
					<dt><?php esc_html_e( 'Versi Plugin', 'apeiron-kit' ); ?></dt>
					<dd><?php echo esc_html( $this->get_plugin_version() ); ?></dd>
				</div>
				<div class="apeiron-license-detail">
					<dt><?php esc_html_e( 'Terakhir diperiksa', 'apeiron-kit' ); ?></dt>
					<dd><?php echo esc_html( $this->get_last_check_display( $status_display ) ); ?></dd>
				</div>
			</dl>
		</div>
		<?php
	}

	/**
	 * Render License Key management card.
	 */
	private function render_license_key_card( array $license, bool $is_license_active, string $license_key_display ): void {
		?>
		<div class="apeiron-card apeiron-license-manage-card">
			<div class="apeiron-card__header">
				<div class="apeiron-license-card-heading">
					<div class="apeiron-card__icon apeiron-card__icon--green">
						<span class="dashicons dashicons-admin-network"></span>
					</div>
					<div>
						<h3 class="apeiron-card__title"><?php esc_html_e( 'License Key', 'apeiron-kit' ); ?></h3>
						<p class="apeiron-card__subtitle"><?php esc_html_e( 'Kelola aktivasi lisensi plugin.', 'apeiron-kit' ); ?></p>
					</div>
				</div>
			</div>

			<div class="apeiron-license-card-body">
				<?php if ( $is_license_active ) : ?>
					<label class="apeiron-label" for="license-edit-key-btn"><?php esc_html_e( 'License Key', 'apeiron-kit' ); ?></label>
					<div class="apeiron-license-key-row">
						<div class="apeiron-key-display">
							<span class="dashicons dashicons-lock"></span>
							<span><?php echo esc_html( $license_key_display ); ?></span>
						</div>
						<button type="button" class="apeiron-icon-button" id="license-edit-key-btn" aria-pressed="false" aria-label="<?php esc_attr_e( 'Ubah License Key', 'apeiron-kit' ); ?>" title="<?php esc_attr_e( 'Ubah License Key', 'apeiron-kit' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</button>
					</div>
					<p class="apeiron-license-note"><?php esc_html_e( 'Klik ikon edit untuk mengganti License Key.', 'apeiron-kit' ); ?></p>
				<?php else : ?>
					<div class="apeiron-form-group">
						<label class="apeiron-label" for="license-key-input"><?php esc_html_e( 'License Key', 'apeiron-kit' ); ?></label>
						<input type="text" id="license-key-input" class="apeiron-input apeiron-input--mono" value="<?php echo esc_attr( $license['key'] ?? '' ); ?>" placeholder="APEIRON-XXXX-XXXX-XXXX-XXXX">
						<p class="apeiron-license-note"><?php esc_html_e( 'Masukkan License Key valid.', 'apeiron-kit' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<div class="apeiron-actions apeiron-license-actions <?php echo empty( $license['key'] ) ? 'is-single' : 'is-paired'; ?>">
				<?php if ( empty( $license['key'] ) ) : ?>
					<button type="button" class="apeiron-btn apeiron-btn--primary" id="license-activate-btn">
						<span class="dashicons dashicons-yes"></span>
						<?php esc_html_e( 'Aktifkan', 'apeiron-kit' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="apeiron-btn apeiron-btn--primary" id="license-check-btn">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Periksa', 'apeiron-kit' ); ?>
					</button>
					<button type="button" class="apeiron-btn apeiron-btn--danger" id="license-deactivate-btn">
						<span class="dashicons dashicons-no"></span>
						<?php esc_html_e( 'Nonaktifkan', 'apeiron-kit' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<div id="license-message" class="apeiron-toast" role="status" aria-live="polite"></div>

			<?php if ( $is_license_active ) : ?>
				<div id="apeiron-license-key-modal" class="apeiron-modal apeiron-license-modal">
					<div class="apeiron-modal__backdrop"></div>
					<div class="apeiron-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="apeiron-license-key-modal-title">
						<div class="apeiron-modal__header">
							<span class="apeiron-modal__icon dashicons dashicons-edit"></span>
							<span class="apeiron-modal__title" id="apeiron-license-key-modal-title"><?php esc_html_e( 'Ubah License Key', 'apeiron-kit' ); ?></span>
						</div>
						<p class="apeiron-modal__desc"><?php esc_html_e( 'Masukkan License Key baru untuk mengganti aktivasi saat ini.', 'apeiron-kit' ); ?></p>
						<div class="apeiron-form-group">
							<label class="apeiron-label" for="license-key-input"><?php esc_html_e( 'License Key Baru', 'apeiron-kit' ); ?></label>
							<input type="text" id="license-key-input" class="apeiron-input apeiron-input--mono" value="" placeholder="<?php esc_attr_e( 'APEIRON-XXXX-XXXX-XXXX-XXXX', 'apeiron-kit' ); ?>">
						</div>
						<div class="apeiron-modal__actions">
							<button type="button" class="apeiron-btn apeiron-btn--outline" id="license-key-modal-cancel">
								<?php esc_html_e( 'Batal', 'apeiron-kit' ); ?>
							</button>
							<button type="button" class="apeiron-btn apeiron-btn--primary" id="license-activate-btn">
								<?php echo $this->get_save_icon_markup(); ?>
								<?php esc_html_e( 'Simpan', 'apeiron-kit' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $license['key'] ) ) : ?>
				<div id="apeiron-license-deactivate-modal" class="apeiron-modal apeiron-license-modal apeiron-license-modal--danger">
					<div class="apeiron-modal__backdrop"></div>
					<div class="apeiron-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="apeiron-license-deactivate-modal-title" aria-describedby="apeiron-license-deactivate-modal-desc">
						<div class="apeiron-modal__header">
							<span class="apeiron-modal__icon dashicons dashicons-warning"></span>
							<span class="apeiron-modal__title" id="apeiron-license-deactivate-modal-title"><?php esc_html_e( 'Nonaktifkan Lisensi', 'apeiron-kit' ); ?></span>
						</div>
						<p class="apeiron-modal__desc" id="apeiron-license-deactivate-modal-desc"><?php esc_html_e( 'Lisensi akan dinonaktifkan dari domain ini. Anda bisa mengaktifkannya kembali dengan License Key yang valid.', 'apeiron-kit' ); ?></p>
						<div class="apeiron-modal__actions">
							<button type="button" class="apeiron-btn apeiron-btn--outline" id="license-deactivate-modal-cancel">
								<?php esc_html_e( 'Batal', 'apeiron-kit' ); ?>
							</button>
							<button type="button" class="apeiron-btn apeiron-btn--danger" id="license-deactivate-confirm-btn">
								<span class="dashicons dashicons-no"></span>
								<?php esc_html_e( 'Nonaktifkan', 'apeiron-kit' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render API Key configuration card.
	 */
	private function render_api_key_card( bool $api_key_auto, bool $has_api_key ): void {
		$api_key_ready = $api_key_auto || $has_api_key;
		?>
		<div class="apeiron-card apeiron-license-server-card">
			<div class="apeiron-card__header">
				<div class="apeiron-license-card-heading">
					<div class="apeiron-card__icon apeiron-card__icon--blue">
						<span class="dashicons dashicons-admin-links"></span>
					</div>
					<div>
						<h3 class="apeiron-card__title"><?php esc_html_e( 'Koneksi Server', 'apeiron-kit' ); ?></h3>
						<p class="apeiron-card__subtitle"><?php esc_html_e( 'Validasi API Key.', 'apeiron-kit' ); ?></p>
					</div>
				</div>
				<span class="apeiron-license-status-chip <?php echo $api_key_ready ? 'is-active' : 'is-warning'; ?>">
					<span class="dashicons <?php echo $api_key_ready ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
					<?php echo $api_key_ready ? esc_html__( 'Tersimpan', 'apeiron-kit' ) : esc_html__( 'Perlu Key', 'apeiron-kit' ); ?>
				</span>
			</div>

			<div class="apeiron-license-card-body">
				<?php if ( $api_key_ready ) : ?>
					<label class="apeiron-label" for="api-key-edit-btn"><?php esc_html_e( 'API Key', 'apeiron-kit' ); ?><span>*</span></label>
					<div class="apeiron-license-key-row<?php echo $api_key_auto ? ' is-single' : ''; ?>">
						<div class="apeiron-key-display">
							<span class="dashicons dashicons-lock"></span>
							<span><?php esc_html_e( 'API Key tersimpan', 'apeiron-kit' ); ?></span>
						</div>
						<?php if ( ! $api_key_auto ) : ?>
							<button type="button" class="apeiron-icon-button" id="api-key-edit-btn" aria-pressed="false" aria-label="<?php esc_attr_e( 'Ubah API Key', 'apeiron-kit' ); ?>" title="<?php esc_attr_e( 'Ubah API Key', 'apeiron-kit' ); ?>">
								<span class="dashicons dashicons-edit"></span>
							</button>
						<?php endif; ?>
					</div>
					<p class="apeiron-license-note">
						<?php
						echo $api_key_auto
							? esc_html__( 'API Key dikonfigurasi otomatis.', 'apeiron-kit' )
							: esc_html__( 'Klik ikon edit untuk mengganti API Key.', 'apeiron-kit' );
						?>
					</p>
				<?php else : ?>
					<div class="apeiron-form-group">
						<label class="apeiron-label" for="api-key-input"><?php esc_html_e( 'API Key', 'apeiron-kit' ); ?><span>*</span></label>
						<input type="password" id="api-key-input" class="apeiron-input apeiron-input--mono" value="" placeholder="<?php esc_attr_e( 'Masukkan API Key', 'apeiron-kit' ); ?>">
						<p class="apeiron-license-note"><?php esc_html_e( 'Masukkan API Key valid.', 'apeiron-kit' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<div class="apeiron-actions apeiron-license-actions <?php echo ( ! $api_key_auto && ! $has_api_key ) ? 'is-paired' : 'is-single'; ?>">
				<?php if ( ! $api_key_auto && ! $has_api_key ) : ?>
					<button type="button" class="apeiron-btn apeiron-btn--primary" id="save-api-key-btn">
						<?php echo $this->get_save_icon_markup(); ?>
						<?php esc_html_e( 'Simpan', 'apeiron-kit' ); ?>
					</button>
				<?php endif; ?>
				<button type="button" class="apeiron-btn apeiron-btn--primary" id="test-server-connection-btn">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Test', 'apeiron-kit' ); ?>
				</button>
			</div>

			<div id="server-config-message" class="apeiron-toast" role="status" aria-live="polite"></div>

			<?php if ( ! $api_key_auto && $has_api_key ) : ?>
				<div id="apeiron-api-key-modal" class="apeiron-modal apeiron-license-modal">
					<div class="apeiron-modal__backdrop"></div>
					<div class="apeiron-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="apeiron-api-key-modal-title">
						<div class="apeiron-modal__header">
							<span class="apeiron-modal__icon dashicons dashicons-edit"></span>
							<span class="apeiron-modal__title" id="apeiron-api-key-modal-title"><?php esc_html_e( 'Ubah API Key', 'apeiron-kit' ); ?></span>
						</div>
						<p class="apeiron-modal__desc"><?php esc_html_e( 'Masukkan API Key baru. Nilai lama tetap tersembunyi.', 'apeiron-kit' ); ?></p>
						<div class="apeiron-form-group">
							<label class="apeiron-label" for="api-key-input"><?php esc_html_e( 'API Key Baru', 'apeiron-kit' ); ?></label>
							<input type="password" id="api-key-input" class="apeiron-input apeiron-input--mono" value="" placeholder="<?php esc_attr_e( 'Masukkan API Key baru', 'apeiron-kit' ); ?>">
						</div>
						<div class="apeiron-modal__actions">
							<button type="button" class="apeiron-btn apeiron-btn--outline" id="api-key-modal-cancel">
								<?php esc_html_e( 'Batal', 'apeiron-kit' ); ?>
							</button>
							<button type="button" class="apeiron-btn apeiron-btn--primary" id="save-api-key-btn">
								<?php echo $this->get_save_icon_markup(); ?>
								<?php esc_html_e( 'Simpan', 'apeiron-kit' ); ?>
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Expose dynamic data to the external license runtime.
	 */
	private function render_runtime_config( bool $api_key_auto, array $license ): void {
		$activate_text = empty( $license['key'] ) ? __( 'Aktifkan', 'apeiron-kit' ) : __( 'Simpan', 'apeiron-kit' );

		$this->render_config_payload(
			'license',
			[
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'apeiron_license_management' ),
				'apiKeyRequired'    => ! $api_key_auto,
				'currentLicenseKey' => (string) ( $license['key'] ?? '' ),
				'activateIcon'      => empty( $license['key'] ) ? 'dashicons-yes' : 'apeiron-save-icon',
				'i18n'              => [
					'saveApi'           => __( 'Simpan', 'apeiron-kit' ),
					'testConnection'    => __( 'Test', 'apeiron-kit' ),
					'activate'          => $activate_text,
					'checkStatus'       => __( 'Periksa', 'apeiron-kit' ),
					'deactivate'        => __( 'Nonaktifkan', 'apeiron-kit' ),
					'requestTimeout'    => __( 'Permintaan lisensi melewati batas waktu. Silakan coba lagi.', 'apeiron-kit' ),
					'enterApiKey'       => __( 'Masukkan API Key baru.', 'apeiron-kit' ),
					'saving'            => __( 'Menyimpan...', 'apeiron-kit' ),
					'genericError'      => __( 'Terjadi kesalahan.', 'apeiron-kit' ),
					'saved'             => __( 'Berhasil disimpan!', 'apeiron-kit' ),
					'saveFailed'        => __( 'Gagal menyimpan.', 'apeiron-kit' ),
					'enterLicense'      => __( 'Masukkan License Key.', 'apeiron-kit' ),
					'activating'        => __( 'Mengaktifkan...', 'apeiron-kit' ),
					'licenseActive'     => __( 'Lisensi aktif!', 'apeiron-kit' ),
					'activateFailed'    => __( 'Gagal mengaktifkan.', 'apeiron-kit' ),
					'checking'          => __( 'Memeriksa...', 'apeiron-kit' ),
					'statusUpdated'     => __( 'Status diperbarui!', 'apeiron-kit' ),
					'checkFailed'       => __( 'Gagal memeriksa.', 'apeiron-kit' ),
					'deactivating'      => __( 'Menonaktifkan...', 'apeiron-kit' ),
					'deactivated'       => __( 'Lisensi dinonaktifkan.', 'apeiron-kit' ),
					'deactivateFailed'  => __( 'Gagal menonaktifkan.', 'apeiron-kit' ),
					'testing'           => __( 'Menguji...', 'apeiron-kit' ),
					'connectionError'   => __( 'Terjadi kesalahan saat menguji koneksi.', 'apeiron-kit' ),
					'connectionSuccess' => __( 'Koneksi berhasil!', 'apeiron-kit' ),
					'connectionFailed'  => __( 'Koneksi gagal.', 'apeiron-kit' ),
				],
			]
		);
	}
}
