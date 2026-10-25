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
