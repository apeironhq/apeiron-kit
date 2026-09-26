<?php

namespace ApeironKit\Admin\Ajax;

use ApeironKit\Support\UcapanTamuSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UcapanTamuHandler {

	private const NONCE_ACTION = 'apeiron_ucapan_tamu';
	private bool $registered = false;

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		add_action( 'wp_ajax_apeiron_save_ucapan_tamu', [ $this, 'save_settings' ] );
	}

	public static function get_nonce_action(): string {
		return self::NONCE_ACTION;
	}

	public function save_settings(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ] );
		}

		parse_str( wp_unslash( $_POST['form_data'] ?? '' ), $form_data );

		$current = UcapanTamuSettings::get_settings();
		foreach ( [ 'prevent_duplicate', 'enable_rate_limit' ] as $switch_key ) {
			if ( ! array_key_exists( $switch_key, $form_data ) ) {
				$form_data[ $switch_key ] = '';
			}
		}
		$settings   = UcapanTamuSettings::sanitize( array_replace( $current, $form_data ) );

		update_option( UcapanTamuSettings::OPTION_NAME, $settings, false );

		wp_send_json_success( [ 'message' => __( 'Pengaturan berhasil disimpan.', 'apeiron-kit' ) ] );
	}
}
