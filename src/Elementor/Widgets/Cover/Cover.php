<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Cover;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\Cover\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\Cover\Concerns\RendersCover;
use ApeironKit\Elementor\Widgets\Cover\Concerns\RegistersStyleControls;
use ApeironKit\Support\CoverTypeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cover extends BaseWidget {

	use RegistersContentControls;
	use RendersCover;
	use RegistersStyleControls;

	private const PANEL_EFFECTS = [ 'shrink', 'slide' ];
	private const EXIT_EFFECTS  = [ 'down', 'up', 'fade', 'none' ];

	public function get_name() {
		return 'apeiron-cover';
	}

	public function get_title() {
		return __( 'Sampul', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-cover';
	}

	public function get_keywords() {
		return [ 'cover', 'undangan', 'invitation', 'opening', 'pita', 'apeiron' ];
	}

	public function get_style_depends() {
		$styles = array_merge(
			parent::get_style_depends(),
			[ 'apeiron-kit-cover' ],
			CoverTypeRegistry::all_style_handles()
		);

		return array_values( array_unique( $styles ) );
	}

	public function get_script_depends() {
		$scripts = array_merge( parent::get_script_depends(), CoverTypeRegistry::all_script_handles() );

		return array_values( array_unique( $scripts ) );
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}
}
