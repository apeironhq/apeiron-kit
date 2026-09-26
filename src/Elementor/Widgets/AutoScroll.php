<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\\AutoScroll', false ) ) {
	class_alias(
		\ApeironKit\Elementor\Widgets\AutoScroll\AutoScroll::class,
		__NAMESPACE__ . '\\AutoScroll'
	);
}
