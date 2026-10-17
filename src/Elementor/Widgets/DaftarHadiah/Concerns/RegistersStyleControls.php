<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\DaftarHadiah\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->hadiah_section( 'layout', __( 'Layout & Spacing', 'apeiron-kit' ) );
		$this->hadiah_dimensions( 'area_padding', __( 'Padding Area', 'apeiron-kit' ), '{{WRAPPER}} .apeiron-daftar-hadiah', 'padding' );
		$this->hadiah_slider( 'grid_gap', __( 'Gap Grid', 'apeiron-kit' ), $this->hadiah_selector( 'grid' ), 'gap', 0, 80 );
		$this->hadiah_slider( 'header_gap', __( 'Jarak Heading ke Grid', 'apeiron-kit' ), $this->hadiah_selector( 'header' ), 'margin-bottom', 0, 100 );
		$this->hadiah_slider( 'content_gap', __( 'Gap Isi Card', 'apeiron-kit' ), $this->hadiah_selector( 'body' ), 'gap', 0, 60 );
		$this->hadiah_alignment( 'card_alignment', $this->hadiah_selector( 'body' ) );
		$this->hadiah_slider( 'swipe_card_width', __( 'Lebar Card Saat Swipe', 'apeiron-kit' ), '{{WRAPPER}} .apeiron-daftar-hadiah', '--dh-swipe-width', 120, 500, [ '%', 'px' ] );
		$this->end_controls_section();

		$this->hadiah_section( 'swipe_navigation', __( 'Navigasi Swipe Card', 'apeiron-kit' ) );
		$this->add_responsive_control( 'swipe_navigation_alignment', [
			'label' => __( 'Perataan', 'apeiron-kit' ), 'type' => Controls_Manager::CHOOSE,
			'options' => [
				'flex-start' => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
				'flex-end' => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [ $this->hadiah_selector( 'swipe-navigation' ) => 'justify-content: {{VALUE}};' ],
		] );
		$this->hadiah_slider( 'swipe_navigation_spacing', __( 'Jarak Card ke Navigasi', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-navigation' ), 'margin-top', 0, 60 );
		$this->hadiah_slider( 'swipe_navigation_gap', __( 'Jarak Panah ke Dot', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-navigation' ), 'gap', 0, 40 );
		$this->hadiah_slider( 'swipe_dot_size', __( 'Ukuran Dot', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-navigation' ), '--dh-dot-size', 4, 24 );
		$this->hadiah_slider( 'swipe_dot_gap', __( 'Jarak Antar Dot', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-dots' ), 'gap', 0, 30 );
		$this->hadiah_color( 'swipe_dot_color', __( 'Warna Dot', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-dot' ), 'background-color' );
		$this->hadiah_color( 'swipe_dot_active_color', __( 'Warna Dot Aktif', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-dot' ) . '[aria-current="true"]', 'background-color' );
		$this->hadiah_color( 'swipe_dot_hover_color', __( 'Warna Dot Hover / Focus', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-dot' ) . ':is(:hover, :focus-visible)', 'background-color' );
		$this->hadiah_dimensions( 'swipe_dot_radius', __( 'Radius Dot', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-dot' ), 'border-radius' );
		$this->hadiah_slider( 'swipe_arrow_size', __( 'Ukuran Tombol Panah', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ), 'width', 28, 64 );
		$this->hadiah_slider( 'swipe_arrow_icon_size', __( 'Ukuran Ikon Panah', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ), 'font-size', 10, 32 );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'swipe_arrow_border', 'selector' => $this->hadiah_selector( 'swipe-arrow' ) ] );
		$this->hadiah_dimensions( 'swipe_arrow_radius', __( 'Radius Tombol Panah', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ), 'border-radius' );
		$this->hadiah_color( 'swipe_arrow_color', __( 'Warna Panah', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ), 'color' );
		$this->hadiah_color( 'swipe_arrow_background', __( 'Latar Panah', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ), 'background-color' );
		$this->hadiah_color( 'swipe_arrow_hover_color', __( 'Warna Panah Hover / Focus', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ) . ':is(:hover, :focus-visible):not(:disabled)', 'color' );
		$this->hadiah_color( 'swipe_arrow_hover_background', __( 'Latar Panah Hover / Focus', 'apeiron-kit' ), $this->hadiah_selector( 'swipe-arrow' ) . ':is(:hover, :focus-visible):not(:disabled)', 'background-color' );
		$this->end_controls_section();

		foreach ( [
			'heading' => [ __( 'Judul Section', 'apeiron-kit' ), 'heading' ],
			'description' => [ __( 'Deskripsi Pembuka', 'apeiron-kit' ), 'intro' ],
			'gift_name' => [ __( 'Nama Hadiah', 'apeiron-kit' ), 'name' ],
			'gift_description' => [ __( 'Deskripsi Hadiah', 'apeiron-kit' ), 'description' ],
		] as $key => $part ) {
			$this->hadiah_section( $key, $part[0] );
			$selector = $this->hadiah_selector( $part[1] );
			$this->hadiah_text_style( $key, $selector );
			$this->hadiah_alignment( $key . '_alignment', $selector );
			$this->hadiah_dimensions( $key . '_margin', __( 'Margin', 'apeiron-kit' ), $selector, 'margin' );
			$this->end_controls_section();
		}

		$this->hadiah_section( 'card', __( 'Card Hadiah', 'apeiron-kit' ) );
		$this->hadiah_slider( 'card_max_width', __( 'Lebar Maksimum Card', 'apeiron-kit' ), '{{WRAPPER}} .apeiron-daftar-hadiah', '--dh-card-max-width', 140, 800 );
		$this->add_responsive_control( 'card_photo_share', [
			'label' => __( 'Porsi Foto Card Horizontal', 'apeiron-kit' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ '%' ], 'range' => [ '%' => [ 'min' => 20, 'max' => 60 ] ],
			'selectors' => [ '{{WRAPPER}} .apeiron-daftar-hadiah' => '--dh-card-photo-share: {{SIZE}}%;' ],
		] );
		$this->hadiah_box_style( 'card', $this->hadiah_selector( 'card' ) );
		$this->hadiah_dimensions( 'card_content_padding', __( 'Padding Isi', 'apeiron-kit' ), $this->hadiah_selector( 'body' ), 'padding' );
		$this->hadiah_slider( 'card_media_gap', __( 'Jarak Foto ke Isi', 'apeiron-kit' ), $this->hadiah_selector( 'card' ), 'gap', 0, 60 );
		$this->hadiah_slider( 'card_actions_gap', __( 'Jarak Tombol ke Link', 'apeiron-kit' ), $this->hadiah_selector( 'card-actions' ), 'gap', 0, 40 );
		$this->add_control( 'category_style_heading', [ 'label' => __( 'Kategori', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING ] );
		$this->hadiah_text_style( 'category', $this->hadiah_selector( 'category' ) );
		$this->end_controls_section();

		$this->hadiah_section( 'image', __( 'Foto Hadiah', 'apeiron-kit' ) );
		$this->hadiah_slider( 'image_width', __( 'Lebar Foto', 'apeiron-kit' ), $this->hadiah_selector( 'photo' ), 'width', 0, 100, [ '%', 'px' ] );
		$this->hadiah_slider( 'image_height', __( 'Tinggi Foto', 'apeiron-kit' ), $this->hadiah_selector( 'photo' ), 'height', 40, 600 );
		$this->add_responsive_control( 'image_ratio', [
			'label' => __( 'Aspect Ratio', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ '1 / 1' => '1:1', '4 / 3' => '4:3', '3 / 4' => '3:4', '16 / 9' => '16:9' ],
			'selectors' => [ $this->hadiah_selector( 'photo' ) => 'aspect-ratio: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'image_fit', [
			'label' => __( 'Object Fit', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ 'cover' => 'Cover', 'contain' => 'Contain', 'fill' => 'Fill', 'none' => 'None' ],
			'selectors' => [ $this->hadiah_selector( 'image' ) => 'object-fit: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'image_position', [
			'label' => __( 'Posisi Foto', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ 'center center' => __( 'Tengah', 'apeiron-kit' ), 'center top' => __( 'Atas', 'apeiron-kit' ), 'center bottom' => __( 'Bawah', 'apeiron-kit' ) ],
			'selectors' => [ $this->hadiah_selector( 'image' ) => 'object-position: {{VALUE}};' ],
		] );
		$this->hadiah_box_style( 'image', $this->hadiah_selector( 'photo' ) );
		$this->end_controls_section();

		$this->hadiah_section( 'status', __( 'Status', 'apeiron-kit' ) );
		$this->hadiah_text_style( 'status', $this->hadiah_selector( 'status' ) );
		$this->hadiah_slider( 'availability_icon_size', __( 'Ukuran Ikon Gift', 'apeiron-kit' ), $this->hadiah_selector( 'availability-icon' ), 'font-size', 10, 32 );
		$this->hadiah_color( 'availability_icon_color', __( 'Warna Ikon Gift', 'apeiron-kit' ), $this->hadiah_selector( 'availability-icon' ), 'color' );
		$this->hadiah_slider( 'availability_gap', __( 'Jarak Ikon & Jumlah', 'apeiron-kit' ), $this->hadiah_selector( 'status' ), 'gap', 0, 30 );
		$this->hadiah_color( 'available_color', __( 'Warna Tersedia', 'apeiron-kit' ), $this->hadiah_selector( 'status--available' ), 'color' );
		$this->hadiah_color( 'available_background', __( 'Latar Tersedia', 'apeiron-kit' ), $this->hadiah_selector( 'status--available' ), 'background-color' );
		$this->hadiah_color( 'unavailable_color', __( 'Warna Tidak Tersedia', 'apeiron-kit' ), $this->hadiah_selector( 'status--unavailable' ), 'color' );
		$this->hadiah_color( 'unavailable_background', __( 'Latar Tidak Tersedia', 'apeiron-kit' ), $this->hadiah_selector( 'status--unavailable' ), 'background-color' );
		$this->hadiah_dimensions( 'status_padding', __( 'Padding Status', 'apeiron-kit' ), $this->hadiah_selector( 'status' ), 'padding' );
		$this->hadiah_dimensions( 'status_radius', __( 'Radius Status', 'apeiron-kit' ), $this->hadiah_selector( 'status' ), 'border-radius' );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'status_border', 'selector' => $this->hadiah_selector( 'status' ) ] );
		$this->add_control( 'quantity_style_heading', [ 'label' => __( 'Jumlah Awal', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->hadiah_text_style( 'quantity', $this->hadiah_selector( 'quantity' ) );
		$this->hadiah_text_style( 'manual_note', $this->hadiah_selector( 'manual-note' ) );
		$this->end_controls_section();

		$this->hadiah_section( 'buttons', __( 'Tombol', 'apeiron-kit' ) );
		$this->hadiah_button_style( 'primary', __( 'Pilih & Konfirmasi', 'apeiron-kit' ) );
		$this->hadiah_button_style( 'secondary', __( 'Link Toko di Popup', 'apeiron-kit' ) );
		$this->add_control( 'purchase_style_heading', [ 'label' => __( 'Link Pembelian', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->hadiah_text_style( 'purchase', $this->hadiah_selector( 'button--secondary' ) );
		$this->hadiah_color( 'purchase_hover_color', __( 'Warna Hover / Focus', 'apeiron-kit' ), $this->hadiah_selector( 'button--secondary' ) . ':is(:hover, :focus-visible)', 'color' );
		$this->end_controls_section();

		$this->hadiah_section( 'popup', __( 'Popup', 'apeiron-kit' ) );
		$this->hadiah_box_style( 'popup', $this->hadiah_selector( 'dialog' ) );
		$this->hadiah_slider( 'popup_width', __( 'Lebar Popup', 'apeiron-kit' ), $this->hadiah_selector( 'dialog' ), 'width', 280, 1000, [ 'px', 'vw' ] );
		$this->hadiah_slider( 'popup_gap', __( 'Gap Popup', 'apeiron-kit' ), $this->hadiah_selector( 'dialog-content' ), 'gap', 0, 60 );
		$this->hadiah_slider( 'popup_image_width', __( 'Lebar Foto Popup', 'apeiron-kit' ), $this->hadiah_selector( 'gallery' ) . ' .apeiron-daftar-hadiah__photo', 'width', 60, 600, [ 'px', '%' ] );
		$this->hadiah_slider( 'popup_media_share', __( 'Lebar Galeri di Popup Lebar', 'apeiron-kit' ), $this->hadiah_selector( 'dialog' ), '--dh-popup-media-share', 30, 60, [ '%' ] );
		$this->hadiah_slider( 'popup_summary_gap', __( 'Jarak Informasi Hadiah', 'apeiron-kit' ), $this->hadiah_selector( 'summary-text' ), 'gap', 0, 40 );
		$this->add_control( 'popup_gift_style_heading', [ 'label' => __( 'Nama Hadiah di Popup', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING ] );
		$this->hadiah_text_style( 'popup_gift_name', $this->hadiah_selector( 'dialog' ) . ' .apeiron-daftar-hadiah__name' );
		$this->add_control( 'popup_photo_style_heading', [ 'label' => __( 'Galeri Popup', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_responsive_control( 'popup_image_ratio', [
			'label' => __( 'Rasio Foto Popup', 'apeiron-kit' ), 'type' => Controls_Manager::SELECT,
			'options' => [ '1 / 1' => '1:1', '4 / 3' => '4:3', '3 / 4' => '3:4', '16 / 9' => '16:9' ],
			'default' => '1 / 1', 'tablet_default' => '4 / 3', 'mobile_default' => '4 / 3',
			'selectors' => [ $this->hadiah_selector( 'gallery' ) . ' .apeiron-daftar-hadiah__photo' => 'aspect-ratio: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'popup_image_height', [
			'label' => __( 'Tinggi Foto Popup', 'apeiron-kit' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ], 'range' => [ 'px' => [ 'min' => 40, 'max' => 600 ] ],
			'description' => __( 'Kosongkan untuk mengikuti rasio foto.', 'apeiron-kit' ),
			'selectors' => [ $this->hadiah_selector( 'gallery' ) . ' .apeiron-daftar-hadiah__photo' => 'height: {{SIZE}}px;' ],
		] );
		$this->hadiah_slider( 'gallery_gap', __( 'Jarak Foto ke Thumbnail', 'apeiron-kit' ), $this->hadiah_selector( 'gallery' ), 'gap', 0, 40 );
		$this->hadiah_slider( 'thumbnail_size', __( 'Ukuran Thumbnail', 'apeiron-kit' ), $this->hadiah_selector( 'thumbnail' ), 'width', 32, 120 );
		$this->hadiah_slider( 'thumbnail_gap', __( 'Jarak Thumbnail', 'apeiron-kit' ), $this->hadiah_selector( 'thumbnails' ), 'gap', 0, 30 );
		$this->hadiah_dimensions( 'thumbnail_radius', __( 'Radius Thumbnail', 'apeiron-kit' ), $this->hadiah_selector( 'thumbnail' ), 'border-radius' );
		$this->hadiah_color( 'thumbnail_active_color', __( 'Border Thumbnail Aktif', 'apeiron-kit' ), $this->hadiah_selector( 'thumbnail' ) . '[aria-current="true"]', 'border-color' );
		$this->hadiah_color( 'gallery_arrow_color', __( 'Warna Panah Galeri', 'apeiron-kit' ), $this->hadiah_selector( 'gallery-arrow' ), 'color' );
		$this->hadiah_color( 'gallery_arrow_background', __( 'Latar Panah Galeri', 'apeiron-kit' ), $this->hadiah_selector( 'gallery-arrow' ), 'background-color' );
		$this->hadiah_color( 'popup_overlay', __( 'Warna Overlay', 'apeiron-kit' ), $this->hadiah_selector( 'dialog' ) . '::backdrop', 'background-color' );
		$this->add_control( 'popup_title_style_heading', [ 'label' => __( 'Judul Popup', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING ] );
		$this->hadiah_text_style( 'popup_title', $this->hadiah_selector( 'popup-title' ) );
		$this->hadiah_alignment( 'popup_alignment', $this->hadiah_selector( 'dialog-content' ) );
		$this->hadiah_color( 'close_color', __( 'Warna Close', 'apeiron-kit' ), $this->hadiah_selector( 'close' ), 'color' );
		$this->hadiah_color( 'close_background', __( 'Latar Close', 'apeiron-kit' ), $this->hadiah_selector( 'close' ), 'background-color' );
		$this->hadiah_color( 'close_hover_color', __( 'Warna Close Hover / Focus', 'apeiron-kit' ), $this->hadiah_selector( 'close' ) . ':is(:hover, :focus-visible)', 'color' );
		$this->hadiah_slider( 'close_size', __( 'Ukuran Close', 'apeiron-kit' ), $this->hadiah_selector( 'close' ), 'width', 28, 60 );
		$this->hadiah_dimensions( 'close_radius', __( 'Radius Close', 'apeiron-kit' ), $this->hadiah_selector( 'close' ), 'border-radius' );
		$this->add_control( 'popup_footer_heading', [ 'label' => __( 'Area Tombol (Tetap di Mobile)', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->hadiah_color( 'popup_footer_background', __( 'Latar Area Tombol', 'apeiron-kit' ), $this->hadiah_selector( 'popup-footer' ), 'background-color' );
		$this->hadiah_dimensions( 'popup_footer_padding', __( 'Padding Area Tombol', 'apeiron-kit' ), $this->hadiah_selector( 'popup-footer' ), 'padding' );
		$this->hadiah_slider( 'popup_footer_gap', __( 'Jarak Antar Tombol', 'apeiron-kit' ), $this->hadiah_selector( 'form-actions' ), 'gap', 0, 40 );
		$this->end_controls_section();

		$this->hadiah_section( 'form', __( 'Formulir', 'apeiron-kit' ) );
		$this->hadiah_box_style( 'form', $this->hadiah_selector( 'form' ) );
		$this->hadiah_slider( 'form_gap', __( 'Gap Field', 'apeiron-kit' ), $this->hadiah_selector( 'form' ), 'gap', 0, 60 );
		$this->hadiah_text_style( 'label', $this->hadiah_selector( 'label' ) );
		$this->hadiah_slider( 'label_spacing', __( 'Jarak Label ke Input', 'apeiron-kit' ), $this->hadiah_selector( 'field' ), 'gap', 0, 40 );
		$this->hadiah_text_style( 'input', $this->hadiah_selector( 'input' ) );
		$this->hadiah_dimensions( 'input_padding', __( 'Padding Input', 'apeiron-kit' ), $this->hadiah_selector( 'input' ), 'padding' );
		$this->hadiah_dimensions( 'input_radius', __( 'Radius Input', 'apeiron-kit' ), $this->hadiah_selector( 'input' ), 'border-radius' );
		$this->hadiah_slider( 'input_height', __( 'Tinggi Minimum Input', 'apeiron-kit' ), $this->hadiah_selector( 'input' ), 'min-height', 32, 100 );
		$this->hadiah_color( 'placeholder_color', __( 'Warna Placeholder', 'apeiron-kit' ), $this->hadiah_selector( 'input' ) . '::placeholder', 'color' );
		$this->start_controls_tabs( 'input_state_tabs' );
		foreach ( [ 'normal' => '', 'hover' => ':hover', 'focus' => ':focus', 'invalid' => '[aria-invalid="true"]' ] as $state => $suffix ) {
			$this->start_controls_tab( 'input_state_' . $state, [ 'label' => ucfirst( $state ) ] );
			$selector = $this->hadiah_selector( 'input' ) . $suffix;
			$this->hadiah_color( 'input_' . $state . '_color', __( 'Warna Teks', 'apeiron-kit' ), $selector, 'color' );
			$this->hadiah_color( 'input_' . $state . '_background', __( 'Background', 'apeiron-kit' ), $selector, 'background-color' );
			$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'input_' . $state . '_border', 'selector' => $selector ] );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_control( 'notice_style_heading', [ 'label' => __( 'Keterangan & Validasi', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->hadiah_text_style( 'notice', $this->hadiah_selector( 'notice' ) );
		$this->hadiah_color( 'error_color', __( 'Warna Pesan Error', 'apeiron-kit' ), '{{WRAPPER}} .apeiron-daftar-hadiah', '--dh-error' );
		$this->hadiah_color( 'notice_background', __( 'Latar Keterangan', 'apeiron-kit' ), $this->hadiah_selector( 'notice' ), 'background-color' );
		$this->hadiah_dimensions( 'notice_padding', __( 'Padding Keterangan', 'apeiron-kit' ), $this->hadiah_selector( 'notice' ), 'padding' );
		$this->hadiah_dimensions( 'notice_radius', __( 'Radius Keterangan', 'apeiron-kit' ), $this->hadiah_selector( 'notice' ), 'border-radius' );
		$this->end_controls_section();

		$this->hadiah_section( 'empty', __( 'Empty State', 'apeiron-kit' ) );
		$this->hadiah_box_style( 'empty', $this->hadiah_selector( 'empty' ) );
		$this->hadiah_text_style( 'empty', $this->hadiah_selector( 'empty' ) );
		$this->hadiah_alignment( 'empty_alignment', $this->hadiah_selector( 'empty' ) );
		$this->end_controls_section();
	}

	private function hadiah_section( string $name, string $label ): void {
		$this->start_controls_section( 'section_style_' . $name, [ 'label' => $label, 'tab' => Controls_Manager::TAB_STYLE ] );
	}

	private function hadiah_selector( string $part ): string {
		return '{{WRAPPER}} .apeiron-daftar-hadiah .apeiron-daftar-hadiah__' . $part;
	}

	private function hadiah_text_style( string $name, string $selector ): void {
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => $name . '_typography', 'selector' => $selector,
			'fields_options' => [ 'font_family' => [ 'default' => 'Rubik' ] ],
		] );
		$this->hadiah_color( $name . '_color', __( 'Warna Teks', 'apeiron-kit' ), $selector, 'color' );
	}

	private function hadiah_box_style( string $name, string $selector ): void {
		$this->add_group_control( Group_Control_Background::get_type(), [ 'name' => $name . '_background', 'types' => [ 'classic', 'gradient' ], 'selector' => $selector ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => $name . '_border', 'selector' => $selector ] );
		$this->hadiah_dimensions( $name . '_radius', __( 'Border Radius', 'apeiron-kit' ), $selector, 'border-radius' );
		$this->hadiah_dimensions( $name . '_padding', __( 'Padding', 'apeiron-kit' ), $selector, 'padding' );
	}

	private function hadiah_color( string $name, string $label, string $selector, string $property ): void {
		$this->add_control( $name, [ 'label' => $label, 'type' => Controls_Manager::COLOR, 'selectors' => [ $selector => $property . ': {{VALUE}};' ] ] );
	}

	private function hadiah_dimensions( string $name, string $label, string $selector, string $property ): void {
		$this->add_responsive_control( $name, [
			'label' => $label, 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
	}

	private function hadiah_slider( string $name, string $label, string $selector, string $property, int $min, int $max, array $units = [ 'px' ], string $extra = '' ): void {
		$range = [];
		foreach ( $units as $unit ) {
			$range[ $unit ] = [ 'min' => 'px' === $unit ? $min : 0, 'max' => 'px' === $unit ? max( $max, 100 ) : 100 ];
		}
		$this->add_responsive_control( $name, [
			'label' => $label, 'type' => Controls_Manager::SLIDER, 'size_units' => $units, 'range' => $range,
			'selectors' => [ $selector => $property . ': {{SIZE}}{{UNIT}};' . $extra ],
		] );
	}

	private function hadiah_alignment( string $name, string $selector ): void {
		$this->add_responsive_control( $name, [
			'label' => __( 'Perataan', 'apeiron-kit' ), 'type' => Controls_Manager::CHOOSE,
			'options' => [
				'left' => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
				'right' => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [ $selector => 'text-align: {{VALUE}};' ],
		] );
	}

	private function hadiah_button_style( string $kind, string $label ): void {
		$selector = $this->hadiah_selector( 'button--' . $kind );
		$this->add_control( $kind . '_button_heading', [ 'label' => $label, 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => $kind . '_button_typography', 'selector' => $selector ] );
		$this->hadiah_dimensions( $kind . '_button_padding', __( 'Padding', 'apeiron-kit' ), $selector, 'padding' );
		$this->hadiah_dimensions( $kind . '_button_radius', __( 'Border Radius', 'apeiron-kit' ), $selector, 'border-radius' );
		$this->hadiah_slider( $kind . '_button_height', __( 'Tinggi Minimum', 'apeiron-kit' ), $selector, 'min-height', 32, 100 );
		$this->hadiah_slider( $kind . '_button_width', __( 'Lebar', 'apeiron-kit' ), $selector, 'width', 40, 600, [ '%', 'px' ], 'flex: 0 0 auto;' );
		$this->start_controls_tabs( $kind . '_button_states' );
		foreach ( [ 'normal' => '', 'hover' => ':hover:not(:disabled)', 'focus' => ':focus-visible', 'active' => ':active:not(:disabled)', 'disabled' => ':disabled' ] as $state => $suffix ) {
			$this->start_controls_tab( $kind . '_button_' . $state, [ 'label' => ucfirst( $state ) ] );
			$this->hadiah_color( $kind . '_button_' . $state . '_color', __( 'Warna Teks', 'apeiron-kit' ), $selector . $suffix, 'color' );
			$this->hadiah_color( $kind . '_button_' . $state . '_background', __( 'Background', 'apeiron-kit' ), $selector . $suffix, 'background-color' );
			$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => $kind . '_button_' . $state . '_border', 'selector' => $selector . $suffix ] );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
	}
}
