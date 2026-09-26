<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Countdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses Countdown targets using WordPress site timezone.
 */
final class TargetDateParser {

	private const FORMATS = [
		'!Y-m-d H:i:s',
		'!Y-m-d\TH:i:s',
		'!Y-m-d H:i',
		'!Y-m-d\TH:i',
		'!Y-m-d',
	];

	/** @return int|false */
	public static function parse( string $value, ?\DateTimeZone $timezone = null ) {
		$timezone = $timezone ?? wp_timezone();

		foreach ( self::FORMATS as $format ) {
			$date   = \DateTimeImmutable::createFromFormat( $format, $value, $timezone );
			$errors = \DateTimeImmutable::getLastErrors();

			$is_valid = false !== $date
				&& ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) )
				&& $date->format( ltrim( $format, '!' ) ) === $value;

			if ( $is_valid ) {
				return $date->getTimestamp();
			}
		}

		return false;
	}
}
