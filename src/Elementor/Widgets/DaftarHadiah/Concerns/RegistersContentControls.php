<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\DaftarHadiah\Concerns;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Hadiah Pernikahan', 'apeiron-kit' ) ] );
		$this->add_control( 'heading', [
			'label' => __( 'Judul', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT,
			'default' => __( 'Hadiah Pernikahan', 'apeiron-kit' ), 'label_block' => true, 'dynamic' => [ 'active' => true ],
		] );
		$this->add_control( 'description', [
			'label' => __( 'Deskripsi Pembuka', 'apeiron-kit' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3,
			'default' => __( 'Kehadiran dan doa Anda adalah hadiah terindah. Jika ingin berbagi kebahagiaan, berikut beberapa hadiah yang kami harapkan.', 'apeiron-kit' ),
			'dynamic' => [ 'active' => true ],
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_gifts', [ 'label' => __( 'Daftar Hadiah', 'apeiron-kit' ) ] );
		$repeater = new Repeater();
		$repeater->add_control( 'name', [
			'label' => __( 'Nama Hadiah', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT,
			'default' => __( 'Hadiah Baru', 'apeiron-kit' ), 'label_block' => true, 'dynamic' => [ 'active' => true ],
		] );
		$repeater->add_control( 'category', [
			'label' => __( 'Kategori', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'label_block' => true,
			'placeholder' => __( 'Peralatan Dapur', 'apeiron-kit' ), 'dynamic' => [ 'active' => true ],
		] );
		$repeater->add_control( 'image', [
			'label' => __( 'Foto Hadiah', 'apeiron-kit' ), 'type' => Controls_Manager::MEDIA, 'media_types' => [ 'image' ],
			'description' => __( 'Pilih JPG, PNG, atau WebP melalui Media Library WordPress.', 'apeiron-kit' ),
			'dynamic' => [ 'active' => true, 'categories' => [ TagsModule::IMAGE_CATEGORY ], 'returnType' => 'object' ],
		] );
		$repeater->add_control( 'gallery', [
			'label' => __( 'Foto Tambahan', 'apeiron-kit' ), 'type' => Controls_Manager::GALLERY, 'default' => [],
			'description' => __( 'Foto utama menjadi sampul card. Foto tambahan ditampilkan sebagai galeri dengan thumbnail di popup. Urutkan foto melalui Media Library.', 'apeiron-kit' ),
			'dynamic' => [ 'active' => true, 'categories' => [ TagsModule::GALLERY_CATEGORY ], 'returnType' => 'object' ],
		] );
		$repeater->add_control( 'description', [
			'label' => __( 'Deskripsi Hadiah', 'apeiron-kit' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3,
			'dynamic' => [ 'active' => true ],
		] );
		$repeater->add_control( 'quantity', [
			'label' => __( 'Jumlah Awal', 'apeiron-kit' ), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'step' => 1, 'default' => 1,
			'description' => __( 'Konfigurasi manual, bukan stok real-time. Nilai 0 menonaktifkan pemilihan hadiah.', 'apeiron-kit' ),
		] );
		$repeater->add_control( 'available_quantity', [
			'label' => __( 'Jumlah Tersedia (Manual)', 'apeiron-kit' ), 'type' => Controls_Manager::NUMBER,
			'min' => 0, 'step' => 1, 'default' => '',
			'description' => __( 'Kosongkan untuk mengikuti jumlah awal. Ini kapasitas manual sebelum reservasi online; saat koneksi online aktif, reservasi terkonfirmasi dikurangi dari nilai ini. Nilai 0 menonaktifkan pemilihan.', 'apeiron-kit' ),
		] );
		$repeater->add_control( 'availability', [
			'label' => __( 'Ketersediaan Manual', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'available',
			'options' => [ 'available' => __( 'Tersedia', 'apeiron-kit' ), 'unavailable' => __( 'Tidak Tersedia', 'apeiron-kit' ) ],
			'description' => __( 'Nonaktifkan hadiah secara manual. Saat koneksi online aktif, hadiah juga tidak tersedia ketika kapasitasnya sudah terisi.', 'apeiron-kit' ),
		] );
		$repeater->add_control( 'purchase_url', [
			'label' => __( 'Link Pembelian (Opsional)', 'apeiron-kit' ), 'type' => Controls_Manager::URL,
			'options' => [ 'is_external', 'nofollow' ], 'dynamic' => [ 'active' => true ],
		] );
		$repeater->add_control( 'purchase_label', [
			'label' => __( 'Link Toko', 'apeiron-kit' ), 'type' => Controls_Manager::TEXT, 'label_block' => true,
			'placeholder' => __( 'Lihat di Shopee / Tokopedia', 'apeiron-kit' ), 'dynamic' => [ 'active' => true ],
			'description' => __( 'Kosongkan untuk mengikuti teks link pembelian widget.', 'apeiron-kit' ),
		] );
		$this->add_control( 'gifts', [
			'label' => __( 'Daftar Hadiah', 'apeiron-kit' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $repeater->get_controls(), 'title_field' => '{{{ name }}}', 'prevent_empty' => false,
			'default' => [
				[ 'name' => __( 'Rice Cooker', 'apeiron-kit' ), 'description' => __( 'Hadiah untuk melengkapi dapur di rumah baru.', 'apeiron-kit' ), 'quantity' => 1 ],
				[ 'name' => __( 'Blender', 'apeiron-kit' ), 'description' => __( 'Teman untuk menyiapkan sajian setiap hari.', 'apeiron-kit' ), 'quantity' => 1 ],
				[ 'name' => __( 'Set Piring', 'apeiron-kit' ), 'description' => __( 'Perlengkapan untuk meja makan keluarga.', 'apeiron-kit' ), 'quantity' => 2 ],
				[ 'name' => __( 'Perlengkapan Rumah', 'apeiron-kit' ), 'description' => __( 'Kenangan kecil untuk rumah baru kami.', 'apeiron-kit' ), 'quantity' => 1 ],
			],
			'description' => __( 'ID hadiah mengikuti ID internal item Elementor, tetap sama saat nama atau urutan diubah. Duplikasi membuat ID baru. Dynamic Tags tersedia pada field yang didukung Elementor.', 'apeiron-kit' ),
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_display', [ 'label' => __( 'Tampilan Grid', 'apeiron-kit' ) ] );
		$this->add_responsive_control( 'card_layout', [
			'label' => __( 'Layout Card', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'vertical',
			'options' => [ 'vertical' => __( 'Vertikal — Foto di Atas', 'apeiron-kit' ), 'horizontal' => __( 'Horizontal — Foto di Samping', 'apeiron-kit' ) ],
			'selectors' => [ '{{WRAPPER}} .apeiron-daftar-hadiah' => '{{VALUE}}' ],
			'selectors_dictionary' => [
				'vertical' => '--dh-card-columns: minmax(0, 1fr); --dh-card-rows: auto minmax(0, 1fr); --dh-card-inset: 0px; --dh-card-media-height: auto; --dh-card-default-max-width: 260px; --dh-card-photo-radius: 0px; --dh-card-body-padding: 12px;',
				'horizontal' => '--dh-card-columns: minmax(0, var(--dh-card-photo-share, 36%)) minmax(0, 1fr); --dh-card-rows: minmax(0, 1fr); --dh-card-inset: 8px; --dh-card-media-height: 100%; --dh-card-default-max-width: 420px; --dh-card-photo-radius: 4px; --dh-card-body-padding: 4px 4px 4px 12px;',
			],
		] );
		$this->add_responsive_control( 'columns', [
			'label' => __( 'Jumlah Kolom', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4' ],
			'default' => '4', 'tablet_default' => '2', 'mobile_default' => '1',
			'selectors' => [ '{{WRAPPER}} .apeiron-daftar-hadiah__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, var(--dh-card-max-width, var(--dh-card-default-max-width, 260px))));' ],
		] );
		$this->add_control( 'items_limit', [
			'label' => __( 'Jumlah Item Ditampilkan', 'apeiron-kit' ), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'step' => 1, 'default' => 0,
			'description' => __( '0 menampilkan semua hadiah sesuai urutan repeater.', 'apeiron-kit' ),
		] );
		$this->add_control( 'mobile_display', [
			'label' => __( 'Tampilan Card Mobile', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'swipe',
			'options' => [ 'swipe' => __( 'Swipe ke Samping', 'apeiron-kit' ), 'grid' => __( 'Grid / Daftar', 'apeiron-kit' ) ],
			'description' => __( 'Mode swipe menampilkan satu baris card yang dapat digeser. Mode grid mengikuti jumlah kolom mobile.', 'apeiron-kit' ),
		] );
		$this->add_control( 'swipe_navigation', [
			'label' => __( 'Navigasi Swipe Card', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT, 'default' => 'dots',
			'options' => [
				'dots' => __( 'Dot', 'apeiron-kit' ), 'arrows' => __( 'Panah Sebelumnya / Berikutnya', 'apeiron-kit' ),
				'both' => __( 'Dot & Panah', 'apeiron-kit' ), 'none' => __( 'Tanpa Navigasi', 'apeiron-kit' ),
			],
			'condition' => [ 'mobile_display' => 'swipe' ],
		] );
		foreach ( [ 'prev' => __( 'Ikon Sebelumnya', 'apeiron-kit' ), 'next' => __( 'Ikon Berikutnya', 'apeiron-kit' ) ] as $direction => $label ) {
			$this->add_control( 'swipe_' . $direction . '_icon', [
				'label' => $label, 'type' => Controls_Manager::ICONS,
				'description' => __( 'Kosongkan untuk menggunakan panah bawaan.', 'apeiron-kit' ),
				'condition' => [ 'mobile_display' => 'swipe', 'swipe_navigation' => [ 'arrows', 'both' ] ],
			] );
		}
		foreach ( [
			'heading' => __( 'Judul Section', 'apeiron-kit' ), 'description' => __( 'Deskripsi Pembuka', 'apeiron-kit' ),
			'image' => __( 'Foto', 'apeiron-kit' ), 'name' => __( 'Nama Hadiah', 'apeiron-kit' ),
			'gift_description' => __( 'Deskripsi Hadiah', 'apeiron-kit' ), 'quantity' => __( 'Jumlah Tersedia / Awal', 'apeiron-kit' ),
			'status' => __( 'Status Manual', 'apeiron-kit' ), 'purchase_link' => __( 'Link Pembelian di Popup', 'apeiron-kit' ),
			'button' => __( 'Tombol Pilih Hadiah', 'apeiron-kit' ),
		] as $key => $label ) {
			$this->add_control( 'show_' . $key, [ 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );
		}
		$this->end_controls_section();

		$this->start_controls_section( 'section_labels', [ 'label' => __( 'Teks', 'apeiron-kit' ) ] );
		foreach ( [
			'button_text' => [ __( 'Tombol Pilih', 'apeiron-kit' ), __( 'Pilih Hadiah', 'apeiron-kit' ) ],
			'purchase_text' => [ __( 'Link Pembelian', 'apeiron-kit' ), __( 'Lihat Produk', 'apeiron-kit' ) ],
			'available_text' => [ __( 'Status Tersedia', 'apeiron-kit' ), __( 'Tersedia', 'apeiron-kit' ) ],
			'unavailable_text' => [ __( 'Status Tidak Tersedia', 'apeiron-kit' ), __( 'Tidak Tersedia', 'apeiron-kit' ) ],
			'quantity_text' => [ __( 'Label Jumlah Awal (Tooltip)', 'apeiron-kit' ), __( 'Jumlah awal', 'apeiron-kit' ) ],
			'popup_title' => [ __( 'Judul Popup', 'apeiron-kit' ), __( 'Pilihan Hadiah', 'apeiron-kit' ) ],
			'confirm_text' => [ __( 'Tombol Konfirmasi', 'apeiron-kit' ), __( 'Konfirmasi Pilihan', 'apeiron-kit' ) ],
			'cancel_text' => [ __( 'Label Aksesibilitas Close (X)', 'apeiron-kit' ), __( 'Batal', 'apeiron-kit' ) ],
			'name_label' => [ __( 'Label Nama', 'apeiron-kit' ), __( 'Nama Tamu', 'apeiron-kit' ) ],
			'whatsapp_label' => [ __( 'Label WhatsApp', 'apeiron-kit' ), __( 'Nomor WhatsApp', 'apeiron-kit' ) ],
			'message_label' => [ __( 'Label Pesan', 'apeiron-kit' ), __( 'Pesan (Opsional)', 'apeiron-kit' ) ],
			'empty_text' => [ __( 'Empty State', 'apeiron-kit' ), __( 'Daftar hadiah belum ditambahkan.', 'apeiron-kit' ) ],
		] as $key => $field ) {
			$this->add_control( $key, [ 'label' => $field[0], 'type' => Controls_Manager::TEXT, 'default' => $field[1], 'label_block' => true, 'dynamic' => [ 'active' => true ] ] );
		}
		$this->end_controls_section();
	}
}
