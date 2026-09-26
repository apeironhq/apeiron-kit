<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Admin\WidgetCatalog;
use ApeironKit\Support\WidgetRegistry;

/**
 * Widgets settings tab.
 */
class GeneralTab extends AbstractTab {

	/**
	 * Widget definitions used by cards and toggles.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_features(): array {
		return WidgetCatalog::get_features();
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'widgets';
	}

	/**
	 * @inheritDoc
	 */
	public function get_title(): string {
		return __( 'Beranda', 'apeiron-kit' );
	}

	/**
	 * @inheritDoc
	 */
	public function render(): void {
		$disabled = WidgetRegistry::disabled_slugs();

		$features = $this->get_features();
		$toggleable_slugs = array_values( array_filter(
			array_map(
				static function ( array $feature ): string {
					return ( 'coming_soon' === ( $feature['status'] ?? 'available' ) ) ? '' : (string) $feature['slug'];
				},
				$features
			)
		) );
		$all_toggleable_enabled = count( array_intersect( $disabled, $toggleable_slugs ) ) === 0;

		// Collect unique groups for filter dropdown
		$groups = [];
		foreach ( $features as $feature ) {
			$groups[ $feature['group_key'] ] = $feature['group'];
		}
		?>
		<div class="apeiron-dashboard-content-grid">
			<?php $this->render_elements_section( $features, $disabled, $groups, $all_toggleable_enabled ); ?>
		</div>

		<?php $this->render_widget_runtime(); ?>
		<?php
	}

	/**
	 * Render the EA-inspired elements/widget section.
	 *
	 * @param array<int,array<string,mixed>>  $features Widget definitions.
	 * @param array<int,string>               $disabled Disabled widget slugs.
	 * @param array<string,string>            $groups   Unique group key => label map.
	 * @param bool                            $all_toggleable_enabled Whether all selectable widgets are active.
	 */
	private function render_elements_section( array $features, array $disabled, array $groups, bool $all_toggleable_enabled ): void {
		?>
		<section class="apeiron-elements-section apeiron-widget-toggle-section" id="apeiron-widgets">
			<!-- Toolbar: Title + Search + Filter + Enable All -->
			<div class="apeiron-elements-toolbar">
				<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Widgets', 'apeiron-kit' ); ?></h2>

				<div class="apeiron-elements-toolbar__search">
					<label class="screen-reader-text" for="apeiron-widget-search"><?php esc_html_e( 'Cari widget Apeiron Kit', 'apeiron-kit' ); ?></label>
					<input type="text"
						id="apeiron-widget-search"
						placeholder="<?php esc_attr_e( 'Cari widget...', 'apeiron-kit' ); ?>"
						autocomplete="off"
					/>
					<span class="dashicons dashicons-search apeiron-elements-toolbar__search-icon"></span>
					<button type="button" class="apeiron-toolbar-clear" id="apeiron-widget-search-clear" aria-label="<?php esc_attr_e( 'Bersihkan pencarian widget', 'apeiron-kit' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>

				<label class="screen-reader-text" for="apeiron-widget-filter"><?php esc_html_e( 'Filter kategori widget', 'apeiron-kit' ); ?></label>
				<select class="apeiron-elements-toolbar__filter" id="apeiron-widget-filter">
					<option value=""><?php esc_html_e( 'Semua Widget', 'apeiron-kit' ); ?></option>
					<?php foreach ( $groups as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<span class="apeiron-elements-toolbar__spacer"></span>
				<span class="apeiron-elements-toolbar__count" id="apeiron-widget-result-count" aria-live="polite"></span>

				<label class="apeiron-elements-toolbar__enable-all">
					<?php esc_html_e( 'Aktifkan Semua', 'apeiron-kit' ); ?>
					<span class="apeiron-toggle">
						<input type="checkbox"
							id="apeiron-enable-all-toggle"
							aria-label="<?php esc_attr_e( 'Aktifkan atau nonaktifkan semua widget Apeiron Kit', 'apeiron-kit' ); ?>"
							<?php checked( $all_toggleable_enabled ); ?>
						/>
						<span class="apeiron-toggle__slider"></span>
					</span>
				</label>
			</div>

			<!-- Widget Grid: 3 columns, EA pattern -->
			<div class="apeiron-elements-content apeiron-widgets-content">
				<div class="apeiron-elements-grid" id="apeiron-elements-grid">
					<?php foreach ( $features as $feature ) :
						$status         = (string) ( $feature['status'] ?? 'available' );
						$is_coming_soon = 'coming_soon' === $status;
						$is_active      = ! $is_coming_soon && ! in_array( $feature['slug'], $disabled, true );
						$item_classes   = [
							'apeiron-element-item',
							$is_active ? 'is-active' : 'is-inactive',
						];
						if ( $is_coming_soon ) {
							$item_classes[] = 'is-coming-soon';
						}
						$description = $is_coming_soon && ! empty( $feature['status_description'] )
							? (string) $feature['status_description']
							: (string) $feature['description'];
					?>
						<div class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
							data-widget="<?php echo esc_attr( $feature['slug'] ); ?>"
							data-group="<?php echo esc_attr( $feature['group_key'] ); ?>"
							data-status="<?php echo esc_attr( $status ); ?>"
							data-name="<?php echo esc_attr( strtolower( $feature['label'] ) ); ?>"
							<?php echo $is_coming_soon ? 'aria-disabled="true"' : ''; ?>>

							<div class="apeiron-element-item__head">
								<span class="apeiron-element-item__name"><?php echo esc_html( $feature['label'] ); ?></span>
								<?php if ( $is_coming_soon ) : ?>
									<span class="apeiron-element-item__status"><?php echo esc_html( $feature['status_label'] ?? __( 'Coming Soon', 'apeiron-kit' ) ); ?></span>
								<?php else : ?>
									<label class="apeiron-toggle">
										<input type="checkbox"
											class="apeiron-widget-toggle"
											data-widget="<?php echo esc_attr( $feature['slug'] ); ?>"
											aria-label="<?php echo esc_attr( sprintf( __( 'Aktifkan widget %s', 'apeiron-kit' ), $feature['label'] ) ); ?>"
											<?php checked( $is_active ); ?>
										/>
										<span class="apeiron-toggle__slider"></span>
									</label>
								<?php endif; ?>
							</div>

							<div class="apeiron-element-item__foot">
								<span class="apeiron-badge apeiron-badge--<?php echo esc_attr( $feature['group_key'] ); ?>"><?php echo esc_html( $feature['group'] ); ?></span>
								<span class="apeiron-element-item__actions">
									<span class="apeiron-element-item__action" tabindex="0" role="note" aria-label="<?php echo esc_attr( $description ); ?>" title="<?php echo esc_attr( $description ); ?>">
										<span class="dashicons dashicons-info-outline"></span>
									</span>
								</span>
							</div>
						</div>
					<?php endforeach; ?>

					<p class="apeiron-elements-empty" id="apeiron-elements-empty" style="display:none;">
						<?php esc_html_e( 'Tidak ada widget yang sesuai dengan pencarian.', 'apeiron-kit' ); ?>
					</p>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Render runtime mounts and boot config for widget interactions.
	 */
	private function render_widget_runtime(): void {
		$nonce = wp_create_nonce( \ApeironKit\Admin\Ajax\WidgetToggleHandler::get_nonce_action() );

		// Build widget label map for modal display
		$widget_labels = [];
		foreach ( $this->get_features() as $feature ) {
			$widget_labels[ $feature['slug'] ] = $feature['label'];
		}

		$runtime_config = [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => $nonce,
			'widgetLabels' => $widget_labels,
			'i18n'         => [
				'usedIn'          => __( 'digunakan di', 'apeiron-kit' ),
				'pages'           => __( 'halaman:', 'apeiron-kit' ),
				'checkingUsage'   => __( 'Memeriksa penggunaan...', 'apeiron-kit' ),
				'processing'      => __( 'sedang diproses...', 'apeiron-kit' ),
				'enabled'         => __( 'berhasil diaktifkan', 'apeiron-kit' ),
				'disabled'        => __( 'berhasil dinonaktifkan', 'apeiron-kit' ),
				'toggleError'     => __( 'Gagal mengubah status widget', 'apeiron-kit' ),
				'usageCheckError' => __( 'Penggunaan widget tidak dapat diperiksa. Tidak ada perubahan yang disimpan.', 'apeiron-kit' ),
				'bulkProcessing'   => __( 'Memproses %d widget...', 'apeiron-kit' ),
				'bulkEnabled'      => __( '%d widget berhasil diaktifkan.', 'apeiron-kit' ),
				'bulkDisabled'     => __( '%d widget berhasil dinonaktifkan.', 'apeiron-kit' ),
				'bulkToggleError'  => __( 'Perubahan bulk gagal. Semua toggle dikembalikan ke status sebelumnya.', 'apeiron-kit' ),
				/* translators: 1: number of widgets, 2: total page references. */
				'bulkUsageSummary' => __( '%1$d widget yang akan dinonaktifkan digunakan pada %2$d halaman:', 'apeiron-kit' ),
				'visibleWidgets'  => __( 'widget tampil', 'apeiron-kit' ),
				'noChanges'       => __( 'Tidak ada perubahan', 'apeiron-kit' ),
			],
		];

		?>

		<!-- Widget Usage Warning Modal -->
		<div id="apeiron-usage-modal" class="apeiron-modal" style="display:none">
			<div class="apeiron-modal__backdrop"></div>
			<div class="apeiron-modal__dialog">
				<div class="apeiron-modal__header">
					<svg class="apeiron-modal__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d63638" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
					<span class="apeiron-modal__title"><?php esc_html_e( 'Widget Sedang Digunakan', 'apeiron-kit' ); ?></span>
				</div>
				<p class="apeiron-modal__desc" id="apeiron-modal-desc"></p>
				<ul class="apeiron-modal__pages" id="apeiron-modal-pages"></ul>
				<p class="apeiron-modal__warning"><?php esc_html_e( 'Menonaktifkan widget bisa merusak layout halaman tersebut.', 'apeiron-kit' ); ?></p>
				<div class="apeiron-modal__actions">
					<button type="button" class="apeiron-btn apeiron-btn--secondary" id="apeiron-modal-cancel"><?php esc_html_e( 'Batal', 'apeiron-kit' ); ?></button>
					<button type="button" class="apeiron-btn apeiron-btn--danger" id="apeiron-modal-confirm"><?php esc_html_e( 'Nonaktifkan', 'apeiron-kit' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Toast Notification -->
		<div id="apeiron-toast" class="apeiron-toast-container"></div>

		<?php $this->render_config_payload( 'widgets', $runtime_config ); ?>
		<?php
	}
}
