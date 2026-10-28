<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Depthscape;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\Depthscape\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\Depthscape\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paralaks 2.5D — pinned GSAP stage whose layers sit at real CSS 3D depths
 * while a scroll-scrubbed camera moves through scenes.
 */
class Depthscape extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	/**
	 * Template IDs currently rendering, to stop a template layer from embedding itself.
	 *
	 * @var array<int,true>
	 */
	private static array $rendering_templates = [];
	private static bool $layer_control_hooked = false;

	public function __construct( $data = [], $args = null ) {
		if ( ! self::$layer_control_hooked && function_exists( 'add_action' ) ) {
			add_action( 'elementor/controls/register', [ self::class, 'register_layer_control' ] );
			add_action( 'elementor/element/container/section_layout/before_section_end', [ self::class, 'register_content_container_control' ] );
			if ( did_action( 'elementor/controls/register' ) ) {
				self::register_layer_control( \Elementor\Plugin::$instance->controls_manager );
			}
			self::$layer_control_hooked = true;
		}

		parent::__construct( $data, $args );
	}

	public static function register_layer_control( $manager ): void {
		if ( ! $manager->get_control( 'apeiron_depthscape_layers' ) ) {
			$manager->register( new LayerRepeater() );
		}
	}

	public static function register_content_container_control( $container ): void {
		$container->add_control( 'apeiron_depthscape_layer', [
			'type'        => \Elementor\Controls_Manager::HIDDEN,
			'default'     => '',
			'render_type' => 'none',
		] );
	}

	private function supports_native_content(): bool {
		return (bool) \Elementor\Plugin::$instance->elements_manager->get_element_types( 'container' )
			&& \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' );
	}

	protected function get_initial_config() {
		return array_merge( parent::get_initial_config(), [
			'apeiron_nested_content' => $this->supports_native_content(),
		] );
	}

	protected function _get_default_child_type( array $element_data ) {
		if ( 'container' === ( $element_data['elType'] ?? '' ) ) {
			return \Elementor\Plugin::$instance->elements_manager->get_element_types( 'container' );
		}

		return parent::_get_default_child_type( $element_data );
	}

	public function get_name() {
		return 'apeiron-depthscape';
	}

	public function get_title() {
		return __( 'Paralaks 2.5D', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-depthscape';
	}

	public function get_keywords() {
		return [ 'parallax', 'paralaks', '3d', '2.5d', 'gsap', 'scroll', 'kamera', 'adegan', 'cinematic' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-depthscape';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-depthscape-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings = (array) $this->get_settings_for_display();
		$settings['layers'] = $this->resolve_content_layers( is_array( $settings['layers'] ?? null ) ? $settings['layers'] : [] );
		$config   = SceneConfigBuilder::build( $settings );
		$has_native_content = in_array( 'container', array_column( $config['layers'], 'type' ), true );

		$rows     = is_array( $settings['layers'] ?? null ) ? array_values( array_filter( $settings['layers'], 'is_array' ) ) : [];
		$layers   = [];

		foreach ( $rows as $index => $row ) {
			$layer = $config['layers'][ $index ] ?? null;
			if ( null === $layer ) {
				continue;
			}

			$layers[] = $this->prepare_layer( $row, $layer, $index );
		}

		$context = [
			'widget_id'   => $this->get_id(),
			'config_json' => (string) wp_json_encode( $config ),
			'layers'      => $layers,
			'show_hint'   => 'yes' === ( $settings['show_hint'] ?? '' ),
			'hint_text'   => is_scalar( $settings['hint_text'] ?? null ) ? (string) $settings['hint_text'] : '',
			'hint_opacity' => 'after_first' === $config['hintMode'] ? '0' : '1',
		];

		/** Filters render context before output. */
		$context = (array) apply_filters( 'apeiron_depthscape_render_context', $context, $settings, $this );

		$widget_id   = (string) $context['widget_id'];
		$config_json = (string) $context['config_json'];
		$layers      = (array) $context['layers'];
		$show_hint   = (bool) $context['show_hint'];
		$hint_text   = (string) $context['hint_text'];
		$hint_opacity = (float) ( $context['hint_opacity'] ?? 1 );

		require __DIR__ . '/Partials/depthscape.php';
	}

	/**
	 * @param array<string,mixed> $row   Raw repeater row.
	 * @param array<string,mixed> $layer Sanitized layer config.
	 * @return array<string,string>
	 */
	private function prepare_layer( array $row, array $layer, int $index ): array {
		$type   = (string) $layer['type'];
		$fit    = in_array( $type, [ 'image', 'video', 'shape' ], true ) ? SceneConfigBuilder::pick( $row['fit'] ?? 'free', SceneConfigBuilder::FITS, 'free' ) : 'free';
		$anchor = SceneConfigBuilder::pick( $row['anchor'] ?? 'center', SceneConfigBuilder::ANCHORS, 'center' );
		$space  = (string) $layer['space'];

		$classes = [
			'apeiron-depthscape__layer',
			'elementor-repeater-item-' . sanitize_html_class( (string) ( $row['_id'] ?? '' ) ),
			'is-type-' . $type,
			'is-fit-' . $fit,
			'is-anchor-' . $anchor,
			'is-space-' . $space,
		];

		return [
			'key'     => (string) $layer['key'],
			'index'   => (string) $index,
			'space'   => $space,
			'classes' => implode( ' ', array_filter( $classes ) ),
			'content' => $this->with_editor_placeholder( $this->render_layer_content( $type, $row, $index ), (string) $layer['key'] ),
			'start_opacity' => (string) ( $layer['start']['opacity'] ?? 1 ),
		];
	}

	/**
	 * @param array<string,mixed> $row Raw repeater row.
	 */
	private function render_layer_content( string $type, array $row, int $index ): string {
		if ( 'container' === $type ) {
			return $this->render_content_layer( $row );
		}

		if ( 'text' === $type ) {
			return $this->render_text_layer( $row, $index );
		}

		if ( 'template' === $type ) {
			return $this->render_template_layer( $row );
		}

		if ( 'video' === $type ) {
			return $this->render_video_layer( $row );
		}

		if ( 'shape' === $type ) {
			return '<div class="apeiron-depthscape__shape" aria-hidden="true"></div>';
		}

		return $this->render_image_layer( $row );
	}

	/** Resolve native element IDs after Elementor duplicates/imports a widget. */
	private function resolve_content_layers( array $rows ): array {
		$children = [];
		foreach ( $this->get_children() as $child ) {
			$children[ $child->get_id() ] = $child;
		}
		$used = [];
		foreach ( $rows as &$row ) {
			if ( ! is_array( $row ) || ( 'container' !== ( $row['layer_type'] ?? '' ) && empty( $row['content_id'] ) ) ) {
				continue;
			}
			$id = is_scalar( $row['content_id'] ?? null ) ? (string) $row['content_id'] : '';
			if ( ! isset( $children[ $id ] ) ) {
				$id = '';
				foreach ( $children as $child_id => $child ) {
					if ( ! isset( $used[ $child_id ] ) && (string) $child->get_settings( 'apeiron_depthscape_layer' ) === (string) ( $row['_id'] ?? '' ) ) {
						$id = $child_id;
						break;
					}
				}
				$row['content_id'] = $id;
			}
			if ( '' !== $id ) {
				$used[ $id ] = true;
			}
		}
		unset( $row );

		// Duplicated/imported widgets may carry containers without a row; the frontend adopts them. In the editor the
		// layer sync adds such rows itself, and a render requested while a row and its container are being removed
		// would otherwise bring the deleted container back as a stray "konten-…" layer.
		foreach ( $children as $id => $child ) {
			if ( ! isset( $used[ $id ] ) && ! $this->is_content_editor() ) {
				$rows[] = [
					'_id'        => $id,
					'layer_key'  => 'konten-' . $id,
					'layer_type' => 'container',
					'layer_space' => 'screen',
					'content_id' => $id,
				];
			}
		}

		return $rows;
	}

	private function render_content_layer( array $row ): string {
		$id = is_scalar( $row['content_id'] ?? null ) ? (string) $row['content_id'] : '';
		$html = '';
		if ( ! $this->is_content_editor() ) {
			foreach ( $this->get_children() as $child ) {
				if ( $child->get_id() === $id ) {
					ob_start();
					$child->print_element();
					$html = (string) ob_get_clean();
					break;
				}
			}
		}

		return '<div class="apeiron-depthscape__content" data-ds-content-id="' . esc_attr( $id ) . '">' . $html . '</div>';
	}

	private function is_content_editor(): bool {
		return $this->is_elementor_editor_preview()
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() && 'elementor_ajax' === ( $_REQUEST['action'] ?? '' ) );
	}

	/**
	 * @param array<string,mixed> $row Raw repeater row.
	 */
	private function render_image_layer( array $row ): string {
		$image = is_array( $row['image'] ?? null ) ? $row['image'] : [];
		$id    = isset( $image['id'] ) && is_numeric( $image['id'] ) ? (int) $image['id'] : 0;
		$attrs = [
			'class'     => 'apeiron-depthscape__img',
			'loading'   => 'eager',
			'decoding'  => 'async',
			'draggable' => 'false',
		];

		if ( $id > 0 ) {
			$html = wp_get_attachment_image( $id, 'full', false, $attrs );
			if ( '' !== $html ) {
				return $html;
			}
		}

		$url = is_scalar( $image['url'] ?? null ) ? (string) $image['url'] : '';
		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<img class="%s" src="%s" alt="" loading="eager" decoding="async" draggable="false">',
			esc_attr( $attrs['class'] ),
			esc_url( $url )
		);
	}

	/**
	 * @param array<string,mixed> $row Raw repeater row.
	 */
	private function render_video_layer( array $row ): string {
		$video = is_array( $row['video'] ?? null ) ? $row['video'] : [];
		$id    = isset( $video['id'] ) && is_numeric( $video['id'] ) ? (int) $video['id'] : 0;
		$url   = $id > 0 ? wp_get_attachment_url( $id ) : '';

		if ( ! is_string( $url ) || '' === $url ) {
			$url = is_scalar( $video['url'] ?? null ) ? (string) $video['url'] : '';
		}
		$url = esc_url( $url );
		if ( '' === $url ) {
			return '';
		}

		return '<video class="apeiron-depthscape__video" src="' . $url . '" muted playsinline preload="metadata" aria-hidden="true"'
			. ( 'yes' === ( $row['video_loop'] ?? 'yes' ) ? ' loop' : '' ) . '></video>';
	}

	/**
	 * @param array<string,mixed> $row Raw repeater row.
	 */
	private function render_text_layer( array $row, int $index ): string {
		$eyebrow  = $this->text_value( $row['eyebrow'] ?? '' );
		$title    = $this->text_value( $row['title'] ?? '' );
		$subtitle = $this->text_value( $row['subtitle'] ?? '' );
		$body     = is_scalar( $row['body'] ?? null ) ? trim( (string) $row['body'] ) : '';
		$button   = $this->text_value( $row['button_text'] ?? '' );
		$html     = '';

		if ( '' !== $eyebrow ) {
			$html .= '<span class="apeiron-depthscape__eyebrow" data-ds-split>' . esc_html( $eyebrow ) . '</span>';
		}

		if ( '' !== $title ) {
			$html .= '<span class="apeiron-depthscape__title" data-ds-split>' . nl2br( esc_html( $title ) ) . '</span>';
		}

		if ( '' !== $subtitle ) {
			$html .= '<span class="apeiron-depthscape__subtitle" data-ds-split>' . esc_html( $subtitle ) . '</span>';
		}

		if ( '' !== $body ) {
			$html .= '<div class="apeiron-depthscape__body" data-ds-split>' . wp_kses_post( wpautop( $body ) ) . '</div>';
		}

		if ( '' !== $button ) {
			$link_key = 'ds_button_' . $index;
			$link     = is_array( $row['button_link'] ?? null ) ? $row['button_link'] : [];

			$this->add_render_attribute( $link_key, 'class', 'apeiron-depthscape__button' );
			if ( ! empty( $link['url'] ) ) {
				$this->add_link_attributes( $link_key, $link );
			} else {
				$this->add_render_attribute( $link_key, 'role', 'button' );
			}

			$html .= '<a ' . $this->get_render_attribute_string( $link_key ) . '>' . esc_html( $button ) . '</a>';
		}

		return '' !== $html ? '<div class="apeiron-depthscape__text">' . $html . '</div>' : '';
	}

	/**
	 * @param array<string,mixed> $row Raw repeater row.
	 */
	private function render_template_layer( array $row ): string {
		$template_id = isset( $row['template_id'] ) && is_numeric( $row['template_id'] ) ? (int) $row['template_id'] : 0;

		if ( $template_id <= 0 || isset( self::$rendering_templates[ $template_id ] ) || get_the_ID() === $template_id ) {
			return '';
		}

		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! isset( \Elementor\Plugin::$instance->frontend ) ) {
			return '';
		}

		self::$rendering_templates[ $template_id ] = true;

		try {
			$html = (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
		} finally {
			unset( self::$rendering_templates[ $template_id ] );
		}

		return '' !== $html ? '<div class="apeiron-depthscape__template">' . $html . '</div>' : '';
	}

	/**
	 * Empty slots stay invisible on the live site but show a labelled outline in the editor,
	 * so a fresh widget (structure without media) is easy to fill in.
	 */
	private function with_editor_placeholder( string $content, string $key ): string {
		if ( '' !== $content || ! $this->is_elementor_editor_preview() ) {
			return $content;
		}

		return '<div class="apeiron-depthscape__empty">' . esc_html( $key ) . '</div>';
	}

	/**
	 * @param mixed $value Raw text setting.
	 */
	private function text_value( $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
