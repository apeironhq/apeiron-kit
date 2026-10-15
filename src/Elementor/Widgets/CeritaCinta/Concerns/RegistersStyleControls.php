<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CeritaCinta\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->start_controls_section(
			'section_style_widget_area',
			[ 'label' => __( 'Area Widget', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_responsive_control(
			'widget_padding',
			[
				'label'      => __( 'Padding Area Widget', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px', 'isLinked' => true ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-cerita-cinta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);
		$this->end_controls_section();
		$this->register_collage_style_controls();
		$this->start_controls_section(
			'section_style_heading',
			[ 'label' => __( 'Heading', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->register_story_text_style( 'heading', __( 'Heading', 'apeiron-kit' ), '.apeiron-cerita-cinta__heading' );
		$this->register_story_text_style( 'subheading', __( 'Subheading', 'apeiron-kit' ), '.apeiron-cerita-cinta__subheading' );
		$this->add_responsive_control(
			'heading_alignment',
			[
				'label'     => __( 'Perataan', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'toggle'    => false,
				'selectors' => [ '{{WRAPPER}} .apeiron-cerita-cinta__header' => 'text-align: {{VALUE}};' ],
			]
		);
		$this->register_story_slider( 'subheading_spacing', __( 'Jarak Judul ke Subjudul', 'apeiron-kit' ), '.apeiron-cerita-cinta__header', 'gap', 0, 60 );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_paper',
			[ 'label' => __( 'Story Card', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$card_selector = '{{WRAPPER}} .apeiron-cerita-cinta__paper, {{WRAPPER}} .apeiron-cerita-cinta__polaroid';
		$this->add_control(
			'paper_color',
			[ 'label' => __( 'Warna Card', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR, 'default' => '#f9fafb', 'selectors' => [ $card_selector => 'background-color: {{VALUE}};' ] ]
		);
		$this->register_story_color( 'tape_color', __( 'Warna Tape', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-tape-color' );
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'paper_border',
				'selector'       => $card_selector,
				'fields_options' => [ 'color' => [ 'default' => '#eaecf0' ] ],
			]
		);
		$this->add_responsive_control(
			'paper_radius',
			[ 'label' => __( 'Border Radius', 'apeiron-kit' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%' ], 'selectors' => [ $card_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[ 'name' => 'paper_shadow', 'selector' => $card_selector ]
		);
		$this->register_story_dimensions( 'paper_padding', __( 'Padding Kertas', 'apeiron-kit' ), '.apeiron-cerita-cinta__paper', 'padding' );
		$this->add_responsive_control(
			'timeline_card_width',
			[
				'label'          => __( 'Lebar Card Timeline', 'apeiron-kit' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ '%', 'px' ],
				'default'        => [ 'size' => 360, 'unit' => 'px' ],
				'mobile_default' => [ 'size' => 100, 'unit' => '%' ],
				'range'          => [ '%' => [ 'min' => 40, 'max' => 100 ], 'px' => [ 'min' => 140, 'max' => 900 ] ],
				'selectors'      => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '--cc-timeline-card-width: {{SIZE}}{{UNIT}};' ],
			]
		);
		$this->add_control(
			'card_entrance_duration',
			[
				'label'      => __( 'Durasi Animasi Masuk', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 's' ],
				'range'      => [ 's' => [ 'min' => 0.1, 'max' => 1.2, 'step' => 0.05 ] ],
				'default'    => [ 'size' => 0.36, 'unit' => 's' ],
				'condition'  => [ 'card_entrance_animation!' => 'none' ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '--cc-entrance-duration: {{SIZE}}s;' ],
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_per_card',
			[ 'label' => __( 'Style Per Cerita', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_story_styles' => 'yes' ] ]
		);
		$this->add_control(
			'per_card_editor',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<div data-apeiron-cerita-cinta-card-styles></div>',
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_photo',
			[ 'label' => __( 'Photo', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->register_story_color( 'polaroid_color', __( 'Warna Bingkai', 'apeiron-kit' ), '.apeiron-cerita-cinta__polaroid', 'background-color' );
		$this->add_responsive_control(
			'photo_width',
			[
				'label'      => __( 'Lebar Foto', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'default'    => [ 'unit' => '%' ],
				'range'      => [ '%' => [ 'min' => 30, 'max' => 100 ], 'px' => [ 'min' => 80, 'max' => 700 ] ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-cerita-cinta__polaroid' => 'width: min({{SIZE}}{{UNIT}}, 100%);' ],
			]
		);
		$this->add_responsive_control(
			'photo_ratio',
			[
				'label'     => __( 'Rasio Foto', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [ '4 / 3' => '4:3', '1 / 1' => '1:1', '3 / 4' => '3:4', '3 / 2' => '3:2' ],
				'default'   => '4 / 3',
				'selectors' => [ '{{WRAPPER}} .apeiron-cerita-cinta__photo' => 'aspect-ratio: {{VALUE}};' ],
			]
		);
		$this->register_story_dimensions( 'polaroid_padding', __( 'Padding Bingkai', 'apeiron-kit' ), '.apeiron-cerita-cinta__polaroid', 'padding' );
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'polaroid_border',
				'selector'       => '{{WRAPPER}} .apeiron-cerita-cinta__polaroid',
				'fields_options' => [
					'border' => [ 'label' => __( 'Border Bingkai', 'apeiron-kit' ) ],
				],
			]
		);
		$this->register_story_dimensions( 'polaroid_radius', __( 'Radius Bingkai', 'apeiron-kit' ), '.apeiron-cerita-cinta__polaroid', 'border-radius' );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[ 'name' => 'polaroid_shadow', 'label' => __( 'Box Shadow Bingkai', 'apeiron-kit' ), 'selector' => '{{WRAPPER}} .apeiron-cerita-cinta__polaroid' ]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'photo_border',
				'selector'       => '{{WRAPPER}} .apeiron-cerita-cinta__photo',
				'fields_options' => [
					'border' => [ 'label' => __( 'Border Foto', 'apeiron-kit' ), 'default' => 'none' ],
					'width'  => [ 'default' => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px', 'isLinked' => true ] ],
					'color'  => [ 'default' => '#eaecf0' ],
				],
			]
		);
		$this->register_story_dimensions( 'photo_radius', __( 'Radius Foto', 'apeiron-kit' ), '.apeiron-cerita-cinta__photo', 'border-radius' );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[ 'name' => 'photo_shadow', 'label' => __( 'Box Shadow Foto', 'apeiron-kit' ), 'selector' => '{{WRAPPER}} .apeiron-cerita-cinta__photo' ]
		);
		$this->register_story_slider( 'photo_rotation', __( 'Kemiringan Foto', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-photo-rotation', -6, 6, [ 'deg' ] );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_ornament',
			[ 'label' => __( 'Ornamen', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->register_story_color( 'ornament_color', __( 'Warna Icon', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-ornament-color' );
		$this->register_story_slider( 'ornament_size', __( 'Ukuran Ornamen', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-ornament-size', 12, 80, [ 'px' ], 20 );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_timeline',
			[ 'label' => __( 'Timeline', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->register_story_color( 'timeline_base_color', __( 'Warna Timeline Base', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-line-base' );
		$this->register_story_color( 'timeline_active_color', __( 'Warna Timeline Active', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-line-active' );
		$this->register_story_slider( 'timeline_width', __( 'Ketebalan Garis', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-line-width', 1, 6, [ 'px' ], 2 );
		$this->add_control(
			'journey_marker_style',
			[
				'label'     => __( 'Penanda Bergerak', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [ 'timeline_skin' => 'journey-icon' ],
			]
		);
		$this->add_responsive_control(
			'journey_marker_size',
			[
				'label'      => __( 'Ukuran Penanda', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [ 'size' => 25, 'unit' => 'px' ],
				'range'      => [ 'px' => [ 'min' => 14, 'max' => 64 ] ],
				'condition'  => [ 'timeline_skin' => 'journey-icon' ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '--cc-marker-size: {{SIZE}}{{UNIT}};' ],
			]
		);
		$this->add_control(
			'journey_marker_color',
			[
				'label'     => __( 'Warna Icon Penanda', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'condition' => [ 'timeline_skin' => 'journey-icon', 'journey_marker_type' => 'icon' ],
				'selectors' => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '--cc-marker-color: {{VALUE}};' ],
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_dot',
			[ 'label' => __( 'Icon', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_control(
			'dot_shape',
			[
				'label'   => __( 'Bentuk Dot', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'circle',
				'options' => [
					'circle'  => __( 'Bulat', 'apeiron-kit' ),
					'square'  => __( 'Kotak', 'apeiron-kit' ),
					'rounded' => __( 'Kotak Membulat', 'apeiron-kit' ),
					'diamond' => __( 'Diamond', 'apeiron-kit' ),
				],
			]
		);
		$this->start_controls_tabs( 'tabs_dot' );
		$this->start_controls_tab( 'tab_dot_base', [ 'label' => __( 'Base', 'apeiron-kit' ) ] );
		$this->register_story_color( 'dot_color', __( 'Warna Icon', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-dot-color' );
		$this->register_story_color( 'dot_background', __( 'Background', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-dot-background' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_dot_active', [ 'label' => __( 'Active', 'apeiron-kit' ) ] );
		$this->register_story_color( 'dot_active_color', __( 'Warna Icon', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-dot-active-color' );
		$this->register_story_color( 'dot_active_background', __( 'Background', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-dot-active-background' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->register_story_slider( 'dot_size', __( 'Ukuran Dot', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-dot-size', 20, 64 );
		$this->register_story_slider( 'icon_size', __( 'Ukuran Icon', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-icon-size', 8, 32 );
		$this->register_story_dimensions( 'dot_radius', __( 'Border Radius', 'apeiron-kit' ), '.apeiron-cerita-cinta__dot', 'border-radius' );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_story_text',
			[ 'label' => __( 'Teks Cerita', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->register_story_text_style( 'date', __( 'Date', 'apeiron-kit' ), '.apeiron-cerita-cinta__date' );
		$this->register_story_text_style( 'story_title', __( 'Story Title', 'apeiron-kit' ), '.apeiron-cerita-cinta__title' );
		$this->register_story_text_style( 'description', __( 'Description', 'apeiron-kit' ), '.apeiron-cerita-cinta__description' );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_spacing',
			[ 'label' => __( 'Spacing', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);
		$this->add_responsive_control(
			'story_width',
			[
				'label'      => __( 'Lebar Maksimum Widget', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 280, 'max' => 1400 ], '%' => [ 'min' => 50, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-cerita-cinta' => 'max-width: {{SIZE}}{{UNIT}};' ],
			]
		);
		$this->register_story_slider( 'header_spacing', __( 'Jarak Heading ke Cerita', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-header-gap', 0, 160 );
		$this->register_story_slider( 'story_spacing', __( 'Jarak Antar Cerita', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-story-gap', 0, 180 );
		$this->add_responsive_control(
			'timeline_spacing',
			[
				'label'          => __( 'Jarak Card ke Timeline', 'apeiron-kit' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px' ],
				'default'        => [ 'unit' => 'px' ],
				'mobile_default' => [ 'size' => 12, 'unit' => 'px' ],
				'range'          => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'selectors'      => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '--cc-timeline-gap: {{SIZE}}{{UNIT}};' ],
			]
		);
		$this->register_story_slider( 'content_spacing', __( 'Jarak Isi Card', 'apeiron-kit' ), '.apeiron-cerita-cinta__paper', 'gap', 0, 48 );
		$this->end_controls_section();
	}

	private function register_collage_style_controls(): void {
		// Keep responsive CSS available; the editor filters controls for the active device's skin.
		$this->start_controls_section(
			'section_style_collage',
			[
				'label'      => __( 'Scrapbook Collage', 'apeiron-kit' ),
				'tab'        => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'collage_photo_side',
			[
				'label'                => __( 'Posisi Foto', 'apeiron-kit' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'alternate',
				'options'              => [ 'left' => __( 'Kiri', 'apeiron-kit' ), 'right' => __( 'Kanan', 'apeiron-kit' ), 'alternate' => __( 'Bergantian', 'apeiron-kit' ) ],
				'selectors'            => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '{{VALUE}}' ],
				'selectors_dictionary' => [
					'left'      => '--cc-collage-side: left; --cc-side-measure: 0px;',
					'right'     => '--cc-collage-side: right; --cc-side-measure: 1px;',
					'alternate' => '--cc-collage-side: alternate; --cc-side-measure: 2px;',
				],
			]
		);
		$this->register_story_slider( 'collage_width', __( 'Lebar Komposisi', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-width', 280, 1200, [ 'px' ], 360 );
		$this->add_responsive_control(
			'collage_photo_ratio',
			[
				'label'     => __( 'Rasio Foto Collage', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1 / 1',
				'options'   => [ '3 / 4' => '3:4', '4 / 5' => '4:5', '1 / 1' => '1:1', '4 / 3' => '4:3' ],
				'selectors' => [ '{{WRAPPER}} .apeiron-cerita-cinta.apeiron-cerita-cinta--skin-scrapbook-collage .apeiron-cerita-cinta__photo' => 'aspect-ratio: {{VALUE}};' ],
			]
		);
		$this->register_story_slider( 'collage_overlap', __( 'Tumpukan Foto dan Kertas', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-overlap', 0, 120, [ 'px' ], 56 );
		$this->register_story_slider( 'collage_paper_offset', __( 'Offset Vertikal Kertas', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-paper-offset', -24, 80, [ 'px' ], 16 );
		$this->register_story_slider( 'collage_paper_rotation', __( 'Kemiringan Kertas', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-paper-rotation', -6, 6, [ 'deg' ], 2 );
		$this->register_story_slider( 'collage_story_gap', __( 'Jarak Antar Komposisi', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-gap', 0, 160, [ 'px' ], 64 );
		$this->add_control(
			'collage_palette',
			[
				'label'                => __( 'Palet Kertas', 'apeiron-kit' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'story',
				'options'              => [ 'pastel' => __( 'Pastel Bergantian', 'apeiron-kit' ), 'story' => __( 'Ikuti Story Card', 'apeiron-kit' ) ],
				'selectors'            => [ '{{WRAPPER}} .apeiron-cerita-cinta' => '{{VALUE}}' ],
				'selectors_dictionary' => [
					'pastel' => '--cc-collage-background-odd: var(--cc-collage-color-odd); --cc-collage-background-even: var(--cc-collage-color-even);',
					'story'  => '--cc-collage-background-odd: inherit; --cc-collage-background-even: inherit;',
				],
			]
		);
		$this->register_story_color( 'collage_color_odd', __( 'Warna Kertas Ganjil', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-color-odd', [ 'collage_palette' => 'pastel' ] );
		$this->register_story_color( 'collage_color_even', __( 'Warna Kertas Genap', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-color-even', [ 'collage_palette' => 'pastel' ] );
		$this->register_story_color( 'collage_canvas_color', __( 'Warna Latar Collage', 'apeiron-kit' ), '.apeiron-cerita-cinta', '--cc-collage-canvas' );
		$this->end_controls_section();
	}

	private function register_story_text_style( string $name, string $label, string $selector ): void {
		$this->add_control(
			$name . '_style_heading',
			[ 'label' => $label, 'type' => Controls_Manager::HEADING, 'separator' => 'before' ]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => $name . '_typography',
				'selector'       => '{{WRAPPER}} ' . $selector,
				'fields_options' => [ 'font_family' => [ 'default' => 'Rubik' ] ],
			]
		);
		$this->register_story_color( $name . '_color', __( 'Warna', 'apeiron-kit' ), $selector, 'color' );
	}

	private function register_story_color( string $name, string $label, string $selector, string $property, array $condition = [] ): void {
		$defaults = [
			'heading_color'         => '#1d2939',
			'subheading_color'      => '#475467',
			'paper_color'           => '#f9fafb',
			'tape_color'            => '#eaecf0',
			'polaroid_color'        => '',
			'ornament_color'        => '#083c57',
			'collage_color_odd'     => '#f5e3e7',
			'collage_color_even'    => '#e4eef5',
			'collage_canvas_color'  => 'rgba(0, 0, 0, 0)',
			'timeline_base_color'   => '#eaecf0',
			'timeline_active_color' => '#083c57',
			'dot_color'             => '#475467',
			'dot_background'        => '#f9fafb',
			'dot_active_color'      => '#ffffff',
			'dot_active_background' => '#083c57',
			'date_color'            => '#083c57',
			'story_title_color'     => '#1d2939',
			'description_color'     => '#475467',
		];

		$this->add_control(
			$name,
			[
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'default'   => $defaults[ $name ] ?? '',
				'condition' => $condition,
				'selectors' => [ '{{WRAPPER}} ' . $selector => $property . ': {{VALUE}};' ],
			]
		);
	}

	private function register_story_slider( string $name, string $label, string $selector, string $property, int $min, int $max, array $units = [ 'px' ], ?int $default_size = null ): void {
		$default = [ 'unit' => $units[0] ];
		if ( null !== $default_size ) {
			$default['size'] = $default_size;
		}
		$this->add_responsive_control(
			$name,
			[
				'label'      => $label,
				'type'       => Controls_Manager::SLIDER,
				'size_units' => $units,
				'default'    => $default,
				'range'      => [ $units[0] => [ 'min' => $min, 'max' => $max ] ],
				'selectors'  => [ '{{WRAPPER}} ' . $selector => $property . ': {{SIZE}}{{UNIT}};' ],
			]
		);
	}

	private function register_story_dimensions( string $name, string $label, string $selector, string $property ): void {
		$this->add_responsive_control(
			$name,
			[
				'label'      => $label,
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [ '{{WRAPPER}} ' . $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);
	}
}
