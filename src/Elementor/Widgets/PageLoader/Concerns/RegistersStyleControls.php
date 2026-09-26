<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\PageLoader\Concerns;

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
		$this->register_overlay_style_controls();
		$this->register_surface_style_controls();
		$this->register_loader_visual_style_controls();
		$this->register_text_style_controls();
	}

	private function register_overlay_style_controls(): void {
		$this->start_controls_section(
			'section_style_overlay',
			[
				'label' => __( 'Background', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'overlay_background',
				'label'    => __( 'Background Color / Gradient', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-page-loader__overlay::before',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color' => [
						'default' => '#f7f7fb',
					],
				],
			]
		);

		$this->add_control(
			'overlay_opacity',
			[
				'label'     => __( 'Overlay Opacity', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0.1,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'default'   => [
					'size' => 0.98,
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-overlay-opacity: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'backdrop_blur',
			[
				'label'      => __( 'Background Blur', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 32,
					],
				],
				'default'    => [
					'size' => 10,
					'unit' => 'px',
				],
				'condition'  => [
					'blur_background' => 'yes',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-backdrop-blur: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'overlay_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'      => 24,
					'right'    => 24,
					'bottom'   => 24,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_alignment',
			[
				'label'   => __( 'Horizontal Alignment', 'apeiron-kit' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'center',
				'options' => [
					'flex-start' => [
						'title' => __( 'Kiri', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center' => [
						'title' => __( 'Tengah', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-center',
					],
					'flex-end' => [
						'title' => __( 'Kanan', 'apeiron-kit' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__overlay' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'vertical_alignment',
			[
				'label'   => __( 'Vertical Alignment', 'apeiron-kit' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'center',
				'options' => [
					'flex-start' => [
						'title' => __( 'Atas', 'apeiron-kit' ),
						'icon'  => 'eicon-v-align-top',
					],
					'center' => [
						'title' => __( 'Tengah', 'apeiron-kit' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'flex-end' => [
						'title' => __( 'Bawah', 'apeiron-kit' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__overlay' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'z_index',
			[
				'label'     => __( 'Z-Index', 'apeiron-kit' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2147483020,
				'min'       => 1,
				'max'       => 2147483647,
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader' => 'z-index: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_surface_style_controls(): void {
		$this->start_controls_section(
			'section_style_surface',
			[
				'label' => __( 'Container', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'surface_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-page-loader__surface',
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color' => [
						'default' => 'rgba(255,255,255,0)',
					],
				],
			]
		);

		$this->add_responsive_control(
			'surface_width',
			[
				'label'      => __( 'Max Width', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [
						'min' => 180,
						'max' => 900,
					],
					'%' => [
						'min' => 20,
						'max' => 100,
					],
					'vw' => [
						'min' => 20,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 420,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 340,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__surface' => 'width: 100%; max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'surface_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
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
					'{{WRAPPER}} .apeiron-page-loader__surface' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'surface_spacing',
			[
				'label'      => __( 'Spacing', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
					'em' => [
						'min' => 0,
						'max' => 6,
					],
				],
				'default'    => [
					'size' => 18,
					'unit' => 'px',
				],
				'condition'  => [
					'loader_style!' => 'default',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__loading-phase' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'surface_border',
				'selector' => '{{WRAPPER}} .apeiron-page-loader__surface',
			]
		);

		$this->add_responsive_control(
			'surface_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
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
					'{{WRAPPER}} .apeiron-page-loader__surface' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'surface_shadow',
				'selector' => '{{WRAPPER}} .apeiron-page-loader__surface',
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
							'color'      => 'rgba(0, 0, 0, 0)',
						],
					],
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_loader_visual_style_controls(): void {
		$this->start_controls_section(
			'section_style_loader',
			[
				'label' => __( 'Loader', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$non_default_style = [ 'loader_style!' => 'default' ];

		$this->add_control(
			'primary_color',
			[
				'label'     => __( 'Loader Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083C57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-primary: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'secondary_color',
			[
				'label'     => __( 'Accent Color', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083C57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-secondary: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'track_color',
			[
				'label'     => __( 'Progress Track', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'condition' => $non_default_style,
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-track: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'loader_size',
			[
				'label'      => __( 'Loader / Artwork Size', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 28,
						'max' => 220,
					],
					'em' => [
						'min' => 2,
						'max' => 14,
					],
					'rem' => [
						'min' => 2,
						'max' => 14,
					],
				],
				'default'    => [
					'size' => 76,
					'unit' => 'px',
				],
				'condition'  => $non_default_style,
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stroke_width',
			[
				'label'      => __( 'Ketebalan Stroke', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 2,
						'max' => 18,
					],
				],
				'default'    => [
					'size' => 4,
					'unit' => 'px',
				],
				'condition'  => $non_default_style,
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-stroke: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'animation_speed',
			[
				'label'     => __( 'Kecepatan Animasi', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0.35,
						'max'  => 3,
						'step' => 0.05,
					],
				],
				'default'   => [
					'size' => 1.1,
				],
				'condition' => $non_default_style,
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader' => '--ap-loader-speed: {{SIZE}}s;',
				],
			]
		);

		$this->add_responsive_control(
			'loader_spacing',
			[
				'label'      => __( 'Jarak Loader ke Teks', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'condition'  => $non_default_style,
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__visual-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$percentage_condition = [
			'loader_style!'   => 'default',
			'show_percentage' => 'yes',
		];

		$this->add_control(
			'percentage_heading',
			[
				'label'     => __( 'Percentage', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => $percentage_condition,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'percentage_typography',
				'selector'  => '{{WRAPPER}} .apeiron-page-loader__percentage',
				'condition' => $percentage_condition,
			]
		);

		$this->add_control(
			'percentage_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083C57',
				'condition' => $percentage_condition,
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__percentage' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'percentage_spacing',
			[
				'label'      => __( 'Spacing', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 40 ],
				],
				'default'    => [ 'size' => 14, 'unit' => 'px' ],
				'condition'  => $percentage_condition,
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__percentage' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'loader_shadow',
				'label'     => __( 'Shadow', 'apeiron-kit' ),
				'selector'  => '{{WRAPPER}} .apeiron-loader-visual',
				'condition' => $non_default_style,
			]
		);

		$this->end_controls_section();
	}

	private function register_text_style_controls(): void {
		$this->start_controls_section(
			'section_style_text',
			[
				'label' => __( 'Text', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_alignment',
			[
				'label'   => __( 'Alignment', 'apeiron-kit' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'center',
				'options' => [
					'left' => [
						'title' => __( 'Kiri', 'apeiron-kit' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Tengah', 'apeiron-kit' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __( 'Kanan', 'apeiron-kit' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__surface' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'intro_text_heading',
			[
				'label' => __( 'Intro Text', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'intro_text_typography',
				'selector' => '{{WRAPPER}} .apeiron-page-loader__intro',
				'exclude'  => [ 'text_transform', 'font_style', 'text_decoration', 'word_spacing' ],
				'fields_options' => [
					'font_size' => [
						'default' => [
							'size' => 14,
							'unit' => 'px',
						],
					],
					'font_weight' => [
						'default' => '300',
					],
					'letter_spacing' => [
						'selectors' => [
							'{{SELECTOR}}' => '--ap-loader-intro-letter-spacing: {{SIZE}}{{UNIT}}; letter-spacing: var(--ap-loader-intro-letter-spacing);',
						],
					],
				],
			]
		);

		$this->add_control(
			'intro_text_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7890',
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__intro' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'intro_text_spacing',
			[
				'label'      => __( 'Spacing', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'default'    => [
					'size' => 0,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__intro' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'main_text_heading',
			[
				'label'     => __( 'Main / Initial Text', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'main_text_typography',
				'selector' => '{{WRAPPER}} .apeiron-page-loader__main',
				'exclude'  => [ 'text_transform', 'font_style', 'text_decoration', 'word_spacing' ],
				'fields_options' => [
					'font_size' => [
						'default' => [
							'size' => 130,
							'unit' => 'px',
						],
					],
					'font_weight' => [
						'default' => '300',
					],
				],
			]
		);

		$this->add_control(
			'main_text_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#253044',
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__main' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'main_text_spacing',
			[
				'label'      => __( 'Spacing', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 80 ],
				],
				'default'    => [ 'size' => 0, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__mask' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'secondary_text_heading',
			[
				'label'     => __( 'Secondary / Name Text', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'secondary_text_typography',
				'selector' => '{{WRAPPER}} .apeiron-page-loader__secondary',
				'exclude'  => [ 'text_transform', 'font_style', 'text_decoration', 'word_spacing' ],
				'fields_options' => [
					'font_size' => [ 'default' => [ 'size' => 13, 'unit' => 'px' ] ],
					'font_weight' => [ 'default' => '400' ],
					'letter_spacing' => [
						'selectors' => [
							'{{SELECTOR}}' => '--ap-loader-secondary-letter-spacing: {{SIZE}}{{UNIT}}; letter-spacing: var(--ap-loader-secondary-letter-spacing);',
						],
					],
				],
			]
		);

		$this->add_control(
			'secondary_text_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7890',
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__secondary' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'secondary_text_spacing',
			[
				'label'      => __( 'Spacing', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 0, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__secondary' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'loading_text_heading',
			[
				'label'     => __( 'Loading Text', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'loading_text_typography',
				'selector' => '{{WRAPPER}} .apeiron-page-loader__text',
				'exclude'  => [ 'text_transform', 'font_style', 'text_decoration', 'word_spacing' ],
				'fields_options' => [
					'font_size' => [ 'default' => [ 'size' => 13, 'unit' => 'px' ] ],
					'font_weight' => [ 'default' => '300' ],
				],
			]
		);

		$this->add_control(
			'loading_text_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#253044',
				'selectors' => [
					'{{WRAPPER}} .apeiron-page-loader__text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'loading_text_spacing',
			[
				'label'      => __( 'Spacing', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'default'    => [ 'size' => 14, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-page-loader__text' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

}
