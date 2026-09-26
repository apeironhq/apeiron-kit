<?php

namespace ApeironKit\Admin\Ajax;

use ApeironKit\Support\WidgetRegistry;
use ApeironKit\Support\WidgetUsageIndex;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget toggle AJAX handler.
 */
class WidgetToggleHandler {

	private const NONCE_ACTION = 'apeiron_widget_toggle';

	private bool $registered = false;
	private bool $cache_clear_scheduled = false;

	/**
	 * Register individual compatibility endpoints and the atomic bulk endpoint.
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		add_action( 'wp_ajax_apeiron_toggle_widget', [ $this, 'handle_toggle' ] );
		add_action( 'wp_ajax_apeiron_bulk_toggle_widgets', [ $this, 'handle_bulk_toggle' ] );
		add_action( 'wp_ajax_apeiron_check_widget_usage', [ $this, 'handle_check_usage' ] );
	}

	/**
	 * Preserve the individual toggle endpoint for existing clients.
	 */
	public function handle_toggle(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Anda tidak memiliki izin untuk mengubah pengaturan ini.', 'apeiron-kit' ),
			] );
		}

		$widget = isset( $_POST['widget'] ) && is_string( $_POST['widget'] )
			? sanitize_key( wp_unslash( $_POST['widget'] ) )
			: '';

		if ( '' === $widget ) {
			wp_send_json_error( [
				'message' => __( 'Widget slug tidak boleh kosong.', 'apeiron-kit' ),
			] );
		}

		if ( ! in_array( $widget, self::allowed_widgets(), true ) ) {
			wp_send_json_error( [
				'message' => __( 'Widget tidak dikenali.', 'apeiron-kit' ),
			] );
		}

		$state = isset( $_POST['state'] ) && is_string( $_POST['state'] )
			? sanitize_key( wp_unslash( $_POST['state'] ) )
			: '';

		if ( ! in_array( $state, [ 'on', 'off' ], true ) ) {
			wp_send_json_error( [
				'message' => __( 'State tidak valid. Gunakan "on" atau "off".', 'apeiron-kit' ),
			] );
		}

		$before   = WidgetRegistry::disabled_slugs();
		$disabled = $before;

		if ( 'off' === $state ) {
			$disabled[] = $widget;
		} else {
			$disabled = array_values( array_diff( $disabled, [ $widget ] ) );
		}

		$disabled = WidgetRegistry::sanitize_slugs( $disabled );

		if ( ! self::same_slug_set( $before, $disabled ) ) {
			if ( ! $this->persist_disabled_widgets( $disabled ) ) {
				wp_send_json_error( [
					'message' => __( 'Status widget tidak dapat disimpan.', 'apeiron-kit' ),
				] );
			}

			$this->schedule_elementor_cache_clear();
		}

		wp_send_json_success( [
			'message' => 'off' === $state
				? __( 'Widget berhasil dinonaktifkan.', 'apeiron-kit' )
				: __( 'Widget berhasil diaktifkan.', 'apeiron-kit' ),
			'widget'   => $widget,
			'state'    => $state,
			'disabled' => $disabled,
		] );
	}

	/**
	 * Atomically apply one state to a validated set of widgets.
	 *
	 * Validation and usage checks complete before the single option write, so a
	 * rejected request cannot leave a partially applied widget list.
	 */
	public function handle_bulk_toggle(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Anda tidak memiliki izin untuk mengubah pengaturan ini.', 'apeiron-kit' ),
			] );
		}

		$state = isset( $_POST['state'] ) && is_string( $_POST['state'] )
			? sanitize_key( wp_unslash( $_POST['state'] ) )
			: '';

		if ( ! in_array( $state, [ 'on', 'off' ], true ) ) {
			wp_send_json_error( [
				'message' => __( 'State bulk tidak valid. Gunakan "on" atau "off".', 'apeiron-kit' ),
			] );
		}

		$widgets = $this->get_bulk_widgets();
		$current = WidgetRegistry::disabled_slugs();
		$next    = 'off' === $state
			? WidgetRegistry::sanitize_slugs( array_merge( $current, $widgets ) )
			: WidgetRegistry::sanitize_slugs( array_values( array_diff( $current, $widgets ) ) );

		$current_lookup = array_fill_keys( $current, true );
		$next_lookup    = array_fill_keys( $next, true );
		$changed        = [];

		foreach ( $widgets as $widget ) {
			if ( isset( $current_lookup[ $widget ] ) !== isset( $next_lookup[ $widget ] ) ) {
				$changed[] = $widget;
			}
		}

		if ( empty( $changed ) ) {
			wp_send_json_success( [
				'message'               => __( 'Tidak ada perubahan status widget.', 'apeiron-kit' ),
				'state'                 => $state,
				'changed'               => [],
				'disabled'              => $current,
				'requires_confirmation' => false,
			] );
		}

		$confirmed = isset( $_POST['confirmed'] )
			&& is_scalar( $_POST['confirmed'] )
			&& '1' === sanitize_text_field( wp_unslash( (string) $_POST['confirmed'] ) );

		if ( 'off' === $state && ! $confirmed ) {
			$usage = [];
			try {
				$usage = WidgetUsageIndex::get_usage( $changed );
			} catch ( \Throwable $exception ) {
				wp_send_json_error( [
					'message' => __( 'Penggunaan widget tidak dapat diperiksa. Tidak ada perubahan yang disimpan.', 'apeiron-kit' ),
				] );
			}

			$used = [];
			foreach ( $changed as $widget ) {
				$widget_usage = $usage[ $widget ] ?? null;
				if ( ! is_array( $widget_usage )
					|| ! isset( $widget_usage['used'], $widget_usage['count'], $widget_usage['pages'] )
					|| ! is_bool( $widget_usage['used'] )
					|| ! is_int( $widget_usage['count'] )
					|| $widget_usage['count'] < 0
					|| ! is_array( $widget_usage['pages'] )
					|| $widget_usage['used'] !== ( $widget_usage['count'] > 0 )
				) {
					wp_send_json_error( [
						'message' => __( 'Hasil pemeriksaan penggunaan widget tidak valid. Tidak ada perubahan yang disimpan.', 'apeiron-kit' ),
					] );
				}

				if ( $widget_usage['count'] > 0 ) {
					$used[ $widget ] = $widget_usage;
				}
			}

			if ( ! empty( $used ) ) {
				wp_send_json_success( [
					'message'               => __( 'Konfirmasi diperlukan karena widget yang dipilih masih digunakan.', 'apeiron-kit' ),
					'state'                 => $state,
					'changed'               => $changed,
					'usage'                 => $used,
					'requires_confirmation' => true,
				] );
			}
		}

		if ( ! $this->persist_disabled_widgets( $next ) ) {
			wp_send_json_error( [
				'message' => __( 'Perubahan status widget tidak dapat disimpan.', 'apeiron-kit' ),
			] );
		}

		$this->schedule_elementor_cache_clear();

		$message = 'on' === $state
			? sprintf( __( '%d widget berhasil diaktifkan.', 'apeiron-kit' ), count( $changed ) )
			: sprintf( __( '%d widget berhasil dinonaktifkan.', 'apeiron-kit' ), count( $changed ) );

		wp_send_json_success( [
			'message'               => $message,
			'state'                 => $state,
			'changed'               => $changed,
			'disabled'              => $next,
			'requires_confirmation' => false,
		] );
	}

	/**
	 * Get the nonce action string.
	 */
	public static function get_nonce_action(): string {
		return self::NONCE_ACTION;
	}

	/**
	 * Return indexed usage for the compatibility usage-check endpoint.
	 */
	public function handle_check_usage(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Tidak memiliki izin.', 'apeiron-kit' ),
			] );
		}

		$widget = isset( $_POST['widget'] ) && is_string( $_POST['widget'] )
			? sanitize_key( wp_unslash( $_POST['widget'] ) )
			: '';

		if ( ! in_array( $widget, self::allowed_widgets(), true ) ) {
			wp_send_json_error( [
				'message' => __( 'Widget tidak dikenali.', 'apeiron-kit' ),
			] );
		}

		$usage = [];
		try {
			$usage = WidgetUsageIndex::get_usage( [ $widget ] );
		} catch ( \Throwable $exception ) {
			wp_send_json_error( [
				'message' => __( 'Penggunaan widget tidak dapat diperiksa. Coba lagi sebelum menonaktifkan widget.', 'apeiron-kit' ),
			] );
		}

		if ( ! isset( $usage[ $widget ] ) || ! is_array( $usage[ $widget ] ) ) {
			wp_send_json_error( [
				'message' => __( 'Hasil pemeriksaan penggunaan widget tidak valid.', 'apeiron-kit' ),
			] );
		}

		wp_send_json_success( $usage[ $widget ] );
	}

	/**
	 * @return string[]
	 */
	private static function allowed_widgets(): array {
		return WidgetRegistry::allowed_slugs();
	}

	/**
	 * Parse and strictly validate the complete bulk widget list before any write.
	 *
	 * @return string[]
	 */
	private function get_bulk_widgets(): array {
		$raw_widgets = isset( $_POST['widgets'] ) ? wp_unslash( $_POST['widgets'] ) : [];

		if ( is_string( $raw_widgets ) ) {
			$decoded     = json_decode( $raw_widgets, true );
			$raw_widgets = is_array( $decoded ) ? $decoded : [];
		}

		if ( ! is_array( $raw_widgets ) || empty( $raw_widgets ) ) {
			wp_send_json_error( [
				'message' => __( 'Daftar widget bulk tidak boleh kosong.', 'apeiron-kit' ),
			] );
		}

		$allowed = array_fill_keys( self::allowed_widgets(), true );
		if ( count( $raw_widgets ) > count( $allowed ) ) {
			wp_send_json_error( [
				'message' => __( 'Daftar widget bulk tidak valid.', 'apeiron-kit' ),
			] );
		}

		$widgets = [];
		$seen    = [];

		foreach ( $raw_widgets as $raw_widget ) {
			if ( ! is_string( $raw_widget ) ) {
				wp_send_json_error( [
					'message' => __( 'Daftar widget bulk tidak valid.', 'apeiron-kit' ),
				] );
			}

			$widget = sanitize_key( $raw_widget );
			if ( '' === $widget || ! isset( $allowed[ $widget ] ) ) {
				wp_send_json_error( [
					'message' => __( 'Salah satu widget bulk tidak dikenali.', 'apeiron-kit' ),
				] );
			}

			if ( isset( $seen[ $widget ] ) ) {
				wp_send_json_error( [
					'message' => __( 'Daftar widget bulk mengandung duplikat.', 'apeiron-kit' ),
				] );
			}

			$seen[ $widget ] = true;
			$widgets[]       = $widget;
		}

		return $widgets;
	}

	/**
	 * Persist the complete disabled list with exactly one option write.
	 *
	 * @param string[] $disabled Disabled widget slugs.
	 */
	private function persist_disabled_widgets( array $disabled ): bool {
		$disabled = WidgetRegistry::sanitize_slugs( $disabled );
		if ( update_option( WidgetRegistry::DISABLED_OPTION, $disabled, true ) ) {
			return true;
		}

		$stored = WidgetRegistry::disabled_slugs();

		return self::same_slug_set( $stored, $disabled );
	}

	/**
	 * @param string[] $left  First widget list.
	 * @param string[] $right Second widget list.
	 */
	private static function same_slug_set( array $left, array $right ): bool {
		sort( $left );
		sort( $right );

		return $left === $right;
	}

	/**
	 * Schedule one non-blocking Elementor CSS cache clear for this request.
	 */
	private function schedule_elementor_cache_clear(): void {
		if ( $this->cache_clear_scheduled ) {
			return;
		}

		$this->cache_clear_scheduled = true;

		add_action( 'shutdown', static function () {
			if ( class_exists( '\Elementor\Plugin' )
				&& isset( \Elementor\Plugin::$instance->files_manager )
			) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
		} );
	}
}
