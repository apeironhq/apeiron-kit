<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CeritaCinta\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Repeater;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_content',
			[ 'label' => __( 'Cerita Cinta', 'apeiron-kit' ) ]
		);

		$this->add_responsive_control(
			'skin',
			[
				'label'                => __( 'Skin', 'apeiron-kit' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'scrapbook-story',
				'options'              => [
					'scrapbook-story'   => __( 'Scrapbook Story', 'apeiron-kit' ),
					'scrapbook-collage' => __( 'Scrapbook Collage', 'apeiron-kit' ),
				],
				'selectors'            => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '{{VALUE}}' ],
				'selectors_dictionary' => [
					'scrapbook-story'   => '--cc-skin: scrapbook-story; --cc-skin-measure: 0px;',
					'scrapbook-collage' => '--cc-skin: scrapbook-collage; --cc-skin-measure: 1px;',
				],
			]
		);
		$this->add_control(
			'skin_schema',
			[ 'type' => Controls_Manager::HIDDEN, 'default' => '' ]
		);

		$this->add_control(
			'heading',
			[
				'label'       => __( 'Judul Widget', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Cerita Cinta', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'subheading',
			[
				'label'   => __( 'Deskripsi / Subjudul', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => __( 'Dari pertemuan sederhana, hingga memilih untuk melangkah bersama.', 'apeiron-kit' ),
				'dynamic' => [ 'active' => true ],
			]
		);
		$this->add_control(
			'timeline_skin',
			[
				'label'   => __( 'Gaya Timeline', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'doodle-path',
				'options' => [
					'doodle-path'  => __( 'Doodle Path', 'apeiron-kit' ),
					'ribbon-trail' => __( 'Ribbon Trail', 'apeiron-kit' ),
					'journey-icon' => __( 'Journey Icon', 'apeiron-kit' ),
				],
			]
		);
		$this->add_control(
			'journey_marker_type',
			[
				'label'     => __( 'Penanda Bergerak', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'icon',
				'options'   => [ 'icon' => __( 'Icon', 'apeiron-kit' ), 'image' => __( 'Gambar', 'apeiron-kit' ) ],
				'condition' => [ 'timeline_skin' => 'journey-icon' ],
			]
		);
		$this->add_control(
			'journey_marker_icon',
			[
				'label'     => __( 'Icon Penanda', 'apeiron-kit' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [ 'value' => 'fas fa-heartbeat', 'library' => 'fa-solid' ],
				'condition' => [ 'timeline_skin' => 'journey-icon', 'journey_marker_type' => 'icon' ],
			]
		);
		$this->add_control(
			'journey_marker_image',
			[
				'label'       => __( 'Gambar Penanda', 'apeiron-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'Gunakan gambar PNG, WebP, atau format gambar yang didukung WordPress.', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'timeline_skin' => 'journey-icon', 'journey_marker_type' => 'image' ],
			]
		);
		$this->add_control(
			'card_entrance_animation',
			[
				'label'   => __( 'Animasi Masuk Card', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide-up',
				'options' => [
					'none'            => __( 'Tanpa Animasi', 'apeiron-kit' ),
					'fade'            => __( 'Fade In', 'apeiron-kit' ),
					'slide-up'        => __( 'Slide Up', 'apeiron-kit' ),
					'slide-alternate' => __( 'Slide Alternating', 'apeiron-kit' ),
					'zoom-in'         => __( 'Zoom In', 'apeiron-kit' ),
				],
			]
		);
		$this->add_control(
			'show_story_styles',
			[
				'label'        => __( 'Style Per Cerita', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'render_type'  => 'ui',
			]
		);

		$this->end_controls_section();
		$this->start_controls_section(
			'section_stories',
			[ 'label' => __( 'Daftar Cerita', 'apeiron-kit' ) ]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'title',
			[
				'label'       => __( 'Judul', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Cerita Baru', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);
		$repeater->add_control(
			'date',
			[
				'label'       => __( 'Tanggal / Tahun', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Contoh: Juni 2022', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
			]
		);
		$repeater->add_control(
			'description',
			[
				'label'   => __( 'Deskripsi', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 5,
				'default' => __( 'Tuliskan kenangan yang ingin dibagikan di sini.', 'apeiron-kit' ),
				'dynamic' => [ 'active' => true ],
			]
		);
		$repeater->add_control( 'photo_heading', [ 'label' => __( 'Foto', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$repeater->add_control(
			'photo_mode',
			[ 'label' => __( 'Tampilan Foto', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'single', 'options' => [ 'single' => __( 'Foto Tunggal', 'apeiron-kit' ), 'slideshow' => __( 'Slideshow', 'apeiron-kit' ) ] ]
		);
		$repeater->add_control(
			'photo',
			[
				'label'   => __( 'Foto', 'apeiron-kit' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => Utils::get_placeholder_image_src() ],
				'dynamic' => [ 'active' => true, 'categories' => [ TagsModule::IMAGE_CATEGORY ], 'returnType' => 'object' ],
				'condition' => [ 'photo_mode' => 'single' ],
			]
		);
		$repeater->add_control(
			'photo_gallery',
			[
				'label'       => __( 'Foto Slideshow', 'apeiron-kit' ),
				'type'        => Controls_Manager::GALLERY,
				'default'     => [],
				'dynamic'     => [ 'active' => true, 'categories' => [ TagsModule::GALLERY_CATEGORY ], 'returnType' => 'object' ],
				'condition'   => [ 'photo_mode' => 'slideshow' ],
				'description' => __( 'Pilih beberapa foto untuk slideshow. Galeri mendukung Dynamic Tags Elementor yang tersedia.', 'apeiron-kit' ),
			]
		);
		$repeater->add_control(
			'slideshow_effect',
			[ 'label' => __( 'Efek Transisi', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'slide', 'options' => [ 'fade' => 'Fade', 'slide' => 'Slide' ], 'condition' => [ 'photo_mode' => 'slideshow' ] ]
		);
		$repeater->add_control(
			'slideshow_duration',
			[ 'label' => __( 'Durasi Tampil Foto (detik)', 'apeiron-kit' ), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 60, 'step' => 0.5, 'default' => 4, 'condition' => [ 'photo_mode' => 'slideshow' ] ]
		);
		$repeater->add_control(
			'slideshow_speed',
			[ 'label' => __( 'Kecepatan Transisi (ms)', 'apeiron-kit' ), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'max' => 3000, 'step' => 50, 'default' => 600, 'condition' => [ 'photo_mode' => 'slideshow' ] ]
		);
		foreach ( [ 'slideshow_autoplay' => __( 'Autoplay', 'apeiron-kit' ), 'slideshow_loop' => __( 'Loop', 'apeiron-kit' ) ] as $name => $label ) {
			$repeater->add_control( $name, [ 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => [ 'photo_mode' => 'slideshow' ] ] );
		}
		$repeater->add_responsive_control(
			'photo_position_x',
			[
				'label'      => __( 'Posisi Foto X', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [ '%' => [ 'min' => 0, 'max' => 100 ] ],
				'default'    => [ 'size' => 50, 'unit' => '%' ],
				'conditions' => [ 'relation' => 'or', 'terms' => [
					[ 'terms' => [
						[ 'name' => 'photo_mode', 'operator' => '==', 'value' => 'single' ],
						[ 'name' => 'photo', 'operator' => '!=', 'value' => false ],
						[ 'name' => 'photo[url]', 'operator' => '!=', 'value' => '' ],
					] ],
					[ 'terms' => [
						[ 'name' => 'photo_mode', 'operator' => '==', 'value' => 'slideshow' ],
						[ 'name' => 'photo_gallery', 'operator' => '!=', 'value' => false ],
					] ],
				] ],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__photo' => '--cc-photo-position-x: {{SIZE}}%;',
				],
			]
		);
		$repeater->add_responsive_control(
			'photo_position_y',
			[
				'label'       => __( 'Posisi Foto Y', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ '%' ],
				'range'       => [ '%' => [ 'min' => 0, 'max' => 100 ] ],
				'default'     => [ 'size' => 50, 'unit' => '%' ],
				'description' => __( 'Geser titik fokus foto tanpa mengubah file aslinya.', 'apeiron-kit' ),
				'conditions'  => [ 'relation' => 'or', 'terms' => [
					[ 'terms' => [
						[ 'name' => 'photo_mode', 'operator' => '==', 'value' => 'single' ],
						[ 'name' => 'photo', 'operator' => '!=', 'value' => false ],
						[ 'name' => 'photo[url]', 'operator' => '!=', 'value' => '' ],
					] ],
					[ 'terms' => [
						[ 'name' => 'photo_mode', 'operator' => '==', 'value' => 'slideshow' ],
						[ 'name' => 'photo_gallery', 'operator' => '!=', 'value' => false ],
					] ],
				] ],
				'selectors'   => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__photo' => '--cc-photo-position-y: {{SIZE}}%;',
				],
			]
		);
		$repeater->add_control(
			'ornament_type',
			[
				'label'       => __( 'Dekorasi Sudut Foto', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'icon',
				'description' => __( 'Pilih Gambar untuk memakai ornamen PNG atau WebP.', 'apeiron-kit' ),
				'options'     => [
					'icon'  => __( 'Icon', 'apeiron-kit' ),
					'image' => __( 'Gambar Custom', 'apeiron-kit' ),
					'none'  => __( 'Tidak Ditampilkan', 'apeiron-kit' ),
				],
				'separator'   => 'before',
			]
		);
		$repeater->add_control(
			'ornament_position',
			[
				'label' => __( 'Posisi Dekorasi', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'top-right',
				'options' => [ 'top-left' => __( 'Atas Kiri', 'apeiron-kit' ), 'top-right' => __( 'Atas Kanan', 'apeiron-kit' ), 'bottom-left' => __( 'Bawah Kiri', 'apeiron-kit' ), 'bottom-right' => __( 'Bawah Kanan', 'apeiron-kit' ) ],
				'condition' => [ 'ornament_type!' => 'none' ],
				'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__polaroid .apeiron-cerita-cinta__doodle' => '{{VALUE}}' ],
				'selectors_dictionary' => [
					'top-left' => 'top:6px; bottom:auto; left:6px; right:auto;', 'top-right' => 'top:6px; bottom:auto; left:auto; right:6px;',
					'bottom-left' => 'top:auto; bottom:6px; left:6px; right:auto;', 'bottom-right' => 'top:auto; bottom:6px; left:auto; right:6px;',
				],
			]
		);
		$repeater->add_responsive_control(
			'ornament_item_size',
			[ 'label' => __( 'Ukuran Dekorasi Foto', 'apeiron-kit' ), 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px' ], 'range' => [ 'px' => [ 'min' => 8, 'max' => 160 ] ], 'condition' => [ 'ornament_type!' => 'none' ], 'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__polaroid .apeiron-cerita-cinta__doodle' => '--cc-ornament-size: {{SIZE}}{{UNIT}};' ] ]
		);
		foreach ( [ 'x' => 'X', 'y' => 'Y' ] as $axis => $label ) {
			$repeater->add_responsive_control(
				'ornament_offset_' . $axis,
				[ 'label' => __( 'Offset Dekorasi', 'apeiron-kit' ) . ' ' . $label, 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px' ], 'range' => [ 'px' => [ 'min' => -100, 'max' => 100 ] ], 'condition' => [ 'ornament_type!' => 'none' ], 'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__doodle' => '--cc-ornament-offset-' . $axis . ': {{SIZE}}{{UNIT}};' ] ]
			);
		}
		$repeater->add_control(
			'ornament_item_color',
			[ 'label' => __( 'Warna Ikon Dekorasi', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR, 'condition' => [ 'ornament_type' => 'icon' ], 'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__doodle' => 'color: {{VALUE}};' ] ]
		);
		$repeater->add_control(
			'ornament_icon',
			[
				'label'     => __( 'Icon Ornamen', 'apeiron-kit' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [ 'value' => 'far fa-heart', 'library' => 'fa-regular' ],
				'condition' => [ 'ornament_type' => 'icon' ],
			]
		);
		$repeater->add_control(
			'ornament_image',
			[
				'label'       => __( 'Gambar Ornamen', 'apeiron-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'Mendukung PNG, WebP, dan format gambar yang didukung WordPress.', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true, 'categories' => [ TagsModule::IMAGE_CATEGORY ], 'returnType' => 'object' ],
				'condition'   => [ 'ornament_type' => 'image' ],
			]
		);
		$repeater->add_control(
			'icon',
			[ 'label' => __( 'Icon Timeline (Opsional)', 'apeiron-kit' ), 'type' => Controls_Manager::ICONS, 'default' => [ 'value' => '', 'library' => '' ], 'separator' => 'before' ]
		);
		$repeater->add_control(
			'tape_type',
			[
				'label'     => __( 'Dekorasi Tape', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'tape',
				'options'   => [ 'tape' => __( 'Tape', 'apeiron-kit' ), 'image' => __( 'Gambar', 'apeiron-kit' ), 'none' => __( 'Sembunyikan', 'apeiron-kit' ) ],
				'separator' => 'before',
			]
		);
		$repeater->add_control(
			'tape_image',
			[
				'label'       => __( 'Gambar Tape', 'apeiron-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'Gunakan PNG atau WebP untuk dekorasi foto dan kertas cerita.', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'tape_type' => 'image' ],
			]
		);
		$repeater->add_responsive_control(
			'tape_image_size',
			[
				'label'      => __( 'Ukuran Gambar Tape', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [ 'size' => 48, 'unit' => 'px' ],
				'range'      => [ 'px' => [ 'min' => 12, 'max' => 160 ] ],
				'condition'  => [ 'tape_type' => 'image' ],
				'selectors'  => [ '{{WRAPPER}} {{CURRENT_ITEM}}' => '--cc-tape-image-size: {{SIZE}}{{UNIT}};' ],
			]
		);
		$repeater->add_control(
			'custom_style',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);
		$repeater->add_control( 'item_style_overrides', [ 'type' => Controls_Manager::HIDDEN, 'default' => [] ] );
		$card_selector = '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__paper, {{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__polaroid';
		$repeater->add_control(
			'item_card_color',
			[
				'label'     => __( 'Warna Card', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [ 'custom_style' => 'yes' ],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => '--cc-item-card-color: {{VALUE}};',
					$card_selector => 'background-color: {{VALUE}};',
				],
			]
		);
		$repeater->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'item_border', 'selector' => $card_selector, 'condition' => [ 'custom_style' => 'yes' ],
				'fields_options' => [
					'border' => [ 'label' => __( 'Jenis Border Card', 'apeiron-kit' ) ],
					'width'  => [ 'label' => __( 'Ketebalan Border Card', 'apeiron-kit' ) ],
					'color'  => [ 'label' => __( 'Warna Border Card', 'apeiron-kit' ) ],
				],
			]
		);
		$repeater->add_responsive_control(
			'item_radius',
			[
				'label'      => __( 'Radius Sudut Card', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'condition'  => [ 'custom_style' => 'yes' ],
				'selectors'  => [ $card_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);
		foreach ( [ 'title' => __( 'Judul', 'apeiron-kit' ), 'date' => __( 'Tanggal', 'apeiron-kit' ), 'description' => __( 'Deskripsi', 'apeiron-kit' ) ] as $part => $label ) {
			$selector = '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__' . $part;
			$repeater->add_group_control(
				Group_Control_Typography::get_type(),
				[ 'name' => 'item_' . $part . '_typography', 'label' => __( 'Tipografi', 'apeiron-kit' ) . ' ' . $label, 'selector' => $selector, 'condition' => [ 'custom_style' => 'yes' ] ]
			);
			$repeater->add_control(
				'item_' . $part . '_color',
				[ 'label' => __( 'Warna', 'apeiron-kit' ) . ' ' . $label, 'type' => Controls_Manager::COLOR, 'condition' => [ 'custom_style' => 'yes' ], 'selectors' => [ $selector => 'color: {{VALUE}};' ] ]
			);
		}
		$repeater->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'item_shadow',
				'label'    => __( 'Box Shadow Card', 'apeiron-kit' ),
				'selector' => $card_selector,
			]
		);
		$repeater->add_responsive_control(
			'item_padding',
			[
				'label'      => __( 'Padding Kertas Cerita', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [ '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-cerita-cinta__paper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);
		foreach ( $repeater->get_controls() as $name => $control ) {
			if ( 0 !== strpos( $name, 'item_' ) || 'item_style_overrides' === $name || 0 === strpos( $name, 'item_shadow' ) ) {
				continue;
			}
			$condition = $control['condition'] ?? [];
			unset( $condition['custom_style'] );
			$repeater->update_control(
				$name,
				[
					'condition'  => $condition,
					'conditions' => [
						'relation' => 'or',
						'terms'    => [
							[ 'name' => 'custom_style', 'operator' => '==', 'value' => 'yes' ],
							[ 'name' => 'item_style_overrides', 'operator' => 'contains', 'value' => $name ],
						],
					],
				]
			);
		}
		foreach ( [ 'x' => __( 'Posisi Card X', 'apeiron-kit' ), 'y' => __( 'Posisi Card Y', 'apeiron-kit' ) ] as $axis => $label ) {
			$repeater->add_responsive_control(
				'card_position_' . $axis,
				[
					'label'       => $label,
					'type'        => Controls_Manager::SLIDER,
					'size_units'  => [ 'px' ],
					'range'       => [ 'px' => [ 'min' => -200, 'max' => 200 ] ],
					'default'     => [ 'size' => 0, 'unit' => 'px' ],
					'description' => __( 'Geser card pada Skin Scrapbook Story tanpa mengubah lebarnya.', 'apeiron-kit' ),
					'selectors'   => [
						'{{WRAPPER}} {{CURRENT_ITEM}}' => '--cc-card-position-' . $axis . ': {{SIZE}}{{UNIT}};',
					],
				]
			);
		}

		$this->add_control(
			'stories',
			[
				'label'         => __( 'Daftar Cerita', 'apeiron-kit' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'title_field'   => '{{{ title }}}',
				'prevent_empty' => false,
				'default'       => [
					[
						'title'       => __( 'Pertama Bertemu', 'apeiron-kit' ),
						'date'        => __( 'Juni 2020', 'apeiron-kit' ),
						'description' => __( 'Sebuah sapaan singkat menjadi awal cerita kami. Siapa sangka, pertemuan itu akan membawa begitu banyak kenangan?', 'apeiron-kit' ),
					],
					[
						'title'       => __( 'Mulai Bersama', 'apeiron-kit' ),
						'date'        => __( 'Februari 2021', 'apeiron-kit' ),
						'description' => __( 'Obrolan panjang, tawa kecil, dan waktu yang terasa cepat. Kami mulai mengenal satu sama lain dan memilih berjalan bersama.', 'apeiron-kit' ),
					],
					[
						'title'       => __( 'Lamaran', 'apeiron-kit' ),
						'date'        => __( 'Desember 2024', 'apeiron-kit' ),
						'description' => __( 'Di hadapan keluarga, kami mengucapkan niat untuk melangkah lebih jauh. Hari yang hangat, penuh doa dan rasa syukur.', 'apeiron-kit' ),
					],
					[
						'title'       => __( 'Pernikahan', 'apeiron-kit' ),
						'date'        => __( 'Juni 2025', 'apeiron-kit' ),
						'description' => __( 'Kini kami siap membuka lembaran baru. Bersama orang-orang tersayang, kami merayakan awal perjalanan sebagai satu keluarga.', 'apeiron-kit' ),
					],
				],
			]
		);

		$this->end_controls_section();
	}
}
