<?php

namespace ApeironKit\Support\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DocumentWidgetIndex {

	/**
	 * @var array|false|null
	 */
	private $parsed_elementor_data = null;

	/**
	 * @var array{types:array<string,true>,page_loader:?array{id:string,settings:array},cover_settings:array<int,array>}|null
	 */
	private ?array $index = null;

	/**
	 * @return array{types:array<string,true>,page_loader:?array{id:string,settings:array},cover_settings:array<int,array>}
	 */
	public function get(): array {
		if ( null !== $this->index ) {
			return $this->index;
		}

		$this->index = [
			'types'          => [],
			'page_loader'    => null,
			'cover_settings' => [],
		];

		if ( ! is_singular() ) {
			return $this->index;
		}

		$data = $this->get_parsed_elementor_data();
		if ( is_array( $data ) ) {
			$this->collect( $data, $this->index );
		}

		return $this->index;
	}

	/**
	 * @param mixed $data Elementor document data.
	 * @param array{types:array<string,true>,page_loader:?array{id:string,settings:array},cover_settings:array<int,array>} $index Document index.
	 */
	private function collect( $data, array &$index ): void {
		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $data as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$type     = isset( $element['widgetType'] ) && is_string( $element['widgetType'] ) ? $element['widgetType'] : '';
			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];

			if ( '' !== $type ) {
				$index['types'][ $type ] = true;
			}
			if ( 'apeiron-page-loader' === $type && null === $index['page_loader'] ) {
				$index['page_loader'] = [
					'id'       => isset( $element['id'] ) ? (string) $element['id'] : '',
					'settings' => $settings,
				];
			}
			if ( 'apeiron-cover' === $type ) {
				$index['cover_settings'][] = $settings;
			}

			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->collect( $element['elements'], $index );
			}
		}
	}

	/**
	 * @return array|false
	 */
	private function get_parsed_elementor_data() {
		if ( null !== $this->parsed_elementor_data ) {
			return $this->parsed_elementor_data;
		}

		$elementor_data = get_post_meta( get_the_ID(), '_elementor_data', true );
		if ( ! $elementor_data || ! is_string( $elementor_data ) ) {
			return $this->parsed_elementor_data = false;
		}

		$decoded = json_decode( $elementor_data, true );

		return $this->parsed_elementor_data = is_array( $decoded ) ? $decoded : false;
	}
}
