<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SoundscapePlayer\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** State styles share CSS variables emitted by these controls. */
trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->register_placement_style_controls();
		$this->register_effect_style_controls();
		$this->register_icon_style_controls();
		$this->register_button_style_controls();
		$this->register_status_style_controls();
	}

	private function register_placement_style_controls(): void {
		$this->start_controls_section(
			'section_style_placement',
			[
				'label' => __( 'Penempatan', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'position_type',
			[
				'label'     => __( 'Mode', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'fixed',
				'options'   => [
					'relative' => __( 'Posisi Normal', 'apeiron-kit' ),
					'fixed'    => __( 'Tombol Mengambang', 'apeiron-kit' ),
				],
				'selectors' => [
					'{{WRAPPER}}' => 'position: {{VALUE}}; width: auto;',
				],
			]
		);

		$this->add_control(
			'position_v_anchor',
			[
				'label'                => __( 'Acuan Vertikal', 'apeiron-kit' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'top'    => [
						'title' => __( 'Atas', 'apeiron-kit' ),
						'icon'  => 'eicon-v-align-top',
					],
					'bottom' => [
						'title' => __( 'Bawah', 'apeiron-kit' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'default'              => 'bottom',
				'condition'            => [
					'position_type' => 'fixed',
				],
				'selectors_dictionary' => [
					'top'    => 'top: var(--apeiron-pos-y, 40px); bottom: auto;',
					'bottom' => 'bottom: var(--apeiron-pos-y, 40px); top: auto;',
				],
				'selectors'            => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
			]
		);

		$this->add_control(
			'position_h_anchor',
			[
				'label'                => __( 'Acuan Horizontal', 'apeiron-kit' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'left'   => [
						'title' => __( 'Kiri', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center' => [
						'title' => __( 'Tengah', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-center',
					],
					'right'  => [
						'title' => __( 'Kanan', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'default'              => 'center',
				'condition'            => [
					'position_type' => 'fixed',
				],
				'selectors_dictionary' => [
					'left'   => 'left: var(--apeiron-pos-x, 40px); right: auto; transform: none;',
					'center' => 'left: 50%; right: auto; transform: translateX(-50%);',
					'right'  => 'right: var(--apeiron-pos-x, 40px); left: auto; transform: none;',
				],
				'selectors'            => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'position_offset_x',
			[
				'label'      => __( 'Jarak Horizontal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 200 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
					'vw' => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [ 'size' => 40, 'unit' => 'px' ],
				'condition'  => [
					'position_type'       => 'fixed',
					'position_h_anchor!'  => 'center',
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--apeiron-pos-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'position_offset_y',
			[
				'label'      => __( 'Jarak Vertikal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 200 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
					'vh' => [ 'min' => 0, 'max' => 50 ],
				],
				'default'    => [ 'size' => 40, 'unit' => 'px' ],
				'condition'  => [
					'position_type' => 'fixed',
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--apeiron-pos-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'position_zindex',
			[
				'label'     => __( 'Lapisan (Z-Index)', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 9999,
				'selectors' => [
					'{{WRAPPER}}' => 'z-index: {{VALUE}};',
				],
				'condition' => [
					'position_type' => 'fixed',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_effect_style_controls(): void {
		$this->start_controls_section(
			'section_style_effects',
			[
				'label' => __( 'Efek Saat Diputar', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pulse_style',
			[
				'label'   => __( 'Jenis Efek', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'spin',
				'options' => [
					''           => __( 'Tanpa Efek', 'apeiron-kit' ),
					'spin'       => __( 'Cincin Berputar', 'apeiron-kit' ),
					'pulse'      => __( 'Denyut Cincin', 'apeiron-kit' ),
					'spin_pulse' => __( 'Putar + Denyut', 'apeiron-kit' ),
					'beat'       => __( 'Ketukan', 'apeiron-kit' ),
					'glow'       => __( 'Cahaya Halus', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'pulse_color',
			[
				'label'     => __( 'Warna Efek', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'condition' => [
					'pulse_style' => [ 'pulse', 'spin_pulse', 'glow' ],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-effect-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'spin_track_color',
			[
				'label'     => __( 'Warna Trek Cincin', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6f94a5',
				'condition' => [
					'pulse_style' => [ 'spin', 'spin_pulse' ],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-spin-track-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pulse_speed',
			[
				'label'       => __( 'Kecepatan Animasi', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'description' => __( 'Nilai lebih kecil membuat efek bergerak lebih cepat.', 'apeiron-kit' ),
				'default'     => [ 'size' => 3 ],
				'range'       => [
					'px' => [
						'min'  => 0.5,
						'max'  => 5,
						'step' => 0.1,
					],
				],
				'condition'   => [
					'pulse_style!' => '',
				],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-effect-duration: {{SIZE}}s;',
				],
			]
		);

		$this->add_control(
			'spin_direction',
			[
				'label'     => __( 'Arah Putaran', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'normal',
				'options'   => [
					'normal'  => __( 'Searah Jarum Jam', 'apeiron-kit' ),
					'reverse' => __( 'Berlawanan Jarum Jam', 'apeiron-kit' ),
				],
				'condition' => [
					'pulse_style' => [ 'spin', 'spin_pulse' ],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-spin-direction: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'spin_width',
			[
				'label'      => __( 'Ketebalan Cincin Putar', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [ 'size' => 2, 'unit' => 'px' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 6,
						'step' => 1,
					],
				],
				'condition'  => [
					'pulse_style' => [ 'spin', 'spin_pulse' ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-spin-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'pulse_spread',
			[
				'label'     => __( 'Sebaran Efek', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ 'size' => 2.2 ],
				'range'     => [
					'px' => [
						'min'  => 1.2,
						'max'  => 4,
						'step' => 0.1,
					],
				],
				'condition' => [
					'pulse_style' => [ 'pulse', 'spin_pulse', 'glow' ],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-effect-spread: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'pulse_opacity',
			[
				'label'     => __( 'Opasitas Denyut / Cahaya', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ 'size' => 0.5 ],
				'range'     => [
					'px' => [
						'min'  => 0.1,
						'max'  => 1,
						'step' => 0.05,
					],
				],
				'condition' => [
					'pulse_style' => [ 'pulse', 'spin_pulse', 'glow' ],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-effect-opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_icon_style_controls(): void {
		$this->start_controls_section(
			'section_style_icon',
			[
				'label' => __( 'Ikon', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Warna Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-icon-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_hover_color',
			[
				'label'     => __( 'Warna Ikon saat Disorot', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#062f44',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-icon-hover-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_active_color',
			[
				'label'     => __( 'Warna Ikon saat Diputar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-icon-active-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'size',
			[
				'label'     => __( 'Ukuran Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ 'size' => 18, 'unit' => 'px' ],
				'range'     => [
					'px' => [ 'min' => 6, 'max' => 300 ],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rotate',
			[
				'label'     => __( 'Rotasi Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [ 'size' => 0, 'unit' => 'deg' ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-icon i, {{WRAPPER}} .apeiron-soundscape-icon svg' => 'transform: rotate({{SIZE}}{{UNIT}});',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_button_style_controls(): void {
		$this->start_controls_section(
			'section_style_wrapper',
			[
				'label' => __( 'Tombol', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f7f7fb',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_background_color',
			[
				'label'     => __( 'Latar saat Disorot', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#eef6fa',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-hover-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_active_background_color',
			[
				'label'     => __( 'Latar saat Diputar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-active-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_color',
			[
				'label'     => __( 'Warna Garis', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => __( 'Garis saat Disorot', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d6dae5',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-hover-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_active_border_color',
			[
				'label'     => __( 'Garis saat Diputar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-active-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'shape',
			[
				'label'   => __( 'Bentuk', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'circle',
				'options' => [
					'circle' => __( 'Bulat', 'apeiron-kit' ),
					'square' => __( 'Kotak', 'apeiron-kit' ),
				],
			]
		);

		$this->add_responsive_control(
			'button_size',
			[
				'label'      => __( 'Ukuran Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [ 'size' => 42, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 32, 'max' => 120 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_padding',
			[
				'label'      => __( 'Ruang Dalam Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-soundscape-player' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'square_radius',
			[
				'label'      => __( 'Radius Sudut', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'default'    => [ 'size' => 8, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
				],
				'condition'  => [
					'shape' => 'square',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-square-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'wrapper_box_shadow',
				'label'     => __( 'Bayangan Tombol', 'apeiron-kit' ),
				'selector'  => '{{WRAPPER}} .apeiron-soundscape-player',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_transition_duration',
			[
				'label'      => __( 'Durasi Transisi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 's' ],
				'range'      => [
					's' => [ 'min' => 0, 'max' => 2, 'step' => 0.1 ],
				],
				'default'    => [ 'size' => 0.2, 'unit' => 's' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-transition-duration: {{SIZE}}s;',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'button_transition_timing',
			[
				'label'     => __( 'Easing Transisi', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ease',
				'options'   => [
					'linear'      => __( 'Linear', 'apeiron-kit' ),
					'ease'        => __( 'Ease', 'apeiron-kit' ),
					'ease-in'     => __( 'Ease In', 'apeiron-kit' ),
					'ease-out'    => __( 'Ease Out', 'apeiron-kit' ),
					'ease-in-out' => __( 'Ease In Out', 'apeiron-kit' ),
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-transition-timing: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'error_heading',
			[
				'label'     => __( 'Status Error', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'error_background_color',
			[
				'label'     => __( 'Latar saat Error', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fef2f2',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-error-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'error_text_color',
			[
				'label'     => __( 'Warna Ikon saat Error', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#991b1b',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-error-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'error_border_color',
			[
				'label'     => __( 'Garis saat Error', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fecaca',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-error-border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_status_style_controls(): void {
		$this->start_controls_section(
			'section_style_status',
			[
				'label' => __( 'Status', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'status_background_color',
			[
				'label'     => __( 'Warna Latar Status', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-status-bg: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'status_text_color',
			[
				'label'     => __( 'Warna Teks Status', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7890',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-status-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'status_border_color',
			[
				'label'     => __( 'Warna Garis Status', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'selectors' => [
					'{{WRAPPER}} .apeiron-soundscape-player' => '--apeiron-soundscape-status-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'status_font_size',
			[
				'label'      => __( 'Ukuran Teks Status', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'default'    => [ 'size' => 12, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 24 ],
					'em' => [ 'min' => 0.5, 'max' => 2 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-soundscape-status' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Keep font size on its responsive control to avoid competing output.
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'status_typography',
				'label'    => __( 'Tipografi Teks Status', 'apeiron-kit' ),
				'exclude'  => [ 'font_size' ],
				'selector' => '{{WRAPPER}} .apeiron-soundscape-status',
			]
		);

		$this->end_controls_section();
	}
}
