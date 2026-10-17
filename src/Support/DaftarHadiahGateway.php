<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WordPress-only transport. Credentials never form part of a browser response. */
final class DaftarHadiahGateway {
	public const SERVICE = 'apeiron-daftar-hadiah';
	public const VERSION = 'registry-v1';
	public const API_VERSION = 'registry-api-v1';

	/** @return array|WP_Error */
	public static function request( string $operation, array $payload = [] ) {
		$settings = DaftarHadiahSettings::get();
		$is_api = 'api' === $settings['provider'];
		$url = $is_api ? $settings['api_url'] : $settings['webhook_url'];
		$secret = $is_api ? DaftarHadiahSettings::api_token() : DaftarHadiahSettings::secret();
		$valid_url = $is_api ? DaftarHadiahSettings::valid_api_url( $url ) : DaftarHadiahSettings::valid_webhook_url( $url );
		if ( ! $valid_url || '' === $secret || ( $is_api && ! DaftarHadiahSettings::valid_api_token( $secret ) ) ) {
			return new WP_Error( 'not_configured', $is_api ? __( 'Simpan URL API dan Token Website Daftar Hadiah terlebih dahulu.', 'apeiron-kit' ) : __( 'Simpan URL Web App dan Secret Key Daftar Hadiah terlebih dahulu.', 'apeiron-kit' ), [ 'status' => 503 ] );
		}
		$correlation = wp_generate_uuid4();
		$body = array_merge( $payload, [
			'service' => self::SERVICE, 'schema_version' => 1, 'operation' => $operation,
			'correlation_id' => $correlation,
		] );
		$headers = [ 'Content-Type' => 'application/json' ];
		if ( $is_api ) {
			if ( ! in_array( $operation, [ 'ping', 'sync_catalog', 'availability', 'reserve', 'reservations' ], true ) ) { return self::error( 'invalid_request' ); }
			unset( $body['secret'] );
			$headers['Authorization'] = 'Bearer ' . $secret;
			$body['website_id'] = DaftarHadiahCatalog::website_id();
			$body['website_url'] = home_url( '/' );
			if ( 'ping' !== $operation ) {
				if ( ! is_int( $payload['invitation_id'] ?? null ) || $payload['invitation_id'] < 1 || ( $payload['website_id'] ?? '' ) !== $body['website_id'] ) { return self::error( 'invalid_request' ); }
				$owner_id = DaftarHadiahCatalog::owner_id( $payload['invitation_id'] );
				if ( is_wp_error( $owner_id ) ) { return $owner_id; }
				$body['owner_id'] = $owner_id;
			}
		} else {
			$body['secret'] = $secret;
		}
		$local_api = $is_api && DaftarHadiahSettings::is_local_api_url( $url );
		$args = [
			'timeout' => 'reserve' === $operation ? 30 : 20, 'redirection' => 0, 'reject_unsafe_urls' => ! $local_api,
			'limit_response_size' => 1048576, 'headers' => $headers, 'body' => wp_json_encode( $body ),
		];
		$response = $local_api ? wp_remote_post( $url, $args ) : wp_safe_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) { return self::error( 'connection_failed' ); }
		if ( $is_api && in_array( wp_remote_retrieve_response_code( $response ), [ 301, 302, 303, 307, 308 ], true ) ) { return self::error( 'api_redirect' ); }
		for ( $redirect = 0; ! $is_api && in_array( wp_remote_retrieve_response_code( $response ), [ 302, 303 ], true ) && $redirect < 3; ++$redirect ) {
			$location = wp_remote_retrieve_header( $response, 'location' );
			$parts = is_string( $location ) ? wp_parse_url( $location ) : false;
			if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || 'script.googleusercontent.com' !== ( $parts['host'] ?? '' )
				|| '/macros/echo' !== ( $parts['path'] ?? '' ) || isset( $parts['port'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ) {
				return self::error( 'invalid_redirect' );
			}
			// Google serves ContentService output through a GET redirect; never forward the secret.
			$response = wp_safe_remote_get( $location, [ 'timeout' => 20, 'redirection' => 0, 'reject_unsafe_urls' => true, 'limit_response_size' => 1048576 ] );
			if ( is_wp_error( $response ) ) { return self::error( 'connection_failed' ); }
		}
		$status = wp_remote_retrieve_response_code( $response );
		if ( $is_api && in_array( $status, [ 401, 403 ], true ) ) { return self::error( 'api_unauthorized' ); }
		if ( $is_api && 429 === $status ) { return self::error( 'api_rate_limit' ); }
		if ( ! $is_api && 200 !== $status ) { return self::error( 'http_error' ); }
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $result ) || self::SERVICE !== ( $result['service'] ?? '' ) || ( $is_api ? self::API_VERSION : self::VERSION ) !== ( $result['version'] ?? '' )
			|| 1 !== ( $result['schema_version'] ?? null ) || $operation !== ( $result['operation'] ?? '' ) || $correlation !== ( $result['correlation_id'] ?? '' ) ) {
			return self::error( 'invalid_response' );
		}
		if ( true !== ( $result['success'] ?? false ) ) { return self::error( is_string( $result['error'] ?? null ) ? $result['error'] : 'invalid_response' ); }
		if ( $is_api && ( $status < 200 || $status >= 300 ) ) { return self::error( 'http_error' ); }
		if ( 'ping' === $operation ) {
			if ( $is_api ) { return true === ( $result['data']['backend_ready'] ?? false ) ? $result['data'] : self::error( 'not_initialized' ); }
			return true === ( $result['data']['spreadsheet_ready'] ?? false ) ? $result['data'] : self::error( 'not_initialized' );
		}
		$data = $result['data'] ?? null;
		if ( ! is_array( $data ) || ! is_array( $data['scope'] ?? null ) ) { return self::error( 'invalid_response' ); }
		foreach ( [ 'website_id', 'invitation_id', 'registry_id' ] as $key ) {
			if ( ( $data['scope'][ $key ] ?? null ) !== ( $payload[ $key ] ?? null ) ) { return self::error( 'scope_mismatch' ); }
		}
		if ( $is_api ) {
			if ( ! array_key_exists( 'owner_id', $data['scope'] ) || $data['scope']['owner_id'] !== $body['owner_id'] ) { return self::error( 'scope_mismatch' ); }
			$data['scope'] = array_intersect_key( $data['scope'], array_flip( [ 'website_id', 'invitation_id', 'registry_id' ] ) );
		}
		return $data;
	}

	public static function error( string $code ): WP_Error {
		$messages = [
			'connection_failed' => __( 'WordPress tidak dapat menghubungi GAS. Periksa koneksi keluar dan SSL hosting.', 'apeiron-kit' ),
			'invalid_redirect' => __( 'Redirect GAS tidak valid. Pastikan URL /exec, Execute as Me, dan akses Anyone.', 'apeiron-kit' ),
			'http_error' => __( 'Web App tidak memberikan respons HTTP 200. Periksa URL deployment dan izin akses.', 'apeiron-kit' ),
			'invalid_response' => __( 'Respons GAS tidak sesuai kontrak Daftar Hadiah registry-v1. Periksa Code.gs dan versi deployment.', 'apeiron-kit' ),
			'scope_mismatch' => __( 'Identitas undangan pada respons GAS tidak cocok. Operasi tidak dapat dikonfirmasi.', 'apeiron-kit' ),
			'not_initialized' => __( 'Spreadsheet belum siap. Jalankan setupDaftarHadiah pada Apps Script terlebih dahulu.', 'apeiron-kit' ),
			'secret_missing' => __( 'Script Property APEIRON_DAFTAR_HADIAH_SECRET belum diisi.', 'apeiron-kit' ),
			'secret_mismatch' => __( 'Secret Key GAS tidak cocok dengan pengaturan Daftar Hadiah.', 'apeiron-kit' ),
			'sheet_conflict' => __( 'Struktur sheet tidak sesuai. Gunakan sheet Daftar Hadiah dengan header dari setupDaftarHadiah.', 'apeiron-kit' ),
			'catalog_stale' => __( 'Katalog berubah saat diproses. Muat ulang undangan sebelum melanjutkan.', 'apeiron-kit' ),
			'invalid_catalog' => __( 'Data katalog ditolak GAS. Periksa ID dan konfigurasi jumlah hadiah.', 'apeiron-kit' ),
			'gift_unavailable' => __( 'Hadiah ini sudah tidak tersedia. Silakan pilih hadiah lainnya.', 'apeiron-kit' ),
			'invalid_request' => __( 'Data reservasi tidak valid.', 'apeiron-kit' ),
			'request_conflict' => __( 'ID permintaan sudah digunakan untuk data lain. Muat ulang halaman.', 'apeiron-kit' ),
			'busy' => __( 'Reservasi lain sedang diproses. Silakan coba kembali.', 'apeiron-kit' ),
			'script_error' => __( 'GAS gagal memproses data. Periksa ID Spreadsheet, izin akses, dan deployment.', 'apeiron-kit' ),
		];
		if ( 'api' === DaftarHadiahSettings::get()['provider'] ) {
			$messages = array_merge( $messages, [
				'connection_failed' => __( 'WordPress tidak dapat menghubungi API Daftar Hadiah. Periksa URL API, SSL, dan akses keluar hosting.', 'apeiron-kit' ),
				'http_error' => __( 'API Daftar Hadiah tidak memberikan respons HTTP yang valid. Periksa URL endpoint dan deployment.', 'apeiron-kit' ),
				'invalid_response' => __( 'Respons API Daftar Hadiah tidak sesuai kontrak registry-api-v1. Periksa versi backend.', 'apeiron-kit' ),
				'scope_mismatch' => __( 'Identitas undangan atau pemilik pada respons API Daftar Hadiah tidak cocok. Operasi tidak dapat dikonfirmasi.', 'apeiron-kit' ),
				'not_initialized' => __( 'Backend API Daftar Hadiah belum siap. Jalankan migrasi database dan periksa konfigurasi server.', 'apeiron-kit' ),
				'api_redirect' => __( 'URL API mengalihkan permintaan. Gunakan URL endpoint final tanpa redirect.', 'apeiron-kit' ),
				'api_unauthorized' => __( 'Token Website ditolak API Daftar Hadiah. Periksa token dan akses website pada dashboard Daftar Hadiah.', 'apeiron-kit' ),
				'api_rate_limit' => __( 'Batas permintaan API Daftar Hadiah tercapai. Silakan coba kembali nanti.', 'apeiron-kit' ),
				'invalid_catalog' => __( 'Data katalog ditolak API Daftar Hadiah. Periksa ID dan konfigurasi jumlah hadiah.', 'apeiron-kit' ),
				'script_error' => __( 'Backend API Daftar Hadiah gagal memproses data. Periksa deployment dan database.', 'apeiron-kit' ),
			] );
		}
		$status = in_array( $code, [ 'gift_unavailable', 'catalog_stale', 'request_conflict' ], true ) ? 409 : ( 'busy' === $code ? 503 : ( 'api_rate_limit' === $code ? 429 : 502 ) );
		return new WP_Error( $code, $messages[ $code ] ?? $messages['invalid_response'], [ 'status' => $status ] );
	}

	/** Reject missing, duplicated, foreign, or impossible stock values. @return array|WP_Error */
	public static function availability( array $data, array $record ) {
		$rows = $data['gifts'] ?? null;
		if ( ! is_array( $rows ) || count( $rows ) !== count( $record['gifts'] ) ) { return self::error( 'invalid_response' ); }
		$expected = array_column( $record['gifts'], null, 'gift_id' );
		$seen = [];
		$clean = [];
		foreach ( $rows as $row ) {
			$id = is_array( $row ) && is_string( $row['gift_id'] ?? null ) ? $row['gift_id'] : '';
			if ( ! isset( $expected[ $id ] ) || isset( $seen[ $id ] )
				|| ! is_int( $row['initial_quantity'] ?? null ) || $row['initial_quantity'] !== $expected[ $id ]['initial_quantity']
				|| ! is_int( $row['available_quantity'] ?? null ) || $row['available_quantity'] < 0 || $row['available_quantity'] > $expected[ $id ]['available_quantity']
				|| ( $row['availability'] ?? '' ) !== ( $row['available_quantity'] > 0 ? 'available' : 'unavailable' ) ) {
				return self::error( 'invalid_response' );
			}
			$seen[ $id ] = true;
			$clean[] = array_intersect_key( $row, array_flip( [ 'gift_id', 'initial_quantity', 'available_quantity', 'availability' ] ) );
		}
		return $clean;
	}
}
