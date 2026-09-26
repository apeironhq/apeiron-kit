<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SocialProof\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->register_layout_style_controls();
		$this->register_container_style_controls();
		$this->register_close_button_style_controls();
		$this->register_image_style_controls();
		$this->register_typography_style_controls();
	}

	private function register_text_style( string $prefix, string $selector, string $color_default ): void {
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => $prefix . '_typography',
				'selector' => $selector,
			]
		);

		$this->add_control(
			$prefix . '_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => $color_default,
				'selectors' => [ $selector => 'color: {{VALUE}};' ],
			]
		);
	}

	private function register_layout_style_controls(): void {
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => __( 'Layout', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'animation_type',
			[
				'label'   => __( 'Animasi Popup', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'global'       => __( 'Ikuti Dashboard', 'apeiron-kit' ),
					'fade'         => __( 'Fade', 'apeiron-kit' ),
					'slide-top'    => __( 'Slide dari Atas', 'apeiron-kit' ),
					'slide-bottom' => __( 'Slide dari Bawah', 'apeiron-kit' ),
					'slide-left'   => __( 'Slide dari Kiri', 'apeiron-kit' ),
					'slide-right'  => __( 'Slide dari Kanan', 'apeiron-kit' ),
				],
				'default' => 'global',
			]
		);

		$this->add_responsive_control(
			'popup_width',
			[
				'label'      => __( 'Lebar Popup', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 250, 'max' => 500, 'step' => 10 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
				],
				'default'    => [ 'size' => 300, 'unit' => 'px' ],
				'selectors'  => [
					self::POPUP => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'popup_offset',
			[
				'label'       => __( 'Jarak Popup dari Tepi', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'em' ],
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 80 ],
					'em' => [ 'min' => 0, 'max' => 20 ],
				],
				'default'     => [ 'size' => 20, 'unit' => 'px' ],
				'description' => __( 'Mengatur jarak popup pada sisi horizontal dan vertikal.', 'apeiron-kit' ),
				'selectors'   => [
					self::POPUP => '--apeiron-sp-offset: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'popup_z_index',
			[
				'label'     => __( 'Lapisan Popup (Z-index)', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 2147483647,
				'default'   => 9999,
				'selectors' => [
					self::POPUP => 'z-index: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'animation_distance',
			[
				'label'      => __( 'Jarak Animasi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 100 ],
					'em' => [ 'min' => 0, 'max' => 8 ],
				],
				'default'    => [ 'size' => 24, 'unit' => 'px' ],
				'condition'  => [ 'animation_type!' => 'fade' ],
				'selectors'  => [
					self::POPUP => '--apeiron-sp-distance: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'popup_animation_duration',
			[
				'label'       => __( 'Durasi Animasi', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 's' ],
				'range'       => [ 's' => [ 'min' => 0.1, 'max' => 2, 'step' => 0.1 ] ],
				'default'     => [ 'size' => 0.4, 'unit' => 's' ],
				'render_type' => 'template',
				'selectors'   => [
					self::POPUP => '--apeiron-sp-duration: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_container_style_controls(): void {
		$this->start_controls_section(
			'section_style_popup',
			[
				'label' => __( 'Container Popup', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'popup_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [ 'top' => 14, 'right' => 14, 'bottom' => 14, 'left' => 14, 'unit' => 'px', 'isLinked' => true ],
				'selectors'  => [
					self::POPUP => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'popup_background_color',
			[
				'label'     => __( 'Warna Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					self::POPUP => '--apeiron-surface: {{VALUE}}; background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->add_control(
			'popup_text_color',
			[
				'label'     => __( 'Warna Teks Dasar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					self::POPUP => '--apeiron-text: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'popup_border',
				'selector'       => self::POPUP,
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [ 'default' => [ 'top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'unit' => 'px', 'isLinked' => true ] ],
					'color'  => [ 'default' => 'rgba(15, 23, 42, 0.06)' ],
				],
			]
		);

		$this->add_responsive_control(
			'popup_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [ 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'unit' => 'px', 'isLinked' => true ],
				'selectors'  => [
					self::POPUP => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'popup_box_shadow',
				'selector'       => self::POPUP,
				'fields_options' => [
					'box_shadow_type' => [
						'default'     => 'yes',
						'render_type' => 'template',
					],
					'box_shadow'      => [
						'default' => [
							'horizontal' => 0,
							'vertical'   => 6,
							'blur'       => 20,
							'spread'     => 0,
							'color'      => 'rgba(15, 23, 42, 0.035)',
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'content_gap',
			[
				'label'      => __( 'Jarak Gambar dan Teks', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
					'em' => [ 'min' => 0, 'max' => 5 ],
				],
				'default'    => [ 'size' => 12, 'unit' => 'px' ],
				'selectors'  => [
					self::POPUP => '--apeiron-sp-gap: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();
	}

	private function register_close_button_style_controls(): void {
		$this->start_controls_section(
			'section_style_close_button',
			[
				'label' => __( 'Tombol Tutup', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_close_button_style' );

		$this->start_controls_tab( 'tab_close_button_normal', [ 'label' => __( 'Normal', 'apeiron-kit' ) ] );
		$this->add_control(
			'close_button_color',
			[
				'label'     => __( 'Warna Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [ self::CLOSE => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'close_button_background',
			[
				'label'     => __( 'Warna Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f9fafb',
				'selectors' => [ self::CLOSE => 'background-color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'close_button_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'selectors' => [ self::CLOSE => 'border-color: {{VALUE}};' ],
			]
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_close_button_hover', [ 'label' => __( 'Hover', 'apeiron-kit' ) ] );
		$this->add_control(
			'close_button_hover_color',
			[
				'label'     => __( 'Warna Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [ self::CLOSE . ':hover' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'close_button_hover_background',
			[
				'label'     => __( 'Warna Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f3f7fa',
				'selectors' => [ self::CLOSE . ':hover' => 'background-color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'close_button_hover_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d6dae5',
				'selectors' => [ self::CLOSE . ':hover' => 'border-color: {{VALUE}};' ],
			]
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_close_button_active', [ 'label' => __( 'Active', 'apeiron-kit' ) ] );
		$this->add_control(
			'close_button_active_color',
			[
				'label'     => __( 'Warna Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [ self::CLOSE . ':active' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'close_button_active_background',
			[
				'label'     => __( 'Warna Background', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f3f7fa',
				'selectors' => [ self::CLOSE . ':active' => 'background-color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'close_button_active_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8, 60, 87, 0.4)',
				'selectors' => [
					self::CLOSE . ':active'         => 'border-color: {{VALUE}};',
					self::CLOSE . ':focus-visible'  => 'border-color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'close_button_size',
			[
				'label'      => __( 'Ukuran Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 16, 'max' => 48 ],
					'em' => [ 'min' => 1, 'max' => 4 ],
				],
				'default'    => [ 'size' => 28, 'unit' => 'px' ],
				'selectors'  => [
					self::POPUP => '--apeiron-sp-close-size: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'close_button_radius',
			[
				'label'      => __( 'Radius Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [ 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'unit' => 'px', 'isLinked' => true ],
				'selectors'  => [
					self::CLOSE => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'close_button_offset',
			[
				'label'      => __( 'Jarak dari Tepi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 30 ],
					'em' => [ 'min' => 0, 'max' => 3 ],
				],
				'default'    => [ 'size' => 8, 'unit' => 'px' ],
				'selectors'  => [
					self::CLOSE => 'top: {{SIZE}}{{UNIT}}; right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'close_button_icon_size',
			[
				'label'      => __( 'Ukuran Ikon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 32 ],
					'em' => [ 'min' => 0.5, 'max' => 2 ],
				],
				'default'    => [ 'size' => 16, 'unit' => 'px' ],
				'selectors'  => [
					self::CLOSE => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_image_style_controls(): void {
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => __( 'Foto Pelanggan', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_size',
			[
				'label'      => __( 'Ukuran', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 32, 'max' => 120, 'step' => 2 ],
					'em' => [ 'min' => 2, 'max' => 8 ],
				],
				'default'    => [ 'size' => 40, 'unit' => 'px' ],
				'selectors'  => [
					self::POPUP => '--apeiron-sp-image-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// DIMENSIONS preserves values saved before the token refactor.
		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'       => __( 'Border Radius', 'apeiron-kit' ),
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => [ 'px', '%' ],
				'description' => __( 'Kosongkan untuk mengikuti pengaturan dashboard.', 'apeiron-kit' ),
				'selectors'   => [
					self::POPUP => '--apeiron-sp-image-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'image_background_color',
			[
				'label'     => __( 'Background (Placeholder)', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f9fafb',
				'selectors' => [
					self::IMAGE => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'selectors' => [
					self::IMAGE                => 'border-color: {{VALUE}};',
					self::IMAGE . ' img'       => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_placeholder_icon_color',
			[
				'label'     => __( 'Warna Ikon Placeholder', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#98a2b3',
				'selectors' => [
					'{{WRAPPER}} .apeiron-social-proof__placeholder-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_placeholder_icon_size',
			[
				'label'      => __( 'Ukuran Ikon Placeholder', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 12, 'max' => 48 ],
					'em' => [ 'min' => 1, 'max' => 4 ],
				],
				'default'    => [ 'size' => 22, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-social-proof__placeholder-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_typography_style_controls(): void {
		$this->start_controls_section(
			'section_style_text',
			[
				'label' => __( 'Teks', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_alignment_control();

		$this->add_control( 'name_heading', [ 'label' => __( 'Nama Pelanggan', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->register_text_style( 'name', '{{WRAPPER}} .apeiron-social-proof__name, {{WRAPPER}} .apeiron-social-proof__inline-name', '#1d2939' );

		$this->add_control( 'desc_heading', [ 'label' => __( 'Deskripsi', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->register_text_style( 'desc', '{{WRAPPER}} .apeiron-social-proof__description', '#475467' );

		$this->add_control( 'product_heading', [ 'label' => __( 'Nama Produk/Tema', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->register_text_style( 'product', '{{WRAPPER}} .apeiron-social-proof__product', '#1d2939' );

		$this->add_control( 'date_heading', [ 'label' => __( 'Tanggal', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->register_text_style( 'date', '{{WRAPPER}} .apeiron-social-proof__date', '#98a2b3' );

		$this->add_control( 'spacing_heading', [ 'label' => __( 'Jarak Antar Teks', 'apeiron-kit' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_responsive_control(
			'spacing_name_desc',
			[
				'label'      => __( 'Nama → Deskripsi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 20 ], 'em' => [ 'min' => 0, 'max' => 2 ] ],
				'default'    => [ 'size' => 4, 'unit' => 'px' ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-social-proof__name' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
			]
		);
		$this->add_responsive_control(
			'spacing_desc_date',
			[
				'label'      => __( 'Deskripsi → Tanggal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 20 ], 'em' => [ 'min' => 0, 'max' => 2 ] ],
				'default'    => [ 'size' => 4, 'unit' => 'px' ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-social-proof__description' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_alignment_control(): void {
		$this->add_responsive_control(
			'text_alignment',
			[
				'label'     => __( 'Perataan', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'left',
				'selectors' => [ self::TEXT => 'text-align: {{VALUE}};' ],
			]
		);
	}
}
