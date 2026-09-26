<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SignalForm\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->start_controls_section(
			'section_style_container',
			[
				'label' => __( 'Wrapper', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'container_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f7f7fb',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form' => 'background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .apeiron-signal-form',
			]
		);

		$this->add_responsive_control(
			'container_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => 8,
					'right'  => 8,
					'bottom' => 8,
					'left'   => 8,
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 28,
					'right'    => 28,
					'bottom'   => 28,
					'left'     => 28,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'tablet_default' => [
					'top'      => 22,
					'right'    => 22,
					'bottom'   => 22,
					'left'     => 22,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'mobile_default' => [
					'top'      => 16,
					'right'    => 16,
					'bottom'   => 16,
					'left'     => 16,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'container_shadow',
				'selector'       => '{{WRAPPER}} .apeiron-signal-form',
				'fields_options' => [
					'box_shadow_type' => [
						'default' => 'no',
					],
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

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_labels',
			[
				'label' => __( 'Label', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .apeiron-signal-form label span',
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form label span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_fields',
			[
				'label' => __( 'Kolom Input', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_fields_style' );

		$this->start_controls_tab(
			'tab_fields_normal',
			[
				'label' => __( 'Normal', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'input_text_color',
			[
				'label'     => __( 'Text Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form input, {{WRAPPER}} .apeiron-signal-form textarea, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select)' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'fields_typography',
				'selector' => '{{WRAPPER}} .apeiron-signal-form input, {{WRAPPER}} .apeiron-signal-form textarea, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select)',
			]
		);

		$this->add_control(
			'input_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form input, {{WRAPPER}} .apeiron-signal-form textarea, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'fields_border',
				'selector' => '{{WRAPPER}} .apeiron-signal-form input, {{WRAPPER}} .apeiron-signal-form textarea, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select)',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => '#e5e7ef',
					],
				],
			]
		);

		$this->add_responsive_control(
			'fields_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '8',
					'right'    => '8',
					'bottom'   => '8',
					'left'     => '8',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form input, {{WRAPPER}} .apeiron-signal-form textarea, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_text_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 12,
					'right'    => 14,
					'bottom'   => 12,
					'left'     => 14,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form input, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_fields_focus',
			[
				'label' => __( 'Focus', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'input_focus_text_color',
			[
				'label'     => __( 'Text Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form input:focus, {{WRAPPER}} .apeiron-signal-form textarea:focus, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select):focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_focus_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form input:focus, {{WRAPPER}} .apeiron-signal-form textarea:focus, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select):focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_focus_border_type',
			[
				'label'     => __( 'Jenis Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'none'   => __( 'None', 'apeiron-kit' ),
					'solid'  => __( 'Solid', 'apeiron-kit' ),
					'double' => __( 'Double', 'apeiron-kit' ),
					'dotted' => __( 'Dotted', 'apeiron-kit' ),
					'dashed' => __( 'Dashed', 'apeiron-kit' ),
					'groove' => __( 'Groove', 'apeiron-kit' ),
				],
				'default'   => 'solid',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form input:focus, {{WRAPPER}} .apeiron-signal-form textarea:focus, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select):focus' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_focus_border_width',
			[
				'label'      => __( 'Lebar Border', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'    => '1',
					'right'  => '1',
					'bottom' => '1',
					'left'   => '1',
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form input:focus, {{WRAPPER}} .apeiron-signal-form textarea:focus, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select):focus' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'input_focus_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'input_focus_border_color',
			[
				'label'     => __( 'Border Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8, 60, 87, 0.22)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form input:focus, {{WRAPPER}} .apeiron-signal-form textarea:focus, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select):focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'input_focus_border_type!' => 'none',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'input_focus_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-signal-form input:focus, {{WRAPPER}} .apeiron-signal-form textarea:focus, {{WRAPPER}} .apeiron-signal-form select:not(.apeiron-signal-form__select):focus',
				'fields_options' => [
					'box_shadow_type' => [
						'default' => 'no',
					],
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

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'fields_spacing_heading',
			[
				'label'     => __( 'Jarak Antar Field', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'fields_margin',
			[
				'label'      => __( 'Margin', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__field' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_textarea',
			[
				'label' => __( 'Ucapan (Textarea)', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'textarea_typography',
				'selector' => '{{WRAPPER}} .apeiron-signal-form textarea',
			]
		);

		$this->add_control(
			'textarea_text_color',
			[
				'label'     => __( 'Text Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form textarea' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'textarea_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form textarea' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'textarea_border',
				'selector' => '{{WRAPPER}} .apeiron-signal-form textarea',
			]
		);

		$this->add_responsive_control(
			'textarea_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => 8,
					'right'    => 8,
					'bottom'   => 8,
					'left'     => 8,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'textarea_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 12,
					'right'    => 14,
					'bottom'   => 12,
					'left'     => 14,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'textarea_focus_heading',
			[
				'label'     => __( 'Focus State', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'textarea_focus_text_color',
			[
				'label'     => __( 'Text Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form textarea:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'textarea_focus_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form textarea:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'textarea_focus_border_color',
			[
				'label'     => __( 'Border Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form textarea:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'textarea_focus_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-signal-form textarea:focus',
				'fields_options' => [
					'box_shadow_type' => [
						'default' => 'no',
					],
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

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			[
				'label' => __( 'Tombol', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
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
				'label'     => __( 'Text Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__button',
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__button' => 'background-color: {{VALUE}}; background-image: none;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__button',
			]
		);

		$this->add_responsive_control(
			'button_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => 8,
					'right'    => 8,
					'bottom'   => 8,
					'left'     => 8,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 12,
					'right'    => 18,
					'bottom'   => 12,
					'left'     => 18,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_shadow',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__button',
				'fields_options' => [
					'box_shadow_type' => [
						'default' => 'no',
					],
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

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => __( 'Hover', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'button_hover_color',
			[
				'label'     => __( 'Text Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background_hover_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__button:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => __( 'Border Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__button:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_opacity',
			[
				'label'      => __( 'Opacity Hover', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
				],
				'default'    => [
					'size' => 85,
					'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__button:hover' => 'opacity: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_confirmation',
			[
				'label'     => __( 'Konfirmasi Kehadiran', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_confirmation' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'confirmation_typography',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__select',
			]
		);

		$this->add_control(
			'confirmation_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__select' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'confirmation_background_color',
			[
				'label'     => __( 'Background Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__select' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'confirmation_border',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__select',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => '#e5e7ef',
					],
				],
			]
		);

		$this->add_responsive_control(
			'confirmation_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => '8',
					'right'    => '8',
					'bottom'   => '8',
					'left'     => '8',
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'confirmation_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 12,
					'right'    => 14,
					'bottom'   => 12,
					'left'     => 14,
					'unit'     => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'confirmation_focus_heading',
			[
				'label'     => __( 'Focus State', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'confirmation_focus_border_type',
			[
				'label'     => __( 'Jenis Border', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'none'   => __( 'None', 'apeiron-kit' ),
					'solid'  => __( 'Solid', 'apeiron-kit' ),
					'double' => __( 'Double', 'apeiron-kit' ),
					'dotted' => __( 'Dotted', 'apeiron-kit' ),
					'dashed' => __( 'Dashed', 'apeiron-kit' ),
					'groove' => __( 'Groove', 'apeiron-kit' ),
				],
				'default'   => 'solid',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__select:focus' => 'border-style: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'confirmation_focus_border_width',
			[
				'label'      => __( 'Lebar Border', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top'    => '1',
					'right'  => '1',
					'bottom' => '1',
					'left'   => '1',
					'unit'   => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__select:focus' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'confirmation_focus_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'confirmation_focus_border_color',
			[
				'label'     => __( 'Border Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8, 60, 87, 0.22)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__select:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'confirmation_focus_border_type!' => 'none',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'confirmation_focus_box_shadow',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__select:focus',
				'fields_options' => [
					'box_shadow_type' => [
						'default' => 'no',
					],
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

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_states',
			[
				'label' => __( 'State dan Interaksi', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'notice_typography',
				'label'    => __( 'Tipografi Status', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty',
			]
		);

		$this->add_control(
			'notice_color',
			[
				'label'     => __( 'Warna Status Default', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'notice_background_color',
			[
				'label'     => __( 'Background Status', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'notice_border',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty',
				'fields_options' => [
					'border' => [ 'default' => 'none' ],
				],
			]
		);

		$this->add_responsive_control(
			'notice_padding',
			[
				'label'      => __( 'Padding Status', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 0,
					'right'    => 0,
					'bottom'   => 0,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'notice_radius',
			[
				'label'      => __( 'Border Radius Status', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'      => 0,
					'right'    => 0,
					'bottom'   => 0,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'notice_shadow',
				'selector' => '{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty',
				'fields_options' => [
					'box_shadow_type' => [ 'default' => 'no' ],
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

		$this->add_control(
			'notice_alignment',
			[
				'label'     => __( 'Alignment Status', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => 'left',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form__notice, {{WRAPPER}} .apeiron-signal-form__empty' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_gap',
			[
				'label'      => __( 'Jarak Antar Elemen', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 80 ],
					'em' => [ 'min' => 0, 'max' => 8 ],
				],
				'default'    => [
					'size' => 18,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form' => '--apeiron-signal-form-field-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'focus_ring_color',
			[
				'label'     => __( 'Warna Focus Ring', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form' => '--apeiron-signal-form-focus-ring-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'loading_opacity',
			[
				'label'      => __( 'Opacity Loading', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [ 'min' => 20, 'max' => 100, 'step' => 1 ],
				],
				'default'    => [
					'size' => 65,
					'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form' => '--apeiron-signal-form-loading-opacity: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'transition_duration',
			[
				'label'      => __( 'Durasi Transisi', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'ms' ],
				'range'      => [
					'ms' => [ 'min' => 0, 'max' => 1000, 'step' => 10 ],
				],
				'default'    => [
					'size' => 200,
					'unit' => 'ms',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-signal-form' => '--apeiron-signal-form-transition-duration: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'success_color',
			[
				'label'     => __( 'Warna Status Berhasil', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form' => '--apeiron-signal-form-success-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'error_color',
			[
				'label'     => __( 'Warna Status Error', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#991b1b',
				'selectors' => [
					'{{WRAPPER}} .apeiron-signal-form' => '--apeiron-signal-form-error-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
