<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\AutoScroll\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->register_container_style_controls();
		$this->register_button_style_controls();
		$this->register_progress_style_controls();

		$this->register_speed_control_style_controls();
		$this->register_scroll_top_style_controls();
	}

	private function register_container_style_controls(): void {
		$this->start_controls_section(
			'section_style_container',
			[
				'label' => __( 'Container Lanjutan', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'selector' => '{{WRAPPER}} .apeiron-autoscroll-wrap',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .apeiron-autoscroll-wrap',
			]
		);

		$this->add_responsive_control(
			'container_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_shadow',
				'selector' => '{{WRAPPER}} .apeiron-autoscroll-wrap',
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'container_opacity',
			[
				'label'     => __( 'Opacity', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.1, 'max' => 1, 'step' => 0.05 ] ],
				'default'   => [ 'size' => 1 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_button_style_controls(): void {
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => __( 'Tombol Utama', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_size',
			[
				'label'      => __( 'Ukuran', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 24, 'max' => 120 ] ],
				'default'    => [ 'size' => 38, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-scroll-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_icon_size',
			[
				'label'      => __( 'Ukuran Icon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 10, 'max' => 80 ] ],
				'default'    => [ 'size' => 12, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-scroll-btn i, {{WRAPPER}} .apeiron-scroll-btn svg' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab( 'tab_button_normal', [ 'label' => __( 'Normal', 'apeiron-kit' ) ] );

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Warna Icon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-btn' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-scroll-btn svg' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'button_background',
				'selector'       => '{{WRAPPER}} .apeiron-scroll-btn',
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#083c57' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .apeiron-scroll-btn',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 50 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [ 'size' => 50, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-scroll-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_shadow',
				'selector' => '{{WRAPPER}} .apeiron-scroll-btn',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_button_hover', [ 'label' => __( 'Hover', 'apeiron-kit' ) ] );

		$this->add_control(
			'button_hover_color',
			[
				'label'     => __( 'Warna Icon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-btn:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-scroll-btn:hover svg' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'button_hover_background',
				'selector' => '{{WRAPPER}} .apeiron-scroll-btn:hover',
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-btn:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_hover_shadow',
				'selector' => '{{WRAPPER}} .apeiron-scroll-btn:hover',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_button_active', [ 'label' => __( 'Aktif', 'apeiron-kit' ) ] );

		$this->add_control(
			'button_active_color',
			[
				'label'     => __( 'Warna Icon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-btn.is-active' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-scroll-btn.is-active svg' => 'fill: {{VALUE}}; stroke: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'button_active_background',
				'selector'       => '{{WRAPPER}} .apeiron-scroll-btn.is-active',
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#083c57' ],
				],
			]
		);

		$this->add_control(
			'button_active_animation',
			[
				'label'   => __( 'Animasi Aktif', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'none'            => __( 'Tidak Ada', 'apeiron-kit' ),
					'pulse-soft'      => __( 'Pulse Soft', 'apeiron-kit' ),
					'scale-breathing' => __( 'Scale Breathing', 'apeiron-kit' ),
					'micro-bounce'    => __( 'Micro Bounce', 'apeiron-kit' ),
					'smooth-rotate'   => __( 'Smooth Rotate', 'apeiron-kit' ),
					'glow-ring'       => __( 'Glow Ring Subtle', 'apeiron-kit' ),
					'orbit-ring'      => __( 'Orbit Ring', 'apeiron-kit' ),
					'ripple-wave'     => __( 'Ripple Wave', 'apeiron-kit' ),
				],
				'default' => 'pulse-soft',
			]
		);

		$this->add_control(
			'button_active_anim_speed',
			[
				'label'     => __( 'Kecepatan Animasi', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.5, 'max' => 3, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 1.5 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-active-anim-speed: {{SIZE}}s;',
				],
				'condition' => [ 'button_active_animation!' => 'none' ],
			]
		);

		$this->add_control(
			'button_active_anim_color',
			[
				'label'     => __( 'Warna Animasi', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8,60,87,0.3)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-active-anim-color: {{VALUE}};',
				],
				'condition' => [
					'button_active_animation' => [ 'glow-ring', 'orbit-ring', 'ripple-wave' ],
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'button_transition',
			[
				'label'     => __( 'Transisi', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'separator' => 'before',
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
				'default'   => [ 'size' => 0.3 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-btn' => 'transition: background-color {{SIZE}}s cubic-bezier(0.4, 0, 0.2, 1), color {{SIZE}}s cubic-bezier(0.4, 0, 0.2, 1), border-color {{SIZE}}s cubic-bezier(0.4, 0, 0.2, 1), box-shadow {{SIZE}}s cubic-bezier(0.4, 0, 0.2, 1), opacity {{SIZE}}s cubic-bezier(0.4, 0, 0.2, 1), transform {{SIZE}}s cubic-bezier(0.4, 0, 0.2, 1);',
				],
			]
		);

		$this->register_ripple_controls();

		$this->end_controls_section();
	}

	private function register_ripple_controls(): void {
		$this->add_control(
			'heading_button_ripple_effect',
			[
				'label'     => __( 'Ripple Aktif', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ripple_enable',
			[
				'label'        => __( 'Tampilkan Ripple', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Efek cincin aktif saat AutoScroll berjalan. Cocok sebagai feedback visual, bukan wajib.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'ripple_color',
			[
				'label'     => __( 'Warna Ripple', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8,60,87,0.3)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-ripple-color: {{VALUE}};',
				],
				'condition' => [ 'ripple_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'ripple_size',
			[
				'label'      => __( 'Ukuran Ripple', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [ '%' => [ 'min' => 120, 'max' => 300 ] ],
				'default'    => [ 'size' => 180, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-ripple-size: {{SIZE}};',
				],
				'condition' => [ 'ripple_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'ripple_thickness',
			[
				'label'     => __( 'Ketebalan Ripple', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 1, 'max' => 6 ] ],
				'default'   => [ 'size' => 2 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-ripple-thickness: {{SIZE}}px;',
				],
				'condition' => [ 'ripple_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'ripple_speed',
			[
				'label'     => __( 'Kecepatan Ripple', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.5, 'max' => 3, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 1.5 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-ripple-speed: {{SIZE}}s;',
				],
				'condition' => [ 'ripple_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'ripple_opacity',
			[
				'label'     => __( 'Opacity Ripple', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
				'default'   => [ 'size' => 0.6 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-ripple-opacity: {{SIZE}};',
				],
				'condition' => [ 'ripple_enable' => 'yes' ],
			]
		);

		$this->add_control(
			'ripple_mode',
			[
				'label'     => __( 'Mode Ripple', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'infinite',
				'options'   => [
					'single'   => __( 'Single Wave', 'apeiron-kit' ),
					'infinite' => __( 'Infinite Pulse', 'apeiron-kit' ),
					'double'   => __( 'Double Wave', 'apeiron-kit' ),
				],
				'condition' => [ 'ripple_enable' => 'yes' ],
			]
		);
	}

	private function register_progress_style_controls(): void {
		$this->start_controls_section(
			'section_style_progress',
			[
				'label'     => __( 'Indikator Progress', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_progress' => 'yes' ],
			]
		);

		$this->add_control(
			'progress_color',
			[
				'label'     => __( 'Warna Progress', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-progress-ring circle.progress' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .apeiron-progress-bar .bar-fill' => 'background: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-progress-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'progress_bg_color',
			[
				'label'     => __( 'Warna Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8,60,87,0.2)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-progress-ring circle.track' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .apeiron-progress-bar' => 'background: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-progress-bg-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'progress_stroke_width',
			[
				'label'     => __( 'Ketebalan', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 2, 'max' => 10 ] ],
				'default'   => [ 'size' => 3 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-progress-ring circle' => 'stroke-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-progress-stroke-width: {{SIZE}};',
					'{{WRAPPER}} .apeiron-progress-bar' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-progress-bar .bar-fill' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'progress_indicator_size',
			[
				'label'       => __( 'Ukuran Indicator', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', '%' ],
				'range'       => [
					'px' => [ 'min' => 30, 'max' => 150 ],
					'%'  => [ 'min' => 100, 'max' => 300 ],
				],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-progress-ring-size: {{SIZE}}{{UNIT}};',
				],
				'description' => __( 'Mengatur ukuran keseluruhan progress indicator. Kosongkan untuk mengikuti ukuran tombol otomatis.', 'apeiron-kit' ),
				'condition'   => [
					'show_progress' => 'yes',
					'progress_type' => 'circle',
				],
			]
		);

		$this->add_control(
			'progress_stroke_cap',
			[
				'label'     => __( 'Ujung Stroke', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'round',
				'options'   => [
					'round'  => __( 'Round', 'apeiron-kit' ),
					'square' => __( 'Square', 'apeiron-kit' ),
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-progress-ring .progress' => 'stroke-linecap: {{VALUE}};',
				],
				'condition' => [
					'show_progress' => 'yes',
					'progress_type' => 'circle',
				],
			]
		);

		$this->add_control(
			'progress_animation_type',
			[
				'label'     => __( 'Animasi Progress', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => [
					'none'            => __( 'Tidak Ada', 'apeiron-kit' ),
					'linear-clean'    => __( 'Linear Clean', 'apeiron-kit' ),
					'smooth-fill'     => __( 'Smooth Fill', 'apeiron-kit' ),
					'wave-stroke'     => __( 'Wave Stroke', 'apeiron-kit' ),
					'rotating-stroke' => __( 'Rotating Stroke', 'apeiron-kit' ),
					'elastic-stroke'  => __( 'Elastic Stroke', 'apeiron-kit' ),
				],
				'separator' => 'before',
				'condition' => [
					'show_progress' => 'yes',
					'progress_type' => 'circle',
				],
			]
		);

		$this->add_responsive_control(
			'progress_animation_speed',
			[
				'label'     => __( 'Kecepatan Animasi', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.5, 'max' => 5, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 1.5 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-progress-animation-speed: {{SIZE}}s;',
				],
				'condition' => [
					'show_progress'            => 'yes',
					'progress_type'            => 'circle',
					'progress_animation_type!' => 'none',
				],
			]
		);

		$this->end_controls_section();
	}



	private function register_speed_control_style_controls(): void {
		$this->start_controls_section(
			'section_style_speed_container',
			[
				'label'     => __( 'Panel Kecepatan', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_speed_control' => 'yes' ],
			]
		);

		$this->start_controls_tabs( 'tabs_speed_control_style' );

		$this->start_controls_tab( 'tab_speed_panel', [ 'label' => __( 'Panel', 'apeiron-kit' ) ] );

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'speed_control_bg',
				'selector'       => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control',
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#ffffff' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'speed_control_shadow',
				'selector' => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'speed_control_border',
				'selector'       => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control',
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [ 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'unit' => 'px' ],
					],
					'color'  => [ 'default' => '#083C572E' ],
				],
			]
		);

		$this->add_responsive_control(
			'speed_control_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'default'    => [ 'top' => 9, 'right' => 4, 'bottom' => 8, 'left' => 4, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'speed_control_border_radius',
			[
				'label'     => __( 'Border Radius', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
				'default'   => [ 'size' => 30 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'speed_control_gap',
			[
				'label'      => __( 'Jarak ke Tombol Utama', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'default'    => [ 'size' => 20, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-control-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_speed_animation',
			[
				'label'     => __( 'Animasi', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'speed_control_show_animation',
			[
				'label'        => __( 'Tampilkan Animasi', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$animation_options = [
			'fade'       => __( 'Fade', 'apeiron-kit' ),
			'slide'      => __( 'Slide', 'apeiron-kit' ),
			'scale'      => __( 'Scale', 'apeiron-kit' ),
			'bounce'     => __( 'Bounce', 'apeiron-kit' ),
			'zoom'       => __( 'Zoom', 'apeiron-kit' ),
			'flip'       => __( 'Flip', 'apeiron-kit' ),
			'elastic'    => __( 'Elastic', 'apeiron-kit' ),
			'slide-up'   => __( 'Slide Up', 'apeiron-kit' ),
			'slide-down' => __( 'Slide Down', 'apeiron-kit' ),
		];

		$this->add_control(
			'speed_control_appear_animation',
			[
				'label'     => __( 'Animasi Muncul', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $animation_options,
				'default'   => 'scale',
				'condition' => [ 'speed_control_show_animation' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_control_disappear_animation',
			[
				'label'     => __( 'Animasi Keluar', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $animation_options,
				'default'   => 'scale',
				'condition' => [ 'speed_control_show_animation' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_control_animation_duration',
			[
				'label'     => __( 'Durasi Animasi (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.1, 'max' => 2, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 0.4 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-control-animation-duration: {{SIZE}}s;',
				],
				'condition' => [ 'speed_control_show_animation' => 'yes' ],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_speed_slider', [ 'label' => __( 'Slider', 'apeiron-kit' ) ] );

		$this->add_control(
			'speed_slider_color',
			[
				'label'     => __( 'Warna Utama', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-thumb: {{VALUE}}; --ak-slider-track-active: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-webkit-slider-thumb' => 'background: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-moz-range-thumb' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'speed_slider_track_color',
			[
				'label'     => __( 'Warna Track', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e2e8f0',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-track: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-moz-range-track' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'speed_slider_width',
			[
				'label'     => __( 'Panjang Slider', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 40, 'max' => 300 ] ],
				'default'   => [ 'size' => 96 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control.layout-horizontal .apeiron-speed-slider' => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control.layout-vertical .apeiron-speed-slider' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'speed_slider_track_height',
			[
				'label'       => __( 'Ketebalan', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [ 'px' => [ 'min' => 2, 'max' => 12 ] ],
				'default'     => [ 'size' => 4 ],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-track-height: {{SIZE}}{{UNIT}};',
				],
				'description' => __( 'Mengatur ketebalan garis track slider', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'speed_slider_track_radius',
			[
				'label'       => __( 'Radius', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [ 'px' => [ 'min' => 0, 'max' => 20 ] ],
				'default'     => [ 'size' => 20 ],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-track-radius: {{SIZE}}{{UNIT}};',
				],
				'description' => __( 'Mengatur kebulatan ujung track slider', 'apeiron-kit' ),
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_speed_thumb', [ 'label' => __( 'Thumb', 'apeiron-kit' ) ] );

		$this->add_control(
			'speed_slider_thumb_size',
			[
				'label'     => __( 'Ukuran', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 10, 'max' => 30 ] ],
				'default'   => [ 'size' => 12 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-thumb-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-webkit-slider-thumb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-moz-range-thumb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'speed_slider_thumb_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'default'    => [ 'size' => 50, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-thumb-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-webkit-slider-thumb' => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-moz-range-thumb' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'speed_slider_thumb_border_width',
			[
				'label'     => __( 'Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 5, 'step' => 1 ] ],
				'default'   => [ 'size' => 0 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-thumb-border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-webkit-slider-thumb' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-moz-range-thumb' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
				],
			]
		);

		$this->add_control(
			'speed_slider_thumb_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.9)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-slider-thumb-border-color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-webkit-slider-thumb' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-slider::-moz-range-thumb' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_speed_arrows',
			[
				'label'     => __( '+/-', 'apeiron-kit' ),
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->register_speed_arrow_controls();

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_speed_counter', [ 'label' => __( 'Counter', 'apeiron-kit' ) ] );

		$this->register_speed_counter_controls();

		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	private function register_speed_arrow_controls(): void {
		$this->add_responsive_control(
			'speed_control_elements_gap',
			[
				'label'       => __( 'Jarak Antar Elemen', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'em' ],
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 20 ],
					'em' => [ 'min' => 0, 'max' => 2, 'step' => 0.1 ],
				],
				'default'     => [ 'size' => 4, 'unit' => 'px' ],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-inner' => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrows' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'description' => __( 'Mengatur jarak antara tombol +, angka, dan tombol -', 'apeiron-kit' ),
				'condition'   => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'speed_count_arrows_gap',
			[
				'label'      => __( 'Jarak Count ke Tombol +/-', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'default'    => [ 'size' => 12, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control.layout-horizontal' => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control.layout-vertical' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_color',
			[
				'label'     => __( 'Warna Icon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-color: {{VALUE}};',
				],
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_bg',
			[
				'label'     => __( 'Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f1f5f9',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-bg: {{VALUE}};',
				],
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_hover_bg',
			[
				'label'     => __( 'Background Hover', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-hover-bg: {{VALUE}}; --ak-speed-arrow-active-bg: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow:hover' => 'background: {{VALUE}};',
				],
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_hover_color',
			[
				'label'     => __( 'Warna Icon Hover', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-hover-color: {{VALUE}}; --ak-speed-arrow-active-color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow:hover svg' => 'stroke: {{VALUE}}; fill: {{VALUE}}; color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow:hover i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow:hover i::before' => 'color: {{VALUE}};',
				],
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_size',
			[
				'label'      => __( 'Ukuran Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 20, 'max' => 50 ] ],
				'default'    => [ 'size' => 28 ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_icon_size',
			[
				'label'      => __( 'Ukuran Icon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 8, 'max' => 32 ] ],
				'default'    => [ 'size' => 10 ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-icon-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow i' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'speed_arrow_radius',
			[
				'label'     => __( 'Radius Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 24 ] ],
				'default'   => [ 'size' => 24 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'speed_arrow_padding',
			[
				'label'      => __( 'Padding Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [ 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'speed_arrow_border',
				'selector'  => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow',
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'speed_arrow_shadow',
				'selector'  => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-arrow',
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_opacity',
			[
				'label'     => __( 'Opacity Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.1, 'max' => 1, 'step' => 0.05 ] ],
				'default'   => [ 'size' => 1 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-arrow-opacity: {{SIZE}};',
				],
				'condition' => [ 'show_speed_arrows' => 'yes' ],
			]
		);
	}

	private function register_speed_counter_controls(): void {
		$this->add_control(
			'speed_label_color',
			[
				'label'     => __( 'Warna Angka', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-value-color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'speed_label_bg_color',
			[
				'label'     => __( 'Background Angka', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e8f0f4',
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-speed-value-bg: {{VALUE}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'speed_counter_size',
			[
				'label'      => __( 'Ukuran', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 20, 'max' => 60 ] ],
				'default'    => [ 'size' => 26 ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'min-width: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'speed_label_size',
			[
				'label'     => __( 'Ukuran Font', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 8, 'max' => 20 ] ],
				'default'   => [ 'size' => 9 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Keep `font_size` on the dedicated 9px slider to avoid conflicting controls;
		// omit typography defaults so baseline CSS remains until the user opts in.
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'speed_value_typography',
				'label'    => __( 'Tipografi Angka Kecepatan', 'apeiron-kit' ),
				'exclude'  => [ 'font_size' ],
				'selector' => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value',
			]
		);

		$this->add_responsive_control(
			'speed_counter_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 30 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [ 'size' => 23, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'speed_counter_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [ 'top' => 5, 'right' => 5, 'bottom' => 5, 'left' => 5, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'speed_counter_border',
				'selector' => '{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value',
			]
		);

		$this->add_responsive_control(
			'speed_counter_margin',
			[
				'label'      => __( 'Margin', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap .apeiron-speed-control .speed-value' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

	private function register_scroll_top_style_controls(): void {
		$this->start_controls_section(
			'section_style_scroll_top',
			[
				'label'     => __( 'Kembali ke Atas', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_scroll_top' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'scroll_top_size',
			[
				'label'     => __( 'Ukuran Bulatan', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 30, 'max' => 60 ] ],
				'default'   => [ 'size' => 30 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-top-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'scroll_top_icon_size',
			[
				'label'      => __( 'Ukuran Icon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 8, 'max' => 80, 'step' => 1 ] ],
				'default'    => [ 'size' => 12, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-scroll-top-icon-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn' => 'font-size: {{SIZE}}{{UNIT}}; --ak-scroll-top-icon-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn i' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: 1;',
					'{{WRAPPER}} .apeiron-scroll-top-btn i::before' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn > *' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'scroll_top_icon_padding',
			[
				'label'      => __( 'Padding Icon', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-scroll-top-icon-padding-top: {{TOP}}{{UNIT}}; --ak-scroll-top-icon-padding-right: {{RIGHT}}{{UNIT}}; --ak-scroll-top-icon-padding-bottom: {{BOTTOM}}{{UNIT}}; --ak-scroll-top-icon-padding-left: {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn svg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn i' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn > *' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'scroll_top_color',
			[
				'label'     => __( 'Warna Icon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-top-btn' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn svg' => 'stroke: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .apeiron-scroll-top-btn i' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'scroll_top_bg',
			[
				'label'     => __( 'Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-scroll-top-btn' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'scroll_top_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'default'    => [ 'size' => 68, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-scroll-top-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'scroll_top_gap',
			[
				'label'      => __( 'Jarak dari Progress', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
				'default'    => [ 'size' => 7, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap' => '--ak-scroll-top-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
