<?php

namespace ApeironKit\Support;

use ApeironKit\Core\Crypto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FormOrderSettings {
	public const OPTION_NAME = 'apeiron_form_order_settings';
	private const SECRET_OPTION = 'apeiron_form_order_secret';
	private const CONTEXT = 'apeiron-kit/form-order/v1';

	public static function defaults(): array {
		return [
			'delivery_target' => 'both',
			'success_message' => __( 'Pesanan berhasil diproses.', 'apeiron-kit' ),
			'whatsapp_enabled' => 'no',
			'whatsapp_phone' => '',
			'whatsapp_template' => "Pesanan undangan baru:\n{details}",
			'whatsapp_behavior' => 'new_tab',
			'spreadsheet_enabled' => 'no',
			'webhook_url' => '',
			'sheet_name' => 'Orders',
		];
	}

	public static function get(): array {
		$saved = get_option( self::OPTION_NAME, [] );
		$saved = is_array( $saved ) ? $saved : [];
		$saved['delivery_target'] = self::delivery_target( $saved );
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
			'webhook_url' => esc_url_raw( (string) ( $input['webhook_url'] ?? '' ) ),
			'sheet_name' => sanitize_text_field( (string) ( $input['sheet_name'] ?? '' ) ),
		];
	}

	public static function valid_webhook_url( string $url ): bool {
		$parts = wp_parse_url( $url );
		return is_array( $parts )
			&& ( $parts['scheme'] ?? '' ) === 'https'
			&& ( $parts['host'] ?? '' ) === 'script.google.com'
			&& empty( $parts['port'] )
			&& empty( $parts['user'] ) && empty( $parts['pass'] )
			&& empty( $parts['query'] ) && empty( $parts['fragment'] )
			&& (bool) preg_match( '~^/macros/s/[A-Za-z0-9_-]+/exec$~D', $parts['path'] ?? '' );
	}

	private static function secret_key(): string {
		return Crypto::derive_hkdf_key( wp_salt( 'auth' ), wp_salt( 'secure_auth' ), self::CONTEXT );
	}

	public static function save_secret( string $secret ): bool {
		$encrypted = Crypto::encrypt_authenticated( $secret, self::secret_key(), self::CONTEXT );
		if ( '' === $encrypted ) {
			return false;
		}
		return update_option( self::SECRET_OPTION, $encrypted, false ) || get_option( self::SECRET_OPTION ) === $encrypted;
	}

	public static function secret(): string {
		$encrypted = get_option( self::SECRET_OPTION, '' );
		return is_string( $encrypted ) ? Crypto::decrypt_authenticated( $encrypted, self::secret_key(), self::CONTEXT ) : '';
	}

	public static function has_secret(): bool {
		return '' !== self::secret();
	}

	/** @param array<string,string|string[]> $fields */
	public static function send_to_sheet( array $fields, string $type = 'order', ?string &$failure = null, array $labels = [], ?string &$version = null, string $submission_id = '' ): bool {
		$failure = null;
		$version = null;
		$settings = self::get();
		$secret = self::secret();
		if ( ! self::valid_webhook_url( $settings['webhook_url'] ) || '' === $secret ) {
			$failure = __( 'URL Web App atau Secret Key belum tersimpan dengan benar.', 'apeiron-kit' );
			return false;
		}

		$timeout = 'order' === $type ? 30 : 12;
		$response = wp_safe_remote_post( $settings['webhook_url'], [
			'timeout' => $timeout,
			'redirection' => 0,
			'reject_unsafe_urls' => true,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body' => wp_json_encode( [
				'type' => $type,
				'sheet' => $settings['sheet_name'],
				'secret' => $secret,
				'fields' => $fields,
				'labels' => $labels,
				'request_id' => $submission_id,
			] ),
		] );
		if ( is_wp_error( $response ) ) {
			$failure = __( 'Server WordPress tidak dapat menghubungi Google Apps Script. Periksa koneksi keluar atau SSL hosting.', 'apeiron-kit' );
			return false;
		}
		$status = wp_remote_retrieve_response_code( $response );
		$redirects = 0;
		while ( in_array( $status, [ 302, 303 ], true ) && $redirects < 3 ) {
			$location = wp_remote_retrieve_header( $response, 'location' );
			$parts = is_string( $location ) ? wp_parse_url( $location ) : false;
			if ( is_array( $parts ) && 'accounts.google.com' === ( $parts['host'] ?? '' ) ) {
				$failure = __( 'Google meminta login. Pastikan Web App dijalankan sebagai Me dan dapat diakses Anyone.', 'apeiron-kit' );
				return false;
			}
			if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' )
				|| 'script.googleusercontent.com' !== ( $parts['host'] ?? '' )
				|| '/macros/echo' !== ( $parts['path'] ?? '' )
				|| isset( $parts['port'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ) {
				$failure = __( 'Redirect Web App bukan respons Google Apps Script yang diharapkan. Periksa URL /exec dan akses Anyone.', 'apeiron-kit' );
				return false;
			}
			$response = wp_safe_remote_get( $location, [ 'timeout' => $timeout, 'redirection' => 0, 'reject_unsafe_urls' => true ] );
			if ( is_wp_error( $response ) ) {
				$failure = __( 'Server WordPress tidak dapat mengambil respons Google Apps Script.', 'apeiron-kit' );
				return false;
			}
			$status = wp_remote_retrieve_response_code( $response );
			$redirects++;
		}
		if ( in_array( $status, [ 302, 303 ], true ) ) {
			$failure = __( 'Respons Google terus dialihkan. Periksa akses Web App dan URL /exec.', 'apeiron-kit' );
			return false;
		}
		if ( 200 !== $status ) {
			$failure = 400 === $status
				? __( 'HTTP 400: Google menolak permintaan Web App. Buat deployment Web app baru (Execute as: Me, akses: Anyone), lalu simpan ulang URL /exec di dashboard.', 'apeiron-kit' )
				: sprintf( __( 'Web App merespons HTTP %d. Periksa URL /exec dan akses deployment (Anyone).', 'apeiron-kit' ), $status );
			return false;
		}
		$content = wp_remote_retrieve_body( $response );
		$body = json_decode( $content, true );
		if ( ! is_array( $body ) ) {
			$failure = '' === trim( $content )
				? __( 'Web App mengembalikan respons kosong. Periksa fungsi doPost pada deployment aktif.', 'apeiron-kit' )
				: __( 'Web App mengembalikan halaman atau teks, bukan JSON. Periksa akses Anyone dan versi Code.gs pada deployment aktif.', 'apeiron-kit' );
			return false;
		}
		if ( true !== ( $body['success'] ?? false ) ) {
			$reasons = [
				'secret_missing' => __( 'Script Property APEIRON_FORM_ORDER_SECRET tidak ditemukan atau kosong pada project Web App yang aktif.', 'apeiron-kit' ),
				'secret_mismatch' => __( 'Secret Key Apps Script tidak cocok dengan dashboard Form Order.', 'apeiron-kit' ),
				'legacy_secret_mismatch' => __( 'Code.gs mengembalikan pesan Secret Key dari contoh lama. Contoh lama memakai Script Property APEIRON_SECRET; kode dashboard terbaru memakai APEIRON_FORM_ORDER_SECRET. Cocokkan kode dan nama property, lalu deploy versi terbaru.', 'apeiron-kit' ),
				'sheet_not_found' => __( 'Nama tab Sheet tidak ditemukan. Samakan dengan Nama Sheet di dashboard.', 'apeiron-kit' ),
				'invalid_fields' => __( 'Apps Script menolak data pesanan. Periksa kode Code.gs.', 'apeiron-kit' ),
				'script_error' => __( 'Apps Script gagal memproses permintaan. Periksa ID Spreadsheet dan izin akses.', 'apeiron-kit' ),
				'busy' => __( 'Spreadsheet sedang memproses pesanan lain. Silakan coba lagi.', 'apeiron-kit' ),
			];
			$legacy_messages = [
				'Secret Key tidak cocok' => 'legacy_secret_mismatch',
				'Tab sheet tidak ditemukan' => 'sheet_not_found',
				'Data pesanan tidak valid' => 'invalid_fields',
				'Apps Script gagal memproses permintaan' => 'script_error',
			];
			$legacy_message = is_string( $body['message'] ?? null ) ? $body['message'] : '';
			$reason = is_string( $body['error'] ?? null ) && '' !== $body['error']
				? $body['error']
				: ( $legacy_messages[ $legacy_message ] ?? '' );
			$failure = $reasons[ $reason ] ?? __( 'Web App mengembalikan JSON tanpa success:true. Pastikan deployment memakai versi terbaru dari kode Code.gs di dashboard.', 'apeiron-kit' );
			if ( 'test' === $type && 'sheet_not_found' === $reason && is_array( $body['details'] ?? null ) ) {
				$details = $body['details'];
				$file = is_string( $details['spreadsheet'] ?? null ) ? sanitize_text_field( $details['spreadsheet'] ) : '';
				$names = [];
				foreach ( array_slice( is_array( $details['sheets'] ?? null ) ? $details['sheets'] : [], 0, 10 ) as $name ) {
					if ( is_string( $name ) ) { $names[] = sanitize_text_field( $name ); }
				}
				if ( '' !== $file && $names ) {
					$failure .= ' ' . sprintf( __( 'File yang dibuka Code.gs: %1$s. Tab yang tersedia: %2$s.', 'apeiron-kit' ), $file, implode( ', ', $names ) );
				}
			}
			return false;
		}
		$version = is_string( $body['version'] ?? null ) ? $body['version'] : null;
		return true;
	}
}
