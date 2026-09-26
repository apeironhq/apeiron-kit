<?php
/**
 * @var array<string,mixed> $settings
 * @var string              $widget_id
 * @var string              $unique_id
 * @var string              $excel_import_mode
 * @var string              $skip_excel_header
 * @var string              $show_guest_list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="apeiron-invitation-container" id="<?php echo esc_attr( $unique_id ); ?>" data-import-mode="<?php echo esc_attr( $excel_import_mode ); ?>" data-skip-header="<?php echo esc_attr( $skip_excel_header ); ?>" data-show-results="<?php echo esc_attr( $show_guest_list ); ?>">
			<?php if ( 'yes' === $settings['show_header'] ) : ?>
			<div class="apeiron-header-section">
				<?php if ( ! empty( $settings['header_icon']['value'] ) ) : ?>
				<div class="apeiron-header-icon">
					<?php if ( 'fas fa-user-plus' === $settings['header_icon']['value'] ) : ?>
					<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
					<?php else : ?>
					<?php \Elementor\Icons_Manager::render_icon( $settings['header_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				<div class="apeiron-header-text">
					<?php if ( ! empty( $settings['header_title'] ) ) : ?>
					<h2 class="apeiron-header-title"><?php echo esc_html( $settings['header_title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $settings['header_description'] ) ) : ?>
					<p class="apeiron-header-description"><?php echo esc_html( $settings['header_description'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="apeiron-invitation-wrapper">
				
				<div class="apeiron-invitation-form">
					<div class="apeiron-form-group">
						<label class="apeiron-form-label" for="invitation_link_input_<?php echo esc_attr( $widget_id ); ?>">
							<?php echo esc_html( ! empty( $settings['invitation_link_label'] ) ? $settings['invitation_link_label'] : __( 'Link Undangan', 'apeiron-kit' ) ); ?>
						</label>
						<p class="apeiron-form-hint">
							<?php echo esc_html( ! empty( $settings['invitation_link_hint'] ) ? $settings['invitation_link_hint'] : __( '* Masukkan link undangan Anda. Contoh: apeiron.id/ika-budi atau https://apeiron.id/ika-budi', 'apeiron-kit' ) ); ?>
						</p>
						<input 
							type="text" 
							id="invitation_link_input_<?php echo esc_attr( $widget_id ); ?>"
							class="apeiron-form-input apeiron-invitation-link-input" 
							placeholder="<?php echo esc_attr( ! empty( $settings['invitation_link_placeholder'] ) ? $settings['invitation_link_placeholder'] : __( 'Contoh: apeiron.id/ika-budi', 'apeiron-kit' ) ); ?>"
							required
						>
					</div>

					<div class="apeiron-form-group">
						<label class="apeiron-form-label" for="guest_names_<?php echo esc_attr( $widget_id ); ?>">
							<?php echo esc_html( ! empty( $settings['guest_names_label'] ) ? $settings['guest_names_label'] : __( 'Nama Tamu', 'apeiron-kit' ) ); ?>
						</label>
						<p class="apeiron-form-hint">
							<?php echo esc_html( ! empty( $settings['guest_names_hint'] ) ? $settings['guest_names_hint'] : __( '* Satu tamu per baris. Opsional: Nama | Nomor WhatsApp.', 'apeiron-kit' ) ); ?>
						</p>
						<textarea 
							id="guest_names_<?php echo esc_attr( $widget_id ); ?>"
							class="apeiron-form-input apeiron-guest-names" 
							rows="5" 
							placeholder="<?php echo esc_attr( ! empty( $settings['guest_names_placeholder'] ) ? $settings['guest_names_placeholder'] : __( 'Nama Tamu | 628123456789', 'apeiron-kit' ) ); ?>"
						></textarea>
					</div>

					<?php if ( 'yes' === $settings['enable_excel_import'] ) : ?>
					<div class="apeiron-import-section">
						<label class="apeiron-form-label" for="import_excel_<?php echo esc_attr( $widget_id ); ?>">
							<?php echo esc_html( ! empty( $settings['import_section_label'] ) ? $settings['import_section_label'] : __( 'Impor dari Excel', 'apeiron-kit' ) ); ?>
						</label>
						<div class="apeiron-dropzone" id="dropzone_<?php echo esc_attr( $widget_id ); ?>">
							<input 
								type="file" 
								id="import_excel_<?php echo esc_attr( $widget_id ); ?>" 
								class="apeiron-file-input apeiron-dropzone-input" 
								accept=".xlsx,.xls"
							>
							<div class="apeiron-dropzone-content">
								<div class="apeiron-dropzone-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0d9e6d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
								</div>
								<p class="apeiron-dropzone-text"><?php echo esc_html__( 'Klik atau drop file Excel di sini', 'apeiron-kit' ); ?></p>
								<p class="apeiron-dropzone-hint"><?php echo esc_html__( 'Format: Kolom 1 Nama, Kolom 2 Nomor WhatsApp (opsional)', 'apeiron-kit' ); ?></p>
							</div>
						</div>
						<div class="apeiron-import-actions">
							<?php
							$download_link = $settings['download_template_link'];
							if ( ! empty( $download_link['url'] ) ) :
								$download_label = ! empty( $settings['download_template_label'] ) ? esc_html( $settings['download_template_label'] ) : esc_html__( 'Unduh Template', 'apeiron-kit' );
								$download_target = ! empty( $download_link['is_external'] ) ? ' target="_blank"' : '';
								$download_rel = [];
								if ( ! empty( $download_link['is_external'] ) ) {
									$download_rel[] = 'noopener';
									$download_rel[] = 'noreferrer';
								}
								if ( ! empty( $download_link['nofollow'] ) ) {
									$download_rel[] = 'nofollow';
								}
								$download_rel_attr = ! empty( $download_rel ) ? ' rel="' . esc_attr( implode( ' ', array_unique( $download_rel ) ) ) . '"' : '';
							?>
							<a 
								href="<?php echo esc_url( $download_link['url'] ); ?>"
								class="apeiron-btn apeiron-guest-btn btn-download apeiron-btn-download"
								<?php echo wp_kses_post( $download_target . $download_rel_attr ); ?>
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
								<?php echo $download_label; ?>
							</a>
							<?php endif; ?>
							<button 
								type="button" 
								class="apeiron-btn apeiron-guest-btn btn-import apeiron-btn-excel"
								data-apeiron-action="import"
								data-widget-id="<?php echo esc_attr( $widget_id ); ?>"
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
								<?php echo esc_html( ! empty( $settings['process_import_label'] ) ? $settings['process_import_label'] : __( 'Impor Excel', 'apeiron-kit' ) ); ?>
							</button>
						</div>
					</div>
					<?php endif; ?>

					<div class="apeiron-template-section">
						<label class="apeiron-form-label" for="message_template_<?php echo esc_attr( $widget_id ); ?>">
							<?php echo esc_html( ! empty( $settings['template_label'] ) ? $settings['template_label'] : __( 'Pilih Template Ucapan', 'apeiron-kit' ) ); ?>
						</label>
						
						<div class="apeiron-template-buttons">
							<?php 
							if ( ! empty( $settings['templates'] ) ) : 
								foreach ( $settings['templates'] as $index => $item ) :
									$item_id = 'template_' . $index;
									$item_message = isset( $item['item_message'] ) ? $item['item_message'] : '';
									$item_label = ! empty( $item['item_label'] ) ? esc_html( $item['item_label'] ) : sprintf( __( 'Ucapan %d', 'apeiron-kit' ), $index + 1 );
							?>
								<button 
									type="button" 
									class="apeiron-btn apeiron-guest-btn btn-template apeiron-btn-template"
									data-template-index="<?php echo esc_attr( $index ); ?>"
									data-widget-id="<?php echo esc_attr( $widget_id ); ?>"
									data-template-id="<?php echo esc_attr( $item_id ); ?>"
									data-message="<?php echo esc_attr( str_replace( [ "\n", "\r" ], [ '\\n', '' ], $item_message ) ); ?>"
								>
									<?php echo $item_label; ?>
								</button>
							<?php 
								endforeach;
							endif;
							?>
						</div>

						<textarea 
							id="message_template_<?php echo esc_attr( $widget_id ); ?>"
							class="apeiron-form-input apeiron-message-template" 
							rows="12" 
							placeholder="<?php echo esc_attr( ! empty( $settings['message_template_placeholder'] ) ? $settings['message_template_placeholder'] : __( 'Template ucapan akan muncul di sini...', 'apeiron-kit' ) ); ?>"
						></textarea>
					</div>

					<?php if ( 'yes' === $show_guest_list ) : ?>
					<div class="apeiron-form-actions">
						<button 
							type="button" 
								class="apeiron-btn apeiron-guest-btn btn-register apeiron-btn-create"
								data-apeiron-action="create"
								data-widget-id="<?php echo esc_attr( $widget_id ); ?>"
						>
							<?php echo esc_html( ! empty( $settings['create_button_label'] ) ? $settings['create_button_label'] : __( 'Buat Daftar', 'apeiron-kit' ) ); ?>
						</button>
						<p class="apeiron-create-status" id="create_status_<?php echo esc_attr( $widget_id ); ?>" role="status" aria-live="polite" hidden></p>
					</div>
					<?php endif; ?>
				</div>

			<?php if ( 'yes' === $show_guest_list ) : ?>
				<div class="apeiron-guest-list-section">
					<h3 class="apeiron-section-title">
						<?php echo esc_html( ! empty( $settings['guest_list_title'] ) ? $settings['guest_list_title'] : __( 'Daftar Tamu', 'apeiron-kit' ) ); ?>
					</h3>
					<div class="apeiron-table-wrapper">
						<table class="apeiron-guest-table">
							<thead>
								<tr>
									<th scope="col"><?php echo esc_html( ! empty( $settings['table_label_no'] ) ? $settings['table_label_no'] : __( 'No', 'apeiron-kit' ) ); ?></th>
									<th scope="col"><?php echo esc_html( ! empty( $settings['table_label_guest'] ) ? $settings['table_label_guest'] : __( 'Nama Tamu', 'apeiron-kit' ) ); ?></th>
									<th scope="col"><?php echo esc_html( ! empty( $settings['table_label_option'] ) ? $settings['table_label_option'] : __( 'Aksi', 'apeiron-kit' ) ); ?></th>
								</tr>
							</thead>
							<tbody id="guest_list_<?php echo esc_attr( $widget_id ); ?>">
								<tr class="apeiron-empty-state">
									<td colspan="3">
										<?php echo esc_html( ! empty( $settings['guest_list_empty_text'] ) ? $settings['guest_list_empty_text'] : __( 'Belum ada tamu. Buat daftar terlebih dahulu.', 'apeiron-kit' ) ); ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			<?php endif; ?>

			</div>
		</div>
