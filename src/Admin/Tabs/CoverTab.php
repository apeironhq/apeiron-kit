<?php

namespace ApeironKit\Admin\Tabs;

use ApeironKit\Support\CoverTypeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cover type library. */
class CoverTab extends AbstractTab {

	private const ELEMENTOR_META_KEY = '_elementor_data';
	private const COVER_WIDGET_TYPE  = 'apeiron-cover';

	public function get_slug(): string {
		return 'cover';
	}

	public function get_title(): string {
		return __( 'Sampul', 'apeiron-kit' );
	}

	public function render(): void {
		$usage_counts = $this->get_usage_counts();
		?>
		<div class="apeiron-cover-settings-wrap">
			<section class="apeiron-elements-section apeiron-cover-settings-section">
				<div class="apeiron-elements-toolbar apeiron-cover-settings-toolbar">
					<div class="apeiron-cover-settings-toolbar__copy">
						<h2 class="apeiron-elements-toolbar__title"><?php esc_html_e( 'Sampul', 'apeiron-kit' ); ?></h2>
						<p><?php esc_html_e( 'Koleksi jenis Sampul untuk undangan.', 'apeiron-kit' ); ?></p>
					</div>
				</div>

				<div class="apeiron-elements-content apeiron-cover-settings-content">
					<div class="apeiron-cover-choice-grid" aria-label="<?php esc_attr_e( 'Pilihan Sampul', 'apeiron-kit' ); ?>">
						<?php foreach ( CoverTypeRegistry::get_types() as $slug => $type ) : ?>
							<?php $available = CoverTypeRegistry::is_available_type( $slug ); ?>
							<article class="apeiron-cover-summary-card <?php echo $available ? 'is-active' : 'is-coming-soon'; ?>" <?php echo $available ? '' : 'aria-disabled="true"'; ?> aria-labelledby="apeiron-cover-<?php echo esc_attr( $slug ); ?>-title">
								<span class="apeiron-cover-summary-card__icon dashicons dashicons-<?php echo esc_attr( $type['icon'] ); ?>" aria-hidden="true"></span>
								<div class="apeiron-cover-summary-card__copy">
									<span class="apeiron-cover-summary-card__eyebrow"><?php echo esc_html( $type['eyebrow'] ); ?></span>
									<h3 id="apeiron-cover-<?php echo esc_attr( $slug ); ?>-title"><?php echo esc_html( $type['label'] ); ?></h3>
									<p><?php echo esc_html( $type['description'] ); ?></p>
									<?php if ( $available && null !== $usage_counts ) : ?>
										<p class="apeiron-cover-summary-card__usage"><?php echo esc_html( sprintf( __( 'Digunakan pada %s undangan', 'apeiron-kit' ), number_format_i18n( $usage_counts[ $slug ] ?? 0 ) ) ); ?></p>
									<?php endif; ?>
								</div>
								<div class="apeiron-cover-summary-card__status <?php echo $available ? '' : 'is-muted'; ?>" <?php echo $available ? 'role="status" aria-label="' . esc_attr( sprintf( __( '%s tersedia', 'apeiron-kit' ), $type['label'] ) ) . '"' : ''; ?>>
									<span><?php echo esc_html( $type['status_label'] ); ?></span>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Count distinct Elementor documents per rendered Cover type.
	 *
	 * @return array<string,int>|null Null means the usage query could not run safely.
	 */
	private function get_usage_counts(): ?array {
		global $wpdb;

		if ( ! is_object( $wpdb )
			|| ! isset( $wpdb->posts, $wpdb->postmeta )
			|| ! method_exists( $wpdb, 'esc_like' )
			|| ! method_exists( $wpdb, 'prepare' )
			|| ! method_exists( $wpdb, 'get_results' ) ) {
			return null;
		}

		$sql = "SELECT DISTINCT p.ID, data_pm.meta_value AS elementor_data
			FROM {$wpdb->postmeta} data_pm
			INNER JOIN {$wpdb->posts} p ON p.ID = data_pm.post_id
			WHERE data_pm.meta_key = %s
				AND p.post_status IN ('publish', 'draft', 'private')
				AND data_pm.meta_value LIKE %s";
		$query = $wpdb->prepare(
			$sql,
			self::ELEMENTOR_META_KEY,
			'%' . $wpdb->esc_like( self::COVER_WIDGET_TYPE ) . '%'
		);

		if ( ! is_string( $query ) ) {
			return null;
		}

		$rows = $wpdb->get_results( $query );
		if ( ! is_array( $rows ) ) {
			return null;
		}

		$posts_by_type = array_fill_keys( CoverTypeRegistry::get_slugs(), [] );
		foreach ( $rows as $row ) {
			$post_id        = is_object( $row ) ? (int) ( $row->ID ?? 0 ) : (int) ( $row['ID'] ?? 0 );
			$elementor_data = is_object( $row ) ? ( $row->elementor_data ?? null ) : ( $row['elementor_data'] ?? null );
			if ( $post_id <= 0 ) {
				continue;
			}

			foreach ( self::extract_cover_types( $elementor_data ) as $cover_type ) {
				$posts_by_type[ $cover_type ][ $post_id ] = true;
			}
		}

		return array_map( 'count', $posts_by_type );
	}

	/**
	 * Resolve persisted Cover widgets exactly as the frontend registry does.
	 *
	 * Missing or unavailable values therefore count toward Classic because that
	 * is the type those legacy widgets actually render.
	 *
	 * @param mixed $elementor_data Raw Elementor document data.
	 * @return string[]
	 */
	private static function extract_cover_types( $elementor_data ): array {
		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		if ( ! is_array( $elementor_data ) ) {
			return [];
		}

		$found = [];
		$stack = [ $elementor_data ];

		while ( ! empty( $stack ) ) {
			$node = array_pop( $stack );
			if ( ! is_array( $node ) ) {
				continue;
			}

			if ( self::COVER_WIDGET_TYPE === ( $node['widgetType'] ?? null ) ) {
				$settings    = is_array( $node['settings'] ?? null ) ? $node['settings'] : [];
				$stored_type = $settings['cover_type'] ?? '';
				$stored_type = is_scalar( $stored_type ) ? (string) $stored_type : '';
				$found[ CoverTypeRegistry::sanitize_type( $stored_type ) ] = true;
			}

			foreach ( $node as $value ) {
				if ( is_array( $value ) ) {
					$stack[] = $value;
				}
			}
		}

		return array_keys( $found );
	}
}
