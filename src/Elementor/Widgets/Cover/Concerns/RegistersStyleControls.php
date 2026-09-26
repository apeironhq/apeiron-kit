<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Cover\Concerns;

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
			'section_style_overlay',
			[
				'label' => __( 'Layer dan Background', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'z_index',
			[
				'label'     => __( 'Z-Index', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2147483000,
				'min'       => 1,
				'max'       => 2147483647,
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-z-index: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'background_color',
			[
				'label'     => __( 'Warna Dasar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#bcaf93',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-bg-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'background_tint',
			[
				'label'     => __( 'Overlay Pattern', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.08)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-bg-tint: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pattern_size',
			[
				'label'                => __( 'Ukuran Pattern', 'apeiron-kit' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'auto',
				'options'              => [
					'auto'    => __( 'Auto', 'apeiron-kit' ),
					'cover'   => __( 'Cover', 'apeiron-kit' ),
					'contain' => __( 'Contain', 'apeiron-kit' ),
					'custom'  => __( 'Custom', 'apeiron-kit' ),
				],
				'selectors_dictionary' => [
					'auto'    => 'auto',
					'cover'   => 'cover',
					'contain' => 'contain',
					'custom'  => 'var(--apeiron-cover-pattern-custom-size)',
				],
				'selectors'            => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pattern-size: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'pattern_custom_size',
			[
				'label'      => __( 'Ukuran Custom', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 40, 'max' => 1600 ],
					'%'  => [ 'min' => 10, 'max' => 300 ],
					'vw' => [ 'min' => 10, 'max' => 200 ],
				],
				'default'    => [
					'size' => 520,
					'unit' => 'px',
				],
				'condition'  => [
					'pattern_size' => 'custom',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pattern-custom-size: {{SIZE}}{{UNIT}} auto;',
				],
			]
		);

		$this->add_control(
			'pattern_repeat',
			[
				'label'     => __( 'Repeat Pattern', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'repeat',
				'options'   => [
					'repeat'    => __( 'Repeat', 'apeiron-kit' ),
					'repeat-x'  => __( 'Repeat X', 'apeiron-kit' ),
					'repeat-y'  => __( 'Repeat Y', 'apeiron-kit' ),
					'no-repeat' => __( 'No Repeat', 'apeiron-kit' ),
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pattern-repeat: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_welcome',
			[
				'label' => __( 'Layer Welcome', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'welcome_max_width',
			[
				'label'      => __( 'Lebar Maksimum', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 180, 'max' => 900 ],
					'%'  => [ 'min' => 20, 'max' => 100 ],
					'vw' => [ 'min' => 20, 'max' => 100 ],
				],
				'default'    => [
					'size' => 360,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__welcome-inner' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'welcome_text_color',
			[
				'label'     => __( 'Warna Label Wedding', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4e453f',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__event-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'welcome_typography',
				'label'    => __( 'Tipografi Label Wedding', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-cover__event-label',
			]
		);

		$this->add_responsive_control(
			'event_label_alignment',
			[
				'label'     => __( 'Alignment Label Wedding', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__event-label' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'event_label_spacing',
			[
				'label'      => __( 'Spacing Label Wedding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [ 'top' => 10, 'right' => 0, 'bottom' => 0, 'left' => 0, 'unit' => 'px', 'isLinked' => false ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__event-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'names_typography',
				'label'    => __( 'Tipografi Nama', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-cover__name, {{WRAPPER}} .apeiron-cover__name-separator',
			]
		);

		$this->add_control(
			'names_color',
			[
				'label'     => __( 'Warna Nama Pasangan', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4e453f',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__names' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'names_alignment',
			[
				'label'     => __( 'Alignment Nama Pasangan', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'end'    => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__names' => 'justify-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'names_spacing',
			[
				'label'      => __( 'Spacing Nama Pasangan', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [ 'top' => 14, 'right' => 0, 'bottom' => 0, 'left' => 0, 'unit' => 'px', 'isLinked' => false ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__names' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'event_date_color',
			[
				'label'     => __( 'Warna Tanggal Acara', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4e453f',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__event-date' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'event_date_typography',
				'label'    => __( 'Tipografi Tanggal Acara', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-cover__event-date',
			]
		);

		$this->add_responsive_control(
			'event_date_alignment',
			[
				'label'     => __( 'Alignment Tanggal Acara', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__event-date' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'event_date_spacing',
			[
				'label'      => __( 'Spacing Tanggal Acara', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [ 'top' => 14, 'right' => 0, 'bottom' => 0, 'left' => 0, 'unit' => 'px', 'isLinked' => false ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__event-date' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ornament_size',
			[
				'label'      => __( 'Ukuran Ornamen', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 240 ],
					'vw' => [ 'min' => 2, 'max' => 50 ],
				],
				'default'    => [
					'size' => 58,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__ornament' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'photo_size',
			[
				'label'      => __( 'Ukuran Foto', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 24, 'max' => 320 ],
					'vw' => [ 'min' => 10, 'max' => 90 ],
				],
				'default'    => [
					'size' => 159,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__photo' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'photo_border',
				'selector' => '{{WRAPPER}} .apeiron-cover__photo',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'photo_shadow',
				'selector' => '{{WRAPPER}} .apeiron-cover__photo',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_panels',
			[
				'label' => __( 'Panel Cover', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'panel_background_color',
			[
				'label'     => __( 'Warna Panel', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-panel-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'panel_divider_color',
			[
				'label'     => __( 'Warna Belahan Tengah', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
				'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-panel-divider: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'panel_shadow',
				'selector' => '{{WRAPPER}} .apeiron-cover__panel',
			]
		);

		$this->add_control(
			'center_strip_heading',
			[
				'label'     => __( 'Strip Tengah', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'center_strip_width',
			[
				'label'      => __( 'Lebar Strip', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 1, 'max' => 120 ],
					'vw' => [ 'min' => 2, 'max' => 22 ],
				],
				'default'    => [
					'size' => 9,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 9,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-seam-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'center_strip_gap',
			[
				'label'      => __( 'Jarak Dua Strip', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
					'vw' => [ 'min' => 0, 'max' => 8 ],
				],
				'default'    => [
					'size' => 1,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 1,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-seam-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'center_strip_background',
			[
				'label'     => __( 'Latar Strip', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-center-strip-bg: {{VALUE}}; --apeiron-cover-center-strip-surface: {{VALUE}}; --apeiron-cover-center-mask-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'center_strip_border_color',
			[
				'label'     => __( 'Bayangan Sisi Strip', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-center-strip-border: {{VALUE}}; --apeiron-cover-center-mask-line-color: {{VALUE}}; --apeiron-cover-center-edge-shadow: color-mix(in srgb, {{VALUE}} 10%, transparent);',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_ribbon',
			[
				'label' => __( 'Pita', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'info_top',
			[
				'label'      => __( 'Posisi Vertikal Pita', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px', 'vh' ],
				'range'      => [
					'%'  => [ 'min' => 5, 'max' => 95 ],
					'px' => [ 'min' => 0, 'max' => 1200 ],
					'vh' => [ 'min' => 0, 'max' => 100 ],
				],
				'default'    => [
					'size' => 50,
					'unit' => '%',
				],
				'description' => __( 'Pita dikunci di tengah layar. Logo dan kartu penerima mengikuti pita, bukan sebaliknya.', 'apeiron-kit' ),
				'selectors'   => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-info-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ribbon_height',
			[
				'label'      => __( 'Tinggi Pita', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 20, 'max' => 180 ],
					'vh' => [ 'min' => 2, 'max' => 30 ],
				],
				'default'    => [
					'size' => 68,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 51,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ribbon_color',
			[
				'label'     => __( 'Warna Pita', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4c1337',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ribbon_tail_color',
			[
				'label'     => __( 'Warna Lipatan Pita', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4c1337',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-tail-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ribbon_dash_color',
			[
				'label'     => __( 'Warna Garis Putus', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-dash-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ribbon_dash_width',
			[
				'label'      => __( 'Tebal Garis Putus', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 12 ],
				],
				'default'    => [
					'size' => 3,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-dash-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ribbon_frame_color',
			[
				'label'     => __( 'Warna Aksen Emas', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-frame-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ribbon_edge_width',
			[
				'label'      => __( 'Ketebalan Edge Pita', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 16, 'step' => 0.5 ] ],
				'default'    => [ 'size' => 3.5, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-edge-width: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'ribbon_tail_edge_width',
			[
				'label'      => __( 'Ketebalan Edge Lipatan', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 20, 'step' => 0.5 ] ],
				'default'    => [ 'size' => 7, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-tail-edge-width: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'ribbon_text_color',
			[
				'label'     => __( 'Warna Teks Pita', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.92)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-text-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ribbon_text_typography',
				'label'    => __( 'Tipografi Teks Pita', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-cover__ribbon-name',
			]
		);

		$this->add_responsive_control(
			'ribbon_tail_width',
			[
				'label'      => __( 'Lebar Lipatan', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 40, 'max' => 320 ],
					'vw' => [ 'min' => 8, 'max' => 80 ],
				],
				'default'    => [
					'size' => 40,
					'unit' => 'px',
				],
				'tablet_default' => [
					'size' => 36,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 32,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-tail-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ribbon_tail_height',
			[
				'label'      => __( 'Tinggi Lipatan', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 16, 'max' => 420 ],
					'vh' => [ 'min' => 2, 'max' => 48 ],
				],
				'default'    => [
					'size' => 54,
					'unit' => 'px',
				],
				'tablet_default' => [
					'size' => 49,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 43,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-ribbon-tail-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'ribbon_shadow',
				'selector' => '{{WRAPPER}} .apeiron-cover__ribbon, {{WRAPPER}} .apeiron-cover__ribbon::before, {{WRAPPER}} .apeiron-cover__ribbon::after',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_pin',
			[
				'label' => __( 'Lingkaran Tengah', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pin_size',
			[
				'label'      => __( 'Ukuran Lingkaran', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 72, 'max' => 260 ],
					'vw' => [ 'min' => 16, 'max' => 70 ],
				],
				'default'    => [
					'size' => 128,
					'unit' => 'px',
				],
				'tablet_default' => [
					'size' => 116,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 104,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pin-size: {{SIZE}}{{UNIT}}; --apeiron-cover-pin-min-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pin_background',
			[
				'label'     => __( 'Warna Lingkaran', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3a0c29',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pin-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pin_text_color',
			[
				'label'     => __( 'Warna Monogram', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pin-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pin_ring_color',
			[
				'label'     => __( 'Warna Ring Seal', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3a0c29',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pin-ring-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pin_ring_width',
			[
				'label'      => __( 'Ketebalan Ring Emas', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 30, 'step' => 0.5 ] ],
				'default'    => [ 'size' => 10, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pin-ring-width: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'pin_typography',
				'selector'  => '{{WRAPPER}} .apeiron-cover__monogram',
				'fields_options' => [
					'font_size' => [
						'mobile_default' => [
							'size' => 24,
							'unit' => 'px',
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'pin_image_size',
			[
				'label'      => __( 'Ukuran Logo', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 12, 'max' => 160 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'default'    => [
					'size' => 46,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-pin-image-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'pin_border',
				'selector' => '{{WRAPPER}} .apeiron-cover--seal-circle .apeiron-cover__pin',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'pin_shadow',
				'selector' => '{{WRAPPER}} .apeiron-cover--seal-circle .apeiron-cover__pin',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			[
				'label' => __( 'Tombol Buka', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'button_typography',
				'selector'  => '{{WRAPPER}} .apeiron-cover__open-button',
				'fields_options' => [
					'font_size' => [
						'mobile_default' => [
							'size' => 9.5,
							'unit' => 'px',
						],
					],
				],
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-button-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-button-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_background_color',
			[
				'label'     => __( 'Warna Latar Hover', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-button-hover-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_text_color',
			[
				'label'     => __( 'Warna Teks Hover', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-button-hover-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_transition_duration',
			[
				'label'      => __( 'Durasi Transisi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [ 'min' => 0, 'max' => 1000, 'step' => 10 ],
				],
				'default'    => [
					'size' => 280,
					'unit' => 'ms',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-button-transition-duration: {{SIZE}}ms;',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => 0,
					'right'    => 0,
					'bottom'   => 0,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-button-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => 8,
					'right'    => 8,
					'bottom'   => 8,
					'left'     => 8,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__open-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_recipient',
			[
				'label' => __( 'Kartu Penerima', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'recipient_width',
			[
				'label'      => __( 'Lebar', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 160, 'max' => 520 ],
					'%'  => [ 'min' => 20, 'max' => 100 ],
					'vw' => [ 'min' => 20, 'max' => 100 ],
				],
				'default'    => [
					'size' => 176,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 144,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'recipient_gap',
			[
				'label'      => __( 'Jarak dari Pita', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
					'vh' => [ 'min' => 0, 'max' => 6 ],
				],
				'default'    => [
					'size' => 3,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 2,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-drop: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'recipient_background',
			[
				'label'     => __( 'Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'recipient_border_color',
			[
				'label'     => __( 'Warna Border Kartu', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'recipient_border_width',
			[
				'label'      => __( 'Ketebalan Border Kartu', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 20, 'step' => 0.5 ] ],
				'default'    => [ 'size' => 5, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-border-width: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'recipient_art_radius',
			[
				'label'      => __( 'Radius Kartu', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'default'    => [ 'size' => 34, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-radius: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'recipient_custom_shadow',
			[
				'label'        => __( 'Custom Shadow Kartu', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'selectors' => [
					'{{WRAPPER}} .guest-tag__paper-group' => 'filter: drop-shadow(0 calc(var(--ap-cover-recipient-shadow-y) * 1px) calc(var(--ap-cover-recipient-shadow-blur) * 1px) var(--ap-cover-recipient-shadow-color));',
				],
			]
		);

		$this->add_control(
			'recipient_shadow_color',
			[
				'label'     => __( 'Warna Shadow Kartu', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.16)',
				'condition' => [ 'recipient_custom_shadow' => 'yes' ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-shadow-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'recipient_shadow_blur',
			[
				'label'      => __( 'Blur Shadow Kartu', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'default'    => [ 'size' => 10, 'unit' => 'px' ],
				'condition'  => [ 'recipient_custom_shadow' => 'yes' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-shadow-blur: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'recipient_shadow_vertical',
			[
				'label'      => __( 'Posisi Shadow Kartu', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => -40, 'max' => 40 ] ],
				'default'    => [ 'size' => 10, 'unit' => 'px' ],
				'condition'  => [ 'recipient_custom_shadow' => 'yes' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-recipient-shadow-y: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'recipient_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2c1903',
				'selectors' => [
					'{{WRAPPER}} .apeiron-cover__recipient-card' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'recipient_typography',
				'label'     => __( 'Tipografi Kartu', 'apeiron-kit' ),
				'selector'  => '{{WRAPPER}} .apeiron-cover__recipient-heading, {{WRAPPER}} .apeiron-cover__recipient-name',
				'fields_options' => [
					'font_size' => [
						'mobile_default' => [
							'size' => 12.5,
							'unit' => 'px',
						],
					],
				],
			]
		);

		// Direct selectors override Classic child typography only when configured.
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'recipient_heading_typography',
				'label'    => __( 'Tipografi Judul Kartu', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-cover__recipient-heading',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'recipient_name_typography',
				'label'    => __( 'Tipografi Nama Penerima', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-cover__recipient-name',
			]
		);

		$this->add_responsive_control(
			'recipient_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'      => 2,
					'right'    => 4,
					'bottom'   => 2,
					'left'     => 4,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover__recipient-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
