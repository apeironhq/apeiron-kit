<?php

namespace ApeironKit\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;
use ApeironKit\Support\WidgetRegistry;

class WidgetManager {

	/**
	 * Whether Elementor hooks have already been attached.
	 */
	private bool $registered = false;

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

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

		// Legacy aliases: hidden from the panel but still registered so
		// pages saved with deprecated widget types (e.g. `apeiron-pulse-countdown`)
		// keep rendering after the canonical rename.
		foreach ( WidgetRegistry::legacy_widget_classes() as $widget_class ) {
			$canonical_slug = $map[ $widget_class ] ?? '';
			$this->register_widget_class( $manager, $widget_class, $canonical_slug, $disabled );
		}
	}

	/**
	 * Register one widget without allowing a broken optional widget to abort
	 * registration of every other widget.
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

