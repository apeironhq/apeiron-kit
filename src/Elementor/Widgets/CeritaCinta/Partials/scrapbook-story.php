<?php

use Elementor\Icons_Manager;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $stories */
/** @var string $clip_id */
$skin               = 'scrapbook-collage' === ( $settings['skin'] ?? '' ) ? 'scrapbook-collage' : 'scrapbook-story';
$timeline_skin      = in_array( $settings['timeline_skin'] ?? '', [ 'doodle-path', 'ribbon-trail', 'journey-icon' ], true ) ? $settings['timeline_skin'] : 'doodle-path';
$entrance_animation = in_array( $settings['card_entrance_animation'] ?? '', [ 'none', 'fade', 'slide-up', 'slide-alternate', 'zoom-in' ], true ) ? $settings['card_entrance_animation'] : 'slide-up';
$dot_shape          = in_array( $settings['dot_shape'] ?? '', [ 'circle', 'square', 'rounded', 'diamond' ], true ) ? $settings['dot_shape'] : 'circle';
$marker_type        = 'image' === ( $settings['journey_marker_type'] ?? '' ) ? 'image' : 'icon';
$marker_icon        = is_array( $settings['journey_marker_icon'] ?? null ) ? $settings['journey_marker_icon'] : [ 'value' => 'fas fa-heartbeat', 'library' => 'fa-solid' ];
$marker_image       = is_array( $settings['journey_marker_image'] ?? null ) ? $settings['journey_marker_image'] : [];
?>
<div class="apeiron-cerita-cinta apeiron-cerita-cinta--scrapbook-story apeiron-cerita-cinta--skin-<?php echo esc_attr( $skin ); ?> apeiron-cerita-cinta--timeline-<?php echo esc_attr( $timeline_skin ); ?> apeiron-cerita-cinta--entrance-<?php echo esc_attr( $entrance_animation ); ?> apeiron-cerita-cinta--dot-<?php echo esc_attr( $dot_shape ); ?>" data-apeiron-cerita-cinta data-apeiron-cerita-cinta-skin="<?php echo esc_attr( $skin ); ?>" data-apeiron-cerita-cinta-timeline="<?php echo esc_attr( $timeline_skin ); ?>">
	<?php if ( '' !== ( $settings['heading'] ?? '' ) || '' !== ( $settings['subheading'] ?? '' ) ) : ?>
		<div class="apeiron-cerita-cinta__header">
			<?php if ( '' !== ( $settings['heading'] ?? '' ) ) : ?>
				<h2 class="apeiron-cerita-cinta__heading"><?php echo esc_html( (string) $settings['heading'] ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== ( $settings['subheading'] ?? '' ) ) : ?>
				<div class="apeiron-cerita-cinta__subheading"><?php echo nl2br( esc_html( (string) $settings['subheading'] ) ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="apeiron-cerita-cinta__track">
		<span class="apeiron-cerita-cinta__skin-measure apeiron-cerita-cinta__layout-measure" aria-hidden="true"></span>
		<svg class="apeiron-cerita-cinta__timeline" aria-hidden="true" focusable="false" preserveAspectRatio="none">
			<defs>
				<clipPath id="<?php echo esc_attr( $clip_id ); ?>" clipPathUnits="userSpaceOnUse">
					<rect class="apeiron-cerita-cinta__progress" x="0" y="0" width="100%" height="0" />
				</clipPath>
			</defs>
			<path class="apeiron-cerita-cinta__line-base" />
			<g clip-path="url(#<?php echo esc_attr( $clip_id ); ?>)">
				<path class="apeiron-cerita-cinta__line-active" />
				<path class="apeiron-cerita-cinta__ribbon-stitch" />
			</g>
		</svg>
		<?php if ( 'journey-icon' === $timeline_skin ) : ?>
			<span class="apeiron-cerita-cinta__journey-marker" aria-hidden="true">
				<?php if ( 'image' === $marker_type && ! empty( $marker_image['url'] ) ) : ?>
					<img src="<?php echo esc_url( (string) $marker_image['url'] ); ?>" alt="" decoding="async" />
				<?php elseif ( 'icon' === $marker_type && ! empty( $marker_icon['value'] ) ) : ?>
					<?php Icons_Manager::render_icon( $marker_icon, [ 'aria-hidden' => 'true', 'focusable' => 'false' ] ); ?>
				<?php endif; ?>
			</span>
		<?php endif; ?>
		<ol class="apeiron-cerita-cinta__list">
			<?php foreach ( $stories as $story ) : ?>
				<?php
				$photo          = is_array( $story['photo'] ?? null ) ? $story['photo'] : [];
				$photo_url      = (string) ( $photo['url'] ?? '' );
				$gallery        = is_array( $story['photo_gallery'] ?? null ) ? $story['photo_gallery'] : [];
				if ( 'single' === ( $story['photo_mode'] ?? '' ) ) {
					$gallery = [];
				}
				$photos         = array_values( array_filter( $gallery, static function ( $image ): bool {
					return is_array( $image ) && ( '' !== (string) ( $image['url'] ?? '' ) || absint( $image['id'] ?? 0 ) > 0 );
				} ) );
				if ( empty( $photos ) && ( '' !== $photo_url || absint( $photo['id'] ?? 0 ) > 0 ) && 'slideshow' !== ( $story['photo_mode'] ?? '' ) ) {
					$photos = [ $photo ];
				}
				$slideshow      = count( $photos ) > 1;
				$effect         = 'fade' === ( $story['slideshow_effect'] ?? '' ) ? 'fade' : 'slide';
				$duration       = max( 1, min( 60, (float) ( $story['slideshow_duration'] ?? 4 ) ) ) * 1000;
				$speed          = max( 0, min( 3000, (int) ( $story['slideshow_speed'] ?? 600 ) ) );
				$autoplay       = 'yes' === ( $story['slideshow_autoplay'] ?? 'yes' );
				$loop           = 'yes' === ( $story['slideshow_loop'] ?? 'yes' );
				$icon           = is_array( $story['icon'] ?? null ) ? $story['icon'] : [];
				$ornament_type  = $story['ornament_type'] ?? 'icon';
				$ornament_icon  = is_array( $story['ornament_icon'] ?? null ) ? $story['ornament_icon'] : [ 'value' => 'far fa-heart', 'library' => 'fa-regular' ];
				$ornament_image = is_array( $story['ornament_image'] ?? null ) ? $story['ornament_image'] : [];
				$tape_type      = in_array( $story['tape_type'] ?? 'tape', [ 'tape', 'image', 'none' ], true ) ? ( $story['tape_type'] ?? 'tape' ) : 'tape';
				$tape_image     = is_array( $story['tape_image'] ?? null ) ? $story['tape_image'] : [];
				$show_tape      = 'tape' === $tape_type || ( 'image' === $tape_type && ! empty( $tape_image['url'] ) );
				$title          = (string) ( $story['title'] ?? '' );
				$item_id        = sanitize_html_class( (string) ( $story['_id'] ?? '' ) );
				?>
				<li class="apeiron-cerita-cinta__item elementor-repeater-item-<?php echo esc_attr( $item_id ); ?>">
					<span class="apeiron-cerita-cinta__card-position-measure" aria-hidden="true"></span>
					<span class="apeiron-cerita-cinta__dot" aria-hidden="true">
						<?php if ( ! empty( $icon['value'] ) ) : ?>
							<?php Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true', 'focusable' => 'false' ] ); ?>
						<?php else : ?>
							<span class="apeiron-cerita-cinta__dot-core"></span>
						<?php endif; ?>
					</span>
					<div class="apeiron-cerita-cinta__composition<?php echo empty( $photos ) ? ' is-without-photo' : ''; ?>">
						<article class="apeiron-cerita-cinta__paper">
							<?php if ( ! empty( $photos ) ) : ?>
								<figure class="apeiron-cerita-cinta__polaroid">
									<?php if ( 'icon' === $ornament_type && ! empty( $ornament_icon['value'] ) ) : ?>
										<span class="apeiron-cerita-cinta__doodle" aria-hidden="true"><?php Icons_Manager::render_icon( $ornament_icon, [ 'aria-hidden' => 'true', 'focusable' => 'false' ] ); ?></span>
									<?php elseif ( 'image' === $ornament_type && ! empty( $ornament_image['url'] ) ) : ?>
										<span class="apeiron-cerita-cinta__doodle" aria-hidden="true"><img src="<?php echo esc_url( (string) $ornament_image['url'] ); ?>" alt="" loading="lazy" decoding="async" /></span>
									<?php endif; ?>
									<?php if ( $show_tape ) : ?>
										<span class="apeiron-cerita-cinta__photo-tape<?php echo 'image' === $tape_type ? ' is-image' : ''; ?>" aria-hidden="true">
											<?php if ( 'image' === $tape_type ) : ?>
												<img src="<?php echo esc_url( (string) $tape_image['url'] ); ?>" alt="" loading="lazy" decoding="async" />
											<?php endif; ?>
										</span>
									<?php endif; ?>
									<?php if ( $slideshow ) : ?>
										<div class="apeiron-cerita-cinta__slideshow is-<?php echo esc_attr( $effect ); ?>" data-apeiron-cerita-cinta-slideshow data-duration="<?php echo esc_attr( (string) $duration ); ?>" data-speed="<?php echo esc_attr( (string) $speed ); ?>" data-autoplay="<?php echo $autoplay ? 'yes' : 'no'; ?>" data-loop="<?php echo $loop ? 'yes' : 'no'; ?>" style="--cc-slide-speed:<?php echo esc_attr( (string) $speed ); ?>ms;">
									<?php endif; ?>
									<?php foreach ( $photos as $slide_index => $slide ) : ?>
										<?php
										$photo_id  = absint( $slide['id'] ?? 0 );
										$photo_url = (string) ( $slide['url'] ?? '' );
										?>
										<?php if ( $slideshow ) : ?>
											<div class="apeiron-cerita-cinta__slide<?php echo 0 === $slide_index ? ' is-current' : ''; ?>" aria-hidden="<?php echo 0 === $slide_index ? 'false' : 'true'; ?>">
										<?php endif; ?>
										<?php if ( $photo_id ) : ?>
											<?php echo wp_get_attachment_image( $photo_id, 'large', false, [ 'class' => 'apeiron-cerita-cinta__photo', 'loading' => 'lazy' ] ); ?>
										<?php else : ?>
											<img class="apeiron-cerita-cinta__photo" src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( Utils::get_placeholder_image_src() === $photo_url ? '' : $title ); ?>" loading="lazy" decoding="async" />
										<?php endif; ?>
										<?php if ( $slideshow ) : ?>
											</div>
										<?php endif; ?>
									<?php endforeach; ?>
									<?php if ( $slideshow ) : ?>
										</div>
									<?php endif; ?>
									<?php if ( $slideshow ) : ?>
										<div class="apeiron-cerita-cinta__slideshow-controls">
											<button type="button" data-cc-slide="previous" aria-label="<?php esc_attr_e( 'Foto sebelumnya', 'apeiron-kit' ); ?>">&#8249;</button>
											<button type="button" data-cc-slide="next" aria-label="<?php esc_attr_e( 'Foto berikutnya', 'apeiron-kit' ); ?>">&#8250;</button>
										</div>
									<?php endif; ?>
								</figure>
							<?php endif; ?>
							<div class="apeiron-cerita-cinta__story-body">
								<?php if ( $show_tape ) : ?>
									<span class="apeiron-cerita-cinta__tape<?php echo 'image' === $tape_type ? ' is-image' : ''; ?>" aria-hidden="true">
										<?php if ( 'image' === $tape_type ) : ?>
											<img src="<?php echo esc_url( (string) $tape_image['url'] ); ?>" alt="" loading="lazy" decoding="async" />
										<?php endif; ?>
									</span>
								<?php endif; ?>
								<?php if ( empty( $photos ) && 'icon' === $ornament_type && ! empty( $ornament_icon['value'] ) ) : ?>
									<span class="apeiron-cerita-cinta__doodle" aria-hidden="true"><?php Icons_Manager::render_icon( $ornament_icon, [ 'aria-hidden' => 'true', 'focusable' => 'false' ] ); ?></span>
								<?php elseif ( empty( $photos ) && 'image' === $ornament_type && ! empty( $ornament_image['url'] ) ) : ?>
									<span class="apeiron-cerita-cinta__doodle" aria-hidden="true"><img src="<?php echo esc_url( (string) $ornament_image['url'] ); ?>" alt="" loading="lazy" decoding="async" /></span>
								<?php endif; ?>
								<div class="apeiron-cerita-cinta__story-copy">
									<?php if ( '' !== ( $story['date'] ?? '' ) ) : ?>
										<div class="apeiron-cerita-cinta__date"><?php echo esc_html( (string) $story['date'] ); ?></div>
									<?php endif; ?>
									<?php if ( '' !== $title ) : ?>
										<h3 class="apeiron-cerita-cinta__title"><?php echo esc_html( $title ); ?></h3>
									<?php endif; ?>
									<?php if ( '' !== ( $story['description'] ?? '' ) ) : ?>
										<div class="apeiron-cerita-cinta__description"><?php echo nl2br( esc_html( (string) $story['description'] ) ); ?></div>
									<?php endif; ?>
								</div>
							</div>
						</article>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</div>
