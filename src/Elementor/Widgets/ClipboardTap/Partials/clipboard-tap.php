<?php
/**
 * @var string $copy_source         Selected copy source (manual|current_url|custom_url|shortcode).
 * @var string $value               Resolved copy value (empty for current_url — resolved in JS).
 * @var string $button_text         Button label.
 * @var string $success_message     Feedback message on successful copy.
 * @var string $empty_message       Feedback message when there is nothing to copy.
 * @var string $error_message       Feedback message when copying fails.
 * @var string $invalid_url_message Feedback message for an invalid custom URL.
 * @var int    $feedback_duration   Feedback display duration in milliseconds.
 * @var string $icon_html           Pre-rendered icon HTML (empty when no icon is selected).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="apeiron-kit-clipboard-tap-wrapper">
	<button
		class="apeiron-kit-clipboard-tap"
		type="button"
		data-copy-source="<?php echo esc_attr( $copy_source ); ?>"
		data-apeiron-clipboard="<?php echo esc_attr( $value ); ?>"
		data-copy-message="<?php echo esc_attr( $success_message ); ?>"
		data-empty-message="<?php echo esc_attr( $empty_message ); ?>"
		data-error-message="<?php echo esc_attr( $error_message ); ?>"
		data-invalid-url-message="<?php echo esc_attr( $invalid_url_message ); ?>"
		data-feedback-duration="<?php echo esc_attr( (string) $feedback_duration ); ?>"
	>
		<span class="apeiron-kit-clipboard-tap__content">
			<?php if ( '' !== $icon_html ) : ?>
				<span class="apeiron-kit-clipboard-tap__icon"><?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered by Elementor Icons_Manager. ?></span>
			<?php endif; ?>
			<span class="apeiron-kit-clipboard-tap__text"><?php echo esc_html( $button_text ); ?></span>
		</span>
	</button>
	<span class="apeiron-kit-clipboard-tap__status" role="status" aria-live="polite"></span>
</div>
<?php
