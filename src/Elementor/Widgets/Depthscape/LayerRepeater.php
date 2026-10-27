<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Depthscape;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native repeater data with editor controls rendered only when a layer is opened.
 */
final class LayerRepeater extends \Elementor\Control_Repeater {

	public function get_type() {
		return 'apeiron_depthscape_layers';
	}

	/**
	 * On template import Elementor fills every row with field defaults, so non-image layers (container,
	 * text, …) carry the placeholder image and the importer downloads it once per row. Only real images
	 * are imported; the skipped values are put back unchanged.
	 *
	 * @param mixed $settings
	 * @param array $control_data
	 * @return mixed
	 */
	public function on_import( $settings, $control_data = [] ) {
		if ( ! is_array( $settings ) ) {
			return parent::on_import( $settings, $control_data );
		}

		$placeholder = \Elementor\Utils::get_placeholder_image_src();
		$skipped     = [];

		foreach ( $settings as $index => $item ) {
			if ( ! is_array( $item ) || ! isset( $item['image'] ) ) {
				continue;
			}

			$image  = is_array( $item['image'] ) ? $item['image'] : [];
			$is_set = ! empty( $image['id'] ) || ( ! empty( $image['url'] ) && $placeholder !== $image['url'] );
			if ( 'image' !== ( $item['layer_type'] ?? 'image' ) || ! $is_set ) {
				$skipped[ $index ]           = $item['image'];
				$settings[ $index ]['image'] = [];
			}
		}

		$settings = parent::on_import( $settings, $control_data );

		foreach ( $skipped as $index => $image ) {
			$settings[ $index ]['image'] = $image;
		}

		return $settings;
	}

	public function enqueue() {
		$relative = 'assets/css/editor-depthscape.css';
		$path     = APEIRON_KIT_PATH . $relative;

		wp_enqueue_style(
			'apeiron-kit-depthscape-editor',
			APEIRON_KIT_URL . $relative,
			[ 'elementor-editor' ],
			file_exists( $path ) ? (string) filemtime( $path ) : APEIRON_KIT_VERSION
		);

		$relative = 'assets/js/editor-depthscape.js';
		$path     = APEIRON_KIT_PATH . $relative;

		wp_enqueue_script(
			'apeiron-kit-depthscape-editor',
			APEIRON_KIT_URL . $relative,
			[ 'elementor-editor' ],
			file_exists( $path ) ? (string) filemtime( $path ) : APEIRON_KIT_VERSION,
			true
		);

		$relative = 'assets/js/editor-depthscape-content.js';
		$path     = APEIRON_KIT_PATH . $relative;
		wp_enqueue_script(
			'apeiron-kit-depthscape-content-editor',
			APEIRON_KIT_URL . $relative,
			[ 'apeiron-kit-depthscape-editor' ],
			file_exists( $path ) ? (string) filemtime( $path ) : APEIRON_KIT_VERSION,
			true
		);
	}
}
