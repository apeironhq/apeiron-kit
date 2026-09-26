<?php

namespace ApeironKit\Core;

use ApeironKit\Support\UcapanTamuSettings;

class Activator {

	public static function activate(): void {
		self::seed_options();

		// Schedule the first daily license check at activation so the next request is not
		// responsible for creating the schedule (which previously caused a small race on
		// the first page view after activation). The event itself still fires tomorrow.
		if ( ! wp_next_scheduled( 'apeiron_kit_check_license' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'apeiron_kit_check_license' );
		}
	}

	private static function seed_options(): void {
		if ( ! get_option( 'apeiron_kit_settings' ) ) {
			add_option(
				'apeiron_kit_settings',
				[
					'comment_moderation' => 'auto',
				],
				'',
				false
			);
		}

		if ( false === get_option( UcapanTamuSettings::OPTION_NAME, false ) ) {
			add_option( UcapanTamuSettings::OPTION_NAME, UcapanTamuSettings::defaults(), '', false );
		}

		if ( false === get_option( 'apeiron_kit_license_api_url', false ) ) {
			add_option( 'apeiron_kit_license_api_url', 'https://server-apeiron.web.id/api', '', false );
		}
	}
}

