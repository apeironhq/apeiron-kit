<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Versioned per-post index of Apeiron widgets found in Elementor data.
 */
final class WidgetUsageIndex {

	public const META_KEY      = '_apeiron_widget_usage_index';
	public const INDEX_VERSION = 1;

	private const ELEMENTOR_META_KEY   = '_elementor_data';
	private const MAX_PAGES_PER_WIDGET = 50;

	private bool $registered = false;

	/**
	 * Register index maintenance hooks.
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		add_action( 'elementor/editor/after_save', [ $this, 'on_elementor_save' ], 10, 2 );
		add_action( 'save_post', [ $this, 'on_post_save' ], 100 );
		add_action( 'added_post_meta', [ $this, 'invalidate_on_meta_change' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'invalidate_on_meta_change' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'invalidate_on_meta_change' ], 10, 4 );
	}

	/**
	 * Build the authoritative index from Elementor's saved editor payload.
	 *
	 * @param int   $post_id     Saved post ID.
	 * @param mixed $editor_data Saved Elementor data.
	 */
	public function on_elementor_save( $post_id, $editor_data ): void {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return;
		}

		self::index_post( $post_id, $editor_data );
	}

	/**
	 * Backfill a missing or stale index when an existing Elementor post is saved.
	 *
	 * @param int $post_id Saved post ID.
	 */
	public function on_post_save( $post_id ): void {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( function_exists( 'metadata_exists' ) && ! metadata_exists( 'post', $post_id, self::ELEMENTOR_META_KEY ) ) {
			return;
		}

		$current = get_post_meta( $post_id, self::META_KEY, true );
		if ( null !== self::decode_index( $current ) ) {
			return;
		}

		self::index_post( $post_id );
	}

	/**
	 * Invalidate the index whenever Elementor data changes outside the save hook.
	 *
	 * @param mixed  $meta_id    Meta row ID or IDs.
	 * @param int    $post_id    Owning post ID.
	 * @param string $meta_key   Changed key.
	 * @param mixed  $meta_value Changed value.
	 */
	public function invalidate_on_meta_change( $meta_id, $post_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );

		if ( self::ELEMENTOR_META_KEY !== $meta_key ) {
			return;
		}

		self::invalidate( (int) $post_id );
	}

	/**
	 * Build and store an index for one post.
	 *
	 * @param int        $post_id       Post ID.
	 * @param mixed|null $elementor_data Optional Elementor payload; null reads post meta.
	 */
	public static function index_post( int $post_id, $elementor_data = null ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( null === $elementor_data ) {
			$elementor_data = get_post_meta( $post_id, self::ELEMENTOR_META_KEY, true );
		}

		$slugs = self::extract_widget_slugs( $elementor_data );
		if ( null === $slugs ) {
			self::invalidate( $post_id );
			return false;
		}

		update_post_meta( $post_id, self::META_KEY, self::encode_index( $slugs ) );

		return true;
	}

	/**
	 * Remove a post's usage index so reads fall back to legacy Elementor data.
	 */
	public static function invalidate( int $post_id ): void {
		if ( $post_id > 0 ) {
			delete_post_meta( $post_id, self::META_KEY );
		}
	}

	/**
	 * Encode a canonical, queryable index value.
	 *
	 * @param string[] $slugs Widget slugs.
	 */
	public static function encode_index( array $slugs ): string {
		$slugs = self::normalize_slugs( $slugs );
		$value = self::version_prefix();

		if ( ! empty( $slugs ) ) {
			$value .= implode( '|', $slugs ) . '|';
		}

		return $value;
	}

	/**
	 * Decode only values written by the current index schema.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string[]|null Null means missing, malformed, or from an old schema.
	 */
	public static function decode_index( $value ): ?array {
		if ( ! is_string( $value ) || 0 !== strpos( $value, self::version_prefix() ) ) {
			return null;
		}

		$encoded = substr( $value, strlen( self::version_prefix() ) );
		if ( '' === $encoded ) {
			return [];
		}

		if ( '|' !== substr( $encoded, -1 ) ) {
			return null;
		}

		$slugs = explode( '|', rtrim( $encoded, '|' ) );

		return self::normalize_slugs( $slugs );
	}

	/**
	 * Extract canonical widget slugs from an Elementor element tree or JSON value.
	 *
	 * @param mixed $elementor_data Elementor data.
	 * @return string[]|null Null indicates unreadable data and must not be indexed.
	 */
	public static function extract_widget_slugs( $elementor_data ): ?array {
		if ( is_string( $elementor_data ) ) {
			if ( '' === trim( $elementor_data ) ) {
				return null;
			}

			$decoded = json_decode( $elementor_data, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				return null;
			}

			$elementor_data = $decoded;
		}

		if ( ! is_array( $elementor_data ) ) {
			return null;
		}

		$type_map = WidgetRegistry::type_to_slug_map();
		$found    = [];
		$stack    = [ $elementor_data ];

		while ( ! empty( $stack ) ) {
			$node = array_pop( $stack );
			if ( ! is_array( $node ) ) {
				continue;
			}

			if ( isset( $node['widgetType'] ) && is_string( $node['widgetType'] ) && isset( $type_map[ $node['widgetType'] ] ) ) {
				$found[] = $type_map[ $node['widgetType'] ];
			}

			foreach ( $node as $value ) {
				if ( is_array( $value ) ) {
					$stack[] = $value;
				}
			}
		}

		return self::normalize_slugs( $found );
	}

	/**
	 * Return aggregate usage for widget slugs, using current indexes first and
	 * scanning only unindexed/stale Elementor records as a compatibility fallback.
	 *
	 * @param string[] $slugs Widget slugs.
	 * @return array<string,array{used:bool,count:int,pages:array<int,array<string,mixed>>}>
	 * @throws \RuntimeException When usage cannot be checked safely.
	 */
	public static function get_usage( array $slugs ): array {
		$slugs = self::normalize_slugs( $slugs );
		$usage = [];

		foreach ( $slugs as $slug ) {
			$usage[ $slug ] = [
				'used'  => false,
				'count' => 0,
				'pages' => [],
			];
		}

		if ( empty( $slugs ) ) {
			return $usage;
		}

		$seen = array_fill_keys( $slugs, [] );

		foreach ( self::query_indexed_posts( $slugs ) as $row ) {
			$indexed_slugs = self::decode_index( self::row_value( $row, 'usage_index' ) );
			if ( null !== $indexed_slugs ) {
				self::add_usage_row( $usage, $seen, $row, $indexed_slugs );
			}
		}

		foreach ( self::query_legacy_posts( $slugs ) as $row ) {
			$raw          = self::row_value( $row, 'elementor_data' );
			$legacy_slugs = self::extract_widget_slugs( $raw );

			if ( null === $legacy_slugs ) {
				$legacy_slugs = self::extract_slugs_from_legacy_string( $raw );
			}

			self::add_usage_row( $usage, $seen, $row, $legacy_slugs );
		}

		foreach ( $usage as &$widget_usage ) {
			$widget_usage['used'] = $widget_usage['count'] > 0;
		}
		unset( $widget_usage );

		return $usage;
	}

	/**
	 * @param string[] $slugs
	 * @return string[]
	 */
	private static function normalize_slugs( array $slugs ): array {
		$clean  = WidgetRegistry::sanitize_slugs( $slugs );
		$lookup = array_fill_keys( $clean, true );
		$result = [];

		foreach ( WidgetRegistry::allowed_slugs() as $slug ) {
			if ( isset( $lookup[ $slug ] ) ) {
				$result[] = $slug;
			}
		}

		return $result;
	}

	private static function version_prefix(): string {
		return 'v' . self::INDEX_VERSION . '|';
	}

	/**
	 * @param string[] $slugs
	 * @return array<int,object|array>
	 */
	private static function query_indexed_posts( array $slugs ): array {
		global $wpdb;

		if ( ! is_object( $wpdb ) || ! isset( $wpdb->posts, $wpdb->postmeta ) ) {
			throw new \RuntimeException( 'WordPress database is unavailable.' );
		}

		$conditions = [];
		$args       = [ self::META_KEY, $wpdb->esc_like( self::version_prefix() ) . '%' ];

		foreach ( $slugs as $slug ) {
			$conditions[] = 'usage_pm.meta_value LIKE %s';
			$args[]       = '%' . $wpdb->esc_like( '|' . $slug . '|' ) . '%';
		}

		$sql = "SELECT DISTINCT p.ID, p.post_title, usage_pm.meta_value AS usage_index
			FROM {$wpdb->postmeta} usage_pm
			INNER JOIN {$wpdb->posts} p ON p.ID = usage_pm.post_id
			WHERE usage_pm.meta_key = %s
				AND usage_pm.meta_value LIKE %s
				AND p.post_status IN ('publish', 'draft', 'private')
				AND (" . implode( ' OR ', $conditions ) . ')';

		return self::run_query( $sql, $args );
	}

	/**
	 * @param string[] $slugs
	 * @return array<int,object|array>
	 */
	private static function query_legacy_posts( array $slugs ): array {
		global $wpdb;

		if ( ! is_object( $wpdb ) || ! isset( $wpdb->posts, $wpdb->postmeta ) ) {
			throw new \RuntimeException( 'WordPress database is unavailable.' );
		}

		$requested = array_fill_keys( $slugs, true );
		$types     = [];
		foreach ( WidgetRegistry::type_to_slug_map() as $type => $slug ) {
			if ( isset( $requested[ $slug ] ) ) {
				$types[] = $type;
			}
		}

		if ( empty( $types ) ) {
			return [];
		}

		$conditions = [];
		$args       = [
			self::META_KEY,
			$wpdb->esc_like( self::version_prefix() ) . '%',
			self::ELEMENTOR_META_KEY,
		];

		foreach ( $types as $type ) {
			$conditions[] = 'data_pm.meta_value LIKE %s';
			$args[]       = '%' . $wpdb->esc_like( $type ) . '%';
		}

		$sql = "SELECT DISTINCT p.ID, p.post_title, data_pm.meta_value AS elementor_data
			FROM {$wpdb->postmeta} data_pm
			INNER JOIN {$wpdb->posts} p ON p.ID = data_pm.post_id
			LEFT JOIN {$wpdb->postmeta} current_index
				ON current_index.post_id = data_pm.post_id
				AND current_index.meta_key = %s
				AND current_index.meta_value LIKE %s
			WHERE data_pm.meta_key = %s
				AND current_index.meta_id IS NULL
				AND p.post_status IN ('publish', 'draft', 'private')
				AND (" . implode( ' OR ', $conditions ) . ')';

		return self::run_query( $sql, $args );
	}

	/**
	 * @param string $sql  SQL with placeholders.
	 * @param array  $args Placeholder values.
	 * @return array<int,object|array>
	 */
	private static function run_query( string $sql, array $args ): array {
		global $wpdb;

		$query = $wpdb->prepare( $sql, ...$args );
		if ( ! is_string( $query ) ) {
			throw new \RuntimeException( 'Widget usage query could not be prepared.' );
		}

		$rows = $wpdb->get_results( $query );
		if ( ! is_array( $rows ) ) {
			throw new \RuntimeException( 'Widget usage query failed.' );
		}

		return $rows;
	}

	/**
	 * @param mixed $row Database row.
	 * @return mixed
	 */
	private static function row_value( $row, string $key ) {
		if ( is_object( $row ) && isset( $row->{$key} ) ) {
			return $row->{$key};
		}

		if ( is_array( $row ) && isset( $row[ $key ] ) ) {
			return $row[ $key ];
		}

		return null;
	}

	/**
	 * @param mixed $raw Legacy Elementor meta value.
	 * @return string[]
	 */
	private static function extract_slugs_from_legacy_string( $raw ): array {
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}

		$found = [];
		foreach ( WidgetRegistry::type_to_slug_map() as $type => $slug ) {
			if ( false !== strpos( $raw, $type ) ) {
				$found[] = $slug;
			}
		}

		return self::normalize_slugs( $found );
	}

	/**
	 * @param array<string,array{used:bool,count:int,pages:array<int,array<string,mixed>>}> $usage
	 * @param array<string,array<int,bool>>                                             $seen
	 * @param mixed                                                                     $row
	 * @param string[]                                                                  $row_slugs
	 */
	private static function add_usage_row( array &$usage, array &$seen, $row, array $row_slugs ): void {
		$post_id = (int) self::row_value( $row, 'ID' );
		if ( $post_id <= 0 ) {
			return;
		}

		$title = self::row_value( $row, 'post_title' );
		$title = is_string( $title ) && '' !== $title ? $title : __( '(Tanpa Judul)', 'apeiron-kit' );

		$edit_url = get_edit_post_link( $post_id, 'raw' );
		$edit_url = is_string( $edit_url ) ? $edit_url : '';

		foreach ( $row_slugs as $slug ) {
			if ( ! isset( $usage[ $slug ] ) || isset( $seen[ $slug ][ $post_id ] ) ) {
				continue;
			}

			$seen[ $slug ][ $post_id ] = true;
			++$usage[ $slug ]['count'];

			if ( count( $usage[ $slug ]['pages'] ) < self::MAX_PAGES_PER_WIDGET ) {
				$usage[ $slug ]['pages'][] = [
					'id'       => $post_id,
					'title'    => $title,
					'edit_url' => $edit_url,
				];
			}
		}
	}
}
