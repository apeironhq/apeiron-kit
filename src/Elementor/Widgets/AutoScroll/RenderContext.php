<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\AutoScroll;

use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and normalizes the Auto Scroll frontend contract.
 */
final class RenderContext {

	private const SPEED_MIN = 1;
	private const SPEED_MAX = 100;
	private const DEFAULT_SPEED = 30;

	private const BASE_POSITION_MAP = [
		'bottom-right'  => [ 'v' => 'bottom', 'h' => 'right' ],
		'bottom-left'   => [ 'v' => 'bottom', 'h' => 'left' ],
		'bottom-center' => [ 'v' => 'bottom', 'h' => 'center' ],
		'right-center'  => [ 'v' => 'center', 'h' => 'right' ],
		'left-center'   => [ 'v' => 'center', 'h' => 'left' ],
	];

	private const POSITION_VERTICAL = [ 'top', 'center', 'bottom' ];
	private const POSITION_HORIZONTAL = [ 'left', 'center', 'right' ];

	private const CONFIG_ENUMS = [
		'scrollEngine'                   => [ 'apeiron', 'step' ],
		'mode'                           => [ 'auto', 'manual', 'both' ],
		'userInteractionBehavior'        => [ 'continue', 'stop_on_interaction' ],
		'direction'                      => [ 'down', 'up' ],
		'smoothness'                     => [ 'normal', 'smooth', 'ultra' ],
		'motionProfile'                  => [ 'legacy', 'steady', 'kinetic', 'hand' ],
		'easing'                         => [ 'linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out' ],
		'activeAnimation'                => [ 'none', 'pulse-soft', 'scale-breathing', 'micro-bounce', 'smooth-rotate', 'glow-ring', 'orbit-ring', 'ripple-wave' ],
		'speedValueAnimation'            => [ 'none', 'pulse', 'fade', 'slide', 'bounce' ],
		'buttonAppearAnimation'          => [ 'none', 'fade', 'slide', 'scale', 'bounce', 'zoom', 'flip', 'elastic' ],
		'endNotificationAnimation'       => [ 'fade', 'slide', 'scale', 'bounce', 'zoom', 'flip' ],
		'speedControlAppearAnimation'    => [ 'fade', 'slide', 'scale', 'bounce', 'zoom', 'flip', 'elastic', 'slide-up', 'slide-down' ],
		'speedControlDisappearAnimation' => [ 'fade', 'slide', 'scale', 'bounce', 'zoom', 'flip', 'elastic', 'slide-up', 'slide-down' ],
		'progressAnimation'              => [ 'none', 'linear-clean', 'smooth-fill', 'wave-stroke', 'rotating-stroke', 'elastic-stroke' ],
	];

	private const CONFIG_ENUM_DEFAULTS = [
		'scrollEngine'                   => 'apeiron',
		'mode'                           => 'auto',
		'userInteractionBehavior'        => 'continue',
		'direction'                      => 'down',
		'smoothness'                     => 'ultra',
		'motionProfile'                  => 'steady',
		'easing'                         => 'linear',
		'activeAnimation'                => 'pulse-soft',
		'speedValueAnimation'            => 'pulse',
		'buttonAppearAnimation'          => 'fade',
		'endNotificationAnimation'       => 'fade',
		'speedControlAppearAnimation'    => 'scale',
		'speedControlDisappearAnimation' => 'scale',
		'progressAnimation'              => 'none',
	];

	private const CONFIG_BOOLEANS = [
		'autoStart',
		'pauseOnInteraction',
		'resumeAfterIdle',
		'pauseOnHover',
		'loopScroll',
		'disableOnIOS',
		'showSpeedControl',
		'speedDraggable',
		'showScrollTop',
		'showEndNotification',
		'rippleEnabled',
		'isEditor',
	];

	private const CONFIG_BOOLEAN_DEFAULTS = [
		'autoStart'           => true,
		'pauseOnInteraction'  => true,
		'resumeAfterIdle'     => true,
		'pauseOnHover'        => true,
		'loopScroll'          => false,
		'disableOnIOS'        => false,
		'showSpeedControl'    => false,
		'speedDraggable'      => false,
		'showScrollTop'       => true,
		'showEndNotification' => true,
		'rippleEnabled'       => false,
		'isEditor'            => false,
	];

	private const FALLBACK_MINUS_SVG = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" stroke="currentColor" fill="none" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12" /></svg>';
	private const FALLBACK_PLUS_SVG  = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" stroke="currentColor" fill="none" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>';
	private const FALLBACK_TOP_ICON  = '<i class="fas fa-chevron-up"></i>';

	/**
	 * @param array<string,mixed>|null $icon
	 */
	public static function render_icon( ?array $icon ): string {
		if ( empty( $icon ) || empty( $icon['value'] ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );

		return self::sanitize_icon_html( (string) ob_get_clean() );
	}

	/**
	 * @param array<string,mixed>|null $icon
	 */
	public static function render_icon_with_fallback( ?array $icon, string $fallback ): string {
		$html = self::render_icon( $icon );

		return '' === trim( $html ) ? self::sanitize_icon_html( $fallback ) : $html;
	}

	/**
	 * Sanitize Elementor and fallback icon markup with an SVG-aware allowlist.
	 */
	public static function sanitize_icon_html( string $html ): string {
		if ( ! function_exists( 'wp_kses' ) ) {
			return $html;
		}

		$common_svg_attributes = [
			'aria-hidden'       => true,
			'class'             => true,
			'fill'              => true,
			'focusable'         => true,
			'height'            => true,
			'role'              => true,
			'stroke'            => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-width'      => true,
			'viewbox'           => true,
			'width'             => true,
			'xmlns'             => true,
		];

		return wp_kses(
			$html,
			[
				'i'        => [ 'aria-hidden' => true, 'class' => true ],
				'svg'      => $common_svg_attributes,
				'g'        => $common_svg_attributes,
				'path'     => array_merge( $common_svg_attributes, [ 'd' => true ] ),
				'line'     => array_merge( $common_svg_attributes, [ 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true ] ),
				'circle'   => array_merge( $common_svg_attributes, [ 'cx' => true, 'cy' => true, 'r' => true ] ),
				'polyline' => array_merge( $common_svg_attributes, [ 'points' => true ] ),
				'polygon'  => array_merge( $common_svg_attributes, [ 'points' => true ] ),
				'use'      => [ 'href' => true, 'xlink:href' => true ],
			]
		);
	}

	public static function fallback_minus(): string {
		return self::FALLBACK_MINUS_SVG;
	}

	public static function fallback_plus(): string {
		return self::FALLBACK_PLUS_SVG;
	}

	public static function fallback_top(): string {
		return self::FALLBACK_TOP_ICON;
	}

	/**
	 * Resolve the position class (e.g. `pos-bottom-right`) from a settings array.
	 *
	 * @param array<string,mixed> $settings
	 */
	public static function resolve_position_class( array $settings ): string {
		$base       = (string) ( $settings['button_position'] ?? 'bottom-right' );
		$axes       = self::BASE_POSITION_MAP[ $base ] ?? self::BASE_POSITION_MAP['bottom-right'];
		$position_v = self::enum_value(
			$settings['widget_position_vertical'] ?? $axes['v'],
			self::POSITION_VERTICAL,
			$axes['v']
		);
		$position_h = self::enum_value(
			$settings['widget_position_horizontal'] ?? $axes['h'],
			self::POSITION_HORIZONTAL,
			$axes['h']
		);

		return 'pos-' . $position_v . '-' . $position_h;
	}

	/**
	 * Extract the resolved scroll speed (numeric value clamped to 1..100).
	 *
	 * @param array<string,mixed> $settings
	 */
	public static function resolve_speed( array $settings ): int {
		$engine = (string) ( $settings['scroll_engine'] ?? 'apeiron' );
		$key    = 'step' === $engine ? 'step_scroll_speed' : 'scroll_speed';
		$size   = $settings[ $key ]['size'] ?? self::DEFAULT_SPEED;
		$size   = is_numeric( $size ) ? $size : self::DEFAULT_SPEED;

		return (int) max( self::SPEED_MIN, min( self::SPEED_MAX, (int) $size ) );
	}

	/**
	 * Build the JS config array serialised into `data-config` for the frontend runtime.
	 *
	 * Icons and editor state are resolved upstream and passed through `$runtime`.
	 *
	 * @param array<string,mixed> $settings
	 * @param array<string,mixed> $runtime Keys: iconStart, iconStop, isEditor.
	 * @return array<string,mixed>
	 */
	public static function build_config( array $settings, array $runtime ): array {
		$config = [
			'scrollEngine'                    => (string) ( $settings['scroll_engine'] ?? 'apeiron' ),
			'mode'                            => (string) ( $settings['scroll_mode'] ?? 'auto' ),
			'userInteractionBehavior'         => (string) ( $settings['user_interaction_behavior'] ?? 'continue' ),
			'direction'                       => (string) ( $settings['scroll_direction'] ?? 'down' ),
			'speed'                           => self::resolve_speed( $settings ),
			'smoothness'                      => (string) ( $settings['smoothness'] ?? 'ultra' ),
			'motionProfile'                   => 'steady',
			'motionGlide'                     => 48,
			'motionResponsiveness'            => 78,
			'easing'                          => 'linear',
			'stepScrollRange'                 => self::slider_value( $settings, 'step_scroll_range', 275 ),
			'stepScrollInterval'              => self::slider_value( $settings, 'step_scroll_interval', 2 ) * 1000,
			'stepScrollDuration'              => self::slider_value( $settings, 'step_scroll_duration', 1.25 ) * 1000,
			'autoStart'                       => 'yes' === ( $settings['auto_start'] ?? 'yes' ),
			'autoStartDelay'                  => self::slider_value( $settings, 'auto_start_delay', 2 ) * 1000,
			'pauseOnInteraction'              => 'yes' === ( $settings['pause_on_interaction'] ?? 'yes' ),
			'resumeAfterIdle'                 => 'yes' === ( $settings['resume_after_idle'] ?? 'yes' ),
			'resumeIdleDelay'                 => self::slider_value( $settings, 'resume_idle_delay', 1.2 ) * 1000,
			'pauseOnHover'                    => 'yes' === ( $settings['pause_on_hover'] ?? 'yes' ),
			'loopScroll'                      => 'yes' === ( $settings['loop_scroll'] ?? '' ),
			'disableOnIOS'                    => 'yes' === ( $settings['disable_on_ios'] ?? '' ),
			'showSpeedControl'                => 'yes' === ( $settings['show_speed_control'] ?? '' ),
			'speedDraggable'                  => 'yes' === ( $settings['speed_control_draggable'] ?? '' ),
			'showScrollTop'                   => 'yes' === ( $settings['show_scroll_top'] ?? 'yes' ),
			'scrollTopShowAfter'              => self::slider_value( $settings, 'scroll_top_show_after', 20 ) / 100,
			'buttonStartLabel'                => __( 'Mulai Auto Scroll', 'apeiron-kit' ),
			'buttonStopLabel'                 => __( 'Berhenti Scroll', 'apeiron-kit' ),
			'pausedLabel'                     => __( 'Auto Scroll dijeda', 'apeiron-kit' ),

			'iconStart'                       => (string) ( $runtime['iconStart'] ?? '' ),
			'iconStop'                        => (string) ( $runtime['iconStop'] ?? '' ),
			'activeAnimation'                 => (string) ( $settings['button_active_animation'] ?? 'pulse-soft' ),
			'speedValueAnimation'             => (string) ( $settings['speed_value_animation_type'] ?? 'pulse' ),
			'buttonAppearAnimation'           => (string) ( $settings['button_appear_animation'] ?? 'fade' ),
			'buttonAppearDelay'               => self::slider_value( $settings, 'button_appear_animation_delay', 0 ) * 1000,
			'speedControlShowAnimation'       => (string) ( $settings['speed_control_show_animation'] ?? 'yes' ),
			'speedControlAppearAnimation'     => (string) ( $settings['speed_control_appear_animation'] ?? 'scale' ),
			'speedControlDisappearAnimation'  => (string) ( $settings['speed_control_disappear_animation'] ?? 'scale' ),
			'speedControlAnimationDuration'   => self::slider_value( $settings, 'speed_control_animation_duration', 0.4 ) * 1000,
			'progressAnimation'               => (string) ( $settings['progress_animation_type'] ?? 'none' ),
			'rippleEnabled'                   => 'yes' === ( $settings['ripple_enable'] ?? '' ),
			'isEditor'                        => ! empty( $runtime['isEditor'] ),
		];

		return self::normalize_config( $config, $config );
	}

	/**
	 * Normalize filterable config while preserving extension-specific keys.
	 *
	 * @param array<string,mixed> $config
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public static function normalize_config( array $config, array $defaults = [] ): array {
		$normalized = array_replace( $defaults, $config );

		foreach ( self::CONFIG_ENUMS as $key => $allowed ) {
			$canonical_default  = self::CONFIG_ENUM_DEFAULTS[ $key ] ?? $allowed[0];
			$fallback           = self::enum_value( $defaults[ $key ] ?? $canonical_default, $allowed, $canonical_default );
			$normalized[ $key ] = self::enum_value( $normalized[ $key ] ?? $fallback, $allowed, $fallback );
		}

		foreach ( self::CONFIG_BOOLEANS as $key ) {
			$fallback           = $defaults[ $key ] ?? self::CONFIG_BOOLEAN_DEFAULTS[ $key ] ?? false;
			$normalized[ $key ] = self::boolean_value( $normalized[ $key ] ?? $fallback );
		}

		unset( $normalized['showTooltip'], $normalized['tooltipStart'], $normalized['tooltipStop'] );

		$normalized['speed']                         = self::clamp_number( $normalized['speed'] ?? self::DEFAULT_SPEED, self::SPEED_MIN, self::SPEED_MAX, true, $defaults['speed'] ?? self::DEFAULT_SPEED );
		$normalized['motionGlide']                   = self::clamp_number( $normalized['motionGlide'] ?? 48, 0, 100, true, $defaults['motionGlide'] ?? 48 );
		$normalized['motionResponsiveness']          = self::clamp_number( $normalized['motionResponsiveness'] ?? 78, 0, 100, true, $defaults['motionResponsiveness'] ?? 78 );
		$normalized['stepScrollRange']               = self::clamp_number( $normalized['stepScrollRange'] ?? 275, 60, 2000, true, $defaults['stepScrollRange'] ?? 275 );
		$normalized['stepScrollInterval']            = self::clamp_number( $normalized['stepScrollInterval'] ?? 2000, 160, 30000, true, $defaults['stepScrollInterval'] ?? 2000 );
		$normalized['stepScrollDuration']            = self::clamp_number( $normalized['stepScrollDuration'] ?? 1250, 120, 10000, true, $defaults['stepScrollDuration'] ?? 1250 );
		$normalized['autoStartDelay']                = self::clamp_number( $normalized['autoStartDelay'] ?? 2000, 0, 60000, true, $defaults['autoStartDelay'] ?? 2000 );
		$normalized['resumeIdleDelay']               = self::clamp_number( $normalized['resumeIdleDelay'] ?? 1200, 150, 60000, true, $defaults['resumeIdleDelay'] ?? 1200 );
		$normalized['scrollTopShowAfter']            = self::clamp_number( $normalized['scrollTopShowAfter'] ?? 0.2, 0, 1, false, $defaults['scrollTopShowAfter'] ?? 0.2 );
		$normalized['buttonAppearDelay']             = self::clamp_number( $normalized['buttonAppearDelay'] ?? 0, 0, 60000, true, $defaults['buttonAppearDelay'] ?? 0 );
		$normalized['speedControlAnimationDuration'] = self::clamp_number( $normalized['speedControlAnimationDuration'] ?? 400, 0, 10000, true, $defaults['speedControlAnimationDuration'] ?? 400 );
		$normalized['speedControlShowAnimation']     = self::boolean_value( $normalized['speedControlShowAnimation'] ?? true ) ? 'yes' : 'no';

		foreach ( [ 'buttonStartLabel', 'buttonStopLabel', 'pausedLabel', 'endNotificationText' ] as $key ) {
			$value              = (string) ( $normalized[ $key ] ?? '' );
			$normalized[ $key ] = function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : $value;
		}

		$normalized['iconStart'] = self::sanitize_icon_html( (string) ( $normalized['iconStart'] ?? '' ) );
		$normalized['iconStop']  = self::sanitize_icon_html( (string) ( $normalized['iconStop'] ?? '' ) );

		return $normalized;
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function slider_value( array $settings, string $key, float $fallback ): float {
		$value = $settings[ $key ]['size'] ?? $fallback;

		return is_numeric( $value ) ? (float) $value : $fallback;
	}

	/**
	 * @param array<int,string> $allowed
	 */
	private static function enum_value( $value, array $allowed, string $fallback ): string {
		$value = (string) $value;

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private static function boolean_value( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true );
	}

	/**
	 * @return int|float
	 */
	private static function clamp_number( $value, float $min, float $max, bool $integer = false, $fallback = null ) {
		$number = is_numeric( $value ) ? (float) $value : ( is_numeric( $fallback ) ? (float) $fallback : $min );
		$number = max( $min, min( $max, $number ) );

		return $integer ? (int) round( $number ) : $number;
	}
}
