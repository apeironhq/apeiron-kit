<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\PhotoAlbum;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\PhotoAlbum\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\PhotoAlbum\Concerns\RegistersStyleControls;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PhotoAlbum extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const LAYOUTS        = [ 'filmstrip', 'card' ];
	private const EFFECTS        = [ 'fade', 'slide' ];
	private const CAPTION_SOURCE = [ 'caption', 'title', 'alt', 'description', 'none' ];
	private const TITLE_TAGS     = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p' ];

	public function get_name() {
		return 'apeiron-photo-album';
	}

	public function get_title() {
		return __( 'Album Foto', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'eicon-slider-album';
	}

	public function get_keywords() {
		return [ 'album', 'foto', 'galeri', 'gallery', 'photo', 'slideshow', 'momen', 'undangan', 'apeiron' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-photo-album';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-photo-album-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings  = (array) $this->get_settings_for_display();
		$is_editor = $this->is_elementor_editor_preview();
		$photos    = $this->collect_photos( $settings );

		if ( empty( $photos ) ) {
			if ( ! $is_editor ) {
				return;
			}
			$photos = $this->placeholder_photos( $settings );
		}

		$layout       = $this->choice( $settings['layout'] ?? '', self::LAYOUTS, 'filmstrip' );
		$title_tag    = $this->choice( $settings['title_tag'] ?? '', self::TITLE_TAGS, 'h2' );
		$effect       = $this->choice( $settings['transition_effect'] ?? '', self::EFFECTS, 'fade' );
		$duration     = (int) round( max( 1.5, min( 30, (float) ( $settings['autoplay_duration'] ?? 5 ) ) ) * 1000 );
		$speed        = max( 0, min( 3000, (int) ( $settings['transition_speed'] ?? 700 ) ) );
		$lightbox_id  = 'apeiron-photo-album-' . $this->get_id();
		$flags        = [
			'title'      => 'yes' === ( $settings['show_title'] ?? 'yes' ) && '' !== trim( (string) ( $settings['title'] ?? '' ) ),
			'title_line' => 'yes' === ( $settings['show_title_line'] ?? 'yes' ),
			'peek'       => 'yes' === ( $settings['show_peek'] ?? 'yes' ) && count( $photos ) > 1,
			'counter'    => 'yes' === ( $settings['show_counter'] ?? 'yes' ),
			'caption'    => 'yes' === ( $settings['show_caption'] ?? 'yes' ),
			'thumbs'     => 'yes' === ( $settings['show_thumbnails'] ?? 'yes' ) && count( $photos ) > 1,
			'numbers'    => 'yes' === ( $settings['show_numbers'] ?? 'yes' ),
			'progress'   => 'yes' === ( $settings['show_progress'] ?? 'yes' ) && count( $photos ) > 1,
			'next'       => 'yes' === ( $settings['show_next'] ?? 'yes' ) && count( $photos ) > 1,
			'prev'       => 'yes' === ( $settings['show_prev'] ?? '' ) && count( $photos ) > 1,
			'lightbox'   => 'yes' === ( $settings['open_lightbox'] ?? 'yes' ),
		];

		$this->add_render_attribute(
			'photo_album',
			[
				'class'                       => [ 'apeiron-photo-album', 'apeiron-photo-album--effect-' . $effect ],
				'data-apeiron-photo-album'    => 'yes',
				'data-layout'                 => $layout,
				'data-effect'                 => $effect,
				'data-autoplay'               => 'yes' === ( $settings['autoplay'] ?? 'yes' ) && count( $photos ) > 1 ? 'yes' : 'no',
				'data-duration'               => (string) $duration,
				'data-speed'                  => (string) $speed,
				'data-loop'                   => 'yes' === ( $settings['loop'] ?? 'yes' ) ? 'yes' : 'no',
				'data-pause-on-hover'         => 'yes' === ( $settings['pause_on_hover'] ?? 'yes' ) ? 'yes' : 'no',
				'data-editor'                 => $is_editor ? 'yes' : 'no',
				'style'                       => '--pa-count:' . count( $photos ) . ';--pa-speed:' . $speed . 'ms;',
			]
		);

		$render_attributes = $this->get_render_attribute_string( 'photo_album' );

		require __DIR__ . '/Partials/photo-album.php';
	}

	/**
	 * Gallery control values arrive as [id, url] rows from the media library or a Dynamic Tag.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function collect_photos( array $settings ): array {
		$gallery = is_array( $settings['gallery'] ?? null ) ? $settings['gallery'] : [];
		$size    = is_string( $settings['photo_size_size'] ?? null ) && '' !== $settings['photo_size_size'] ? $settings['photo_size_size'] : 'large';
		$source  = $this->choice( $settings['caption_source'] ?? '', self::CAPTION_SOURCE, 'caption' );
		$default = trim( (string) ( $settings['fallback_caption'] ?? '' ) );
		$photos  = [];

		foreach ( $gallery as $image ) {
			if ( ! is_array( $image ) ) {
				continue;
			}

			$id  = absint( $image['id'] ?? 0 );
			$url = trim( (string) ( $image['url'] ?? '' ) );

			if ( $id > 0 && wp_attachment_is_image( $id ) ) {
				$main  = wp_get_attachment_image_src( $id, $size );
				$thumb = wp_get_attachment_image_src( $id, 'medium' );
				$full  = wp_get_attachment_image_src( $id, 'full' );
				if ( ! $main ) {
					continue;
				}
				$photos[] = [
					'src'     => (string) $main[0],
					'srcset'  => (string) wp_get_attachment_image_srcset( $id, $size ),
					'thumb'   => $thumb ? (string) $thumb[0] : (string) $main[0],
					'full'    => $full ? (string) $full[0] : (string) $main[0],
					'alt'     => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
					'caption' => $this->caption_for( $id, $source, $default ),
				];
				continue;
			}

			if ( '' !== $url ) {
				$photos[] = [
					'src'     => $url,
					'srcset'  => '',
					'thumb'   => $url,
					'full'    => $url,
					'alt'     => '',
					'caption' => 'none' === $source ? '' : $default,
				];
			}
		}

		return $photos;
	}

	private function caption_for( int $id, string $source, string $default ): string {
		switch ( $source ) {
			case 'none':
				return '';
			case 'title':
				$text = get_the_title( $id );
				break;
			case 'alt':
				$text = get_post_meta( $id, '_wp_attachment_image_alt', true );
				break;
			case 'description':
				$text = get_post_field( 'post_content', $id );
				break;
			default:
				$text = wp_get_attachment_caption( $id );
		}

		$text = trim( wp_strip_all_tags( is_string( $text ) ? $text : '' ) );

		return '' !== $text ? $text : $default;
	}

	/**
	 * Editor-only preview so the layout is visible before photos are chosen.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function placeholder_photos( array $settings ): array {
		$src     = Utils::get_placeholder_image_src();
		$caption = 'none' === ( $settings['caption_source'] ?? '' ) ? '' : trim( (string) ( $settings['fallback_caption'] ?? '' ) );
		$photos  = [];

		for ( $i = 0; $i < 4; $i++ ) {
			$photos[] = [ 'src' => $src, 'srcset' => '', 'thumb' => $src, 'full' => $src, 'alt' => '', 'caption' => $caption ];
		}

		return $photos;
	}

	private function choice( $value, array $allowed, string $fallback ): string {
		return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
