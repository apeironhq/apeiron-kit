<?php

namespace ApeironKit\Core;

/**
 * API Key Manager
 * 
 * Handles encrypted API key storage and retrieval
 * API key is encrypted using WordPress options API with additional obfuscation
 */
class ApiKeyManager {
	private const AEAD_CONTEXT = 'apeiron-kit/api-key/v1';

	private string $option_name = 'apeiron_kit_api_key_encrypted';
	private string $salt_option = 'apeiron_kit_api_salt';

	/**
	 * Get encrypted API key
	 *
	 * @return string|null Decrypted API key or null
	 */
	public function get_api_key(): ?string {
		$encrypted = get_option( $this->option_name );

		if ( empty( $encrypted ) ) {
			return null;
		}

		$salt = $this->get_salt();
		if ( Crypto::is_versioned( $encrypted ) ) {
			$decrypted = Crypto::decrypt_authenticated( $encrypted, $this->derive_authenticated_key( $salt ), self::AEAD_CONTEXT );
			return '' === $decrypted ? null : $decrypted;
		}

		$legacy_key = $this->derive_key( $salt );
		$decrypted  = Crypto::decrypt_aes_cbc( $encrypted, $legacy_key );
		if ( '' !== $decrypted ) {
			return $decrypted;
		}

		$legacy = $this->xor_decrypt( $encrypted, $legacy_key );
		if ( strlen( $legacy ) < 8 ) {
			return null;
		}

		return $legacy;
	}

	/**
	 * Set and encrypt API key
	 *
	 * @param string $api_key API key to encrypt and store
	 * @return bool Success
	 */
	public function set_api_key( string $api_key ): bool {
		// Trim whitespace first, then sanitize
		$api_key = trim( $api_key );
		$api_key = sanitize_text_field( $api_key );

		// Trim again after sanitize to be safe
		$api_key = trim( $api_key );

		if ( empty( $api_key ) ) {
			return false;
		}

		// Generate salt if not exists
		$salt = $this->get_salt();
		if ( empty( $salt ) ) {
			$salt = $this->generate_salt();
			update_option( $this->salt_option, $salt, false );
		}

		$key       = $this->derive_authenticated_key( $salt );
		$encrypted = Crypto::encrypt_authenticated( $api_key, $key, self::AEAD_CONTEXT );

		if ( empty( $encrypted ) ) {
			return false;
		}

		// Store encrypted key
		return update_option( $this->option_name, $encrypted, false );
	}

	/**
	 * Delete API key
	 * 
	 * @return bool Success
	 */
	public function delete_api_key(): bool {
		delete_option( $this->salt_option );
		return delete_option( $this->option_name );
	}

	/**
	 * Check if API key is set
	 * 
	 * @return bool True if API key exists
	 */
	public function has_api_key(): bool {
		return ! empty( $this->get_api_key() );
	}

	/**
	 * Get or generate salt
	 * 
	 * @return string Salt
	 */
	private function get_salt(): string {
		$salt = get_option( $this->salt_option );
		
		if ( empty( $salt ) ) {
			$salt = $this->generate_salt();
			update_option( $this->salt_option, $salt, false );
		}
		
		return $salt;
	}

	/**
	 * Generate random salt
	 * 
	 * @return string Salt
	 */
	private function generate_salt(): string {
		// Use WordPress salt + additional randomness
		$wp_salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : wp_generate_password( 32, true, true );
		$random = wp_generate_password( 16, true, true );
		
		return hash( 'sha256', $wp_salt . $random . get_option( 'siteurl' ) );
	}

	/**
	 * Derive encryption key from salt
	 * 
	 * @param string $salt Salt
	 * @return string Derived key
	 */
	private function derive_key( string $salt ): string {
		// Use stable sources for key derivation
		// NOTE: admin_email removed - too volatile, causes key loss on email change
		$sources = [
			$salt,
			defined( 'AUTH_KEY' ) ? AUTH_KEY : '',
			get_option( 'siteurl' ),
		];
		
		$combined = implode( '|', $sources );
		return Crypto::derive_sha256_key( $combined );
	}

	private function derive_authenticated_key( string $salt ): string {
		$key_material = defined( 'AUTH_KEY' ) && '' !== AUTH_KEY ? AUTH_KEY : $salt;
		return Crypto::derive_hkdf_key( $key_material, $salt, self::AEAD_CONTEXT );
	}

	/**
	 * Legacy XOR decrypt (kept for backward compatibility during migration)
	 */

	/**
	 * XOR decrypt
	 * 
	 * @param string $encrypted Encrypted data (base64 encoded)
	 * @param string $key Decryption key
	 * @return string Decrypted data
	 */
	private function xor_decrypt( string $encrypted, string $key ): string {
		return Crypto::xor_decrypt( $encrypted, $key );
	}
}

