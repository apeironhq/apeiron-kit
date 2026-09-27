<?php

namespace ApeironKit\Admin;

use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Metadata widget untuk halaman admin. */
class WidgetCatalog {

	/** @return array<int,array<string,mixed>> */
	public static function get_features(): array {
		return WidgetRegistry::features();
	}
}
