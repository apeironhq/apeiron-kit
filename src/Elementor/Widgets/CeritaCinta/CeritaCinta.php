<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CeritaCinta;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\CeritaCinta\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\CeritaCinta\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CeritaCinta extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	public function __construct( $data = [], $args = null ) {
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = self::resolve_skin_settings( $data['settings'] );
			if ( is_array( $data['settings']['stories'] ?? null ) ) {
				foreach ( $data['settings']['stories'] as &$story ) {
					if ( is_array( $story ) && ! isset( $story['photo_mode'] ) && ( ! empty( $story['photo_gallery'] ) || ! empty( $story['__dynamic__']['photo_gallery'] ) ) ) {
						$story['photo_mode'] = 'slideshow';
					}
					if ( is_array( $story ) && is_array( $story['item_style_overrides'] ?? null ) && array_values( $story['item_style_overrides'] ) !== $story['item_style_overrides'] ) {
						$story['item_style_overrides'] = array_keys( array_filter( $story['item_style_overrides'], static function ( $value ): bool { return 'yes' === $value; } ) );
					}
				}
				unset( $story );
			}
		}

		parent::__construct( $data, $args );
	}

	/** Normalize saved appearance choices before Elementor builds settings, CSS, or editor data. */
	private static function resolve_skin_settings( array $settings ): array {
		$aliases = [
			'scrapbook-story'   => 'scrapbook-story',
			'scrapbook_story'   => 'scrapbook-story',
			'timeline'         => 'scrapbook-story',
			'scrapbook-collage' => 'scrapbook-collage',
			'scrapbook_collage' => 'scrapbook-collage',
			'collage'          => 'scrapbook-collage',
		];
		$suffixes  = [ '', '_tablet', '_mobile' ];
		$converted = in_array( $settings['skin_schema'] ?? null, [ '2', 2 ], true );

		// Include saved optional Elementor breakpoints without changing their inheritance.
		foreach ( array_keys( $settings ) as $key ) {
			if ( is_string( $key ) && preg_match( '/^story_layout(_[a-z0-9_]+)$/D', $key, $match ) ) {
				$suffixes[] = $match[1];
			}
		}

		foreach ( array_unique( $suffixes ) as $suffix ) {
			$key        = 'skin' . $suffix;
			$legacy_key = 'story_layout' . $suffix;
			$current    = is_string( $settings[ $key ] ?? null ) ? $settings[ $key ] : '';
			$legacy     = is_string( $settings[ $legacy_key ] ?? null ) ? $settings[ $legacy_key ] : '';

			// The original Skin control had only Scrapbook Story; Layout owned the actual choice.
			if ( ! $converted && isset( $aliases[ $legacy ] ) && ( '' === $current || 'scrapbook-story' === $current ) ) {
				$settings[ $key ] = $aliases[ $legacy ];
			} elseif ( isset( $aliases[ $current ] ) ) {
				$settings[ $key ] = $aliases[ $current ];
			} elseif ( '' === $suffix ) {
				$settings[ $key ] = 'scrapbook-story';
			}
		}

		// Subsequent Skin changes must not be overridden by retained legacy Layout values.
		$settings['skin_schema'] = '2';

		return $settings;
	}

	public function get_name() {
		return 'apeiron-cerita-cinta';
	}

	public function get_title() {
		return __( 'Cerita Cinta', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'eicon-time-line';
	}

	public function get_keywords() {
		return [ 'cerita', 'cinta', 'love', 'story', 'timeline', 'scrapbook', 'undangan' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-cerita-cinta';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-cerita-cinta-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings = (array) $this->get_settings_for_display();
		$stories  = is_array( $settings['stories'] ?? null ) ? array_filter( $settings['stories'], 'is_array' ) : [];
		$clip_id  = wp_unique_id( 'apeiron-cerita-cinta-clip-' . $this->get_id() . '-' );

		// Both skins share their existing content markup and asset handles.
		require __DIR__ . '/Partials/scrapbook-story.php';
	}
}
