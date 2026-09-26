<?php

namespace ApeironKit\Core;

class Deactivator {

	public static function deactivate(): void {
		if ( wp_next_scheduled( 'apeiron_kit_check_license' ) ) {
			wp_clear_scheduled_hook( 'apeiron_kit_check_license' );
		}

		// No rewrite rules, post types, or taxonomies are registered by this plugin, so a
		// rewrite flush is unnecessary and only adds DB write cost on every deactivation.
	}
}

