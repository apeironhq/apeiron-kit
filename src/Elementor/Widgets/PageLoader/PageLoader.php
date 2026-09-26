<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\PageLoader;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\PageLoader\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\PageLoader\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PageLoader extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const LOADER_STYLES     = [ 'default', 'coffee', 'water' ];
	private const ENTRANCE_ANIMS    = [ 'none', 'fade', 'fade_scale', 'slide_up', 'soft_blur' ];
	private const EXIT_ANIMS        = [ 'none', 'fade', 'fade_scale', 'slide_up', 'curtain', 'soft_blur' ];
	private const DEFAULT_STORAGE_KEY = 'apeiron_page_loader_seen';

	private const DURATION_MIN = 300;
	private const DURATION_MAX = 12000;
	private const DELAY_MIN    = 0;
	private const DELAY_MAX    = 4000;
	private const TIMEOUT_MIN  = 2000;
	private const TIMEOUT_MAX  = 20000;

	public function get_name() {
		return 'apeiron-page-loader';
	}

	public function get_title() {
		return __( 'Pemuat Halaman', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-loader';
	}

	public function get_keywords() {
		return [ 'loader', 'preloader', 'page loader', 'transition', 'loading', 'apeiron' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-page-loader';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-page-loader-js';

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
		$settings     = $this->resolve_dynamic_content_settings(
			is_array( $settings ) ? $settings : [],
			is_array( $raw_settings ) ? $raw_settings : []
		);
		$is_editor  = $this->is_elementor_editor_preview();
		$element_id = 'apeiron-page-loader-' . $this->get_id();

		$this->prepare_render_attribute( $element_id, $settings, $is_editor );

		$default_context = [
			'render_attributes' => $this->get_render_attribute_string( 'page_loader' ),
			'loader_style'      => $this->sanitize_choice( (string) ( $settings['loader_style'] ?? 'default' ), self::LOADER_STYLES, 'default' ),
			'intro_text'        => $settings['intro_text'],
			'main_text'         => $settings['main_text'],
			'loading_text'      => $settings['loading_text'],
			'secondary_text'    => $settings['secondary_text'],
			'show_percentage'   => 'yes' === ( $settings['show_percentage'] ?? 'yes' ),
		];

		/**
		 * Filter the render context before markup is emitted.
		 *
		 * @since 1.1.0
		 *
		 * @param array $context  Render context passed to the partial.
		 * @param array $settings Full settings array.
		 * @param self  $widget   Widget instance.
		 */
		$context = array_replace(
			$default_context,
			(array) apply_filters(
				'apeiron_page_loader_render_context',
				$default_context,
				$settings,
				$this
			)
		);

		$this->render_page_loader( $context );
	}

	private function resolve_dynamic_content_settings( array $settings, array $raw_settings ): array {
		$dynamic = is_array( $raw_settings['__dynamic__'] ?? null ) ? $raw_settings['__dynamic__'] : [];
		$defaults = [
			'intro_text'     => __( 'The Wedding of', 'apeiron-kit' ),
			'main_text'      => 'RK',
			'loading_text'   => __( 'Menyiapkan halaman', 'apeiron-kit' ),
			'secondary_text' => __( 'Rania & Kenny', 'apeiron-kit' ),
		];

		foreach ( $defaults as $key => $default ) {
			$value = is_scalar( $settings[ $key ] ?? null ) ? trim( (string) $settings[ $key ] ) : '';
			if ( '' === $value && ! empty( $dynamic[ $key ] ) ) {
				$value = is_scalar( $raw_settings[ $key ] ?? null ) ? trim( (string) $raw_settings[ $key ] ) : '';
				$value = '' !== $value ? $value : $default;
			}
			$settings[ $key ] = $value;
		}

		return $settings;
	}

	private function prepare_render_attribute( string $element_id, array $settings, bool $is_editor ): void {
		$loader_style = $this->sanitize_choice( (string) ( $settings['loader_style'] ?? 'default' ), self::LOADER_STYLES, 'default' );

		$classes = [
			'apeiron-page-loader',
			'apeiron-page-loader--display-fullscreen',
			'apeiron-page-loader--style-' . $loader_style,
			'apeiron-page-loader--fullscreen',
		];

		if ( $is_editor ) {
			$classes[] = 'is-editor-preview';
		}
		if ( 'yes' === ( $settings['glassmorphism'] ?? '' ) ) {
			$classes[] = 'has-glass';
		}
		if ( 'yes' === ( $settings['blur_background'] ?? '' ) ) {
			$classes[] = 'has-backdrop-blur';
		}

		$this->add_render_attribute(
			'page_loader',
			[
				'id'                      => $element_id,
				'class'                   => $classes,
				'role'                    => 'status',
				'aria-live'               => 'polite',
				'aria-busy'               => $is_editor ? 'false' : 'true',
				'data-apeiron-page-loader' => 'yes',
				'data-lock-scroll'        => 'yes',
				'data-duration'           => (string) $this->get_slider_int( $settings, 'loader_duration', 7600, self::DURATION_MIN, self::DURATION_MAX ),
				'data-max-runtime'        => (string) $this->get_slider_int( $settings, 'maximum_timeout', 14000, self::TIMEOUT_MIN, self::TIMEOUT_MAX ),
				'data-custom-delay'       => (string) $this->get_slider_int( $settings, 'custom_delay', 0, self::DELAY_MIN, self::DELAY_MAX ),
				'data-progress-based'     => 'yes' === ( $settings['progress_based'] ?? 'yes' ) ? 'yes' : 'no',
				'data-loader-style'       => $loader_style,
				'data-first-visit-only'   => 'yes' === ( $settings['first_visit_only'] ?? '' ) ? 'yes' : 'no',
				'data-storage-key'        => $this->sanitize_storage_key( $settings['storage_key'] ?? self::DEFAULT_STORAGE_KEY ),
				'data-entrance-animation' => $this->sanitize_choice( (string) ( $settings['entrance_animation'] ?? 'fade_scale' ), self::ENTRANCE_ANIMS, 'fade_scale' ),
				'data-exit-animation'     => $this->sanitize_choice( (string) ( $settings['exit_animation'] ?? 'fade_scale' ), self::EXIT_ANIMS, 'fade_scale' ),
				'data-editor-preview'     => $is_editor ? 'yes' : 'no',
				'data-show-desktop'       => 'yes' === ( $settings['show_desktop'] ?? 'yes' ) ? 'yes' : 'no',
				'data-show-tablet'        => 'yes' === ( $settings['show_tablet'] ?? 'yes' ) ? 'yes' : 'no',
				'data-show-mobile'        => 'yes' === ( $settings['show_mobile'] ?? 'yes' ) ? 'yes' : 'no',
			]
		);
	}

	private function render_page_loader( array $context ): void {
		$render_attributes = (string) $context['render_attributes'];
		$loader_style      = (string) $context['loader_style'];
		$intro_text        = (string) $context['intro_text'];
		$main_text         = (string) $context['main_text'];
		$loading_text      = (string) $context['loading_text'];
		$secondary_text    = (string) $context['secondary_text'];
		$show_percentage   = (bool) $context['show_percentage'];

		require __DIR__ . '/Partials/page-loader.php';
	}

	private function sanitize_choice( string $value, array $allowed, string $fallback ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private function get_slider_int( array $settings, string $key, int $fallback, int $min, int $max ): int {
		$raw   = $settings[ $key ]['size'] ?? $fallback;
		$value = is_numeric( $raw ) ? (int) round( (float) $raw ) : $fallback;

		return max( $min, min( $max, $value ) );
	}

	private function sanitize_storage_key( $key ): string {
		$key = is_scalar( $key ) ? sanitize_key( (string) $key ) : '';
		if ( '' === $key ) {
			return self::DEFAULT_STORAGE_KEY;
		}

		return 0 === strpos( $key, 'apeiron_page_loader_' ) ? $key : 'apeiron_page_loader_' . $key;
	}

}
