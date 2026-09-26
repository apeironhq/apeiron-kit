<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Countdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Keeps saved `apeiron-pulse-countdown` widgets rendering. */
final class LegacyPulseCountdown extends Countdown {

	public function get_name() {
		return 'apeiron-pulse-countdown';
	}

	public function show_in_panel(): bool {
		return false;
	}

	/** Avoid duplicating the canonical control stack in Elementor editor config. */
	protected function register_widget_controls() {
	}
}
