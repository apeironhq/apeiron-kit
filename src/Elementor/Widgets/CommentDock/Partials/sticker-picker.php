<?php

use ApeironKit\Elementor\Widgets\CommentDock\StickerLibrary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="apeiron-kit-sticker-field<?php echo esc_attr($sticker_field_extra_class); ?> is-<?php echo esc_attr($sticker_picker_mode); ?>">
	<span class="apeiron-kit-sticker-label apeiron-kit-form-label"><?php echo esc_html($sticker_label); ?></span>
	<input type="hidden" name="sticker_src" value="">
	<input type="hidden" name="sticker_type" value="">
	<?php if ('popup' === $sticker_picker_mode): ?>
		<div class="apeiron-kit-sticker-summary">
			<button type="button" class="apeiron-kit-sticker-trigger" data-sticker-open>
				<?php if (!empty($sticker_trigger_icon_text)): ?>
					<span class="apeiron-kit-sticker-trigger-icon" aria-hidden="true"><?php echo esc_html($sticker_trigger_icon_text); ?></span>
				<?php endif; ?>
				<span class="apeiron-kit-sticker-trigger-text"><?php echo esc_html($sticker_trigger_label); ?></span>
			</button>
			<span class="apeiron-kit-sticker-preview" data-sticker-preview hidden></span>
			<button type="button" class="apeiron-kit-sticker-clear" data-sticker-clear hidden aria-label="<?php esc_attr_e('Hapus sticker', 'apeiron-kit'); ?>">&times;</button>
		</div>
		<div class="apeiron-kit-sticker-popover is-anim-<?php echo esc_attr($sticker_popup_animation); ?> is-close-anim-<?php echo esc_attr($sticker_popup_close_animation); ?>" data-sticker-popover data-sticker-popup-position="<?php echo esc_attr($sticker_popup_position); ?>" data-background-scroll="<?php echo esc_attr(($settings['sticker_popup_background_scroll'] ?? 'no') === 'yes' ? 'yes' : 'no'); ?>" data-popup-scroll="<?php echo esc_attr(($settings['sticker_popup_scroll_area'] ?? 'picker') === 'panel' ? 'panel' : 'picker'); ?>" hidden>
			<div class="apeiron-kit-sticker-popover-backdrop" data-sticker-close></div>
			<div class="apeiron-kit-sticker-popover-panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($sticker_label); ?>">
				<div class="apeiron-kit-sticker-popover-header">
					<span class="apeiron-kit-sticker-popover-title"><?php echo esc_html($sticker_label); ?></span>
					<button type="button" class="apeiron-kit-sticker-popover-close" data-sticker-close aria-label="<?php esc_attr_e('Tutup pilihan sticker', 'apeiron-kit'); ?>">&times;</button>
				</div>
				<div class="apeiron-kit-sticker-tabs" role="tablist" aria-label="<?php esc_attr_e('Jenis sticker', 'apeiron-kit'); ?>">
					<?php if (!empty($image_stickers)): ?>
						<button type="button" class="apeiron-kit-sticker-tab is-active" role="tab" aria-selected="true" data-sticker-tab="image"><?php esc_html_e('Gambar', 'apeiron-kit'); ?></button>
					<?php endif; ?>
					<?php if (!empty($video_stickers)): ?>
						<button type="button" class="apeiron-kit-sticker-tab <?php echo empty($image_stickers) ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo empty($image_stickers) ? 'true' : 'false'; ?>" data-sticker-tab="video"><?php esc_html_e('Video', 'apeiron-kit'); ?></button>
					<?php endif; ?>
				</div>
				<?php if (!empty($image_stickers)): ?>
					<div class="apeiron-kit-sticker-picker is-popup-panel is-active" role="tabpanel" data-sticker-panel="image" data-sticker-picker="true">
						<div class="apeiron-kit-sticker-grid">
							<?php foreach ($image_stickers as $sticker): ?>
								<?php echo StickerLibrary::render_option($sticker); ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
				<?php if (!empty($video_stickers)): ?>
					<div class="apeiron-kit-sticker-picker is-popup-panel <?php echo empty($image_stickers) ? 'is-active' : ''; ?>" role="tabpanel" data-sticker-panel="video" data-sticker-picker="true">
						<div class="apeiron-kit-sticker-grid">
							<?php foreach ($video_stickers as $sticker): ?>
								<?php echo StickerLibrary::render_option($sticker); ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php else: ?>
		<div class="apeiron-kit-sticker-picker" data-sticker-picker="true">
			<div class="apeiron-kit-sticker-track">
				<?php foreach ($stickers as $sticker): ?>
					<?php echo StickerLibrary::render_option($sticker); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
