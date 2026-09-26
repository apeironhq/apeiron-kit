<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div
	id="<?php echo esc_attr( $container_id ); ?>"
	class="<?php echo esc_attr( $player_classes ); ?>"
	data-apeiron-soundscape
	data-src-type="<?php echo esc_attr( $src_type ); ?>"
	data-autoplay="<?php echo esc_attr( $is_autoplay ? 'yes' : 'no' ); ?>"
	data-loop="<?php echo esc_attr( $has_loop ? 'yes' : 'no' ); ?>"
	data-start="<?php echo esc_attr( $start_sec ); ?>"
	data-end="<?php echo esc_attr( $end_sec ); ?>"
	data-pause-hidden="<?php echo esc_attr( $pause_hidden ? 'yes' : 'no' ); ?>"
	data-cover-music-start="<?php echo esc_attr( $cover_music_start ); ?>"
	data-empty-message="<?php echo esc_attr( $empty_message ); ?>"
	data-loading-message="<?php echo esc_attr( $loading_message ); ?>"
	data-error-message="<?php echo esc_attr( $error_message ); ?>"
	data-range-message="<?php echo esc_attr( $range_message ); ?>"
	<?php if ( $is_empty ) : ?>
		aria-disabled="true"
	<?php endif; ?>
>
	<?php if ( $audio_url ) : ?>
		<?php if ( $is_youtube ) : ?>
			<div data-video="<?php echo esc_url( $audio_url ); ?>" id="<?php echo esc_attr( $youtube_id ); ?>"></div>
		<?php else : ?>
			<audio id="<?php echo esc_attr( $audio_id ); ?>"<?php echo $has_loop ? ' loop' : ''; ?> preload="<?php echo $is_autoplay ? 'auto' : 'none'; ?>">
				<source
					src="<?php echo esc_url( $audio_url ); ?>"
					<?php if ( '' !== $mime_type ) : ?>
						type="<?php echo esc_attr( $mime_type ); ?>"
					<?php endif; ?>
				>
			</audio>
		<?php endif; ?>
	<?php endif; ?>

	<span class="apeiron-soundscape-status" role="status" aria-live="polite"></span>

	<button
		type="button"
		class="elementor-icon-wrapper apeiron-soundscape-toggle is-play"
		id="<?php echo esc_attr( $play_toggle_id ); ?>"
		aria-label="<?php echo esc_attr( $play_aria_label ); ?>"
		aria-pressed="false"
	>
		<span class="apeiron-soundscape-toggle-inner">
			<span class="apeiron-soundscape-icon-shell">
				<span class="elementor-icon apeiron-soundscape-icon">
					<?php echo $play_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered by Elementor Icons_Manager. ?>
				</span>
			</span>
		</span>
	</button>

	<button
		type="button"
		class="elementor-icon-wrapper apeiron-soundscape-toggle is-pause"
		id="<?php echo esc_attr( $pause_toggle_id ); ?>"
		aria-label="<?php echo esc_attr( $pause_aria_label ); ?>"
		aria-pressed="false"
	>
		<span class="apeiron-soundscape-toggle-inner">
			<span class="apeiron-soundscape-icon-shell">
				<span class="elementor-icon apeiron-soundscape-icon">
					<?php echo $pause_icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered by Elementor Icons_Manager. ?>
				</span>
			</span>
		</span>
	</button>
</div>
