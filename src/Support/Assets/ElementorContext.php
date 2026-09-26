<?php

namespace ApeironKit\Support\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ElementorContext {

	public function is_editor_shell(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		return ! $this->is_preview_iframe()
			&& isset( $elementor->editor )
			&& method_exists( $elementor->editor, 'is_edit_mode' )
			&& $elementor->editor->is_edit_mode();
	}

	public function is_preview_iframe(): bool {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance, \Elementor\Plugin::$instance->preview )
			&& method_exists( \Elementor\Plugin::$instance->preview, 'is_preview_mode' )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	public function is_editing(): bool {
		return $this->is_editor_shell() || $this->is_preview_iframe();
	}
}
