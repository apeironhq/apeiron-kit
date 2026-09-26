<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SignalForm\Concerns;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_general',
			[
				'label' => __( 'Konten Form', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'phone_number',
			[
				'label'       => __( 'Nomor Tujuan WhatsApp', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '628123456789',
				'placeholder' => '628123456789',
				'description' => __( 'Masukkan nomor tujuan dengan kode negara, contoh: 628123456789.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'form_title',
			[
				'label'       => __( 'Judul Form', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Konfirmasi Kehadiran', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_fields',
			[
				'label' => __( 'Field Form', 'apeiron-kit' ),
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			[
				'label'   => __( 'Label', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [ 'active' => true ],
				'default' => __( 'Nama Tamu', 'apeiron-kit' ),
			]
		);
		$repeater->add_control(
			'name',
			[
				'label'       => __( 'Token Pesan (Opsional)', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Jika dikosongkan, token dibuat otomatis dari label. Contoh: label "Nama Tamu" menjadi [nama_tamu].', 'apeiron-kit' ),
			]
		);
		$repeater->add_control(
			'placeholder',
			[
				'label'   => __( 'Placeholder', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [ 'active' => true ],
				'default' => __( 'Tulis nama lengkap', 'apeiron-kit' ),
			]
		);
		$repeater->add_control(
			'type',
			[
				'label'   => __( 'Tipe Input', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'text'     => __( 'Teks', 'apeiron-kit' ),
					'email'    => __( 'Email', 'apeiron-kit' ),
					'tel'      => __( 'Telepon', 'apeiron-kit' ),
					'number'   => __( 'Angka', 'apeiron-kit' ),
					'url'      => __( 'URL', 'apeiron-kit' ),
					'date'     => __( 'Tanggal', 'apeiron-kit' ),
					'textarea' => __( 'Paragraf', 'apeiron-kit' ),
				],
				'default' => 'text',
			]
		);
		$repeater->add_control(
			'number_min',
			[
				'label'     => __( 'Angka Minimal', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1,
				'condition' => [
					'type' => 'number',
				],
			]
		);
		$repeater->add_control(
			'number_max',
			[
				'label'     => __( 'Angka Maksimal', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'condition' => [
					'type' => 'number',
				],
			]
		);
		$repeater->add_control(
			'number_step',
			[
				'label'     => __( 'Kelipatan Angka', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1,
				'min'       => 0.01,
				'step'      => 0.01,
				'condition' => [
					'type' => 'number',
				],
			]
		);
		$repeater->add_control(
			'required',
			[
				'label'        => __( 'Wajib Diisi', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'fields',
			[
				'label'       => __( 'Field Form', 'apeiron-kit' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'label'       => __( 'Nama Tamu', 'apeiron-kit' ),
						'name'        => 'nama',
						'placeholder' => __( 'Tulis nama lengkap', 'apeiron-kit' ),
						'type'        => 'text',
						'required'    => 'yes',
					],
					[
						'label'       => __( 'Jumlah Tamu', 'apeiron-kit' ),
						'name'        => 'jumlah',
						'placeholder' => __( 'Contoh: 2', 'apeiron-kit' ),
						'type'        => 'number',
						'required'    => 'yes',
						'number_min'  => 1,
						'number_step' => 1,
					],
					[
						'label'       => __( 'Ucapan', 'apeiron-kit' ),
						'name'        => 'ucapan',
						'placeholder' => __( 'Tulis doa atau pesan', 'apeiron-kit' ),
						'type'        => 'textarea',
						'required'    => 'yes',
					],
				],
				'title_field' => '{{{ label }}}',
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Teks Tombol', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Kirim via WhatsApp', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'message_template',
			[
				'label'       => __( 'Format Pesan WhatsApp', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => "Halo, saya [nama] akan hadir bersama [jumlah] orang. [konfirmasi]. Pesan: [ucapan]",
				'description' => __( 'Gunakan token dari field, misalnya [nama]. Untuk pilihan kehadiran gunakan [konfirmasi].', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_message',
			[
				'label' => __( 'Pesan dan Validasi', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'message_template_help',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Tips: token otomatis dibuat dari label field. Contoh "Nama Tamu" menjadi [nama_tamu]. Token bawaan default: [nama], [jumlah], [ucapan], [konfirmasi].', 'apeiron-kit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'success_message',
			[
				'label'       => __( 'Pesan Setelah Klik Kirim', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'WhatsApp akan terbuka untuk mengirim pesan.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'validation_message',
			[
				'label'       => __( 'Pesan Field Belum Lengkap', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Lengkapi field yang wajib diisi.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'invalid_template_message',
			[
				'label'       => __( 'Pesan Token Tidak Cocok', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Ada token pesan yang belum cocok dengan field form.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'invalid_phone_message',
			[
				'label'       => __( 'Pesan Nomor WhatsApp Tidak Valid', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Nomor WhatsApp tujuan belum valid.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_confirmation',
			[
				'label' => __( 'Konfirmasi Kehadiran', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'show_confirmation',
			[
				'label'        => __( 'Tambahkan Pilihan Kehadiran', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'confirmation_label',
			[
				'label'       => __( 'Label Konfirmasi', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Konfirmasi Kehadiran', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [
					'show_confirmation' => 'yes',
				],
			]
		);

		$option_repeater = new Repeater();
		$option_repeater->add_control(
			'option_text',
			[
				'label'   => __( 'Opsi', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [ 'active' => true ],
				'default' => '',
			]
		);

		$this->add_control(
			'confirmation_options',
			[
				'label'       => __( 'Opsi Konfirmasi', 'apeiron-kit' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $option_repeater->get_controls(),
				'default'     => [
					[
						'option_text' => __( 'Ya saya akan datang', 'apeiron-kit' ),
					],
					[
						'option_text' => __( 'Saya masih ragu', 'apeiron-kit' ),
					],
					[
						'option_text' => __( 'Maaf saya tidak bisa datang', 'apeiron-kit' ),
					],
				],
				'title_field' => '{{{ option_text }}}',
				'condition'   => [
					'show_confirmation' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}
}
