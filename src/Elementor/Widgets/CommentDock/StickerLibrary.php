<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CommentDock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StickerLibrary {
	public const DEFAULT_FOLDER = 'assets/stickers/gift';
	public const UPLOAD_SUBDIR  = 'apeiron-kit/stickers';
	public const UPLOAD_PREFIX  = 'uploads/apeiron-kit/stickers/gift';

	private const IMAGE_EXTENSIONS = [ 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp' ];

	private const VIDEO_EXTENSIONS = [ 'webm', 'mp4' ];

	/** @var array<string,array<int,array<string,string>>> */
	private static array $cache = [];

	/**
	 * Legacy controls remain fallback for instances saved before `sticker_media_type`.
	 * @return array{0:bool,1:bool}
	 */
	public static function resolve_media_permissions( array $settings, array $raw_settings = [] ): array {
		$media_type = $settings['sticker_media_type'] ?? 'all';

		if ( ! array_key_exists( 'sticker_media_type', $raw_settings ) ) {
			$legacy_allow_image = $raw_settings['sticker_allow_image'] ?? $settings['sticker_allow_image'] ?? 'yes';
			$legacy_allow_video = $raw_settings['sticker_allow_video'] ?? $settings['sticker_allow_video'] ?? 'yes';
			if ( 'yes' !== $legacy_allow_image || 'yes' !== $legacy_allow_video ) {
				return [ 'yes' === $legacy_allow_image, 'yes' === $legacy_allow_video ];
			}
		}

		if ( ! in_array( $media_type, [ 'all', 'image', 'video' ], true ) ) {
			$media_type = 'all';
		}

		return [ 'video' !== $media_type, 'image' !== $media_type ];
	}

	/** @return array<int,array<string,string>> */
	public static function load( string $folder, bool $allow_image = true, bool $allow_video = true ): array {
		$folder = trim( $folder );
		if ( '' === $folder ) {
			return [];
		}

		$cache_key = $folder . '|' . ( $allow_image ? '1' : '0' ) . '|' . ( $allow_video ? '1' : '0' );
		if ( isset( self::$cache[ $cache_key ] ) ) {
			return self::$cache[ $cache_key ];
		}

		$relative = ltrim( wp_normalize_path( $folder ), '/\\' );
		if ( '' === $relative ) {
			return self::$cache[ $cache_key ] = [];
		}

		$allowed_extensions = [];
		if ( $allow_image ) {
			$allowed_extensions = array_merge( $allowed_extensions, self::IMAGE_EXTENSIONS );
		}
		if ( $allow_video ) {
			$allowed_extensions = array_merge( $allowed_extensions, self::VIDEO_EXTENSIONS );
		}

		if ( empty( $allowed_extensions ) ) {
			return self::$cache[ $cache_key ] = [];
		}

		$items = [];
		foreach ( self::storage_locations( $relative ) as $location ) {
			foreach ( self::scan_files( $location['path'], $allowed_extensions ) as $file ) {
				$identifier = trailingslashit( $location['identifier_prefix'] ) . basename( $file );

				$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
				$type = in_array( $ext, self::VIDEO_EXTENSIONS, true ) ? 'video' : 'image';
				$items[] = [
					'id'       => hash( 'sha256', $identifier ),
					'url'      => trailingslashit( $location['url'] ) . rawurlencode( basename( $file ) ),
					'relative' => $identifier,
					'type'     => $type,
					'name'     => ucwords( str_replace( [ '-', '_' ], ' ', pathinfo( $file, PATHINFO_FILENAME ) ) ),
				];
			}
		}
		usort( $items, static fn( array $a, array $b ): int => strnatcasecmp( $a['name'], $b['name'] ) );

		return self::$cache[ $cache_key ] = array_values( $items );
	}

	/** @return array{absolute:string,url:string,relative:string,type:string,storage:string}|null */
	public static function resolve( string $identifier ): ?array {
		$relative = ltrim( wp_normalize_path( trim( $identifier ) ), '/\\' );
		if ( preg_match( '#(^|/)\.\.($|/)#', $relative ) ) {
			return null;
		}

		$locations = self::storage_locations( self::DEFAULT_FOLDER );
		$location  = null;
		foreach ( $locations as $candidate ) {
			$prefix = trailingslashit( $candidate['identifier_prefix'] );
			if ( 0 === strpos( $relative, $prefix )
				&& preg_match( '#^' . preg_quote( $prefix, '#' ) . '[a-zA-Z0-9._-]+$#', $relative ) ) {
				$location = $candidate;
				break;
			}
		}
		if ( null === $location ) {
			return null;
		}

		$ext = strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array_merge( self::IMAGE_EXTENSIONS, self::VIDEO_EXTENSIONS ), true ) ) {
			return null;
		}

		$absolute  = wp_normalize_path( trailingslashit( $location['path'] ) . basename( $relative ) );
		$real_base = realpath( $location['path'] );
		$real_file = realpath( $absolute );
		if ( false !== $real_base && false !== $real_file && is_file( $real_file )
			&& self::is_path_within( $real_file, $real_base ) ) {
			return [
				'absolute' => wp_normalize_path( $real_file ),
				'url'      => trailingslashit( $location['url'] ) . rawurlencode( basename( $relative ) ),
				'relative' => $relative,
				'type'     => in_array( $ext, self::VIDEO_EXTENSIONS, true ) ? 'video' : 'image',
				'storage'  => $location['storage'],
			];
		}

		return null;
	}

	/** @return array{path:string,url:string}|null */
	public static function upload_directory(): ?array {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return null;
		}

		return [
			'path' => wp_normalize_path( trailingslashit( $uploads['basedir'] ) . self::UPLOAD_SUBDIR . '/gift' ),
			'url'  => trailingslashit( $uploads['baseurl'] ) . self::UPLOAD_SUBDIR . '/gift',
		];
	}

	/** @param array<string,string> $sticker */
	public static function render_option( array $sticker ): string {
		$type = 'video' === ( $sticker['type'] ?? 'image' ) ? 'video' : 'image';
		$name = $sticker['name'] ?? __( 'Stiker', 'apeiron-kit' );
		$media = 'video' === $type
			? sprintf(
				'<video src="%1$s" preload="metadata" autoplay muted loop playsinline></video>',
				esc_url( $sticker['url'] ?? '' )
			)
			: sprintf(
				'<img src="%1$s" alt="%2$s">',
				esc_url( $sticker['url'] ?? '' ),
				esc_attr( $name )
			);

		return sprintf(
			'<button type="button" class="apeiron-kit-sticker-option" data-src="%1$s" data-type="%2$s" aria-label="%3$s">%4$s</button>',
			esc_attr( $sticker['relative'] ?? '' ),
			esc_attr( $type ),
			esc_attr( $name ),
			$media
		);
	}

	/**
	 * @param string[] $allowed_extensions
	 * @return string[]
	 */
	private static function scan_files( string $base_path, array $allowed_extensions ): array {
		if ( ! is_dir( $base_path ) || ! is_readable( $base_path ) ) {
			return [];
		}

		$all_files = glob( trailingslashit( $base_path ) . '*' );
		$files     = [];
		if ( is_array( $all_files ) ) {
			foreach ( $all_files as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}
				$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
				if ( in_array( $ext, $allowed_extensions, true ) ) {
					$files[] = $file;
				}
			}
		}

		natcasesort( $files );
		return is_array( $files ) ? $files : [];
	}

	/** @return array<int,array{path:string,url:string,storage:string,identifier_prefix:string}> */
	private static function storage_locations( string $relative ): array {
		$locations = [];
		$upload    = self::upload_directory();
		if ( null !== $upload && self::DEFAULT_FOLDER === $relative ) {
			$locations[] = [
				'path'              => $upload['path'],
				'url'               => $upload['url'],
				'storage'           => 'uploads',
				'identifier_prefix' => self::UPLOAD_PREFIX,
			];
		}

		$locations[] = [
			'path'    => trailingslashit( APEIRON_KIT_PATH ) . $relative,
			'url'     => trailingslashit( APEIRON_KIT_URL ) . $relative,
			'storage' => 'legacy',
			'identifier_prefix' => self::DEFAULT_FOLDER,
		];
		return $locations;
	}

	private static function is_path_within( string $path, string $base ): bool {
		$path = wp_normalize_path( $path );
		$base = rtrim( wp_normalize_path( $base ), '/' );
		return $path === $base || 0 === strpos( $path, $base . '/' );
	}
}
