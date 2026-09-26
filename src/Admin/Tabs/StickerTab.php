<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Elementor\Widgets\CommentDock\StickerLibrary;

/**
 * Sticker management tab.
 */
class StickerTab extends AbstractTab {

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'stickers';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title(): string {
		return __( 'Stiker', 'apeiron-kit' );
	}

	/**
	 * @inheritDoc
	 */
	public function render(): void {
		$default_folder = StickerLibrary::DEFAULT_FOLDER;
		$folder = isset( $_GET['folder'] ) ? sanitize_text_field( wp_unslash( $_GET['folder'] ) ) : $default_folder;
		$folder = $default_folder === $folder ? $folder : $default_folder;
		$stickers = StickerLibrary::load( $folder, true, true );
		$total_stickers = count( $stickers );
		$image_count = count( array_filter( $stickers, fn( $s ) => $s['type'] === 'image' ) );
		$video_count = $total_stickers - $image_count;
		?>
		<div class="apeiron-sticker-wrap">
			<?php $this->render_sticker_section( $stickers, $total_stickers, $image_count, $video_count ); ?>
		</div>

		<?php $this->render_runtime_config( $folder ); ?>
		<?php
	}

	/**
	 * Render sticker management using the same section system as the widget tab.
	 */
	private function render_sticker_section( array $stickers, int $total, int $images, int $videos ): void {
		?>
		<section class="apeiron-elements-section apeiron-sticker-section">
			<div class="apeiron-elements-toolbar apeiron-sticker-toolbar">
				<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Stiker', 'apeiron-kit' ); ?></h2>

				<div class="apeiron-sticker-toolbar__stats" aria-label="<?php esc_attr_e( 'Statistik stiker', 'apeiron-kit' ); ?>">
					<span class="apeiron-sticker-toolbar__stat">
						<strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
						<?php esc_html_e( 'Total', 'apeiron-kit' ); ?>
					</span>
					<span class="apeiron-sticker-toolbar__stat">
						<strong><?php echo esc_html( number_format_i18n( $images ) ); ?></strong>
						<?php esc_html_e( 'Gambar', 'apeiron-kit' ); ?>
					</span>
					<span class="apeiron-sticker-toolbar__stat">
						<strong><?php echo esc_html( number_format_i18n( $videos ) ); ?></strong>
						<?php esc_html_e( 'Video', 'apeiron-kit' ); ?>
					</span>
				</div>

				<label class="screen-reader-text" for="apeiron-sticker-filter"><?php esc_html_e( 'Filter tipe stiker', 'apeiron-kit' ); ?></label>
				<select class="apeiron-elements-toolbar__filter" id="apeiron-sticker-filter">
					<option value=""><?php esc_html_e( 'Semua Tipe', 'apeiron-kit' ); ?></option>
					<option value="image"><?php esc_html_e( 'Gambar', 'apeiron-kit' ); ?></option>
					<option value="video"><?php esc_html_e( 'Video', 'apeiron-kit' ); ?></option>
				</select>

				<span class="apeiron-elements-toolbar__spacer"></span>

				<div class="apeiron-sticker-toolbar__upload">
					<span class="apeiron-sticker-toolbar__upload-hint"><?php esc_html_e( 'PNG, JPG, GIF, WebP, WebM, MP4', 'apeiron-kit' ); ?></span>
					<input type="file" id="sticker-upload-input" accept=".png,.jpg,.jpeg,.gif,.webp,.webm,.mp4" multiple>
					<button type="button" class="apeiron-btn apeiron-btn--primary apeiron-sticker-upload-trigger" id="sticker-upload-trigger">
						<span class="dashicons dashicons-cloud-upload"></span>
						<?php esc_html_e( 'Upload Stiker', 'apeiron-kit' ); ?>
					</button>
				</div>
			</div>

			<div class="apeiron-elements-content apeiron-sticker-content">
				<div class="apeiron-progress" id="upload-progress">
					<div class="apeiron-progress__bar">
						<div class="apeiron-progress__fill" id="progress-fill"></div>
					</div>
					<div class="apeiron-progress__text" id="progress-text">0%</div>
				</div>

				<div id="upload-message" class="apeiron-toast"></div>

				<?php if ( empty( $stickers ) ) : ?>
					<div class="apeiron-empty">
						<div class="apeiron-empty__icon">
							<span class="dashicons dashicons-images-alt"></span>
						</div>
						<div class="apeiron-empty__title"><?php esc_html_e( 'Belum ada stiker', 'apeiron-kit' ); ?></div>
						<div class="apeiron-empty__text"><?php esc_html_e( 'Upload stiker pertama Anda menggunakan form di atas', 'apeiron-kit' ); ?></div>
					</div>
				<?php else : ?>
					<div class="apeiron-sticker-grid">
						<?php foreach ( $stickers as $sticker ) : ?>
							<div class="apeiron-sticker-item" data-path="<?php echo esc_attr( $sticker['relative'] ); ?>" data-type="<?php echo esc_attr( $sticker['type'] ); ?>" data-name="<?php echo esc_attr( strtolower( $sticker['name'] ) ); ?>">
								<div class="apeiron-sticker-item__preview">
									<?php if ( 'video' === $sticker['type'] ) : ?>
										<video src="<?php echo esc_url( $sticker['url'] ); ?>" muted loop playsinline onmouseover="this.play()" onmouseout="this.pause();this.currentTime=0;"></video>
									<?php else : ?>
										<img src="<?php echo esc_url( $sticker['url'] ); ?>" alt="<?php echo esc_attr( $sticker['name'] ); ?>" loading="lazy">
									<?php endif; ?>
								</div>
								<div class="apeiron-sticker-item__info">
									<div class="apeiron-sticker-item__name" title="<?php echo esc_attr( $sticker['name'] ); ?>"><?php echo esc_html( $sticker['name'] ); ?></div>
									<span class="apeiron-sticker-item__type apeiron-sticker-item__type--<?php echo esc_attr( $sticker['type'] ); ?>">
										<span class="dashicons dashicons-<?php echo $sticker['type'] === 'video' ? 'video-alt3' : 'format-image'; ?>"></span>
										<?php echo esc_html( ucfirst( $sticker['type'] ) ); ?>
									</span>
								</div>
								<button type="button" class="apeiron-sticker-item__delete" data-path="<?php echo esc_attr( $sticker['relative'] ); ?>" title="<?php esc_attr_e( 'Hapus', 'apeiron-kit' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</div>
						<?php endforeach; ?>
						<p class="apeiron-elements-empty" id="apeiron-sticker-empty" style="display:none;">
							<?php esc_html_e( 'Tidak ada stiker yang sesuai dengan filter tipe.', 'apeiron-kit' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render runtime config for sticker interactions.
	 */
	private function render_runtime_config( string $folder ): void {
		$runtime_config = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'folder'  => $folder,
			'nonce'   => wp_create_nonce( 'apeiron_sticker_management' ),
			'i18n'    => [
				'uploading'     => __( 'Uploading...', 'apeiron-kit' ),
				'uploadSuccess' => __( 'Upload berhasil!', 'apeiron-kit' ),
				'uploadFailed'  => __( 'Upload gagal', 'apeiron-kit' ),
				'uploadError'   => __( 'Upload error', 'apeiron-kit' ),
				'uploadTimeout' => __( 'Timeout - file terlalu besar', 'apeiron-kit' ),
				'confirmDelete' => __( 'Hapus stiker ini?', 'apeiron-kit' ),
				'deleteFailed'  => __( 'Gagal menghapus.', 'apeiron-kit' ),
				'deleteError'   => __( 'Terjadi kesalahan saat menghapus stiker.', 'apeiron-kit' ),
			],
		];

		$this->render_config_payload( 'stickers', $runtime_config );
	}
}
