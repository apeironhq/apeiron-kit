<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\AutoScroll\Concerns;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->register_general_controls();
		$this->register_speed_controls();
		$this->register_button_position_controls();
		$this->register_feedback_controls();
	}

	private function register_general_controls(): void {
		$this->start_controls_section(
			'section_general',
			[
				'label' => __( 'Quick Setup', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'scroll_engine',
			[
				'label'       => __( 'Gaya Gerakan', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'apeiron' => __( 'Halus Berkelanjutan', 'apeiron-kit' ),
					'step'    => __( 'Scroll per Bagian', 'apeiron-kit' ),
				],
				'default'     => 'apeiron',
				'description' => __( 'Halus untuk scroll berkelanjutan. Per bagian untuk gerakan ritmis seperti presentasi.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'scroll_mode',
			[
				'label'       => __( 'Mode Scroll', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'auto'   => __( 'Otomatis', 'apeiron-kit' ),
					'manual' => __( 'Tekan & Tahan', 'apeiron-kit' ),
					'both'   => __( 'Otomatis + Manual', 'apeiron-kit' ),
				],
				'default'     => 'auto',
				'description' => __( 'Pilih cara user menjalankan tombol scroll.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'scroll_direction',
			[
				'label'   => __( 'Arah Scroll', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'down' => __( 'Ke Bawah', 'apeiron-kit' ),
					'up'   => __( 'Ke Atas', 'apeiron-kit' ),
				],
				'default' => 'down',
			]
		);

		$this->add_control(
			'scroll_speed',
			[
				'label'       => __( 'Kecepatan Scroll', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'dynamic'     => [ 'active' => true ],
				'range'       => [
					'px' => [ 'min' => 1, 'max' => 100, 'step' => 1 ],
				],
				'default'     => [ 'size' => 30 ],
				'description' => __( 'Kecepatan utama untuk gerakan halus.', 'apeiron-kit' ),
				'condition'   => [ 'scroll_engine' => 'apeiron' ],
			]
		);

		$this->add_control(
			'step_scroll_speed',
			[
				'label'       => __( 'Kecepatan Scroll', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'dynamic'     => [ 'active' => true ],
				'range'       => [
					'px' => [ 'min' => 1, 'max' => 100, 'step' => 1 ],
				],
				'default'     => [ 'size' => 30 ],
				'description' => __( 'Kecepatan dasar untuk scroll per bagian.', 'apeiron-kit' ),
				'condition'   => [ 'scroll_engine' => 'step' ],
			]
		);

		$this->add_control(
			'auto_start',
			[
				'label'        => __( 'Mulai Otomatis', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Scroll dimulai otomatis saat halaman dimuat', 'apeiron-kit' ),
				'condition'    => [ 'scroll_mode!' => 'manual' ],
			]
		);

		$this->add_control(
			'auto_start_delay',
			[
				'label'     => __( 'Delay Mulai (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 10, 'step' => 0.5 ] ],
				'default'   => [ 'size' => 2 ],
				'condition' => [
					'auto_start'   => 'yes',
					'scroll_mode!' => 'manual',
				],
			]
		);

		$this->add_control(
			'heading_scroll_behavior',
			[
				'label'     => __( 'Perilaku Scroll', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'user_interaction_behavior',
			[
				'label'   => __( 'Interaksi Pengguna', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'continue'            => __( 'Tetap Berjalan', 'apeiron-kit' ),
					'stop_on_interaction' => __( 'Berhenti Saat Interaksi', 'apeiron-kit' ),
				],
				'default' => 'continue',
			]
		);

		$this->add_control(
			'pause_on_interaction',
			[
				'label'        => __( 'Pause Saat Interaksi', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Auto-scroll melambat saat user melakukan scroll manual atau navigasi halaman.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'resume_after_idle',
			[
				'label'        => __( 'Resume Setelah Idle', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Setelah user berhenti scroll manual, autoscroll lanjut lagi dengan akselerasi lembut.', 'apeiron-kit' ),
				'condition'    => [ 'pause_on_interaction' => 'yes' ],
			]
		);

		$this->add_control(
			'resume_idle_delay',
			[
				'label'     => __( 'Lanjut Setelah (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.2, 'max' => 5, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 1.2 ],
				'condition' => [
					'pause_on_interaction' => 'yes',
					'resume_after_idle'    => 'yes',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => __( 'Pause Saat Hover Widget', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Autoscroll melambat saat pointer berada di area widget, lalu lanjut halus saat keluar.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'loop_scroll',
			[
				'label'        => __( 'Loop ke Atas', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Kembali ke atas setelah sampai bawah', 'apeiron-kit' ),
				'condition'    => [ 'scroll_direction' => 'down' ],
			]
		);

		$this->add_control(
			'disable_on_ios',
			[
				'label'        => __( 'Nonaktifkan di iOS', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Auto-scroll mungkin tidak optimal di iOS', 'apeiron-kit' ),
			]
		);

		$this->end_controls_section();
	}

	private function register_speed_controls(): void {
		$this->start_controls_section(
			'section_speed',
			[
				'label' => __( 'Kontrol Kecepatan', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'step_scroll_range',
			[
				'label'       => __( 'Jarak Tiap Langkah', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [ 'px' => [ 'min' => 80, 'max' => 700, 'step' => 5 ] ],
				'default'     => [ 'size' => 275 ],
				'description' => __( 'Semakin besar, scroll berpindah lebih jauh setiap langkah.', 'apeiron-kit' ),
				'condition'   => [ 'scroll_engine' => 'step' ],
			]
		);

		$this->add_control(
			'step_scroll_interval',
			[
				'label'       => __( 'Jeda Antar Langkah', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [ 'px' => [ 'min' => 0.4, 'max' => 5, 'step' => 0.1 ] ],
				'default'     => [ 'size' => 2 ],
				'description' => __( 'Waktu diam sebelum langkah berikutnya dimulai.', 'apeiron-kit' ),
				'condition'   => [ 'scroll_engine' => 'step' ],
			]
		);

		$this->add_control(
			'step_scroll_duration',
			[
				'label'       => __( 'Durasi Gerak Langkah', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [ 'px' => [ 'min' => 0.2, 'max' => 3, 'step' => 0.05 ] ],
				'default'     => [ 'size' => 1.25 ],
				'description' => __( 'Durasi animasi untuk berpindah dari satu langkah ke langkah berikutnya.', 'apeiron-kit' ),
				'condition'   => [ 'scroll_engine' => 'step' ],
			]
		);

		$this->add_control(
			'heading_scroll_performance',
			[
				'label'     => __( 'Performa Render', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'smoothness',
			[
				'label'       => __( 'Kualitas Gerakan', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'ultra'  => __( 'Native Refresh (hingga 120Hz)', 'apeiron-kit' ),
					'smooth' => __( 'Balanced (60fps)', 'apeiron-kit' ),
					'normal' => __( 'Hemat Performa (45fps)', 'apeiron-kit' ),
				],
				'default'     => 'ultra',
				'description' => __( 'Native Refresh mengikuti refresh rate layar untuk gerakan paling fluid.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'heading_speed_control',
			[
				'label'     => __( 'Panel Kecepatan', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_speed_control',
			[
				'label'        => __( 'Tampilkan Panel Kecepatan', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'speed_control_layout',
			[
				'label'     => __( 'Arah Layout', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'horizontal' => __( 'Horizontal', 'apeiron-kit' ),
					'vertical'   => __( 'Vertikal', 'apeiron-kit' ),
				],
				'default'   => 'vertical',
				'condition' => [ 'show_speed_control' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_control_position',
			[
				'label'       => __( 'Posisi Panel', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'auto'   => __( 'Otomatis (Menjauhi Tepi)', 'apeiron-kit' ),
					'left'   => __( 'Kiri Tombol', 'apeiron-kit' ),
					'right'  => __( 'Kanan Tombol', 'apeiron-kit' ),
					'top'    => __( 'Atas Tombol', 'apeiron-kit' ),
					'bottom' => __( 'Bawah Tombol', 'apeiron-kit' ),
				],
				'default'     => 'auto',
				'description' => __( 'Posisi kontrol kecepatan relatif terhadap tombol utama', 'apeiron-kit' ),
				'condition'   => [ 'show_speed_control' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_control_draggable',
			[
				'label'        => __( 'Bisa Digeser', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Izinkan user menggeser posisi kontrol kecepatan', 'apeiron-kit' ),
				'condition'    => [ 'show_speed_control' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_value_animation_type',
			[
				'label'     => __( 'Animasi Angka Speed', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'none'   => __( 'Tidak Ada', 'apeiron-kit' ),
					'pulse'  => __( 'Pulse', 'apeiron-kit' ),
					'bounce' => __( 'Bounce', 'apeiron-kit' ),
					'slide'  => __( 'Slide', 'apeiron-kit' ),
				],
				'default'   => 'pulse',
				'condition' => [ 'show_speed_control' => 'yes' ],
			]
		);

		$this->add_control(
			'show_speed_arrows',
			[
				'label'        => __( 'Tampilkan Tombol +/-', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'condition'    => [ 'show_speed_control' => 'yes' ],
			]
		);

		$this->add_control(
			'speed_arrow_minus_icon',
			[
				'label'     => __( 'Icon Kurangi', 'apeiron-kit' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-minus',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_speed_control' => 'yes',
					'show_speed_arrows'  => 'yes',
				],
			]
		);

		$this->add_control(
			'speed_arrow_plus_icon',
			[
				'label'     => __( 'Icon Tambah', 'apeiron-kit' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-plus',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_speed_control' => 'yes',
					'show_speed_arrows'  => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_button_position_controls(): void {
		$this->start_controls_section(
			'section_button',
			[
				'label' => __( 'Tombol dan Posisi', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'button_icon_start',
			[
				'label'   => __( 'Icon Mulai', 'apeiron-kit' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-arrow-down',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'button_icon_stop',
			[
				'label'   => __( 'Icon Berhenti', 'apeiron-kit' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-pause',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'button_position',
			[
				'label'   => __( 'Posisi Tombol', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'bottom-right'  => __( 'Kanan Bawah', 'apeiron-kit' ),
					'bottom-left'   => __( 'Kiri Bawah', 'apeiron-kit' ),
					'bottom-center' => __( 'Tengah Bawah', 'apeiron-kit' ),
					'right-center'  => __( 'Kanan Tengah', 'apeiron-kit' ),
					'left-center'   => __( 'Kiri Tengah', 'apeiron-kit' ),
				],
				'default' => 'bottom-right',
			]
		);

		$this->add_control(
			'heading_button_position_advanced',
			[
				'label'     => __( 'Posisi Lanjutan', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'widget_position_horizontal',
			[
				'label'       => __( 'Posisi Horizontal Custom', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					''       => __( 'Gunakan Posisi Utama', 'apeiron-kit' ),
					'left'   => __( 'Kiri', 'apeiron-kit' ),
					'center' => __( 'Tengah', 'apeiron-kit' ),
					'right'  => __( 'Kanan', 'apeiron-kit' ),
				],
				'default'     => '',
				'description' => __( 'Kosongkan untuk mengikuti Posisi Tombol.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'widget_position_vertical',
			[
				'label'       => __( 'Posisi Vertikal Custom', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					''       => __( 'Gunakan Posisi Utama', 'apeiron-kit' ),
					'top'    => __( 'Atas', 'apeiron-kit' ),
					'center' => __( 'Tengah', 'apeiron-kit' ),
					'bottom' => __( 'Bawah', 'apeiron-kit' ),
				],
				'default'     => '',
				'description' => __( 'Kosongkan untuk mengikuti Posisi Tombol.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'heading_button_animation',
			[
				'label'     => __( 'Animasi Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_appear_animation',
			[
				'label'   => __( 'Animasi Muncul', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'none'    => __( 'Tidak Ada', 'apeiron-kit' ),
					'fade'    => __( 'Fade', 'apeiron-kit' ),
					'slide'   => __( 'Slide', 'apeiron-kit' ),
					'scale'   => __( 'Scale', 'apeiron-kit' ),
					'bounce'  => __( 'Bounce', 'apeiron-kit' ),
					'zoom'    => __( 'Zoom', 'apeiron-kit' ),
					'flip'    => __( 'Flip', 'apeiron-kit' ),
					'elastic' => __( 'Elastic', 'apeiron-kit' ),
				],
				'default' => 'fade',
			]
		);

		$this->add_control(
			'button_appear_animation_duration',
			[
				'label'     => __( 'Durasi Animasi Muncul (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0.1, 'max' => 2, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 0.5 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-container' => '--ak-button-appear-duration: {{SIZE}}s;',
				],
				'condition' => [ 'button_appear_animation!' => 'none' ],
			]
		);

		$this->add_control(
			'button_appear_animation_delay',
			[
				'label'     => __( 'Delay Animasi Muncul (detik)', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 5, 'step' => 0.1 ] ],
				'default'   => [ 'size' => 0 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-container' => '--ak-button-appear-delay: {{SIZE}}s;',
				],
				'condition' => [ 'button_appear_animation!' => 'none' ],
			]
		);

		$this->add_responsive_control(
			'button_offset_x',
			[
				'label'      => __( 'Offset Kiri/Kanan', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 200 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
					'vw' => [ 'min' => 0, 'max' => 20 ],
				],
				'default'    => [ 'size' => 25, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap.pos-top-right, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-center-right, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-bottom-right, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-right-center' => 'right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap.pos-top-left, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-center-left, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-bottom-left, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-left-center' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_center_offset_x',
			[
				'label'       => __( 'Offset Tengah Horizontal', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', '%', 'vw' ],
				'range'       => [
					'px' => [ 'min' => -200, 'max' => 200 ],
					'%'  => [ 'min' => -50, 'max' => 50 ],
					'vw' => [ 'min' => -20, 'max' => 20 ],
				],
				'default'     => [ 'size' => 0, 'unit' => 'px' ],
				'description' => __( 'Gunakan ini saat posisi horizontal berada di tengah.', 'apeiron-kit' ),
				'selectors'   => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap.pos-top-center, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-center-center, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-bottom-center' => '--ak-widget-offset-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_offset_y',
			[
				'label'      => __( 'Offset Vertikal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 300 ],
					'%'  => [ 'min' => 0, 'max' => 50 ],
					'vh' => [ 'min' => 0, 'max' => 30 ],
				],
				'default'    => [ 'size' => 100, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-autoscroll-wrap.pos-top-left, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-top-center, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-top-right' => 'top: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap.pos-bottom-right, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-bottom-left, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-bottom-center' => 'bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-autoscroll-wrap.pos-center-left, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-center-center, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-center-right, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-right-center, {{WRAPPER}} .apeiron-autoscroll-wrap.pos-left-center' => '--ak-widget-offset-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_feedback_controls(): void {
		$this->start_controls_section(
			'section_feedback',
			[
				'label' => __( 'Indikator dan Pesan', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'heading_progress',
			[
				'label'     => __( 'Progress', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_progress',
			[
				'label'        => __( 'Tampilkan Progress', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'progress_type',
			[
				'label'     => __( 'Tipe', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'circle' => __( 'Circle (Melingkar)', 'apeiron-kit' ),
					'bar'    => __( 'Bar (Garis)', 'apeiron-kit' ),
				],
				'default'   => 'circle',
				'condition' => [ 'show_progress' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_scroll_top',
			[
				'label'     => __( 'Kembali ke Atas', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_scroll_top',
			[
				'label'        => __( 'Tampilkan Tombol Kembali', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'scroll_top_icon',
			[
				'label'     => __( 'Icon', 'apeiron-kit' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-chevron-up',
					'library' => 'fa-solid',
				],
				'condition' => [ 'show_scroll_top' => 'yes' ],
			]
		);

		$this->add_control(
			'scroll_top_show_after',
			[
				'label'       => __( 'Muncul Setelah Scroll (%)', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [ 'px' => [ 'min' => 5, 'max' => 50, 'step' => 5 ] ],
				'default'     => [ 'size' => 20 ],
				'description' => __( 'Tombol muncul setelah user scroll sekian persen dari halaman', 'apeiron-kit' ),
				'condition'   => [ 'show_scroll_top' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}
}
