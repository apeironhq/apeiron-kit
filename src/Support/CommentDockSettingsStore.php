<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Stores REST fallback settings for Theme Builder and global templates. */
class CommentDockSettingsStore {

	private const OPTION_PREFIX = 'apeiron_comment_dock_settings_';
	private const WIDGET_TYPE   = 'apeiron-comment-dock';

	/** @var array<string,bool> */
	private static array $persisted = [];

	/** @var array<int,bool> */
	private static array $syncing_posts = [];

	public static function register(): void {
		add_action( 'elementor/document/after_save', [ self::class, 'on_document_save' ], 10, 2 );
		add_action( 'delete_post', [ self::class, 'on_post_deleted' ] );
	}

	private static function index_option_key( int $post_id ): string {
		return self::OPTION_PREFIX . 'index_' . $post_id;
	}

	public static function on_post_deleted( int $post_id ): void {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return;
		}

		$ids = get_option( self::index_option_key( $post_id ), [] );
		if ( is_array( $ids ) ) {
			foreach ( $ids as $element_id ) {
				if ( is_string( $element_id ) && '' !== $element_id ) {
					delete_option( self::option_key( $post_id, (string) $element_id ) );
				}
			}
		}
		delete_option( self::index_option_key( $post_id ) );
	}

	public static function option_key( int $post_id, string $element_id ): string {
		return self::OPTION_PREFIX . md5( $post_id . '|' . $element_id );
	}

	public static function persist( int $post_id, string $element_id, array $settings ): void {
		if ( $post_id <= 0 || '' === $element_id ) {
			return;
		}
		update_option( self::option_key( $post_id, $element_id ), $settings, false );
	}

	public static function persist_once( int $post_id, string $element_id, array $settings ): void {
		if ( $post_id <= 0 || '' === $element_id ) {
			return;
		}
		$key = $post_id . '|' . $element_id;
		if ( isset( self::$persisted[ $key ] ) ) {
			return;
		}
		self::$persisted[ $key ] = true;
		self::persist( $post_id, $element_id, $settings );

		$index_key = self::index_option_key( $post_id );
		$ids       = get_option( $index_key, [] );
		$ids       = is_array( $ids ) ? array_values( array_filter( $ids, 'is_string' ) ) : [];
		if ( ! in_array( $element_id, $ids, true ) ) {
			$ids[] = $element_id;
			update_option( $index_key, $ids, false );
		}
	}

	/** @param mixed $document Elementor document instance. */
	public static function on_document_save( $document, $data ): void {
		if ( ! is_object( $document ) || ! method_exists( $document, 'get_main_id' ) ) {
			return;
		}

		$post_id = (int) $document->get_main_id();
		if ( $post_id <= 0 || isset( self::$syncing_posts[ $post_id ] ) ) {
			return;
		}

		if ( is_array( $data ) && array_key_exists( 'elements', $data ) ) {
			if ( ! is_array( $data['elements'] ) ) {
				return;
			}

			$elements = $data['elements'];
		} else {
			// Never call Document::get_elements_data() from inside after_save. On a
			// new/empty document that method can call convert_to_elementor()->save(),
			// re-entering this hook until PHP exhausts its memory limit.
			$stored = get_post_meta( $post_id, '_elementor_data', true );
			if ( is_string( $stored ) ) {
				$stored = json_decode( $stored, true );
			}

			$elements = is_array( $stored ) ? $stored : [];
		}

		self::$syncing_posts[ $post_id ] = true;
		try {
			// Empty data is authoritative: it removes fallback options for Comment
			// Dock elements that were deleted from the document.
			self::persist_elements( $post_id, $elements );
		} finally {
			unset( self::$syncing_posts[ $post_id ] );
		}
	}

	private static function persist_elements( int $post_id, array $elements ): void {
		$found = [];
		self::collect_comment_dock_elements( $elements, $post_id, $found );

		foreach ( $found as $element_id => $settings ) {
			self::persist( $post_id, $element_id, $settings );
		}

		$previous = get_option( self::index_option_key( $post_id ), [] );
		if ( ! is_array( $previous ) ) {
			$previous = [];
		}

		$current_ids = array_keys( $found );
		foreach ( $previous as $element_id ) {
			if ( ! in_array( $element_id, $current_ids, true ) ) {
				delete_option( self::option_key( $post_id, (string) $element_id ) );
			}
		}

		// Index enables precise deletion without wildcard option scans.
		update_option( self::index_option_key( $post_id ), $current_ids, false );
	}

	/** @param array<string,array> $found */
	private static function collect_comment_dock_elements( array $elements, int $post_id, array &$found ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ( $element['widgetType'] ?? '' ) === self::WIDGET_TYPE ) {
				$element_id = (string) ( $element['id'] ?? '' );
				$settings   = is_array( $element['settings'] ?? null ) ? $element['settings'] : [];
				if ( '' !== $element_id ) {
					$found[ $element_id ] = $settings;
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				self::collect_comment_dock_elements( $element['elements'], $post_id, $found );
			}
		}
	}
}
