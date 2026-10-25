<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Depthscape;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts raw Elementor settings into the sanitized scene config consumed by depthscape.js.
 *
 * Every numeric value is clamped and every string is whitelisted so the
 * JSON printed into `data-apeiron-depthscape` never carries arbitrary input.
 */
final class SceneConfigBuilder {

	public const LAYER_TYPES = [ 'image', 'video', 'text', 'template', 'shape', 'container' ];

	public const SPACES = [ 'auto', 'world', 'screen' ];

	public const FITS = [ 'free', 'cover' ];

	public const ANCHORS = [ 'center', 'bottom', 'top' ];

	public const IDLE_TYPES = [ 'none', 'float', 'sway', 'breathe', 'drift' ];

	public const SPLIT_TYPES = [ 'none', 'lines', 'words', 'chars' ];

	public const REVEAL_STYLES = [ 'rise', 'fade', 'blur' ];

	public const PARTICLE_STYLES = [ 'pollen', 'petal', 'bokeh' ];

	public const HINT_MODES = [ 'after_first', 'start' ];

	/** Keyframe properties that may be animated, with their allowed ranges. */
	private const KEYFRAME_PROPS = [
		'x'       => [ -500.0, 500.0 ],
		'y'       => [ -500.0, 500.0 ],
		'scale'   => [ 0.0, 20.0 ],
		'rotate'  => [ -720.0, 720.0 ],
		'opacity' => [ 0.0, 1.0 ],
		'blur'    => [ 0.0, 40.0 ],
	];

	/** Camera properties with their allowed ranges. */
	private const CAMERA_PROPS = [
		'x'  => [ -200.0, 200.0 ],
		'y'  => [ -200.0, 200.0 ],
		'z'  => [ -3000.0, 2000.0 ],
		'rx' => [ -60.0, 60.0 ],
		'ry' => [ -60.0, 60.0 ],
		'rz' => [ -180.0, 180.0 ],
	];

	/**
	 * @return array<string,string> GSAP ease => editor label.
	 */
	public static function ease_options(): array {
		return [
			'none'            => __( 'Linear', 'apeiron-kit' ),
			'sine.inOut'      => __( 'Sine – lembut', 'apeiron-kit' ),
			'power1.inOut'    => __( 'Power 1 – halus', 'apeiron-kit' ),
			'power2.inOut'    => __( 'Power 2 – sinematik', 'apeiron-kit' ),
			'power3.inOut'    => __( 'Power 3 – tegas', 'apeiron-kit' ),
			'power4.inOut'    => __( 'Power 4 – dramatis', 'apeiron-kit' ),
			'expo.inOut'      => __( 'Expo – sangat dramatis', 'apeiron-kit' ),
			'circ.inOut'      => __( 'Circ – melingkar', 'apeiron-kit' ),
			'power2.out'      => __( 'Power 2 – melambat di akhir', 'apeiron-kit' ),
			'power3.out'      => __( 'Power 3 – melambat di akhir', 'apeiron-kit' ),
			'expo.out'        => __( 'Expo – melambat di akhir', 'apeiron-kit' ),
			'power2.in'       => __( 'Power 2 – mempercepat', 'apeiron-kit' ),
			'back.out(1.4)'   => __( 'Back – memantul di akhir', 'apeiron-kit' ),
			'back.inOut(1.2)' => __( 'Back – memantul dua arah', 'apeiron-kit' ),
		];
	}

	/**
	 * @param array<string,mixed> $settings Elementor settings for display.
	 * @return array<string,mixed>
	 */
	public static function build( array $settings ): array {
		$layers = self::build_layers( self::rows( $settings['layers'] ?? [] ) );

		return [
			'v'           => 1,
			'scrub'       => self::num( self::slider_size( $settings['smoothness'] ?? null ), 1.0, 0.0, 3.0 ),
			'snap'        => 'yes' === ( $settings['snap'] ?? '' ),
			'snapEase'    => self::ease( $settings['snap_ease'] ?? 'power2.inOut', 'power2.inOut' ),
			'coverPlay'   => 'yes' === ( $settings['cover_autoplay'] ?? '' ),
			'stayFixed'   => 'yes' === ( $settings['stay_fixed'] ?? '' ),
			'perspective' => self::num( self::slider_size( $settings['perspective'] ?? null ), 1000.0, 200.0, 4000.0 ),
			'compensate'  => 'yes' === ( $settings['depth_compensation'] ?? '' ),
			'idle'        => 'yes' === ( $settings['idle_enabled'] ?? '' ),
			'pointer'     => 'yes' === ( $settings['pointer_tilt'] ?? '' ),
			'gyro'        => 'yes' === ( $settings['gyro_tilt'] ?? '' ),
			'tilt'        => self::num( self::slider_size( $settings['tilt_strength'] ?? null ), 4.0, 0.0, 20.0 ),
			'wind'        => self::num( self::slider_size( $settings['wind_strength'] ?? null ), 7.0, 0.0, 20.0 ),
			'breath'      => self::num( self::slider_size( $settings['camera_breath'] ?? null ), 0.0, 0.0, 10.0 ),
			'particles'   => [
				'count' => (int) self::num( self::slider_size( $settings['particle_count'] ?? null ), 0.0, 0.0, 60.0 ),
				'style' => self::pick( $settings['particle_style'] ?? 'pollen', self::PARTICLE_STYLES, 'pollen' ),
				'color' => self::color( $settings['particle_color'] ?? '', '#fff3b0' ),
				'size'  => self::num( self::slider_size( $settings['particle_size'] ?? null ), 6.0, 2.0, 40.0 ),
			],
			'hintMode'    => self::pick( $settings['hint_mode'] ?? 'after_first', self::HINT_MODES, 'after_first' ),
			'editorScene' => self::editor_scene( $settings['editor_preview'] ?? '' ),
			'camera'      => self::camera( $settings, 'cam_start_', true ),
			'scenes'      => self::build_scenes( self::rows( $settings['scenes'] ?? [] ) ),
			'layers'      => $layers,
			'keyframes'   => self::build_keyframes( self::rows( $settings['keyframes'] ?? [] ) ),
		];
	}

	/**
	 * Normalize a layer key into a stable `[a-z0-9_-]` identifier.
	 *
	 * @param mixed $value Raw key.
	 */
	public static function layer_key( $value, int $index ): string {
		$key = is_scalar( $value ) ? sanitize_key( str_replace( ' ', '-', trim( (string) $value ) ) ) : '';

		return '' !== $key ? $key : 'layer-' . ( $index + 1 );
	}

	/**
	 * Resolve where a layer lives: "world" moves with the 3D camera, "screen" stays fixed over it.
	 *
	 * @param mixed $value Raw layer space.
	 */
	public static function layer_space( $value, string $type ): string {
		$space = self::pick( $value, self::SPACES, 'auto' );

		if ( 'auto' === $space ) {
			return in_array( $type, [ 'text', 'shape', 'container' ], true ) ? 'screen' : 'world';
		}

		return $space;
	}

	/**
	 * @param mixed  $value   Raw value.
	 * @param string $default Fallback value.
	 */
	public static function pick( $value, array $allowed, string $default ): string {
		$value = is_scalar( $value ) ? (string) $value : '';

		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * @param array<int,array<string,mixed>> $rows Layer repeater rows.
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_layers( array $rows ): array {
		$layers = [];

		foreach ( $rows as $index => $row ) {
			$type  = self::pick( $row['layer_type'] ?? 'image', self::LAYER_TYPES, 'image' );
			$space = self::layer_space( $row['layer_space'] ?? 'auto', $type );

			$layers[] = [
				'key'    => self::layer_key( $row['layer_key'] ?? '', $index ),
				'type'   => $type,
				'space'  => $space,
				'z'      => 'screen' === $space ? 0.0 : self::num( self::slider_size( $row['depth'] ?? null ), 0.0, -3000.0, 900.0 ),
				'start'  => [
					'x'       => self::num( $row['start_x'] ?? 0, 0.0, -500.0, 500.0 ),
					'y'       => self::num( $row['start_y'] ?? 0, 0.0, -500.0, 500.0 ),
					'scale'   => self::num( $row['start_scale'] ?? 1, 1.0, 0.0, 20.0 ),
					'rotate'  => self::num( $row['start_rotate'] ?? 0, 0.0, -720.0, 720.0 ),
					'opacity' => self::num( $row['start_opacity'] ?? 1, 1.0, 0.0, 1.0 ),
					'blur'    => self::num( $row['start_blur'] ?? 0, 0.0, 0.0, 40.0 ),
				],
				'idle'   => [
					'type'   => self::pick( $row['idle_type'] ?? 'none', self::IDLE_TYPES, 'none' ),
					'amount' => self::num( $row['idle_amount'] ?? 10, 10.0, 0.0, 200.0 ),
					'speed'  => self::num( $row['idle_speed'] ?? 6, 6.0, 0.5, 60.0 ),
				],
				'wind'   => 'yes' === ( $row['layer_wind'] ?? '' ),
				'split'  => in_array( $type, [ 'text', 'container' ], true ) ? self::pick( $row['reveal_split'] ?? 'words', self::SPLIT_TYPES, 'words' ) : 'none',
				'reveal' => self::pick( $row['reveal_style'] ?? 'rise', self::REVEAL_STYLES, 'rise' ),
			];
		}

		return $layers;
	}

	/**
	 * @param array<int,array<string,mixed>> $rows Scene repeater rows.
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_scenes( array $rows ): array {
		$scenes = [];

		foreach ( $rows as $row ) {
			$scenes[] = [
				'length' => self::num( self::slider_size( $row['scene_length'] ?? null ), 100.0, 10.0, 600.0 ),
				'ease'   => self::ease( $row['scene_ease'] ?? 'power2.inOut', 'power2.inOut' ),
				'camera' => self::camera( $row, 'cam_', false ),
			];
		}

		return $scenes;
	}

	/**
	 * @param array<int,array<string,mixed>> $rows Keyframe repeater rows.
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_keyframes( array $rows ): array {
		$keyframes = [];

		foreach ( $rows as $index => $row ) {
			$props = [];
			foreach ( self::KEYFRAME_PROPS as $prop => $range ) {
				$value = self::optional_num( $row[ 'kf_' . $prop ] ?? '', $range[0], $range[1] );
				if ( null !== $value ) {
					$props[ $prop ] = $value;
				}
			}

			$reveal = 'yes' === ( $row['kf_reveal'] ?? '' );
			if ( empty( $props ) && ! $reveal ) {
				continue;
			}

			$at       = self::num( self::slider_size( $row['kf_at'] ?? null ), 0.0, 0.0, 100.0 ) / 100;
			$duration = self::num( self::slider_size( $row['kf_duration'] ?? null ), 100.0, 1.0, 100.0 ) / 100;

			$keyframes[] = [
				'layer'    => self::layer_key( $row['kf_layer'] ?? '', $index ),
				'scene'    => (int) self::num( $row['kf_scene'] ?? 1, 1.0, 1.0, 99.0 ),
				'at'       => $at,
				'duration' => min( $duration, 1 - $at ) > 0 ? min( $duration, 1 - $at ) : 0.01,
				'ease'     => self::ease( $row['kf_ease'] ?? 'power2.inOut', 'power2.inOut' ),
				'props'    => (object) $props,
				'reveal'   => $reveal,
			];
		}

		return $keyframes;
	}

	/**
	 * @param array<string,mixed> $source   Settings or repeater row.
	 * @param bool                $complete Fill missing values with zero (start camera) instead of omitting them.
	 * @return object
	 */
	private static function camera( array $source, string $prefix, bool $complete ): object {
		$camera = [];

		foreach ( self::CAMERA_PROPS as $prop => $range ) {
			$value = self::optional_num( $source[ $prefix . $prop ] ?? '', $range[0], $range[1] );
			if ( null === $value && $complete ) {
				$value = 0.0;
			}
			if ( null !== $value ) {
				$camera[ $prop ] = $value;
			}
		}

		return (object) $camera;
	}

	/**
	 * @param mixed $value Raw editor preview option.
	 */
	private static function editor_scene( $value ): int {
		if ( ! is_scalar( $value ) || '' === (string) $value || ! is_numeric( $value ) ) {
			return -1;
		}

		return (int) self::num( $value, -1.0, 0.0, 99.0 );
	}

	/**
	 * Accept only plain colour literals (hex, rgb[a], hsl[a]); anything else falls back.
	 *
	 * @param mixed $value Raw colour.
	 */
	private static function color( $value, string $default ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) || preg_match( '/^(rgb|hsl)a?\(\s*[0-9.,%\s\/]+\)$/', $value ) ) {
			return $value;
		}

		return $default;
	}

	/**
	 * @param mixed $value Raw ease.
	 */
	private static function ease( $value, string $default ): string {
		return self::pick( $value, array_keys( self::ease_options() ), $default );
	}

	/**
	 * @param mixed $value Raw repeater value.
	 * @return array<int,array<string,mixed>>
	 */
	private static function rows( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_filter( $value, 'is_array' ) );
	}

	/**
	 * @param mixed $value Elementor slider value or plain number.
	 * @return mixed
	 */
	private static function slider_size( $value ) {
		return is_array( $value ) ? ( $value['size'] ?? '' ) : $value;
	}

	/**
	 * @param mixed $value Raw numeric value.
	 */
	private static function num( $value, float $default, float $min, float $max ): float {
		if ( ! is_scalar( $value ) || '' === (string) $value || ! is_numeric( $value ) ) {
			return $default;
		}

		return round( max( $min, min( $max, (float) $value ) ), 4 );
	}

	/**
	 * @param mixed $value Raw numeric value; empty means "not set".
	 */
	private static function optional_num( $value, float $min, float $max ): ?float {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
			return null;
		}

		return round( max( $min, min( $max, (float) $value ) ), 4 );
	}
}
