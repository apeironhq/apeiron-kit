<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Cover\Concerns;

use ApeironKit\Support\CoverSettings;
use ApeironKit\Support\CoverTypeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RendersCover {

	protected function render_widget() {
		$element_data = method_exists( $this, 'get_data' ) ? $this->get_data() : [];
		$raw_settings = is_array( $element_data['settings'] ?? null ) ? $element_data['settings'] : [];
		$settings     = $this->get_settings_for_display();
		$settings     = CoverSettings::resolve_widget_settings(
			is_array( $settings ) ? $settings : [],
			is_array( $raw_settings ) ? $raw_settings : []
		);

		/**
		 * Filter the resolved Cover settings before assets and markup are built.
		 *
		 * @since 1.1.0
		 *
		 * @param array $settings     Resolved widget settings.
		 * @param array $raw_settings Persisted Elementor settings.
		 * @param self  $widget       Widget instance.
		 */
		$settings = (array) apply_filters(
			'apeiron_cover_resolved_settings',
			$settings,
			$raw_settings,
			$this
		);
		$is_editor = $this->is_elementor_editor_preview();
		$cover_id  = 'apeiron-cover-' . $this->get_id();

		$cover_type = CoverTypeRegistry::sanitize_type( (string) ( $settings['cover_type'] ?? '' ) );

		// The prototype cannot resolve the rendered Cover type.
		foreach ( CoverTypeRegistry::get_style_handles( $cover_type ) as $type_style ) {
			if ( wp_style_is( $type_style, 'registered' ) && ! wp_style_is( $type_style, 'enqueued' ) ) {
				wp_enqueue_style( $type_style );
			}
		}

		$default_pattern_url  = $this->get_default_pattern_url();
		$pattern_url          = $this->get_media_url( $settings, 'pattern_image' );
		$uses_default_pattern = '' === $pattern_url || $default_pattern_url === $pattern_url;
		$pattern_url          = '' === $pattern_url ? $default_pattern_url : $pattern_url;
		$background_url       = $this->get_media_url( $settings, 'background_image' );
		$ornament_url  = $this->get_media_url( $settings, 'ornament_image' );
		$photo_url     = $this->get_media_url( $settings, 'cover_photo' );
		$pin_image_url = $this->get_media_url( $settings, 'pin_image' );

		$panel_effect = $this->sanitize_choice( $settings['panel_effect'] ?? 'slide', self::PANEL_EFFECTS, 'slide' );
		if ( 'yes' === ( $settings['show_center_strip'] ?? 'yes' ) ) {
			$panel_effect = 'slide';
		}
		$exit_effect = $this->sanitize_choice( $settings['exit_effect'] ?? 'down', self::EXIT_EFFECTS, 'down' );
		$guest_param = sanitize_key( (string) ( $settings['guest_parameter'] ?? 'to' ) );
		$guest_param = '' !== $guest_param ? $guest_param : 'to';
		$cover_label = trim(
			(string) ( $settings['primary_name'] ?? '' ) . ' ' .
			(string) ( $settings['name_separator'] ?? '&' ) . ' ' .
			(string) ( $settings['secondary_name'] ?? '' )
		);
		$cover_label = '' !== $cover_label
			? sprintf( __( 'Cover undangan untuk %s', 'apeiron-kit' ), $cover_label )
			: __( 'Cover undangan', 'apeiron-kit' );

		$classes = [
			'apeiron-cover',
			'apeiron-cover--type-' . $cover_type,
			'apeiron-cover--panel-' . $panel_effect,
			'apeiron-cover--exit-' . $exit_effect,
			'apeiron-cover--seal-circle',
		];
		if ( $uses_default_pattern ) {
			$classes[] = 'apeiron-cover--default-pattern';
		}

		$pin_classes = [
			'apeiron-cover__pin',
			'apeiron-cover__pin--circle',
		];

		if ( $is_editor ) {
			$classes[] = 'is-editor-preview';
		}

		$info_duration_ms    = $this->get_slider_int( $settings, 'info_duration', 4100, 100, 5000 );
		$panel_duration_ms   = $this->get_slider_int( $settings, 'panel_duration', 3650, 100, 5000 );
		$exit_duration_ms    = $this->get_slider_int( $settings, 'exit_duration', 2150, 0, 6000 );
		$open_delay_ms       = $this->get_slider_int( $settings, 'open_delay', 150, 0, 2000 );
		$lock_scroll         = 'yes' === ( $settings['lock_scroll'] ?? 'yes' ) ? 'yes' : 'no';
		$close_on_escape     = 'yes' === ( $settings['close_on_escape'] ?? '' ) ? 'yes' : 'no';
		$first_visit_only    = 'yes' === ( $settings['first_visit_only'] ?? '' ) ? 'yes' : 'no';
		$storage_key         = $this->sanitize_storage_key( (string) ( $settings['storage_key'] ?? 'apeiron_cover_opened' ) );
		$auto_recipient      = 'yes' === ( $settings['auto_recipient'] ?? 'yes' ) ? 'yes' : 'no';
		$event_name          = $this->sanitize_event_name( (string) ( $settings['event_name'] ?? 'apeiron:cover:opened' ) );
		$click_selector      = sanitize_text_field( (string) ( $settings['click_selector'] ?? '' ) );
		$scroll_selector     = sanitize_text_field( (string) ( $settings['scroll_selector'] ?? '' ) );
		$scroll_behavior     = $this->sanitize_choice( $settings['scroll_behavior'] ?? 'smooth', [ 'smooth', 'auto' ], 'smooth' );
		$show_desktop        = 'yes' === ( $settings['show_desktop'] ?? 'yes' ) ? 'yes' : 'no';
		$show_tablet         = 'yes' === ( $settings['show_tablet'] ?? 'yes' ) ? 'yes' : 'no';
		$show_mobile         = 'yes' === ( $settings['show_mobile'] ?? 'yes' ) ? 'yes' : 'no';

		$this->add_render_attribute(
			'cover',
			[
				'id'                         => $cover_id,
				'class'                      => $classes,
				'role'                       => 'dialog',
				'aria-modal'                 => $is_editor ? 'false' : 'true',
				'aria-label'                 => $cover_label,
				'data-apeiron-cover'         => 'yes',
				// Legacy aliases preserve published integrations.
				'data-apeiron-cover-type'             => $cover_type,
				'data-cover-type'                     => $cover_type,
				'data-apeiron-cover-info-duration'    => (string) $info_duration_ms,
				'data-info-duration'                 => (string) $info_duration_ms,
				'data-apeiron-cover-panel-duration'   => (string) $panel_duration_ms,
				'data-panel-duration'                => (string) $panel_duration_ms,
				'data-apeiron-cover-exit-duration'    => (string) $exit_duration_ms,
				'data-exit-duration'                 => (string) $exit_duration_ms,
				'data-apeiron-cover-open-delay'       => (string) $open_delay_ms,
				'data-open-delay'                    => (string) $open_delay_ms,
				'data-apeiron-cover-lock-scroll'      => $lock_scroll,
				'data-lock-scroll'                   => $lock_scroll,
				'data-apeiron-cover-close-on-escape'  => $close_on_escape,
				'data-close-on-escape'               => $close_on_escape,
				'data-apeiron-cover-first-visit-only' => $first_visit_only,
				'data-first-visit-only'              => $first_visit_only,
				'data-apeiron-cover-storage-key'     => $storage_key,
				'data-storage-key'                   => $storage_key,
				'data-apeiron-cover-editor-preview'  => $is_editor ? 'yes' : 'no',
				'data-editor-preview'                => $is_editor ? 'yes' : 'no',
				'data-apeiron-cover-auto-recipient'  => $auto_recipient,
				'data-auto-recipient'                => $auto_recipient,
				'data-apeiron-cover-guest-parameter' => $guest_param,
				'data-guest-parameter'               => $guest_param,
				'data-apeiron-cover-event-name'      => $event_name,
				'data-event-name'                    => $event_name,
				'data-apeiron-cover-click-selector'  => $click_selector,
				'data-click-selector'                => $click_selector,
				'data-apeiron-cover-scroll-selector' => $scroll_selector,
				'data-scroll-selector'               => $scroll_selector,
				'data-apeiron-cover-scroll-behavior' => $scroll_behavior,
				'data-scroll-behavior'               => $scroll_behavior,
				'data-apeiron-cover-show-desktop'    => $show_desktop,
				'data-show-desktop'                  => $show_desktop,
				'data-apeiron-cover-show-tablet'     => $show_tablet,
				'data-show-tablet'                   => $show_tablet,
				'data-apeiron-cover-show-mobile'     => $show_mobile,
				'data-show-mobile'                   => $show_mobile,
				'style'                      => $this->build_inline_style( $pattern_url, $background_url ),
			]
		);

		?>
		<div <?php echo $this->get_render_attribute_string( 'cover' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor escapes render attributes ?>>
			<div class="apeiron-cover__stage">
				<?php
				$template_assets = [
						'ornament' => $ornament_url,
						'photo'    => $photo_url,
						'pin'      => $pin_image_url,
					];

				$context = [
					'settings'       => $settings,
					'assets'         => $template_assets,
					'pin_classes'    => $pin_classes,
					'cover_id'       => $cover_id,
					'is_editor'      => $is_editor,
					'root_classes'   => $classes,
				];

				/**
				 * Filter the complete Cover template context before output.
				 *
				 * @since 1.1.0
				 *
				 * @param array $context Resolved template context.
				 * @param array $settings Resolved widget settings.
				 * @param self  $widget Widget instance.
				 */
				$context = (array) apply_filters(
					'apeiron_cover_render_context',
					$context,
					$settings,
					$this
				);

				$template_settings    = is_array( $context['settings'] ?? null ) ? $context['settings'] : $settings;
				$template_assets      = is_array( $context['assets'] ?? null ) ? $context['assets'] : $template_assets;
				$template_pin_classes = is_array( $context['pin_classes'] ?? null ) ? $context['pin_classes'] : $pin_classes;
				$button_lines         = $this->get_open_button_lines( $template_settings );

				$this->render_cover_template(
					$cover_type,
					[
						'settings'                  => $template_settings,
						'assets'                    => $template_assets,
						'pin_classes'               => $template_pin_classes,
						'center_strip_left_html'    => $this->get_center_strip_html( $template_settings, false ),
						'center_strip_right_html'   => $this->get_center_strip_html( $template_settings, true ),
						'center_strip_label_top'    => $this->get_center_strip_label( $template_settings, 'center_strip_label' ),
						'center_strip_label_bottom' => $this->get_center_strip_label( $template_settings, 'center_strip_label_bottom' ),
						'ribbon_text_html'          => $this->get_ribbon_text_html( $template_settings ),
						'art'                       => $this->get_cover_art( $cover_id ),
						'button_lines'              => $button_lines,
						'button_label'              => trim( implode( ' ', $button_lines ) ),
						'recipient_heading'         => $this->get_recipient_heading( $template_settings ),
					]
				);
				?>
			</div>
		</div>
		<?php
	}

	private function render_cover_template( string $cover_type, array $context ): void {
		$template = CoverTypeRegistry::get_template_file( $cover_type );
		if ( '' === $template ) {
			$template = CoverTypeRegistry::get_template_file( CoverTypeRegistry::DEFAULT_TYPE );
		}

		if ( '' === $template ) {
			return;
		}

		extract( $context, EXTR_SKIP );
		require $template;
	}

	private function get_center_strip_html( array $settings, bool $reverse ): string {
		if ( 'yes' !== ( $settings['show_center_strip'] ?? 'yes' ) ) {
			return '';
		}

		$class = $reverse ? ' apeiron-cover__center-strip--reverse' : '';
		return '<div class="apeiron-cover__center-strip' . esc_attr( $class ) . '" aria-hidden="true"></div>';
	}

	private function get_center_strip_label( array $settings, string $key ): string {
		$label = (string) ( $settings[ $key ] ?? 'Plugin Apeiron Kit Cover Invitation' );
		$label = trim( $this->replace_text_tokens( $label, $settings ) );

		return '' !== $label ? $label : '';
	}

	private function get_ribbon_text_html( array $settings ): string {
		$ribbon_text = $this->replace_text_tokens( (string) ( $settings['ribbon_text'] ?? '' ), $settings );
		$ribbon_text = trim( $ribbon_text );
		if ( '' === $ribbon_text ) {
			return '';
		}

		return '<span class="apeiron-cover__ribbon-name apeiron-cover__ribbon-name--left">' . esc_html( $ribbon_text ) . '</span>'
			. '<span class="apeiron-cover__ribbon-name apeiron-cover__ribbon-name--right">' . esc_html( $ribbon_text ) . '</span>';
	}

	private function get_open_button_lines( array $settings ): array {
		$text = trim( (string) ( $settings['button_text'] ?? __( 'Buka Undangan', 'apeiron-kit' ) ) );
		if ( '' === $text ) {
			$text = __( 'Buka Undangan', 'apeiron-kit' );
		}

		$lines = preg_split( '/\R+/', $text );
		$lines = array_values(
			array_filter(
				array_map(
					static fn( $line ) => trim( preg_replace( '/\s+/', ' ', (string) $line ) ),
					false !== $lines ? $lines : []
				),
				static fn( $line ) => '' !== $line
			)
		);

		if ( 1 === count( $lines ) && false !== strpos( $lines[0], ' ' ) ) {
			$parts = preg_split( '/\s+/', $lines[0], 2 );
			if ( is_array( $parts ) && 2 === count( $parts ) ) {
				$lines = [ $parts[0], $parts[1] ];
			}
		}

		return array_slice( $lines, 0, 2 );
	}

	private function replace_text_tokens( string $text, array $settings ): string {
		$primary   = trim( (string) ( $settings['primary_name'] ?? '' ) );
		$secondary = trim( (string) ( $settings['secondary_name'] ?? '' ) );
		$separator = trim( (string) ( $settings['name_separator'] ?? '&' ) );
		$names     = trim( preg_replace( '/\s+/', ' ', $primary . ' ' . $separator . ' ' . $secondary ) );

		return strtr(
			$text,
			[
				'{primary}'   => $primary,
				'{secondary}' => $secondary,
				'{separator}' => $separator,
				'{names}'     => $names,
			]
		);
	}

	private function get_recipient_heading( array $settings ): string {
		$heading = trim( (string) ( $settings['recipient_heading'] ?? '' ) );

		return '' !== $heading ? $heading : __( 'Kepada', 'apeiron-kit' );
	}

	/**
	 * Inline final SVGs so Elementor variables apply; IDs are prefixed per widget.
	 *
	 * @return array<string,string>
	 */
	private function get_cover_art( string $cover_id ): array {
		$slots = [
			'ribbon' => 'invitation-ribbon',
			'tail'   => 'invitation-ribbon-tail',
			'seal'   => 'seal-medallion',
			'tag'    => 'guest-tag',
		];

		$art = [];
		foreach ( $slots as $slot => $file ) {
			$art[ $slot ] = $this->get_cover_svg( $file, $cover_id . '-' . $slot );
		}

		return $art;
	}

	private function get_cover_svg( string $name, string $prefix ): string {
		static $cache = [];

		$allowed = [ 'seal-medallion', 'invitation-ribbon', 'invitation-ribbon-tail', 'guest-tag' ];
		if ( ! in_array( $name, $allowed, true ) ) {
			return '';
		}

		if ( ! array_key_exists( $name, $cache ) ) {
			$path           = trailingslashit( APEIRON_KIT_PATH ) . 'assets/svg/cover/' . $name . '.svg';
			$markup         = is_readable( $path ) ? (string) file_get_contents( $path ) : '';
			$cache[ $name ] = trim( $markup );
		}

		if ( '' === $cache[ $name ] ) {
			return '';
		}

		return $this->prefix_svg_ids( $cache[ $name ], $prefix );
	}

	/** Prevent SVG gradient, filter, and clipPath ID collisions. */
	private function prefix_svg_ids( string $markup, string $prefix ): string {
		$prefix = preg_replace( '/[^A-Za-z0-9_-]/', '', $prefix );
		if ( '' === (string) $prefix ) {
			return $markup;
		}

		$markup = (string) preg_replace(
			'/\bid="([A-Za-z][A-Za-z0-9_-]*)"/',
			'id="' . $prefix . '-$1"',
			$markup
		);

		return (string) preg_replace(
			'/url\(#([A-Za-z][A-Za-z0-9_-]*)\)/',
			'url(#' . $prefix . '-$1)',
			$markup
		);
	}

	private function get_cover_asset_url( string $filename ): string {
		static $cache = [];

		$filename = basename( $filename );
		if ( '' === $filename ) {
			return '';
		}
		if ( array_key_exists( $filename, $cache ) ) {
			return $cache[ $filename ];
		}

		$path = trailingslashit( APEIRON_KIT_PATH ) . 'assets/img/cover/' . $filename;
		if ( file_exists( $path ) ) {
			return $cache[ $filename ] = trailingslashit( APEIRON_KIT_URL ) . 'assets/img/cover/' . rawurlencode( $filename );
		}

		return $cache[ $filename ] = '';
	}

	private function get_default_pattern_url(): string {
		return $this->get_cover_asset_url( 'bg.jpg' );
	}

	private function get_media_url( array $settings, string $key, string $fallback = '' ): string {
		$url = '';
		if ( ! empty( $settings[ $key ]['url'] ) ) {
			$url = (string) $settings[ $key ]['url'];
		}
		if ( '' === $url && ! empty( $settings[ $key ]['id'] ) ) {
			$attachment_url = wp_get_attachment_image_url( absint( $settings[ $key ]['id'] ), 'full' );
			$url            = is_string( $attachment_url ) ? $attachment_url : '';
		}

		$url = esc_url_raw( $url );
		return '' !== $url ? $url : $fallback;
	}

	private function build_inline_style( string $pattern_url, string $background_url ): string {
		$tokens = [
			'--apeiron-cover-pattern-image' => $this->css_url( $pattern_url ),
			'--apeiron-cover-bg-image'      => $this->css_url( $background_url ?: $pattern_url ),
		];
		$style = [];
		foreach ( $tokens as $name => $value ) {
			$style[] = $name . ':' . $value . ';';
		}

		return implode( '', $style );
	}

	private function css_url( string $url ): string {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return 'none';
		}

		return 'url("' . str_replace( [ '\\', '"' ], [ '\\\\', '\"' ], $url ) . '")';
	}

	private function get_slider_int( array $settings, string $key, int $fallback, int $min, int $max ): int {
		$value = $settings[ $key ]['size'] ?? $fallback;
		$value = absint( $value );

		if ( $value < $min ) {
			return $min;
		}
		if ( $value > $max ) {
			return $max;
		}

		return $value;
	}

	private function sanitize_choice( string $value, array $allowed, string $fallback ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private function sanitize_storage_key( string $key ): string {
		$key = sanitize_key( $key );
		if ( '' === $key ) {
			return 'apeiron_cover_opened';
		}

		if ( 0 === strpos( $key, 'apeiron_cover_' ) ) {
			return $key;
		}

		return 'apeiron_cover_' . $key;
	}

	private function sanitize_event_name( string $event_name ): string {
		$event_name = preg_replace( '/[^a-zA-Z0-9:_-]/', '', $event_name );
		return $event_name ?: 'apeiron:cover:opened';
	}

}
