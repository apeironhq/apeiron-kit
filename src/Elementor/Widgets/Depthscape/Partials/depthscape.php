<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$render_layer = static function ( array $layer ): void {
	$opacity = (float) ( $layer['start_opacity'] ?? 1 );
	?>
	<div
		class="<?php echo esc_attr( $layer['classes'] ); ?>"
		data-ds-layer="<?php echo esc_attr( $layer['key'] ); ?>"
		data-ds-index="<?php echo esc_attr( $layer['index'] ); ?>"
	>
		<div class="apeiron-depthscape__inner" style="opacity: <?php echo esc_attr( (string) $opacity ); ?>; visibility: <?php echo $opacity > 0 ? 'inherit' : 'hidden'; ?>;">
			<div class="apeiron-depthscape__idle">
				<?php echo $layer['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped per field in Depthscape::render_layer_content(). ?>
			</div>
		</div>
	</div>
	<?php
};
?>
<div
	class="apeiron-depthscape is-booting<?php echo $has_native_content ? ' has-native-content' : ''; ?>"
	id="apeiron-depthscape-<?php echo esc_attr( $widget_id ); ?>"
	data-apeiron-depthscape="<?php echo esc_attr( $config_json ); ?>"
>
	<div class="apeiron-depthscape__stage">
		<div class="apeiron-depthscape__viewport">
			<div class="apeiron-depthscape__camera">
				<?php
				foreach ( $layers as $layer ) {
					if ( 'screen' !== $layer['space'] ) {
						$render_layer( $layer );
					}
				}
				?>
			</div>
		</div>

		<div class="apeiron-depthscape__overlay" aria-hidden="true"></div>

		<div class="apeiron-depthscape__screen">
			<?php
			foreach ( $layers as $layer ) {
				if ( 'screen' === $layer['space'] ) {
					$render_layer( $layer );
				}
			}
			?>
		</div>

		<?php if ( $show_hint || $this->is_content_editor() ) : ?>
			<div class="apeiron-depthscape__hint" aria-hidden="true"<?php echo $show_hint ? '' : ' hidden'; ?> style="opacity: <?php echo esc_attr( (string) $hint_opacity ); ?>; visibility: <?php echo $hint_opacity > 0 ? 'inherit' : 'hidden'; ?>;<?php echo $show_hint ? '' : ' display:none;'; ?>">
				<span class="apeiron-depthscape__hint-arrow">
					<svg class="apeiron-depthscape__hint-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
						<path d="M6 15l6-6 6 6"></path>
					</svg>
				</span>
				<?php if ( '' !== $hint_text ) : ?>
					<span class="apeiron-depthscape__hint-text"><?php echo esc_html( $hint_text ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $this->is_content_editor() ) : ?>
		<div class="apeiron-depthscape__content-bank"></div>
	<?php endif; ?>
</div>
