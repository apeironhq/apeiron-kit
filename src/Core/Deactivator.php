<?php

namespace ApeironKit\Core;

class Deactivator {

	public static function deactivate(): void {
		if ( wp_next_scheduled( 'apeiron_kit_check_license' ) ) {
			wp_clear_scheduled_hook( 'apeiron_kit_check_license' );
		}
	}
}

