<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cover settings compatibility resolver.
 */
class CoverSettings {

	public const OPTION_NAME = 'apeiron_cover_settings';

	public static function defaults(): array {
		return [
			'active_cover_type'      => CoverTypeRegistry::DEFAULT_TYPE,
			'allow_widget_override' => 'no',
		];
	}

	public static function widget_defaults(): array {
		return [
			'cover_settings_mode' => 'custom',
			'cover_type'          => CoverTypeRegistry::DEFAULT_TYPE,
		];
	}

	public static function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, [] );

		return self::sanitize( wp_parse_args( (array) $saved, self::defaults() ) );
	}

	public static function get_active_type(): string {
		return CoverTypeRegistry::DEFAULT_TYPE;
	}

	public static function is_widget_override_allowed(): bool {
		return false;
	}

	public static function sanitize( $input ): array {
		return self::defaults();
	}

	public static function sanitize_form( $input ): array {
		return self::sanitize( $input );
	}

	public static function resolve_widget_settings( array $settings, array $raw_settings = [] ): array {
		// Elementor can null controls whose conditions use persisted settings.
		// Restore those values before resolving the widget type.
		$settings = array_replace(
			$raw_settings,
			array_filter(
				$settings,
				static fn( $value ) => null !== $value
			)
		);
		$settings                        = wp_parse_args( $settings, self::widget_defaults() );
		$settings['cover_settings_mode'] = 'custom';
		$settings['cover_type']          = CoverTypeRegistry::sanitize_type(
			(string) ( $raw_settings['cover_type'] ?? $settings['cover_type'] ?? '' )
		);

		return $settings;
	}

	public static function resolve_widget_mode( array $settings, array $raw_settings = [] ): string {
		return 'custom';
	}

}
