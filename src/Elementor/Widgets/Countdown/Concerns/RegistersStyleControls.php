<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Countdown\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
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
		$this->register_item_style_controls();
		$this->register_typography_style_controls();
		$this->register_separator_style_controls();
		$this->register_expired_style_controls();
	}

	private function register_layout_style_controls(): void {
		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => __( 'Layout', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'countdown_position',
			[
				'label'                => __( 'Arah Tampilan', 'apeiron-kit' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'inline',
				'options'              => [
					'inline' => __( 'Horizontal', 'apeiron-kit' ),
					'block'  => __( 'Vertikal', 'apeiron-kit' ),
				],
				'selectors'            => [
					'{{WRAPPER}} .apeiron-kit-countdown-wrapper' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'inline' => '--apeiron-countdown-direction: row; --apeiron-countdown-width: 252px;',
					'block'  => '--apeiron-countdown-direction: column; --apeiron-countdown-width: 74px;',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_width',
			[
				'label'       => __( 'Lebar Timer', 'apeiron-kit' ),
				'type'        => Controls_Manager::SLIDER,
				'description' => __( 'Kosongkan untuk mengikuti arah tampilan: Horizontal 252px, Vertikal 74px.', 'apeiron-kit' ),
				'size_units'  => [ 'px', '%', 'em' ],
				'range'       => [
					'px' => [ 'min' => 0, 'max' => 1200 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
					'em' => [ 'min' => 0, 'max' => 80 ],
				],
				'selectors'   => [
					'{{WRAPPER}} .apeiron-kit-countdown-wrapper' => '--apeiron-countdown-width: {{SIZE}}{{UNIT}}; width: var(--apeiron-countdown-width, 252px); max-width: 100%;',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_alignment',
			[
				'label'                => __( 'Perataan Timer di Halaman', 'apeiron-kit' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'              => 'center',
				'toggle'               => false,
				'selectors'            => [
					'{{WRAPPER}} .apeiron-kit-countdown-wrapper' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'margin-left: 0; margin-right: auto;',
					'center' => 'margin-left: auto; margin-right: auto;',
					'right'  => 'margin-left: auto; margin-right: 0;',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_container_style_controls(): void {
		$this->start_controls_section(
			'section_style_wrapper',
			[
				'label' => __( 'Container Timer', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_wrapper_style_controls(
			'wrapper',
			'{{WRAPPER}} .apeiron-kit-countdown-wrapper',
			[
				'background_color' => self::WRAPPER_DEFAULTS['background_color'],
				'border'           => self::WRAPPER_DEFAULTS['border'],
				'border_color'     => self::WRAPPER_DEFAULTS['border_color'],
				'border_radius'    => self::WRAPPER_DEFAULTS['border_radius'],
				'padding'          => self::WRAPPER_DEFAULTS['padding'],
			]
		);

		$this->end_controls_section();
	}

	private function register_wrapper_style_controls( string $prefix, string $selector, array $defaults = [] ): void {
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => $prefix . '_background',
				'label'          => __( 'Background', 'apeiron-kit' ),
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => $selector,
				'fields_options' => [
					'background' => [ 'default' => $defaults['background_type'] ?? 'classic' ],
					'color'      => [ 'default' => $defaults['background_color'] ?? '' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => $prefix . '_border',
				'selector'       => $selector,
				'fields_options' => [
					'border' => [ 'default' => $defaults['border']['default'] ?? 'solid' ],
					'width'  => [
						'default' => $defaults['border'] ?? [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color'  => [ 'default' => $defaults['border_color'] ?? '#e5e7ef' ],
				],
			]
		);

		$this->add_responsive_control(
			$prefix . '_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => $defaults['border_radius'] ?? [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
				],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => $prefix . '_box_shadow',
				'selector'       => $selector,
				'fields_options' => $defaults['box_shadow'] ?? [
					'box_shadow' => [
						'default' => [
							'horizontal' => 0,
							'vertical'   => 0,
							'blur'       => 0,
							'spread'     => 0,
							'color'      => 'rgba(0,0,0,0)',
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			$prefix . '_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => $defaults['padding'] ?? [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
				],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

	private function register_item_style_controls(): void {
		$this->start_controls_section(
			'section_style_item',
			[
				'label' => __( 'Kotak Angka', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_item_style' );

		$this->start_controls_tab(
			'tab_item_normal',
			[ 'label' => __( 'Normal', 'apeiron-kit' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'item_background',
				'label'          => __( 'Background', 'apeiron-kit' ),
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__item',
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#ffffff' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'item_border',
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__item',
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color'  => [ 'default' => '#e5e7ef' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'item_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__item',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_item_hover',
			[ 'label' => __( 'Hover', 'apeiron-kit' ) ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'item_hover_background',
				'label'          => __( 'Background', 'apeiron-kit' ),
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__item:hover',
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#fbfcff' ],
				],
			]
		);

		$this->add_control(
			'item_hover_value_color',
			[
				'label'     => __( 'Warna Angka', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__item:hover .apeiron-kit-countdown__value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'item_hover_label_color',
			[
				'label'     => __( 'Warna Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__item:hover .apeiron-kit-countdown__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'item_hover_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d6dae5',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__item:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'item_hover_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__item:hover',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'item_radius',
			[
				'label'      => __( 'Radius Kotak', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_padding',
			[
				'label'      => __( 'Padding Kotak', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '7',
					'right'    => '10',
					'bottom'   => '7',
					'left'     => '10',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_min_width',
			[
				'label'      => __( 'Lebar Minimum Kotak', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 400 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
					'em' => [ 'min' => 0, 'max' => 30 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__item' => 'min-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_gap',
			[
				'label'     => __( 'Jarak Antar Kotak', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'   => [ 'size' => 6, 'unit' => 'px' ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_alignment',
			[
				'label'                => __( 'Perataan Isi Dalam Kotak', 'apeiron-kit' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'              => 'center',
				'toggle'               => false,
				'selectors'            => [
					'{{WRAPPER}} .apeiron-kit-countdown__item' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'left'   => 'align-items: flex-start; text-align: left;',
					'center' => 'align-items: center; text-align: center;',
					'right'  => 'align-items: flex-end; text-align: right;',
				],
			]
		);

		$this->add_responsive_control(
			'items_alignment',
			[
				'label'     => __( 'Perataan Barisan Kotak', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center'     => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'flex-end'   => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'item_transition_duration',
			[
				'label'      => __( 'Durasi Transisi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 's' ],
				'range'      => [ 's' => [ 'min' => 0, 'max' => 2, 'step' => 0.1 ] ],
				'default'    => [ 'size' => 0.2, 'unit' => 's' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__item' => 'transition-duration: {{SIZE}}s;',
				],
			]
		);

		$this->add_control(
			'item_transition_timing',
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
					'{{WRAPPER}} .apeiron-kit-countdown__item' => 'transition-timing-function: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_typography_style_controls(): void {
		$this->start_controls_section(
			'section_style_typography',
			[
				'label' => __( 'Teks dan Warna', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'value_heading',
			[
				'label' => __( 'Angka', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => 'value_typography',
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__value',
				'fields_options' => [
					'font_size'   => [ 'default' => [ 'size' => 22, 'unit' => 'px' ] ],
					'font_weight' => [ 'default' => '600' ],
					'line_height' => [ 'default' => [ 'size' => 1.1, 'unit' => 'em' ] ],
				],
			]
		);

		$this->add_control(
			'value_color',
			[
				'label'     => __( 'Warna Angka', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'label_heading',
			[
				'label'     => __( 'Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => 'label_typography',
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__label',
				'fields_options' => [
					'font_size'   => [ 'default' => [ 'size' => 11, 'unit' => 'px' ] ],
					'font_weight' => [ 'default' => '400' ],
					'line_height' => [ 'default' => [ 'size' => 1.35, 'unit' => 'em' ] ],
				],
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __( 'Warna Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7890',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'label_spacing',
			[
				'label'     => __( 'Jarak Angka ke Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'default'   => [ 'size' => 3, 'unit' => 'px' ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__label' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	private function register_separator_style_controls(): void {
		$this->start_controls_section(
			'section_style_separator',
			[
				'label'     => __( 'Pemisah', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_separator' => 'yes' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => 'separator_typography',
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__separator',
				'fields_options' => [
					'font_size'   => [ 'default' => [ 'size' => 18, 'unit' => 'px' ] ],
					'font_weight' => [ 'default' => '700' ],
				],
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7890',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__separator' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'separator_background',
				'label'    => __( 'Background', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__separator',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'separator_border',
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__separator',
			]
		);

		$this->add_responsive_control(
			'separator_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__separator' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'separator_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '0',
					'right'    => '1',
					'bottom'   => '0',
					'left'     => '1',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__separator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'separator_margin',
			[
				'label'      => __( 'Margin', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__separator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'separator_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__separator',
			]
		);

		$this->end_controls_section();
	}

	private function register_expired_style_controls(): void {
		$this->start_controls_section(
			'section_style_expired',
			[
				'label'     => __( 'Pesan Waktu Habis', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'expired_text!' => '' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'expired_typography',
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__expired',
			]
		);

		$this->add_control(
			'expired_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__expired' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'expired_background',
				'label'          => __( 'Background', 'apeiron-kit' ),
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__expired',
				'fields_options' => [
					'background' => [ 'default' => 'classic' ],
					'color'      => [ 'default' => '#ffffff' ],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'expired_border',
				'selector'       => '{{WRAPPER}} .apeiron-kit-countdown__expired',
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color'  => [ 'default' => '#e5e7ef' ],
				],
			]
		);

		$this->add_responsive_control(
			'expired_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__expired' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'expired_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '10',
					'right'    => '12',
					'bottom'   => '10',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__expired' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'expired_margin',
			[
				'label'      => __( 'Margin', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '4',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__expired' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'expired_width',
			[
				'label'      => __( 'Lebar', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 1200 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
					'em' => [ 'min' => 0, 'max' => 80 ],
				],
				'default'    => [ 'size' => 100, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-countdown__expired' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'expired_alignment',
			[
				'label'     => __( 'Perataan Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'center',
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown__expired' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'expired_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-kit-countdown__expired',
			]
		);

		$this->add_control(
			'expired_items_opacity',
			[
				'label'     => __( 'Opacity Kotak Setelah Selesai', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ '' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
				'default'   => [ 'size' => 0.55 ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-countdown' => '--apeiron-countdown-expired-opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
