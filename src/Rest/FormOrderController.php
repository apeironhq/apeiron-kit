<?php

namespace ApeironKit\Rest;

use ApeironKit\Core\LicenseManager;
use ApeironKit\Elementor\Widgets\FormOrder\FieldSchema;
use ApeironKit\Support\FormOrderSettings;
use ApeironKit\Support\WidgetRegistry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FormOrderController {
	public function register_routes(): void {
		register_rest_route( 'apeiron-kit/v1', '/form-order', [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [ $this, 'submit' ],
			'permission_callback' => [ $this, 'verify_nonce' ],
		] );
	}

	public function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-Apeiron-Nonce' );
		return is_string( $nonce ) && (bool) wp_verify_nonce( $nonce, 'apeiron_form_order' );
	}

	public function submit( WP_REST_Request $request ) {
		if ( in_array( 'form_order', WidgetRegistry::disabled_slugs(), true ) || ! LicenseManager::instance()->is_valid() ) {
			return $this->error( 'widget_disabled', __( 'Form Order tidak tersedia.', 'apeiron-kit' ), 403 );
		}
		$settings = FormOrderSettings::get();
		$use_whatsapp = in_array( $settings['delivery_target'], [ 'whatsapp', 'both' ], true );
		$use_sheet = in_array( $settings['delivery_target'], [ 'spreadsheet', 'both' ], true );
		if ( ( $use_whatsapp && 'yes' !== $settings['whatsapp_enabled'] ) || ( $use_sheet && 'yes' !== $settings['spreadsheet_enabled'] ) ) {
			return $this->error( 'not_configured', __( 'Form Order belum dikonfigurasi.', 'apeiron-kit' ), 503 );
		}
		$post_id = absint( $request->get_param( 'post_id' ) );
		$document_id = absint( $request->get_param( 'document_id' ) );
		$element_id = sanitize_text_field( (string) $request->get_param( 'element_id' ) );
		$token = (string) $request->get_param( 'target_token' );
		$post = get_post( $post_id );
		$widget_settings = $this->widget_settings( $document_id, $element_id );
		if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password
			|| ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $element_id )
			|| ! hash_equals( wp_hash( 'apeiron-form-order|' . $post_id . '|' . $document_id . '|' . $element_id ), $token )
			|| null === $widget_settings ) {
			return $this->error( 'invalid_target', __( 'Form Order tidak ditemukan pada halaman ini.', 'apeiron-kit' ), 403 );
		}
		$honeypot = $request->get_param( 'website' );
		if ( ! is_string( $honeypot ) || '' !== trim( $honeypot ) ) {
			return $this->error( 'invalid_request', __( 'Permintaan tidak valid.', 'apeiron-kit' ), 422 );
		}
		$request_id = $request->get_header( 'X-Apeiron-Request-ID' );
		if ( is_string( $request_id ) && '' !== $request_id && ! preg_match( '/^[a-f0-9]{32}$/D', $request_id ) ) {
			return $this->error( 'invalid_request', __( 'Permintaan tidak valid.', 'apeiron-kit' ), 422 );
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$key = 'apeiron_form_order_rate_' . md5( $ip . '|' . $post_id );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return $this->error( 'rate_limit', __( 'Terlalu banyak permintaan. Silakan coba lagi nanti.', 'apeiron-kit' ), 429 );
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		$raw = $request->get_param( 'fields' );
		if ( ! is_array( $raw ) || count( $raw ) > 200 ) {
			return $this->error( 'invalid_fields', __( 'Data formulir tidak valid.', 'apeiron-kit' ), 422 );
		}
		$fields = [];
		$details = [];
		$labels = [];
		$label_contexts = [];
		foreach ( FieldSchema::resolve( $widget_settings ) as $step ) {
			if ( 'confirmation' === $step['kind'] ) {
				continue;
			}
			$step_details = [];
			$step_has_whatsapp_field = false;
			$step_fields = FieldSchema::with_extra_accounts( $step['fields'], $raw );
			if ( ! empty( $step['is_story'] ) ) {
				$step_fields = FieldSchema::with_extra_journeys( $step_fields, $raw );
			}
			foreach ( FieldSchema::layout_groups( $step_fields ) as $group ) {
				foreach ( $group['fields'] as $name => $definition ) {
					if ( 'heading' === $definition['type'] ) { continue; }
					$labels[ $name ] = $definition['label'];
					$label_contexts[ $name ] = $group['title'] ?: $step['title'];
				}
			}
			foreach ( $step_fields as $name => $definition ) {
				if ( ! empty( $step['is_story'] ) && ! FieldSchema::story_group_has_content( $name, $raw ) ) {
					continue;
				}
				if ( in_array( $name, [ 'ngunduh_date', 'ngunduh_time', 'ngunduh_address', 'ngunduh_maps' ], true )
					&& ! array_key_exists( 'ngunduh_date', $raw ) && ! array_key_exists( 'ngunduh_time', $raw )
					&& ! array_key_exists( 'ngunduh_address', $raw ) && ! array_key_exists( 'ngunduh_maps', $raw ) ) {
					continue;
				}
				if ( 'heading' === $definition['type'] ) {
					$step_details[] = $definition['label'];
					continue;
				}
			$value = $raw[ $name ] ?? '';
			if ( 'checkbox' === $definition['type'] ) {
				if ( ! is_array( $value ) || count( $value ) > 50 ) {
					return $this->error( 'invalid_fields', __( 'Data formulir tidak valid.', 'apeiron-kit' ), 422 );
				}
				$values = [];
				foreach ( $value as $option ) {
					if ( ! is_string( $option ) || strlen( $option ) > 512 ) {
						return $this->error( 'invalid_fields', __( 'Data formulir tidak valid.', 'apeiron-kit' ), 422 );
					}
					$option = sanitize_text_field( $option );
					if ( ! array_key_exists( $option, $definition['options'] ) || in_array( $option, $values, true ) ) {
						return $this->error( 'invalid_field', sprintf( __( 'Format tidak valid: %s', 'apeiron-kit' ), $definition['label'] ), 422 );
					}
					$values[] = $option;
				}
				$value = $values;
			} elseif ( ! is_string( $value ) || strlen( $value ) > ( 'textarea' === $definition['type'] ? 4000 : 1024 ) ) {
				return $this->error( 'invalid_fields', __( 'Data formulir terlalu panjang atau tidak valid.', 'apeiron-kit' ), 422 );
			} else {
				$value = 'textarea' === $definition['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
				$value = trim( $value );
			}
			if ( $definition['required'] && ( '' === $value || [] === $value ) ) {
				return $this->error( 'required_field', sprintf( __( 'Field wajib diisi: %s', 'apeiron-kit' ), $definition['label'] ), 422 );
			}
			if ( is_string( $value ) && '' !== $value && ! $this->valid_field( $name, $definition, $value ) ) {
				return $this->error( 'invalid_field', sprintf( __( 'Format tidak valid: %s', 'apeiron-kit' ), $definition['label'] ), 422 );
			}
			if ( ! empty( $step['is_story'] ) && ( '' === $value || [] === $value ) ) {
				continue;
			}
			$fields[ $name ] = $value;
			$display = is_array( $value ) ? $value : ( '' === $value ? [] : [ $value ] );
			if ( in_array( $definition['type'], [ 'select', 'radio', 'checkbox' ], true ) ) {
				$display = array_map( static function ( $option ) use ( $definition ) { return $definition['options'][ $option ] ?? $option; }, $display );
			}
			$step_has_whatsapp_field = true;
			$step_details[] = $definition['label'] . ': ' . ( $display ? implode( ', ', $display ) : '-' );
			}
			if ( $step_has_whatsapp_field ) {
				$details[] = '*' . $step['title'] . "*\n" . implode( "\n", $step_details );
			}
		}
		$label_counts = array_count_values( $labels );
		$used_labels = [];
		foreach ( $labels as $name => $label ) {
			$context = str_replace( [ 'Mempelai Pria', 'Mempelai Wanita' ], [ 'Pria', 'Wanita' ], $label_contexts[ $name ] );
			$title = $label_counts[ $label ] > 1 ? $context . ': ' . $label : $label;
			$used_labels[ $title ] = ( $used_labels[ $title ] ?? 0 ) + 1;
			$labels[ $name ] = $used_labels[ $title ] > 1 ? $title . ' (' . $used_labels[ $title ] . ')' : $title;
		}
		$send_sheet = $use_sheet && ! empty( $fields );
		$send_whatsapp = $use_whatsapp && ! empty( $details );
		if ( ! $send_sheet && ! $send_whatsapp ) {
			return $this->error( 'no_delivery_target', __( 'Tidak ada tujuan pengiriman aktif untuk field Form Order ini.', 'apeiron-kit' ), 422 );
		}
		$sheet_failure = null;
		$sheet_version = null;
		$submission_id = is_string( $request_id ) && '' !== $request_id
			? hash( 'sha256', wp_hash( 'apeiron-form-order-request|' . $post_id . '|' . $document_id . '|' . $element_id . '|' . $request_id . '|' . wp_json_encode( $fields ) ) )
			: '';
		if ( $send_sheet && ! FormOrderSettings::send_to_sheet( $fields, 'order', $sheet_failure, $labels, $sheet_version, $submission_id ) ) {
			return $this->error( 'sheet_failed', __( 'Gagal mengirim ke Google Spreadsheet. Data belum diteruskan ke WhatsApp; coba lagi atau hubungi pengelola.', 'apeiron-kit' ), 502 );
		}
		$result = [ 'message' => $settings['success_message'] ?? __( 'Pesanan berhasil diproses.', 'apeiron-kit' ), 'whatsapp_url' => '' ];
		if ( $send_whatsapp ) {
			$message = str_replace( '{details}', "\n" . implode( "\n\n────────────\n\n", $details ), $settings['whatsapp_template'] );
			$result['whatsapp_url'] = 'https://api.whatsapp.com/send?phone=' . $settings['whatsapp_phone'] . '&text=' . rawurlencode( $message );
			$result['whatsapp_behavior'] = $settings['whatsapp_behavior'];
		}
		return rest_ensure_response( $result );
	}

	private function widget_settings( int $post_id, string $element_id ): ?array {
		$data = get_post_meta( $post_id, '_elementor_data', true );
		$elements = is_string( $data ) ? json_decode( $data, true ) : $data;
		if ( ! is_array( $elements ) ) { return null; }
		$stack = [ $elements ];
		while ( $stack ) {
			foreach ( array_pop( $stack ) as $element ) {
				if ( ! is_array( $element ) ) { continue; }
				if ( ( $element['widgetType'] ?? '' ) === 'apeiron-form-order' && ( $element['id'] ?? '' ) === $element_id ) {
					return is_array( $element['settings'] ?? null ) ? $element['settings'] : [];
				}
				if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) { $stack[] = $element['elements']; }
			}
		}
		return null;
	}

	private function valid_field( string $name, array $definition, string $value ): bool {
		$type = $definition['type'];
		if ( 'email' === $type ) { return (bool) is_email( $value ) && strlen( $value ) <= 254; }
		if ( 'url' === $type ) {
			$host = wp_parse_url( $value, PHP_URL_HOST );
			if ( ! wp_http_validate_url( $value ) || ! is_string( $host ) ) { return false; }
			if ( 'photo_drive_url' === $name ) { return in_array( strtolower( $host ), [ 'drive.google.com', 'photos.google.com' ], true ); }
			if ( in_array( $name, [ 'akad_maps', 'reception_maps', 'ngunduh_maps' ], true ) ) {
				return in_array( strtolower( $host ), [ 'google.com', 'www.google.com', 'maps.google.com', 'maps.app.goo.gl' ], true );
			}
			return true;
		}
		if ( in_array( $type, [ 'select', 'radio' ], true ) ) { return array_key_exists( $value, $definition['options'] ); }
		if ( 'number' === $type ) {
			if ( in_array( $name, [ 'groom_child_order', 'bride_child_order' ], true ) ) {
				return ctype_digit( $value ) && (int) $value >= 1 && (int) $value <= 30;
			}
			return is_numeric( $value ) && (float) $value >= 1 && (float) $value <= 999999999;
		}
		if ( 'date' === $type ) {
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
			return $date && $date->format( 'Y-m-d' ) === $value;
		}
		if ( 'time' === $type ) { return (bool) preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value ); }
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
		return $length <= ( 'textarea' === $type ? 1000 : 255 );
	}

	private function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
