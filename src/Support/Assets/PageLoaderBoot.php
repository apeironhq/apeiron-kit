<?php

namespace ApeironKit\Support\Assets;

use ApeironKit\Core\LicenseManager;
use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageLoaderBoot {

	private AssetRegistrar $registrar;
	private DocumentWidgetIndex $document_index;
	private ElementorContext $context;

	/**
	 * @var array|false|null
	 */
	private $config = null;

	public function __construct(
		AssetRegistrar $registrar,
		DocumentWidgetIndex $document_index,
		ElementorContext $context
	) {
		$this->registrar      = $registrar;
		$this->document_index = $document_index;
		$this->context        = $context;
	}

	/**
	 * Enqueue the early boot shell and per-widget preflight in the document head.
	 */
	public function print(): void {
		$config = $this->get_config();
		if ( ! is_array( $config ) ) {
			return;
		}

		if ( $config['bootEnabled'] ) {
			wp_enqueue_style(
				'apeiron-kit-page-loader-boot',
				$this->registrar->url( 'assets/css/page-loader-boot', 'css' ),
				[],
				$this->registrar->version( 'assets/css/page-loader-boot', 'css' )
			);
		}

		wp_register_script(
			'apeiron-kit-page-loader-boot',
			$this->registrar->url( 'assets/js/page-loader-boot', 'js' ),
			[],
			$this->registrar->version( 'assets/js/page-loader-boot', 'js' ),
			false
		);
		wp_localize_script( 'apeiron-kit-page-loader-boot', 'ApeironPageLoaderBootConfig', $config );
		wp_enqueue_script( 'apeiron-kit-page-loader-boot' );
	}

	/**
	 * @return array|false
	 */
	private function get_config() {
		if ( null !== $this->config ) {
			return $this->config;
		}

		$this->config = false;
		if ( $this->context->is_editing() || ! is_singular() ) {
			return false;
		}

		if ( in_array( 'apeiron-page-loader', WidgetRegistry::disabled_types(), true ) ) {
			return false;
		}

		if ( class_exists( LicenseManager::class ) && ! LicenseManager::instance()->is_valid() ) {
			return false;
		}

		$widget = $this->document_index->get()['page_loader'];
		if ( ! $widget ) {
			return false;
		}

		$settings        = $widget['settings'];
		$maximum_timeout = $this->get_slider_int( $settings, 'maximum_timeout', 14000, 2000, 20000 );
		$legacy   = [
			'#062f44'            => '#083C57',
			'#14b8a6'            => '#083C57',
			'rgb(6,47,68)'       => '#083C57',
			'rgb(20,184,166)'    => '#083C57',
			'rgba(6,47,68,1)'    => '#083C57',
			'rgba(20,184,166,1)' => '#083C57',
		];

		$this->config = [
			'id'             => $widget['id'],
			'elementId'      => 'apeiron-page-loader-' . $widget['id'],
			'bootEnabled'    => true,
			'lockScroll'     => true,
			'primary'        => $this->sanitize_color( $settings['primary_color'] ?? '#083C57', '#083C57', $legacy ),
			'track'          => $this->sanitize_color( $settings['track_color'] ?? '#e5e7ef', '#e5e7ef' ),
			'background'     => $this->sanitize_color( $settings['overlay_background_color'] ?? '#f7f7fb', '#f7f7fb' ),
			'opacity'        => isset( $settings['overlay_opacity']['size'] ) ? max( 0.1, min( 1, (float) $settings['overlay_opacity']['size'] ) ) : 0.98,
			'firstVisitOnly' => $this->get_bool( $settings, 'first_visit_only', false ),
			'storageKey'     => $this->sanitize_storage_key( (string) ( $settings['storage_key'] ?? 'apeiron_page_loader_seen' ) ),
			'showDesktop'    => $this->get_bool( $settings, 'show_desktop', true ),
			'showTablet'     => $this->get_bool( $settings, 'show_tablet', true ),
			'showMobile'     => $this->get_bool( $settings, 'show_mobile', true ),
			'maximumTimeout' => $maximum_timeout,
		];

		return $this->config;
	}

	private function get_bool( array $settings, string $key, bool $fallback ): bool {
		return array_key_exists( $key, $settings ) ? 'yes' === $settings[ $key ] : $fallback;
	}

	private function get_slider_int( array $settings, string $key, int $fallback, int $min, int $max ): int {
		$raw   = $settings[ $key ]['size'] ?? $fallback;
		$value = is_numeric( $raw ) ? (int) round( (float) $raw ) : $fallback;

		return max( $min, min( $max, $value ) );
	}

	/**
	 * @param mixed                $value      Raw color value.
	 * @param array<string,string> $legacy_map Legacy color aliases.
	 */
	private function sanitize_color( $value, string $fallback, array $legacy_map = [] ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return $fallback;
		}

		$key = strtolower( (string) preg_replace( '/\s+/', '', $value ) );
		if ( isset( $legacy_map[ $key ] ) ) {
			return $legacy_map[ $key ];
		}

		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return $hex;
		}

		if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0|1|0?\.\d+))?\s*\)$/', $value ) ) {
			return $value;
		}

		return $fallback;
	}

	private function sanitize_storage_key( string $key ): string {
		$key = sanitize_key( $key );
		if ( '' === $key ) {
			return 'apeiron_page_loader_seen';
		}

		return 0 === strpos( $key, 'apeiron_page_loader_' ) ? $key : 'apeiron_page_loader_' . $key;
	}
}
