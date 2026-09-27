<?php

namespace ApeironKit\Core;

/**
 * Penyimpanan dan pengambilan API key terenkripsi.
 */
class ApiKeyManager {
	private const AEAD_CONTEXT = 'apeiron-kit/api-key/v1';

	private string $option_name = 'apeiron_kit_api_key_encrypted';
	private string $salt_option = 'apeiron_kit_api_salt';

	/**
	 * Ambil API key.
	 *
	 * @return string|null API key hasil dekripsi atau null.
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
	 * Simpan API key terenkripsi.
	 *
	 * @param string $api_key API key yang disimpan.
	 * @return bool Status penyimpanan.
	 */
	public function set_api_key( string $api_key ): bool {
		$api_key = trim( $api_key );
		$api_key = sanitize_text_field( $api_key );

		$api_key = trim( $api_key );

		if ( empty( $api_key ) ) {
			return false;
		}

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

		return update_option( $this->option_name, $encrypted, false );
	}

	/**
	 * Hapus API key.
	 *
	 * @return bool Status penghapusan.
	 */
	public function delete_api_key(): bool {
		delete_option( $this->salt_option );
		return delete_option( $this->option_name );
	}

	/**
	 * Periksa keberadaan API key.
	 *
	 * @return bool Benar jika API key tersedia.
	 */
	public function has_api_key(): bool {
		return ! empty( $this->get_api_key() );
	}

	/**
	 * Ambil atau buat salt.
	 *
	 * @return string Salt.
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
	 * Buat salt acak.
	 *
	 * @return string Salt.
	 */
	private function generate_salt(): string {
		$wp_salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : wp_generate_password( 32, true, true );
		$random = wp_generate_password( 16, true, true );
		
		return hash( 'sha256', $wp_salt . $random . get_option( 'siteurl' ) );
	}

	/**
	 * Turunkan kunci enkripsi dari salt.
	 *
	 * @param string $salt Salt.
	 * @return string Kunci turunan.
	 */
	private function derive_key( string $salt ): string {
		// Jangan gunakan email admin: perubahan email dapat memutus dekripsi.
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
	 * Dekripsi format XOR lama untuk kompatibilitas.
	 *
	 * @param string $encrypted Data terenkripsi dalam base64.
	 * @param string $key Kunci dekripsi.
	 * @return string Data hasil dekripsi.
	 */
	private function xor_decrypt( string $encrypted, string $key ): string {
		return Crypto::xor_decrypt( $encrypted, $key );
	}
}

