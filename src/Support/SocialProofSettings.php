<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SocialProofSettings {

	public const OPTION_NAME = 'apeiron_social_proof_settings';

	/**
	 * @var array<string,mixed>
	 */
	private const DEFAULTS = [
		'display_duration'    => 3,
		'interval_duration'   => 8,
		'initial_delay'       => 0,
		'max_notifications'   => 0,
		'animation_type'      => 'slide-top',
		'popup_position'      => 'bottom-right',
		'text_template'       => '{name} telah membeli {product} pada:',
		'image_border_radius' => 10,
		'entries'             => [],
	];

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return self::DEFAULTS;
	}

	/** @return array<string,mixed> */
	public static function get(): array {
		$settings = get_option( self::OPTION_NAME, [] );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : [], self::DEFAULTS );
		$settings['display_duration']    = self::integer_or_default( $settings['display_duration'], 'display_duration' );
		$settings['interval_duration']   = self::integer_or_default( $settings['interval_duration'], 'interval_duration' );
		$settings['initial_delay']       = self::integer_or_default( $settings['initial_delay'], 'initial_delay' );
		$settings['max_notifications']   = self::integer_or_default( $settings['max_notifications'], 'max_notifications' );
		$settings['image_border_radius'] = self::integer_or_default( $settings['image_border_radius'], 'image_border_radius' );
		$settings['animation_type']      = is_string( $settings['animation_type'] ) ? $settings['animation_type'] : self::DEFAULTS['animation_type'];
		$settings['popup_position']      = is_string( $settings['popup_position'] ) ? $settings['popup_position'] : self::DEFAULTS['popup_position'];
		$settings['text_template']       = is_string( $settings['text_template'] ) ? $settings['text_template'] : self::DEFAULTS['text_template'];
		$settings['entries'] = is_array( $settings['entries'] ?? null ) ? $settings['entries'] : [];

		return $settings;
	}

	private static function integer_or_default( $value, string $key ): int {
		return is_scalar( $value ) && is_numeric( $value ) ? (int) $value : self::DEFAULTS[ $key ];
	}
}
