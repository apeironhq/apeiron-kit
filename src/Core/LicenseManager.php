<?php

namespace ApeironKit\Core;

/**
 * License Manager untuk handle remote license validation
 */
class LicenseManager {

	private static ?LicenseManager $instance = null;

	/** In-memory license data cache (avoids repeated PBKDF2 decrypts per request). */
	private ?array $license_cache = null;

	/** In-memory is_valid result cache (avoids repeated transient/option reads). */
	private ?bool $valid_cache = null;

	private string $option_name = 'apeiron_kit_license';
	private string $cache_option = 'apeiron_kit_license_cache';
	private string $persistent_cache_option = 'apeiron_kit_license_persistent';
	private string $api_url = '';
	private bool $api_url_resolved = false;
	private string $product_id = 'apeiron-kit';
	private ApiKeyManager $api_key_manager;

	/**
	 * Max consecutive check failures before deactivating license
	 */
	private const MAX_CHECK_FAILURES = 3;
	private const NONCE_ACTION       = 'apeiron_license_management';
	private const AEAD_CONTEXT       = 'apeiron-kit/license-key/v1';

	/**
	 * Persistent cache duration in seconds (7 days)
	 */
	private const PERSISTENT_CACHE_TTL = 604800;

	/**
	 * Default license server URL (hardcoded for security)
	 */
	private const DEFAULT_API_URL = 'https://server-apeiron.web.id/api';

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Don't resolve API URL here - wait until register() or first use
		// This avoids race condition where filter is not yet registered
		$this->api_url = '';
		$this->api_url_resolved = false;
		
		$this->api_key_manager = new ApiKeyManager();
		
		// Auto-set API Key dari constant jika ada dan belum ada saved key
		if ( defined( 'APEIRON_KIT_LICENSE_API_KEY' ) && ! empty( APEIRON_KIT_LICENSE_API_KEY ) ) {
			if ( ! $this->api_key_manager->has_api_key() ) {
				$this->api_key_manager->set_api_key( APEIRON_KIT_LICENSE_API_KEY );
			}
		}
	}

	/**
	 * Register license management hooks
	 */
	public function register(): void {
		// Register filter to allow saved API URL to override default/constant
		add_filter( 'apeiron_kit_license_api_url', [ $this, 'filter_api_url_from_option' ], 10 );

		// Schedule periodic license check — defer the first run by one day so
		// the activation request itself never blocks on a synchronous HTTP
		// probe to the license server. The daily recurrence continues afterwards.
		if ( ! wp_next_scheduled( 'apeiron_kit_check_license' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'apeiron_kit_check_license' );
		}
		add_action( 'apeiron_kit_check_license', [ $this, 'check_license_status' ] );

		// Register AJAX handlers
		add_action( 'wp_ajax_apeiron_activate_license', [ $this, 'handle_activate' ] );
		add_action( 'wp_ajax_apeiron_deactivate_license', [ $this, 'handle_deactivate' ] );
		add_action( 'wp_ajax_apeiron_check_license', [ $this, 'handle_check' ] );
		add_action( 'wp_ajax_apeiron_save_server_config', [ $this, 'handle_save_server_config' ] );
		add_action( 'wp_ajax_apeiron_test_server_connection', [ $this, 'handle_test_connection' ] );

		// Surface configuration/licensing issues in dashboard
		add_action( 'admin_notices', [ $this, 'render_admin_notices' ] );
	}

	/**
	 * Filter to get API URL from saved option if available
	 * This allows the saved URL to override the default/constant
	 *
	 * @param string $default_url Default URL from constant or hardcoded default
	 * @return string API URL to use
	 */
	public function filter_api_url_from_option( string $default_url ): string {
		// If constant is defined and not empty, use it (highest priority)
		if ( defined( 'APEIRON_KIT_LICENSE_API_URL' ) && ! empty( APEIRON_KIT_LICENSE_API_URL ) ) {
			return $default_url;
		}

		// Otherwise, try to get from saved option
		$saved_url = get_option( 'apeiron_kit_license_api_url', '' );
		if ( ! empty( $saved_url ) ) {
			return $this->sanitize_api_url( $saved_url );
		}

		return $default_url;
	}

	/**
	 * Helper: sanitized License Server URL.
	 */
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
	 * Deterministic destination check for the license server, resolved purely by
	 * parsing — never by DNS.
	 *
	 * `wp_http_validate_url()` used to guard this path, but it resolves the host
	 * with `gethostbyname()`: an IPv4-only lookup with no timeout control. On
	 * hosting whose resolver is slow, restricted, or IPv6-first, that lookup fails
	 * or stalls and the request is rejected before a single packet is sent — the
	 * license server never even sees it. Its actual protection here was blocking a
	 * destination that resolves somewhere private, which is redundant for a
	 * destination pinned to an exact hostname allowlist: reaching a private address
	 * would require controlling DNS for our own domain.
	 *
	 * Every request on this path must pass through here first. A URL that fails is
	 * never sent — there is no "validation failed, try anyway" fallback.
	 */
	private function is_allowed_license_url( string $url ): bool {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return false;
		}

		// Credentials in the URL are a classic way to disguise the real host.
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return false;
		}

		if ( 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) {
			return false;
		}

		// Only the default HTTPS port, stated or omitted.
		if ( isset( $parts['port'] ) && 443 !== (int) $parts['port'] ) {
			return false;
		}

		// Exact hostname match after lowercasing — never a substring test, so
		// server-apeiron.web.id.evil.com and evil.com/server-apeiron.web.id both fail.
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

	/**
	 * Resolve API URL lazily (after filters are registered)
	 * Priority: constant > saved option > hardcoded default
	 */
	private function resolve_api_url(): string {
		if ( $this->api_url_resolved ) {
			return $this->api_url;
		}
		
		$this->api_url_resolved = true;
		
		// Priority 1: Constant
		if ( defined( 'APEIRON_KIT_LICENSE_API_URL' ) && ! empty( APEIRON_KIT_LICENSE_API_URL ) ) {
			$url = APEIRON_KIT_LICENSE_API_URL;
		} else {
			// Priority 2: Saved option
			$saved = get_option( 'apeiron_kit_license_api_url', '' );
			// Priority 3: Hardcoded default
			$url = ! empty( $saved ) ? $saved : self::DEFAULT_API_URL;
		}
		
		$this->api_url = $this->sanitize_api_url(
			apply_filters( 'apeiron_kit_license_api_url', $url )
		);
		
		// Final fallback: if everything fails, use hardcoded default
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

	/**
	 * Get license data
	 */
	public function get_license(): array {
		// Serve from in-memory cache so PBKDF2 decrypt only runs once per request.
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
		
		// Decrypt license key if encrypted
		if ( ! empty( $license['key'] ) && ! empty( $license['key_encrypted'] ) ) {
			$decrypted_key = $this->decrypt_license_key( $license['key'] );
			
			// If decryption failed (empty result), log error but keep original
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

	/**
	 * Save license data
	 */
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
	 * Reset all in-request caches so the next is_valid()/get_license() reads
	 * fresh from the database. Called after any mutation that clears the
	 * transient.
	 */
	private function invalidate_caches(): void {
		$this->license_cache = null;
		$this->valid_cache   = null;
		delete_transient( 'apeiron_kit_license_valid' );
	}

	/**
	 * Check if license is active
	 */
	public function is_active(): bool {
		$license = $this->get_license();
		return 'active' === $license['status'];
	}

	/**
	 * Check if license is valid (active and not expired)
	 * Uses cache to improve performance
	 */
	public function is_valid(): bool {
		// In-memory guard: within a single request the answer never changes.
		if ( null !== $this->valid_cache ) {
			return $this->valid_cache;
		}

		// Check transient first (12 hour cache — reduces periodic PBKDF2 hits).
		$cached = get_transient( 'apeiron_kit_license_valid' );
		if ( $cached !== false ) {
			return $this->valid_cache = (bool) $cached;
		}

		// Perform actual validation
		if ( ! $this->is_active() ) {
			// Before returning false, check persistent cache
			// License might be active but status was corrupted by a failed check
			$persistent = $this->get_persistent_cache();
			if ( $persistent !== null && ( $persistent['status'] ?? '' ) === 'active' ) {
				// Persistent cache says license is active — trust it
				set_transient( 'apeiron_kit_license_valid', 1, 12 * HOUR_IN_SECONDS );
				return $this->valid_cache = true;
			}

			set_transient( 'apeiron_kit_license_valid', 0, 12 * HOUR_IN_SECONDS );
			return $this->valid_cache = false;
		}

		$license = $this->get_license();

		// Entitlement is only ever as fresh as the last time the licence server
		// actually answered. `last_check` advances on activation and on an
		// authoritative check and is deliberately left alone by the
		// transient-failure path, which makes it the verification anchor. Records
		// written before it existed fall back to the persistent cache, which
		// validates that it belongs to this installation before handing back a
		// timestamp. With no trustworthy timestamp the install counts as
		// unverified rather than entitled — an unreachable server must not become
		// a permanent licence. The stored key, site and activation identity are
		// untouched either way, so recovery is a check, never a new seat.
		$verified_at = (int) $license['last_check'];
		if ( $verified_at <= 0 ) {
			$persistent  = $this->get_persistent_cache();
			$verified_at = null !== $persistent ? (int) ( $persistent['cached_at'] ?? 0 ) : 0;
		}

		if ( $verified_at <= 0 || ( time() - $verified_at ) > self::PERSISTENT_CACHE_TTL ) {
			set_transient( 'apeiron_kit_license_valid', 0, 12 * HOUR_IN_SECONDS );
			return $this->valid_cache = false;
		}

		// If no expiration date, consider it valid if active
		if ( empty( $license['expires'] ) ) {
			set_transient( 'apeiron_kit_license_valid', 1, 12 * HOUR_IN_SECONDS );
			return $this->valid_cache = true;
		}

		// Check if expired
		$expires = strtotime( $license['expires'] );
		// Malformed expiration values previously unlocked the license: when `strtotime()` failed
		// it returned `false`, which the old branch treated as "valid forever". Treat a
		// non-empty but unparsable expiration as invalid so the user must re-validate.
		$is_valid = false !== $expires && $expires > time();

		// Cache result
		set_transient( 'apeiron_kit_license_valid', $is_valid ? 1 : 0, 12 * HOUR_IN_SECONDS );

		return $this->valid_cache = $is_valid;
	}

	/**
	 * Activate license via remote API
	 */
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
			
			// Save persistent cache — protects license during server downtime
			$this->save_persistent_cache( $license_data );
			
			// Reset failure counter on successful activation
			delete_option( 'apeiron_kit_check_fail_count' );
			
			// Clear validation cache to re-evaluate
			$this->invalidate_caches();
		}

		return $response;
	}

	/**
	 * Deactivate license via remote API
	 */
	public function deactivate(): array {
		$license = $this->get_license();

		if ( empty( $license['key'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Tidak ada license key yang terdaftar.', 'apeiron-kit' ),
			];
		}

		// Release the seat that was actually recorded at activation. home_url() can
		// drift afterwards (HTTPS migration, host or path change) and would target
		// an activation record the server does not have, orphaning the seat.
		$activated_site = ! empty( $license['site_url'] ) ? $license['site_url'] : home_url();

		$response = $this->api_request( 'deactivate', [
			'license_key' => $license['key'],
			'site_url'    => $activated_site,
			'product_id'  => $this->product_id,
		] );

		if ( $response['success'] ) {
			// Clear license data
			delete_option( $this->option_name );
			// Clear all caches
			$this->invalidate_caches();
			$this->clear_persistent_cache();
			delete_option( 'apeiron_kit_check_fail_count' );
		}

		return $response;
	}

	/**
	 * Check license status via remote API
	 */
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

		// Cached success is not a new verification and must not renew either anchor.
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
			
			// Update persistent cache on successful check
			$this->save_persistent_cache( $license_data );
			
			// Reset failure counter on success
			delete_option( 'apeiron_kit_check_fail_count' );
			
			// Clear validation cache to re-evaluate
			$this->invalidate_caches();
		} else {
			// Don't immediately deactivate — use grace period
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
			
			// A licence is retired by the licence server, never by the network in
			// between. Unless the server actually delivered a verdict about this
			// licence, leave the stored activation exactly as it is: the failure
			// counter above is enough to surface the outage.
			if ( ! $this->is_authoritative_license_failure( $response ) ) {
				return $response;
			}

			// Try to restore from persistent cache first
			$cached = $this->get_persistent_cache();
			if ( $cached !== null && ( $cached['status'] ?? '' ) === 'active' ) {
				// Keep license active from persistent cache
				if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
					\ApeironKit\Support\ErrorLogger::info(
						'License kept active from persistent cache (fail #' . $fail_count . ')'
					);
				}
				
				// Only deactivate after MAX consecutive failures AND cache expired
				if ( $fail_count >= self::MAX_CHECK_FAILURES ) {
					$cache_age = time() - ( $cached['cached_at'] ?? 0 );
					if ( $cache_age > self::PERSISTENT_CACHE_TTL ) {
						// Cache too old + max failures = deactivate
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
					// else: cache still valid, keep license active
				}
			} else {
				// No persistent cache and check failed — only deactivate after MAX failures
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
	 * Whether a failed response is the licence server's own verdict about this
	 * licence, rather than something that merely prevented us from asking.
	 *
	 * Only a verdict may retire a stored activation. Transport classes
	 * (`APEIRON_*`), HTTP 5xx, malformed replies and client-side configuration
	 * problems such as a missing or rejected API key say nothing about whether
	 * the customer's licence is still valid.
	 *
	 * `api/check.php` reports expiry, suspension and an unregistered site through
	 * a *successful* response carrying `data.status`, so the failure path sees a
	 * verdict only when the licence record itself is gone or refused outright.
	 * The codes below are the ones the server actually defines in
	 * `includes/exceptions.php`.
	 */
	private function is_authoritative_license_failure( array $response ): bool {
		return in_array(
			(string) ( $response['error_code'] ?? '' ),
			[ 'LICENSE_NOT_FOUND', 'LICENSE_EXPIRED', 'LICENSE_SUSPENDED', 'LICENSE_INACTIVE' ],
			true
		);
	}

	/**
	 * Transport error classes. Kept separate from the business error codes the
	 * server returns (LICENSE_EXPIRED, ACTIVATION_LIMIT_REACHED, …) so support can
	 * tell "we could not reach the server" apart from "the server said no".
	 */
	private function classify_transport_error( \WP_Error $error ): string {
		$message = strtolower( $error->get_error_message() );

		if ( false !== strpos( $message, 'valid url was not provided' ) || false !== strpos( $message, 'url yang sah' ) ) {
			// WordPress refused the URL before sending anything: its safe-request
			// pre-check resolves the host with an IPv4-only lookup that some hosts fail.
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

	/** Transport failures worth exactly one more attempt. */
	private function is_retryable_transport_error( string $class ): bool {
		return in_array(
			$class,
			[ 'APEIRON_CONNECT_TIMEOUT', 'APEIRON_REQUEST_TIMEOUT', 'APEIRON_CONNECTION_RESET', 'APEIRON_SERVER_UNREACHABLE' ],
			true
		);
	}

	/**
	 * One POST, plus at most one retry for a transient transport failure.
	 *
	 * The licence host publishes both A and AAAA records. On hosting where IPv6 is
	 * configured but not routed, the first attempt can burn its whole budget on a
	 * connection that will never complete, so the retry pins itself to IPv4. The
	 * cURL filter is scoped to this exact URL and removed immediately, so no other
	 * plugin's requests are affected. Business errors never reach this method —
	 * they arrive as an HTTP response, not as a WP_Error.
	 */
	private function send_with_one_retry( string $url, array $args ) {
		// Last gate before the wire. wp_remote_post() is used instead of the "safe"
		// variant so WordPress does not gate the request behind its IPv4-only DNS
		// pre-check; that is only sound because the destination is pinned here to an
		// exact hostname allowlist rather than being an arbitrary user-supplied URL.
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

	/**
	 * Make API request to license server
	 */
	private function api_request( string $action, array $data, bool &$fresh = false ): array {
		$fresh = false;
		// Resolve API URL lazily (ensures filter + option + fallback are all applied)
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

		// Get API key
		$api_key = $this->api_key_manager->get_api_key();
		if ( empty( $api_key ) ) {
			return $this->get_cached_response( $action, [
				'success' => false,
				'message' => __( 'API Key tidak dikonfigurasi. Silakan set API Key di settings.', 'apeiron-kit' ),
			] );
		}

		$url = trailingslashit( $api_url ) . $action . '.php';

		// URL already validated by resolve_api_url() + sanitize_api_url()

		$args = [
			'timeout'     => 15,
			'redirection' => 0,
			'body'        => wp_json_encode( $data ),
			'headers'     => [
				'Content-Type'  => 'application/json',
				'X-API-Key'    => $api_key,
				'User-Agent'   => 'ApeironKit/' . APEIRON_KIT_VERSION . '; ' . home_url(),
			],
			'sslverify' => true, // Verify SSL certificate
		];

		// Debug logging (guarded — strips sensitive data)
		if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
			\ApeironKit\Support\ErrorLogger::info( 'License API request', [
				'url'       => $url,
				'action'    => $action,
			] );
		}

		$response = $this->send_with_one_retry( $url, $args );

		// Handle connection errors - use cache if available
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_code = $this->classify_transport_error( $response );
			
			// Log connection error
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
			
			// Try cache on connection error (only for check action)
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
			
			// Customers see a plain sentence; the classification code carries the
			// detail that support actually needs.
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
			// Log error for debugging
			$error_details = [
				'code' => $code,
				'body' => $body,
				'url' => $url,
				'action' => $action,
			];
			
			// Try to parse error message from response body
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
			
			// Log error with context
			if ( class_exists( '\ApeironKit\Support\ErrorLogger' ) ) {
				\ApeironKit\Support\ErrorLogger::error( 
					'License server returned error',
					$error_details
				);
			}
			
			// Try cache on server error (only for check action, not activate/deactivate)
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
			
			// Build user-friendly error message
			$user_message = sprintf( __( 'License server mengembalikan error: %d', 'apeiron-kit' ), $code );
			
			// Add error message from server if available
			if ( ! empty( $error_message ) ) {
				$user_message .= '. ' . esc_html( $error_message );
			}
			
			// Add helpful troubleshooting tips for 500 error
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

		// Expected response format:
		// { "success": true, "data": { "status": "active", "expires": "2024-12-31", ... } }
		// or
		// { "success": false, "message": "Error message" }

		if ( isset( $response_data['success'] ) && $response_data['success'] ) {
			$fresh = true;
			// Cache successful response
			$this->cache_response( $action, $response_data['data'] ?? [] );
			
			return [
				'success' => true,
				'data'    => $response_data['data'] ?? [],
				'message' => $response_data['message'] ?? __( 'License berhasil divalidasi.', 'apeiron-kit' ),
			];
		}

		// Handle specific error codes
		$error_code = $response_data['error_code'] ?? '';
		$error_message = $response_data['message'] ?? __( 'License validation gagal.', 'apeiron-kit' );

		// Customize error message for specific error codes
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
	 * Cache API response for offline use
	 * 
	 * @param string $action Action name
	 * @param array $data Response data
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
	 * Get cached API response
	 * 
	 * @param string $action Action name
	 * @param array|null $fallback Fallback response if cache expired
	 * @return array|null Cached data or null
	 */
	private function get_cached_response( string $action, ?array $fallback = null ): ?array {
		$cache = get_option( $this->cache_option, [] );
		
		if ( ! isset( $cache[ $action ] ) ) {
			return $fallback;
		}
		
		$cached = $cache[ $action ];
		$age = time() - ( $cached['timestamp'] ?? 0 );
		
		// Cache valid for 24 hours
		if ( $age > 86400 ) {
			return $fallback;
		}
		
		$data = $cached['data'] ?? $fallback;
		if ( 'check' === $action && is_array( $data ) ) {
			// Accept stored success envelopes as well as the normal raw data shape.
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
	 * Save persistent license cache (long-term protection against server downtime)
	 * 
	 * This cache survives server errors and prevents license from being deactivated
	 * when the license server is temporarily unavailable.
	 * 
	 * @param array $license_data License data to cache
	 * @return void
	 */
	private function save_persistent_cache( array $license_data ): void {
		$cache_data = $license_data;

		// Never persist the decrypted license key in plaintext. Keep only the metadata
		// required by the silence-during-outage grace period.
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
	 * Get persistent license cache
	 * 
	 * Returns cached license data if still within TTL and from same site.
	 * 
	 * @return array|null Cached license data or null if expired/invalid
	 */
	private function get_persistent_cache(): ?array {
		$cached = get_option( $this->persistent_cache_option, null );

		if ( empty( $cached ) || ! is_array( $cached ) ) {
			return null;
		}

		// Validate cache is from same site. Compared in normalized form so a bare
		// scheme or trailing-slash difference — home_url() follows the scheme of the
		// current request — does not throw away the outage grace period.
		$cached_site = $cached['cached_site_url'] ?? '';
		if ( ! empty( $cached_site ) && $this->normalize_site_url( $cached_site ) !== $this->normalize_site_url( home_url() ) ) {
			// Site URL changed (migration) — cache invalid
			delete_option( $this->persistent_cache_option );
			return null;
		}

		// Check TTL
		$cache_age = time() - ( $cached['cached_at'] ?? 0 );
		if ( $cache_age > self::PERSISTENT_CACHE_TTL ) {
			return null; // Expired
		}

		// Do not expose a decrypted license key from the persistent cache. The store used
		// to mirror `license_data['key']` decrypted, which is plaintext at-rest; expose
		// only the metadata the grace-period logic actually needs.
		unset( $cached['key'] );

		return $cached;
	}

	/**
	 * Comparable form of an installation URL. Used only for local comparisons —
	 * the value transmitted to the license server is deliberately left verbatim so
	 * existing activation records keep matching.
	 */
	private function normalize_site_url( string $url ): string {
		$url = strtolower( trim( $url ) );
		$url = (string) preg_replace( '#^https?://#', '', $url );

		return untrailingslashit( $url );
	}

	/**
	 * License data safe to hand back to the browser. The admin UI masks the key
	 * once a license is active, so the AJAX payload must not carry it either.
	 */
	private function get_license_for_response(): array {
		$license = $this->get_license();
		unset( $license['key'], $license['key_encrypted'] );

		return $license;
	}

	/**
	 * Clear persistent cache (called on deactivate)
	 */
	private function clear_persistent_cache(): void {
		delete_option( $this->persistent_cache_option );
	}

	/**
	 * Encrypt license key using versioned authenticated storage.
	 *
	 * @param string $key License key to encrypt
	 * @return string Versioned encrypted license key
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
	 * Decrypt authenticated storage with legacy fallback.
	 *
	 * @param string $encrypted Encrypted license key
	 * @return string Decrypted license key
	 */
	private function decrypt_license_key( string $encrypted ): string {
		$salt = get_option( 'apeiron_kit_license_salt' );
		if ( Crypto::is_versioned( $encrypted ) ) {
			return Crypto::decrypt_authenticated( $encrypted, $this->derive_authenticated_license_key( $salt ), self::AEAD_CONTEXT );
		}

		$wp_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_generate_password( 32, true, true );
		$site_url = get_option( 'siteurl' );
		
		// Derive encryption key using PBKDF2 (must match encryption method)
		$encryption_key = Crypto::derive_pbkdf2_key( $salt . $wp_key . $site_url, 'apeiron_kit_salt' );
		$decrypted      = Crypto::decrypt_aes_cbc( $encrypted, $encryption_key );
		if ( '' === $decrypted ) {
			// Try old XOR format for backward compatibility
			return $this->decrypt_license_key_xor( $encrypted );
		}
		
		return $decrypted;
	}

	private function derive_authenticated_license_key( string $salt ): string {
		$key_material = defined( 'AUTH_KEY' ) && '' !== AUTH_KEY ? AUTH_KEY : $salt;
		return Crypto::derive_hkdf_key( $key_material, $salt, self::AEAD_CONTEXT );
	}
	
	/**
	 * Legacy XOR decryption for backward compatibility
	 * 
	 * @param string $encrypted Encrypted license key
	 * @return string Decrypted license key
	 */
	private function decrypt_license_key_xor( string $encrypted ): string {
		$salt = get_option( 'apeiron_kit_license_salt' );
		$wp_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_generate_password( 32, true, true );
		$site_url = get_option( 'siteurl' );
		
		$encryption_key = Crypto::derive_sha256_key( $salt . $wp_key . $site_url );

		return Crypto::xor_decrypt( $encrypted, $encryption_key );
	}

	/**
	 * Handle AJAX activate license
	 */
	public function handle_activate(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		// Check user capabilities
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

	/**
	 * Handle AJAX deactivate license
	 */
	public function handle_deactivate(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		// Check user capabilities
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

	/**
	 * Handle AJAX check license
	 */
	public function handle_check(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		// Check user capabilities
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

	/**
	 * Handle AJAX save server configuration
	 */
	public function handle_save_server_config(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['api_key'] ) ) ) : '';

		// Determine API URL: constant > POST > saved option > default
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

		// Save API URL to option (can be filtered later)
		update_option( 'apeiron_kit_license_api_url', $api_url, false );

		// Update API URL in instance
		$this->api_url = $api_url;
		$this->api_url_resolved = true;

		// Save API Key if provided
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

	/**
	 * Handle AJAX test server connection
	 */
	public function handle_test_connection(): void {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		// Get current API URL (refresh from source)
		$api_url = $this->get_api_url();

		// Get API key
		$api_key = $this->api_key_manager->get_api_key();

		// Check server configuration
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

		// Test connection using health.php endpoint (no auth required for basic check)
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

		// Same destination rule as the licence endpoints: pinned by the allowlist,
		// so it does not need WordPress's DNS-resolving pre-check either.
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
			
			// Try to get error message from response body
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

	/**
	 * Get license status for display
	 */
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

	/**
	 * Show admin notices for missing configuration or invalid license
	 */
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
