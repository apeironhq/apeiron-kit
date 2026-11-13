<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\PhotoAlbum\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$root = '{{WRAPPER}} .apeiron-photo-album';

		// Kartu album.
		$this->start_controls_section(
			'section_style_card',
			[ 'label' => __( 'Kartu Album', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_album_color( 'card_background', __( 'Warna Latar', 'apeiron-kit' ), '--pa-card-bg' );
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[ 'name' => 'card_border', 'selector' => $root ]
		);
		$this->add_responsive_control(
			'card_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [ $root => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);
		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [ $root => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[ 'name' => 'card_shadow', 'selector' => $root ]
		);
		$this->end_controls_section();

		// Judul.
		$this->start_controls_section(
			'section_style_title',
			[ 'label' => __( 'Judul', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_title' => 'yes' ] ]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 'title_typography', 'selector' => '{{WRAPPER}} .apeiron-photo-album__title' ]
		);
		$this->add_album_color( 'title_color', __( 'Warna Judul', 'apeiron-kit' ), '--pa-title-color' );
		$this->add_album_color( 'title_line_color', __( 'Warna Garis', 'apeiron-kit' ), '--pa-line-color', [ 'show_title_line' => 'yes' ] );
		$this->add_album_slider( 'title_spacing', __( 'Jarak ke Foto', 'apeiron-kit' ), '--pa-header-gap', 0, 80 );
		$this->end_controls_section();

		// Foto.
		$this->start_controls_section(
			'section_style_photo',
			[ 'label' => __( 'Foto', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_album_slider( 'photo_height', __( 'Tinggi Foto', 'apeiron-kit' ), '--pa-photo-height', 200, 900 );
		$this->add_album_slider( 'photo_radius', __( 'Radius Foto', 'apeiron-kit' ), '--pa-photo-radius', 0, 60 );
		$this->add_album_color( 'peek_frame_color', __( 'Warna Bingkai Foto Intip', 'apeiron-kit' ), '--pa-peek-frame', [ 'show_peek' => 'yes' ] );
		$this->add_album_slider( 'peek_rotation', __( 'Rotasi Foto Intip (deg)', 'apeiron-kit' ), '--pa-peek-rotate', -15, 15, 'deg', [ 'show_peek' => 'yes' ] );
		$this->end_controls_section();

		// Thumbnail.
		$this->start_controls_section(
			'section_style_thumbs',
			[ 'label' => __( 'Thumbnail', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_thumbnails' => 'yes' ] ]
		);
		$this->add_album_color( 'film_background', __( 'Warna Film Strip', 'apeiron-kit' ), '--pa-film-bg' );
		$this->add_album_color( 'film_number_color', __( 'Warna Nomor Film', 'apeiron-kit' ), '--pa-film-number', [ 'show_numbers' => 'yes' ] );
		$this->add_album_color( 'film_active_color', __( 'Bingkai Aktif (Film Strip)', 'apeiron-kit' ), '--pa-film-active' );
		$this->add_album_color( 'thumb_active_color', __( 'Bingkai Aktif (Kartu)', 'apeiron-kit' ), '--pa-thumb-active' );
		$this->add_album_slider( 'thumb_size', __( 'Ukuran Thumbnail', 'apeiron-kit' ), '--pa-thumb-size', 36, 160 );
		$this->add_album_slider( 'thumb_radius', __( 'Radius Thumbnail', 'apeiron-kit' ), '--pa-thumb-radius', 0, 30 );
		$this->end_controls_section();

		// Nomor & keterangan.
		$this->start_controls_section(
			'section_style_meta',
			[ 'label' => __( 'Nomor & Keterangan', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 'counter_typography', 'label' => __( 'Tipografi Nomor', 'apeiron-kit' ), 'selector' => '{{WRAPPER}} .apeiron-photo-album__current' ]
		);
		$this->add_album_color( 'counter_color', __( 'Warna Nomor', 'apeiron-kit' ), '--pa-counter-color' );
		$this->add_album_color( 'total_color', __( 'Warna Total', 'apeiron-kit' ), '--pa-total-color' );
		$this->add_album_color( 'rule_color', __( 'Warna Garis Pemisah', 'apeiron-kit' ), '--pa-rule-color' );
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 'caption_typography', 'label' => __( 'Tipografi Keterangan', 'apeiron-kit' ), 'selector' => '{{WRAPPER}} .apeiron-photo-album__caption', 'separator' => 'before' ]
		);
		$this->add_album_color( 'caption_color', __( 'Warna Keterangan', 'apeiron-kit' ), '--pa-caption-color' );
		$this->add_control(
			'card_overlay_heading',
			[ 'label' => __( 'Layout Kartu', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]
		);
		$this->add_album_color( 'overlay_text_color', __( 'Warna Teks di Atas Foto', 'apeiron-kit' ), '--pa-overlay-text' );
		$this->add_album_color( 'overlay_color', __( 'Warna Gradasi Foto', 'apeiron-kit' ), '--pa-overlay-color' );
		$this->end_controls_section();

		// Tombol.
		$this->start_controls_section(
			'section_style_button',
			[ 'label' => __( 'Tombol', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_album_slider( 'button_size', __( 'Ukuran Tombol', 'apeiron-kit' ), '--pa-button-size', 32, 120 );
		$this->add_album_color( 'button_background', __( 'Warna Tombol', 'apeiron-kit' ), '--pa-button-bg' );
		$this->add_album_color( 'button_hover_background', __( 'Warna Tombol (Hover)', 'apeiron-kit' ), '--pa-button-bg-hover' );
		$this->add_album_color( 'button_icon_color', __( 'Warna Ikon', 'apeiron-kit' ), '--pa-button-icon' );
		$this->add_album_color( 'button_ring_color', __( 'Warna Cincin', 'apeiron-kit' ), '--pa-button-ring' );
		$this->end_controls_section();

		// Progres.
		$this->start_controls_section(
			'section_style_progress',
			[ 'label' => __( 'Bar Progres', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_progress' => 'yes' ] ]
		);
		$this->add_album_color( 'progress_track_color', __( 'Warna Jalur', 'apeiron-kit' ), '--pa-progress-track' );
		$this->add_album_color( 'progress_bar_color', __( 'Warna Progres', 'apeiron-kit' ), '--pa-progress-bar' );
		$this->add_album_slider( 'progress_height', __( 'Ketebalan', 'apeiron-kit' ), '--pa-progress-height', 1, 10 );
		$this->end_controls_section();
	}

	private function add_album_color( string $name, string $label, string $variable, array $condition = [] ): void {
		$args = [
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-photo-album' => $variable . ': {{VALUE}};' ],
		];
		if ( ! empty( $condition ) ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $name, $args );
	}

	private function add_album_slider( string $name, string $label, string $variable, int $min, int $max, string $unit = 'px', array $condition = [] ): void {
		$args = [
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ $unit ],
			'range'      => [ $unit => [ 'min' => $min, 'max' => $max ] ],
			'selectors'  => [ '{{WRAPPER}} .apeiron-photo-album' => $variable . ': {{SIZE}}{{UNIT}};' ],
		];
		if ( ! empty( $condition ) ) {
			$args['condition'] = $condition;
		}
		$this->add_responsive_control( $name, $args );
	}
}
