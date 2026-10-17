<?php

declare( strict_types=1 );

namespace ApeironKit\Support;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Server-rendered catalogues retain Dynamic Tag and shared-template context. */
final class DaftarHadiahCatalog {
	private const META_PREFIX = '_apeiron_daftar_hadiah_catalog_';

	public static function website_id(): string {
		return 'site-' . substr( hash( 'sha256', home_url( '/' ) . '|' . get_current_blog_id() ), 0, 32 );
	}

	public static function invitation_title( int $post_id ): string {
		return sanitize_text_field( html_entity_decode( (string) get_the_title( $post_id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/** Server integration only; unresolved ownership stays null. @return string|null|WP_Error */
	public static function owner_id( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || in_array( $post->post_type, [ 'elementor_library', 'revision', 'attachment' ], true ) ) {
			return new WP_Error( 'invalid_target', __( 'Post undangan untuk pemetaan pemilik tidak valid.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}
		$owner_id = apply_filters( 'apeiron_daftar_hadiah_owner_id', null, $post_id );
		if ( null === $owner_id || '' === $owner_id ) { return null; }
		if ( is_int( $owner_id ) && $owner_id > 0 ) { return (string) $owner_id; }
		if ( is_string( $owner_id ) && preg_match( '/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/D', $owner_id ) ) { return $owner_id; }
		return new WP_Error( 'invalid_owner', __( 'Pemetaan pemilik undangan tidak valid. Periksa integrasi Daftar Hadiah pada server WordPress.', 'apeiron-kit' ), [ 'status' => 422 ] );
	}

	public static function token( array $identity ): string {
		return wp_hash( 'apeiron-daftar-hadiah|' . self::website_id() . '|' . $identity['invitation_id'] . '|' . $identity['registry_id'] );
	}

	public static function nonce_action( array $identity ): string {
		return 'apeiron_daftar_hadiah|' . $identity['invitation_id'] . '|' . $identity['registry_id'];
	}

	private static function meta_key( string $registry_id ): string {
		return self::META_PREFIX . md5( $registry_id );
	}

	private static function catalog_url( string $url ): string {
		$url = esc_url_raw( $url, [ 'http', 'https' ] );
		$parts = wp_parse_url( $url );
		return is_array( $parts ) && in_array( $parts['scheme'] ?? '', [ 'http', 'https' ], true ) && ! empty( $parts['host'] ) ? $url : '';
	}

	private static function invitation_hash( int $post_id ): string {
		$post = get_post( $post_id );
		return hash( 'sha256', (string) wp_json_encode( [
			get_post_meta( $post_id, '_elementor_data', true ),
			(string) ( $post->post_content ?? '' ),
		] ) );
	}

	private static function source( int $document_id, string $element_id ): ?array {
		$data = get_post_meta( $document_id, '_elementor_data', true );
		$data = is_string( $data ) ? json_decode( $data, true ) : $data;
		if ( ! is_array( $data ) ) { return null; }
		$stack = [ $data ];
		$matches = [];
		while ( $stack ) {
			foreach ( array_pop( $stack ) as $element ) {
				if ( ! is_array( $element ) ) { continue; }
				if ( 'apeiron-daftar-hadiah' === ( $element['widgetType'] ?? '' ) && $element_id === ( $element['id'] ?? '' ) ) {
					$matches[] = is_array( $element['settings'] ?? null ) ? $element['settings'] : [];
				}
				if ( is_array( $element['elements'] ?? null ) ) { $stack[] = $element['elements']; }
			}
		}
		return 1 === count( $matches ) ? $matches[0] : null;
	}

	/** No guest input or reservation is persisted here. */
	public static function remember( array $identity, array $gifts ): void {
		$post_id = (int) ( $identity['invitation_id'] ?? 0 );
		$post = get_post( $post_id );
		$document_id = (int) ( $identity['document_id'] ?? 0 );
		$element_id = (string) ( $identity['element_id'] ?? '' );
		$registry_id = (string) ( $identity['registry_id'] ?? '' );
		if ( ! $post || in_array( $post->post_type, [ 'elementor_library', 'revision', 'attachment' ], true )
			|| ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post_id ) )
			|| '' !== $post->post_password || $registry_id !== 'registry-' . $document_id . '-' . $element_id ) {
			return;
		}
		$source = self::source( $document_id, $element_id );
		if ( null === $source ) { return; }
		$source_hash = hash( 'sha256', (string) wp_json_encode( $source ) );
		$invitation_hash = self::invitation_hash( $post_id );
		$normalized = [];
		$seen = [];
		$invalid = count( $gifts ) > 200;
		foreach ( array_slice( $gifts, 0, 200 ) as $gift ) {
			$id = (string) ( $gift['gift_id'] ?? '' );
			$name = sanitize_text_field( (string) ( $gift['name'] ?? '' ) );
			$quantity = (int) ( $gift['initial_quantity'] ?? 0 );
			$description = sanitize_textarea_field( (string) ( $gift['description'] ?? '' ) );
			if ( ! preg_match( '/^gift-[A-Za-z0-9_-]{1,100}$/D', $id ) || isset( $seen[ $id ] ) || '' === $name || strlen( $name ) > 600 || $quantity < 0 || $quantity > 1000000 ) {
				$invalid = true;
			}
			if ( strlen( $description ) > 20000 ) { $invalid = true; }
			$seen[ $id ] = true;
			$normalized[] = [
				'gift_id' => $id, 'name' => $name,
				'description' => $description,
				'image_url' => self::catalog_url( (string) ( $gift['image_url'] ?? '' ) ),
				'purchase_url' => self::catalog_url( (string) ( $gift['purchase_url'] ?? '' ) ),
				'initial_quantity' => $quantity,
				'available_quantity' => max( 0, min( $quantity, (int) ( $gift['available_quantity'] ?? $quantity ) ) ),
				'availability' => 'unavailable' === ( $gift['availability'] ?? '' ) ? 'unavailable' : 'available',
			];
		}
		$hash = hash( 'sha256', (string) wp_json_encode( $normalized ) );
		$previous = get_post_meta( $post_id, self::meta_key( $registry_id ), true );
		if ( is_array( $previous ) && ( $previous['website_id'] ?? '' ) === self::website_id() && ( $previous['catalog_hash'] ?? '' ) === $hash
			&& ( $previous['source_hash'] ?? '' ) === $source_hash && ( $previous['invitation_hash'] ?? '' ) === $invitation_hash ) { return; }
		$version = sprintf( '%.0f', max( floor( microtime( true ) * 1000 ), (float) ( $previous['catalog_version'] ?? 0 ) + 1 ) );
		update_post_meta( $post_id, self::meta_key( $registry_id ), [
			'website_id' => self::website_id(), 'invitation_id' => $post_id,
			'registry_id' => $registry_id, 'document_id' => $document_id, 'element_id' => $element_id,
			'source_hash' => $source_hash, 'invitation_hash' => $invitation_hash, 'catalog_hash' => $hash, 'catalog_version' => $version,
			'invalid' => $invalid, 'gifts' => $normalized,
		] );
	}

	/** @return array|WP_Error */
	public static function load( int $post_id, string $registry_id ) {
		$record = get_post_meta( $post_id, self::meta_key( $registry_id ), true );
		if ( ! is_array( $record ) || self::website_id() !== ( $record['website_id'] ?? '' )
			|| $post_id !== ( $record['invitation_id'] ?? null ) || $registry_id !== ( $record['registry_id'] ?? '' ) ) {
			return new WP_Error( 'catalog_missing', __( 'Katalog belum tersedia. Buka halaman undangan untuk menyiapkannya, lalu coba kembali.', 'apeiron-kit' ), [ 'status' => 409 ] );
		}
		$source = self::source( (int) $record['document_id'], (string) $record['element_id'] );
		if ( null === $source || ! hash_equals( (string) $record['source_hash'], hash( 'sha256', (string) wp_json_encode( $source ) ) )
			|| ! hash_equals( (string) ( $record['invitation_hash'] ?? '' ), self::invitation_hash( $post_id ) ) ) {
			return new WP_Error( 'catalog_changed', __( 'Pengaturan hadiah berubah. Muat ulang halaman undangan sebelum melanjutkan.', 'apeiron-kit' ), [ 'status' => 409 ] );
		}
		if ( ! empty( $record['invalid'] ) || strlen( (string) wp_json_encode( $record['gifts'] ) ) > 240000 ) {
			return new WP_Error( 'invalid_catalog', __( 'Katalog hadiah tidak valid. Periksa nama, ID, jumlah, dan batas 200 hadiah per registry.', 'apeiron-kit' ), [ 'status' => 422 ] );
		}
		return $record;
	}

	public static function for_post( int $post_id ): array {
		$records = [];
		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			if ( 0 !== strpos( $key, self::META_PREFIX ) ) { continue; }
			$record = get_post_meta( $post_id, $key, true );
			if ( ! is_array( $record ) || ! is_string( $record['registry_id'] ?? null ) ) { continue; }
			$valid = self::load( $post_id, $record['registry_id'] );
			if ( is_wp_error( $valid ) ) { continue; }
			$records[] = [ 'registry_id' => $record['registry_id'], 'document_id' => $record['document_id'], 'element_id' => $record['element_id'], 'count' => count( $record['gifts'] ) ];
		}
		return $records;
	}

	public static function invitations(): array {
		$posts = get_posts( [
			'post_type' => array_values( array_diff( get_post_types(), [ 'elementor_library', 'revision', 'attachment' ] ) ),
			'post_status' => [ 'publish', 'private', 'future', 'draft', 'pending' ],
			'posts_per_page' => -1,
			'orderby' => 'title', 'order' => 'ASC',
			'meta_query' => [ [ 'key' => self::META_PREFIX, 'compare_key' => 'LIKE', 'compare' => 'EXISTS' ] ],
		] );
		$invitations = [];
		foreach ( $posts as $post ) {
			if ( '' !== $post->post_password || ! current_user_can( 'edit_post', $post->ID ) ) { continue; }
			$title = self::invitation_title( (int) $post->ID );
			$invitations[] = [ 'id' => (int) $post->ID, 'title' => '' !== $title ? $title : sprintf( __( 'Undangan #%d', 'apeiron-kit' ), $post->ID ) ];
		}
		return $invitations;
	}

	public static function payload( array $record ): array {
		return array_intersect_key( $record, array_flip( [ 'website_id', 'invitation_id', 'registry_id', 'document_id', 'element_id', 'catalog_hash', 'catalog_version', 'gifts' ] ) )
			+ [ 'invitation_title' => self::invitation_title( (int) $record['invitation_id'] ) ];
	}
}
