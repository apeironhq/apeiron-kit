<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\DaftarHadiah;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Builds the manual catalogue and the invitation-scoped integration identity. */
final class RenderContext {

	/**
	 * Template identity and invitation identity deliberately have different sources.
	 * A shared Theme Builder document must never become the invitation ID.
	 * These public identifiers are routing context, not authorization credentials.
	 */
	public static function identity( $document, string $element_id, bool $is_editor ): array {
		$document_id = is_object( $document ) && method_exists( $document, 'get_main_id' ) ? absint( $document->get_main_id() ) : absint( get_the_ID() );
		$invitation  = self::invitation_post( get_queried_object() );
		$source      = $invitation ? 'queried_post' : 'unresolved';

		if ( ! $invitation && $is_editor && is_object( $document ) && method_exists( $document, 'get_preview_as_query_args' ) ) {
			$args = $document->get_preview_as_query_args();
			$id   = is_array( $args ) ? absint( $args['p'] ?? $args['page_id'] ?? 0 ) : 0;
			$invitation = $id ? self::invitation_post( get_post( $id ) ) : null;
			$source     = $invitation ? 'template_preview' : 'unresolved';
		}

		if ( ! $invitation && ( is_singular() || $is_editor ) ) {
			$invitation = self::invitation_post( get_post() );
			$source     = $invitation ? 'current_post' : 'unresolved';
		}
		if ( ! $invitation && $is_editor && $document_id ) {
			$invitation = self::invitation_post( get_post( $document_id ) );
			$source     = $invitation ? 'document_post' : 'unresolved';
		}

		return [
			'website_id'     => 'site-' . substr( hash( 'sha256', home_url( '/' ) . '|' . get_current_blog_id() ), 0, 32 ),
			'invitation_id'  => $invitation ? (int) $invitation->ID : 0,
			'registry_id'    => $document_id && preg_match( '/^[a-zA-Z0-9_-]+$/D', $element_id ) ? 'registry-' . $document_id . '-' . $element_id : '',
			'document_id'    => $document_id,
			'element_id'     => $element_id,
			'context_source' => $source,
		];
	}

	private static function invitation_post( $post ): ?\WP_Post {
		return $post instanceof \WP_Post && $post->ID > 0 && ! in_array( $post->post_type, [ 'elementor_library', 'revision', 'attachment' ], true ) ? $post : null;
	}

	public static function gifts( array $settings ): array {
		$rows   = is_array( $settings['gifts'] ?? null ) ? array_values( array_filter( $settings['gifts'], 'is_array' ) ) : [];
		$counts = [];
		foreach ( $rows as $row ) {
			$id = self::row_id( $row );
			$counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
		}
		$limit = max( 0, (int) ( $settings['items_limit'] ?? 0 ) );
		if ( $limit > 0 ) {
			$rows = array_slice( $rows, 0, $limit );
		}

		$gifts = [];
		foreach ( $rows as $row ) {
			$id       = self::row_id( $row );
			$images   = self::images( $row );
			$link     = is_array( $row['purchase_url'] ?? null ) ? $row['purchase_url'] : [];
			$quantity = max( 0, (int) ( $row['quantity'] ?? 1 ) );
			$available_quantity = is_numeric( $row['available_quantity'] ?? null ) ? max( 0, min( $quantity, (int) $row['available_quantity'] ) ) : $quantity;
			if ( 'unavailable' === ( $row['availability'] ?? 'available' ) ) {
				$available_quantity = 0;
			}
			$name     = self::text( $row['name'] ?? '' );
			$gifts[]  = [
				// Elementor persists _id and regenerates it on native repeater duplication.
				// Invalid/duplicate IDs fail closed; never derive an ID from a name/index.
				'gift_id'          => '' !== $id && 1 === $counts[ $id ] ? 'gift-' . $id : '',
				'row_id'           => $id,
				'name'             => $name,
				'category'         => self::text( $row['category'] ?? '' ),
				'description'      => self::text( $row['description'] ?? '' ),
				'initial_quantity' => $quantity,
				'available_quantity' => $available_quantity,
				'availability'     => $available_quantity > 0 ? 'available' : 'unavailable',
				'image_url'        => $images[0]['url'] ?? '',
				'image_alt'        => $images[0]['alt'] ?? $name,
				'images'           => $images,
				'purchase_url'     => esc_url_raw( self::text( $link['url'] ?? '' ), [ 'http', 'https' ] ),
				'purchase_label'   => self::text( $row['purchase_label'] ?? '' ),
				'purchase_new_tab' => ! empty( $link['is_external'] ),
				'purchase_nofollow' => ! empty( $link['nofollow'] ),
			];
		}

		return $gifts;
	}

	private static function images( array $row ): array {
		$gallery = is_array( $row['gallery'] ?? null ) ? $row['gallery'] : [];
		$primary = is_array( $row['image'] ?? null ) ? $row['image'] : [];
		$images  = [];
		$seen    = [];
		foreach ( array_merge( [ $primary ], $gallery ) as $image ) {
			if ( ! is_array( $image ) ) {
				continue;
			}
			$id  = absint( $image['id'] ?? 0 );
			$url = esc_url_raw( self::text( $image['url'] ?? '' ) ?: ( $id ? (string) wp_get_attachment_image_url( $id, 'large' ) : '' ), [ 'http', 'https' ] );
			if ( '' === $url || isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;
			$images[] = [
				'url' => $url,
				'alt' => $id ? self::text( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ?: self::text( $row['name'] ?? '' ) : self::text( $row['name'] ?? '' ),
			];
		}
		return $images;
	}

	private static function row_id( array $row ): string {
		$id = self::text( $row['_id'] ?? '' );
		return preg_match( '/^[a-zA-Z0-9_-]+$/D', $id ) ? $id : '';
	}

	private static function text( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}
