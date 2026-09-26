<?php

namespace ApeironKit\Support\Assets;

use ApeironKit\Core\LicenseManager;
use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendAssetEnqueuer {

	private AssetRegistrar $registrar;
	private CoverAssetManager $cover_assets;
	private DocumentWidgetIndex $document_index;
	private ElementorContext $context;

	/**
	 * @var array<string,true>|null
	 */
	private ?array $present_widget_types = null;

	public function __construct(
		AssetRegistrar $registrar,
		CoverAssetManager $cover_assets,
		DocumentWidgetIndex $document_index,
		ElementorContext $context
	) {
		$this->registrar      = $registrar;
		$this->cover_assets   = $cover_assets;
		$this->document_index = $document_index;
		$this->context        = $context;
	}

	public function register_assets(): void {
		if ( $this->context->is_editor_shell() ) {
			return;
		}

		$this->registrar->register();
	}

	public function enqueue(): void {
		if ( $this->context->is_editor_shell() ) {
			return;
		}

		$this->registrar->register();

		$widget_css_map = $this->active_map( WidgetRegistry::css_map() );
		$widget_js_map  = $this->active_map( WidgetRegistry::js_map() );

		if ( $this->context->is_preview_iframe() ) {
			$this->enqueue_preview_assets( $widget_css_map, $widget_js_map );
			return;
		}

		$present_types = $this->get_present_widget_types();
		if ( empty( $present_types ) || ! LicenseManager::instance()->is_valid() ) {
			return;
		}

		wp_enqueue_style( 'apeiron-kit-frontend' );

		foreach ( $widget_css_map as $widget_type => $css_handle ) {
			if ( ! isset( $present_types[ $widget_type ] ) ) {
				continue;
			}

			wp_enqueue_style( $css_handle );
			if ( 'apeiron-cover' === $widget_type ) {
				$this->cover_assets->enqueue_current_type_styles();
			}
		}

		foreach ( $widget_js_map as $widget_type => $js_handle ) {
			if ( ! isset( $present_types[ $widget_type ] ) ) {
				continue;
			}

			wp_enqueue_script( $js_handle );
			if ( 'apeiron-cover' === $widget_type ) {
				$this->cover_assets->enqueue_current_type_scripts();
			}
		}
	}

	/**
	 * @param array<string,string> $widget_css_map Active CSS map.
	 * @param array<string,string> $widget_js_map  Active JS map.
	 */
	private function enqueue_preview_assets( array $widget_css_map, array $widget_js_map ): void {
		if ( ! LicenseManager::instance()->is_valid() ) {
			wp_enqueue_style( 'apeiron-kit-widget-lock' );
			return;
		}

		wp_enqueue_style( 'apeiron-kit-frontend' );

		foreach ( $widget_css_map as $css_handle ) {
			wp_enqueue_style( $css_handle );
		}
		if ( isset( $widget_css_map['apeiron-cover'] ) ) {
			$this->cover_assets->enqueue_all_type_styles();
		}

		foreach ( $widget_js_map as $js_handle ) {
			wp_enqueue_script( $js_handle );
		}
		if ( isset( $widget_js_map['apeiron-cover'] ) ) {
			$this->cover_assets->enqueue_all_type_scripts();
		}
	}

	/**
	 * @param array<string,string> $map Widget type to handle map.
	 * @return array<string,string>
	 */
	private function active_map( array $map ): array {
		return array_diff_key( $map, array_flip( WidgetRegistry::disabled_types() ) );
	}

	/**
	 * @return array<string,true>
	 */
	private function get_present_widget_types(): array {
		if ( null !== $this->present_widget_types ) {
			return $this->present_widget_types;
		}

		$legacy_to_slug = WidgetRegistry::legacy_type_to_slug_map();
		$slug_to_type   = WidgetRegistry::slug_to_type_map();
		$known_types    = array_fill_keys( array_values( $slug_to_type ), true );
		$disabled_types = array_flip( WidgetRegistry::disabled_types() );
		$present        = [];

		foreach ( array_keys( $this->document_index->get()['types'] ) as $type ) {
			if ( isset( $legacy_to_slug[ $type ] ) ) {
				$type = $slug_to_type[ $legacy_to_slug[ $type ] ] ?? $type;
			}

			if ( isset( $known_types[ $type ] ) && ! isset( $disabled_types[ $type ] ) ) {
				$present[ $type ] = true;
			}
		}

		return $this->present_widget_types = $present;
	}
}
