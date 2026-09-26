<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\PageLoader\Concerns;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->register_loader_controls();
		$this->register_behavior_controls();
	}

	private function register_loader_controls(): void {
		$this->start_controls_section(
			'section_loader_content',
			[
				'label' => __( 'Loader', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'loader_style',
			[
				'label'   => __( 'Loader Style', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default' => __( 'Default', 'apeiron-kit' ),
					'coffee'  => __( 'Coffee', 'apeiron-kit' ),
					'water'   => __( 'Water', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'intro_text',
			[
				'label'       => __( 'Intro Text', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'The Wedding of', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'main_text',
			[
				'label'       => __( 'Main / Initial Text', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'RK',
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'loading_text',
			[
				'label'       => __( 'Loading Text', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Menyiapkan halaman', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'secondary_text',
			[
				'label'       => __( 'Secondary / Name Text', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( 'Rania & Kenny', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_percentage',
			[
				'label'        => __( 'Show Percentage', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'progress_based',
			[
				'label'        => __( 'Progress Based Loading', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Progress mengikuti state dokumen lalu diselesaikan saat halaman siap.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'loader_duration',
			[
				'label'      => __( 'Minimum Display Time', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 300,
						'max'  => 12000,
						'step' => 100,
					],
				],
				'default'    => [
					'size' => 7600,
					'unit' => 'ms',
				],
				'description' => __( 'Durasi minimum agar urutan animasi teks selesai. Loader tetap selesai segera setelah halaman siap dan waktu minimum tercapai.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'maximum_timeout',
			[
				'label'      => __( 'Maximum Timeout', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 2000,
						'max'  => 20000,
						'step' => 500,
					],
				],
				'default'    => [
					'size' => 14000,
					'unit' => 'ms',
				],
				'description' => __( 'Batas fail-open. Loader selalu dilepas setelah waktu ini meskipun resource eksternal gagal selesai.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'custom_delay',
			[
				'label'      => __( 'Custom Delay', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 0,
						'max'  => 4000,
						'step' => 100,
					],
				],
				'default'    => [
					'size' => 0,
					'unit' => 'ms',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_behavior_controls(): void {
		$this->start_controls_section(
			'section_behavior_content',
			[
				'label' => __( 'Behavior', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'first_visit_only',
			[
				'label'        => __( 'First Visit Only', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Menggunakan LocalStorage agar loader hanya muncul pada kunjungan pertama.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'storage_key',
			[
				'label'       => __( 'LocalStorage Key', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'apeiron_page_loader_seen',
				'label_block' => true,
				'condition'   => [
					'first_visit_only' => 'yes',
				],
			]
		);

		$this->add_control(
			'entrance_animation',
			[
				'label'   => __( 'Entrance Animation', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade_scale',
				'options' => [
					'none'       => __( 'None', 'apeiron-kit' ),
					'fade'       => __( 'Fade', 'apeiron-kit' ),
					'fade_scale' => __( 'Fade Scale', 'apeiron-kit' ),
					'slide_up'   => __( 'Slide Up', 'apeiron-kit' ),
					'soft_blur'  => __( 'Soft Blur', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'exit_animation',
			[
				'label'   => __( 'Exit Animation', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade_scale',
				'options' => [
					'none'       => __( 'None', 'apeiron-kit' ),
					'fade'       => __( 'Fade', 'apeiron-kit' ),
					'fade_scale' => __( 'Fade Scale', 'apeiron-kit' ),
					'slide_up'   => __( 'Slide Up', 'apeiron-kit' ),
					'curtain'    => __( 'Curtain Lift', 'apeiron-kit' ),
					'soft_blur'  => __( 'Soft Blur', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'glassmorphism',
			[
				'label'        => __( 'Glassmorphism Background', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'blur_background',
			[
				'label'        => __( 'Blur Background', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'device_visibility_heading',
			[
				'label'     => __( 'Device Visibility', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_desktop',
			[
				'label'        => __( 'Desktop', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Tampil', 'apeiron-kit' ),
				'label_off'    => __( 'Sembunyi', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_tablet',
			[
				'label'        => __( 'Tablet', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Tampil', 'apeiron-kit' ),
				'label_off'    => __( 'Sembunyi', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_mobile',
			[
				'label'        => __( 'Mobile', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Tampil', 'apeiron-kit' ),
				'label_off'    => __( 'Sembunyi', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}
}
