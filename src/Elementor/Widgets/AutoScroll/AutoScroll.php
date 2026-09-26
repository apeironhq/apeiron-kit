<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\AutoScroll;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\AutoScroll\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\AutoScroll\Concerns\RegistersStyleControls;
use ApeironKit\Elementor\Widgets\AutoScroll\RenderContext;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AutoScroll extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	public function get_name() {
		return 'apeiron-autoscroll';
	}

	public function get_title() {
		return __( 'Gulir Otomatis', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-scroll';
	}

	public function get_keywords() {
		return [ 'scroll', 'auto', 'navigation', 'button', 'page scroll', 'smooth' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-autoscroll';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-autoscroll-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings  = $this->get_settings_for_display();
		$widget_id = (string) $this->get_id();

		$icon_start      = RenderContext::render_icon( $settings['button_icon_start'] ?? null );
		$icon_stop       = RenderContext::render_icon( $settings['button_icon_stop'] ?? null );
		$icon_minus      = RenderContext::render_icon_with_fallback( $settings['speed_arrow_minus_icon'] ?? null, RenderContext::fallback_minus() );
		$icon_plus       = RenderContext::render_icon_with_fallback( $settings['speed_arrow_plus_icon'] ?? null, RenderContext::fallback_plus() );
		$icon_scroll_top = RenderContext::render_icon_with_fallback( $settings['scroll_top_icon'] ?? null, RenderContext::fallback_top() );

		$config = RenderContext::build_config(
			$settings,
			[
				'iconStart' => $icon_start,
				'iconStop'  => $icon_stop,
				'isEditor'  => $this->is_elementor_editor_preview(),
			]
		);
		$default_config = $config;

		/**
		 * Filter the JS runtime config before it is JSON encoded.
		 *
		 * @since 1.0.5
		 *
		 * @param array<string,mixed> $config   Runtime config for the frontend JS.
		 * @param array<string,mixed> $settings Full settings array.
		 * @param self                $widget   Widget instance.
		 */
		$config = RenderContext::normalize_config(
			(array) apply_filters( 'apeiron_autoscroll_config', $config, $settings, $this ),
			$default_config
		);

		$position_class = RenderContext::resolve_position_class( $settings );

		/**
		 * Filter the resolved position class on the outer wrapper.
		 *
		 * @since 1.0.5
		 *
		 * @param string              $position_class E.g. `pos-bottom-right`.
		 * @param array<string,mixed> $settings       Full settings array.
		 * @param self                $widget         Widget instance.
		 */
		$position_class = (string) apply_filters( 'apeiron_autoscroll_position_class', $position_class, $settings, $this );
		$position_class = sanitize_html_class( $position_class, RenderContext::resolve_position_class( $settings ) );

		$context = [
			'widget_id'       => $widget_id,
			'position_class'  => $position_class,
			'settings'        => $settings,
			'config'          => $config,
			'icon_start'      => $icon_start,
			'icon_stop'       => $icon_stop,
			'icon_minus'      => $icon_minus,
			'icon_plus'       => $icon_plus,
			'icon_scroll_top' => $icon_scroll_top,
		];

		/**
		 * Filter the full render context before markup is emitted.
		 *
		 * @since 1.0.5
		 *
		 * @param array<string,mixed> $context  Render context.
		 * @param array<string,mixed> $settings Full settings array.
		 * @param self                $widget   Widget instance.
		 */
		$context = array_replace(
			$context,
			(array) apply_filters( 'apeiron_autoscroll_render_context', $context, $settings, $this )
		);
		$context['config']          = RenderContext::normalize_config( (array) $context['config'], $config );
		$context['position_class']  = sanitize_html_class( (string) $context['position_class'], $position_class );
		$context['icon_start']      = RenderContext::sanitize_icon_html( (string) $context['icon_start'] );
		$context['icon_stop']       = RenderContext::sanitize_icon_html( (string) $context['icon_stop'] );
		$context['icon_minus']      = RenderContext::sanitize_icon_html( (string) $context['icon_minus'] );
		$context['icon_plus']       = RenderContext::sanitize_icon_html( (string) $context['icon_plus'] );
		$context['icon_scroll_top'] = RenderContext::sanitize_icon_html( (string) $context['icon_scroll_top'] );

		$widget_id       = (string) $context['widget_id'];
		$position_class  = (string) $context['position_class'];
		$settings        = (array) $context['settings'];
		$config          = (array) $context['config'];
		$icon_start      = (string) $context['icon_start'];
		$icon_stop       = (string) $context['icon_stop'];
		$icon_minus      = (string) $context['icon_minus'];
		$icon_plus       = (string) $context['icon_plus'];
		$icon_scroll_top = (string) $context['icon_scroll_top'];

		require __DIR__ . '/Partials/autoscroll.php';
	}
}
