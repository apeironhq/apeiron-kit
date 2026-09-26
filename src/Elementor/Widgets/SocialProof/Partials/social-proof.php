<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SocialProof\Partials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array<string,mixed> $context */

$entries_json = wp_json_encode( $context['entries'] );
if ( false === $entries_json ) {
	return;
}
$animation_duration_seconds = max(
	0.1,
	min( 2, (float) ( $context['animation_duration'] ?? 400 ) / 1000 )
);

$popup_classes = [
	'apeiron-social-proof',
	// Preserve legacy custom CSS hooks.
	'apeiron-social-proof__popup',
	'apeiron-social-proof-popup',
	'apeiron-sp-animation-' . $context['animation_type'],
	'apeiron-sp-h-' . $context['position_horizontal'],
	'apeiron-sp-v-' . $context['position_vertical'],
];
if ( false === ( $context['box_shadow_enabled'] ?? true ) ) {
	$popup_classes[] = 'apeiron-sp-shadow-disabled';
}
?>
<div
	class="<?php echo esc_attr( implode( ' ', $popup_classes ) ); ?>"
	style="--apeiron-sp-image-radius-global: <?php echo esc_attr( (string) $context['image_radius'] ); ?>px; --apeiron-sp-duration: <?php echo esc_attr( (string) $animation_duration_seconds ); ?>s;"
	role="status"
	aria-live="polite"
	aria-atomic="true"
	data-apeiron-social-proof-instance-id="<?php echo esc_attr( $context['instance_id'] ); ?>"
	data-apeiron-social-proof-entries="<?php echo esc_attr( $entries_json ); ?>"
	data-apeiron-social-proof-text-template="<?php echo esc_attr( $context['text_template'] ); ?>"
	data-apeiron-social-proof-initial-delay="<?php echo esc_attr( (string) $context['initial_delay'] ); ?>"
	data-apeiron-social-proof-display-duration="<?php echo esc_attr( (string) $context['display_duration'] ); ?>"
	data-apeiron-social-proof-interval-duration="<?php echo esc_attr( (string) $context['interval_duration'] ); ?>"
	data-apeiron-social-proof-max-notifications="<?php echo esc_attr( (string) $context['max_notifications'] ); ?>"
	data-apeiron-social-proof-animation-duration="<?php echo esc_attr( (string) $context['animation_duration'] ); ?>"
>
	<button type="button" class="apeiron-social-proof__close apeiron-popup-close" aria-label="<?php esc_attr_e( 'Tutup notifikasi social proof', 'apeiron-kit' ); ?>">
		<span aria-hidden="true">&times;</span>
	</button>
	<div class="apeiron-social-proof__content apeiron-popup-content">
		<div class="apeiron-social-proof__image apeiron-popup-image is-placeholder">
			<svg class="apeiron-social-proof__placeholder-icon apeiron-popup-placeholder-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
		</div>
		<div class="apeiron-social-proof__text apeiron-popup-text">
			<div class="apeiron-social-proof__name apeiron-popup-name"></div>
			<div class="apeiron-social-proof__description apeiron-popup-desc"></div>
			<div class="apeiron-social-proof__date apeiron-popup-date"></div>
		</div>
	</div>
</div>
