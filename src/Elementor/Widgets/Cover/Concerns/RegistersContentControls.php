<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Cover\Concerns;

use ApeironKit\Support\CoverTypeRegistry;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Elementor control IDs are persisted data. */
trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_cover_assets',
			[
				'label' => __( 'Aset Cover', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'cover_settings_mode',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'custom',
			]
		);

		$this->add_control(
			'cover_type',
			[
				'label'       => __( 'Tipe Sampul', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => CoverTypeRegistry::DEFAULT_TYPE,
				'save_default' => true,
				'options'     => CoverTypeRegistry::get_options(),
				'description' => __( 'Pilihan ini hanya berlaku untuk undangan ini.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'pattern_image',
			[
				'label'       => __( 'Pattern Panel', 'apeiron-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => [
					'url' => $this->get_default_pattern_url(),
				],
				'description' => __( 'Default otomatis mencari assets/img/cover/pattern.* lalu assets/img/cover/bg.* sebagai sumber utama panel cover.', 'apeiron-kit' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'background_image',
			[
				'label'       => __( 'Background Belakang', 'apeiron-kit' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => __( 'Opsional. Jika kosong, pattern panel digunakan sebagai fallback background.', 'apeiron-kit' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_ornament',
			[
				'label'        => __( 'Tampilkan Ornamen Atas', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'ornament_image',
			[
				'label'     => __( 'Gambar Ornamen', 'apeiron-kit' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [
					'url' => '',
				],
				'condition' => [
					'show_ornament' => 'yes',
				],
				'dynamic'   => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_cover_photo',
			[
				'label'        => __( 'Tampilkan Foto Cover', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'cover_photo',
			[
				'label'     => __( 'Foto Cover', 'apeiron-kit' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [
					'url' => '',
				],
				'condition' => [
					'show_cover_photo' => 'yes',
				],
				'dynamic'   => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_cover_text',
			[
				'label' => __( 'Teks', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'welcome_text',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->add_control(
			'event_label',
			[
				'label'       => __( 'Label Acara', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'The Wedding Of', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'primary_name',
			[
				'label'       => __( 'Nama Pertama', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Anissa', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'name_separator',
			[
				'label'   => __( 'Pemisah Nama', 'apeiron-kit' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '&',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'secondary_name',
			[
				'label'       => __( 'Nama Kedua', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Hamzah', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'event_date',
			[
				'label'       => __( 'Tanggal Acara', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Minggu, 12 November 2029', 'apeiron-kit' ),
				'placeholder' => __( 'Minggu, 12 November 2029', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'monogram_text',
			[
				'label'       => __( 'Teks Lingkaran Tengah', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'A & H',
				'description' => __( 'Tampil di lingkaran tengah. Dipakai jika Logo Lingkaran dimatikan.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_pin_image',
			[
				'label'        => __( 'Tampilkan Logo Lingkaran', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'pin_image',
			[
				'label'     => __( 'Logo Lingkaran Tengah', 'apeiron-kit' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [
					'url' => '',
				],
				'condition' => [
					'show_pin_image' => 'yes',
				],
				'dynamic'   => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Tombol Buka', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Buka Undangan', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'ribbon_text',
			[
				'label'       => __( 'Teks Nama di Pita', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Kosongkan untuk menyembunyikan. Token tersedia: {primary}, {secondary}, {separator}, {names}.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_center_strip',
			[
				'label'        => __( 'Tampilkan Strip Tengah', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'center_strip_label',
			[
				'label'       => __( 'Teks Strip Atas', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Plugin Apeiron Kit Cover Invitation',
				'description' => __( 'Teks vertikal di tengah strip. Token tersedia: {primary}, {secondary}, {separator}, {names}.', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'show_center_strip' => 'yes',
				],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'center_strip_label_bottom',
			[
				'label'       => __( 'Teks Strip Bawah', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Plugin Apeiron Kit Cover Invitation',
				'description' => __( 'Teks ini menghadap ke arah sebaliknya dari teks atas.', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'show_center_strip' => 'yes',
				],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_recipient_card',
			[
				'label'        => __( 'Tampilkan Kartu Penerima', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'recipient_heading',
			[
				'label'     => __( 'Judul Kartu', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Kepada Yth.', 'apeiron-kit' ),
				'condition' => [
					'show_recipient_card' => 'yes',
				],
				'dynamic'   => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'auto_recipient',
			[
				'label'        => __( 'Ambil Nama dari URL', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'show_recipient_card' => 'yes',
				],
			]
		);

		$this->add_control(
			'guest_parameter',
			[
				'label'       => __( 'Parameter Tamu', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'to',
				'description' => __( 'Contoh: ?to=Nama%20Tamu. Widget juga membaca format /to/Nama%20Tamu.', 'apeiron-kit' ),
				'condition'   => [
					'show_recipient_card' => 'yes',
					'auto_recipient'      => 'yes',
				],
			]
		);

		$this->add_control(
			'recipient_text',
			[
				'label'       => __( 'Nama Penerima / Fallback', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Tamu Undangan', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'show_recipient_card' => 'yes',
				],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_cover_behavior',
			[
				'label' => __( 'Animasi dan Interaksi', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'panel_effect',
			[
				'label'       => __( 'Animasi Panel', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'slide',
				'options'     => [
					'slide'  => __( 'Bergeser ke Samping', 'apeiron-kit' ),
					'shrink' => __( 'Menyusut ke Samping', 'apeiron-kit' ),
				],
				'description' => __( 'Saat Strip Tengah aktif, widget otomatis memakai mode bergeser agar strip tidak mengecil.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'exit_effect',
			[
				'label'   => __( 'Transisi Akhir', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'down',
				'options' => [
					'down' => __( 'Modal Turun', 'apeiron-kit' ),
					'up'   => __( 'Modal Naik', 'apeiron-kit' ),
					'fade' => __( 'Fade Out', 'apeiron-kit' ),
					'none' => __( 'Tanpa Transisi', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'info_duration',
			[
				'label'      => __( 'Durasi Pita Turun', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 100,
						'max'  => 5000,
						'step' => 50,
					],
				],
				'default'    => [
					'size' => 4100,
					'unit' => 'ms',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-info-duration: {{SIZE}}ms;',
				],
			]
		);

		$this->add_control(
			'panel_duration',
			[
				'label'      => __( 'Durasi Panel Terbuka', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 100,
						'max'  => 5000,
						'step' => 50,
					],
				],
				'default'    => [
					'size' => 3650,
					'unit' => 'ms',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-panel-duration: {{SIZE}}ms;',
				],
			]
		);

		$this->add_control(
			'exit_duration',
			[
				'label'      => __( 'Durasi Transisi Akhir', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 0,
						'max'  => 6000,
						'step' => 50,
					],
				],
				'default'    => [
					'size' => 2150,
					'unit' => 'ms',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-cover' => '--apeiron-cover-exit-duration: {{SIZE}}ms;',
				],
			]
		);

		$this->add_control(
			'open_delay',
			[
				'label'      => __( 'Delay Setelah Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [
						'min'  => 0,
						'max'  => 2000,
						'step' => 50,
					],
				],
				'default'    => [
					'size' => 150,
					'unit' => 'ms',
				],
			]
		);

		$this->add_control(
			'lock_scroll',
			[
				'label'        => __( 'Kunci Scroll Saat Cover Aktif', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'close_on_escape',
			[
				'label'        => __( 'Buka dengan Tombol Escape', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'first_visit_only',
			[
				'label'        => __( 'Tampil Sekali per Pengunjung', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'storage_key',
			[
				'label'       => __( 'LocalStorage Key', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'apeiron_cover_opened',
				'label_block' => true,
				'condition'   => [
					'first_visit_only' => 'yes',
				],
			]
		);

		$this->add_control(
			'after_open_heading',
			[
				'label'     => __( 'Aksi Setelah Terbuka', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'event_name',
			[
				'label'       => __( 'Nama Event JS', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'apeiron:cover:opened',
				'label_block' => true,
			]
		);

		$this->add_control(
			'click_selector',
			[
				'label'       => __( 'Klik Selector Opsional', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '.backsound, #button-mode-read',
				'label_block' => true,
				'description' => __( 'Opsional. Setelah cover selesai, widget akan mencoba klik elemen pertama yang cocok.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'scroll_selector',
			[
				'label'       => __( 'Scroll ke Selector Opsional', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '#content, .elementor',
				'label_block' => true,
			]
		);

		$this->add_control(
			'scroll_behavior',
			[
				'label'     => __( 'Jenis Scroll', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'smooth',
				'options'   => [
					'smooth' => __( 'Halus', 'apeiron-kit' ),
					'auto'   => __( 'Instan', 'apeiron-kit' ),
				],
				'condition' => [
					'scroll_selector!' => '',
				],
			]
		);

		$this->add_control(
			'device_visibility_heading',
			[
				'label'     => __( 'Tampil di Perangkat', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		foreach ( [
			'show_desktop' => __( 'Desktop', 'apeiron-kit' ),
			'show_tablet'  => __( 'Tablet', 'apeiron-kit' ),
			'show_mobile'  => __( 'Mobile', 'apeiron-kit' ),
		] as $key => $label ) {
			$this->add_control(
				$key,
				[
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Tampil', 'apeiron-kit' ),
					'label_off'    => __( 'Sembunyi', 'apeiron-kit' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			);
		}

		$this->end_controls_section();
	}
}
