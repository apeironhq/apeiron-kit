<?php

namespace ApeironKit\Admin\Ajax;

use ApeironKit\Support\FormOrderSettings;
use ApeironKit\Support\DaftarHadiahSettings;

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
		if ( isset( $input['storage_provider'] ) && 'api' !== $input['storage_provider'] ) {
			wp_send_json_error( [ 'message' => __( 'Pilih penyimpanan Form Order yang valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( array_key_exists( 'api_url', $input ) && ! is_string( $input['api_url'] ) ) {
			wp_send_json_error( [ 'message' => __( 'URL API Form Order tidak valid.', 'apeiron-kit' ) ], 422 );
		}
		$settings = FormOrderSettings::sanitize( $input );
		if ( array_key_exists( 'delivery_target', $input )
			&& ( ! is_string( $input['delivery_target'] ) || ! in_array( $input['delivery_target'], [ 'whatsapp', 'spreadsheet', 'both' ], true ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Tujuan pengiriman tidak valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( ( in_array( $settings['delivery_target'], [ 'whatsapp', 'both' ], true ) && 'yes' !== $settings['whatsapp_enabled'] )
			|| ( in_array( $settings['delivery_target'], [ 'spreadsheet', 'both' ], true ) && 'yes' !== $settings['spreadsheet_enabled'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Aktifkan integrasi sesuai Tujuan Pengiriman pada tab WhatsApp atau Penyimpanan.', 'apeiron-kit' ) ], 422 );
		}
		if ( strlen( $settings['success_message'] ) > 255 || '' === $settings['success_message']
			|| strlen( $settings['whatsapp_template'] ) > 4000 ) {
			wp_send_json_error( [ 'message' => __( 'Pengaturan terlalu panjang atau tidak valid.', 'apeiron-kit' ) ], 422 );
		}
		if ( 'yes' === $settings['whatsapp_enabled'] && ( ! preg_match( '/^[1-9][0-9]{7,14}$/D', $settings['whatsapp_phone'] ) || '' === trim( $settings['whatsapp_template'] ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Nomor dan template WhatsApp wajib valid.', 'apeiron-kit' ) ], 422 );
		}
		$url = FormOrderSettings::api_url( $settings );
		$needs_storage = in_array( $settings['delivery_target'], [ 'spreadsheet', 'both' ], true );
		if ( strlen( $settings['api_url'] ) > 2048 || ( '' !== $url && ! FormOrderSettings::valid_api_url( $url ) )
			|| ( $needs_storage && ( ! FormOrderSettings::valid_api_url( $url ) || ! DaftarHadiahSettings::has_api_token() ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Hubungkan Token Website Daftar Hadiah dan gunakan protokol, host, serta port API yang sama. HTTP hanya untuk alamat lokal.', 'apeiron-kit' ) ], 422 );
		}
		$current = get_option( FormOrderSettings::OPTION_NAME, [] );
		$stored = array_merge( is_array( $current ) ? $current : [], $settings );
		if ( ! update_option( FormOrderSettings::OPTION_NAME, $stored, false ) && get_option( FormOrderSettings::OPTION_NAME ) !== $stored ) {
			wp_send_json_error( [ 'message' => __( 'Pengaturan gagal disimpan.', 'apeiron-kit' ) ], 500 );
		}
		wp_send_json_success( [ 'message' => __( 'Pengaturan Form Order disimpan.', 'apeiron-kit' ), 'storage_provider' => 'api', 'api_connection' => FormOrderSettings::api_connection() ] );
	}

	public function test_connection(): void {
		$this->authorize();
		$failure = null;
		if ( ! FormOrderSettings::test_api_connection( $failure ) ) { wp_send_json_error( [ 'message' => $failure, 'api_connection' => FormOrderSettings::api_connection() ], 502 ); }
		wp_send_json_success( [ 'message' => FormOrderSettings::api_connection()['message'], 'api_connection' => FormOrderSettings::api_connection() ] );
	}
}
