<?php

namespace ApeironKit\Admin\Ajax;

use ApeironKit\Support\SocialProofSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SocialProofHandler {
	private bool $registered = false;

	private const NONCE_ACTION = 'apeiron_social_proof';

	private const BACKUP_SCHEMA_VERSION = 1;

	private const MAX_BACKUP_SIZE = 5242880;

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		add_action( 'wp_ajax_apeiron_save_social_proof', [ $this, 'save_settings' ] );
		add_action( 'wp_ajax_apeiron_delete_social_proof_entry', [ $this, 'delete_entry' ] );
		add_action( 'wp_ajax_apeiron_upload_client_photo', [ $this, 'upload_photo' ] );
		add_action( 'wp_ajax_apeiron_export_social_proof_backup', [ $this, 'export_backup' ] );
		add_action( 'wp_ajax_apeiron_validate_social_proof_backup', [ $this, 'validate_backup' ] );
		add_action( 'wp_ajax_apeiron_import_social_proof_backup', [ $this, 'import_backup' ] );
	}

	public function save_settings(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		$settings = SocialProofSettings::get();

		parse_str( wp_unslash( $_POST['form_data'] ?? '' ), $form_data );

		if ( ! empty( $form_data['add_entry'] ) ) {
			$entry_name = sanitize_text_field( $form_data['entry_name'] ?? '' );
			$entry_product = sanitize_text_field( $form_data['entry_product'] ?? '' );
			$entry_image = esc_url_raw( $form_data['entry_image'] ?? '' );
			$entry_datetime = sanitize_text_field( $form_data['entry_datetime'] ?? '' );

			if ( empty( $entry_name ) || empty( $entry_product ) || empty( $entry_datetime ) ) {
				wp_send_json_error( [ 'message' => __( 'Nama, produk, dan datetime wajib diisi.', 'apeiron-kit' ) ] );
			}
			if ( false === strtotime( $entry_datetime ) ) {
				wp_send_json_error( [ 'message' => __( 'Tanggal dan waktu tidak valid.', 'apeiron-kit' ) ] );
			}

			$entries = is_array( $settings['entries'] ?? null ) ? $settings['entries'] : [];
			$entries[] = [
				'name'     => $entry_name,
				'product'  => $entry_product,
				'image'    => $entry_image,
				'datetime' => $entry_datetime,
			];

			$settings['entries'] = $entries;
		} else {
			$animation_type = sanitize_text_field( $form_data['animation_type'] ?? $settings['animation_type'] );
			$popup_position = sanitize_text_field( $form_data['popup_position'] ?? $settings['popup_position'] );

			$settings['display_duration']    = max( 1, min( 10, absint( $form_data['display_duration'] ?? $settings['display_duration'] ) ) );
			$settings['interval_duration']   = max( 1, min( 30, absint( $form_data['interval_duration'] ?? $settings['interval_duration'] ) ) );
			$settings['initial_delay']       = max( 0, min( 30, absint( $form_data['initial_delay'] ?? $settings['initial_delay'] ?? 0 ) ) );
			$settings['max_notifications']   = max( 0, min( 20, absint( $form_data['max_notifications'] ?? $settings['max_notifications'] ?? 0 ) ) );
			$settings['animation_type']      = in_array( $animation_type, [ 'fade', 'slide-top', 'slide-bottom', 'slide-left', 'slide-right' ], true ) ? $animation_type : SocialProofSettings::defaults()['animation_type'];
			$settings['popup_position']      = in_array( $popup_position, [ 'top-right', 'top-left', 'bottom-right', 'bottom-left' ], true ) ? $popup_position : SocialProofSettings::defaults()['popup_position'];
			$settings['text_template']       = sanitize_text_field( $form_data['text_template'] ?? $settings['text_template'] );
			$settings['image_border_radius'] = max( 0, min( 50, absint( $form_data['image_border_radius'] ?? $settings['image_border_radius'] ?? 10 ) ) );
		}

		if ( ! $this->persist_settings( $settings ) ) {
			wp_send_json_error( [ 'message' => __( 'Pengaturan gagal disimpan.', 'apeiron-kit' ) ] );
		}
		wp_send_json_success( [ 'message' => __( 'Berhasil disimpan!', 'apeiron-kit' ) ] );
	}

	public function delete_entry(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		$settings = SocialProofSettings::get();
		$entries  = is_array( $settings['entries'] ?? null ) ? $settings['entries'] : [];

		$raw_index = isset( $_POST['entry_index'] ) ? wp_unslash( $_POST['entry_index'] ) : null;
		if ( ! is_scalar( $raw_index ) ) {
			wp_send_json_error( [ 'message' => __( 'Entry tidak ditemukan.', 'apeiron-kit' ) ] );
		}
		$index = filter_var(
			(string) $raw_index,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 0,
				],
			]
		);

		if ( false === $index || $index >= count( $entries ) ) {
			wp_send_json_error( [ 'message' => __( 'Entry tidak ditemukan.', 'apeiron-kit' ) ] );
		}

		unset( $entries[ $index ] );
		$entries = array_values( $entries ); // Re-index array

		$settings['entries'] = $entries;
		if ( ! $this->persist_settings( $settings ) ) {
			wp_send_json_error( [ 'message' => __( 'Aktivitas gagal dihapus.', 'apeiron-kit' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Entry berhasil dihapus.', 'apeiron-kit' ) ] );
	}

	public function upload_photo(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		$file = $_FILES['client_photo'] ?? null;
		if ( ! is_array( $file ) || ! isset( $file['error'], $file['size'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
			wp_send_json_error( [ 'message' => __( 'Tidak ada file yang diupload atau terjadi error.', 'apeiron-kit' ) ] );
		}

		if ( $file['size'] > 5 * 1024 * 1024 ) {
			wp_send_json_error( [ 'message' => __( 'Ukuran file terlalu besar. Maksimal 5MB.', 'apeiron-kit' ) ] );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$uploaded = wp_handle_upload(
			$file,
			[
				'test_form' => false,
				'mimes'     => [
					'png'      => 'image/png',
					'jpg|jpeg' => 'image/jpeg',
					'gif'      => 'image/gif',
					'webp'     => 'image/webp',
				],
			]
		);

		if ( isset( $uploaded['error'] ) ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						__( 'Gagal menyimpan file: %s', 'apeiron-kit' ),
						sanitize_text_field( $uploaded['error'] )
					),
				]
			);
		}

		wp_send_json_success( [
			'message' => __( 'Foto berhasil diupload!', 'apeiron-kit' ),
			'url'     => esc_url_raw( $uploaded['url'] ),
		] );
	}

	public function export_backup(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		$settings = SocialProofSettings::get();
		$entries  = is_array( $settings['entries'] ?? null ) ? array_values( $settings['entries'] ) : [];

		unset( $settings['entries'] );

		$upload_dir      = wp_upload_dir();
		$upload_base_url = isset( $upload_dir['baseurl'] ) ? untrailingslashit( $upload_dir['baseurl'] ) : '';
		$export_entries  = array_map(
			function ( $entry ) use ( $upload_base_url ) {
				return $this->prepare_entry_for_export( is_array( $entry ) ? $entry : [], $upload_base_url );
			},
			$entries
		);

		$backup = [
			'apeiron_backup'  => 'social_proof',
			'schema_version'  => self::BACKUP_SCHEMA_VERSION,
			'plugin_version'  => defined( 'APEIRON_KIT_VERSION' ) ? APEIRON_KIT_VERSION : '1.0.0',
			'exported_at'     => current_time( 'mysql' ),
			'exported_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			'site_url'        => site_url(),
			'home_url'        => home_url(),
			'upload_base_url' => $upload_base_url,
			'data'            => [
				'settings' => $settings,
				'entries'  => $export_entries,
			],
		];

		$content = wp_json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $content ) {
			wp_send_json_error( [ 'message' => __( 'Gagal membuat file backup.', 'apeiron-kit' ) ] );
		}

		wp_send_json_success(
			[
				'filename'      => sanitize_file_name( 'apeiron-social-proof-backup-' . current_time( 'Y-m-d-His' ) . '.json' ),
				'content'       => $content,
				'entries_count' => count( $export_entries ),
			]
		);
	}

	public function validate_backup(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		$payload = $this->decode_backup_json_from_request();

		if ( is_wp_error( $payload ) ) {
			wp_send_json_error( [ 'message' => $payload->get_error_message() ] );
		}

		$parsed = $this->parse_backup_payload( $payload );

		if ( is_wp_error( $parsed ) ) {
			wp_send_json_error( [ 'message' => $parsed->get_error_message() ] );
		}

		$current_settings = SocialProofSettings::get();
		$current_entries  = is_array( $current_settings['entries'] ?? null ) ? $current_settings['entries'] : [];

		wp_send_json_success(
			[
				'message'               => __( 'File backup valid.', 'apeiron-kit' ),
				'entries_count'         => count( $parsed['settings']['entries'] ?? [] ),
				'current_entries_count' => count( $current_entries ),
				'settings_count'        => count( SocialProofSettings::defaults() ) - 1,
				'schema_version'        => $parsed['metadata']['schema_version'],
				'plugin_version'        => $parsed['metadata']['plugin_version'],
				'source_site'           => $parsed['metadata']['site_url'],
				'exported_at'           => $parsed['metadata']['exported_at'],
				'warnings'              => $parsed['warnings'],
			]
		);
	}

	public function import_backup(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		$confirmed = isset( $_POST['overwrite_confirmed'] ) && '1' === (string) wp_unslash( $_POST['overwrite_confirmed'] );

		if ( ! $confirmed ) {
			wp_send_json_error( [ 'message' => __( 'Konfirmasi overwrite diperlukan sebelum import.', 'apeiron-kit' ) ] );
		}

		$payload = $this->decode_backup_json_from_request();

		if ( is_wp_error( $payload ) ) {
			wp_send_json_error( [ 'message' => $payload->get_error_message() ] );
		}

		$parsed = $this->parse_backup_payload( $payload );

		if ( is_wp_error( $parsed ) ) {
			wp_send_json_error( [ 'message' => $parsed->get_error_message() ] );
		}

		if ( ! $this->persist_settings( $parsed['settings'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Data backup gagal disimpan.', 'apeiron-kit' ) ] );
		}

		$entries_count = count( $parsed['settings']['entries'] ?? [] );

		wp_send_json_success(
			[
				'message'       => sprintf(
					/* translators: %s: restored activity count. */
					_n( '%s aktivitas berhasil dipulihkan.', '%s aktivitas berhasil dipulihkan.', $entries_count, 'apeiron-kit' ),
					number_format_i18n( $entries_count )
				),
				'entries_count' => $entries_count,
			]
		);
	}

	private function prepare_entry_for_export( array $entry, string $upload_base_url ): array {
		$image = esc_url_raw( $entry['image'] ?? '' );

		$backup_entry = [
			'name'     => sanitize_text_field( $entry['name'] ?? '' ),
			'product'  => sanitize_text_field( $entry['product'] ?? '' ),
			'datetime' => sanitize_text_field( $entry['datetime'] ?? '' ),
			'image'    => $image,
		];

		$relative_path = $this->get_image_relative_path( $image, $upload_base_url );

		if ( '' !== $relative_path ) {
			$backup_entry['image_relative_path'] = $relative_path;
		}

		return $backup_entry;
	}

	/** @return array|\WP_Error */
	private function decode_backup_json_from_request() {
		$raw = isset( $_POST['backup_json'] ) ? wp_unslash( $_POST['backup_json'] ) : '';

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return new \WP_Error( 'empty_backup', __( 'File backup kosong atau tidak terbaca.', 'apeiron-kit' ) );
		}

		if ( strlen( $raw ) > self::MAX_BACKUP_SIZE ) {
			return new \WP_Error( 'backup_too_large', __( 'Ukuran file backup terlalu besar. Maksimal 5MB.', 'apeiron-kit' ) );
		}

		$payload = json_decode( $raw, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			return new \WP_Error( 'invalid_json', __( 'Format JSON backup tidak valid.', 'apeiron-kit' ) );
		}

		return $payload;
	}

	/** @return array|\WP_Error */
	private function parse_backup_payload( array $payload ) {
		if ( isset( $payload['apeiron_backup'] ) && 'social_proof' !== $payload['apeiron_backup'] ) {
			return new \WP_Error( 'invalid_backup_type', __( 'File ini bukan backup Aktivitas Apeiron Kit.', 'apeiron-kit' ) );
		}

		$schema_version = isset( $payload['schema_version'] ) ? absint( $payload['schema_version'] ) : 0;
		$warnings       = [];

		if ( $schema_version > self::BACKUP_SCHEMA_VERSION ) {
			$warnings[] = __( 'Backup dibuat dengan versi skema yang lebih baru. Apeiron Kit akan memulihkan field yang kompatibel.', 'apeiron-kit' );
		}

		$settings_source = $this->extract_settings_from_payload( $payload );

		if ( is_wp_error( $settings_source ) ) {
			return $settings_source;
		}

		$settings = $this->sanitize_import_settings( $settings_source );

		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		return [
			'settings' => $settings,
			'metadata' => [
				'schema_version' => $schema_version,
				'plugin_version' => sanitize_text_field( $payload['plugin_version'] ?? '' ),
				'site_url'       => esc_url_raw( $payload['site_url'] ?? '' ),
				'exported_at'    => sanitize_text_field( $payload['exported_at'] ?? '' ),
			],
			'warnings' => $warnings,
		];
	}

	/** @return array|\WP_Error */
	private function extract_settings_from_payload( array $payload ) {
		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			$data         = $payload['data'];
			$has_settings = array_key_exists( 'settings', $data );
			$has_entries  = array_key_exists( 'entries', $data );

			if ( $has_settings && ! is_array( $data['settings'] ) ) {
				return new \WP_Error( 'invalid_settings', __( 'Data pengaturan pada backup tidak valid.', 'apeiron-kit' ) );
			}

			if ( ! $has_settings && ! $has_entries ) {
				return new \WP_Error( 'missing_data', __( 'File backup tidak memiliki data Aktivitas yang bisa dipulihkan.', 'apeiron-kit' ) );
			}

			$settings = $has_settings ? $data['settings'] : [];

			if ( $has_entries ) {
				$settings['entries'] = $data['entries'];
			}

			return $settings;
		}

		if ( isset( $payload['settings'] ) && ! is_array( $payload['settings'] ) ) {
			return new \WP_Error( 'invalid_settings', __( 'Data pengaturan pada backup tidak valid.', 'apeiron-kit' ) );
		}

		if ( isset( $payload['settings'] ) ) {
			return $payload['settings'];
		}

		foreach ( array_keys( SocialProofSettings::defaults() ) as $key ) {
			if ( array_key_exists( $key, $payload ) ) {
				return $payload;
			}
		}

		return new \WP_Error( 'missing_data', __( 'File backup tidak memiliki data Aktivitas yang bisa dipulihkan.', 'apeiron-kit' ) );
	}

	/** @return array|\WP_Error */
	private function sanitize_import_settings( array $input ) {
		$settings       = SocialProofSettings::defaults();
		$animation_type = sanitize_text_field( $input['animation_type'] ?? $settings['animation_type'] );
		$popup_position = sanitize_text_field( $input['popup_position'] ?? $settings['popup_position'] );

		$settings['display_duration']    = max( 1, min( 10, absint( $input['display_duration'] ?? $settings['display_duration'] ) ) );
		$settings['interval_duration']   = max( 1, min( 30, absint( $input['interval_duration'] ?? $settings['interval_duration'] ) ) );
		$settings['initial_delay']       = max( 0, min( 30, absint( $input['initial_delay'] ?? $settings['initial_delay'] ) ) );
		$settings['max_notifications']   = max( 0, min( 20, absint( $input['max_notifications'] ?? $settings['max_notifications'] ) ) );
			$settings['animation_type']      = in_array( $animation_type, [ 'fade', 'slide-top', 'slide-bottom', 'slide-left', 'slide-right' ], true ) ? $animation_type : SocialProofSettings::defaults()['animation_type'];
			$settings['popup_position']      = in_array( $popup_position, [ 'top-right', 'top-left', 'bottom-right', 'bottom-left' ], true ) ? $popup_position : SocialProofSettings::defaults()['popup_position'];
		$settings['text_template']       = sanitize_text_field( $input['text_template'] ?? $settings['text_template'] );
		$settings['image_border_radius'] = max( 0, min( 50, absint( $input['image_border_radius'] ?? $settings['image_border_radius'] ) ) );

		$entries = $input['entries'] ?? [];

		if ( ! is_array( $entries ) ) {
			return new \WP_Error( 'invalid_entries', __( 'Data aktivitas pada backup tidak valid.', 'apeiron-kit' ) );
		}

		$settings['entries'] = [];

		foreach ( array_values( $entries ) as $index => $entry ) {
			if ( ! is_array( $entry ) ) {
				return new \WP_Error(
					'invalid_entry',
					sprintf(
						/* translators: %d: activity number. */
						__( 'Aktivitas #%d pada backup tidak valid.', 'apeiron-kit' ),
						$index + 1
					)
				);
			}

			$sanitized_entry = $this->sanitize_import_entry( $entry, $index );

			if ( is_wp_error( $sanitized_entry ) ) {
				return $sanitized_entry;
			}

			$settings['entries'][] = $sanitized_entry;
		}

		return $settings;
	}

	/** @return array|\WP_Error */
	private function sanitize_import_entry( array $entry, int $index ) {
		$name     = sanitize_text_field( $entry['name'] ?? '' );
		$product  = sanitize_text_field( $entry['product'] ?? '' );
		$datetime = sanitize_text_field( $entry['datetime'] ?? '' );
		$image    = $this->sanitize_import_image_url( $entry );

		if ( '' === $name || '' === $product || '' === $datetime ) {
			return new \WP_Error(
				'incomplete_entry',
				sprintf(
					/* translators: %d: activity number. */
					__( 'Aktivitas #%d wajib memiliki nama, produk, dan tanggal/waktu.', 'apeiron-kit' ),
					$index + 1
				)
			);
		}

		if ( false === strtotime( $datetime ) ) {
			return new \WP_Error(
				'invalid_datetime',
				sprintf(
					/* translators: %d: activity number. */
					__( 'Tanggal/waktu aktivitas #%d tidak valid.', 'apeiron-kit' ),
					$index + 1
				)
			);
		}

		return [
			'name'     => $name,
			'product'  => $product,
			'image'    => $image,
			'datetime' => $datetime,
		];
	}

	private function sanitize_import_image_url( array $entry ): string {
		$image = isset( $entry['image'] ) ? esc_url_raw( $entry['image'] ) : '';

		if ( '' === $image && ! empty( $entry['image_relative_path'] ) ) {
			$upload_dir = wp_upload_dir();
			$base_url   = isset( $upload_dir['baseurl'] ) ? untrailingslashit( $upload_dir['baseurl'] ) : '';

			if ( '' !== $base_url ) {
				$image = $base_url . '/' . ltrim( sanitize_text_field( $entry['image_relative_path'] ), '/' );
				$image = esc_url_raw( $image );
			}
		}

		return $image;
	}

	private function persist_settings( array $settings ): bool {
		return update_option( SocialProofSettings::OPTION_NAME, $settings )
			|| get_option( SocialProofSettings::OPTION_NAME ) === $settings;
	}

	private function get_image_relative_path( string $image, string $upload_base_url ): string {
		if ( '' === $image || '' === $upload_base_url ) {
			return '';
		}

		$base = untrailingslashit( $upload_base_url );

		if ( 0 !== strpos( $image, $base . '/' ) ) {
			return '';
		}

		return ltrim( substr( $image, strlen( $base ) ), '/' );
	}

	public static function get_nonce_action(): string {
		return self::NONCE_ACTION;
	}
}
