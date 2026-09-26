<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Countdown;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\Countdown\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\Countdown\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Countdown extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const DIGIT_PADDING_MIN = 2;
	private const DIGIT_PADDING_MAX = 4;

	private const WRAPPER_DEFAULTS = [
		'background_color' => '#f7f7fb',
		'border'           => [
			'top'      => '1',
			'right'    => '1',
			'bottom'   => '1',
			'left'     => '1',
			'unit'     => 'px',
			'isLinked' => true,
		],
		'border_color'     => '#e5e7ef',
		'border_radius'    => [
			'top'    => '8',
			'right'  => '8',
			'bottom' => '8',
			'left'   => '8',
			'unit'   => 'px',
		],
		'padding'          => [
			'top'    => '8',
			'right'  => '8',
			'bottom' => '8',
			'left'   => '8',
			'unit'   => 'px',
		],
	];

	public function get_name() {
		return 'apeiron-countdown';
	}

	public function get_title() {
		return __( 'Hitung Mundur', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-countdown';
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-countdown';

		return $styles;
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-countdown-js';

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
		$parts    = $this->get_visible_parts( $settings );

		if ( empty( $settings['target_date'] ) ) {
			$this->render_configuration_notice( __( 'Tanggal & waktu selesai belum diatur.', 'apeiron-kit' ) );
			return;
		}

		if ( empty( $parts ) ) {
			$this->render_configuration_notice( __( 'Aktifkan minimal satu unit waktu.', 'apeiron-kit' ) );
			return;
		}

		$target_timestamp = TargetDateParser::parse( (string) $settings['target_date'] );
		if ( false === $target_timestamp ) {
			$this->render_configuration_notice( __( 'Format tanggal & waktu selesai tidak valid.', 'apeiron-kit' ) );
			return;
		}

		/** Filters target timestamp before browser output. */
		$target_timestamp = (int) apply_filters(
			'apeiron_countdown_target_timestamp',
			$target_timestamp,
			$settings,
			$this
		);

		$context = [
			'settings'       => $settings,
			'parts'          => $parts,
			'target_ms'      => $target_timestamp * 1000,
			'server_now_ms'  => time() * 1000,
			'show_separator' => 'yes' === ( $settings['show_separator'] ?? '' ),
			'separator_text' => (string) ( $settings['separator_text'] ?? ':' ),
			'expired_text'   => (string) ( $settings['expired_text'] ?? '' ),
			'digit_padding'  => $this->get_digit_padding( $settings ),
		];

		/** Filters render context before output. */
		$context = (array) apply_filters(
			'apeiron_countdown_render_context',
			$context,
			$settings,
			$this
		);

		$this->render_countdown( $context );
	}

	private function resolve_dynamic_settings( array $settings, array $raw_settings ): array {
		$dynamic = is_array( $raw_settings['__dynamic__'] ?? null ) ? $raw_settings['__dynamic__'] : [];
		$defaults = [
			'expired_text'  => __( 'Waktu habis!', 'apeiron-kit' ),
			'label_days'    => __( 'Hari', 'apeiron-kit' ),
			'label_hours'   => __( 'Jam', 'apeiron-kit' ),
			'label_minutes' => __( 'Menit', 'apeiron-kit' ),
			'label_seconds' => __( 'Detik', 'apeiron-kit' ),
			'separator_text' => ':',
		];

		foreach ( $defaults as $key => $default ) {
			$value = is_scalar( $settings[ $key ] ?? null ) ? sanitize_text_field( (string) $settings[ $key ] ) : '';
			if ( '' === trim( $value ) && array_key_exists( $key, $dynamic ) ) {
				$raw_value = is_scalar( $raw_settings[ $key ] ?? null ) ? sanitize_text_field( (string) $raw_settings[ $key ] ) : '';
				$value     = '' !== trim( $raw_value ) ? $raw_value : $default;
			}
			$settings[ $key ] = $value;
		}

		$target = is_scalar( $settings['target_date'] ?? null ) ? sanitize_text_field( (string) $settings['target_date'] ) : '';
		if ( array_key_exists( 'target_date', $dynamic ) ) {
			$target = $this->normalize_dynamic_target( $target );
			if ( '' === $target || false === TargetDateParser::parse( $target ) ) {
				$raw_target = is_scalar( $raw_settings['target_date'] ?? null ) ? sanitize_text_field( (string) $raw_settings['target_date'] ) : '';
				$target     = false !== TargetDateParser::parse( $raw_target ) ? $raw_target : wp_date( 'Y-m-d H:i', time() + WEEK_IN_SECONDS );
			}
		}
		$settings['target_date'] = $target;

		return $settings;
	}

	private function normalize_dynamic_target( string $value ): string {
		$value = trim( $value );
		if ( preg_match( '/^\d{10}(?:\d{3})?$/', $value ) ) {
			$timestamp = 13 === strlen( $value ) ? (int) floor( (int) $value / 1000 ) : (int) $value;

			return wp_date( 'Y-m-d H:i:s', $timestamp, wp_timezone() );
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:?\d{2})$/', $value ) ) {
			try {
				return ( new \DateTimeImmutable( $value ) )->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $exception ) {
				return '';
			}
		}

		return $value;
	}

	/** @return array<string,string> */
	private function get_visible_parts( array $settings ): array {
		$definitions = [
			'days'    => [ 'show_days', 'label_days', __( 'Hari', 'apeiron-kit' ) ],
			'hours'   => [ 'show_hours', 'label_hours', __( 'Jam', 'apeiron-kit' ) ],
			'minutes' => [ 'show_minutes', 'label_minutes', __( 'Menit', 'apeiron-kit' ) ],
			'seconds' => [ 'show_seconds', 'label_seconds', __( 'Detik', 'apeiron-kit' ) ],
		];
		$parts       = [];

		foreach ( $definitions as $name => [ $visibility_key, $label_key, $default_label ] ) {
			if ( 'yes' === ( $settings[ $visibility_key ] ?? 'yes' ) ) {
				$parts[ $name ] = (string) ( $settings[ $label_key ] ?? $default_label );
			}
		}

		return (array) apply_filters( 'apeiron_countdown_visible_parts', $parts, $settings, $this );
	}

	private function get_digit_padding( array $settings ): int {
		$raw = isset( $settings['digit_padding'] ) ? (int) $settings['digit_padding'] : self::DIGIT_PADDING_MIN;

		return max( self::DIGIT_PADDING_MIN, min( self::DIGIT_PADDING_MAX, $raw ) );
	}

	private function render_countdown( array $context ): void {
		$parts          = $context['parts'];
		$show_separator = (bool) $context['show_separator'];
		$separator_text = (string) $context['separator_text'];
		$expired_text   = (string) $context['expired_text'];
		$target_ms      = (int) $context['target_ms'];
		$server_now_ms  = (int) $context['server_now_ms'];
		$digit_padding  = (int) $context['digit_padding'];
		?>
		<div class="apeiron-kit-countdown-wrapper">
			<div
				class="apeiron-kit-countdown"
				data-apeiron-countdown="<?php echo esc_attr( (string) $target_ms ); ?>"
				data-apeiron-server-now="<?php echo esc_attr( (string) $server_now_ms ); ?>"
				data-apeiron-digit-padding="<?php echo esc_attr( (string) $digit_padding ); ?>"
			>
				<?php foreach ( $parts as $name => $label ) : ?>
					<?php if ( $show_separator && $name !== array_key_first( $parts ) ) : ?>
						<span class="apeiron-kit-countdown__separator" aria-hidden="true"><?php echo esc_html( $separator_text ); ?></span>
					<?php endif; ?>
					<div class="apeiron-kit-countdown__item">
						<span class="apeiron-kit-countdown__value" data-apeiron-countdown-part="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( str_pad( '0', $digit_padding, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="apeiron-kit-countdown__label"><?php echo esc_html( $label ); ?></span>
					</div>
				<?php endforeach; ?>
				<?php if ( '' !== $expired_text ) : ?>
					<div class="apeiron-kit-countdown__expired" role="status" aria-live="polite" hidden><?php echo esc_html( $expired_text ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_configuration_notice( string $message ): void {
		if ( $this->is_elementor_editor_preview() ) {
			printf( '<div class="elementor-alert elementor-alert-warning">%s</div>', esc_html( $message ) );
		}
	}
}
