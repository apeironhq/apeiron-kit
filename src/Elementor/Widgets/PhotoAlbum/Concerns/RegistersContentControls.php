<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\PhotoAlbum\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->register_album_controls();
		$this->register_display_controls();
		$this->register_slideshow_controls();
	}

	private function register_album_controls(): void {
		$this->start_controls_section(
			'section_album',
			[ 'label' => __( 'Album Foto', 'apeiron-kit' ) ]
		);

		$this->add_control(
			'gallery',
			[
				'label'       => __( 'Foto', 'apeiron-kit' ),
				'type'        => Controls_Manager::GALLERY,
				'default'     => [],
				'show_label'  => false,
				'dynamic'     => [ 'active' => true, 'categories' => [ TagsModule::GALLERY_CATEGORY ] ],
				'description' => __( 'Pilih foto dari Media Library atau hubungkan Dynamic Tag galeri (misalnya ACF Gallery).', 'apeiron-kit' ),
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'photo_size',
				'default' => 'large',
				'exclude' => [ 'custom' ],
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'        => __( 'Tampilkan Judul', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => __( 'Judul', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Galeri Momen', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'show_title' => 'yes' ],
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'     => __( 'Tag HTML Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => [
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
					'p'   => 'p',
				],
				'condition' => [ 'show_title' => 'yes' ],
			]
		);

		$this->add_control(
			'caption_source',
			[
				'label'       => __( 'Sumber Keterangan Foto', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'caption',
				'options'     => [
					'caption'     => __( 'Caption Media', 'apeiron-kit' ),
					'title'       => __( 'Judul Media', 'apeiron-kit' ),
					'alt'         => __( 'Alt Text Media', 'apeiron-kit' ),
					'description' => __( 'Deskripsi Media', 'apeiron-kit' ),
					'none'        => __( 'Tidak Ditampilkan', 'apeiron-kit' ),
				],
				'description' => __( 'Keterangan diambil dari data tiap foto di Media Library.', 'apeiron-kit' ),
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'fallback_caption',
			[
				'label'       => __( 'Keterangan Cadangan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Hari yang tak terlupakan', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
				'description' => __( 'Dipakai jika foto belum punya keterangan.', 'apeiron-kit' ),
				'condition'   => [ 'caption_source!' => 'none' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_display_controls(): void {
		$this->start_controls_section(
			'section_display',
			[ 'label' => __( 'Tampilan', 'apeiron-kit' ) ]
		);

		$this->add_responsive_control(
			'layout',
			[
				'label'                => __( 'Layout', 'apeiron-kit' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'filmstrip',
				'tablet_default'       => 'filmstrip',
				'mobile_default'       => 'card',
				'options'              => [
					'filmstrip' => __( 'Film Strip', 'apeiron-kit' ),
					'card'      => __( 'Kartu', 'apeiron-kit' ),
				],
				'description'          => __( 'Film Strip menampilkan thumbnail di samping, Kartu menampilkan keterangan di atas foto dengan thumbnail di bawah.', 'apeiron-kit' ),
				'selectors'            => [ '{{WRAPPER}} .apeiron-photo-album' => '{{VALUE}}' ],
				'selectors_dictionary' => [
					'filmstrip' => '--pa-layout: filmstrip;',
					'card'      => '--pa-layout: card;',
				],
			]
		);

		$switches = [
			'show_title_line' => [ __( 'Garis Judul', 'apeiron-kit' ), 'yes' ],
			'show_peek'       => [ __( 'Intip Foto Berikutnya', 'apeiron-kit' ), 'yes' ],
			'show_counter'    => [ __( 'Nomor Urut', 'apeiron-kit' ), 'yes' ],
			'show_caption'    => [ __( 'Keterangan Foto', 'apeiron-kit' ), 'yes' ],
			'show_thumbnails' => [ __( 'Thumbnail', 'apeiron-kit' ), 'yes' ],
			'show_numbers'    => [ __( 'Nomor di Thumbnail', 'apeiron-kit' ), 'yes' ],
			'show_progress'   => [ __( 'Bar Progres', 'apeiron-kit' ), 'yes' ],
			'show_next'       => [ __( 'Tombol Berikutnya', 'apeiron-kit' ), 'yes' ],
			'show_prev'       => [ __( 'Tombol Sebelumnya', 'apeiron-kit' ), '' ],
			'open_lightbox'   => [ __( 'Buka Foto di Lightbox', 'apeiron-kit' ), 'yes' ],
		];

		foreach ( $switches as $name => $config ) {
			$args = [
				'label'        => $config[0],
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => $config[1],
			];
			if ( 'show_numbers' === $name ) {
				$args['condition']   = [ 'show_thumbnails' => 'yes' ];
				$args['description'] = __( 'Nomor hanya tampil pada layout Film Strip.', 'apeiron-kit' );
			}
			$this->add_control( $name, $args );
		}

		$this->end_controls_section();
	}

	private function register_slideshow_controls(): void {
		$this->start_controls_section(
			'section_slideshow',
			[ 'label' => __( 'Slideshow', 'apeiron-kit' ) ]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => __( 'Autoplay', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'autoplay_duration',
			[
				'label'     => __( 'Durasi Tampil Foto (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1.5,
				'max'       => 30,
				'step'      => 0.5,
				'default'   => 5,
				'condition' => [ 'autoplay' => 'yes' ],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => __( 'Jeda Saat Disentuh/Hover', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'autoplay' => 'yes' ],
			]
		);

		$this->add_control(
			'loop',
			[
				'label'        => __( 'Loop', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'transition_effect',
			[
				'label'   => __( 'Efek Transisi', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade',
				'options' => [
					'fade'  => __( 'Fade', 'apeiron-kit' ),
					'slide' => __( 'Slide', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'   => __( 'Kecepatan Transisi (ms)', 'apeiron-kit' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 3000,
				'step'    => 50,
				'default' => 700,
			]
		);

		$this->end_controls_section();
	}
}
