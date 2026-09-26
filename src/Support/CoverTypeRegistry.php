<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for Cover layout metadata.
 */
class CoverTypeRegistry {

	public const DEFAULT_TYPE = 'classic';

	/**
	 * @return array<string,array<string,string>>
	 */
	public static function get_types(): array {
		return [
			'classic' => [
				'label'       => __( 'Classic', 'apeiron-kit' ),
				'short_label' => __( 'Sampul', 'apeiron-kit' ),
				'description' => __( 'Sampul Classic tersedia untuk digunakan pada undangan.', 'apeiron-kit' ),
				'icon'        => 'format-image',
				'status'      => 'available',
				'eyebrow'     => __( 'Sampul tersedia', 'apeiron-kit' ),
				'status_label' => __( 'Tersedia', 'apeiron-kit' ),
				'template'    => 'cover.php',
				'styles'      => [ 'apeiron-kit-cover-classic', 'apeiron-kit-cover-responsive' ],
				'scripts'     => [ 'apeiron-kit-cover-js' ],
			],
			'coming-soon' => [
				'label'        => __( 'Sampul Baru', 'apeiron-kit' ),
				'short_label'  => __( 'Sampul', 'apeiron-kit' ),
				'description'  => __( 'Pilihan Sampul tambahan sedang dipersiapkan.', 'apeiron-kit' ),
				'icon'         => 'lock',
				'status'       => 'coming_soon',
				'eyebrow'      => __( 'Pilihan berikutnya', 'apeiron-kit' ),
				'status_label' => __( 'Segera Hadir', 'apeiron-kit' ),
				'template'     => '',
				'styles'       => [],
				'scripts'      => [],
			],
		];
	}

	/**
	 * @return string[]
	 */
	public static function get_slugs(): array {
		return array_keys( self::get_types() );
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_options(): array {
		$options = [];

		foreach ( self::get_types() as $slug => $type ) {
			if ( self::is_available_type( $slug ) ) {
				$options[ $slug ] = $type['label'];
			}
		}

		return $options;
	}

	public static function is_valid_type( string $type ): bool {
		return isset( self::get_types()[ $type ] );
	}

	public static function is_available_type( string $type ): bool {
		$type_data = self::get_types()[ $type ] ?? [];
		$template  = basename( (string) ( $type_data['template'] ?? '' ) );

		return 'available' === ( $type_data['status'] ?? '' )
			&& '' !== $template
			&& is_readable( trailingslashit( APEIRON_KIT_PATH ) . 'src/Elementor/Widgets/Cover/Templates/' . $template );
	}

	public static function sanitize_type( string $type, string $fallback = self::DEFAULT_TYPE ): string {
		$type     = sanitize_key( $type );
		$fallback = self::is_available_type( $fallback ) ? $fallback : self::DEFAULT_TYPE;

		return self::is_available_type( $type ) ? $type : $fallback;
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_type( string $type ): array {
		$types = self::get_types();
		$type  = self::sanitize_type( $type );

		return $types[ $type ];
	}

	public static function get_label( string $type ): string {
		$type_data = self::get_type( $type );

		return $type_data['label'];
	}

	public static function get_template_file( string $type ): string {
		$type_data = self::get_type( $type );
		$template  = basename( (string) ( $type_data['template'] ?? 'cover.php' ) );
		$path      = trailingslashit( APEIRON_KIT_PATH ) . 'src/Elementor/Widgets/Cover/Templates/' . $template;

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * @return string[]
	 */
	public static function get_style_handles( string $type ): array {
		$type_data = self::get_type( $type );
		$handles   = self::normalize_handles( $type_data['styles'] ?? ( $type_data['style'] ?? [] ) );

		return $handles;
	}

	/**
	 * @return string[]
	 */
	public static function get_script_handles( string $type ): array {
		$type_data = self::get_type( $type );
		$handles   = self::normalize_handles( $type_data['scripts'] ?? ( $type_data['script'] ?? [] ) );

		return $handles;
	}

	/**
	 * Every style handle across all cover types.
	 *
	 * Used by Cover::get_style_depends() so the prototype declares the full
	 * dependency set without reading instance data, while per-instance
	 * rendering still enqueues only the active type.
	 *
	 * @return string[]
	 */
	public static function all_style_handles(): array {
		$handles = [];
		foreach ( self::get_types() as $slug => $type ) {
			if ( ! self::is_available_type( $slug ) ) {
				continue;
			}
			$handles = array_merge( $handles, self::normalize_handles( $type['styles'] ?? ( $type['style'] ?? [] ) ) );
		}

		return array_values( array_unique( $handles ) );
	}

	/** @return string[] */
	public static function all_script_handles(): array {
		$handles = [];
		foreach ( self::get_types() as $slug => $type ) {
			if ( ! self::is_available_type( $slug ) ) {
				continue;
			}
			$handles = array_merge( $handles, self::normalize_handles( $type['scripts'] ?? ( $type['script'] ?? [] ) ) );
		}

		return array_values( array_unique( $handles ) );
	}

	/**
	 * @param mixed $handles
	 * @return string[]
	 */
	private static function normalize_handles( $handles ): array {
		$handles = is_array( $handles ) ? $handles : [ $handles ];
		$handles = array_filter(
			array_map(
				static fn( $handle ) => sanitize_key( (string) $handle ),
				$handles
			)
		);

		return array_values( array_unique( $handles ) );
	}
}
