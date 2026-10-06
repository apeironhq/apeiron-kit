<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$title_id = 'apeiron-form-order-title-' . sanitize_key( (string) $element_id );
$draft_ui = ! empty( $config['draft']['enabled'] ) || 'yes' === ( $settings['draft_auto_save'] ?? '' );
$draft_preview = $draft_ui && empty( $config['draft']['enabled'] );
?>
<div class="apeiron-form-order" data-apeiron-form-order="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
	<?php if ( $draft_ui ) : ?><div class="apeiron-form-order__header<?php echo empty( $settings['form_title'] ) ? ' is-untitled' : ''; ?>"><?php endif; ?>
	<?php if ( ! empty( $settings['form_title'] ) ) : ?>
		<h2 id="<?php echo esc_attr( $title_id ); ?>" class="apeiron-form-order__title"><?php echo esc_html( $settings['form_title'] ); ?></h2>
	<?php endif; ?>
	<?php if ( $draft_ui ) : ?>
		<button type="button" class="apeiron-form-order__draft-clear" data-order-draft-clear aria-label="<?php esc_attr_e( 'Mulai ulang dan hapus draft', 'apeiron-kit' ); ?>" <?php echo $draft_preview ? 'disabled' : ''; ?>><?php esc_html_e( 'Mulai ulang', 'apeiron-kit' ); ?></button>
	</div><?php endif; ?>
	<form class="apeiron-form-order__form" aria-label="<?php esc_attr_e( 'Form Order Undangan', 'apeiron-kit' ); ?>" novalidate>
		<?php if ( $draft_ui ) : ?>
			<div class="apeiron-form-order__draft<?php echo $draft_preview ? ' has-message' : ''; ?>">
				<div class="apeiron-form-order__draft-feedback">
					<span data-order-draft-message role="status" aria-live="polite"><?php if ( $draft_preview ) { esc_html_e( 'Notifikasi pemulihan draft', 'apeiron-kit' ); } ?></span>
				</div>
			</div>
		<?php endif; ?>
		<div class="apeiron-form-order__progress" role="progressbar" aria-valuemin="1" aria-valuemax="<?php echo esc_attr( (string) count( $steps ) ); ?>" aria-valuenow="1" aria-label="<?php esc_attr_e( 'Tahap pengisian', 'apeiron-kit' ); ?>"><span class="apeiron-form-order__progress-fill"></span></div>
		<p class="apeiron-form-order__counter" aria-live="polite"></p>
		<?php foreach ( $steps as $index => $step ) : ?>
			<section class="apeiron-form-order__step" data-order-step="<?php echo esc_attr( (string) $index ); ?>" data-order-step-key="<?php echo esc_attr( $step['key'] ); ?>" <?php echo ! empty( $step['is_story'] ) ? 'data-order-story' : ''; ?> <?php echo $index ? 'hidden' : ''; ?>>
				<h3 class="apeiron-form-order__step-title" tabindex="-1"><?php echo esc_html( $step['title'] ); ?></h3>
				<?php if ( ! empty( $step['is_story'] ) ) : ?><p class="apeiron-form-order__story-note"><?php esc_html_e( 'Isi cerita sesuai perjalanan Anda, atau lewati jika tidak diperlukan.', 'apeiron-kit' ); ?></p><?php endif; ?>
				<?php if ( 'confirmation' === $step['kind'] ) : ?>
					<p><?php echo esc_html( $settings['confirmation_text'] ?? __( 'Periksa kembali seluruh data sebelum mengirim.', 'apeiron-kit' ) ); ?></p>
					<div class="apeiron-form-order__summary"></div>
				<?php else : ?>
				<?php foreach ( \ApeironKit\Elementor\Widgets\FormOrder\FieldSchema::layout_groups( $step['fields'] ) as $group_index => $group ) : ?>
				<?php $group_id = 'order-group-' . sanitize_key( (string) $element_id ) . '-' . $index . '-' . $group_index; ?>
				<?php if ( 'ngunduh' === $group['key'] ) : ?>
					<button type="button" class="apeiron-form-order__button apeiron-form-order__button--extra" data-order-toggle-group="<?php echo esc_attr( $group_id ); ?>" aria-controls="<?php echo esc_attr( $group_id ); ?>" aria-expanded="false"><?php esc_html_e( 'Tambahkan Ngunduh Mantu', 'apeiron-kit' ); ?></button>
				<?php endif; ?>
				<div id="<?php echo esc_attr( $group_id ); ?>" class="apeiron-form-order__group <?php echo strpos( $group['key'], 'love_' ) === 0 ? 'apeiron-form-order__story-group' : ''; ?>" <?php echo 'ngunduh' === $group['key'] ? 'data-order-optional hidden' : ''; ?> <?php echo 'bank_2' === $group['key'] ? 'data-order-bank-group' : ''; ?> <?php echo strpos( $group['key'], 'love_' ) === 0 ? 'data-order-story-group data-order-default-title="' . esc_attr( $group['title'] ) . '"' : ''; ?>>
				<?php if ( 'ngunduh' === $group['key'] ) : ?><button type="button" class="apeiron-form-order__close-group" data-order-close-group="<?php echo esc_attr( $group_id ); ?>" aria-label="<?php esc_attr_e( 'Tutup Ngunduh Mantu', 'apeiron-kit' ); ?>">&times;</button><?php endif; ?>
				<?php if ( '' !== $group['title'] ) : ?><h4 class="apeiron-form-order__heading"><?php echo esc_html( $group['title'] ); ?></h4><?php endif; ?>
				<div class="apeiron-form-order__grid">
					<?php foreach ( $group['fields'] as $name => $field ) : ?>
						<?php $field_id = 'apeiron-order-' . sanitize_key( (string) $element_id ) . '-' . $name; ?>
						<div class="apeiron-form-order__field" data-order-label="<?php echo esc_attr( $field['label'] ); ?>" style="--order-field-width:<?php echo esc_attr( $field['width'] ); ?>%;--order-field-width-tablet:<?php echo esc_attr( $field['width_tablet'] ); ?>%;--order-field-width-mobile:<?php echo esc_attr( $field['width_mobile'] ); ?>%">
							<?php if ( 'heading' === $field['type'] ) : ?>
								<h4 class="apeiron-form-order__heading"><?php echo esc_html( $field['label'] ); ?></h4>
								<?php if ( '' !== $field['help'] ) : ?><p class="apeiron-form-order__help"><?php echo esc_html( $field['help'] ); ?></p><?php endif; ?>
							<?php else : ?>
							<label class="apeiron-form-order__field-label" <?php echo in_array( $field['type'], [ 'radio', 'checkbox' ], true ) ? '' : 'for="' . esc_attr( $field_id ) . '"'; ?>><?php echo esc_html( $field['label'] ); ?><?php echo $field['required'] ? ' *' : ''; ?></label>
							<?php if ( 'textarea' === $field['type'] ) : ?>
								<textarea class="apeiron-form-order__input" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" rows="3" maxlength="1000" <?php echo $field['required'] ? 'required' : ''; ?>><?php echo esc_textarea( $field['default'] ); ?></textarea>
							<?php elseif ( 'select' === $field['type'] ) : ?>
								<select class="apeiron-form-order__input" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php echo $field['required'] ? 'required' : ''; ?>><option value=""><?php echo esc_html( $field['placeholder'] ?: __( 'Pilih salah satu', 'apeiron-kit' ) ); ?></option><?php foreach ( $field['options'] as $option_value => $option_label ) : ?><option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $field['default'], $option_value ); ?>><?php echo esc_html( $option_label ); ?></option><?php endforeach; ?></select>
							<?php elseif ( in_array( $field['type'], [ 'radio', 'checkbox' ], true ) ) : ?>
								<div class="apeiron-form-order__choices" role="group" aria-label="<?php echo esc_attr( $field['label'] ); ?>" data-order-required="<?php echo $field['required'] ? 'yes' : 'no'; ?>">
									<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
										<label class="apeiron-form-order__choice"><input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $name . ( 'checkbox' === $field['type'] ? '[]' : '' ) ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( in_array( (string) $option_value, array_map( 'trim', explode( ',', $field['default'] ) ), true ) ); ?>><span><?php echo esc_html( $option_label ); ?></span></label>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<input class="apeiron-form-order__input" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" value="<?php echo esc_attr( $field['default'] ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" <?php echo $field['required'] ? 'required' : ''; ?> <?php echo 'number' === $field['type'] ? 'min="1" step="1"' : ''; ?> <?php echo preg_match( '/^love_(meeting|engagement|journey)_title$/D', $name ) ? 'data-order-story-title' : ''; ?> maxlength="255">
							<?php endif; ?>
							<?php if ( '' !== $field['help'] ) : ?><p class="apeiron-form-order__help"><?php echo esc_html( $field['help'] ); ?></p><?php endif; ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php if ( 'bank_2' === $group['key'] && isset( $group['fields']['bank_account_2'] ) && 'text' === $group['fields']['bank_account_2']['type'] ) : ?>
					<button type="button" class="apeiron-form-order__button apeiron-form-order__button--extra" data-order-add-account><?php esc_html_e( 'Tambah Rekening', 'apeiron-kit' ); ?></button>
				<?php endif; ?>
				</div>
				<?php endforeach; ?>
				<?php if ( ! empty( $step['is_story'] ) && isset( $step['fields']['love_meeting_date'], $step['fields']['love_meeting_story'] ) ) : ?>
					<button type="button" class="apeiron-form-order__button apeiron-form-order__button--extra" data-order-add-journey><?php esc_html_e( 'Tambah Perjalanan', 'apeiron-kit' ); ?></button>
				<?php endif; ?>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
		<div class="apeiron-form-order__honeypot" aria-hidden="true"><label for="apeiron-order-site-<?php echo esc_attr( $element_id ); ?>">Website</label><input id="apeiron-order-site-<?php echo esc_attr( $element_id ); ?>" type="text" name="website" tabindex="-1" autocomplete="off"></div>
		<div class="apeiron-form-order__actions">
			<button class="apeiron-form-order__button apeiron-form-order__button--back" type="button" hidden><?php echo esc_html( $settings['back_text'] ?? __( 'Kembali', 'apeiron-kit' ) ); ?></button>
			<button class="apeiron-form-order__button apeiron-form-order__button--next" type="button"><?php echo esc_html( $settings['next_text'] ?? __( 'Lanjut', 'apeiron-kit' ) ); ?></button>
			<button class="apeiron-form-order__button apeiron-form-order__button--submit" type="submit" hidden><?php echo esc_html( $settings['submit_text'] ?? __( 'Kirim Pesanan', 'apeiron-kit' ) ); ?></button>
		</div>
		<div class="apeiron-form-order__notice" role="status" aria-live="polite" hidden></div>
	</form>
</div>
