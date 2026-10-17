<?php

declare( strict_types=1 );

namespace ApeironKit\Rest;

use ApeironKit\Core\LicenseManager;
use ApeironKit\Support\DaftarHadiahCatalog;
use ApeironKit\Support\DaftarHadiahGateway;
use ApeironKit\Support\DaftarHadiahSettings;
use ApeironKit\Support\WidgetRegistry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DaftarHadiahController {
	public function register_routes(): void {
		foreach ( [ 'availability' => 'availability', 'reserve' => 'reserve' ] as $route => $method ) {
			register_rest_route( 'apeiron-kit/v1', '/daftar-hadiah/' . $route, [
				'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, $method ],
				'permission_callback' => [ $this, 'verify_request' ],
			] );
		}
	}

	public function verify_request( WP_REST_Request $request ) {
		$context = $this->context( $request );
		return is_wp_error( $context ) ? $context : true;
	}

	/** @return array|WP_Error */
	private function context( WP_REST_Request $request ) {
		if ( strlen( $request->get_body() ) > 12000 ) { return $this->invalid_form(); }
		if ( in_array( 'daftar_hadiah', WidgetRegistry::disabled_slugs(), true ) || ! LicenseManager::instance()->is_valid() ) {
			return new WP_Error( 'widget_disabled', __( 'Daftar Hadiah tidak tersedia.', 'apeiron-kit' ), [ 'status' => 403 ] );
		}
		$id = $request->get_param( 'invitation_id' );
		$document_id = $request->get_param( 'document_id' );
		$registry_id = $request->get_param( 'registry_id' );
		$element_id = $request->get_param( 'element_id' );
		$token = $request->get_param( 'target_token' );
		$nonce = $request->get_header( 'X-Apeiron-Nonce' );
		if ( ! is_int( $id ) || $id < 1 || ! is_int( $document_id ) || $document_id < 1 || ! is_string( $registry_id ) || strlen( $registry_id ) > 150
			|| ! is_string( $element_id ) || ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $element_id )
			|| $registry_id !== 'registry-' . $document_id . '-' . $element_id || ! is_string( $token ) || ! is_string( $nonce )
			|| DaftarHadiahCatalog::website_id() !== $request->get_param( 'website_id' ) ) {
			return $this->invalid_target();
		}
		$identity = [ 'invitation_id' => $id, 'registry_id' => $registry_id ];
		if ( ! wp_verify_nonce( $nonce, DaftarHadiahCatalog::nonce_action( $identity ) ) || ! hash_equals( DaftarHadiahCatalog::token( $identity ), $token ) ) {
			return $this->invalid_target();
		}
		$post = get_post( $id );
		if ( ! $post || in_array( $post->post_type, [ 'elementor_library', 'revision', 'attachment' ], true ) || '' !== $post->post_password
			|| ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $id ) ) ) {
			return $this->invalid_target();
		}
		$record = DaftarHadiahCatalog::load( $id, $registry_id );
		if ( is_wp_error( $record ) ) { return $record; }
		return $record['document_id'] === $document_id && $record['element_id'] === $element_id ? $record : $this->invalid_target();
	}

	private function invalid_target(): WP_Error {
		return new WP_Error( 'invalid_target', __( 'Identitas undangan tidak valid atau sesi berakhir. Muat ulang halaman undangan.', 'apeiron-kit' ), [ 'status' => 403 ] );
	}

	private function check_ready( array $record, string $kind ): ?WP_Error {
		if ( ! DaftarHadiahSettings::ready() ) {
			return new WP_Error( 'not_configured', __( 'Fitur pengiriman reservasi belum dikonfigurasi. Pilihan Anda belum dikirim atau disimpan.', 'apeiron-kit' ), [ 'status' => 503 ] );
		}
		$ip = is_string( $_SERVER['REMOTE_ADDR'] ?? null ) ? $_SERVER['REMOTE_ADDR'] : '';
		$key = 'apeiron_dh_rate_' . md5( $kind . '|' . wp_hash( $ip ) . '|' . $record['invitation_id'] );
		$count = (int) get_transient( $key );
		if ( $count >= ( 'reserve' === $kind ? 5 : 30 ) ) {
			return new WP_Error( 'rate_limit', __( 'Terlalu banyak permintaan. Silakan coba lagi sebentar.', 'apeiron-kit' ), [ 'status' => 429 ] );
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return null;
	}

	public function availability( WP_REST_Request $request ) {
		$record = $this->context( $request );
		if ( is_wp_error( $record ) ) { return $record; }
		$error = $this->check_ready( $record, 'availability' );
		if ( $error ) { return $error; }
		$data = DaftarHadiahGateway::request( 'availability', DaftarHadiahCatalog::payload( $record ) );
		if ( is_wp_error( $data ) ) { return $data; }
		$gifts = DaftarHadiahGateway::availability( $data, $record );
		return is_wp_error( $gifts ) ? $gifts : $this->response( [ 'status' => 'synced', 'scope' => $data['scope'], 'gifts' => $gifts ] );
	}

	public function reserve( WP_REST_Request $request ) {
		$record = $this->context( $request );
		if ( is_wp_error( $record ) ) { return $record; }
		$error = $this->check_ready( $record, 'reserve' );
		if ( $error ) { return $error; }
		$guest = $request->get_param( 'guest' );
		$message = $request->get_param( 'message' );
		$request_id = $request->get_param( 'request_id' );
		$gift_id = $request->get_param( 'gift_id' );
		if ( ! is_array( $guest ) || ! is_string( $guest['name'] ?? null ) || ! is_string( $guest['whatsapp'] ?? null )
			|| ! is_string( $message ) || ! is_string( $request_id ) || ! preg_match( '/^[a-f0-9]{32}$/D', $request_id )
			|| ! is_string( $gift_id ) || $request->get_param( 'quantity' ) !== 1 || strlen( $guest['whatsapp'] ) > 24 || strlen( $guest['name'] ) > 1200 || strlen( $message ) > 4000 ) {
			return $this->invalid_form();
		}
		$name = trim( sanitize_text_field( $guest['name'] ) );
		$phone = preg_match( '/^\+?[0-9\s().-]+$/D', $guest['whatsapp'] ) ? preg_replace( '/[\s().-]/', '', ltrim( $guest['whatsapp'], '+' ) ) : '';
		if ( 0 === strpos( $phone, '0' ) ) { $phone = '62' . substr( $phone, 1 ); }
		$message = trim( sanitize_textarea_field( $message ) );
		$length = static function ( string $text ): int { return function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text ); };
		if ( '' === $name || $length( $name ) > 100 || $length( $message ) > 1000 || ! preg_match( '/^[1-9][0-9]{7,14}$/D', $phone ) ) { return $this->invalid_form(); }
		$gifts = array_column( $record['gifts'], null, 'gift_id' );
		if ( ! isset( $gifts[ $gift_id ] ) || 'available' !== $gifts[ $gift_id ]['availability'] || $gifts[ $gift_id ]['available_quantity'] < 1 ) {
			return DaftarHadiahGateway::error( 'gift_unavailable' );
		}
		$payload = DaftarHadiahCatalog::payload( $record ) + [
			'gift_id' => $gift_id, 'quantity' => 1, 'guest' => [ 'name' => $name, 'whatsapp' => $phone ], 'message' => $message,
			'request_id' => hash_hmac( 'sha256', $record['website_id'] . '|' . $record['invitation_id'] . '|' . $record['registry_id'] . '|' . $request_id, wp_salt( 'nonce' ) ),
		];
		$data = DaftarHadiahGateway::request( 'reserve', $payload );
		if ( is_wp_error( $data ) ) { return $data; }
		$stock = DaftarHadiahGateway::availability( $data, $record );
		if ( is_wp_error( $stock ) ) { return $stock; }
		if ( 'confirmed' !== ( $data['status'] ?? '' ) || $gift_id !== ( $data['gift_id'] ?? '' ) || 1 !== ( $data['quantity'] ?? null )
			|| ! is_string( $data['reservation_id'] ?? null ) || ! preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $data['reservation_id'] )
			|| ! is_string( $data['timestamp'] ?? null ) || ! preg_match( '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d{3})?Z$/D', $data['timestamp'] ) ) {
			return DaftarHadiahGateway::error( 'invalid_response' );
		}
		return $this->response( [
			'status' => 'confirmed', 'reservation_id' => $data['reservation_id'], 'gift_id' => $gift_id, 'quantity' => 1,
			'timestamp' => $data['timestamp'], 'gifts' => $stock,
			'message' => ! empty( $data['already_reserved'] ) ? __( 'Pilihan hadiah Anda sudah tercatat sebelumnya.', 'apeiron-kit' ) : __( 'Pilihan hadiah berhasil dikonfirmasi.', 'apeiron-kit' ),
		] );
	}

	private function invalid_form(): WP_Error {
		return new WP_Error( 'invalid_request', __( 'Periksa nama tamu, nomor WhatsApp, dan pesan yang Anda masukkan.', 'apeiron-kit' ), [ 'status' => 422 ] );
	}

	private function response( array $data ) {
		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', 'no-store, max-age=0' );
		return $response;
	}
}
