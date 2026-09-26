<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SoundscapePlayer;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\SoundscapePlayer\AudioSourceResolver;
use ApeironKit\Elementor\Widgets\SoundscapePlayer\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\SoundscapePlayer\Concerns\RegistersStyleControls;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SoundscapePlayer extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	/** @var array<string,string> */
	private const LEGACY_EFFECT_MAP = [
		'ripple'    => 'pulse',
		'heartbeat' => 'beat',
	];

	private const VALID_EFFECTS = [ '', 'spin', 'pulse', 'spin_pulse', 'beat', 'glow' ];

	private const VALID_SHAPES = [ 'circle', 'square' ];

	public function get_name() {
		return 'apeiron-soundscape';
	}

	public function get_title() {
		return __( 'Tombol Audio', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-audio';
	}

	public function get_keywords() {
		return [ 'audio', 'music', 'sound', 'suara', 'musik', 'youtube' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-soundscape';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-soundscape-js';

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
		$wid       = $this->get_id();
		$src_type  = (string) ( $settings['src_type'] ?? 'upload' );

		$audio_url  = AudioSourceResolver::resolve_url( $settings, $src_type );
		$is_youtube = AudioSourceResolver::is_youtube( $src_type );

		/** Filters the resolved URL before browser output. */
		$audio_url = (string) apply_filters(
			'apeiron_soundscape_audio_url',
			$audio_url,
			$settings,
			$this
		);

		$is_empty     = empty( $audio_url );
		$mime_type    = $is_youtube ? '' : AudioSourceResolver::mime_type( $audio_url );
		$shape        = $this->resolve_shape( $settings );
		$pulse_style  = $this->resolve_pulse_style( $settings );
		$has_pulse    = '' !== $pulse_style;

		$context = [
			'settings'        => $settings,
			'widget_id'        => $wid,
			'src_type'          => $src_type,
			'audio_url'         => $audio_url,
			'is_youtube'        => $is_youtube,
			'mime_type'         => $mime_type,
			'has_loop'          => ! empty( $settings['loop'] ),
			'is_empty'          => $is_empty,
			'is_autoplay'       => 'yes' === ( $settings['autoplay'] ?? '' ),
			'pause_hidden'      => 'yes' === ( $settings['pause_hidden'] ?? 'yes' ),
			'cover_music_start' => 'cover_click' === ( $settings['cover_music_start'] ?? '' ) ? 'cover_click' : 'cover_opened',
			'start_sec'         => (string) ( $settings['start'] ?? '' ),
			'end_sec'           => (string) ( $settings['end'] ?? '' ),
			'empty_message'     => (string) ( $settings['empty_message'] ?? __( 'Pilih audio terlebih dahulu.', 'apeiron-kit' ) ),
			'loading_message'   => (string) ( $settings['loading_message'] ?? __( 'Memuat audio...', 'apeiron-kit' ) ),
			'error_message'     => (string) ( $settings['error_message'] ?? __( 'Audio gagal diputar.', 'apeiron-kit' ) ),
			'range_message'     => (string) ( $settings['range_message'] ?? __( 'Waktu berhenti harus lebih besar dari waktu mulai.', 'apeiron-kit' ) ),
			'shape'             => $shape,
			'pulse_style'       => $pulse_style,
			'has_pulse'         => $has_pulse,
			'play_aria_label'   => __( 'Putar audio', 'apeiron-kit' ),
			'pause_aria_label'  => __( 'Jeda audio', 'apeiron-kit' ),
			'play_icon_html'    => $this->render_icon_html( $settings, 'pause_icon' ),
			'pause_icon_html'   => $this->render_icon_html( $settings, 'play_icon' ),
			'player_classes'    => $this->build_player_classes( $wid, $shape, $pulse_style, $has_pulse, $is_empty ),
			'container_id'      => 'audio-container-' . $wid,
			'audio_id'          => 'song-' . $wid,
			'youtube_id'        => 'youtube-audio-' . $wid,
			'play_toggle_id'    => 'unmute-sound-' . $wid,
			'pause_toggle_id'   => 'mute-sound-' . $wid,
		];

		/** Filters render context before output. */
		$context = (array) apply_filters(
			'apeiron_soundscape_render_context',
			$context,
			$settings,
			$this
		);

		$settings         = (array) $context['settings'];
		$widget_id        = (string) $context['widget_id'];
		$src_type          = (string) $context['src_type'];
		$audio_url         = (string) $context['audio_url'];
		$is_youtube        = (bool) $context['is_youtube'];
		$mime_type         = (string) $context['mime_type'];
		$has_loop          = (bool) $context['has_loop'];
		$is_empty          = (bool) $context['is_empty'];
		$is_autoplay       = (bool) $context['is_autoplay'];
		$pause_hidden      = (bool) $context['pause_hidden'];
		$cover_music_start = (string) $context['cover_music_start'];
		$start_sec         = (string) $context['start_sec'];
		$end_sec           = (string) $context['end_sec'];
		$empty_message     = (string) $context['empty_message'];
		$loading_message   = (string) $context['loading_message'];
		$error_message     = (string) $context['error_message'];
		$range_message     = (string) $context['range_message'];
		$shape             = (string) $context['shape'];
		$pulse_style       = (string) $context['pulse_style'];
		$has_pulse         = (bool) $context['has_pulse'];
		$play_aria_label   = (string) $context['play_aria_label'];
		$pause_aria_label  = (string) $context['pause_aria_label'];
		$play_icon_html    = (string) $context['play_icon_html'];
		$pause_icon_html   = (string) $context['pause_icon_html'];
		$player_classes    = (string) $context['player_classes'];
		$container_id      = (string) $context['container_id'];
		$audio_id          = (string) $context['audio_id'];
		$youtube_id        = (string) $context['youtube_id'];
		$play_toggle_id    = (string) $context['play_toggle_id'];
		$pause_toggle_id   = (string) $context['pause_toggle_id'];

		require __DIR__ . '/Partials/soundscape-player.php';
	}

	private function resolve_dynamic_settings( array $settings, array $raw_settings ): array {
		$dynamic = is_array( $raw_settings['__dynamic__'] ?? null ) ? $raw_settings['__dynamic__'] : [];
		if ( array_key_exists( 'audio_dynamic_url', $dynamic ) ) {
			$value = $this->get_url_value( $settings['audio_dynamic_url'] ?? null );
			if ( '' === $value ) {
				$value = $this->get_url_value( $raw_settings['audio_dynamic_url'] ?? null );
			}
			if ( '' !== $value ) {
				$settings['audio_upload'] = [ 'url' => $value ];
			}
		}

		foreach ( [ 'audio_upload', 'audio_link' ] as $key ) {
			if ( ! array_key_exists( $key, $dynamic ) ) {
				continue;
			}

			$value = $this->get_url_value( $settings[ $key ] ?? null );
			if ( '' === $value ) {
				$value = $this->get_url_value( $raw_settings[ $key ] ?? null );
			}
			$settings[ $key ] = [ 'url' => $value ];
		}

		if ( array_key_exists( 'youtube_link', $dynamic ) ) {
			$value = $this->get_url_value( $settings['youtube_link'] ?? null );
			if ( '' === $value ) {
				$value = $this->get_url_value( $raw_settings['youtube_link'] ?? null );
			}
			$settings['youtube_link'] = '' !== $value ? $value : 'https://youtu.be/Trjrj_fQnIM';
		}

		$messages = [
			'empty_message'   => __( 'Pilih audio terlebih dahulu.', 'apeiron-kit' ),
			'loading_message' => __( 'Memuat audio...', 'apeiron-kit' ),
			'error_message'   => __( 'Audio gagal diputar.', 'apeiron-kit' ),
			'range_message'   => __( 'Waktu berhenti harus lebih besar dari waktu mulai.', 'apeiron-kit' ),
		];
		foreach ( $messages as $key => $default ) {
			$value = is_scalar( $settings[ $key ] ?? null ) ? sanitize_text_field( (string) $settings[ $key ] ) : '';
			if ( '' === trim( $value ) && array_key_exists( $key, $dynamic ) ) {
				$raw_value = is_scalar( $raw_settings[ $key ] ?? null ) ? sanitize_text_field( (string) $raw_settings[ $key ] ) : '';
				$value     = '' !== trim( $raw_value ) ? $raw_value : $default;
			}
			$settings[ $key ] = $value;
		}

		foreach ( [ 'start', 'end' ] as $key ) {
			if ( ! array_key_exists( $key, $dynamic ) ) {
				continue;
			}
			$value = $settings[ $key ] ?? '';
			if ( ! is_numeric( $value ) || (float) $value < 0 ) {
				$value = $raw_settings[ $key ] ?? '';
			}
			$settings[ $key ] = is_numeric( $value ) && (float) $value >= 0 ? (string) $value : '';
		}

		return $settings;
	}

	private function get_url_value( $value ): string {
		if ( is_array( $value ) ) {
			$value = $value['url'] ?? '';
		}
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$url   = esc_url_raw( trim( (string) $value ), [ 'http', 'https' ] );
		$parts = wp_parse_url( $url );

		return is_array( $parts ) && ! empty( $parts['host'] ) ? $url : '';
	}

	private function resolve_shape( array $settings ): string {
		$shape = (string) ( $settings['shape'] ?? 'circle' );

		return in_array( $shape, self::VALID_SHAPES, true ) ? $shape : 'circle';
	}

	private function resolve_pulse_style( array $settings ): string {
		$pulse_style = (string) ( $settings['pulse_style'] ?? 'spin' );

		if ( isset( self::LEGACY_EFFECT_MAP[ $pulse_style ] ) ) {
			$pulse_style = self::LEGACY_EFFECT_MAP[ $pulse_style ];
		}

		return in_array( $pulse_style, self::VALID_EFFECTS, true ) ? $pulse_style : 'spin';
	}

	private function render_icon_html( array $settings, string $key ): string {
		$icon = $settings[ $key ] ?? null;

		if ( ! is_array( $icon ) || empty( $icon['value'] ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );

		return (string) ob_get_clean();
	}

	private function build_player_classes( string $widget_id, string $shape, string $pulse_style, bool $has_pulse, bool $is_empty ): string {
		$classes = [
			'audio-box',
			'apeiron-soundscape-player',
			'apeiron-soundscape-player-' . $widget_id,
			'is-shape-' . sanitize_html_class( $shape ),
		];

		if ( $has_pulse ) {
			$classes[] = 'has-playing-effect';
			$classes[] = 'is-effect-' . sanitize_html_class( $pulse_style );
		}

		if ( $is_empty ) {
			$classes[] = 'is-empty';
		}

		$classes = array_unique( array_filter( array_map( 'sanitize_html_class', $classes ) ) );

		return implode( ' ', $classes );
	}
}
