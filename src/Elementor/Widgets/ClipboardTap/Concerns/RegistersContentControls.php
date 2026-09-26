<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\ClipboardTap\Concerns;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Control IDs are persisted Elementor data. */
trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->register_copy_controls();
		$this->register_button_controls();
		$this->register_feedback_controls();
	}

	private function register_copy_controls(): void {
		$this->start_controls_section(
			'section_copy_content',
			[
				'label' => __( 'Isi Salinan', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'copy_source',
			[
				'label'       => __( 'Jenis Salinan', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'manual',
				'options'     => [
					'manual'      => __( 'Teks Manual', 'apeiron-kit' ),
					'current_url' => __( 'URL Halaman Ini', 'apeiron-kit' ),
					'custom_url'  => __( 'URL Kustom', 'apeiron-kit' ),
					'shortcode'   => __( 'Hasil Shortcode', 'apeiron-kit' ),
				],
				'description' => __( 'Pilih sumber konten yang akan disalin saat tombol diklik.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'value',
			[
				'label'       => __( 'Isi yang Akan Disalin', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '1234567890',
				'label_block' => true,
				'description' => __( 'Isi teks biasa, nomor rekening, kode, atau pesan yang ingin disalin.', 'apeiron-kit' ),
				'condition'   => [
					'copy_source' => 'manual',
				],
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'custom_url',
			[
				'label'       => __( 'URL yang Akan Disalin', 'apeiron-kit' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://example.com',
				'label_block' => true,
				'description' => __( 'Masukkan link lengkap yang ingin disalin.', 'apeiron-kit' ),
				'condition'   => [
					'copy_source' => 'custom_url',
				],
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'shortcode_content',
			[
				'label'       => __( 'Shortcode', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'placeholder' => '[shortcode_anda]',
				'label_block' => true,
				'description' => __( 'Hasil shortcode akan diproses lalu disalin sebagai teks biasa.', 'apeiron-kit' ),
				'condition'   => [
					'copy_source' => 'shortcode',
				],
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'current_url_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Tombol akan menyalin URL halaman yang sedang dibuka pengunjung.', 'apeiron-kit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => [
					'copy_source' => 'current_url',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_button_controls(): void {
		$this->start_controls_section(
			'section_button_content',
			[
				'label' => __( 'Tombol', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Label Tombol', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Salin No. Rekening', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'                => __( 'Posisi Tombol', 'apeiron-kit' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'left'   => [
						'title' => __( 'Kiri', 'apeiron-kit' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Tengah', 'apeiron-kit' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => __( 'Kanan', 'apeiron-kit' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'              => 'center',
				'toggle'               => false,
				'selectors'            => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap-wrapper' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'justify-content: flex-start;',
					'center' => 'justify-content: center;',
					'right'  => 'justify-content: flex-end;',
				],
			]
		);

		$this->add_control(
			'selected_icon',
			[
				'label'            => __( 'Ikon Tombol', 'apeiron-kit' ),
				'type'             => Controls_Manager::ICONS,
				'label_block'      => true,
				'fa4compatibility' => 'icon',
				'default'          => [
					'value'   => 'far fa-copy',
					'library' => 'fa-regular',
				],
				'separator'        => 'before',
			]
		);

		$this->add_control(
			'icon_align',
			[
				'label'     => __( 'Posisi Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => is_rtl() ? 'row-reverse' : 'row',
				'options'   => [
					'row'         => [
						'title' => __( 'Awal', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-left',
					],
					'row-reverse' => [
						'title' => __( 'Akhir', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap__content' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'selected_icon[value]!' => '',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_feedback_controls(): void {
		$this->start_controls_section(
			'section_feedback_content',
			[
				'label' => __( 'Feedback', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'success_message',
			[
				'label'       => __( 'Pesan Berhasil', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Berhasil disalin', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'empty_message',
			[
				'label'       => __( 'Pesan Jika Kosong', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Tidak ada teks untuk disalin', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'invalid_url_message',
			[
				'label'       => __( 'Pesan URL Tidak Valid', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'URL tidak valid', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'error_message',
			[
				'label'       => __( 'Pesan Jika Gagal', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Gagal menyalin. Silakan coba lagi', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'feedback_duration',
			[
				'label'       => __( 'Durasi Feedback', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'ms' ],
				'range'       => [
					'ms' => [
						'min'  => 800,
						'max'  => 5000,
						'step' => 100,
					],
				],
				'default'     => [
					'size' => 1800,
					'unit' => 'ms',
				],
				'description' => __( 'Lama pesan berhasil atau gagal tampil di tombol.', 'apeiron-kit' ),
			]
		);

		$this->end_controls_section();
	}
}
