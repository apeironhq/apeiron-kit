<?php
/**
 * Auto Scroll widget markup partial.
 *
 * @package ApeironKit
 * @since   1.0.5
 *
 * @var string              $widget_id       Elementor widget id.
 * @var string              $position_class  Position class (e.g. `pos-bottom-right`).
 * @var array<string,mixed> $settings        Sanitized settings for display.
 * @var array<string,mixed> $config          JS runtime config (will be JSON encoded).
 * @var string              $icon_start      Rendered start icon HTML.
 * @var string              $icon_stop       Rendered stop icon HTML.
 * @var string              $icon_minus      Rendered minus icon HTML.
 * @var string              $icon_plus       Rendered plus icon HTML.
 * @var string              $icon_scroll_top Rendered scroll-top icon HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_speed_control = 'yes' === ( $settings['show_speed_control'] ?? '' );
$show_progress      = 'yes' === ( $settings['show_progress'] ?? '' );
$progress_type      = (string) ( $settings['progress_type'] ?? 'circle' );
$show_scroll_top    = 'yes' === ( $settings['show_scroll_top'] ?? '' );
$ripple_enable      = 'yes' === ( $settings['ripple_enable'] ?? '' );
$ripple_mode        = (string) ( $settings['ripple_mode'] ?? 'infinite' );
$show_speed_arrows  = 'yes' === ( $settings['show_speed_arrows'] ?? '' );
$speed_layout       = (string) ( $settings['speed_control_layout'] ?? 'vertical' );
$speed_position     = (string) ( $settings['speed_control_position'] ?? 'auto' );
$speed_draggable    = 'yes' === ( $settings['speed_control_draggable'] ?? '' );
$speed_slider_size  = (float) ( $settings['speed_slider_width']['size'] ?? 96 );
$compact_speed_size = 'vertical' === $speed_layout && in_array( $speed_slider_size, [ 96.0, 132.0, 246.0 ], true );
?>
<div
	class="apeiron-kit-autoscroll apeiron-autoscroll-wrap <?php echo esc_attr( $position_class ); ?>"
	id="apeiron-autoscroll-<?php echo esc_attr( $widget_id ); ?>"
	data-config="<?php echo esc_attr( (string) wp_json_encode( $config ) ); ?>"
>
	<div class="apeiron-btn-container">
		<?php if ( $show_progress && 'circle' === $progress_type ) : ?>
			<svg class="apeiron-progress-ring" viewBox="0 0 44 44" aria-hidden="true">
				<circle class="track" cx="22" cy="22" r="20" fill="none" />
				<circle class="progress" cx="22" cy="22" r="20" fill="none" stroke-dasharray="125.6" stroke-dashoffset="125.6" />
			</svg>
		<?php endif; ?>

		<button
			class="apeiron-scroll-btn"
			type="button"
			aria-label="<?php echo esc_attr( (string) $config['buttonStartLabel'] ); ?>"
			aria-pressed="false"
		>
			<span class="btn-icon"><?php echo $icon_start; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG-aware sanitization is applied by RenderContext. ?></span>
		</button>

		<?php if ( $ripple_enable ) : ?>
			<span class="ak-ripple-ring ripple-mode-<?php echo esc_attr( $ripple_mode ); ?>" aria-hidden="true"></span>
			<?php if ( 'double' === $ripple_mode ) : ?>
				<span class="ak-ripple-ring ak-ripple-ring-2 ripple-mode-double" aria-hidden="true"></span>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<?php if ( $show_speed_control ) : ?>
		<div class="apeiron-speed-control layout-<?php echo esc_attr( $speed_layout ); ?> pos-<?php echo esc_attr( $speed_position ); ?><?php echo $speed_draggable ? ' draggable' : ''; ?><?php echo $compact_speed_size ? ' is-default-speed-size' : ''; ?>">
			<?php if ( $speed_draggable ) : ?>
				<div class="speed-drag-handle" aria-hidden="true">
					<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="5" r="2"/><circle cx="12" cy="5" r="2"/><circle cx="19" cy="5" r="2"/><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
				</div>
			<?php endif; ?>
			<div class="speed-inner">
				<input
					type="range"
					class="apeiron-speed-slider"
					min="1"
					max="100"
					value="<?php echo esc_attr( (string) $config['speed'] ); ?>"
					aria-orientation="<?php echo esc_attr( 'vertical' === $speed_layout ? 'vertical' : 'horizontal' ); ?>"
					<?php if ( 'vertical' === $speed_layout ) : ?>orient="vertical"<?php endif; ?>
					aria-label="<?php esc_attr_e( 'Scroll speed', 'apeiron-kit' ); ?>"
				>
				<span class="speed-value"><?php echo esc_html( (string) $config['speed'] ); ?></span>
			</div>
			<?php if ( $show_speed_arrows ) : ?>
				<div class="speed-arrows">
					<button type="button" class="speed-arrow speed-minus" aria-label="<?php esc_attr_e( 'Kurangi Kecepatan', 'apeiron-kit' ); ?>">
						<span class="speed-arrow-icon-wrap"><?php echo $icon_minus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized by RenderContext. ?></span>
					</button>
					<button type="button" class="speed-arrow speed-plus" aria-label="<?php esc_attr_e( 'Tambah Kecepatan', 'apeiron-kit' ); ?>">
						<span class="speed-arrow-icon-wrap"><?php echo $icon_plus; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized by RenderContext. ?></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_scroll_top ) : ?>
		<button class="apeiron-scroll-top-btn" type="button" aria-label="<?php esc_attr_e( 'Scroll ke Atas', 'apeiron-kit' ); ?>">
			<?php echo $icon_scroll_top; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized by RenderContext. ?>
		</button>
	<?php endif; ?>

	<?php if ( $show_progress && 'bar' === $progress_type ) : ?>
		<div class="apeiron-progress-bar">
			<div class="bar-fill"></div>
		</div>
	<?php endif; ?>
</div>
