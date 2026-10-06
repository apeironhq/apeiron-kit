<?php

namespace ApeironKit\Elementor\Widgets\FormOrder\Concerns;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use ApeironKit\Elementor\Widgets\FormOrder\FieldSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {
	private function register_content_controls(): void {
		$builder = FieldSchema::builder_rows( [] );
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Form', 'apeiron-kit' ) ] );
		$this->add_control( 'form_title', [
			'label' => __( 'Judul Form', 'apeiron-kit' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( 'Form Order Undangan', 'apeiron-kit' ),
		] );
		$this->end_controls_section();

		$field = new Repeater();
		$field->add_control( 'step_key', [ 'label' => __( 'ID Step Tujuan', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT,
			'description' => __( 'Field baru mengikuti Step terakhir yang dipilih. ID ini dapat diganti untuk memindahkannya ke Step lain.', 'apeiron-kit' ),
		] );
		$field->add_control( 'step_label', [ 'type' => Controls_Manager::HIDDEN, 'default' => '' ] );
		$field->add_control( 'group_header', [ 'type' => Controls_Manager::HIDDEN, 'default' => '' ] );
		$field->add_control( 'label', [ 'label' => __( 'Label', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Field Baru', 'apeiron-kit' ), 'label_block' => true ] );
		$field->add_control( 'field_key', [ 'label' => __( 'Internal Field ID (kosong = otomatis)', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'description' => __( 'Gunakan ID unik; ID bawaan jangan diubah bila integrasi sudah digunakan.', 'apeiron-kit' ) ] );
		$field->add_control( 'type', [ 'label' => __( 'Tipe', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'text', 'options' => [
			'text' => 'Text', 'email' => 'Email', 'tel' => 'Tel/WhatsApp', 'number' => 'Number', 'textarea' => 'Textarea',
			'select' => 'Select', 'radio' => 'Radio', 'checkbox' => 'Checkbox', 'date' => 'Date', 'time' => 'Time', 'url' => 'URL', 'heading' => 'Heading/Description',
		] ] );
		$field->add_control( 'placeholder', [ 'label' => __( 'Placeholder', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT ] );
		$field->add_control( 'default_value', [ 'label' => __( 'Nilai Default', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT ] );
		$field->add_control( 'options', [
			'label' => __( 'Opsi (satu per baris: nilai|label)', 'apeiron-kit' ), 'type' => Controls_Manager::TEXTAREA,
			'condition' => [ 'type' => [ 'select', 'radio', 'checkbox' ] ],
		] );
		$field->add_control( 'help_text', [ 'label' => __( 'Teks Bantuan / Deskripsi', 'apeiron-kit' ), 'type' => Controls_Manager::TEXTAREA ] );
		$field->add_control( 'required', [ 'label' => __( 'Wajib', 'apeiron-kit' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes' ] );
		$field->add_control( 'visible', [ 'label' => __( 'Tampilkan', 'apeiron-kit' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$field->add_responsive_control( 'width', [ 'label' => __( 'Lebar Field', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ '25' => '25%', '33' => '33%', '50' => '50%', '66' => '66%', '75' => '75%', '100' => '100%' ],
			'default' => '50', 'tablet_default' => '100', 'mobile_default' => '100',
		] );
		$step = new Repeater();
		$step->add_control( 'title', [ 'label' => __( 'Nama Step', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Step Baru', 'apeiron-kit' ), 'label_block' => true ] );
		$step->add_control( 'step_key', [ 'label' => __( 'ID Step', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT,
			'description' => __( 'Gunakan ID unik, misalnya cerita_cinta. Hubungkan field menggunakan ID ini.', 'apeiron-kit' ),
		] );
		$step->add_control( 'kind', [ 'label' => __( 'Jenis Step', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ 'fields' => __( 'Field', 'apeiron-kit' ), 'confirmation' => __( 'Ringkasan Konfirmasi', 'apeiron-kit' ) ], 'default' => 'fields',
		] );
		$step->add_control( 'enabled', [ 'label' => __( 'Tampilkan Step', 'apeiron-kit' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		$this->start_controls_section( 'section_steps', [ 'label' => __( 'Steps', 'apeiron-kit' ) ] );
		$this->add_control( 'order_steps', [ 'label' => __( 'Step / Section Builder', 'apeiron-kit' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $step->get_controls(), 'default' => $builder['steps'], 'title_field' => '{{{ title }}}', 'prevent_empty' => false,
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_fields_help', [ 'label' => __( 'Fields', 'apeiron-kit' ) ] );
		$this->add_control( 'order_fields', [ 'label' => __( 'Fields', 'apeiron-kit' ), 'type' => 'apeiron_order_fields',
			'fields' => $field->get_controls(), 'default' => $builder['fields'], 'show_label' => false,
			'title_field' => '<# if ( "yes" === group_header ) { #><span class="apeiron-form-order-editor-group">{{{ step_label }}}</span><# } #>{{{ label }}}',
			'prevent_empty' => false,
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_navigation', [ 'label' => __( 'Navigation', 'apeiron-kit' ) ] );
		$this->add_control( 'next_text', [ 'label' => __( 'Tombol Lanjut', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Lanjut', 'apeiron-kit' ) ] );
		$this->add_control( 'back_text', [ 'label' => __( 'Tombol Kembali', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Kembali', 'apeiron-kit' ) ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_submit', [ 'label' => __( 'Submit', 'apeiron-kit' ) ] );
		$this->add_control( 'submit_text', [
			'label' => __( 'Teks Tombol Kirim', 'apeiron-kit' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( 'Kirim Pesanan', 'apeiron-kit' ),
		] );
		$this->end_controls_section();
		$this->start_controls_section( 'section_messages', [ 'label' => __( 'Messages', 'apeiron-kit' ) ] );
		$this->add_control( 'confirmation_text', [ 'label' => __( 'Teks Konfirmasi', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT,
			'default' => __( 'Periksa kembali seluruh data sebelum mengirim.', 'apeiron-kit' ),
		] );
		$this->add_control( 'validation_text', [ 'label' => __( 'Pesan Validasi', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT,
			'default' => __( 'Periksa field yang wajib diisi atau format yang belum valid.', 'apeiron-kit' ),
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_draft', [ 'label' => __( 'Draft / Auto Save', 'apeiron-kit' ) ] );
		$this->add_control( 'draft_auto_save', [ 'label' => __( 'Simpan Draft Otomatis', 'apeiron-kit' ),
			'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
		] );
		foreach ( [
			'draft_restore' => __( 'Pulihkan Data Setelah Reload', 'apeiron-kit' ),
			'draft_save_step' => __( 'Simpan Step Terakhir', 'apeiron-kit' ),
			'draft_clear_success' => __( 'Hapus Draft Setelah Submit Berhasil', 'apeiron-kit' ),
			'draft_show_notice' => __( 'Tampilkan Notifikasi Draft Dipulihkan', 'apeiron-kit' ),
		] as $id => $label ) {
			$this->add_control( $id, [ 'label' => $label, 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => 'yes', 'condition' => [ 'draft_auto_save' => 'yes' ],
			] );
		}
		$this->add_control( 'draft_retention_days', [ 'label' => __( 'Masa Penyimpanan Draft', 'apeiron-kit' ),
			'type' => Controls_Manager::SELECT, 'default' => '7', 'condition' => [ 'draft_auto_save' => 'yes' ],
			'options' => [ '1' => __( '1 hari', 'apeiron-kit' ), '3' => __( '3 hari', 'apeiron-kit' ),
				'7' => __( '7 hari', 'apeiron-kit' ), '14' => __( '14 hari', 'apeiron-kit' ), '30' => __( '30 hari', 'apeiron-kit' ),
			],
		] );
		$this->end_controls_section();
	}
}
