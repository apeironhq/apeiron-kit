<?php

namespace ApeironKit\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;
use Elementor\Core\DynamicTags\Manager as DynamicTagsManager;
use ApeironKit\Support\WidgetRegistry;

class WidgetManager {

	/** Status pendaftaran hook Elementor. */
	private bool $registered = false;

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/dynamic_tags/register', [ $this, 'register_guest_name_parameter' ], 20 );

		$this->register_if_elementor_already_initialized();
	}

	private function register_if_elementor_already_initialized(): void {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return;
		}

		$elementor = \Elementor\Plugin::$instance;

		if (
			did_action( 'elementor/elements/categories_registered' )
			&& isset( $elementor->elements_manager )
			&& $elementor->elements_manager instanceof Elements_Manager
		) {
			$this->register_category( $elementor->elements_manager );
		}

		if (
			did_action( 'elementor/widgets/register' )
			&& isset( $elementor->widgets_manager )
			&& $elementor->widgets_manager instanceof Widgets_Manager
		) {
			$this->register_widgets( $elementor->widgets_manager );
		}
	}

	public function register_category( Elements_Manager $manager ): void {
		$manager->add_category(
			'apeiron-kit',
			[
				'title' => __( 'Apeiron Kit', 'apeiron-kit' ),
				'icon'  => 'fa fa-puzzle-piece',
			]
		);
	}

	public function register_widgets( Widgets_Manager $manager ): void {
		$disabled = WidgetRegistry::disabled_slugs();
		$map      = WidgetRegistry::class_to_slug_map();

		foreach ( WidgetRegistry::widget_classes() as $widget_class ) {
			$this->register_widget_class( $manager, $widget_class, $map[ $widget_class ] ?? '', $disabled );
		}

		// Alias lama tetap terdaftar agar dokumen tersimpan tetap dirender.
		foreach ( WidgetRegistry::legacy_widget_classes() as $widget_class ) {
			$canonical_slug = $map[ $widget_class ] ?? '';
			$this->register_widget_class( $manager, $widget_class, $canonical_slug, $disabled );
		}
	}

	public function register_guest_name_parameter( DynamicTagsManager $manager ): void {
		$class = '\\ElementorPro\\Modules\\DynamicTags\\Tags\\Request_Parameter';
		if ( ! class_exists( $class ) ) {
			return;
		}
		$tag = $manager->get_tag_info( 'request-arg' );
		if ( ! $tag || ltrim( $tag['class'], '\\' ) !== ltrim( $class, '\\' ) ) {
			return;
		}

		$manager->register( new class extends \ElementorPro\Modules\DynamicTags\Tags\Request_Parameter {
			public function render() {
				$settings = $this->get_settings();
				if ( 'to' !== ( $settings['param_name'] ?? '' ) || 'GET' !== strtoupper( (string) ( $settings['request_type'] ?? 'get' ) ) ) {
					parent::render();
					return;
				}
				if ( isset( $_GET['to'] ) && ! is_scalar( $_GET['to'] ) ) {
					return;
				}

				ob_start();
				try {
					parent::render();
				} finally {
					$value = (string) ob_get_clean();
				}
				// Pertahankan sanitasi tag, lalu escape teks tamu tanpa entity ganda.
				echo esc_html( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
			}
		} );
	}

	/**
	 * Kegagalan satu widget tidak menghentikan pendaftaran widget lainnya.
	 *
	 * @param class-string $widget_class
	 * @param string[]     $disabled
	 */
	private function register_widget_class( Widgets_Manager $manager, string $widget_class, string $slug, array $disabled ): void {
		if ( $slug && in_array( $slug, $disabled, true ) ) {
			return;
		}

		try {
			if ( ! class_exists( $widget_class ) || ! is_subclass_of( $widget_class, '\\Elementor\\Widget_Base' ) ) {
				return;
			}

			$manager->register( new $widget_class() );
		} catch ( \Throwable $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Apeiron Kit widget registration failed for %s: %s',
						$widget_class,
						$exception->getMessage()
					)
				);
			}
		}
	}
}

