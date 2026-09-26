<?php

declare( strict_types=1 );

namespace ApeironKit\Core;

/**
 * Small crypto primitives shared by the license and API-key stores.
 */
final class Crypto {

	private const LEGACY_CIPHER      = 'AES-256-CBC';
	private const LEGACY_IV_LENGTH   = 16;
	private const AEAD_CIPHER        = 'aes-256-gcm';
	private const AEAD_PREFIX        = 'v1:';
	private const AEAD_NONCE_LENGTH  = 12;
	private const AEAD_TAG_LENGTH    = 16;

	public static function derive_sha256_key( string $value ): string {
		return hash( 'sha256', $value, true );
	}

	public static function derive_hkdf_key( string $key_material, string $salt, string $context ): string {
		if ( '' === $key_material || ! function_exists( 'hash_hkdf' ) ) {
			return '';
		}

		$key = hash_hkdf( 'sha256', $key_material, 32, $context, $salt );
		return is_string( $key ) ? $key : '';
	}

	public static function derive_pbkdf2_key( string $password, string $salt, int $iterations = 10000, int $length = 32 ): string {
		if ( ! function_exists( 'hash_pbkdf2' ) ) {
			return '';
		}

		return hash_pbkdf2( 'sha256', $password, $salt, $iterations, $length, true );
	}

	public static function encrypt_authenticated( string $value, string $key, string $context ): string {
		if ( 32 !== strlen( $key ) || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		try {
			$nonce = random_bytes( self::AEAD_NONCE_LENGTH );
		} catch ( \Throwable $exception ) {
			return '';
		}

		$tag        = '';
		$ciphertext = openssl_encrypt(
			$value,
			self::AEAD_CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag,
			self::AEAD_PREFIX . $context,
			self::AEAD_TAG_LENGTH
		);

		if ( false === $ciphertext || self::AEAD_TAG_LENGTH !== strlen( $tag ) ) {
			return '';
		}

		return self::AEAD_PREFIX . base64_encode( $nonce . $tag . $ciphertext );
	}

	public static function decrypt_authenticated( string $encoded, string $key, string $context ): string {
		if ( 32 !== strlen( $key ) || ! function_exists( 'openssl_decrypt' ) || 0 !== strpos( $encoded, self::AEAD_PREFIX ) ) {
			return '';
		}

		$decoded = base64_decode( substr( $encoded, strlen( self::AEAD_PREFIX ) ), true );
		if ( false === $decoded || strlen( $decoded ) < self::AEAD_NONCE_LENGTH + self::AEAD_TAG_LENGTH ) {
			return '';
		}

		$nonce      = substr( $decoded, 0, self::AEAD_NONCE_LENGTH );
		$tag        = substr( $decoded, self::AEAD_NONCE_LENGTH, self::AEAD_TAG_LENGTH );
		$ciphertext = substr( $decoded, self::AEAD_NONCE_LENGTH + self::AEAD_TAG_LENGTH );
		$plaintext  = openssl_decrypt(
			$ciphertext,
			self::AEAD_CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag,
			self::AEAD_PREFIX . $context
		);

		return false === $plaintext ? '' : $plaintext;
	}

	public static function is_versioned( string $encoded ): bool {
		return 1 === preg_match( '/^v[0-9]+:/D', $encoded );
	}

	public static function encrypt_aes_cbc( string $value, string $key ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		try {
			$iv = random_bytes( self::LEGACY_IV_LENGTH );
		} catch ( \Throwable $exception ) {
			return '';
		}

		$encrypted = openssl_encrypt( $value, self::LEGACY_CIPHER, $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $encrypted ) {
			return '';
		}

		return base64_encode( $iv . $encrypted );
	}

	public static function decrypt_aes_cbc( string $encoded, string $key ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$decoded = base64_decode( $encoded, true );
		if ( false === $decoded || strlen( $decoded ) <= self::LEGACY_IV_LENGTH ) {
			return '';
		}

		$iv        = substr( $decoded, 0, self::LEGACY_IV_LENGTH );
		$encrypted = substr( $decoded, self::LEGACY_IV_LENGTH );
		$decrypted = openssl_decrypt( $encrypted, self::LEGACY_CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Decode the legacy XOR format during migration only.
	 */
	public static function xor_decrypt( string $encoded, string $key ): string {
		$data = base64_decode( $encoded, true );
		if ( false === $data || '' === $key ) {
			return '';
		}

		$decrypted  = '';
		$key_length = strlen( $key );
		for ( $i = 0, $length = strlen( $data ); $i < $length; $i++ ) {
			$decrypted .= $data[ $i ] ^ $key[ $i % $key_length ];
		}

		return $decrypted;
	}
}
