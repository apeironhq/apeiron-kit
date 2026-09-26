<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SoundscapePlayer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AudioSourceResolver {

	/** @var array<string,string> */
	private const MIME_MAP = [
		'mp3'  => 'mpeg',
		'm4a'  => 'mp4',
		'aac'  => 'aac',
		'ogg'  => 'ogg',
		'oga'  => 'ogg',
		'wav'  => 'wav',
		'webm' => 'webm',
	];

	public static function resolve_url( array $settings, string $src_type ): string {
		switch ( $src_type ) {
			case 'link':
				return isset( $settings['audio_link']['url'] ) ? (string) $settings['audio_link']['url'] : '';
			case 'youtube':
				return isset( $settings['youtube_link'] ) ? (string) $settings['youtube_link'] : '';
			case 'upload':
			default:
				return isset( $settings['audio_upload']['url'] ) ? (string) $settings['audio_upload']['url'] : '';
		}
	}

	public static function is_youtube( string $src_type ): bool {
		return 'youtube' === $src_type;
	}

	public static function mime_type( string $url ): string {
		$path = (string) parse_url( $url, PHP_URL_PATH );

		if ( '' === $path ) {
			return '';
		}

		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		return isset( self::MIME_MAP[ $extension ] ) ? 'audio/' . self::MIME_MAP[ $extension ] : '';
	}
}
