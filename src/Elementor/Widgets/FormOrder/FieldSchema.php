<?php

namespace ApeironKit\Elementor\Widgets\FormOrder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FieldSchema {
	/** @return array<int,array{title:string,fields:array<string,array{label:string,type:string,required:bool}>}> */
	public static function steps(): array {
		return [
			[ 'title' => __( 'Pemesan Undangan', 'apeiron-kit' ), 'fields' => [
				'customer_name' => self::field( __( 'Nama Pemesan', 'apeiron-kit' ), 'text', true ),
				'customer_email' => self::field( __( 'Email', 'apeiron-kit' ), 'email', true ),
				'customer_phone' => self::field( __( 'No. WhatsApp Pemesan', 'apeiron-kit' ), 'tel', true ),
				'invitation_side' => self::field( __( 'Undangan dari pihak', 'apeiron-kit' ), 'select', true ),
			] ],
			[ 'title' => __( 'Mempelai Pria', 'apeiron-kit' ), 'fields' => self::person_fields( 'groom' ) ],
			[ 'title' => __( 'Mempelai Wanita', 'apeiron-kit' ), 'fields' => self::person_fields( 'bride' ) ],
			[ 'title' => __( 'Detail Acara', 'apeiron-kit' ), 'fields' => [
				'akad_date' => self::field( __( 'Akad — Hari/Tanggal', 'apeiron-kit' ), 'date' ),
				'akad_time' => self::field( __( 'Akad — Waktu', 'apeiron-kit' ), 'time' ),
				'akad_address' => self::field( __( 'Akad — Alamat Detail', 'apeiron-kit' ), 'textarea' ),
				'akad_maps' => self::field( __( 'Akad — Link Google Maps', 'apeiron-kit' ), 'url' ),
				'reception_date' => self::field( __( 'Resepsi — Hari/Tanggal', 'apeiron-kit' ), 'date' ),
				'reception_time' => self::field( __( 'Resepsi — Waktu', 'apeiron-kit' ), 'time' ),
				'reception_address' => self::field( __( 'Resepsi — Alamat Detail', 'apeiron-kit' ), 'textarea' ),
				'reception_maps' => self::field( __( 'Resepsi — Link Google Maps', 'apeiron-kit' ), 'url' ),
				'ngunduh_date' => self::field( __( 'Ngunduh Mantu — Hari/Tanggal', 'apeiron-kit' ), 'date' ),
				'ngunduh_time' => self::field( __( 'Ngunduh Mantu — Waktu', 'apeiron-kit' ), 'time' ),
				'ngunduh_address' => self::field( __( 'Ngunduh Mantu — Alamat Detail', 'apeiron-kit' ), 'textarea' ),
				'ngunduh_maps' => self::field( __( 'Ngunduh Mantu — Link Google Maps', 'apeiron-kit' ), 'url' ),
			] ],
			[ 'title' => __( 'Cerita Cinta', 'apeiron-kit' ), 'fields' => [
				'love_meeting_title' => self::field( __( 'Judul Perjalanan', 'apeiron-kit' ), 'text' ),
				'love_meeting_date' => self::field( __( 'Tanggal Pertama Bertemu', 'apeiron-kit' ), 'date' ),
				'love_meeting_story' => self::field( __( 'Cerita Pertama Bertemu', 'apeiron-kit' ), 'textarea' ),
				'love_engagement_title' => self::field( __( 'Judul Perjalanan', 'apeiron-kit' ), 'text' ),
				'love_engagement_date' => self::field( __( 'Tanggal Pertunangan', 'apeiron-kit' ), 'date' ),
				'love_engagement_story' => self::field( __( 'Cerita Pertunangan', 'apeiron-kit' ), 'textarea' ),
				'love_journey_title' => self::field( __( 'Judul Perjalanan', 'apeiron-kit' ), 'text' ),
				'love_journey_date' => self::field( __( 'Tanggal Menuju Pernikahan', 'apeiron-kit' ), 'date' ),
				'love_journey_story' => self::field( __( 'Cerita Menuju Pernikahan', 'apeiron-kit' ), 'textarea' ),
			] ],
			[ 'title' => __( 'Data Tambahan', 'apeiron-kit' ), 'fields' => [
				'bank_name_1' => self::field( __( 'Nama Bank 1', 'apeiron-kit' ), 'text' ),
				'bank_account_1' => self::field( __( 'Nomor Rekening 1', 'apeiron-kit' ), 'text' ),
				'bank_owner_1' => self::field( __( 'Nama Pemilik 1', 'apeiron-kit' ), 'text' ),
				'bank_name_2' => self::field( __( 'Nama Bank 2', 'apeiron-kit' ), 'text' ),
				'bank_account_2' => self::field( __( 'Nomor Rekening 2', 'apeiron-kit' ), 'text' ),
				'bank_owner_2' => self::field( __( 'Nama Pemilik 2', 'apeiron-kit' ), 'text' ),
				'photo_drive_url' => self::field( __( 'Link Google Drive Foto', 'apeiron-kit' ), 'url' ),
				'music_title' => self::field( __( 'Musik/Judul Lagu', 'apeiron-kit' ), 'text' ),
			] ],
		];
	}

	/** @return array<string,array{label:string,type:string,required:bool}> */
	public static function fields(): array {
		$fields = [];
		foreach ( self::steps() as $step ) {
			$fields = array_merge( $fields, $step['fields'] );
		}
		return $fields;
	}

	/** Default Elementor repeater rows; legacy IDs remain the submitted field keys. */
	public static function defaults(): array {
		$steps = [];
		foreach ( self::steps() as $index => $step ) {
			$fields = [];
			foreach ( $step['fields'] as $key => $field ) {
				$fields[] = [
					'field_key' => $key,
					'label' => $field['label'],
					'type' => $field['type'],
					'required' => $field['required'] ? 'yes' : '',
					'options' => 'invitation_side' === $key ? "pria|Pria\nwanita|Wanita" : '',
					'placeholder' => 'invitation_side' === $key ? __( 'Pilih pihak', 'apeiron-kit' ) : ( preg_match( '/^love_(meeting|engagement|journey)_story$/D', $key ) ? __( 'Ceritakan momen ini...', 'apeiron-kit' ) : '' ),
				'default_value' => [
						'love_meeting_title' => __( 'Pertama Bertemu', 'apeiron-kit' ),
						'love_engagement_title' => __( 'Pertunangan', 'apeiron-kit' ),
						'love_journey_title' => __( 'Menuju Pernikahan', 'apeiron-kit' ),
					][ $key ] ?? '',
					'width' => preg_match( '/^bank_(name|account|owner)_[12]$/', $key ) ? '33' : ( 'textarea' === $field['type'] || preg_match( '/^(akad|reception|ngunduh)_maps$/', $key ) ? '100' : '50' ),
				];
			}
			$title = 0 === $index ? __( 'Pemesan', 'apeiron-kit' ) : ( 5 === $index ? __( 'Tambahan', 'apeiron-kit' ) : $step['title'] );
			$steps[] = [ 'title' => $title, 'enabled' => 'yes', 'kind' => 'fields', 'fields' => $fields ];
			if ( 4 === $index ) {
				$steps[ $index ]['step_key'] = 'love_story';
			}
		}
		$steps[] = [ 'title' => __( 'Konfirmasi', 'apeiron-kit' ), 'enabled' => 'yes', 'kind' => 'confirmation', 'fields' => [] ];
		return $steps;
	}

	/** Convert saved nested builder data to independent Elementor repeaters without writing user data. */
	public static function builder_rows( array $settings ): array {
		$rows = isset( $settings['order_steps'] ) && is_array( $settings['order_steps'] ) ? $settings['order_steps'] : self::defaults();
		$steps = [];
		$fields = [];
		$step_labels = [];
		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$key = sanitize_key( (string) ( $row['step_key'] ?? $row['_id'] ?? 'step_' . ( $index + 1 ) ) );
			$row['step_key'] = $key;
			$step_labels[ $key ] = self::text( $row['title'] ?? $key, 120 );
			foreach ( is_array( $row['fields'] ?? null ) ? $row['fields'] : [] as $field ) {
				if ( ! is_array( $field ) ) { continue; }
				$field['step_key'] = $key;
				$field['step_label'] = $step_labels[ $key ];
				$fields[] = $field;
			}
			unset( $row['fields'] );
			$steps[] = $row;
		}
		if ( isset( $settings['order_fields'] ) && is_array( $settings['order_fields'] ) ) {
			$fields = $settings['order_fields'];
		} elseif ( ! $fields && isset( $settings['order_steps'] ) ) {
			// Elementor can omit unchanged default repeater values from the saved document.
			$fields = self::builder_rows( [] )['fields'];
		}
		$previous_step = '';
		foreach ( $fields as &$field ) {
			if ( ! is_array( $field ) ) { continue; }
			$key = sanitize_key( (string) ( $field['step_key'] ?? '' ) );
			$field['step_label'] = $step_labels[ $key ] ?? $key;
			$field['group_header'] = '' !== $key && $key !== $previous_step ? 'yes' : '';
			$previous_step = $key;
		}
		unset( $field );
		return [ 'steps' => $steps, 'fields' => $fields ];
	}

	/** Group only the established order fields; custom fields retain their builder order. */
	public static function layout_groups( array $fields ): array {
		$titles = [
			'akad' => __( 'Akad', 'apeiron-kit' ),
			'reception' => __( 'Resepsi', 'apeiron-kit' ),
			'ngunduh' => __( 'Ngunduh Mantu', 'apeiron-kit' ),
			'love_meeting' => __( 'Pertama Bertemu', 'apeiron-kit' ),
			'love_engagement' => __( 'Pertunangan', 'apeiron-kit' ),
			'love_journey' => __( 'Menuju Pernikahan', 'apeiron-kit' ),
			'bank' => __( 'Rekening', 'apeiron-kit' ),
		];
		$groups = [];
		foreach ( $fields as $key => $field ) {
			$group = '';
			if ( preg_match( '/^(akad|reception|ngunduh)_(date|time|address|maps)$/D', $key, $match ) ) {
				$group = $match[1];
			} elseif ( preg_match( '/^(love_(?:meeting|engagement|journey))_(title|date|story)$/D', $key, $match ) ) {
				$group = $match[1];
			} elseif ( preg_match( '/^bank_(name|account|owner)_([12])$/D', $key, $match ) ) {
				$group = 'bank_' . $match[2];
			}
			$last = count( $groups ) - 1;
			if ( $last < 0 || $groups[ $last ]['key'] !== $group ) {
				$groups[] = [ 'key' => $group, 'title' => strpos( $group, 'bank_' ) === 0 ? sprintf( __( 'Rekening %d', 'apeiron-kit' ), (int) substr( $group, 5 ) ) : ( $titles[ $group ] ?? '' ), 'fields' => [] ];
				$last++;
			}
			$groups[ $last ]['fields'][ $key ] = $field;
		}
		return $groups;
	}

	/** Additional customer accounts are permitted only when the saved builder contains the bank field. */
	public static function with_extra_accounts( array $fields, array $submitted ): array {
		if ( ! isset( $fields['bank_account_2'] ) || 'text' !== $fields['bank_account_2']['type'] ) { return $fields; }
		for ( $number = 3; $number <= 10; $number++ ) {
			foreach ( [ 'account' => __( 'Nomor Rekening %d', 'apeiron-kit' ), 'name' => __( 'Nama Bank %d', 'apeiron-kit' ), 'owner' => __( 'Nama Pemilik %d', 'apeiron-kit' ) ] as $part => $label ) {
				$key = 'bank_' . $part . '_' . $number;
				$source = 'bank_' . $part . '_2';
				if ( isset( $fields[ $source ] ) && array_key_exists( $key, $submitted ) && ! isset( $fields[ $key ] ) ) {
					$field = $fields[ $source ];
					$field['label'] = sprintf( $label, $number );
					$field['required'] = false;
					$fields[ $key ] = $field;
				}
			}
		}
		return $fields;
	}

	/** Permit customer-added Love Story milestones only on the widget's story step. */
	public static function with_extra_journeys( array $fields, array $submitted ): array {
		if ( ! isset( $fields['love_meeting_date'], $fields['love_meeting_story'] ) ) { return $fields; }
		for ( $number = 1; $number <= 10; $number++ ) {
			$prefix = 'love_extra_' . $number . '_';
			if ( ! array_key_exists( $prefix . 'date', $submitted ) && ! array_key_exists( $prefix . 'story', $submitted ) && ! array_key_exists( $prefix . 'title', $submitted ) ) {
				continue;
			}
			foreach ( [ 'title' => __( 'Judul Perjalanan', 'apeiron-kit' ), 'date' => __( 'Tanggal Perjalanan', 'apeiron-kit' ), 'story' => __( 'Cerita Perjalanan', 'apeiron-kit' ) ] as $part => $label ) {
				$key = $prefix . $part;
				if ( isset( $fields[ $key ] ) ) { continue; }
				$source = $fields['love_meeting_' . $part ] ?? $fields['love_meeting_date'];
				$source['type'] = 'title' === $part ? 'text' : ( 'story' === $part ? 'textarea' : 'date' );
				$source['label'] = $label;
				$source['required'] = false;
				$fields[ $key ] = $source;
			}
		}
		return $fields;
	}

	public static function story_group_has_content( string $key, array $submitted ): bool {
		if ( ! preg_match( '/^(love_(?:meeting|engagement|journey|extra_[1-9][0-9]*))_(?:title|date|story)$/D', $key, $match ) ) {
			return true;
		}
		$prefix = $match[1] . '_';
		foreach ( [ 'date', 'story' ] as $part ) {
			$value = $submitted[ $prefix . $part ] ?? '';
			if ( is_string( $value ) && '' !== trim( $value ) ) { return true; }
		}
		return false;
	}

	/** Use the same normalized schema for HTML rendering and server-side validation. */
	public static function resolve( array $settings ): array {
		$rows = isset( $settings['order_steps'] ) && is_array( $settings['order_steps'] ) ? $settings['order_steps'] : self::defaults();
		$flat_steps = isset( $rows[0]['step_key'] ) && ! isset( $rows[0]['fields'] );
		if ( $flat_steps || ( isset( $settings['order_fields'] ) && is_array( $settings['order_fields'] ) ) ) {
			$builder = self::builder_rows( $settings );
			$rows = $builder['steps'];
			foreach ( $rows as &$row ) {
				$row['fields'] = array_values( array_filter( $builder['fields'], static function ( $field ) use ( $row ): bool {
					return is_array( $field ) && sanitize_key( (string) ( $field['step_key'] ?? '' ) ) === $row['step_key'];
				} ) );
			}
			unset( $row );
		}
		$steps = [];
		$used = [];
		foreach ( array_slice( $rows, 0, 30 ) as $step_index => $row ) {
			if ( ! is_array( $row ) || ( array_key_exists( 'enabled', $row ) && 'yes' !== $row['enabled'] ) ) {
				continue;
			}
			$kind = 'confirmation' === ( $row['kind'] ?? '' ) ? 'confirmation' : 'fields';
			$step = [
				'title' => self::text( $row['title'] ?? '', 120 ),
				'kind' => $kind,
				'is_story' => 'love_story' === ( $row['step_key'] ?? '' ),
				'fields' => [],
			];
			$items = isset( $row['fields'] ) && is_array( $row['fields'] ) ? $row['fields'] : [];
			if ( 'fields' === $kind ) {
				foreach ( array_slice( $items, 0, 80 ) as $field_index => $field ) {
					if ( count( $used ) >= 200 ) { break; }
					if ( ! is_array( $field ) || ( array_key_exists( 'visible', $field ) && 'yes' !== $field['visible'] ) ) {
						continue;
					}
					$type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
					if ( ! in_array( $type, [ 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date', 'time', 'url', 'heading' ], true ) ) {
						$type = 'text';
					}
					$key = sanitize_key( (string) ( $field['field_key'] ?? '' ) );
					if ( '' === $key ) {
						$key = 'field_' . sanitize_key( (string) ( $field['_id'] ?? $step_index . '_' . $field_index ) );
					}
					$key = substr( $key, 0, 64 );
					if ( isset( $used[ $key ] ) ) {
						$base = substr( $key, 0, 48 );
						$key = $base . '_' . sanitize_key( (string) ( $row['_id'] ?? $step_index ) );
						$base = $key;
						$counter = 2;
						while ( isset( $used[ $key ] ) ) {
							$key = $base . '_' . $counter++;
						}
					}
					$used[ $key ] = true;
					$options = [];
					foreach ( array_slice( preg_split( '/\r\n|\r|\n/', (string) ( $field['options'] ?? '' ) ), 0, 50 ) as $line ) {
						$pair = explode( '|', $line, 2 );
						$value = self::text( trim( $pair[0] ), 120 );
						if ( '' !== $value && ! isset( $options[ $value ] ) ) {
							$options[ $value ] = self::text( trim( $pair[1] ?? $pair[0] ), 120 );
						}
					}
					$step['fields'][ $key ] = [
						'label' => self::text( $field['label'] ?? $key, 160 ),
						'type' => $type,
						'required' => 'yes' === ( $field['required'] ?? '' ),
						'placeholder' => self::text( $field['placeholder'] ?? '', 200 ),
						'default' => self::text( $field['default_value'] ?? '', 1000, true ),
						'help' => self::text( $field['help_text'] ?? '', 300, true ),
						'options' => $options,
						'width' => self::width( $field['width'] ?? ( 'textarea' === $type ? '100' : '50' ) ),
						'width_tablet' => self::width( $field['width_tablet'] ?? '100' ),
						'width_mobile' => self::width( $field['width_mobile'] ?? '100' ),
					];
				}
			}
			$steps[] = $step;
		}
		return $steps;
	}

	private static function width( $value ): string {
		return in_array( (string) $value, [ '25', '33', '50', '66', '75', '100' ], true ) ? (string) $value : '100';
	}

	private static function text( $value, int $length, bool $multiline = false ): string {
		if ( ! is_scalar( $value ) ) { return ''; }
		$value = substr( (string) $value, 0, $length * 4 );
		return $multiline ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}

	private static function person_fields( string $prefix ): array {
		return [
			$prefix . '_full_name' => self::field( __( 'Nama Lengkap', 'apeiron-kit' ), 'text', true ),
			$prefix . '_nickname' => self::field( __( 'Nama Panggilan', 'apeiron-kit' ), 'text' ),
			$prefix . '_domicile' => self::field( __( 'Domisili', 'apeiron-kit' ), 'text' ),
			$prefix . '_father' => self::field( __( 'Nama Bapak', 'apeiron-kit' ), 'text' ),
			$prefix . '_mother' => self::field( __( 'Nama Ibu', 'apeiron-kit' ), 'text' ),
			$prefix . '_child_order' => self::field( __( 'Anak ke-', 'apeiron-kit' ), 'number' ),
			$prefix . '_phone' => self::field( __( 'No. WhatsApp', 'apeiron-kit' ), 'tel' ),
			$prefix . '_instagram' => self::field( __( 'Instagram', 'apeiron-kit' ), 'text' ),
		];
	}

	private static function field( string $label, string $type, bool $required = false ): array {
		return [ 'label' => $label, 'type' => $type, 'required' => $required ];
	}
}
