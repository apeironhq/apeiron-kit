<?php

namespace ApeironKit\Admin\Ajax;

use ApeironKit\Support\FormOrderSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormOrderHandler {
	public const NONCE_ACTION = 'apeiron_form_order_admin';

	private function authorize(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ], 403 );
		}
	}

	public function save_settings(): void {
		$this->authorize();
		$raw = isset( $_POST['form_data'] ) && is_string( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '';
		parse_str( $raw, $input );
		$settings = FormOrderSettings::sanitize( $input );
		if ( array_key_exists( 'delivery_target', $input )
			&& ( ! is_string( $input['delivery_target'] ) || ! in_array( $input['delivery_target'], [ 'whatsapp', 'spreadsheet', 'both' ], true ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Tujuan pengiriman tidak valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( ( in_array( $settings['delivery_target'], [ 'whatsapp', 'both' ], true ) && 'yes' !== $settings['whatsapp_enabled'] )
			|| ( in_array( $settings['delivery_target'], [ 'spreadsheet', 'both' ], true ) && 'yes' !== $settings['spreadsheet_enabled'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Aktifkan integrasi sesuai Tujuan Pengiriman pada tab WhatsApp atau Spreadsheet.', 'apeiron-kit' ) ], 422 );
		}
		$secret = isset( $input['secret_key'] ) && is_string( $input['secret_key'] ) ? $input['secret_key'] : '';
		if ( strlen( $secret ) > 512 || preg_match( '/[\x00-\x1f\x7f]/', $secret )
			|| strlen( $settings['success_message'] ) > 255 || '' === $settings['success_message']
			|| strlen( $settings['sheet_name'] ) > 100 || '' === $settings['sheet_name']
			|| strlen( $settings['whatsapp_template'] ) > 4000 ) {
			wp_send_json_error( [ 'message' => __( 'Pengaturan terlalu panjang atau tidak valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( 'yes' === $settings['whatsapp_enabled'] && ( ! preg_match( '/^[1-9][0-9]{7,14}$/D', $settings['whatsapp_phone'] ) || '' === trim( $settings['whatsapp_template'] ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Nomor dan template WhatsApp wajib valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( 'yes' === $settings['spreadsheet_enabled'] && ( ! FormOrderSettings::valid_webhook_url( $settings['webhook_url'] ) || ( '' === $secret && ! FormOrderSettings::has_secret() ) ) ) {
			wp_send_json_error( [ 'message' => __( 'URL Apps Script dan Secret Key wajib valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( '' !== $secret && ! FormOrderSettings::save_secret( $secret ) ) {
			wp_send_json_error( [ 'message' => __( 'Secret Key tidak dapat disimpan dengan aman.', 'apeiron-kit' ) ], 500 );
		}
		if ( ! update_option( FormOrderSettings::OPTION_NAME, $settings, false ) && FormOrderSettings::get() !== $settings ) {
			wp_send_json_error( [ 'message' => __( 'Pengaturan gagal disimpan.', 'apeiron-kit' ) ], 500 );
		}
		wp_send_json_success( [ 'message' => __( 'Pengaturan Form Order disimpan.', 'apeiron-kit' ) ] );
	}

	public function test_connection(): void {
		$this->authorize();
		$settings = FormOrderSettings::get();
		if ( ! FormOrderSettings::valid_webhook_url( $settings['webhook_url'] ) || ! FormOrderSettings::has_secret() ) {
			wp_send_json_error( [ 'message' => __( 'Simpan URL dan Secret Key terlebih dahulu.', 'apeiron-kit' ) ], 422 );
		}
		$failure = null;
		$version = null;
		if ( ! FormOrderSettings::send_to_sheet( [], 'test', $failure, [], $version ) ) {
			wp_send_json_error( [ 'message' => $failure ], 502 );
		}
		$message = 'compact-v3' === $version
			? __( 'Koneksi Spreadsheet berhasil. Code.gs compact-v3 aktif.', 'apeiron-kit' )
			: __( 'Koneksi Spreadsheet berhasil, tetapi Web App belum mengirim penanda Code.gs compact-v3. Pastikan URL /exec di dashboard mengarah ke deployment versi terbaru.', 'apeiron-kit' );
		wp_send_json_success( [ 'message' => $message ] );
	}
}
