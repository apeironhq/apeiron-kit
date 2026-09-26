<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\ClipboardTap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CopyValueResolver {

	/**
	 * `current_url` stays empty because JS resolves it at click time.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 */
	public static function resolve( array $settings, string $source ): string {
		switch ( $source ) {
			case 'current_url':
				return '';
			case 'custom_url':
				$custom_url = $settings['custom_url'] ?? [];

				return is_array( $custom_url ) && isset( $custom_url['url'] ) && is_scalar( $custom_url['url'] )
					? (string) $custom_url['url']
					: '';
			case 'shortcode':
				return self::resolve_shortcode( $settings );
			case 'manual':
			default:
				return isset( $settings['value'] ) && is_scalar( $settings['value'] ) ? (string) $settings['value'] : '';
		}
	}

	/** @param array<string,mixed> $settings */
	private static function resolve_shortcode( array $settings ): string {
		$shortcode = isset( $settings['shortcode_content'] ) && is_scalar( $settings['shortcode_content'] )
			? (string) $settings['shortcode_content']
			: '';

		if ( '' === $shortcode ) {
			return '';
		}

		return trim( wp_strip_all_tags( do_shortcode( $shortcode ) ) );
	}
}
