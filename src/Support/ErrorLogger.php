<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error Logger untuk Apeiron Kit
 * 
 * Centralized error logging dengan support untuk debugging
 */
class ErrorLogger {

	/**
	 * Log error message
	 * 
	 * @param string $message Error message
	 * @param array $context Additional context data
	 * @param string $level Log level (error, warning, info)
	 * @return void
	 */
	public static function log( string $message, array $context = [], string $level = 'error' ): void {
		// Only log if WP_DEBUG is enabled
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = '[Apeiron Kit] [' . strtoupper( $level ) . '] ' . $message;
		
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		// Log to WordPress debug log
		error_log( $log_message );
	}

	/**
	 * Log error
	 * 
	 * @param string $message Error message
	 * @param array $context Additional context
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::log( $message, $context, 'error' );
	}

	/**
	 * Log warning
	 * 
	 * @param string $message Warning message
	 * @param array $context Additional context
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::log( $message, $context, 'warning' );
	}

	/**
	 * Log info
	 * 
	 * @param string $message Info message
	 * @param array $context Additional context
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( $message, $context, 'info' );
	}
}
