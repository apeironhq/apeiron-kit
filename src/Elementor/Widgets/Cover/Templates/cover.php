<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apeiron_cover_art = is_array( $art ?? null ) ? $art : [];
?>
<div class="apeiron-cover__background" aria-hidden="true"></div>
<div class="apeiron-cover__welcome">
	<div class="apeiron-cover__welcome-inner">
		<?php if ( 'yes' === ( $settings['show_ornament'] ?? 'yes' ) && ! empty( $assets['ornament'] ) ) : ?>
			<img class="apeiron-cover__ornament" src="<?php echo esc_url( $assets['ornament'] ); ?>" alt="" loading="eager" decoding="async">
		<?php endif; ?>

		<?php if ( 'yes' === ( $settings['show_cover_photo'] ?? 'yes' ) && ! empty( $assets['photo'] ) ) : ?>
			<img class="apeiron-cover__photo" src="<?php echo esc_url( $assets['photo'] ); ?>" alt="<?php echo esc_attr( trim( (string) ( $settings['primary_name'] ?? '' ) . ' ' . ( $settings['name_separator'] ?? '&' ) . ' ' . ( $settings['secondary_name'] ?? '' ) ) ); ?>" loading="eager" decoding="async">
		<?php endif; ?>

		<?php if ( '' !== trim( (string) ( $settings['event_label'] ?? '' ) ) ) : ?>
			<div class="apeiron-cover__event-label"><?php echo esc_html( $settings['event_label'] ); ?></div>
		<?php endif; ?>

		<div class="apeiron-cover__names">
			<?php if ( '' !== trim( (string) ( $settings['primary_name'] ?? '' ) ) ) : ?>
				<div class="apeiron-cover__name"><?php echo esc_html( $settings['primary_name'] ); ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) ( $settings['name_separator'] ?? '' ) ) ) : ?>
				<div class="apeiron-cover__name-separator"><?php echo esc_html( $settings['name_separator'] ); ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) ( $settings['secondary_name'] ?? '' ) ) ) : ?>
				<div class="apeiron-cover__name"><?php echo esc_html( $settings['secondary_name'] ); ?></div>
			<?php endif; ?>
		</div>

		<?php if ( '' !== trim( (string) ( $settings['event_date'] ?? '' ) ) ) : ?>
			<div class="apeiron-cover__event-date"><?php echo esc_html( $settings['event_date'] ); ?></div>
		<?php endif; ?>
	</div>
</div>

<div class="apeiron-cover__panels" aria-hidden="true">
	<div class="apeiron-cover__panel apeiron-cover__panel--left">
		<?php echo $center_strip_left_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from fixed markup and escaped class. ?>
	</div>
	<div class="apeiron-cover__panel apeiron-cover__panel--right">
		<?php echo $center_strip_right_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from fixed markup and escaped class. ?>
	</div>
	<?php if ( 'yes' === ( $settings['show_center_strip'] ?? 'yes' ) ) : ?>
		<?php if ( '' !== $center_strip_label_top ) : ?>
			<span class="apeiron-cover__center-strip-label apeiron-cover__center-strip-label--top" aria-hidden="true"><?php echo esc_html( $center_strip_label_top ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $center_strip_label_bottom ) : ?>
			<span class="apeiron-cover__center-strip-label apeiron-cover__center-strip-label--bottom" aria-hidden="true"><?php echo esc_html( $center_strip_label_bottom ); ?></span>
		<?php endif; ?>
	<?php endif; ?>
</div>

<div class="apeiron-cover__info">
	<div class="apeiron-cover__ribbon">
		<?php if ( ! empty( $apeiron_cover_art['ribbon'] ) ) : ?>
			<span class="apeiron-cover__art apeiron-cover__art--ribbon" aria-hidden="true">
				<?php echo $apeiron_cover_art['ribbon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG file, ids namespaced by prefix_svg_ids(). ?>
			</span>
		<?php endif; ?>

		<?php echo $ribbon_text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text escaped during context construction. ?>

		<?php if ( ! empty( $apeiron_cover_art['tail'] ) ) : ?>
			<span class="apeiron-cover__art apeiron-cover__art--tail" aria-hidden="true">
				<?php echo $apeiron_cover_art['tail']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG file, ids namespaced by prefix_svg_ids(). ?>
			</span>
		<?php endif; ?>

		<div class="<?php echo esc_attr( implode( ' ', $pin_classes ) ); ?>">
			<?php if ( ! empty( $apeiron_cover_art['seal'] ) ) : ?>
				<span class="apeiron-cover__art apeiron-cover__art--seal" aria-hidden="true">
					<?php echo $apeiron_cover_art['seal']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG file, ids namespaced by prefix_svg_ids(). ?>
				</span>
			<?php endif; ?>

			<div class="apeiron-cover__pin-content">
				<?php if ( 'yes' === ( $settings['show_pin_image'] ?? '' ) && ! empty( $assets['pin'] ) ) : ?>
					<img class="apeiron-cover__pin-image" src="<?php echo esc_url( $assets['pin'] ); ?>" alt="" loading="eager" decoding="async">
				<?php elseif ( '' !== trim( (string) ( $settings['monogram_text'] ?? '' ) ) ) : ?>
					<div class="apeiron-cover__monogram"><?php echo esc_html( $settings['monogram_text'] ); ?></div>
				<?php endif; ?>
				<button class="apeiron-cover__open-button" type="button">
					<?php foreach ( $button_lines as $button_line ) : ?>
						<span class="apeiron-cover__open-button-line"><?php echo esc_html( $button_line ); ?></span>
					<?php endforeach; ?>
				</button>
			</div>
		</div>
	</div>

	<?php if ( 'yes' === ( $settings['show_recipient_card'] ?? 'yes' ) ) : ?>
		<div class="apeiron-cover__recipient">
			<div class="apeiron-cover__recipient-card">
				<?php if ( ! empty( $apeiron_cover_art['tag'] ) ) : ?>
					<span class="apeiron-cover__art apeiron-cover__art--tag" aria-hidden="true">
						<?php echo $apeiron_cover_art['tag']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG file, ids namespaced by prefix_svg_ids(). ?>
					</span>
				<?php endif; ?>

				<div class="apeiron-cover__recipient-content">
					<?php if ( '' !== trim( (string) ( $settings['recipient_heading'] ?? '' ) ) ) : ?>
						<div class="apeiron-cover__recipient-heading"><?php echo esc_html( $settings['recipient_heading'] ); ?></div>
					<?php endif; ?>
					<div class="apeiron-cover__recipient-name" data-apeiron-cover-recipient>
						<?php echo esc_html( $settings['recipient_text'] ?? __( 'Tamu Undangan', 'apeiron-kit' ) ); ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
