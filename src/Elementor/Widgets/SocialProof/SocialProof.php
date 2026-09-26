<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SocialProof;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\SocialProof\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\SocialProof\Concerns\RegistersStyleControls;
use ApeironKit\Support\SocialProofSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SocialProof extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const ANIMATIONS = [ 'fade', 'slide-top', 'slide-bottom', 'slide-left', 'slide-right' ];
	private const POSITIONS  = [ 'top-right', 'top-left', 'bottom-right', 'bottom-left' ];
	private const POPUP      = '{{WRAPPER}} .apeiron-social-proof';
	private const CLOSE      = '{{WRAPPER}} .apeiron-social-proof__close';
	private const IMAGE      = '{{WRAPPER}} .apeiron-social-proof__image';
	private const TEXT       = '{{WRAPPER}} .apeiron-social-proof__text';

	public function get_name() {
		return 'apeiron-social-proof';
	}

	public function get_title() {
		return __( 'Aktivitas', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-notification';
	}

	public function get_keywords() {
		return [ 'social', 'proof', 'notification', 'popup', 'alert' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-social-proof';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-social-proof-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$element_data = method_exists( $this, 'get_data' ) ? $this->get_data() : [];
		$raw_settings = is_array( $element_data['settings'] ?? null ) ? $element_data['settings'] : $this->get_settings();
		$settings     = $this->resolve_dynamic_settings(
			(array) $this->get_settings_for_display(),
			is_array( $raw_settings ) ? $raw_settings : []
		);
		if ( 'yes' !== ( $settings['enable_popup'] ?? '' ) ) {
			return;
		}

		$global_settings = SocialProofSettings::get();
		$entries         = $this->prepare_entries( $global_settings['entries'] ?? [] );

		if ( empty( $entries ) ) {
			return;
		}

		$context = $this->build_render_context( $settings, $global_settings, $entries );

		$context = (array) apply_filters( 'apeiron_social_proof_render_context', $context, $settings, $this );

		require __DIR__ . '/Partials/social-proof.php';
	}

	private function resolve_dynamic_settings( array $settings, array $raw_settings ): array {
		$dynamic = is_array( $raw_settings['__dynamic__'] ?? null ) ? $raw_settings['__dynamic__'] : [];
		$value   = is_scalar( $settings['text_template'] ?? null ) ? sanitize_textarea_field( (string) $settings['text_template'] ) : '';

		if ( '' === trim( $value ) && array_key_exists( 'text_template', $dynamic ) ) {
			$raw_value = is_scalar( $raw_settings['text_template'] ?? null ) ? sanitize_textarea_field( (string) $raw_settings['text_template'] ) : '';
			$value     = '' !== trim( $raw_value ) ? $raw_value : '{name} telah membeli {product} pada:';
		}

		$settings['text_template'] = $value;
		if (
			! array_key_exists( 'popup_animation_duration', $raw_settings )
			&& is_array( $raw_settings['animation_duration'] ?? null )
		) {
			$settings['popup_animation_duration'] = $raw_settings['animation_duration'];
		}

		return $settings;
	}

	/**
	 * @param array<string,mixed> $settings
	 * @param array<string,mixed> $global_settings
	 * @param array<int,array<string,string>> $entries
	 * @return array<string,mixed>
	 */
	private function build_render_context( array $settings, array $global_settings, array $entries ): array {
		$dashboard_template = (string) ( $global_settings['text_template'] ?? SocialProofSettings::defaults()['text_template'] );
		$custom_template    = (string) ( $settings['text_template'] ?? '' );
		$text_template      = 'yes' === ( $settings['use_custom_template'] ?? '' ) && '' !== trim( $custom_template )
			? $custom_template
			: $dashboard_template;

		$animation_override = (string) ( $settings['animation_type'] ?? 'global' );
		$animation_type     = 'global' === $animation_override
			? $this->resolve_animation( (string) ( $global_settings['animation_type'] ?? '' ) )
			: $this->resolve_animation( $animation_override );
		$popup_position = 'yes' === ( $settings['override_position'] ?? '' )
			? (string) ( $settings['popup_position'] ?? '' )
			: (string) ( $global_settings['popup_position'] ?? '' );
		$popup_position = $this->resolve_position( $popup_position );
		$animation_duration = isset( $settings['popup_animation_duration']['size'] ) && '' !== $settings['popup_animation_duration']['size']
			? max( 100, min( 2000, (int) round( (float) $settings['popup_animation_duration']['size'] * 1000 ) ) )
			: 400;
		$position_map   = [
			'top-right'    => [ 'right', 'top' ],
			'top-left'     => [ 'left', 'top' ],
			'bottom-right' => [ 'right', 'bottom' ],
			'bottom-left'  => [ 'left', 'bottom' ],
		];

		return [
			'entries'              => $entries,
			'text_template'        => $text_template,
			'initial_delay'        => max( 0, absint( $global_settings['initial_delay'] ?? 0 ) * 1000 ),
			'display_duration'     => max( 1000, absint( $global_settings['display_duration'] ?? 3 ) * 1000 ),
			'interval_duration'    => max( 1000, absint( $global_settings['interval_duration'] ?? 8 ) * 1000 ),
			'max_notifications'    => max( 0, absint( $global_settings['max_notifications'] ?? 0 ) ),
			'image_radius'         => max( 0, min( 50, absint( $global_settings['image_border_radius'] ?? 10 ) ) ),
			'animation_type'       => $animation_type,
			'animation_duration'   => $animation_duration,
			'box_shadow_enabled'    => 'yes' === ( $settings['popup_box_shadow_box_shadow_type'] ?? 'yes' ),
			'position_horizontal'  => $position_map[ $popup_position ][0],
			'position_vertical'    => $position_map[ $popup_position ][1],
			'instance_id'          => $this->get_id(),
		];
	}

	/** @return array<int,array<string,string>> */
	private function prepare_entries( $entries ): array {
		if ( ! is_array( $entries ) ) {
			return [];
		}

		$prepared = [];
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$name     = sanitize_text_field( (string) ( $entry['name'] ?? '' ) );
			$product  = sanitize_text_field( (string) ( $entry['product'] ?? '' ) );
			$datetime = sanitize_text_field( (string) ( $entry['datetime'] ?? '' ) );
			$date     = '' !== $datetime ? date_create_immutable( $datetime, wp_timezone() ) : false;
			if ( '' === $name || '' === $product || false === $date ) {
				continue;
			}

			$prepared[] = [
				'name'     => $name,
				'product'  => $product,
				'image'    => esc_url_raw( (string) ( $entry['image'] ?? '' ) ),
				'datetime' => $date->format( DATE_ATOM ),
			];
		}

		return $prepared;
	}

	private function resolve_animation( string $animation ): string {
		return in_array( $animation, self::ANIMATIONS, true ) ? $animation : 'slide-top';
	}

	private function resolve_position( string $position ): string {
		return in_array( $position, self::POSITIONS, true ) ? $position : 'bottom-right';
	}

}
