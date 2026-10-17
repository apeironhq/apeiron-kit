<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FormOrderSettings {
	public const OPTION_NAME = 'apeiron_form_order_settings';
	private const API_CONNECTION_OPTION = 'apeiron_form_order_api_connection';
	public const API_SERVICE = 'apeiron-form-order';
	public const API_VERSION = 'form-order-api-v1';

	public static function defaults(): array {
		return [
			'delivery_target' => 'both',
			'success_message' => __( 'Pesanan berhasil diproses.', 'apeiron-kit' ),
			'whatsapp_enabled' => 'no',
			'whatsapp_phone' => '',
			'whatsapp_template' => "Pesanan undangan baru:\n{details}",
			'whatsapp_behavior' => 'new_tab',
			'spreadsheet_enabled' => 'no',
			'storage_provider' => 'api',
			'api_url' => '',
		];
	}

	public static function get(): array {
		$saved = get_option( self::OPTION_NAME, [] );
		$saved = is_array( $saved ) ? array_intersect_key( $saved, self::defaults() ) : [];
		$saved['delivery_target'] = self::delivery_target( $saved );
		$saved['storage_provider'] = 'api';
		return wp_parse_args( $saved, self::defaults() );
	}

	/** Existing global enable flags provide the fallback for settings saved before this selector. */
	public static function delivery_target( array $settings ): string {
		if ( isset( $settings['delivery_target'] ) && is_string( $settings['delivery_target'] )
			&& in_array( $settings['delivery_target'], [ 'whatsapp', 'spreadsheet', 'both' ], true ) ) {
			return $settings['delivery_target'];
		}
		$whatsapp = 'yes' === ( $settings['whatsapp_enabled'] ?? 'no' );
		$sheet = 'yes' === ( $settings['spreadsheet_enabled'] ?? 'no' );
		return $whatsapp && ! $sheet ? 'whatsapp' : ( $sheet && ! $whatsapp ? 'spreadsheet' : 'both' );
	}

	public static function sanitize( array $input ): array {
		$phone = preg_replace( '/\D/', '', (string) ( $input['whatsapp_phone'] ?? '' ) );
		$behavior = sanitize_key( (string) ( $input['whatsapp_behavior'] ?? '' ) );
		return [
			'delivery_target' => self::delivery_target( $input ),
			'success_message' => sanitize_text_field( (string) ( $input['success_message'] ?? '' ) ),
			'whatsapp_enabled' => 'yes' === ( $input['whatsapp_enabled'] ?? '' ) ? 'yes' : 'no',
			'whatsapp_phone' => $phone,
			'whatsapp_template' => sanitize_textarea_field( (string) ( $input['whatsapp_template'] ?? '' ) ),
			'whatsapp_behavior' => in_array( $behavior, [ 'new_tab', 'same_tab' ], true ) ? $behavior : 'new_tab',
			'spreadsheet_enabled' => 'yes' === ( $input['spreadsheet_enabled'] ?? '' ) ? 'yes' : 'no',
			'storage_provider' => 'api',
			'api_url' => esc_url_raw( (string) ( $input['api_url'] ?? '' ) ),
		];
	}

	public static function api_url( ?array $settings = null ): string {
		$settings = $settings ?? self::get();
		if ( is_string( $settings['api_url'] ?? null ) && '' !== $settings['api_url'] ) { return $settings['api_url']; }
		$shared_url = DaftarHadiahSettings::get()['api_url'];
		return preg_match( '~/daftar-hadiah/?$~D', $shared_url ) ? (string) preg_replace( '~/daftar-hadiah/?$~D', '/form-order', $shared_url ) : '';
	}

	public static function valid_api_url( string $url ): bool {
		$shared_url = DaftarHadiahSettings::get()['api_url'];
		if ( ! DaftarHadiahSettings::valid_api_url( $url ) || ! DaftarHadiahSettings::valid_api_url( $shared_url ) ) { return false; }
		$parts = wp_parse_url( $url );
		$shared = wp_parse_url( $shared_url );
		return $parts['scheme'] === $shared['scheme'] && strtolower( $parts['host'] ) === strtolower( $shared['host'] )
			&& ( $parts['port'] ?? ( 'https' === $parts['scheme'] ? 443 : 80 ) ) === ( $shared['port'] ?? ( 'https' === $shared['scheme'] ? 443 : 80 ) );
	}

	private static function api_fingerprint(): string {
		return hash( 'sha256', self::api_url() . '|' . DaftarHadiahSettings::api_token() );
	}

	public static function api_connection(): array {
		$value = get_option( self::API_CONNECTION_OPTION, [] );
		if ( ! is_array( $value ) || ( $value['fingerprint'] ?? '' ) !== self::api_fingerprint() ) {
			return [ 'connected' => false, 'message' => __( 'Belum diuji', 'apeiron-kit' ) ];
		}
		return [ 'connected' => true === ( $value['connected'] ?? false ), 'message' => is_string( $value['message'] ?? null ) ? $value['message'] : '' ];
	}

	public static function api_ready(): bool {
		return self::valid_api_url( self::api_url() ) && DaftarHadiahSettings::has_api_token() && self::api_connection()['connected'];
	}

	public static function test_api_connection( ?string &$failure = null ): bool {
		$fingerprint = self::api_fingerprint();
		$connected = self::send_to_api( [], [], [], '', $failure, true );
		$message = $connected ? __( 'Koneksi Form Order API terverifikasi untuk penyimpanan khusus admin.', 'apeiron-kit' ) : ( $failure ?? __( 'Koneksi Form Order API gagal.', 'apeiron-kit' ) );
		update_option( self::API_CONNECTION_OPTION, [ 'fingerprint' => $fingerprint, 'connected' => $connected, 'message' => $message ], false );
		return $connected;
	}

	/** Write-only API contract; neither records nor credentials are returned to guests. */
	public static function send_to_api( array $fields, array $labels, array $context, string $submission_id, ?string &$failure = null, bool $test = false ): bool {
		$failure = null;
		$url = self::api_url();
		$token = DaftarHadiahSettings::api_token();
		if ( ! self::valid_api_url( $url ) || ! DaftarHadiahSettings::valid_api_token( $token ) ) {
			$failure = __( 'Hubungkan Token Website Daftar Hadiah dan gunakan protokol, host, serta port API yang sama. HTTP hanya untuk alamat lokal.', 'apeiron-kit' );
			return false;
		}
		$scope = [ 'website_id' => DaftarHadiahCatalog::website_id() ];
		$body = [
			'service' => self::API_SERVICE, 'schema_version' => 1, 'operation' => $test ? 'ping' : 'submit',
			'correlation_id' => wp_generate_uuid4(), 'website_id' => $scope['website_id'], 'website_url' => home_url( '/' ),
			'visibility' => 'admin_only',
		];
		if ( ! $test ) {
			if ( ! is_int( $context['post_id'] ?? null ) || $context['post_id'] < 1
				|| ! is_int( $context['document_id'] ?? null ) || $context['document_id'] < 1
				|| ! is_string( $context['element_id'] ?? null ) || ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $context['element_id'] )
				|| ! preg_match( '/^[a-f0-9]{64}$/D', $submission_id ) || ! $fields || count( $fields ) > 200 ) {
				$failure = __( 'Konteks atau data pesanan Form Order tidak valid.', 'apeiron-kit' );
				return false;
			}
			$scope += array_intersect_key( $context, array_flip( [ 'post_id', 'document_id', 'element_id' ] ) );
			$body += $scope + [
				'post_title' => sanitize_text_field( html_entity_decode( (string) get_the_title( $context['post_id'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
				'request_id' => $submission_id, 'fields' => $fields, 'labels' => $labels,
			];
		}
		$local_api = DaftarHadiahSettings::is_local_api_url( $url );
		$args = [
			'timeout' => $test ? 12 : 30, 'redirection' => 0, 'reject_unsafe_urls' => ! $local_api, 'limit_response_size' => 262144,
			'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token ],
			'body' => wp_json_encode( $body ),
		];
		$response = $local_api ? wp_remote_post( $url, $args ) : wp_safe_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) { $failure = __( 'WordPress tidak dapat menghubungi API Form Order.', 'apeiron-kit' ); return false; }
		$status = wp_remote_retrieve_response_code( $response );
		if ( in_array( $status, [ 301, 302, 303, 307, 308 ], true ) ) { $failure = __( 'Gunakan URL endpoint API Form Order final tanpa redirect.', 'apeiron-kit' ); return false; }
		if ( in_array( $status, [ 401, 403 ], true ) ) { $failure = __( 'Token Website tidak memiliki akses penyimpanan Form Order.', 'apeiron-kit' ); return false; }
		if ( 429 === $status ) { $failure = __( 'Batas permintaan API Form Order tercapai. Silakan coba lagi nanti.', 'apeiron-kit' ); return false; }
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $result ) || self::API_SERVICE !== ( $result['service'] ?? '' ) || self::API_VERSION !== ( $result['version'] ?? '' )
			|| 1 !== ( $result['schema_version'] ?? null ) || $body['operation'] !== ( $result['operation'] ?? '' ) || $body['correlation_id'] !== ( $result['correlation_id'] ?? '' ) ) {
			$failure = __( 'Respons API Form Order tidak sesuai kontrak form-order-api-v1.', 'apeiron-kit' );
			return false;
		}
		if ( $status < 200 || $status >= 300 || true !== ( $result['success'] ?? false ) ) {
			$failure = 'request_conflict' === ( $result['error'] ?? '' ) ? __( 'Identitas pengiriman sudah dipakai untuk pesanan berbeda. Muat ulang formulir.', 'apeiron-kit' ) : __( 'Backend API Form Order belum mengonfirmasi penyimpanan pesanan.', 'apeiron-kit' );
			return false;
		}
		$data = $result['data'] ?? null;
		if ( ! is_array( $data ) || 'admin_only' !== ( $data['visibility'] ?? '' ) || ! is_array( $data['scope'] ?? null ) ) {
			$failure = __( 'API Form Order belum memverifikasi penyimpanan khusus admin.', 'apeiron-kit' );
			return false;
		}
		foreach ( $scope as $key => $value ) {
			if ( ( $data['scope'][ $key ] ?? null ) !== $value ) { $failure = __( 'Identitas website atau formulir pada respons API tidak cocok.', 'apeiron-kit' ); return false; }
		}
		if ( $test ) {
			if ( true !== ( $data['storage_ready'] ?? false ) ) { $failure = __( 'Penyimpanan Form Order belum siap. Periksa migrasi database dan izin modul pada server.', 'apeiron-kit' ); return false; }
			return true;
		}
		if ( 'stored' !== ( $data['status'] ?? '' ) || $submission_id !== ( $data['request_id'] ?? '' )
			|| ! is_string( $data['order_id'] ?? null ) || ! preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $data['order_id'] )
			|| ! is_string( $data['timestamp'] ?? null ) || ! preg_match( '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d{3})?Z$/D', $data['timestamp'] ) ) {
			$failure = __( 'API Form Order tidak mengembalikan bukti penyimpanan pesanan yang valid.', 'apeiron-kit' );
			return false;
		}
		return true;
	}

}
