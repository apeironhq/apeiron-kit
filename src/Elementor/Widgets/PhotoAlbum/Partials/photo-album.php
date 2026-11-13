<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array  $settings */
/** @var array  $photos */
/** @var array  $flags */
/** @var string $title_tag */
/** @var string $lightbox_id */
/** @var string $render_attributes */
$total       = count( $photos );
$pad         = static function ( int $number ): string {
	return str_pad( (string) $number, 2, '0', STR_PAD_LEFT );
};
$first       = $photos[0];
$second      = $photos[ $total > 1 ? 1 : 0 ];
$arrow_next  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$arrow_prev  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$svg_allowed = [
	'svg'  => [ 'viewbox' => true, 'aria-hidden' => true, 'focusable' => true ],
	'path' => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
];
?>
<div <?php echo $render_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor escapes render attributes ?>>
	<?php if ( $flags['title'] ) : ?>
		<div class="apeiron-photo-album__header<?php echo $flags['title_line'] ? ' has-line' : ''; ?>">
			<span class="apeiron-photo-album__title-line apeiron-photo-album__title-line--start" aria-hidden="true"></span>
			<<?php echo esc_html( $title_tag ); ?> class="apeiron-photo-album__title"><?php echo esc_html( (string) $settings['title'] ); ?></<?php echo esc_html( $title_tag ); ?>>
			<span class="apeiron-photo-album__title-line apeiron-photo-album__title-line--end" aria-hidden="true"></span>
		</div>
	<?php endif; ?>

	<div class="apeiron-photo-album__body<?php echo $flags['thumbs'] ? ' has-thumbs' : ''; ?>">
		<div class="apeiron-photo-album__stage" data-apeiron-photo-album-stage>
			<div class="apeiron-photo-album__viewport" aria-roledescription="carousel" aria-label="<?php echo esc_attr( $flags['title'] ? (string) $settings['title'] : __( 'Album Foto', 'apeiron-kit' ) ); ?>">
				<?php foreach ( $photos as $index => $photo ) : ?>
					<figure class="apeiron-photo-album__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr( (string) $index ); ?>" data-caption="<?php echo esc_attr( $photo['caption'] ); ?>" data-src="<?php echo esc_url( $photo['src'] ); ?>" data-srcset="<?php echo esc_attr( $photo['srcset'] ); ?>" aria-roledescription="slide" aria-label="<?php echo esc_attr( $pad( $index + 1 ) . ' / ' . $pad( $total ) ); ?>"<?php echo 0 === $index ? '' : ' aria-hidden="true"'; ?>>
						<?php if ( $flags['lightbox'] ) : ?>
							<a class="apeiron-photo-album__link" href="<?php echo esc_url( $photo['full'] ); ?>" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="<?php echo esc_attr( $lightbox_id ); ?>" data-elementor-lightbox-title="<?php echo esc_attr( $photo['caption'] ); ?>"<?php echo 0 === $index ? '' : ' tabindex="-1"'; ?>>
						<?php endif; ?>
						<img class="apeiron-photo-album__image" src="<?php echo esc_url( $photo['src'] ); ?>"<?php echo '' !== $photo['srcset'] ? ' srcset="' . esc_attr( $photo['srcset'] ) . '" sizes="(max-width: 767px) 100vw, 60vw"' : ''; ?> alt="<?php echo esc_attr( '' !== $photo['alt'] ? $photo['alt'] : $photo['caption'] ); ?>" decoding="async"<?php echo 0 === $index ? '' : ' loading="lazy"'; ?> />
						<?php if ( $flags['lightbox'] ) : ?>
							</a>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( $flags['peek'] ) : ?>
				<button type="button" class="apeiron-photo-album__peek" data-apeiron-photo-album-next aria-label="<?php esc_attr_e( 'Foto berikutnya', 'apeiron-kit' ); ?>">
					<img class="apeiron-photo-album__peek-image" src="<?php echo esc_url( $second['src'] ); ?>"<?php echo '' !== $second['srcset'] ? ' srcset="' . esc_attr( $second['srcset'] ) . '" sizes="(max-width: 767px) 40vw, 20vw"' : ''; ?> alt="" decoding="async" />
				</button>
			<?php endif; ?>
		</div>

		<div class="apeiron-photo-album__meta">
			<?php if ( $flags['counter'] ) : ?>
				<div class="apeiron-photo-album__counter">
					<span class="apeiron-photo-album__current" data-apeiron-photo-album-current><?php echo esc_html( $pad( 1 ) ); ?></span>
					<span class="apeiron-photo-album__total">/ <?php echo esc_html( $pad( $total ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $flags['counter'] && $flags['caption'] ) : ?>
				<span class="apeiron-photo-album__rule" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ( $flags['caption'] ) : ?>
				<p class="apeiron-photo-album__caption" data-apeiron-photo-album-caption aria-live="polite"><?php echo esc_html( $first['caption'] ); ?></p>
			<?php endif; ?>
			<?php if ( $flags['prev'] || $flags['next'] ) : ?>
				<div class="apeiron-photo-album__nav">
					<?php if ( $flags['prev'] ) : ?>
						<button type="button" class="apeiron-photo-album__button apeiron-photo-album__button--prev" data-apeiron-photo-album-prev aria-label="<?php esc_attr_e( 'Foto sebelumnya', 'apeiron-kit' ); ?>"><?php echo wp_kses( $arrow_prev, $svg_allowed ); ?></button>
					<?php endif; ?>
					<?php if ( $flags['next'] ) : ?>
						<button type="button" class="apeiron-photo-album__button apeiron-photo-album__button--next" data-apeiron-photo-album-next aria-label="<?php esc_attr_e( 'Foto berikutnya', 'apeiron-kit' ); ?>"><?php echo wp_kses( $arrow_next, $svg_allowed ); ?></button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $flags['thumbs'] ) : ?>
			<div class="apeiron-photo-album__thumbs<?php echo $flags['numbers'] ? ' has-numbers' : ''; ?>" data-apeiron-photo-album-thumbs>
				<?php foreach ( $photos as $index => $photo ) : ?>
					<button type="button" class="apeiron-photo-album__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-apeiron-photo-album-thumb="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: photo number. */ __( 'Tampilkan foto %s', 'apeiron-kit' ), $pad( $index + 1 ) ) ); ?>"<?php echo 0 === $index ? ' aria-current="true"' : ''; ?>>
						<span class="apeiron-photo-album__thumb-frame"><img src="<?php echo esc_url( $photo['thumb'] ); ?>" alt="" decoding="async" loading="lazy" /></span>
						<?php if ( $flags['numbers'] ) : ?>
							<span class="apeiron-photo-album__thumb-number" aria-hidden="true"><?php echo esc_html( $pad( $index + 1 ) ); ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $flags['progress'] ) : ?>
			<div class="apeiron-photo-album__progress" aria-hidden="true"><span class="apeiron-photo-album__progress-bar" data-apeiron-photo-album-progress style="width:<?php echo esc_attr( (string) round( 100 / $total, 4 ) ); ?>%"></span></div>
		<?php endif; ?>
	</div>
</div>
<?php
