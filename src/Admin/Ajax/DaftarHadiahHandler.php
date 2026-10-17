<?php

declare( strict_types=1 );

namespace ApeironKit\Admin\Ajax;

use ApeironKit\Support\DaftarHadiahCatalog;
use ApeironKit\Support\DaftarHadiahGateway;
use ApeironKit\Support\DaftarHadiahSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DaftarHadiahHandler {
	public const NONCE_ACTION = 'apeiron_daftar_hadiah_admin';

	private function authorize(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [ 'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ) ], 403 ); }
	}

	public function save_settings(): void {
		$this->authorize();
		$raw = isset( $_POST['form_data'] ) && is_string( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '';
		parse_str( $raw, $input );
		$provider = $input['provider'] ?? 'gas';
		if ( ! in_array( $provider, [ 'gas', 'api' ], true ) ) { wp_send_json_error( [ 'message' => __( 'Pilih backend Daftar Hadiah yang valid.', 'apeiron-kit' ) ], 422 ); }
		$enabled = 'yes' === ( $input['enabled'] ?? '' ) ? 'yes' : 'no';
		$settings = array_merge( DaftarHadiahSettings::get(), [ 'enabled' => $enabled, 'provider' => $provider ] );
		if ( 'api' === $provider ) {
			$url = is_string( $input['api_url'] ?? null ) ? trim( $input['api_url'] ) : '';
			$token = is_string( $input['api_token'] ?? null ) ? $input['api_token'] : '';
			if ( ( '' !== $url && ! DaftarHadiahSettings::valid_api_url( $url ) ) || strlen( $url ) > 2048
				|| ( '' !== $token && ! DaftarHadiahSettings::valid_api_token( $token ) )
				|| ( 'yes' === $enabled && ( '' === $url || ( '' === $token && ! DaftarHadiahSettings::has_api_token() ) ) ) ) {
				wp_send_json_error( [ 'message' => __( 'Gunakan HTTPS untuk API publik atau HTTP untuk alamat lokal. Token Website harus 24–512 karakter berupa huruf, angka, titik, garis bawah, tanda ~, atau tanda hubung.', 'apeiron-kit' ) ], 422 );
			}
			if ( '' !== $token && ! DaftarHadiahSettings::save_api_token( $token ) ) { wp_send_json_error( [ 'message' => __( 'Token Website tidak dapat disimpan dengan aman.', 'apeiron-kit' ) ], 500 ); }
			$settings['api_url'] = esc_url_raw( $url );
		} else {
			$url = is_string( $input['webhook_url'] ?? null ) ? trim( $input['webhook_url'] ) : '';
			$secret = is_string( $input['secret_key'] ?? null ) ? $input['secret_key'] : '';
			if ( ( '' !== $url && ! DaftarHadiahSettings::valid_webhook_url( $url ) ) || strlen( $url ) > 2048
				|| ( '' !== $secret && ( strlen( $secret ) < 24 || strlen( $secret ) > 512 || trim( $secret ) !== $secret || preg_match( '/[\x00-\x1f\x7f]/', $secret ) ) )
				|| ( 'yes' === $enabled && ( '' === $url || ( '' === $secret && ! DaftarHadiahSettings::has_secret() ) ) ) ) {
				wp_send_json_error( [ 'message' => __( 'Gunakan URL GAS /exec yang valid dan Secret Key minimal 24 karakter.', 'apeiron-kit' ) ], 422 );
			}
			if ( '' !== $secret && ! DaftarHadiahSettings::save_secret( $secret ) ) { wp_send_json_error( [ 'message' => __( 'Secret Key tidak dapat disimpan dengan aman.', 'apeiron-kit' ) ], 500 ); }
			$settings['webhook_url'] = esc_url_raw( $url );
		}
		if ( ! update_option( DaftarHadiahSettings::OPTION_NAME, $settings, false ) && DaftarHadiahSettings::get() !== $settings ) {
			wp_send_json_error( [ 'message' => __( 'Pengaturan gagal disimpan.', 'apeiron-kit' ) ], 500 );
		}
		wp_send_json_success( [ 'message' => __( 'Pengaturan Daftar Hadiah disimpan.', 'apeiron-kit' ), 'connection' => DaftarHadiahSettings::connection(), 'has_secret' => DaftarHadiahSettings::has_secret(), 'has_api_token' => DaftarHadiahSettings::has_api_token() ] );
	}

	public function test_connection(): void {
		$this->authorize();
		$result = DaftarHadiahGateway::request( 'ping' );
		$connected = ! is_wp_error( $result );
		$success_message = 'api' === DaftarHadiahSettings::get()['provider'] ? sprintf( __( 'Koneksi API Daftar Hadiah %s dan backend terverifikasi.', 'apeiron-kit' ), DaftarHadiahGateway::API_VERSION ) : __( 'Koneksi Daftar Hadiah registry-v1 dan struktur Spreadsheet terverifikasi.', 'apeiron-kit' );
		$message = $connected ? $success_message : $result->get_error_message();
		DaftarHadiahSettings::record_connection( $connected, $message );
		if ( ! $connected ) { wp_send_json_error( [ 'message' => $message, 'connection' => DaftarHadiahSettings::connection() ], 502 ); }
		wp_send_json_success( [ 'message' => $message, 'connection' => DaftarHadiahSettings::connection() ] );
	}

	private function invitation(): int {
		$value = $_POST['invitation_id'] ?? '';
		$id = is_string( $value ) && ctype_digit( $value ) ? (int) $value : 0;
		$post = get_post( $id );
		if ( ! $post || $id < 1 || in_array( $post->post_type, [ 'elementor_library', 'revision', 'attachment' ], true ) || ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Post undangan tidak valid atau tidak dapat diakses.', 'apeiron-kit' ) ], 403 );
		}
		return $id;
	}

	public function load_catalogs(): void {
		$this->authorize();
		$id = $this->invitation();
		$registries = DaftarHadiahCatalog::for_post( $id );
		wp_send_json_success( [
			'message' => $registries ? __( 'Katalog undangan dimuat.', 'apeiron-kit' ) : __( 'Katalog belum tersedia. Buka halaman undangan setelah menyimpan hadiah di Elementor.', 'apeiron-kit' ),
			'invitation_id' => $id, 'invitation_title' => DaftarHadiahCatalog::invitation_title( $id ), 'registries' => $registries,
		] );
	}

	private function record(): array {
		$id = $this->invitation();
		$registry = isset( $_POST['registry_id'] ) && is_string( $_POST['registry_id'] ) ? wp_unslash( $_POST['registry_id'] ) : '';
		if ( ! preg_match( '/^registry-[1-9][0-9]*-[A-Za-z0-9_-]{1,100}$/D', $registry ) ) {
			wp_send_json_error( [ 'message' => __( 'Pilih registry yang valid.', 'apeiron-kit' ) ], 422 );
		}
		$record = DaftarHadiahCatalog::load( $id, $registry );
		if ( is_wp_error( $record ) ) { wp_send_json_error( [ 'message' => $record->get_error_message() ], 409 ); }
		if ( ! DaftarHadiahSettings::connection()['connected'] ) { wp_send_json_error( [ 'message' => __( 'Simpan konfigurasi dan jalankan Uji Koneksi terlebih dahulu.', 'apeiron-kit' ) ], 503 ); }
		return $record;
	}

	public function sync_catalog(): void {
		$this->authorize();
		$record = $this->record();
		$data = DaftarHadiahGateway::request( 'sync_catalog', DaftarHadiahCatalog::payload( $record ) );
		if ( is_wp_error( $data ) ) { wp_send_json_error( [ 'message' => $data->get_error_message() ], 502 ); }
		$stock = DaftarHadiahGateway::availability( $data, $record );
		if ( is_wp_error( $stock ) ) { wp_send_json_error( [ 'message' => $stock->get_error_message() ], 502 ); }
		$names = array_column( $record['gifts'], 'name', 'gift_id' );
		foreach ( $stock as &$gift ) { $gift['name'] = $names[ $gift['gift_id'] ]; }
		unset( $gift );
		wp_send_json_success( [ 'message' => 'api' === DaftarHadiahSettings::get()['provider'] ? __( 'Katalog tersinkron. Jumlah tersedia sudah memperhitungkan reservasi API Daftar Hadiah.', 'apeiron-kit' ) : __( 'Katalog tersinkron. Jumlah tersedia sudah memperhitungkan reservasi GAS.', 'apeiron-kit' ), 'gifts' => $stock ] );
	}

	public function read_reservations(): void {
		$this->authorize();
		$record = $this->record();
		$offset = $_POST['offset'] ?? '0';
		if ( ! is_string( $offset ) || ! ctype_digit( $offset ) || (float) $offset > 10000000 ) { wp_send_json_error( [ 'message' => __( 'Halaman reservasi tidak valid.', 'apeiron-kit' ) ], 422 ); }
		$data = DaftarHadiahGateway::request( 'reservations', DaftarHadiahCatalog::payload( $record ) + [ 'offset' => (int) $offset ] );
		if ( is_wp_error( $data ) ) { wp_send_json_error( [ 'message' => $data->get_error_message() ], 502 ); }
		$rows = $data['reservations'] ?? null;
		if ( ! is_array( $rows ) || count( $rows ) > 30 || ! is_int( $data['total'] ?? null ) || $data['total'] < 0
			|| ( null !== ( $data['next_offset'] ?? null ) && ( ! is_int( $data['next_offset'] ) || $data['next_offset'] <= (int) $offset || $data['next_offset'] > $data['total'] ) ) ) {
			wp_send_json_error( [ 'message' => 'api' === DaftarHadiahSettings::get()['provider'] ? __( 'Respons reservasi API Daftar Hadiah tidak valid.', 'apeiron-kit' ) : __( 'Respons reservasi GAS tidak valid.', 'apeiron-kit' ) ], 502 );
		}
		$clean = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! is_array( $row['guest'] ?? null ) || ! is_string( $row['guest']['name'] ?? null )
				|| ! is_string( $row['guest']['whatsapp'] ?? null ) || ! is_string( $row['gift_name'] ?? null ) || ! is_string( $row['message'] ?? null )
				|| ! is_string( $row['timestamp'] ?? null ) || ! is_string( $row['status'] ?? null ) || ! is_int( $row['quantity'] ?? null )
				|| ! is_string( $row['reservation_id'] ?? null ) || ! is_string( $row['gift_id'] ?? null ) || ! is_string( $row['guest']['id'] ?? null ) ) {
				wp_send_json_error( [ 'message' => 'api' === DaftarHadiahSettings::get()['provider'] ? __( 'Respons reservasi API Daftar Hadiah tidak valid.', 'apeiron-kit' ) : __( 'Respons reservasi GAS tidak valid.', 'apeiron-kit' ) ], 502 );
			}
			$clean[] = array_intersect_key( $row, array_flip( [ 'reservation_id', 'gift_id', 'gift_name', 'quantity', 'message', 'timestamp', 'status' ] ) )
				+ [ 'guest' => array_intersect_key( $row['guest'], array_flip( [ 'id', 'name', 'whatsapp' ] ) ) ];
		}
		wp_send_json_success( [ 'message' => __( 'Reservasi untuk undangan dan registry terpilih dimuat.', 'apeiron-kit' ), 'reservations' => $clean, 'total' => $data['total'], 'next_offset' => $data['next_offset'] ?? null ] );
	}
}
