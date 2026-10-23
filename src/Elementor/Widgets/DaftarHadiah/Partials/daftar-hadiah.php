<?php

use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $gifts */
/** @var array $config */
/** @var string $dom_id */
$show = static function ( string $part ) use ( $settings ): bool {
	return 'yes' === ( $settings[ 'show_' . $part ] ?? 'yes' );
};
$text = static function ( string $key ) use ( $settings ): string {
	$value = is_scalar( $settings[ $key ] ?? null ) ? (string) $settings[ $key ] : '';
	return ! empty( $settings['__dynamic__'][ $key ] ) ? html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $value;
};
$mobile_swipe = 'grid' !== ( $settings['mobile_display'] ?? 'swipe' );
$navigation   = in_array( $settings['swipe_navigation'] ?? 'dots', [ 'dots', 'arrows', 'both', 'none' ], true ) ? ( $settings['swipe_navigation'] ?? 'dots' ) : 'dots';
$navigation_icon = static function ( string $direction ) use ( $settings ): void {
	$icon = is_array( $settings[ 'swipe_' . $direction . '_icon' ] ?? null ) ? $settings[ 'swipe_' . $direction . '_icon' ] : [];
	if ( ! empty( $icon['value'] ) ) {
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true', 'focusable' => 'false' ] );
		return;
	}
	$path = 'prev' === $direction ? 'm14 5-7 7 7 7' : 'm10 5 7 7-7 7';
	echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="' . $path . '" /></svg>';
};
$render_availability = static function ( array $gift = [], bool $popup = false ) use ( $show, $text ): void {
	if ( ! $show( 'status' ) && ! $show( 'quantity' ) ) {
		return;
	}
	$state = 'unavailable' === ( $gift['availability'] ?? 'available' ) ? 'unavailable' : 'available';
	?>
	<span class="apeiron-daftar-hadiah__status apeiron-daftar-hadiah__status--<?php echo esc_attr( $state ); ?>" title="<?php echo esc_attr__( 'Jumlah tersedia diatur manual, belum tersinkron dengan reservasi.', 'apeiron-kit' ); ?>"<?php if ( $popup ) : ?> data-gift-availability data-available-label="<?php echo esc_attr( $text( 'available_text' ) ); ?>" data-unavailable-label="<?php echo esc_attr( $text( 'unavailable_text' ) ); ?>"<?php endif; ?>>
		<svg class="apeiron-daftar-hadiah__availability-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 8h18v4H3zM5 12v9h14v-9M12 8v13M12 8H7.5A2.5 2.5 0 1 1 10 5.5L12 8Zm0 0h4.5A2.5 2.5 0 1 0 14 5.5L12 8Z" /></svg>
		<?php if ( $show( 'status' ) ) : ?><span<?php if ( $popup ) : ?> data-gift-status<?php endif; ?>><?php echo esc_html( $text( 'available' === $state ? 'available_text' : 'unavailable_text' ) ); ?></span><?php endif; ?>
		<?php if ( $show( 'quantity' ) ) : ?><span class="apeiron-daftar-hadiah__quantity"><span<?php if ( $popup ) : ?> data-gift-remaining<?php endif; ?>><?php echo esc_html( (string) ( $gift['available_quantity'] ?? '' ) ); ?></span> <?php echo esc_html__( 'dari', 'apeiron-kit' ); ?> <span title="<?php echo esc_attr( $text( 'quantity_text' ) ); ?>"<?php if ( $popup ) : ?> data-gift-quantity<?php endif; ?>><?php echo esc_html( (string) ( $gift['initial_quantity'] ?? '' ) ); ?></span></span><?php endif; ?>
	</span>
	<?php
};
?>
<section class="apeiron-daftar-hadiah<?php echo $mobile_swipe ? ' apeiron-daftar-hadiah--mobile-swipe' : ''; ?>" data-apeiron-daftar-hadiah="<?php echo esc_attr( (string) wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ); ?>" aria-label="<?php echo esc_attr__( 'Daftar Hadiah', 'apeiron-kit' ); ?>">
	<?php if ( ( $show( 'heading' ) && '' !== $text( 'heading' ) ) || ( $show( 'description' ) && '' !== $text( 'description' ) ) ) : ?>
		<header class="apeiron-daftar-hadiah__header">
			<?php if ( $show( 'heading' ) && '' !== $text( 'heading' ) ) : ?>
				<h2 class="apeiron-daftar-hadiah__heading"><?php echo esc_html( $text( 'heading' ) ); ?></h2>
			<?php endif; ?>
			<?php if ( $show( 'description' ) && '' !== $text( 'description' ) ) : ?>
				<p class="apeiron-daftar-hadiah__intro"><?php echo nl2br( esc_html( $text( 'description' ) ) ); ?></p>
			<?php endif; ?>
		</header>
	<?php endif; ?>
	<?php if ( ! $gifts ) : ?>
		<div class="apeiron-daftar-hadiah__empty" role="status"><?php echo esc_html( $text( 'empty_text' ) ); ?></div>
	<?php else : ?>
		<div id="<?php echo esc_attr( $dom_id . '-grid' ); ?>" class="apeiron-daftar-hadiah__grid"<?php if ( $mobile_swipe ) : ?> tabindex="0" role="group" aria-label="<?php echo esc_attr__( 'Daftar hadiah, geser ke samping untuk melihat hadiah lainnya', 'apeiron-kit' ); ?>"<?php endif; ?>>
			<?php foreach ( $gifts as $gift ) : ?>
				<?php $available = 'available' === $gift['availability'] && '' !== $gift['gift_id']; ?>
				<article class="apeiron-daftar-hadiah__card elementor-repeater-item-<?php echo esc_attr( $gift['row_id'] ); ?>" data-gift-id="<?php echo esc_attr( $gift['gift_id'] ); ?>">
					<?php if ( $show( 'image' ) ) : ?>
						<div class="apeiron-daftar-hadiah__photo">
							<?php if ( '' !== $gift['image_url'] ) : ?>
								<img class="apeiron-daftar-hadiah__image" src="<?php echo esc_url( $gift['image_url'] ); ?>" alt="<?php echo esc_attr( $gift['image_alt'] ); ?>" decoding="async" />
							<?php else : ?>
								<img class="apeiron-daftar-hadiah__image" src="<?php echo esc_url( $config['placeholder_image'] ); ?>" alt="<?php echo esc_attr__( 'Foto belum ditambahkan', 'apeiron-kit' ); ?>" decoding="async" />
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="apeiron-daftar-hadiah__body">
						<?php $render_availability( $gift ); ?>
						<?php if ( '' !== $gift['category'] ) : ?><p class="apeiron-daftar-hadiah__category"><?php echo esc_html( $gift['category'] ); ?></p><?php endif; ?>
						<?php if ( $show( 'name' ) ) : ?><h3 class="apeiron-daftar-hadiah__name"><?php echo esc_html( $gift['name'] ); ?></h3><?php endif; ?>
						<?php if ( $show( 'gift_description' ) && '' !== $gift['description'] ) : ?><p class="apeiron-daftar-hadiah__description"><?php echo esc_html( $gift['description'] ); ?></p><?php endif; ?>
						<div class="apeiron-daftar-hadiah__card-actions">
							<?php if ( $show( 'button' ) ) : ?>
								<button class="apeiron-daftar-hadiah__button apeiron-daftar-hadiah__button--primary" type="button" data-gift-select="<?php echo esc_attr( $gift['gift_id'] ); ?>" aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $dom_id . '-dialog' ); ?>" aria-label="<?php echo esc_attr( $text( 'button_text' ) . ': ' . $gift['name'] ); ?>" <?php disabled( ! $available ); ?>><?php echo esc_html( $text( 'button_text' ) ); ?></button>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $mobile_swipe && 'none' !== $navigation && count( $gifts ) > 1 ) : ?>
			<div class="apeiron-daftar-hadiah__swipe-navigation" data-gift-card-navigation role="group" aria-label="<?php echo esc_attr__( 'Navigasi daftar hadiah', 'apeiron-kit' ); ?>" hidden>
				<?php if ( in_array( $navigation, [ 'arrows', 'both' ], true ) ) : ?><button class="apeiron-daftar-hadiah__swipe-arrow" type="button" data-gift-card-prev aria-label="<?php echo esc_attr__( 'Hadiah sebelumnya', 'apeiron-kit' ); ?>" aria-controls="<?php echo esc_attr( $dom_id . '-grid' ); ?>" disabled><?php $navigation_icon( 'prev' ); ?></button><?php endif; ?>
				<?php if ( in_array( $navigation, [ 'dots', 'both' ], true ) ) : ?>
					<div class="apeiron-daftar-hadiah__swipe-dots">
						<?php foreach ( $gifts as $index => $gift ) : ?><button class="apeiron-daftar-hadiah__swipe-dot" type="button" data-gift-card-index="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Lihat hadiah %1$d: %2$s', 'apeiron-kit' ), $index + 1, $gift['name'] ) ); ?>" aria-controls="<?php echo esc_attr( $dom_id . '-grid' ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button><?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( in_array( $navigation, [ 'arrows', 'both' ], true ) ) : ?><button class="apeiron-daftar-hadiah__swipe-arrow" type="button" data-gift-card-next aria-label="<?php echo esc_attr__( 'Hadiah berikutnya', 'apeiron-kit' ); ?>" aria-controls="<?php echo esc_attr( $dom_id . '-grid' ); ?>"><?php $navigation_icon( 'next' ); ?></button><?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<noscript><p class="apeiron-daftar-hadiah__notice"><?php echo esc_html__( 'Aktifkan JavaScript untuk membuka pilihan hadiah. Pengiriman reservasi belum dikonfigurasi.', 'apeiron-kit' ); ?></p></noscript>
	<dialog id="<?php echo esc_attr( $dom_id . '-dialog' ); ?>" class="apeiron-daftar-hadiah__dialog" aria-labelledby="<?php echo esc_attr( $dom_id . '-title' ); ?>" aria-describedby="<?php echo esc_attr( $dom_id . '-help' ); ?>">
		<button class="apeiron-daftar-hadiah__close" type="button" data-gift-close aria-label="<?php echo esc_attr( $text( 'cancel_text' ) ?: __( 'Tutup popup', 'apeiron-kit' ) ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18" /></svg></button>
		<div class="apeiron-daftar-hadiah__success" data-gift-success role="status" aria-live="polite" aria-atomic="true" hidden>
			<span class="apeiron-daftar-hadiah__success-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m5 12 4 4L19 6" /></svg></span>
			<h3 id="<?php echo esc_attr( $dom_id . '-success-title' ); ?>" class="apeiron-daftar-hadiah__success-title"><?php esc_html_e( 'Berhasil dikirim', 'apeiron-kit' ); ?></h3>
			<p id="<?php echo esc_attr( $dom_id . '-success-message' ); ?>" class="apeiron-daftar-hadiah__success-message" data-gift-success-message></p>
		</div>
		<div class="apeiron-daftar-hadiah__dialog-scroll" data-gift-scroll><div class="apeiron-daftar-hadiah__dialog-content">
			<?php if ( $show( 'image' ) ) : ?>
				<div class="apeiron-daftar-hadiah__gallery" data-gift-gallery>
					<div class="apeiron-daftar-hadiah__photo" data-gift-photo tabindex="0" role="group" aria-label="<?php echo esc_attr__( 'Foto hadiah', 'apeiron-kit' ); ?>">
						<img class="apeiron-daftar-hadiah__image" data-gift-image alt="" draggable="false" />
						<button class="apeiron-daftar-hadiah__gallery-arrow apeiron-daftar-hadiah__gallery-arrow--prev" type="button" data-gift-gallery-prev aria-label="<?php echo esc_attr__( 'Foto sebelumnya', 'apeiron-kit' ); ?>" hidden><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m14 5-7 7 7 7" /></svg></button>
						<button class="apeiron-daftar-hadiah__gallery-arrow apeiron-daftar-hadiah__gallery-arrow--next" type="button" data-gift-gallery-next aria-label="<?php echo esc_attr__( 'Foto berikutnya', 'apeiron-kit' ); ?>" hidden><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m10 5 7 7-7 7" /></svg></button>
						<span class="apeiron-daftar-hadiah__photo-count" data-gift-photo-count role="status" aria-live="polite" hidden></span>
					</div>
					<div class="apeiron-daftar-hadiah__thumbnails" data-gift-thumbnails role="group" aria-label="<?php echo esc_attr__( 'Pilih foto hadiah', 'apeiron-kit' ); ?>" hidden></div>
					<?php $render_availability( [], true ); ?>
				</div>
			<?php endif; ?>
			<div class="apeiron-daftar-hadiah__summary">
				<div class="apeiron-daftar-hadiah__summary-text">
					<div class="apeiron-daftar-hadiah__popup-header"><h2 id="<?php echo esc_attr( $dom_id . '-title' ); ?>" class="apeiron-daftar-hadiah__popup-title"><?php echo esc_html( $text( 'popup_title' ) ); ?></h2></div>
					<p class="apeiron-daftar-hadiah__category" data-gift-category hidden></p>
					<h3 class="apeiron-daftar-hadiah__name" data-gift-name></h3>
					<?php if ( $show( 'gift_description' ) ) : ?><p class="apeiron-daftar-hadiah__description" data-gift-description></p><?php endif; ?>
					<?php if ( ! $show( 'image' ) ) : ?><?php $render_availability( [], true ); ?><?php endif; ?>
				</div>
			</div>
			<form id="<?php echo esc_attr( $dom_id . '-form' ); ?>" class="apeiron-daftar-hadiah__form" data-gift-form method="dialog" novalidate>
				<div class="apeiron-daftar-hadiah__field">
					<label class="apeiron-daftar-hadiah__label" for="<?php echo esc_attr( $dom_id . '-name' ); ?>"><?php echo esc_html( $text( 'name_label' ) ); ?> <span aria-hidden="true">*</span></label>
					<input id="<?php echo esc_attr( $dom_id . '-name' ); ?>" class="apeiron-daftar-hadiah__input" name="guest_name" type="text" autocomplete="name" maxlength="100" placeholder="<?php echo esc_attr__( 'Contoh: Budi Santoso', 'apeiron-kit' ); ?>" required aria-describedby="<?php echo esc_attr( $dom_id . '-name-error' ); ?>" />
					<span id="<?php echo esc_attr( $dom_id . '-name-error' ); ?>" class="apeiron-daftar-hadiah__field-error" data-gift-error="guest_name" hidden></span>
				</div>
				<div class="apeiron-daftar-hadiah__field">
					<label class="apeiron-daftar-hadiah__label" for="<?php echo esc_attr( $dom_id . '-whatsapp' ); ?>"><?php echo esc_html( $text( 'whatsapp_label' ) ); ?> <span aria-hidden="true">*</span></label>
					<input id="<?php echo esc_attr( $dom_id . '-whatsapp' ); ?>" class="apeiron-daftar-hadiah__input" name="whatsapp" type="tel" inputmode="tel" autocomplete="tel" maxlength="24" placeholder="081234567890" required aria-describedby="<?php echo esc_attr( $dom_id . '-whatsapp-error' ); ?>" />
					<span id="<?php echo esc_attr( $dom_id . '-whatsapp-error' ); ?>" class="apeiron-daftar-hadiah__field-error" data-gift-error="whatsapp" hidden></span>
				</div>
				<div class="apeiron-daftar-hadiah__field">
					<label class="apeiron-daftar-hadiah__label" for="<?php echo esc_attr( $dom_id . '-message' ); ?>"><?php echo esc_html( $text( 'message_label' ) ); ?></label>
					<textarea id="<?php echo esc_attr( $dom_id . '-message' ); ?>" class="apeiron-daftar-hadiah__input" name="message" rows="3" maxlength="1000" placeholder="<?php echo esc_attr__( 'Tulis pesan atau doa untuk kami…', 'apeiron-kit' ); ?>" aria-describedby="<?php echo esc_attr( $dom_id . '-message-error' ); ?>"></textarea>
					<span id="<?php echo esc_attr( $dom_id . '-message-error' ); ?>" class="apeiron-daftar-hadiah__field-error" data-gift-error="message" hidden></span>
				</div>
			</form>
		</div></div>
		<div class="apeiron-daftar-hadiah__popup-footer">
			<p id="<?php echo esc_attr( $dom_id . '-help' ); ?>" class="apeiron-daftar-hadiah__notice" data-gift-notice role="status" aria-live="polite" aria-atomic="true" hidden></p>
			<div class="apeiron-daftar-hadiah__form-actions">
				<?php if ( $show( 'purchase_link' ) ) : ?><a class="apeiron-daftar-hadiah__button apeiron-daftar-hadiah__button--secondary" data-gift-purchase data-default-label="<?php echo esc_attr( $text( 'purchase_text' ) ); ?>" hidden><?php echo esc_html( $text( 'purchase_text' ) ); ?></a><?php endif; ?>
				<button class="apeiron-daftar-hadiah__button apeiron-daftar-hadiah__button--primary" type="submit" form="<?php echo esc_attr( $dom_id . '-form' ); ?>"><?php echo esc_html( $text( 'confirm_text' ) ); ?></button>
			</div>
		</div>
	</dialog>
</section>
