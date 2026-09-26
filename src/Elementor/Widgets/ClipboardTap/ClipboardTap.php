<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\ClipboardTap;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\ClipboardTap\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\ClipboardTap\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ClipboardTap extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const FEEDBACK_DURATION_MIN     = 800;
	private const FEEDBACK_DURATION_MAX     = 5000;
	private const FEEDBACK_DURATION_DEFAULT = 1800;

	public function get_name() {
		return 'apeiron-clipboard-tap';
	}

	public function get_title() {
		return __( 'Tombol Salin', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-clipboard';
	}

	public function get_keywords() {
		return [ 'copy', 'clipboard', 'salin', 'rekening', 'button' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-clipboard-tap';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-clipboard-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$element_data = method_exists( $this, 'get_data' ) ? $this->get_data() : [];
		$raw_settings = is_array( $element_data['settings'] ?? null ) ? $element_data['settings'] : $this->get_settings();
		$settings     = $this->get_settings_for_display();
		$settings     = $this->resolve_dynamic_fallbacks(
			is_array( $settings ) ? $settings : [],
			is_array( $raw_settings ) ? $raw_settings : []
		);
		$copy_source = (string) ( $settings['copy_source'] ?? 'manual' );

		$value = CopyValueResolver::resolve( $settings, $copy_source );

		/**
		 * Filter the resolved copy value before it is emitted to the browser.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value    Text that will be copied on click.
		 * @param array  $settings Full settings array.
		 * @param self   $widget   Widget instance.
		 */
		$value = (string) apply_filters(
			'apeiron_clipboard_copy_value',
			$value,
			$settings,
			$this
		);

		$context = [
			'settings'            => $settings,
			'copy_source'         => $copy_source,
			'value'               => $value,
			'button_text'         => $this->get_text_setting( $settings, 'button_text', __( 'Salin No. Rekening', 'apeiron-kit' ) ),
			'success_message'     => $this->get_text_setting( $settings, 'success_message', __( 'Berhasil disalin', 'apeiron-kit' ) ),
			'empty_message'       => $this->get_text_setting( $settings, 'empty_message', __( 'Tidak ada teks untuk disalin', 'apeiron-kit' ) ),
			'error_message'       => $this->get_text_setting( $settings, 'error_message', __( 'Gagal menyalin. Silakan coba lagi', 'apeiron-kit' ) ),
			'invalid_url_message' => $this->get_text_setting( $settings, 'invalid_url_message', __( 'URL tidak valid', 'apeiron-kit' ) ),
			'feedback_duration'   => $this->get_feedback_duration( $settings ),
			'icon_html'           => $this->render_icon_html( $settings ),
		];

		/**
		 * Filter the full render context before markup is emitted.
		 *
		 * @since 1.1.0
		 *
		 * @param array $context  Render context.
		 * @param array $settings Full settings array.
		 * @param self  $widget   Widget instance.
		 */
		$context = (array) apply_filters(
			'apeiron_clipboard_render_context',
			$context,
			$settings,
			$this
		);

		$copy_source         = (string) ( $context['copy_source'] ?? 'manual' );
		$value               = (string) ( $context['value'] ?? '' );
		$button_text         = (string) ( $context['button_text'] ?? '' );
		$success_message     = (string) ( $context['success_message'] ?? '' );
		$empty_message       = (string) ( $context['empty_message'] ?? '' );
		$error_message       = (string) ( $context['error_message'] ?? '' );
		$invalid_url_message = (string) ( $context['invalid_url_message'] ?? '' );
		$feedback_duration   = max(
			self::FEEDBACK_DURATION_MIN,
			min( self::FEEDBACK_DURATION_MAX, (int) ( $context['feedback_duration'] ?? self::FEEDBACK_DURATION_DEFAULT ) )
		);
		$icon_html           = self::sanitize_icon_html( (string) ( $context['icon_html'] ?? '' ) );

		require __DIR__ . '/Partials/clipboard-tap.php';
	}

	private function resolve_dynamic_fallbacks( array $settings, array $raw_settings ): array {
		$dynamic = is_array( $raw_settings['__dynamic__'] ?? null ) ? $raw_settings['__dynamic__'] : [];
		$defaults = [
			'value'               => '1234567890',
			'custom_url'          => [],
			'shortcode_content'   => '',
			'button_text'         => __( 'Salin No. Rekening', 'apeiron-kit' ),
			'success_message'     => __( 'Berhasil disalin', 'apeiron-kit' ),
			'empty_message'       => __( 'Tidak ada teks untuk disalin', 'apeiron-kit' ),
			'invalid_url_message' => __( 'URL tidak valid', 'apeiron-kit' ),
			'error_message'       => __( 'Gagal menyalin. Silakan coba lagi', 'apeiron-kit' ),
		];

		foreach ( $defaults as $key => $default ) {
			if ( array_key_exists( $key, $dynamic ) && $this->is_empty_dynamic_value( $settings[ $key ] ?? null ) ) {
				$settings[ $key ] = $default;
			}
		}

		return $settings;
	}

	private function is_empty_dynamic_value( $value ): bool {
		if ( is_array( $value ) ) {
			return '' === trim( (string) ( $value['url'] ?? '' ) );
		}

		return ! is_scalar( $value ) || '' === trim( (string) $value );
	}

	private function get_text_setting( array $settings, string $key, string $default ): string {
		return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : $default;
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private function get_feedback_duration( array $settings ): int {
		$raw = isset( $settings['feedback_duration']['size'] ) ? absint( $settings['feedback_duration']['size'] ) : self::FEEDBACK_DURATION_DEFAULT;

		return max( self::FEEDBACK_DURATION_MIN, min( self::FEEDBACK_DURATION_MAX, $raw ) );
	}

	/**
	 * Filtered context may replace `icon_html`, so sanitize it before unescaped output.
	 *
	 * @param array<string,mixed> $settings
	 */
	private function render_icon_html( array $settings ): string {
		$icon = $settings['selected_icon'] ?? null;

		if ( ! is_array( $icon ) || empty( $icon['value'] ) ) {
			return '';
		}

		ob_start();
		\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );

		return self::sanitize_icon_html( (string) ob_get_clean() );
	}

	private static function sanitize_icon_html( string $html ): string {
		$svg_attributes = [
			'aria-hidden'     => true,
			'class'           => true,
			'fill'            => true,
			'focusable'       => true,
			'height'          => true,
			'role'            => true,
			'stroke'          => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'stroke-width'    => true,
			'viewbox'         => true,
			'width'           => true,
			'xmlns'           => true,
		];

		return wp_kses(
			$html,
			[
				'i'        => [ 'aria-hidden' => true, 'class' => true ],
				'svg'      => $svg_attributes,
				'g'        => $svg_attributes,
				'path'     => array_merge( $svg_attributes, [ 'd' => true ] ),
				'line'     => array_merge( $svg_attributes, [ 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true ] ),
				'circle'   => array_merge( $svg_attributes, [ 'cx' => true, 'cy' => true, 'r' => true ] ),
				'polyline' => array_merge( $svg_attributes, [ 'points' => true ] ),
				'polygon'  => array_merge( $svg_attributes, [ 'points' => true ] ),
				'use'      => [ 'href' => true, 'xlink:href' => true ],
			]
		);
	}
}
