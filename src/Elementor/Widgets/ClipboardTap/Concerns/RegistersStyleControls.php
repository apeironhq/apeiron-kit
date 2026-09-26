<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\ClipboardTap\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Plain color control IDs are persisted by older Elementor documents. */
trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->register_button_style_controls();
		$this->register_icon_style_controls();
	}

	private function register_button_style_controls(): void {
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => __( 'Tombol', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'label'    => __( 'Tipografi Tombol', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-kit-clipboard-tap',
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => __( 'Normal', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'background_color',
			[
				'label'     => __( 'Warna Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap' => 'background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => __( 'Hover', 'apeiron-kit' ),
			]
		);

		// :focus-visible avoids retaining hover colors after mouse clicks.
		$this->add_control(
			'hover_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap:focus-visible' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_hover_color',
			[
				'label'     => __( 'Warna Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap:focus-visible' => 'background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => __( 'Warna Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [
					'border_border!' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap:focus-visible' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'border',
				'selector'       => '{{WRAPPER}} .apeiron-kit-clipboard-tap',
				'separator'      => 'before',
				'fields_options' => [
					'border' => [
						'default' => 'none',
					],
				],
			]
		);

		$this->add_responsive_control(
			'border_radius',
			[
				'label'      => __( 'Sudut Tombol', 'apeiron-kit' ),
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
					'{{WRAPPER}} .apeiron-kit-clipboard-tap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_box_shadow_normal',
				'label'    => __( 'Bayangan Tombol', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-kit-clipboard-tap:not(.is-copied)',
			]
		);

		$this->add_responsive_control(
			'text_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => '12',
					'right'    => '18',
					'bottom'   => '12',
					'left'     => '18',
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'button_transition_duration',
			[
				'label'      => __( 'Durasi Transisi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 's' ],
				'range'      => [
					's' => [ 'min' => 0, 'max' => 2, 'step' => 0.1 ],
				],
				'default'    => [ 'size' => 0.2, 'unit' => 's' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap' => 'transition-duration: {{SIZE}}s;',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'button_transition_timing',
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
					'{{WRAPPER}} .apeiron-kit-clipboard-tap' => 'transition-timing-function: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'copied_heading',
			[
				'label'     => __( 'Status Berhasil', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_copied_color',
			[
				'label'     => __( 'Warna Saat Berhasil', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#047857',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied:focus-visible' => 'background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->add_control(
			'button_copied_text_color',
			[
				'label'     => __( 'Warna Teks Saat Berhasil', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied:focus-visible' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_box_shadow_copied',
				'label'    => __( 'Bayangan Saat Berhasil', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-kit-clipboard-tap.is-copied',
			]
		);

		$this->add_control(
			'error_heading',
			[
				'label'     => __( 'Status Gagal', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_error_color',
			[
				'label'     => __( 'Warna Saat Gagal', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#991b1b',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap.is-error, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-error:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-error:focus-visible' => 'background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->add_control(
			'button_error_text_color',
			[
				'label'     => __( 'Warna Teks Saat Gagal', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap.is-error, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-error:hover, {{WRAPPER}} .apeiron-kit-clipboard-tap.is-error:focus-visible' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_icon_style_controls(): void {
		$this->start_controls_section(
			'section_style_icon',
			[
				'label'     => __( 'Ikon', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'selected_icon[value]!' => '' ],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Ukuran Ikon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 10,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap__icon'     => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-kit-clipboard-tap__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_indent',
			[
				'label'      => __( 'Jarak Ikon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
				],
				'default'    => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-kit-clipboard-tap__content' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
