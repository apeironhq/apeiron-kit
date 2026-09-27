<?php

namespace ApeironKit\Core;

/** Kelola validasi lisensi melalui server. */
class LicenseManager {

	private static ?LicenseManager $instance = null;

	/** Cache lisensi selama satu permintaan. */
	private ?array $license_cache = null;

	/** Cache hasil validasi selama satu permintaan. */
	private ?bool $valid_cache = null;

	private string $option_name = 'apeiron_kit_license';
	private string $cache_option = 'apeiron_kit_license_cache';
	private string $persistent_cache_option = 'apeiron_kit_license_persistent';
	private string $api_url = '';
	private bool $api_url_resolved = false;
	private string $product_id = 'apeiron-kit';
	private ApiKeyManager $api_key_manager;

	/** Batas kegagalan pemeriksaan berturut-turut. */
	private const MAX_CHECK_FAILURES = 3;
	private const NONCE_ACTION       = 'apeiron_license_management';
	private const AEAD_CONTEXT       = 'apeiron-kit/license-key/v1';

	/** Masa berlaku cache tetap: tujuh hari. */
	private const PERSISTENT_CACHE_TTL = 604800;

	/** Alamat baku server lisensi. */
	private const DEFAULT_API_URL = 'https://server-apeiron.web.id/api';

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Tunggu filter terdaftar sebelum menentukan URL API.
		$this->api_url = '';
		$this->api_url_resolved = false;
		
		$this->api_key_manager = new ApiKeyManager();
		
		if ( defined( 'APEIRON_KIT_LICENSE_API_KEY' ) && ! empty( APEIRON_KIT_LICENSE_API_KEY ) ) {
			if ( ! $this->api_key_manager->has_api_key() ) {
				$this->api_key_manager->set_api_key( APEIRON_KIT_LICENSE_API_KEY );
			}
		}
	}

	public function register(): void {
		add_filter( 'apeiron_kit_license_api_url', [ $this, 'filter_api_url_from_option' ], 10 );

		// Pemeriksaan pertama dijadwalkan besok agar aktivasi tidak menunggu server.
		if ( ! wp_next_scheduled( 'apeiron_kit_check_license' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'apeiron_kit_check_license' );
		}
		add_action( 'apeiron_kit_check_license', [ $this, 'check_license_status' ] );

		add_action( 'wp_ajax_apeiron_activate_license', [ $this, 'handle_activate' ] );
		add_action( 'wp_ajax_apeiron_deactivate_license', [ $this, 'handle_deactivate' ] );
		add_action( 'wp_ajax_apeiron_check_license', [ $this, 'handle_check' ] );
		add_action( 'wp_ajax_apeiron_save_server_config', [ $this, 'handle_save_server_config' ] );
		add_action( 'wp_ajax_apeiron_test_server_connection', [ $this, 'handle_test_connection' ] );

		add_action( 'admin_notices', [ $this, 'render_admin_notices' ] );
	}

	/**
	 * Pilih URL API tersimpan jika tidak ditetapkan melalui konstanta.
	 *
	 * @param string $default_url URL baku.
	 * @return string URL API yang digunakan.
	 */
	public function filter_api_url_from_option( string $default_url ): string {
		if ( defined( 'APEIRON_KIT_LICENSE_API_URL' ) && ! empty( APEIRON_KIT_LICENSE_API_URL ) ) {
			return $default_url;
		}

		$saved_url = get_option( 'apeiron_kit_license_api_url', '' );
		if ( ! empty( $saved_url ) ) {
			return $this->sanitize_api_url( $saved_url );
		}

		return $default_url;
	}

	/** Validasi URL server lisensi. */
	private function sanitize_api_url( string $url ): string {
		$url = esc_url_raw( untrailingslashit( trim( $url ) ) );

		if ( empty( $url ) || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return '';
		}

		if (
			! $this->is_allowed_license_url( $url )
			|| null !== wp_parse_url( $url, PHP_URL_QUERY )
			|| null !== wp_parse_url( $url, PHP_URL_FRAGMENT )
		) {
			return '';
		}

		return $url;
	}

	/**
	 * Batasi tujuan ke hostname HTTPS yang diizinkan tanpa pencarian DNS.
	 * URL yang gagal validasi tidak pernah dikirim.
	 */
	private function is_allowed_license_url( string $url ): bool {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return false;
		}

		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return false;
		}

		if ( 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) {
			return false;
		}

		if ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) {
			return false;
		}

		// Cocokkan hostname secara utuh, bukan sebagian.
		$host = strtolower( rtrim( (string) $parts['host'], '.' ) );

		return '' !== $host && in_array( $host, $this->get_allowed_api_hosts( $url ), true );
	}

	/**
	 * @return string[]
	 */
	private function get_allowed_api_hosts( string $url ): array {
		$hosts = [ wp_parse_url( self::DEFAULT_API_URL, PHP_URL_HOST ) ];
		if ( defined( 'APEIRON_KIT_LICENSE_API_URL' ) && ! empty( APEIRON_KIT_LICENSE_API_URL ) ) {
			$hosts[] = wp_parse_url( APEIRON_KIT_LICENSE_API_URL, PHP_URL_HOST );
		}

		$hosts = (array) apply_filters( 'apeiron_kit_license_api_allowed_hosts', $hosts, $url );

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $host ): string {
							return is_string( $host ) ? strtolower( rtrim( trim( $host ), '.' ) ) : '';
						},
						$hosts
					)
				)
			)
		);
	}

	/** Prioritas URL: konstanta, opsi tersimpan, lalu alamat baku. */
	private function resolve_api_url(): string {
		if ( $this->api_url_resolved ) {
			return $this->api_url;
		}
		
		$this->api_url_resolved = true;
		
		if ( defined( 'APEIRON_KIT_LICENSE_API_URL' ) && ! empty( APEIRON_KIT_LICENSE_API_URL ) ) {
			$url = APEIRON_KIT_LICENSE_API_URL;
		} else {
			$saved = get_option( 'apeiron_kit_license_api_url', '' );
			$url = ! empty( $saved ) ? $saved : self::DEFAULT_API_URL;
		}
		
		$this->api_url = $this->sanitize_api_url(
			apply_filters( 'apeiron_kit_license_api_url', $url )
		);
		
		if ( empty( $this->api_url ) ) {
			$this->api_url = $this->sanitize_api_url( self::DEFAULT_API_URL );
		}
		
		return $this->api_url;
	}

	public function get_api_url(): string {
		return $this->resolve_api_url();
	}

	public function has_api_key(): bool {
		return $this->api_key_manager->has_api_key();
	}

	public function has_server_config(): bool {
		return ! empty( $this->resolve_api_url() ) && $this->api_key_manager->has_api_key();
	}

	public function get_license(): array {
		if ( null !== $this->license_cache ) {
			return $this->license_cache;
		}

		$default = [
			'key'           => '',
			'status'        => 'inactive',
			'expires'       => '',
			'activations'   => 0,
			'activation_limit' => 0,
			'site_url'      => '',
			'last_check'    => 0,
		];

		$license = get_option( $this->option_name, [] );
		$license = wp_parse_args( $license, $default );
		
		if ( ! empty( $license['key'] ) && ! empty( $license['key_encrypted'] ) ) {
			$decrypted_key = $this->decrypt_license_key( $license['key'] );
			
			if ( empty( $decrypted_key ) && ! empty( $license['key'] ) ) {
				if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
					\ApeironKit\Support\ErrorLogger::error( 'Failed to decrypt license key. Salt, AUTH_KEY, or siteurl may have changed.' );
				}
				$license['key'] = '';
			} else {
				$license['key'] = $decrypted_key;
			}
		}
		
return $this->license_cache = $license;
	}

	private function save_license( array $license ): void {
		if ( ! empty( $license['key'] ) ) {
			$encrypted = $this->encrypt_license_key( $license['key'] );
			if ( '' !== $encrypted ) {
				$license['key']           = $encrypted;
				$license['key_encrypted'] = true;
			} else {
				return;
			}
		}

		update_option( $this->option_name, $license );

		$this->invalidate_caches();
	}

	/**
	 * Hapus cache permintaan setelah data lisensi berubah.
	 */
	private function invalidate_caches(): void {
		$this->license_cache = null;
		$this->valid_cache   = null;
		delete_transient( 'apeiron_kit_license_valid' );
	}

	public function is_active(): bool {
		$license = $this->get_license();
		return 'active' === $license['status'];
	}

	/**
	 * Periksa masa berlaku lisensi dengan cache.
	 */
	public function is_valid(): bool {
		if ( null !== $this->valid_cache ) {
			return $this->valid_cache;
		}

		$cached = get_transient( 'apeiron_kit_license_valid' );
		if ( $cached !== false ) {
			return $this->valid_cache = (bool) $cached;
		}

		if ( ! $this->is_active() ) {
			// Periksa cache tetap sebelum menyatakan lisensi tidak aktif.
			$persistent = $this->get_persistent_cache();
			if ( $persistent !== null && ( $persistent['status'] ?? '' ) === 'active' ) {
				set_transient( 'apeiron_kit_license_valid', 1, 12 * HOUR_IN_SECONDS );
				return $this->valid_cache = true;
			}

			set_transient( 'apeiron_kit_license_valid', 0, 12 * HOUR_IN_SECONDS );
			return $this->valid_cache = false;
		}

		$license = $this->get_license();

		// Gunakan waktu verifikasi terakhir; gangguan jaringan tidak memperpanjang lisensi.
		$verified_at = (int) $license['last_check'];
		if ( $verified_at <= 0 ) {
			$persistent  = $this->get_persistent_cache();
			$verified_at = null !== $persistent ? (int) ( $persistent['cached_at'] ?? 0 ) : 0;
		}

		if ( $verified_at <= 0 || ( time() - $verified_at ) > self::PERSISTENT_CACHE_TTL ) {
			set_transient( 'apeiron_kit_license_valid', 0, 12 * HOUR_IN_SECONDS );
			return $this->valid_cache = false;
		}

		if ( empty( $license['expires'] ) ) {
			set_transient( 'apeiron_kit_license_valid', 1, 12 * HOUR_IN_SECONDS );
			return $this->valid_cache = true;
		}

		$expires = strtotime( $license['expires'] );
		// Tanggal kedaluwarsa yang tidak valid tidak boleh meloloskan lisensi.
		$is_valid = false !== $expires && $expires > time();

		set_transient( 'apeiron_kit_license_valid', $is_valid ? 1 : 0, 12 * HOUR_IN_SECONDS );

		return $this->valid_cache = $is_valid;
	}

	public function activate( string $license_key ): array {
		$license_key = sanitize_text_field( trim( $license_key ) );

		if ( empty( $license_key ) ) {
			return [
				'success' => false,
				'message' => __( 'License key tidak boleh kosong.', 'apeiron-kit' ),
			];
		}

		$response = $this->api_request( 'activate', [
			'license_key' => $license_key,
			'site_url'    => home_url(),
			'product_id'  => $this->product_id,
		] );

		if ( $response['success'] ) {
			$data = $response['data'];
			$license_data = [
				'key'             => $license_key,
				'status'          => $data['status'] ?? 'active',
				'expires'         => $data['expires'] ?? '',
				'activations'     => $data['activations'] ?? 0,
				'activation_limit' => $data['activation_limit'] ?? 0,
				'site_url'        => home_url(),
				'last_check'      => time(),
			];
			$this->save_license( $license_data );
			
			// Pertahankan status lisensi saat server tidak tersedia.
			$this->save_persistent_cache( $license_data );
			
			delete_option( 'apeiron_kit_check_fail_count' );
			
			$this->invalidate_caches();
		}

		return $response;
	}

	public function deactivate(): array {
		$license = $this->get_license();

		if ( empty( $license['key'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Tidak ada license key yang terdaftar.', 'apeiron-kit' ),
			];
		}

		// Lepas aktivasi berdasarkan URL yang tersimpan, bukan URL situs yang mungkin berubah.
		$activated_site = ! empty( $license['site_url'] ) ? $license['site_url'] : home_url();

		$response = $this->api_request( 'deactivate', [
			'license_key' => $license['key'],
			'site_url'    => $activated_site,
			'product_id'  => $this->product_id,
		] );

		if ( $response['success'] ) {
			delete_option( $this->option_name );
			$this->invalidate_caches();
			$this->clear_persistent_cache();
			delete_option( 'apeiron_kit_check_fail_count' );
		}

		return $response;
	}

	public function check_license_status(): array {
		$license = $this->get_license();

		if ( empty( $license['key'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Tidak ada license key yang terdaftar.', 'apeiron-kit' ),
			];
		}

		$fresh = false;
		$response = $this->api_request( 'check', [
			'license_key' => $license['key'],
			'site_url'    => home_url(),
			'product_id'  => $this->product_id,
		], $fresh );

		// Respons cache tidak memperbarui waktu verifikasi.
		if ( $response['success'] && ! $fresh ) {
			return $response;
		}

		if ( $response['success'] ) {
			$data = $response['data'];
			$license_data = [
				'key'             => $license['key'],
				'status'          => $data['status'] ?? $license['status'],
				'expires'         => $data['expires'] ?? $license['expires'],
				'activations'     => $data['activations'] ?? $license['activations'],
				'activation_limit' => $data['activation_limit'] ?? $license['activation_limit'],
				'site_url'        => home_url(),
				'last_check'      => time(),
			];
			$this->save_license( $license_data );
			
			$this->save_persistent_cache( $license_data );
			
			delete_option( 'apeiron_kit_check_fail_count' );
			
			$this->invalidate_caches();
		} else {
			// Gangguan sementara tidak langsung menonaktifkan lisensi.
			$fail_count = (int) get_option( 'apeiron_kit_check_fail_count', 0 );
			$fail_count++;
			update_option( 'apeiron_kit_check_fail_count', $fail_count, false );
			
			if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
				\ApeironKit\Support\ErrorLogger::info( 'License check failed', [
					'fail_count' => $fail_count,
					'max_failures' => self::MAX_CHECK_FAILURES,
					'message' => $response['message'] ?? 'Unknown error',
				] );
			}
			
			// Hanya keputusan server yang boleh mengubah status aktivasi.
			if ( ! $this->is_authoritative_license_failure( $response ) ) {
				return $response;
			}

			$cached = $this->get_persistent_cache();
			if ( $cached !== null && ( $cached['status'] ?? '' ) === 'active' ) {
				if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
					\ApeironKit\Support\ErrorLogger::info(
						'License kept active from persistent cache (fail #' . $fail_count . ')'
					);
				}
				
				// Nonaktifkan hanya setelah batas kegagalan dan masa cache terlampaui.
				if ( $fail_count >= self::MAX_CHECK_FAILURES ) {
					$cache_age = time() - ( $cached['cached_at'] ?? 0 );
					if ( $cache_age > self::PERSISTENT_CACHE_TTL ) {
						$license['status'] = 'inactive';
						$license['last_check'] = time();
						$this->save_license( $license );
						delete_option( 'apeiron_kit_check_fail_count' );

						if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
							\ApeironKit\Support\ErrorLogger::error(
								'License deactivated after ' . self::MAX_CHECK_FAILURES . ' consecutive failures and cache expired'
							);
						}
					}
				}
			} else {
				if ( $fail_count >= self::MAX_CHECK_FAILURES && $this->is_active() ) {
					$license['status'] = 'inactive';
					$license['last_check'] = time();
					$this->save_license( $license );
					delete_option( 'apeiron_kit_check_fail_count' );
				}
			}
		}

		return $response;
	}

	/**
	 * Bedakan keputusan lisensi dari gangguan jaringan atau konfigurasi.
	 * Hanya kode resmi server yang dapat menonaktifkan aktivasi.
	 */
	private function is_authoritative_license_failure( array $response ): bool {
		return in_array(
			(string) ( $response['error_code'] ?? '' ),
			[ 'LICENSE_NOT_FOUND', 'LICENSE_EXPIRED', 'LICENSE_SUSPENDED', 'LICENSE_INACTIVE' ],
			true
		);
	}

	/**
	 * Pisahkan kegagalan transport dari penolakan lisensi oleh server.
	 */
	private function classify_transport_error( \WP_Error $error ): string {
		$message = strtolower( $error->get_error_message() );

		if ( false !== strpos( $message, 'valid url was not provided' ) || false !== strpos( $message, 'url yang sah' ) ) {
			return 'APEIRON_DNS_FAILURE';
		}
		if ( false !== strpos( $message, 'could not resolve host' ) || false !== strpos( $message, 'name or service not known' ) ) {
			return 'APEIRON_DNS_FAILURE';
		}
		if ( false !== strpos( $message, 'ssl' ) || false !== strpos( $message, 'certificate' ) ) {
			return 'APEIRON_SSL_ERROR';
		}
		if ( false !== strpos( $message, 'connection refused' ) ) {
			return 'APEIRON_CONNECTION_REFUSED';
		}
		if ( false !== strpos( $message, 'connection reset' ) || false !== strpos( $message, 'recv failure' ) ) {
			return 'APEIRON_CONNECTION_RESET';
		}
		if ( false !== strpos( $message, 'connect' ) && false !== strpos( $message, 'timed out' ) ) {
			return 'APEIRON_CONNECT_TIMEOUT';
		}
		if ( false !== strpos( $message, 'timed out' ) || false !== strpos( $message, 'timeout' ) ) {
			return 'APEIRON_REQUEST_TIMEOUT';
		}

		return 'APEIRON_SERVER_UNREACHABLE';
	}

	/** Gangguan transport yang boleh dicoba ulang sekali. */
	private function is_retryable_transport_error( string $class ): bool {
		return in_array(
			$class,
			[ 'APEIRON_CONNECT_TIMEOUT', 'APEIRON_REQUEST_TIMEOUT', 'APEIRON_CONNECTION_RESET', 'APEIRON_SERVER_UNREACHABLE' ],
			true
		);
	}

	/**
	 * Coba ulang sekali melalui IPv4 untuk gangguan transport sementara.
	 * Filter cURL dibatasi pada URL lisensi dan segera dilepas.
	 */
	private function send_with_one_retry( string $url, array $args ) {
		// Validasi hostname sebelum POST tanpa pemeriksaan DNS IPv4 WordPress.
		if ( ! $this->is_allowed_license_url( $url ) ) {
			return new \WP_Error( 'apeiron_license_url_not_allowed', __( 'License server URL tidak diizinkan.', 'apeiron-kit' ) );
		}

		$response = wp_remote_post( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $this->is_retryable_transport_error( $this->classify_transport_error( $response ) ) ) {
			return $response;
		}

		$force_ipv4 = static function ( $handle, $parsed_args, $request_url ) use ( $url ) {
			if ( $request_url === $url && defined( 'CURLOPT_IPRESOLVE' ) && defined( 'CURL_IPRESOLVE_V4' ) ) {
				curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			}
		};

		add_action( 'http_api_curl', $force_ipv4, 10, 3 );
		$retry = wp_remote_post( $url, $args );
		remove_action( 'http_api_curl', $force_ipv4, 10 );

		return $retry;
	}

	private function api_request( string $action, array $data, bool &$fresh = false ): array {
		$fresh = false;
		$api_url = $this->resolve_api_url();

		if ( empty( $api_url ) ) {
			return $this->get_cached_response( $action, [
				'success' => false,
				'message' => __( 'License server URL tidak dikonfigurasi.', 'apeiron-kit' ),
			] );
		}

		if ( 'https' !== wp_parse_url( $api_url, PHP_URL_SCHEME ) ) {
			return [
				'success' => false,
				'message' => __( 'License server harus menggunakan HTTPS untuk alasan keamanan.', 'apeiron-kit' ),
			];
		}

		$api_key = $this->api_key_manager->get_api_key();
		if ( empty( $api_key ) ) {
			return $this->get_cached_response( $action, [
				'success' => false,
				'message' => __( 'API Key tidak dikonfigurasi. Silakan set API Key di settings.', 'apeiron-kit' ),
			] );
		}

		$url = trailingslashit( $api_url ) . $action . '.php';

		$args = [
			'timeout'     => 15,
			'redirection' => 0,
			'body'        => wp_json_encode( $data ),
			'headers'     => [
				'Content-Type'  => 'application/json',
				'X-API-Key'    => $api_key,
				'User-Agent'   => 'ApeironKit/' . APEIRON_KIT_VERSION . '; ' . home_url(),
			],
			'sslverify' => true,
		];

		// Catat metadata permintaan tanpa data sensitif.
		if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
			\ApeironKit\Support\ErrorLogger::info( 'License API request', [
				'url'       => $url,
				'action'    => $action,
			] );
		}

		$response = $this->send_with_one_retry( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_code = $this->classify_transport_error( $response );
			
			if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
				\ApeironKit\Support\ErrorLogger::error( 
					'License server connection error',
					[
						'url' => $url,
						'action' => $action,
						'error_code' => $error_code,
						'error_message' => $error_message,
					]
				);
			}
			
			if ( $action === 'check' ) {
				$cached = $this->get_cached_response( $action );
				if ( $cached !== null ) {
					return [
						'success' => true,
						'data'    => $cached['data'],
						'message' => __( 'License status dari cache (server tidak dapat dihubungi).', 'apeiron-kit' ),
						'cached'  => true,
					];
				}
			}
			
			$user_message = __( 'Tidak dapat terhubung ke server lisensi. Silakan coba kembali beberapa saat lagi.', 'apeiron-kit' )
				. ' ' . sprintf( __( 'Kode: %s', 'apeiron-kit' ), $error_code );

			if ( 'APEIRON_SSL_ERROR' === $error_code ) {
				$user_message .= ' ' . __( 'Sertifikat SSL hosting tidak dapat memverifikasi server lisensi. Hubungi penyedia hosting Anda.', 'apeiron-kit' );
			} elseif ( 'APEIRON_DNS_FAILURE' === $error_code ) {
				$user_message .= ' ' . __( 'Hosting tidak dapat menemukan alamat server lisensi. Hubungi penyedia hosting Anda.', 'apeiron-kit' );
			}
			
			return [
				'success' => false,
				'message' => $user_message,
				'error_code' => $error_code,
			];
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 ) {
			$error_details = [
				'code' => $code,
				'body' => $body,
				'url' => $url,
				'action' => $action,
			];
			
			$error_message = '';
			$error_code = '';
			if ( ! empty( $body ) ) {
				$error_data = json_decode( $body, true );
				if ( is_array( $error_data ) && isset( $error_data['message'] ) ) {
					$error_message = $error_data['message'];
				}
				if ( is_array( $error_data ) && isset( $error_data['error_code'] ) ) {
					$error_code = $error_data['error_code'];
				}
			}
			
			if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
				\ApeironKit\Support\ErrorLogger::error( 
					'License server returned error',
					$error_details
				);
			}
			
			if ( $action === 'check' ) {
				$cached = $this->get_cached_response( $action );
				if ( $cached !== null ) {
					return [
						'success' => true,
						'data'    => $cached['data'],
						'message' => __( 'License status dari cache (server error).', 'apeiron-kit' ),
						'cached'  => true,
					];
				}
			}
			
			$user_message = sprintf( __( 'License server mengembalikan error: %d', 'apeiron-kit' ), $code );
			
			if ( ! empty( $error_message ) ) {
				$user_message .= '. ' . esc_html( $error_message );
			}
			
			if ( $code === 500 ) {
				$user_message .= ' ' . __( 'Kemungkinan masalah: (1) Server license sedang maintenance, (2) Database error, (3) Konfigurasi server tidak benar. Silakan cek log server atau hubungi administrator.', 'apeiron-kit' );
			}
			
			return [
				'success' => false,
				'message' => $user_message,
				'error_code' => $error_code ?: 'HTTP_' . $code,
				'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG ? $error_details : null,
			];
		}

		$response_data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return [
				'success' => false,
				'message' => __( 'Invalid response dari license server.', 'apeiron-kit' ),
			];
		}

		if ( isset( $response_data['success'] ) && $response_data['success'] ) {
			$fresh = true;
			$this->cache_response( $action, $response_data['data'] ?? [] );
			
			return [
				'success' => true,
				'data'    => $response_data['data'] ?? [],
				'message' => $response_data['message'] ?? __( 'License berhasil divalidasi.', 'apeiron-kit' ),
			];
		}

		$error_code = $response_data['error_code'] ?? '';
		$error_message = $response_data['message'] ?? __( 'License validation gagal.', 'apeiron-kit' );

		if ( $error_code === 'INVALID_API_KEY' || strpos( $error_message, 'API key' ) !== false ) {
			$error_message = __( 'API Key tidak valid. Silakan cek kembali API Key yang Anda masukkan atau hubungi administrator.', 'apeiron-kit' );
		} elseif ( $error_code === 'SINGLE_DOMAIN_VIOLATION' ) {
			$existing_domain = $response_data['existing_domain'] ?? '';
			$error_message = sprintf(
				__( 'License ini hanya dapat diaktifkan di satu domain. Domain yang sudah terdaftar: %s', 'apeiron-kit' ),
				$existing_domain
			);
		} elseif ( $error_code === 'MAX_DOMAINS_REACHED' ) {
			$error_message = __( 'Batas maksimal domain telah tercapai untuk license ini.', 'apeiron-kit' );
		} elseif ( $error_code === 'DOMAIN_NOT_ALLOWED' ) {
			$error_message = __( 'Domain tidak diizinkan untuk license ini. Silakan hubungi administrator.', 'apeiron-kit' );
		} elseif ( $error_code === 'ORIGIN_MISMATCH' ) {
			$error_message = __( 'Request origin tidak sesuai dengan site URL. Pastikan request dikirim dari domain yang benar.', 'apeiron-kit' );
		} elseif ( $error_code === 'ACTIVATION_LIMIT_REACHED' ) {
			$error_message = __( 'Batas aktivasi untuk license ini telah tercapai. Silakan hubungi administrator untuk reset.', 'apeiron-kit' );
		} elseif ( $error_code === 'LICENSE_INACTIVE' ) {
			$error_message = __( 'License telah dinonaktifkan. Silakan hubungi administrator untuk mengaktifkan kembali.', 'apeiron-kit' );
		} elseif ( $error_code === 'LICENSE_SUSPENDED' ) {
			$error_message = __( 'License telah di-suspend. Silakan hubungi administrator.', 'apeiron-kit' );
		} elseif ( $error_code === 'LICENSE_EXPIRED' ) {
			$error_message = __( 'License telah kedaluwarsa. Silakan hubungi administrator untuk memperpanjang.', 'apeiron-kit' );
		}

		return [
			'success' => false,
			'message' => $error_message,
			'error_code' => $error_code,
		];
	}

	/**
	 * Simpan respons API untuk penggunaan saat offline.
	 *
	 * @param string $action Nama aksi.
	 * @param array $data Data respons.
	 * @return void
	 */
	private function cache_response( string $action, array $data ): void {
		$cache = get_option( $this->cache_option, [] );
		$cache[ $action ] = [
			'data'      => $data,
			'timestamp' => time(),
		];
		update_option( $this->cache_option, $cache, false );
	}

	/**
	 * Ambil respons API dari cache.
	 *
	 * @param string $action Nama aksi.
	 * @param array|null $fallback Respons cadangan saat cache kedaluwarsa.
	 * @return array|null Data cache atau null.
	 */
	private function get_cached_response( string $action, ?array $fallback = null ): ?array {
		$cache = get_option( $this->cache_option, [] );
		
		if ( ! isset( $cache[ $action ] ) ) {
			return $fallback;
		}
		
		$cached = $cache[ $action ];
		$age = time() - ( $cached['timestamp'] ?? 0 );
		
		if ( $age > 86400 ) {
			return $fallback;
		}
		
		$data = $cached['data'] ?? $fallback;
		if ( 'check' === $action && is_array( $data ) ) {
			// Terima format respons tersimpan maupun data mentah.
			if ( isset( $data['success'] ) ) {
				if ( ! $data['success'] ) {
					return $fallback;
				}
				$data = $data['data'] ?? [];
			}
			return [
				'success' => true,
				'data'    => $data,
				'message' => __( 'License status dari cache (server tidak dapat dihubungi).', 'apeiron-kit' ),
				'cached'  => true,
			];
		}

		return $data;
	}

	/**
	 * Simpan cache tetap untuk gangguan server sementara.
	 *
	 * @param array $license_data Data lisensi untuk cache.
	 * @return void
	 */
	private function save_persistent_cache( array $license_data ): void {
		$cache_data = $license_data;

		// Jangan simpan kunci lisensi hasil dekripsi di cache.
		unset( $cache_data['key'] );

		$cache_data['cached_at'] = time();
		$cache_data['cached_site_url'] = home_url();

		update_option( $this->persistent_cache_option, $cache_data, false );

		if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
			\ApeironKit\Support\ErrorLogger::info( 'Persistent license cache saved', [
				'status' => $cache_data['status'] ?? 'unknown',
				'cached_at' => date( 'Y-m-d H:i:s', $cache_data['cached_at'] ),
			] );
		}
	}

	/**
	 * Ambil cache tetap yang belum kedaluwarsa untuk situs ini.
	 *
	 * @return array|null Data cache yang valid atau null.
	 */
	private function get_persistent_cache(): ?array {
		$cached = get_option( $this->persistent_cache_option, null );

		if ( empty( $cached ) || ! is_array( $cached ) ) {
			return null;
		}

		// Bandingkan URL yang dinormalisasi agar perubahan skema tidak membuang cache.
		$cached_site = $cached['cached_site_url'] ?? '';
		if ( ! empty( $cached_site ) && $this->normalize_site_url( $cached_site ) !== $this->normalize_site_url( home_url() ) ) {
			delete_option( $this->persistent_cache_option );
			return null;
		}

		$cache_age = time() - ( $cached['cached_at'] ?? 0 );
		if ( $cache_age > self::PERSISTENT_CACHE_TTL ) {
			return null;
		}

		// Cache tetap hanya mengembalikan metadata, bukan kunci hasil dekripsi.
		unset( $cached['key'] );

		return $cached;
	}

	/**
	 * Normalisasi URL hanya untuk perbandingan lokal, bukan untuk permintaan server.
	 */
	private function normalize_site_url( string $url ): string {
		$url = strtolower( trim( $url ) );
		$url = (string) preg_replace( '#^https?://#', '', $url );

		return untrailingslashit( $url );
	}

	/**
	 * Hilangkan kunci lisensi sebelum data dikirim ke browser.
	 */
	private function get_license_for_response(): array {
		$license = $this->get_license();
		unset( $license['key'], $license['key_encrypted'] );

		return $license;
	}

	private function clear_persistent_cache(): void {
		delete_option( $this->persistent_cache_option );
	}

	/**
	 * Enkripsi kunci lisensi dengan format berversi.
	 *
	 * @param string $key Kunci lisensi yang dienkripsi.
	 * @return string Kunci lisensi terenkripsi.
	 */
	private function encrypt_license_key( string $key ): string {
		$salt = get_option( 'apeiron_kit_license_salt' );
		if ( empty( $salt ) ) {
			$salt = wp_generate_password( 32, true, true );
			update_option( 'apeiron_kit_license_salt', $salt, false );
		}
		
		$encryption_key = $this->derive_authenticated_license_key( $salt );
		$encrypted      = Crypto::encrypt_authenticated( $key, $encryption_key, self::AEAD_CONTEXT );
		if ( '' === $encrypted ) {
			if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
				\ApeironKit\Support\ErrorLogger::error( 'License key encryption failed. Check OpenSSL extension.' );
			}
			return '';
		}
		
		return $encrypted;
	}

	/**
	 * Dekripsi kunci dengan fallback format lama.
	 *
	 * @param string $encrypted Kunci lisensi terenkripsi.
	 * @return string Kunci lisensi hasil dekripsi.
	 */
	private function decrypt_license_key( string $encrypted ): string {
		$salt = get_option( 'apeiron_kit_license_salt' );
		if ( Crypto::is_versioned( $encrypted ) ) {
			return Crypto::decrypt_authenticated( $encrypted, $this->derive_authenticated_license_key( $salt ), self::AEAD_CONTEXT );
		}

		$wp_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_generate_password( 32, true, true );
		$site_url = get_option( 'siteurl' );
		
		$encryption_key = Crypto::derive_pbkdf2_key( $salt . $wp_key . $site_url, 'apeiron_kit_salt' );
		$decrypted      = Crypto::decrypt_aes_cbc( $encrypted, $encryption_key );
		if ( '' === $decrypted ) {
			return $this->decrypt_license_key_xor( $encrypted );
		}
		
		return $decrypted;
	}

	private function derive_authenticated_license_key( string $salt ): string {
		$key_material = defined( 'AUTH_KEY' ) && '' !== AUTH_KEY ? AUTH_KEY : $salt;
		return Crypto::derive_hkdf_key( $key_material, $salt, self::AEAD_CONTEXT );
	}
	
	/**
	 * Dekripsi XOR lama untuk kompatibilitas.
	 *
	 * @param string $encrypted Kunci lisensi terenkripsi.
	 * @return string Kunci lisensi hasil dekripsi.
	 */
	private function decrypt_license_key_xor( string $encrypted ): string {
		$salt = get_option( 'apeiron_kit_license_salt' );
		$wp_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_generate_password( 32, true, true );
		$site_url = get_option( 'siteurl' );
		
		$encryption_key = Crypto::derive_sha256_key( $salt . $wp_key . $site_url );

		return Crypto::xor_decrypt( $encrypted, $encryption_key );
	}

	public function handle_activate(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		$result = $this->activate( $license_key );

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message' => $result['message'],
				'license' => $this->get_license_for_response(),
			] );
		} else {
			wp_send_json_error( [ 'message' => $result['message'] ] );
		}
	}

	public function handle_deactivate(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$result = $this->deactivate();

		if ( $result['success'] ) {
			wp_send_json_success( [ 'message' => $result['message'] ] );
		} else {
			wp_send_json_error( [ 'message' => $result['message'] ] );
		}
	}

	public function handle_check(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$result = $this->check_license_status();

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message' => $result['message'],
				'license' => $this->get_license_for_response(),
			] );
		} else {
			wp_send_json_error( [ 'message' => $result['message'] ] );
		}
	}

	public function handle_save_server_config(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['api_key'] ) ) ) : '';

		// Prioritas URL: konstanta, POST, opsi tersimpan, lalu alamat baku.
		if ( defined( 'APEIRON_KIT_LICENSE_API_URL' ) && ! empty( APEIRON_KIT_LICENSE_API_URL ) ) {
			$api_url = APEIRON_KIT_LICENSE_API_URL;
		} else {
			$api_url = isset( $_POST['api_url'] ) ? (string) wp_unslash( $_POST['api_url'] ) : '';
			if ( empty( $api_url ) ) {
				$api_url = get_option( 'apeiron_kit_license_api_url', self::DEFAULT_API_URL );
			}
		}

		$api_url = $this->sanitize_api_url( $api_url );

		if ( empty( $api_url ) ) {
			wp_send_json_error( [ 'message' => __( 'License Server URL tidak valid atau tidak menggunakan HTTPS.', 'apeiron-kit' ) ] );
			return;
		}

		update_option( 'apeiron_kit_license_api_url', $api_url, false );

		$this->api_url = $api_url;
		$this->api_url_resolved = true;

		if ( ! empty( $api_key ) ) {
			if ( ! $this->api_key_manager->set_api_key( $api_key ) ) {
				wp_send_json_error( [ 'message' => __( 'Gagal menyimpan API Key.', 'apeiron-kit' ) ] );
				return;
			}
		}

		wp_send_json_success( [
			'message' => __( 'Konfigurasi server berhasil disimpan!', 'apeiron-kit' ),
		] );
	}

	public function handle_test_connection(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$api_url = $this->get_api_url();

		$api_key = $this->api_key_manager->get_api_key();

		if ( empty( $api_url ) ) {
			wp_send_json_error( [
				'message' => __( 'License Server URL belum dikonfigurasi.', 'apeiron-kit' ),
				'error_code' => 'NO_API_URL'
			] );
			return;
		}

		if ( empty( $api_key ) ) {
			wp_send_json_error( [
				'message' => __( 'API Key belum dikonfigurasi.', 'apeiron-kit' ),
				'error_code' => 'NO_API_KEY'
			] );
			return;
		}

		$url = trailingslashit( $api_url ) . 'health.php';

		$args = [
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => [
				'Content-Type' => 'application/json',
				'User-Agent'   => 'ApeironKit/' . APEIRON_KIT_VERSION . '; ' . home_url(),
			],
			'sslverify' => true,
		];

		// Endpoint health memakai batasan hostname yang sama.
		if ( ! $this->is_allowed_license_url( $url ) ) {
			$response = new \WP_Error( 'apeiron_license_url_not_allowed', __( 'License server URL tidak diizinkan.', 'apeiron-kit' ) );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_code = $response->get_error_code();
			
			wp_send_json_error( [
				'message'    => sprintf( __( 'Koneksi gagal: %s', 'apeiron-kit' ), $error_message ),
				'error_code' => $error_code,
			] );
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code >= 200 && $code < 300 ) {
			wp_send_json_success( [
				'message' => __( 'Koneksi ke license server berhasil!', 'apeiron-kit' ),
			] );
		} else {
			$error_message = __( 'Koneksi gagal.', 'apeiron-kit' );
			$error_code = 'HTTP_' . $code;
			
			$response_data = json_decode( $body, true );
			if ( is_array( $response_data ) && isset( $response_data['message'] ) ) {
				$error_message = $response_data['message'];
			}
			if ( is_array( $response_data ) && isset( $response_data['error_code'] ) ) {
				$error_code = $response_data['error_code'];
			}
			
			wp_send_json_error( [
				'message'    => $error_message,
				'error_code' => $error_code,
			] );
		}
	}

	public function get_status_display(): array {
		$license = $this->get_license();
		$status = $license['status'];
		$is_valid = $this->is_valid();

		$status_info = [
			'active'   => [
				'label' => __( 'Aktif', 'apeiron-kit' ),
				'color' => '#46b450',
				'icon'  => 'yes-alt',
			],
			'inactive' => [
				'label' => __( 'Tidak Aktif', 'apeiron-kit' ),
				'color' => '#dc3232',
				'icon'  => 'dismiss',
			],
			'expired'  => [
				'label' => __( 'Kedaluwarsa', 'apeiron-kit' ),
				'color' => '#f0b849',
				'icon'  => 'warning',
			],
		];

		if ( ! $is_valid && ! empty( $license['expires'] ) ) {
			$expires = strtotime( $license['expires'] );
			if ( $expires && $expires < time() ) {
				$status = 'expired';
			}
		}

		return [
			'status'      => $status,
			'is_valid'    => $is_valid,
			'info'        => $status_info[ $status ] ?? $status_info['inactive'],
			'expires'     => $license['expires'],
			'activations' => $license['activations'],
			'limit'       => $license['activation_limit'],
			'last_check'  => $license['last_check'],
		];
	}

	public function render_admin_notices(): void {
		if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_apeiron_screen = $screen && 'toplevel_page_apeiron-kit' === $screen->id;

		if ( $is_apeiron_screen ) {
			return;
		}

		if ( ! $this->has_server_config() ) {
			$link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=apeiron-kit&tab=license' ) ),
				esc_html__( 'Konfigurasi sekarang', 'apeiron-kit' )
			);

			printf(
				'<div class="notice notice-warning"><p><strong>%s</strong> %s %s</p></div>',
				esc_html__( 'Apeiron Kit:', 'apeiron-kit' ),
				esc_html__( 'License Server URL atau API Key belum dikonfigurasi.', 'apeiron-kit' ),
				$link
			);

			return;
		}

		$status = $this->get_status_display();
		if ( $status['is_valid'] ) {
			return;
		}
	}
}
