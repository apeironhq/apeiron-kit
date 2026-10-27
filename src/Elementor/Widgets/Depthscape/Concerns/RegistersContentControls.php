<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Depthscape\Concerns;

use ApeironKit\Elementor\Widgets\Depthscape\SceneConfigBuilder;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->register_layer_controls();
		$this->register_scene_controls();
		$this->register_keyframe_controls();
		$this->register_motion_controls();
		$this->register_particle_controls();
		$this->register_hint_controls();
	}

	private function register_layer_controls(): void {
		$this->start_controls_section(
			'section_layers',
			[
				'label' => __( 'Layer & Konten', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'layers_intro',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Susun gambar, video, teks langsung, atau container Elementor sebagai layer. Kedalaman negatif = jauh, positif = dekat. Gerakan kamera diatur pada Adegan & Kamera.', 'apeiron-kit' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'layer_key',
			[
				'label'       => __( 'ID Layer', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'layer',
				'description' => __( 'ID unik untuk menghubungkan layer dengan Animasi Layer. Contoh: bunga-depan.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'layer_type',
			[
				'label'   => __( 'Jenis Layer', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array_merge( [
					'image'    => __( 'Gambar', 'apeiron-kit' ),
					'video'    => __( 'Video', 'apeiron-kit' ),
					'text'     => __( 'Teks Langsung', 'apeiron-kit' ),
					'template' => __( 'Template Elementor', 'apeiron-kit' ),
					'shape'    => __( 'Bentuk / Gradasi', 'apeiron-kit' ),
				], $this->supports_native_content() ? [ 'container' => __( 'Container Elementor', 'apeiron-kit' ) ] : [] ),
				'default' => 'image',
			]
		);

		$repeater->add_control(
			'image',
			[
				'label'     => __( 'Gambar', 'apeiron-kit' ),
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => [ 'active' => true ],
				'default'   => [ 'url' => Utils::get_placeholder_image_src() ],
				'condition' => [ 'layer_type' => 'image' ],
			]
		);

		$repeater->add_control(
			'video',
			[
				'label'       => __( 'File Video', 'apeiron-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => [ 'video' ],
				'dynamic'     => [ 'active' => true, 'categories' => [ \Elementor\Modules\DynamicTags\Module::MEDIA_CATEGORY ] ],
				'condition'   => [ 'layer_type' => 'video' ],
				'description' => __( 'Pilih video dari Media Library. Video diputar otomatis tanpa suara saat layer terlihat dan dijeda saat tersembunyi.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'video_loop',
			[
				'label'        => __( 'Ulangi (Loop)', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'layer_type' => 'video' ],
			]
		);

		$repeater->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'shape_fill',
				'types'     => [ 'classic', 'gradient' ],
				'selector'  => '{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-depthscape__shape',
				'condition' => [ 'layer_type' => 'shape' ],
			]
		);

		$repeater->add_responsive_control(
			'shape_height',
			[
				'label'      => __( 'Tinggi Bentuk', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px', 'vh' ],
				'range'      => [
					'%'  => [ 'min' => 1, 'max' => 200 ],
					'px' => [ 'min' => 4, 'max' => 2000 ],
					'vh' => [ 'min' => 1, 'max' => 200 ],
				],
				'default'    => [ 'size' => 50, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'height: {{SIZE}}{{UNIT}};',
				],
				'description' => __( '% dihitung dari tinggi bingkai.', 'apeiron-kit' ),
				'conditions' => [
					'relation' => 'and',
					'terms'    => [
						[ 'name' => 'layer_type', 'operator' => '===', 'value' => 'shape' ],
						[ 'name' => 'fit', 'operator' => '!==', 'value' => 'cover' ],
					],
				],
			]
		);

		$repeater->add_control(
			'fit',
			[
				'label'     => __( 'Mode Ukuran', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'free'  => __( 'Bebas (atur lebar)', 'apeiron-kit' ),
					'cover' => __( 'Penuhi Bingkai', 'apeiron-kit' ),
				],
				'default'   => 'free',
				'condition' => [ 'layer_type' => [ 'image', 'video', 'shape' ] ],
			]
		);

		$repeater->add_control(
			'eyebrow',
			[
				'label'     => __( 'Teks di Atas Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'condition' => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'title',
			[
				'label'     => __( 'Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 2,
				'dynamic'   => [ 'active' => true ],
				'condition' => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'subtitle',
			[
				'label'     => __( 'Subjudul', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'condition' => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'body',
			[
				'label'     => __( 'Isi Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::WYSIWYG,
				'dynamic'   => [ 'active' => true ],
				'condition' => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'button_text',
			[
				'label'     => __( 'Teks Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'condition' => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'button_link',
			[
				'label'       => __( 'Tautan Tombol', 'apeiron-kit' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [ 'active' => true ],
				'description' => __( 'Isi #ds-next agar tombol meluncurkan kamera ke adegan berikutnya (seperti tombol Buka Undangan). Klik pertama di sampul juga memulai Tombol Audio seperti widget Sampul.', 'apeiron-kit' ),
				'condition'   => [
					'layer_type'   => 'text',
					'button_text!' => '',
				],
			]
		);

		$repeater->add_control(
			'template_id',
			[
				'label'       => __( 'Template', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_template_options(),
				'default'     => '',
				'description' => __( 'Desain isi layer bebas di Elementor (Templates > Saved Templates), lalu pilih di sini.', 'apeiron-kit' ),
				'condition'   => [ 'layer_type' => 'template' ],
			]
		);

		$repeater->add_control( 'content_id', [
			'type' => Controls_Manager::HIDDEN,
			'default' => '',
			'condition' => [ 'layer_type' => 'container' ],
		] );
		$repeater->add_control( 'edit_content', [
			'label' => __( 'Isi Container', 'apeiron-kit' ),
			'type' => Controls_Manager::BUTTON,
			'text' => __( 'Edit Isi Container', 'apeiron-kit' ),
			'event' => 'apeiron:depthscape:edit-content',
			'condition' => [ 'layer_type' => 'container' ],
			'description' => __( 'Masukkan Heading, Text Editor, Button, atau widget lain ke container ini. Animasi mengikuti ID Layer.', 'apeiron-kit' ),
		] );

		$repeater->add_control(
			'heading_layer_position',
			[
				'label'     => __( 'Posisi & Kedalaman', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'layer_space',
			[
				'label'       => __( 'Ruang Layer', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'auto'   => __( 'Otomatis', 'apeiron-kit' ),
					'world'  => __( 'Ikut kamera 3D', 'apeiron-kit' ),
					'screen' => __( 'Tetap di layar', 'apeiron-kit' ),
				],
				'default'     => 'auto',
				'description' => __( 'Otomatis: gambar, video & template ikut kamera; teks, bentuk, dan container tetap di layar agar selalu terbaca. Animasi Layer tetap berlaku.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'depth',
			[
				'label'       => __( 'Kedalaman (Z)', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [ 'min' => -2000, 'max' => 800, 'step' => 10 ],
				],
				'default'     => [ 'size' => 0, 'unit' => 'px' ],
				'description' => __( '-800 latar jauh · 0 subjek · 200 bunga depan.', 'apeiron-kit' ),
				'conditions'  => [
					'relation' => 'or',
					'terms'    => [
						[ 'name' => 'layer_space', 'operator' => '===', 'value' => 'world' ],
						[
							'relation' => 'and',
							'terms'    => [
								[ 'name' => 'layer_space', 'operator' => '===', 'value' => 'auto' ],
								[ 'name' => 'layer_type', 'operator' => '!in', 'value' => [ 'text', 'shape', 'container' ] ],
							],
						],
					],
				],
			]
		);

		$repeater->add_responsive_control(
			'pos_x',
			[
				'label'      => __( 'Posisi Horizontal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => -50, 'max' => 150 ],
				],
				'default'    => [ 'size' => 50, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => '--ds-left: {{SIZE}}%;',
				],
				'conditions' => $this->free_layer_conditions(),
			]
		);

		$repeater->add_responsive_control(
			'pos_y',
			[
				'label'      => __( 'Posisi Vertikal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => -50, 'max' => 150 ],
				],
				'default'    => [ 'size' => 50, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => '--ds-top: {{SIZE}}%;',
				],
				'conditions' => $this->free_layer_conditions(),
			]
		);

		$repeater->add_control(
			'anchor',
			[
				'label'      => __( 'Titik Jangkar', 'apeiron-kit' ),
				'type'       => Controls_Manager::SELECT,
				'options'    => [
					'center' => __( 'Tengah', 'apeiron-kit' ),
					'bottom' => __( 'Bawah', 'apeiron-kit' ),
					'top'    => __( 'Atas', 'apeiron-kit' ),
				],
				'default'    => 'center',
				'conditions' => $this->free_layer_conditions(),
			]
		);

		$repeater->add_responsive_control(
			'width',
			[
				'label'      => __( 'Lebar', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px', 'vw' ],
				'range'      => [
					'%'  => [ 'min' => 5, 'max' => 300 ],
					'px' => [ 'min' => 20, 'max' => 3000 ],
					'vw' => [ 'min' => 5, 'max' => 300 ],
				],
				'default'    => [ 'size' => 80, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'width: {{SIZE}}{{UNIT}};',
				],
				'conditions' => $this->free_layer_conditions(),
			]
		);

		$repeater->add_control(
			'overscan',
			[
				'label'       => __( 'Ruang Lebih (Overscan)', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ '%' ],
				'range'       => [
					'%' => [ 'min' => 0, 'max' => 60 ],
				],
				'default'     => [ 'size' => 12, 'unit' => '%' ],
				'selectors'   => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => '--ds-overscan: {{SIZE}}%;',
				],
				'description' => __( 'Mencegah tepi gambar terlihat saat kamera bergeser.', 'apeiron-kit' ),
				'condition'   => [
					'layer_type' => [ 'image', 'video', 'shape' ],
					'fit'        => 'cover',
				],
			]
		);

		$repeater->add_control(
			'heading_layer_start',
			[
				'label'     => __( 'Tampilan Awal', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_number_pair( $repeater, 'start_x', __( 'Geser X (% bingkai)', 'apeiron-kit' ), 0, -500, 500, 1 );
		$this->add_number_pair( $repeater, 'start_y', __( 'Geser Y (% bingkai)', 'apeiron-kit' ), 0, -500, 500, 1 );
		$this->add_number_pair( $repeater, 'start_scale', __( 'Skala', 'apeiron-kit' ), 1, 0, 20, 0.05 );
		$this->add_number_pair( $repeater, 'start_rotate', __( 'Rotasi (°)', 'apeiron-kit' ), 0, -720, 720, 1 );
		$this->add_number_pair( $repeater, 'start_opacity', __( 'Opasitas (0–1)', 'apeiron-kit' ), 1, 0, 1, 0.05 );
		$this->add_number_pair( $repeater, 'start_blur', __( 'Blur (px)', 'apeiron-kit' ), 0, 0, 40, 0.5 );

		$repeater->add_control(
			'heading_layer_idle',
			[
				'label'     => __( 'Gerakan Berulang', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'idle_type',
			[
				'label'       => __( 'Jenis Gerakan', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'none'    => __( 'Tidak ada', 'apeiron-kit' ),
					'float'   => __( 'Mengambang', 'apeiron-kit' ),
					'sway'    => __( 'Bergoyang', 'apeiron-kit' ),
					'breathe' => __( 'Bernapas (zoom)', 'apeiron-kit' ),
					'drift'   => __( 'Melayang ke samping', 'apeiron-kit' ),
				],
				'default'     => 'none',
				'description' => __( 'Gerak halus terus-menerus, terpisah dari scroll. Cocok untuk bunga dan daun.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'idle_amount',
			[
				'label'     => __( 'Kekuatan', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 200,
				'step'      => 1,
				'default'   => 10,
				'condition' => [ 'idle_type!' => 'none' ],
			]
		);

		$repeater->add_control(
			'idle_speed',
			[
				'label'     => __( 'Durasi Siklus (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0.5,
				'max'       => 60,
				'step'      => 0.5,
				'default'   => 6,
				'condition' => [ 'idle_type!' => 'none' ],
			]
		);

		$repeater->add_control(
			'layer_wind',
			[
				'label'        => __( 'Bergoyang Saat Scroll', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Melengkung tertiup "angin" sesuai kecepatan scroll lalu memantul balik. Cocok untuk bunga, rumput, daun.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'heading_layer_reveal',
			[
				'label'     => __( 'Animasi Muncul Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [ 'layer_type' => [ 'text', 'container' ] ],
			]
		);

		$repeater->add_control(
			'reveal_split',
			[
				'label'       => __( 'Pembagian Teks', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'none'  => __( 'Tidak dipecah', 'apeiron-kit' ),
					'lines' => __( 'Baris', 'apeiron-kit' ),
					'words' => __( 'Kata', 'apeiron-kit' ),
					'chars' => __( 'Huruf', 'apeiron-kit' ),
				],
				'default'     => 'words',
				'description' => __( 'Aktifkan "Animasi Muncul Teks" pada gerakan yang sesuai di bagian Animasi Layer.', 'apeiron-kit' ),
				'condition'   => [ 'layer_type' => [ 'text', 'container' ] ],
			]
		);

		$repeater->add_control(
			'reveal_style',
			[
				'label'     => __( 'Efek Muncul', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'rise' => __( 'Muncul dari Bawah', 'apeiron-kit' ),
					'fade' => __( 'Muncul perlahan', 'apeiron-kit' ),
					'blur' => __( 'Buram ke Tajam', 'apeiron-kit' ),
				],
				'default'   => 'rise',
				'condition' => [
					'layer_type'    => [ 'text', 'container' ],
					'reveal_split!' => 'none',
				],
			]
		);

		$repeater->add_responsive_control(
			'layer_text_align',
			[
				'label'                => __( 'Perataan Teks Layer', 'apeiron-kit' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'selectors_dictionary' => [
					'left'   => 'text-align: left; --ds-button-align: flex-start;',
					'center' => 'text-align: center; --ds-button-align: center;',
					'right'  => 'text-align: right; --ds-button-align: flex-end;',
				],
				'selectors'            => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-depthscape__text' => '{{VALUE}}',
				],
				'separator'            => 'before',
				'condition'            => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_responsive_control(
			'layer_title_size',
			[
				'label'      => __( 'Ukuran Judul Layer', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'cqi' ],
				'range'      => [
					'px'  => [ 'min' => 12, 'max' => 160 ],
					'em'  => [ 'min' => 0.5, 'max' => 10, 'step' => 0.1 ],
					'cqi' => [ 'min' => 2, 'max' => 40 ],
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-depthscape__title' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'layer_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'layer_text_color',
			[
				'label'     => __( 'Warna Teks Layer', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .apeiron-depthscape__text, {{WRAPPER}} {{CURRENT_ITEM}} .apeiron-depthscape__text > :not(.apeiron-depthscape__button)' => 'color: {{VALUE}};',
				],
				'condition' => [ 'layer_type' => 'text' ],
			]
		);

		if ( $this->supports_native_content() ) {
			$this->add_control( 'add_content_layer', [
				'type' => Controls_Manager::BUTTON,
				'text' => __( 'Tambah Layer Container', 'apeiron-kit' ),
				'event' => 'apeiron:depthscape:add-content',
			] );
			$this->add_control( 'preview_content_motion', [
				'type' => Controls_Manager::BUTTON,
				'text' => __( 'Pratinjau Animasi', 'apeiron-kit' ),
				'event' => 'apeiron:depthscape:preview-content',
			] );
		}

		$this->add_control(
			'layers',
			[
				'label'       => __( 'Daftar Layer', 'apeiron-kit' ),
				'type'        => 'apeiron_depthscape_layers',
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ layer_key }}} · {{{ (' . wp_json_encode( [
					'image'     => __( 'Gambar', 'apeiron-kit' ),
					'video'     => __( 'Video', 'apeiron-kit' ),
					'text'      => __( 'Teks', 'apeiron-kit' ),
					'template'  => __( 'Template', 'apeiron-kit' ),
					'shape'     => __( 'Bentuk', 'apeiron-kit' ),
					'container' => __( 'Container', 'apeiron-kit' ),
				] ) . ')[layer_type] || layer_type }}}',
				'default'     => $this->get_default_layers(),
			]
		);

		$this->end_controls_section();
	}

	private function register_scene_controls(): void {
		$this->start_controls_section(
			'section_scenes',
			[
				'label' => __( 'Adegan & Kamera', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'scenes_intro',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Setiap adegan = satu bagian scroll. Isi nilai kamera yang ingin dicapai di akhir adegan; kosongkan untuk mempertahankan nilai sebelumnya.', 'apeiron-kit' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_control(
			'heading_camera_start',
			[
				'label' => __( 'Kamera Awal', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		foreach ( $this->camera_fields() as $suffix => $field ) {
			$this->add_control(
				'cam_start_' . $suffix,
				[
					'label'   => $field['label'],
					'type'    => Controls_Manager::NUMBER,
					'min'     => $field['min'],
					'max'     => $field['max'],
					'step'    => $field['step'],
					'default' => 0,
				]
			);
		}

		$repeater = new Repeater();

		$repeater->add_control(
			'scene_label',
			[
				'label'   => __( 'Nama Adegan', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Adegan', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'scene_length',
			[
				'label'       => __( 'Panjang Scroll', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'vh' ],
				'range'       => [
					'vh' => [ 'min' => 20, 'max' => 400, 'step' => 5 ],
				],
				'default'     => [ 'size' => 100, 'unit' => 'vh' ],
				'description' => __( '100 = satu layar penuh. Makin panjang, makin pelan adegannya.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'scene_ease',
			[
				'label'   => __( 'Pola Gerakan Kamera', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => SceneConfigBuilder::ease_options(),
				'default' => 'power2.inOut',
			]
		);

		$repeater->add_control(
			'heading_scene_camera',
			[
				'label'     => __( 'Kamera di Akhir Adegan', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		foreach ( $this->camera_fields() as $suffix => $field ) {
			$repeater->add_control(
				'cam_' . $suffix,
				[
					'label'       => $field['label'],
					'type'        => Controls_Manager::NUMBER,
					'min'         => $field['min'],
					'max'         => $field['max'],
					'step'        => $field['step'],
					'placeholder' => __( 'tetap', 'apeiron-kit' ),
				]
			);
		}

		$this->add_control(
			'scenes',
			[
				'label'       => __( 'Daftar Adegan', 'apeiron-kit' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ scene_label }}}',
				'default'     => $this->get_default_scenes(),
			]
		);

		$this->end_controls_section();
	}

	private function register_keyframe_controls(): void {
		$this->start_controls_section(
			'section_keyframes',
			[
				'label' => __( 'Animasi Layer', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'keyframes_intro',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Atur animasi setiap layer pada adegan tertentu. Kosongkan nilai yang tidak ingin diubah.', 'apeiron-kit' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'kf_layer',
			[
				'label'       => __( 'ID Layer Tujuan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'layer',
				'description' => __( 'Isi dengan ID Layer dari bagian Layer & Konten.', 'apeiron-kit' ),
			]
		);

		$repeater->add_control(
			'kf_scene',
			[
				'label'   => __( 'Nomor Adegan', 'apeiron-kit' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 99,
				'step'    => 1,
				'default' => 1,
			]
		);

		$repeater->add_control(
			'kf_at',
			[
				'label'      => __( 'Mulai dalam Adegan', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => 0, 'max' => 99 ],
				],
				'default'    => [ 'size' => 0, 'unit' => '%' ],
			]
		);

		$repeater->add_control(
			'kf_duration',
			[
				'label'      => __( 'Durasi Animasi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => 1, 'max' => 100 ],
				],
				'default'    => [ 'size' => 100, 'unit' => '%' ],
			]
		);

		$repeater->add_control(
			'kf_ease',
			[
				'label'   => __( 'Pola Gerakan', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => SceneConfigBuilder::ease_options(),
				'default' => 'power2.inOut',
			]
		);

		$repeater->add_control(
			'heading_kf_target',
			[
				'label'     => __( 'Target Animasi', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$targets = [
			'kf_x'       => [ __( 'Geser X (% bingkai)', 'apeiron-kit' ), -500, 500, 1 ],
			'kf_y'       => [ __( 'Geser Y (% bingkai)', 'apeiron-kit' ), -500, 500, 1 ],
			'kf_scale'   => [ __( 'Skala', 'apeiron-kit' ), 0, 20, 0.05 ],
			'kf_rotate'  => [ __( 'Rotasi (°)', 'apeiron-kit' ), -720, 720, 1 ],
			'kf_opacity' => [ __( 'Opasitas (0–1)', 'apeiron-kit' ), 0, 1, 0.05 ],
			'kf_blur'    => [ __( 'Blur (px)', 'apeiron-kit' ), 0, 40, 0.5 ],
		];

		foreach ( $targets as $key => $field ) {
			$repeater->add_control(
				$key,
				[
					'label'       => $field[0],
					'type'        => Controls_Manager::NUMBER,
					'min'         => $field[1],
					'max'         => $field[2],
					'step'        => $field[3],
					'placeholder' => __( 'tetap', 'apeiron-kit' ),
				]
			);
		}

		$repeater->add_control(
			'kf_reveal',
			[
				'label'        => __( 'Animasi Muncul Teks', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
				'description'  => __( 'Untuk layer teks atau container: huruf/kata/baris pada teks, Heading, dan paragraf Text Editor muncul berurutan selama gerakan ini.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'keyframes',
			[
				'label'       => __( 'Daftar Animasi', 'apeiron-kit' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ kf_layer }}} · #{{{ kf_scene }}}',
				'default'     => $this->get_default_keyframes(),
			]
		);

		$this->end_controls_section();
	}

	private function register_motion_controls(): void {
		$this->start_controls_section(
			'section_motion',
			[
				'label' => __( 'Pengaturan Animasi', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'smoothness',
			[
				'label'       => __( 'Kehalusan Scroll', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 3, 'step' => 0.1 ],
				],
				'default'     => [ 'size' => 1.1 ],
				'description' => __( 'Waktu (detik) animasi mengejar posisi scroll. 0 = langsung, 1–1.5 = sinematik.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'snap',
			[
				'label'        => __( 'Berhenti di Tiap Adegan', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Setelah user berhenti scroll, kamera meluncur halus ke adegan terdekat.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'snap_ease',
			[
				'label'     => __( 'Pola Berhenti', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => SceneConfigBuilder::ease_options(),
				'default'   => 'power2.inOut',
				'condition' => [ 'snap' => 'yes' ],
			]
		);

		$this->add_control(
			'stay_fixed',
			[
				'label'        => __( 'Tetap di Belakang Konten Berikutnya', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Setelah adegan terakhir, bingkai diam sebagai latar dan konten di bawah widget (countdown, RSVP, galeri) bergulir di atasnya. Beri konten itu latar semi-transparan. Container widget ini sebaiknya tanpa warna latar.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'cover_autoplay',
			[
				'label'        => __( 'Mainkan Adegan 1 Setelah Sampul Dibuka', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Saat widget Sampul dibuka dan Paralaks ada di awal halaman, kamera langsung meluncur ke akhir adegan pertama.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'perspective',
			[
				'label'       => __( 'Perspektif Kamera', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [ 'min' => 300, 'max' => 3000, 'step' => 50 ],
				],
				'default'     => [ 'size' => 1000 ],
				'description' => __( 'Makin kecil, efek 3D makin kuat.', 'apeiron-kit' ),
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'depth_compensation',
			[
				'label'        => __( 'Jaga Ukuran Asli Layer', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Layer jauh tidak mengecil di posisi awal; kedalaman hanya memengaruhi parallax.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'idle_enabled',
			[
				'label'        => __( 'Aktifkan Gerakan Berulang', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'wind_strength',
			[
				'label'       => __( 'Kekuatan Goyangan Scroll (°)', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 20, 'step' => 0.5 ],
				],
				'default'     => [ 'size' => 7 ],
				'description' => __( 'Seberapa jauh layer "Bergoyang Saat Scroll" melengkung saat scroll cepat. 0 = mati.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'camera_breath',
			[
				'label'       => __( 'Gerakan Halus Kamera', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 10, 'step' => 0.5 ],
				],
				'default'     => [ 'size' => 0 ],
				'description' => __( 'Kamera "bernapas" sangat halus agar adegan tidak terasa beku saat diam. 0 = mati (paling ringan).', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'pointer_tilt',
			[
				'label'        => __( 'Kamera Ikut Kursor', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'gyro_tilt',
			[
				'label'        => __( 'Kamera Ikut Kemiringan HP', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Di iPhone, izin sensor diminta saat tamu membuka Sampul atau mengetuk bingkai.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'tilt_strength',
			[
				'label'      => __( 'Kekuatan Kemiringan (°)', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 15, 'step' => 0.5 ],
				],
				'default'    => [ 'size' => 4 ],
				'conditions' => [
					'relation' => 'or',
					'terms'    => [
						[ 'name' => 'pointer_tilt', 'operator' => '===', 'value' => 'yes' ],
						[ 'name' => 'gyro_tilt', 'operator' => '===', 'value' => 'yes' ],
					],
				],
			]
		);

		$preview_options = [
			''  => __( 'Interaktif (ikut scroll)', 'apeiron-kit' ),
			'0' => __( 'Tampilan Awal', 'apeiron-kit' ),
		];
		for ( $scene = 1; $scene <= 12; $scene++ ) {
			/* translators: %d: scene number. */
			$preview_options[ (string) $scene ] = sprintf( __( 'Akhir adegan %d', 'apeiron-kit' ), $scene );
		}

		$this->add_control(
			'editor_preview',
			[
				'label'       => __( 'Mode Pratinjau Editor', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $preview_options,
				'default'     => '',
				'separator'   => 'before',
				'description' => __( 'Pilih Interaktif untuk menguji animasi dengan scroll, atau pilih adegan untuk menahan pratinjau pada posisi tertentu. Hanya berlaku di editor.', 'apeiron-kit' ),
			]
		);

		$this->end_controls_section();
	}

	private function register_particle_controls(): void {
		$this->start_controls_section(
			'section_particles',
			[
				'label' => __( 'Efek Partikel', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'particle_count',
			[
				'label'       => __( 'Jumlah Partikel', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 60, 'step' => 1 ],
				],
				'default'     => [ 'size' => 0 ],
				'description' => __( 'Serbuk/kelopak melayang di berbagai kedalaman sehingga parallax terasa 3D. 0 = mati (paling ringan).', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'particle_style',
			[
				'label'     => __( 'Bentuk', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'pollen' => __( 'Serbuk cahaya', 'apeiron-kit' ),
					'petal'  => __( 'Kelopak bunga', 'apeiron-kit' ),
					'bokeh'  => __( 'Bokeh besar', 'apeiron-kit' ),
				],
				'default'   => 'pollen',
				'condition' => [ 'particle_count[size]!' => 0 ],
			]
		);

		$this->add_control(
			'particle_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fff3b0',
				'condition' => [ 'particle_count[size]!' => 0 ],
			]
		);

		$this->add_control(
			'particle_size',
			[
				'label'     => __( 'Ukuran (px)', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [ 'min' => 2, 'max' => 40, 'step' => 1 ],
				],
				'default'   => [ 'size' => 6 ],
				'condition' => [ 'particle_count[size]!' => 0 ],
			]
		);

		$this->end_controls_section();
	}

	private function register_hint_controls(): void {
		$this->start_controls_section(
			'section_hint',
			[
				'label' => __( 'Petunjuk Gulir', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'show_hint',
			[
				'label'        => __( 'Tampilkan Petunjuk', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'hint_mode',
			[
				'label'     => __( 'Waktu Tampil', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'after_first' => __( 'Setelah adegan 1 sampai adegan terakhir', 'apeiron-kit' ),
					'start'       => __( 'Di awal, hilang saat mulai scroll', 'apeiron-kit' ),
				],
				'default'   => 'after_first',
				'condition' => [ 'show_hint' => 'yes' ],
			]
		);

		$this->add_control(
			'hint_text',
			[
				'label'     => __( 'Teks Petunjuk', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'default'   => __( 'Swipe up', 'apeiron-kit' ),
				'condition' => [ 'show_hint' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Add a NUMBER control to a repeater.
	 */
	private function add_number_pair( Repeater $repeater, string $key, string $label, float $default, float $min, float $max, float $step ): void {
		$repeater->add_control(
			$key,
			[
				'label'   => $label,
				'type'    => Controls_Manager::NUMBER,
				'min'     => $min,
				'max'     => $max,
				'step'    => $step,
				'default' => $default,
			]
		);
	}

	/**
	 * Layers placed freely (not full-cover images) expose position and width.
	 *
	 * @return array<string,mixed>
	 */
	private function free_layer_conditions(): array {
		return [
			'relation' => 'or',
			'terms'    => [
				[ 'name' => 'layer_type', 'operator' => '!in', 'value' => [ 'image', 'video', 'shape' ] ],
				[ 'name' => 'fit', 'operator' => '!==', 'value' => 'cover' ],
			],
		];
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function camera_fields(): array {
		return [
			'x'  => [ 'label' => __( 'Geser Kamera X (% bingkai)', 'apeiron-kit' ), 'min' => -200, 'max' => 200, 'step' => 1 ],
			'y'  => [ 'label' => __( 'Geser Kamera Y (% bingkai)', 'apeiron-kit' ), 'min' => -200, 'max' => 200, 'step' => 1 ],
			'z'  => [ 'label' => __( 'Zoom Kamera Z (px)', 'apeiron-kit' ), 'min' => -3000, 'max' => 2000, 'step' => 10 ],
			'rx' => [ 'label' => __( 'Miring Atas/Bawah (°)', 'apeiron-kit' ), 'min' => -60, 'max' => 60, 'step' => 0.5 ],
			'ry' => [ 'label' => __( 'Putar Kiri/Kanan (°)', 'apeiron-kit' ), 'min' => -60, 'max' => 60, 'step' => 0.5 ],
			'rz' => [ 'label' => __( 'Rotasi Bidang (°)', 'apeiron-kit' ), 'min' => -180, 'max' => 180, 'step' => 0.5 ],
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function get_template_options(): array {
		$options = [ '' => __( '— Pilih template —', 'apeiron-kit' ) ];

		$templates = get_posts(
			[
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);

		foreach ( $templates as $template ) {
			$options[ (string) $template->ID ] = '' !== $template->post_title ? $template->post_title : '#' . $template->ID;
		}

		return $options;
	}

	/**
	 * Default stack = the cinematic invitation arrangement with image slots only (no text, no media):
	 * world planes back to front plus a full-screen cover photo. Empty slots show as labelled outlines
	 * in the editor and render nothing on the live site. No idle loops by default: foliage moves with
	 * scroll wind only, so a resting page does no per-frame work.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_default_layers(): array {
		$none = [ 'url' => '', 'id' => '' ];
		$px   = static fn( float $size ): array => [ 'size' => $size, 'unit' => 'px' ];
		$pct  = static fn( float $size ): array => [ 'size' => $size, 'unit' => '%' ];

		$world = static function ( string $key, array $extra ) use ( $none ): array {
			return array_merge( [ 'layer_key' => $key, 'layer_type' => 'image', 'layer_space' => 'world', 'image' => $none ], $extra );
		};

		return [
			// Depths reproduce the reference scale ratios on a perspective of 1000.
			$world( 'latar', [ 'fit' => 'cover', 'depth' => $px( -2000 ), 'overscan' => $pct( 12 ) ] ),
			$world( 'semak', [ 'depth' => $px( -670 ), 'anchor' => 'bottom', 'pos_x' => $pct( 50 ), 'pos_y' => $pct( 103 ), 'width' => $pct( 166.7 ), 'layer_wind' => 'yes' ] ),
			$world( 'couple', [ 'depth' => $px( 0 ), 'anchor' => 'bottom', 'pos_x' => $pct( 50 ), 'pos_y' => $pct( 88.6 ), 'width' => $pct( 73.3 ) ] ),
			$world( 'bunga-tajam', [ 'depth' => $px( 35 ), 'anchor' => 'bottom', 'pos_x' => $pct( 107.9 ), 'pos_y' => $pct( 105.2 ), 'width' => $pct( 206.7 ), 'layer_wind' => 'yes' ] ),
			$world( 'bunga-blur', [ 'depth' => $px( 190 ), 'anchor' => 'bottom', 'pos_x' => $pct( 90.3 ), 'pos_y' => $pct( 105.2 ), 'width' => $pct( 200 ), 'layer_wind' => 'yes' ] ),
			[ 'layer_key' => 'sampul-foto', 'layer_type' => 'image', 'layer_space' => 'screen', 'image' => $none, 'fit' => 'cover', 'overscan' => $pct( 0 ) ],
		];
	}

	/**
	 * Seven scenes: cover → verse, groom, bride, events, dresscode, quote, closing.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_default_scenes(): array {
		$scene = static function ( string $label, array $camera ): array {
			return array_merge( [ 'scene_label' => $label, 'scene_length' => [ 'size' => 100, 'unit' => 'vh' ], 'scene_ease' => 'power2.inOut' ], $camera );
		};

		return [
			$scene( __( 'Pembuka', 'apeiron-kit' ), [ 'cam_z' => 190, 'cam_y' => 4 ] ),
			$scene( __( 'Mempelai Pria', 'apeiron-kit' ), [ 'cam_z' => 600, 'cam_x' => 12, 'cam_y' => 20 ] ),
			$scene( __( 'Mempelai Wanita', 'apeiron-kit' ), [ 'cam_x' => -10 ] ),
			$scene( __( 'Acara', 'apeiron-kit' ), [ 'cam_z' => 450, 'cam_x' => 58.7, 'cam_y' => 11 ] ),
			$scene( __( 'Dresscode', 'apeiron-kit' ), [] ),
			$scene( __( 'Kutipan', 'apeiron-kit' ), [ 'cam_z' => 190, 'cam_x' => 0, 'cam_y' => 4 ] ),
			$scene( __( 'Penutup', 'apeiron-kit' ), [ 'cam_z' => 267, 'cam_y' => 6 ] ),
		];
	}

	/**
	 * The cover photo dissolves into the 3D world during scene 1; everything else is camera motion.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_default_keyframes(): array {
		return [
			[
				'kf_layer'    => 'sampul-foto',
				'kf_scene'    => 1,
				'kf_at'       => [ 'size' => 0, 'unit' => '%' ],
				'kf_duration' => [ 'size' => 50, 'unit' => '%' ],
				'kf_ease'     => 'power1.inOut',
				'kf_opacity'  => 0,
			],
		];
	}
}
