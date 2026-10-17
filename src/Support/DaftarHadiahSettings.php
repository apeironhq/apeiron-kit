<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

use ApeironKit\Core\Crypto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DaftarHadiahSettings {
	public const OPTION_NAME = 'apeiron_daftar_hadiah_settings';
	private const SECRET_OPTION = 'apeiron_daftar_hadiah_secret';
	private const CONNECTION_OPTION = 'apeiron_daftar_hadiah_connection';
	private const CONTEXT = 'apeiron-kit/daftar-hadiah/v1';
	private const API_TOKEN_OPTION = 'apeiron_daftar_hadiah_api_token';
	private const API_CONNECTION_OPTION = 'apeiron_daftar_hadiah_api_connection';
	private const API_CONTEXT = 'apeiron-kit/daftar-hadiah/api/v1';

	public static function get(): array {
		$saved = get_option( self::OPTION_NAME, [] );
		$saved = is_array( $saved ) ? $saved : [];
		return [
			'enabled' => 'yes' === ( $saved['enabled'] ?? '' ) ? 'yes' : 'no',
			'provider' => 'api' === ( $saved['provider'] ?? '' ) ? 'api' : 'gas',
			'webhook_url' => is_string( $saved['webhook_url'] ?? null ) ? $saved['webhook_url'] : '',
			'api_url' => is_string( $saved['api_url'] ?? null ) ? $saved['api_url'] : '',
		];
	}

	public static function valid_webhook_url( string $url ): bool {
		$parts = wp_parse_url( $url );
		return is_array( $parts ) && 'https' === ( $parts['scheme'] ?? '' ) && 'script.google.com' === ( $parts['host'] ?? '' )
			&& empty( $parts['port'] ) && empty( $parts['user'] ) && empty( $parts['pass'] )
			&& empty( $parts['query'] ) && empty( $parts['fragment'] )
			&& (bool) preg_match( '~^/macros/s/[A-Za-z0-9_-]+/exec$~D', $parts['path'] ?? '' );
	}

	public static function valid_api_url( string $url ): bool {
		if ( self::is_local_api_url( $url ) ) { return true; }
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || empty( $parts['host'] )
			|| isset( $parts['port'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			return false;
		}
		$host = strtolower( $parts['host'] );
		return false !== filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME )
			&& (bool) preg_match( '/\.[a-z][a-z0-9-]*$/D', $host )
			&& ! preg_match( '/(?:^|\.)(?:localhost|local|internal|test|invalid)$/D', $host );
	}

	public static function is_local_api_url( string $url ): bool {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || ! in_array( $parts['scheme'] ?? '', [ 'http', 'https' ], true ) || empty( $parts['host'] )
			|| isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] )
			|| ( isset( $parts['port'] ) && ( $parts['port'] < 1 || $parts['port'] > 65535 ) ) ) {
			return false;
		}
		$host = strtolower( $parts['host'] );
		return in_array( $host, [ 'localhost', '127.0.0.1', '[::1]' ], true )
			|| ( false !== filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) && (bool) preg_match( '/\.(?:localhost|local|test)$/D', $host ) );
	}

	public static function valid_api_token( string $token ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9._~-]{24,512}$/D', $token );
	}

	private static function key( string $context = self::CONTEXT ): string {
		return Crypto::derive_hkdf_key( wp_salt( 'auth' ), wp_salt( 'secure_auth' ), $context );
	}

	public static function save_secret( string $secret ): bool {
		$encrypted = Crypto::encrypt_authenticated( $secret, self::key(), self::CONTEXT );
		return '' !== $encrypted && ( update_option( self::SECRET_OPTION, $encrypted, false ) || get_option( self::SECRET_OPTION ) === $encrypted );
	}

	public static function secret(): string {
		$value = get_option( self::SECRET_OPTION, '' );
		return is_string( $value ) ? Crypto::decrypt_authenticated( $value, self::key(), self::CONTEXT ) : '';
	}

	public static function has_secret(): bool {
		return '' !== self::secret();
	}

	public static function save_api_token( string $token ): bool {
		if ( ! self::valid_api_token( $token ) ) { return false; }
		$encrypted = Crypto::encrypt_authenticated( $token, self::key( self::API_CONTEXT ), self::API_CONTEXT );
		return '' !== $encrypted && ( update_option( self::API_TOKEN_OPTION, $encrypted, false ) || get_option( self::API_TOKEN_OPTION ) === $encrypted );
	}

	public static function api_token(): string {
		$value = get_option( self::API_TOKEN_OPTION, '' );
		return is_string( $value ) ? Crypto::decrypt_authenticated( $value, self::key( self::API_CONTEXT ), self::API_CONTEXT ) : '';
	}

	public static function has_api_token(): bool {
		return self::valid_api_token( self::api_token() );
	}

	private static function fingerprint( ?string $provider = null ): string {
		$provider = $provider ?? self::get()['provider'];
		if ( 'api' === $provider ) {
			$encrypted = get_option( self::API_TOKEN_OPTION, '' );
			return hash( 'sha256', self::get()['api_url'] . '|' . ( is_string( $encrypted ) ? $encrypted : '' ) );
		}
		$encrypted = get_option( self::SECRET_OPTION, '' );
		return hash( 'sha256', self::get()['webhook_url'] . '|' . ( is_string( $encrypted ) ? $encrypted : '' ) );
	}

	public static function connection( ?string $provider = null ): array {
		$provider = $provider ?? self::get()['provider'];
		$value = get_option( 'api' === $provider ? self::API_CONNECTION_OPTION : self::CONNECTION_OPTION, [] );
		if ( ! is_array( $value ) || ( $value['fingerprint'] ?? '' ) !== self::fingerprint( $provider ) ) {
			return [ 'connected' => false, 'checked_at' => '', 'message' => __( 'Belum diuji', 'apeiron-kit' ) ];
		}
		$message = is_string( $value['message'] ?? null ) ? $value['message'] : '';
		return [
			'connected' => true === ( $value['connected'] ?? false ),
			'checked_at' => is_string( $value['checked_at'] ?? null ) ? $value['checked_at'] : '',
			'message' => 'api' === $provider ? str_ireplace( 'SaaS', 'Daftar Hadiah', $message ) : $message,
		];
	}

	public static function record_connection( bool $connected, string $message ): void {
		update_option( 'api' === self::get()['provider'] ? self::API_CONNECTION_OPTION : self::CONNECTION_OPTION, [
			'fingerprint' => self::fingerprint(), 'connected' => $connected,
			'checked_at' => gmdate( 'c' ), 'message' => $message,
		], false );
	}

	public static function ready(): bool {
		$settings = self::get();
		if ( 'api' === $settings['provider'] ) {
			return 'yes' === $settings['enabled'] && self::valid_api_url( $settings['api_url'] )
				&& self::has_api_token() && self::connection()['connected'];
		}
		return 'yes' === $settings['enabled'] && self::valid_webhook_url( $settings['webhook_url'] )
			&& self::has_secret() && self::connection()['connected'];
	}
}
