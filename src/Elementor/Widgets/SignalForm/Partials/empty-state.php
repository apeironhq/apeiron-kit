<?php
/** @var string $message */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="apeiron-signal-form">
	<div class="apeiron-signal-form__empty" role="status">
		<?php echo esc_html( $message ); ?>
	</div>
</div>
