<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_id_suffix = $element_id . '-' . wp_unique_id();
?>
<section class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
	data-apeiron-comment-dock-id="<?php echo esc_attr($element_id); ?>"
	data-attendance-display="<?php echo esc_attr($display_mode); ?>"
	data-attendance-map="<?php echo esc_attr(wp_json_encode($attendance_options)); ?>"
	data-use-pagination="<?php echo esc_attr($use_pagination ? 'yes' : 'no'); ?>"
	data-show-avatar="<?php echo esc_attr($show_avatar ? 'yes' : 'no'); ?>"
	data-show-date="<?php echo esc_attr($show_date ? 'yes' : 'no'); ?>"
	data-list-display-mode="<?php echo esc_attr($list_display_mode); ?>"
	data-comments-limit="<?php echo esc_attr($use_pagination ? 0 : max(1, (int) ($settings['comments_limit'] ?? 10))); ?>"
	data-date-format="<?php echo esc_attr($settings['date_format_type'] ?? 'relative'); ?>"
	data-comment-sticker-position="<?php echo esc_attr($sticker_comment_position); ?>"
	data-default-avatar="<?php echo esc_url($default_avatar_url); ?>"
	data-reply-enabled="<?php echo esc_attr($permissions['comment_reply'] ? 'yes' : 'no'); ?>"
	data-reply-depth-limit="<?php echo esc_attr($reply_depth_limit); ?>"
	data-reply-action-text="<?php echo esc_attr($reply_action_text); ?>"
	data-reply-name-placeholder="<?php echo esc_attr($reply_name_placeholder); ?>"
	data-reply-message-placeholder="<?php echo esc_attr($reply_message_placeholder); ?>"
	data-reply-submit-text="<?php echo esc_attr($reply_submit_text); ?>"
	data-reply-cancel-text="<?php echo esc_attr($reply_cancel_text); ?>"
	data-reply-popup-title="<?php echo esc_attr($reply_popup_title); ?>"
	data-reply-success-message="<?php echo esc_attr($reply_success_message); ?>"
	data-reply-error-message="<?php echo esc_attr($reply_error_message); ?>"
	data-comment-edit-mode="<?php echo esc_attr(in_array(($settings['comment_edit_mode'] ?? 'popup'), ['popup', 'inline'], true) ? ($settings['comment_edit_mode'] ?? 'popup') : 'popup'); ?>"
	data-comment-reply-mode="<?php echo esc_attr(in_array(($settings['comment_reply_mode'] ?? 'popup'), ['popup', 'inline'], true) ? ($settings['comment_reply_mode'] ?? 'popup') : 'popup'); ?>"
	data-modal-position="<?php echo esc_attr(($settings['modal_position'] ?? 'center') === 'bottom' ? 'bottom' : 'center'); ?>"
	data-modal-background-scroll="<?php echo esc_attr(($settings['modal_background_scroll'] ?? 'no') === 'yes' ? 'yes' : 'no'); ?>"
	data-modal-scroll-area="<?php echo esc_attr(($settings['modal_scroll_area'] ?? 'overlay') === 'dialog' ? 'dialog' : 'overlay'); ?>"
	data-guest-name-from-url="<?php echo esc_attr($guest_name_from_url_enabled ? 'yes' : 'no'); ?>"
	data-guest-required-message="<?php echo esc_attr($guest_required_message); ?>"
	data-invited-guest="<?php echo esc_attr($invited_guest_name); ?>"
	data-invited-guest-token="<?php echo esc_attr($invited_guest_token); ?>"
	data-reply-sticker-enabled="<?php echo esc_attr($enable_reply_sticker ? 'yes' : 'no'); ?>">
	<?php if ('yes' === ($settings['show_heading'] ?? 'yes') && !empty($settings['heading'])): ?>
		<h3 class="apeiron-kit-comment-heading"><?php echo esc_html($settings['heading']); ?></h3>
	<?php endif; ?>
	<?php if ('after_heading' === $attendance_summary_position && !empty($attendance_summary_html)): ?>
		<?php echo $attendance_summary_html; ?>
	<?php endif; ?>

	<form class="apeiron-kit-comment-form" data-apeiron-comment-form autocomplete="off"
		data-success-message="<?php echo esc_attr($settings['success_message'] ?? __('Terima kasih atas ucapannya!', 'apeiron-kit')); ?>"
		data-error-message="<?php echo esc_attr($settings['error_message'] ?? __('Gagal mengirim komentar.', 'apeiron-kit')); ?>"
		data-notice-position="<?php echo esc_attr($settings['notice_position'] ?? 'after_button'); ?>">
		<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>" />
		<input type="hidden" name="element_id" value="<?php echo esc_attr($element_id); ?>" />
		<input type="hidden" name="target_token" value="<?php echo esc_attr($target_token); ?>" />
		<input type="hidden" name="attendance_label" value="" />
		<input type="hidden" name="invited_guest_name" value="<?php echo esc_attr($invited_guest_name); ?>" />
		<input type="hidden" name="invited_guest_token" value="<?php echo esc_attr($invited_guest_token); ?>" />
		<?php if (!$is_guest_restricted_without_name): ?>
			<div class="apeiron-kit-form-field-wrap">
				<label class="apeiron-kit-form-label"
					for="apeiron-name-<?php echo esc_attr($field_id_suffix); ?>"><?php echo esc_html($settings['name_label'] ?? __('Nama Lengkap', 'apeiron-kit')); ?></label>
				<input type="text" id="apeiron-name-<?php echo esc_attr($field_id_suffix); ?>" name="name" autocomplete="off"
					placeholder="<?php echo esc_attr($settings['name_placeholder']); ?>"
					value="<?php echo esc_attr($invited_guest_name); ?>"
					<?php echo '' !== $invited_guest_name ? 'readonly aria-readonly="true"' : ''; ?>
					required />
			</div>
		<?php else: ?>
			<div class="apeiron-kit-invited-guest-note is-blocking" data-invited-guest-note>
				<p><?php echo esc_html($guest_required_message); ?></p>
			</div>
		<?php endif; ?>
		<div class="apeiron-kit-form-field-wrap">
			<label class="apeiron-kit-form-label"
				for="apeiron-message-<?php echo esc_attr($field_id_suffix); ?>"><?php echo esc_html($settings['message_label'] ?? __('Ucapan', 'apeiron-kit')); ?></label>
			<textarea id="apeiron-message-<?php echo esc_attr($field_id_suffix); ?>" name="message" rows="3" autocomplete="off"
				placeholder="<?php echo esc_attr($settings['message_placeholder']); ?>" required></textarea>
		</div>

		<?php if (!empty($attendance_options) && $show_attendence): ?>
			<label class="apeiron-kit-comment-select">
				<span class="apeiron-kit-form-label"><?php echo esc_html($settings['attendance_label']); ?></span>
				<select name="attendance" required>
					<option value=""><?php echo esc_html($attendance_placeholder); ?></option>
					<?php foreach ($attendance_options as $slug => $opt): ?>
						<option value="<?php echo esc_attr($slug); ?>">
							<?php echo esc_html($opt['label']); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php elseif (!empty($attendance_options) && !$show_attendence): ?>
			<input type="hidden" name="attendance" value="" />
		<?php endif; ?>

		<?php if ($show_stickers): ?>
			<?php
			$sticker_field_extra_class = '';
			$sticker_picker_mode       = $sticker_display_mode;
			require __DIR__ . '/sticker-picker.php';
			?>
		<?php endif; ?>

		<button type="submit" <?php echo $is_guest_restricted_without_name ? 'disabled aria-disabled="true"' : ''; ?>><?php echo esc_html($settings['button_text']); ?></button>
		<?php if ('after_submit' === $attendance_summary_position && !empty($attendance_summary_html)): ?>
			<?php echo $attendance_summary_html; ?>
		<?php endif; ?>
	</form>

	<?php if ($enable_reply_sticker): ?>
		<template data-reply-sticker-template="<?php echo esc_attr($sticker_display_mode); ?>">
			<?php
			$sticker_field_extra_class = ' apeiron-kit-reply-sticker-field';
			$sticker_picker_mode       = $sticker_display_mode;
			require __DIR__ . '/sticker-picker.php';
			?>
		</template>
		<?php if ('popup' === $sticker_display_mode): ?>
			<template data-reply-sticker-template="inline">
				<?php
				$sticker_field_extra_class = ' apeiron-kit-reply-sticker-field';
				$sticker_picker_mode       = 'inline';
				require __DIR__ . '/sticker-picker.php';
				?>
			</template>
		<?php endif; ?>
	<?php endif; ?>

	<ul class="<?php echo esc_attr(implode(' ', $list_classes)); ?>"<?php echo empty($comments) ? ' hidden' : ''; ?>>
		<?php foreach ($comments as $comment_index => $comment): ?>
			<?php echo $comment_renderer->render_item($comment, $comment_tree, 0, ($use_pagination && $comment_index >= $items_per_page)); ?>
		<?php endforeach; ?>
	</ul>

	<?php if ($has_more_top): ?>
		<div class="apeiron-kit-comment-load-more-wrap">
			<button type="button" class="apeiron-kit-comment-load-more" data-load-more
				data-post-id="<?php echo esc_attr($post_id); ?>"
				data-element-id="<?php echo esc_attr($element_id); ?>"
				data-next-page="2"
				data-per-page="<?php echo esc_attr($initial_batch); ?>"
				data-total="<?php echo esc_attr($total); ?>">
				<?php esc_html_e('Muat lebih banyak ucapan', 'apeiron-kit'); ?>
			</button>
		</div>
	<?php endif; ?>

	<?php if ($use_pagination): ?>
		<?php
		$pagination_hidden = (!$is_editor && $total <= $items_per_page) ? ' style="display:none"' : '';
		?>
		<div class="apeiron-kit-comment-pagination" data-total="<?php echo esc_attr($total); ?>"
			data-per-page="<?php echo esc_attr($items_per_page); ?>"
			data-post-id="<?php echo esc_attr($post_id); ?>"
			data-element-id="<?php echo esc_attr($element_id); ?>"
			data-current="1" <?php echo $pagination_hidden; ?>>
			<button type="button" class="apeiron-kit-comment-prev"
				aria-label="<?php esc_attr_e('Halaman komentar sebelumnya', 'apeiron-kit'); ?>" disabled>
				<?php echo esc_html($settings['prev_text'] ?? __('Sebelumnya', 'apeiron-kit')); ?>
			</button>
			<span class="apeiron-kit-comment-page-info">
				<span class="apeiron-kit-comment-page-current">1</span> / <span
					class="apeiron-kit-comment-page-total"><?php echo esc_html(max(1, ceil($total / $items_per_page))); ?></span>
			</span>
			<button type="button" class="apeiron-kit-comment-next"
				aria-label="<?php esc_attr_e('Halaman komentar berikutnya', 'apeiron-kit'); ?>" <?php echo $total <= $items_per_page ? 'disabled' : ''; ?>>
				<?php echo esc_html($settings['next_text'] ?? __('Berikutnya', 'apeiron-kit')); ?>
			</button>
		</div>
	<?php endif; ?>
</section>
