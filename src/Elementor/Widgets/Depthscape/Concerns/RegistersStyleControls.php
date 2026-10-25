<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Depthscape\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->register_stage_style_controls();
		$this->register_overlay_style_controls();
		$this->register_text_style_controls();
		$this->register_panel_style_controls();
		$this->register_button_style_controls();
		$this->register_hint_style_controls();
	}

	private function register_stage_style_controls(): void {
		$this->start_controls_section(
			'section_style_stage',
			[
				'label' => __( 'Bingkai', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'stage_height',
			[
				'label'      => __( 'Tinggi Bingkai', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'vh', 'px' ],
				'range'      => [
					'vh' => [ 'min' => 30, 'max' => 100 ],
					'px' => [ 'min' => 240, 'max' => 1600 ],
				],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-depthscape' => '--ds-stage-height: {{SIZE}}{{UNIT}};',
				],
				'description' => __( 'Kosongkan untuk tinggi layar penuh yang akurat di HP.', 'apeiron-kit' ),
			]
		);

		$this->add_responsive_control(
			'stage_max_width',
			[
				'label'       => __( 'Lebar Maksimal (Bingkai)', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', '%', 'vw' ],
				'range'       => [
					'px' => [ 'min' => 280, 'max' => 2400 ],
					'%'  => [ 'min' => 20, 'max' => 100 ],
					'vw' => [ 'min' => 20, 'max' => 100 ],
				],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-depthscape__stage' => 'max-width: {{SIZE}}{{UNIT}};',
				],
				'description' => __( 'Contoh 480px untuk tampilan bingkai HP di desktop.', 'apeiron-kit' ),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'stage_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-depthscape__stage',
			]
		);

		$this->add_responsive_control(
			'stage_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__stage' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'stage_shadow',
				'selector' => '{{WRAPPER}} .apeiron-depthscape__stage',
			]
		);

		$this->end_controls_section();
	}

	private function register_overlay_style_controls(): void {
		$this->start_controls_section(
			'section_style_overlay',
			[
				'label' => __( 'Lapisan Warna', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'overlay_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Lapisan warna di atas semua layer, cocok untuk gradasi bawah agar teks terbaca.', 'apeiron-kit' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'overlay_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-depthscape__overlay',
			]
		);

		$this->add_control(
			'overlay_opacity',
			[
				'label'     => __( 'Opasitas', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ],
				],
				'default'   => [ 'size' => 1 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__overlay' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_text_style_controls(): void {
		$this->start_controls_section(
			'section_style_text',
			[
				'label' => __( 'Teks', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'text_align',
			[
				'label'     => __( 'Perataan', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__text' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'text_gap',
			[
				'label'      => __( 'Jarak Antar Teks', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 60 ],
				],
				'default'    => [ 'size' => 8, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__text' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$parts = [
			'eyebrow'  => [ __( 'Teks di Atas Judul', 'apeiron-kit' ), '.apeiron-depthscape__eyebrow' ],
			'title'    => [ __( 'Judul', 'apeiron-kit' ), '.apeiron-depthscape__title' ],
			'subtitle' => [ __( 'Subjudul', 'apeiron-kit' ), '.apeiron-depthscape__subtitle' ],
			'body'     => [ __( 'Isi Teks', 'apeiron-kit' ), '.apeiron-depthscape__body' ],
		];

		foreach ( $parts as $key => $part ) {
			$this->add_control(
				'heading_text_' . $key,
				[
					'label'     => $part[0],
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$this->add_control(
				$key . '_color',
				[
					'label'     => __( 'Warna', 'apeiron-kit' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} ' . $part[1] => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name'     => $key . '_typography',
					'selector' => '{{WRAPPER}} ' . $part[1],
				]
			);

			$this->add_group_control(
				Group_Control_Text_Shadow::get_type(),
				[
					'name'     => $key . '_text_shadow',
					'selector' => '{{WRAPPER}} ' . $part[1],
				]
			);
		}

		$this->end_controls_section();
	}

	private function register_panel_style_controls(): void {
		$this->start_controls_section(
			'section_style_panel',
			[
				'label' => __( 'Panel', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'panel_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-depthscape__text',
			]
		);

		$this->add_control(
			'panel_glass',
			[
				'label'       => __( 'Efek Kaca (Blur Latar)', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 40 ],
				],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-depthscape__text' => '-webkit-backdrop-filter: blur({{SIZE}}px); backdrop-filter: blur({{SIZE}}px);',
				],
				'description' => __( 'Butuh warna latar semi-transparan agar terlihat.', 'apeiron-kit' ),
			]
		);

		$this->add_responsive_control(
			'panel_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'panel_border',
				'selector' => '{{WRAPPER}} .apeiron-depthscape__text',
			]
		);

		$this->add_responsive_control(
			'panel_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'panel_shadow',
				'selector' => '{{WRAPPER}} .apeiron-depthscape__text',
			]
		);

		$this->end_controls_section();
	}

	private function register_button_style_controls(): void {
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => __( 'Tombol', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .apeiron-depthscape__button',
			]
		);

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab(
			'button_tab_normal',
			[
				'label' => __( 'Normal', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_tab_hover',
			[
				'label' => __( 'Hover', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'button_hover_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__button:hover, {{WRAPPER}} .apeiron-depthscape__button:focus-visible' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_background',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__button:hover, {{WRAPPER}} .apeiron-depthscape__button:focus-visible' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'button_border',
				'selector'  => '{{WRAPPER}} .apeiron-depthscape__button',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'button_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_hint_style_controls(): void {
		$this->start_controls_section(
			'section_style_hint',
			[
				'label'     => __( 'Petunjuk Gulir', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_hint' => 'yes' ],
			]
		);

		$this->add_control(
			'hint_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-depthscape__hint' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'hint_typography',
				'selector' => '{{WRAPPER}} .apeiron-depthscape__hint-text',
			]
		);

		$this->add_responsive_control(
			'hint_icon_size',
			[
				'label'      => __( 'Ukuran Ikon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 10, 'max' => 64 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__hint-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hint_offset',
			[
				'label'      => __( 'Jarak dari Bawah', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 200 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-depthscape__hint' => 'bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
