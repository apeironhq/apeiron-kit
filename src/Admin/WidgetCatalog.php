<?php

namespace ApeironKit\Admin;

use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared widget metadata for admin screens.
 */
class WidgetCatalog {

	/**
	 * Get Apeiron Kit widget definitions.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_features(): array {
		return WidgetRegistry::features();
	}
}
