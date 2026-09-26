<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegacyContentCleanup {

	private const OPTION_NAME   = 'apeiron_kit_content_cleanup_version';
	private const CURSOR_OPTION = 'apeiron_kit_content_cleanup_cursor_v1';
	private const LOCK_OPTION   = 'apeiron_kit_content_cleanup_lock';
	private const VERSION       = 1;
	private const BATCH_SIZE    = 20;
	private const LOCK_TTL      = 300;

	/** @var string[] */
	private const REMOVED_WIDGET_TYPES = [
		'apeiron-motion-25d',
	];

	private bool $registered = false;

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		add_action( 'admin_init', [ $this, 'run' ] );
	}

	public function run(): void {
		if ( empty( self::REMOVED_WIDGET_TYPES ) || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( (int) get_option( self::OPTION_NAME, 0 ) >= self::VERSION ) {
			return;
		}

		$lock = $this->acquire_lock();
		if ( '' === $lock ) {
			return;
		}

		try {
			$cursor   = absint( get_option( self::CURSOR_OPTION, 0 ) );
			$post_ids = $this->find_posts_to_clean( $cursor );
			if ( empty( $post_ids ) ) {
				update_option( self::OPTION_NAME, self::VERSION, false );
				delete_option( self::CURSOR_OPTION );
				return;
			}

			$changed        = false;
			$failed         = false;
			$last_processed = $cursor;
			foreach ( $post_ids as $post_id ) {
				$raw = get_post_meta( $post_id, '_elementor_data', true );
				if ( ! is_string( $raw ) || '' === $raw ) {
					$last_processed = $post_id;
					continue;
				}

				$data = json_decode( $raw, true );
				if ( ! is_array( $data ) ) {
					$last_processed = $post_id;
					continue;
				}

				$clean = self::strip_removed_widgets( $data );
				if ( $clean === $data ) {
					$last_processed = $post_id;
					continue;
				}

				$encoded = wp_json_encode( $clean );
				if ( ! is_string( $encoded ) ) {
					$last_processed = $post_id;
					continue;
				}

				if ( false === update_post_meta( $post_id, '_elementor_data', wp_slash( $encoded ), $raw ) ) {
					$failed = true;
					break;
				}

				$changed        = true;
				$last_processed = $post_id;
			}

			if ( $last_processed > $cursor ) {
				update_option( self::CURSOR_OPTION, $last_processed, false );
			}

			if ( $changed && class_exists( '\Elementor\Plugin' )
				&& isset( \Elementor\Plugin::$instance->files_manager )
			) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}

			if ( ! $failed && count( $post_ids ) < self::BATCH_SIZE ) {
				update_option( self::OPTION_NAME, self::VERSION, false );
				delete_option( self::CURSOR_OPTION );
			}
		} finally {
			$this->release_lock( $lock );
		}
	}

	/**
	 * @param array<int,mixed> $elements
	 * @return array<int,mixed>
	 */
	public static function strip_removed_widgets( array $elements ): array {
		$clean = [];
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				$clean[] = $element;
				continue;
			}

			$type = isset( $element['widgetType'] ) && is_string( $element['widgetType'] ) ? $element['widgetType'] : '';
			if ( in_array( $type, self::REMOVED_WIDGET_TYPES, true ) ) {
				continue;
			}

			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$element['elements'] = self::strip_removed_widgets( $element['elements'] );
			}
			$clean[] = $element;
		}

		return $clean;
	}

	/** @return int[] */
	private function find_posts_to_clean( int $cursor ): array {
		global $wpdb;

		$conditions = [];
		$args       = [ '_elementor_data', $cursor ];
		foreach ( self::REMOVED_WIDGET_TYPES as $type ) {
			$conditions[] = 'pm.meta_value LIKE %s';
			$args[]       = '%"widgetType":"' . $wpdb->esc_like( $type ) . '"%';
		}
		$args[] = self::BATCH_SIZE;

		$sql = "SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
				AND pm.post_id > %d
				AND p.post_type <> 'revision'
				AND (" . implode( ' OR ', $conditions ) . ')
			ORDER BY pm.post_id ASC
			LIMIT %d';

		$prepared = $wpdb->prepare( $sql, ...$args );
		$ids      = is_string( $prepared ) ? $wpdb->get_col( $prepared ) : [];

		return array_values( array_filter( array_map( 'absint', is_array( $ids ) ? $ids : [] ) ) );
	}

	private function acquire_lock(): string {
		global $wpdb;

		$now  = time();
		$lock = ( $now + self::LOCK_TTL ) . '|' . wp_generate_uuid4();
		if ( add_option( self::LOCK_OPTION, $lock, '', false ) ) {
			return $lock;
		}

		$current   = get_option( self::LOCK_OPTION, '' );
		$separator = is_string( $current ) ? strpos( $current, '|' ) : false;
		$expires   = false === $separator ? 0 : (int) substr( $current, 0, $separator );
		if ( ! is_string( $current ) || $expires > $now ) {
			return '';
		}

		$updated = $wpdb->update(
			$wpdb->options,
			[ 'option_value' => $lock ],
			[ 'option_name' => self::LOCK_OPTION, 'option_value' => $current ],
			[ '%s' ],
			[ '%s', '%s' ]
		);
		wp_cache_delete( self::LOCK_OPTION, 'options' );

		return 1 === $updated ? $lock : '';
	}

	private function release_lock( string $lock ): void {
		global $wpdb;

		$wpdb->delete(
			$wpdb->options,
			[ 'option_name' => self::LOCK_OPTION, 'option_value' => $lock ],
			[ '%s', '%s' ]
		);
		wp_cache_delete( self::LOCK_OPTION, 'options' );
	}
}
