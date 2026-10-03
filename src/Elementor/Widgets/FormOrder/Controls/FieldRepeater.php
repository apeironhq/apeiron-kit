<?php

namespace ApeironKit\Elementor\Widgets\FormOrder\Controls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Native repeater data contract with deferred editor row controls, only for Form Order. */
class FieldRepeater extends \Elementor\Control_Repeater {
	public function get_type() {
		return 'apeiron_order_fields';
	}
}
