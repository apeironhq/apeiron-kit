<?php
/**
 * @var array<string,mixed> $data            Browser runtime configuration.
 * @var array<string,mixed> $settings        Display settings.
 * @var array<int,array>   $prepared_fields Normalized field definitions.
 * @var string             $form_title      Normalized form title.
 * @var string             $title_id        Unique title ID.
 * @var string             $button_text     Submit button label.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="apeiron-signal-form" data-apeiron-wa-form="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
	<?php if ( '' !== $form_title ) : ?>
		<h3 id="<?php echo esc_attr( $title_id ); ?>" class="apeiron-signal-form__title"><?php echo esc_html( $form_title ); ?></h3>
	<?php endif; ?>
	<form novalidate<?php echo '' !== $form_title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ' aria-label="' . esc_attr__( 'Form WhatsApp', 'apeiron-kit' ) . '"'; ?>>
		<?php foreach ( $prepared_fields as $field ) : ?>
			<label class="apeiron-signal-form__field" for="<?php echo esc_attr( $this->get_field_id( $field ) ); ?>">
				<span><?php echo esc_html( $field['label'] ); ?></span>
				<?php echo $this->render_input( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget helper escapes the complete attribute string. ?>
			</label>
		<?php endforeach; ?>

		<?php if ( ! empty( $settings['show_confirmation'] ) && 'yes' === $settings['show_confirmation'] ) : ?>
			<?php $this->render_confirmation_select( $settings ); ?>
		<?php endif; ?>

		<button type="submit" class="apeiron-signal-form__button"><?php echo esc_html( $button_text ); ?></button>
		<div class="apeiron-signal-form__notice" data-apeiron-signal-notice role="status" aria-live="polite" aria-atomic="true" hidden></div>
	</form>
</div>
