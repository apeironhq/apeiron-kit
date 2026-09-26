<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CommentDock\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Control IDs are persisted and must not be renamed. */
trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->register_layout_style_controls();
		$this->register_container_style_controls();
		$this->register_heading_style_controls();
		$this->register_form_style_controls();
		$this->register_fields_style_controls();
		$this->register_submit_style_controls();
		$this->register_comment_list_style_controls();
		$this->register_comment_item_style_controls();
		$this->register_avatar_style_controls();
		$this->register_author_style_controls();
		$this->register_message_style_controls();
		$this->register_metadata_style_controls();
		$this->register_attendance_style_controls();
		$this->register_badge_style_controls();
		$this->register_sticker_style_controls();
		$this->register_sticker_popup_style_controls();
		$this->register_notice_style_controls();
		$this->register_modal_style_controls();
		$this->register_inline_form_style_controls();
		$this->register_pagination_style_controls();
		$this->register_load_more_style_controls();
	}

	private function register_surface_controls( string $prefix, string $selector, array $args = [] ): void {
		$default_shadow = $args['shadow_default'] ?? [
			'box_shadow' => [
				'default' => [
					'horizontal' => 0,
					'vertical'   => 0,
					'blur'       => 0,
					'spread'     => 0,
					'color'      => 'rgba(0,0,0,0)',
				],
			],
		];

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => $prefix . '_background',
				'label'          => __( 'Background', 'apeiron-kit' ),
				'types'          => [ 'classic', 'gradient' ],
				'selector'       => $selector,
				'fields_options' => isset( $args['background_color'] )
					? [ 'color' => [ 'default' => $args['background_color'] ] ]
					: [],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => $prefix . '_border',
				'selector'       => $selector,
				'fields_options' => $args['border_defaults'] ?? [],
			]
		);

		if ( empty( $args['skip_radius'] ) ) {
			$this->add_responsive_control(
				$prefix . '_border_radius',
				[
					'label'      => __( 'Border Radius', 'apeiron-kit' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', '%' ],
					'default'    => $args['border_radius_default'] ?? null,
					'selectors'  => [
						$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
		}

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => $prefix . '_box_shadow',
				'selector'       => $selector,
				'fields_options' => $default_shadow,
			]
		);

		if ( empty( $args['skip_padding'] ) ) {
			$this->add_responsive_control(
				$prefix . '_padding',
				[
					'label'      => __( 'Padding', 'apeiron-kit' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'default'    => $args['padding_default'] ?? null,
					'selectors'  => [
						$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
		}

		if ( empty( $args['skip_margin'] ) ) {
			$this->add_responsive_control(
				$prefix . '_margin',
				[
					'label'      => __( 'Margin', 'apeiron-kit' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', '%' ],
					'selectors'  => [
						$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);
		}
	}

	private function register_typography_color( string $prefix, string $selector, string $label, array $args = [] ): void {
		$this->add_apeiron_typography( $prefix, $selector, $label, $args );

		$this->add_control(
			$prefix . '_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $selector => 'color: {{VALUE}};' ],
			]
		);
	}

	private function add_apeiron_typography( string $prefix, string $selector, string $label = '', array $args = [] ): void {
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'           => $prefix . '_typography',
				'label'          => $label,
				'selector'       => $selector,
				'fields_options' => [
					'font_family' => [ 'default' => 'Rubik' ],
					'font_size'   => [ 'default' => [ 'size' => $args['size'] ?? 14, 'unit' => 'px' ] ],
					'font_weight' => [ 'default' => (string) ( $args['weight'] ?? 400 ) ],
					'line_height' => [ 'default' => [ 'size' => $args['line_height'] ?? 1.45, 'unit' => 'em' ] ],
				],
			]
		);
	}

	private function register_alignment( string $prefix, string $selector, string $label = '', string $default = '' ): void {
		$this->add_responsive_control(
			$prefix . '_alignment',
			[
				'label'     => $label ?: __( 'Perataan', 'apeiron-kit' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default'   => $default ?: '',
				'selectors' => [ $selector => 'text-align: {{VALUE}};' ],
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

		$this->add_responsive_control(
			'wrapper_padding',
			[
				'label'      => __( 'Padding Container', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-comment-dock' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_margin',
			[
				'label'      => __( 'Margin Container', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-comment-dock' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_width',
			[
				'label'      => __( 'Lebar Container', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 1200 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-comment-dock' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'wrapper_radius_simple',
			[
				'label'      => __( 'Radius Container', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-comment-dock' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_gap',
			[
				'label'     => __( 'Jarak Form dan Komentar', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-form' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_columns',
			[
				'label'     => __( 'Kolom Komentar', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1',
				'options'   => [
					'1' => __( '1 Kolom', 'apeiron-kit' ),
					'2' => __( '2 Kolom', 'apeiron-kit' ),
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-list' => '--apeiron-comment-columns: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_gap',
			[
				'label'     => __( 'Jarak Antar Komentar', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-list' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_container_style_controls(): void {
		$this->start_controls_section(
			'section_style_wrapper',
			[
				'label' => __( 'Container', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_surface_controls(
			'wrapper',
			'{{WRAPPER}} .apeiron-comment-dock',
			[
				'background_color' => '',
				'skip_padding'     => true,
				'skip_margin'      => true,
				'skip_radius'      => true,
			]
		);

		$this->end_controls_section();
	}

	private function register_heading_style_controls(): void {
		$this->start_controls_section(
			'section_style_heading',
			[
				'label' => __( 'Judul', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_apeiron_typography( 'heading', '{{WRAPPER}} .apeiron-kit-comment-heading', __( 'Typography', 'apeiron-kit' ), [ 'size' => 18, 'weight' => 600, 'line_height' => 1.35 ] );

		$this->add_control(
			'heading_color',
			[
				'label'     => __( 'Warna', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-heading' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'heading_divider_color',
			[
				'label'     => __( 'Warna Garis Bawah', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-heading' => 'border-bottom-color: {{VALUE}};' ],
			]
		);

		$this->add_responsive_control(
			'heading_spacing',
			[
				'label'     => __( 'Spasi Bawah Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->register_alignment( 'heading', '{{WRAPPER}} .apeiron-kit-comment-heading' );

		$this->end_controls_section();
	}

	private function register_form_style_controls(): void {
		$this->start_controls_section(
			'section_style_form',
			[
				'label' => __( 'Form', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'form_internal_gap',
			[
				'label'     => __( 'Jarak Antar Field', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-form' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_apeiron_typography( 'label', '{{WRAPPER}} .apeiron-kit-form-label, {{WRAPPER}} .apeiron-kit-invited-guest-note', __( 'Typography Label', 'apeiron-kit' ), [ 'weight' => 500 ] );

		$this->add_control(
			'label_color',
			[
				'label'     => __( 'Warna Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-form-label, {{WRAPPER}} .apeiron-kit-invited-guest-note' => 'color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_fields_style_controls(): void {
		$this->start_controls_section(
			'section_style_fields',
			[
				'label' => __( 'Field Form', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		/* Primary form fields are also shared by inline and modal forms so
		   controls apply consistently across every comment entry surface. */
		$field_selector = '{{WRAPPER}} .apeiron-kit-comment-form input, {{WRAPPER}} .apeiron-kit-comment-form textarea, {{WRAPPER}} .apeiron-kit-comment-form select';
		$field_selector_all = $field_selector . ', {{WRAPPER}} .apeiron-kit-comment-inline-form input, {{WRAPPER}} .apeiron-kit-comment-inline-form textarea, {{WRAPPER}} .apeiron-kit-comment-edit-form textarea, {{WRAPPER}} .apeiron-kit-comment-modal input, {{WRAPPER}} .apeiron-kit-comment-modal textarea';

		$this->add_apeiron_typography( 'fields', $field_selector_all, __( 'Typography Field', 'apeiron-kit' ) );

		$this->add_control(
			'fields_background_color_simple',
			[
				'label'     => __( 'Latar Field', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $field_selector_all => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'fields_color',
			[
				'label'     => __( 'Warna Teks Field', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $field_selector_all => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'fields_placeholder_color',
			[
				'label'     => __( 'Warna Placeholder', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-form input::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-form textarea::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-inline-form input::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-inline-form textarea::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-modal textarea::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-modal input::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-edit-form textarea::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'fields_border',
				'selector'       => $field_selector_all,
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
			'fields_radius_simple',
			[
				'label'      => __( 'Radius Field', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$field_selector_all => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'fields_padding',
			[
				'label'      => __( 'Padding Field', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$field_selector_all => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'fields_focus_border_color',
			[
				'label'     => __( 'Border Saat Fokus', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(8, 60, 87, 0.22)',
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-form input:focus, {{WRAPPER}} .apeiron-kit-comment-form textarea:focus, {{WRAPPER}} .apeiron-kit-comment-form select:focus, {{WRAPPER}} .apeiron-kit-comment-form input:active, {{WRAPPER}} .apeiron-kit-comment-form textarea:active, {{WRAPPER}} .apeiron-kit-comment-form select:active, {{WRAPPER}} .apeiron-kit-comment-form input:focus-visible, {{WRAPPER}} .apeiron-kit-comment-form textarea:focus-visible, {{WRAPPER}} .apeiron-kit-comment-form select:focus-visible' => 'border-color: {{VALUE}}; outline: none; box-shadow: none;',
					'{{WRAPPER}} .apeiron-kit-comment-inline-form input:focus, {{WRAPPER}} .apeiron-kit-comment-inline-form textarea:focus, {{WRAPPER}} .apeiron-kit-comment-edit-form textarea:focus, {{WRAPPER}} .apeiron-kit-comment-inline-form input:active, {{WRAPPER}} .apeiron-kit-comment-inline-form textarea:active, {{WRAPPER}} .apeiron-kit-comment-edit-form textarea:active, {{WRAPPER}} .apeiron-kit-comment-inline-form input:focus-visible, {{WRAPPER}} .apeiron-kit-comment-inline-form textarea:focus-visible, {{WRAPPER}} .apeiron-kit-comment-edit-form textarea:focus-visible' => 'border-color: {{VALUE}}; outline: none; box-shadow: none;',
					'{{WRAPPER}} .apeiron-kit-comment-modal input:focus, {{WRAPPER}} .apeiron-kit-comment-modal textarea:focus, {{WRAPPER}} .apeiron-kit-comment-modal select:focus, {{WRAPPER}} .apeiron-kit-comment-modal input:focus-visible, {{WRAPPER}} .apeiron-kit-comment-modal textarea:focus-visible, {{WRAPPER}} .apeiron-kit-comment-modal select:focus-visible' => 'border-color: {{VALUE}}; outline: none; box-shadow: none;',
				],
			]
		);

		$this->add_control(
			'fields_focus_background_color',
			[
				'label'     => __( 'Latar Saat Fokus', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-form input:focus, {{WRAPPER}} .apeiron-kit-comment-form textarea:focus, {{WRAPPER}} .apeiron-kit-comment-form select:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-inline-form input:focus, {{WRAPPER}} .apeiron-kit-comment-inline-form textarea:focus, {{WRAPPER}} .apeiron-kit-comment-edit-form textarea:focus' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-kit-comment-modal input:focus, {{WRAPPER}} .apeiron-kit-comment-modal textarea:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_submit_style_controls(): void {
		$this->start_controls_section(
			'section_style_submit',
			[
				'label' => __( 'Tombol Kirim', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$submit_selectors = '{{WRAPPER}} .apeiron-kit-comment-form button[type="submit"], {{WRAPPER}} .apeiron-kit-comment-inline-actions button[type="submit"]';

		$this->add_apeiron_typography( 'button', $submit_selectors, __( 'Typography Tombol', 'apeiron-kit' ), [ 'weight' => 500 ] );

		$this->add_control(
			'button_background_color_simple',
			[
				'label'     => __( 'Latar Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $submit_selectors => 'background: {{VALUE}}; background-image: none;' ],
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Teks Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $submit_selectors => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => $submit_selectors,
			]
		);

		$this->add_responsive_control(
			'button_radius',
			[
				'label'      => __( 'Radius Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$submit_selectors => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$submit_selectors => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_width',
			[
				'label'      => __( 'Lebar Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 600 ] ],
				'selectors'  => [
					$submit_selectors => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_box_shadow',
				'selector' => $submit_selectors,
			]
		);

		$this->end_controls_section();
	}

	private function register_comment_list_style_controls(): void {
		$this->start_controls_section(
			'section_style_comment_list',
			[
				'label' => __( 'Daftar Komentar', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$selector = '{{WRAPPER}} .apeiron-kit-comment-list';

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'comment_list_background',
				'label'    => __( 'Background', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'comment_list_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'comment_list_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_list_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'comment_list_divider_color',
			[
				'label'     => __( 'Warna Pembatas Komentar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-list > .apeiron-kit-comment-item' => 'border-bottom-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_comment_item_style_controls(): void {
		$this->start_controls_section(
			'section_style_comment_item',
			[
				'label' => __( 'Kartu Komentar', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		/* Top-level cards only: replies use scoped selectors in CSS so they
		   stay compact and don't double-apply the card surface. */
		$selector = '{{WRAPPER}} .apeiron-kit-comment-list > .apeiron-kit-comment-item';

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'comment_item_background',
				'label'    => __( 'Background', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'comment_item_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'comment_item_border_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_item_padding',
			[
				'label'      => __( 'Padding Komentar', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'comment_item_hover_background',
			[
				'label'     => __( 'Latar Saat Hover', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector . ':hover' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'reply_indent',
			[
				'label'     => __( 'Indentasi Balasan', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-replies' => 'margin-left: {{SIZE}}{{UNIT}}; padding-left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'reply_line_color',
			[
				'label'     => __( 'Warna Garis Balasan', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-replies' => '--apeiron-reply-line-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_avatar_style_controls(): void {
		$this->start_controls_section(
			'section_style_avatar',
			[
				'label'     => __( 'Avatar', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_avatar' => 'yes' ],
			]
		);

		$selector = '{{WRAPPER}} .apeiron-kit-comment-avatar img';

		$this->add_responsive_control(
			'avatar_size',
			[
				'label'     => __( 'Ukuran Avatar', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 20, 'max' => 120 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-comment-dock' => '--apeiron-avatar-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avatar_radius',
			[
				'label'      => __( 'Radius Avatar', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'avatar_border',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	private function register_author_style_controls(): void {
		$this->start_controls_section(
			'section_style_author',
			[
				'label' => __( 'Nama Author', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_typography_color(
			'author',
			'{{WRAPPER}} .apeiron-kit-comment-author',
			__( 'Typography Nama', 'apeiron-kit' )
		);

		$this->end_controls_section();
	}

	private function register_message_style_controls(): void {
		$this->start_controls_section(
			'section_style_message',
			[
				'label' => __( 'Isi Ucapan', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_typography_color(
			'message',
			'{{WRAPPER}} .apeiron-kit-comment-text',
			__( 'Typography Pesan', 'apeiron-kit' )
		);

		$this->end_controls_section();
	}

	private function register_metadata_style_controls(): void {
		$this->start_controls_section(
			'section_style_metadata',
			[
				'label' => __( 'Metadata dan Aksi', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_typography_color(
			'time',
			'{{WRAPPER}} .apeiron-kit-comment-time',
			__( 'Typography Tanggal', 'apeiron-kit' ),
			[ 'size' => 12, 'weight' => 400, 'line_height' => 1.2 ]
		);

		$this->register_typography_color(
			'action',
			'{{WRAPPER}} .apeiron-kit-comment-action',
			__( 'Typography Tombol Aksi', 'apeiron-kit' ),
			[ 'size' => 12, 'weight' => 400, 'line_height' => 1.2 ]
		);

		$this->add_control(
			'action_hover_color',
			[
				'label'     => __( 'Warna Aksi Hover', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-action:hover, {{WRAPPER}} .apeiron-kit-comment-action:focus-visible' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'action_danger_color',
			[
				'label'     => __( 'Warna Hapus', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-action.is-danger:hover, {{WRAPPER}} .apeiron-kit-comment-action.is-danger:focus-visible' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_attendance_style_controls(): void {
		$this->start_controls_section(
			'section_style_attendance_summary',
			[
				'label'     => __( 'Ringkasan Kehadiran', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'attendence'              => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$summary_selector = '{{WRAPPER}} .apeiron-kit-attendance-summary';

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'attendance_summary_background',
				'label'    => __( 'Background', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $summary_selector,
			]
		);

		$this->add_responsive_control(
			'attendance_summary_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$summary_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->register_alignment( 'attendance_summary', $summary_selector );

		$this->add_control(
			'attendance_summary_title_heading',
			[
				'label'     => __( 'Judul Ringkasan', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_apeiron_typography( 'attendance_summary_title', '{{WRAPPER}} .apeiron-kit-attendance-summary-title', __( 'Typography Judul', 'apeiron-kit' ), [ 'size' => 12, 'weight' => 500, 'line_height' => 1.2 ] );

		$this->add_control(
			'attendance_summary_title_color',
			[
				'label'     => __( 'Warna Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-attendance-summary-title' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'attendance_summary_title_background',
			[
				'label'     => __( 'Latar Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-attendance-summary-title' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'attendance_summary_line_color',
			[
				'label'     => __( 'Warna Garis Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-attendance-summary-line' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'attendance_summary_item_heading',
			[
				'label'     => __( 'Kartu Item', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$item_selector = '{{WRAPPER}} .apeiron-kit-attendance-summary-item';

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'attendance_summary_item_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $item_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'attendance_summary_item_border',
				'selector' => $item_selector,
			]
		);

		$this->add_responsive_control(
			'attendance_summary_item_radius',
			[
				'label'      => __( 'Radius Kartu', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$item_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'attendance_summary_item_padding',
			[
				'label'      => __( 'Padding Kartu', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$item_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'attendance_summary_list_gap',
			[
				'label'     => __( 'Jarak Antar Kartu', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-attendance-summary-list' => 'gap: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'attendance_summary_count_heading',
			[
				'label'     => __( 'Angka dan Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_apeiron_typography( 'attendance_summary_count', '{{WRAPPER}} .apeiron-kit-attendance-summary-count', __( 'Typography Angka', 'apeiron-kit' ), [ 'size' => 22, 'weight' => 600, 'line_height' => 1 ] );

		$this->add_control(
			'attendance_summary_count_color',
			[
				'label'     => __( 'Warna Angka', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-attendance-summary-count' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_apeiron_typography( 'attendance_summary_label', '{{WRAPPER}} .apeiron-kit-attendance-summary-label', __( 'Typography Label', 'apeiron-kit' ), [ 'size' => 11, 'weight' => 500, 'line_height' => 1.35 ] );

		$this->add_control(
			'attendance_summary_label_color',
			[
				'label'     => __( 'Warna Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-attendance-summary-label' => 'color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_badge_style_controls(): void {
		$this->start_controls_section(
			'section_style_badge',
			[
				'label'     => __( 'Badge Kehadiran', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'attendence' => 'yes' ],
			]
		);

		$badge_selector = '{{WRAPPER}} .apeiron-kit-comment-pill';

		$this->add_apeiron_typography( 'badge', $badge_selector, __( 'Typography Badge', 'apeiron-kit' ), [ 'size' => 11, 'weight' => 400, 'line_height' => 1.2 ] );

		$this->add_control(
			'badge_background',
			[
				'label'     => __( 'Latar Badge', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $badge_selector => 'background: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'badge_color',
			[
				'label'     => __( 'Warna Badge', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $badge_selector => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'badge_border',
				'selector' => $badge_selector,
			]
		);

		$this->add_responsive_control(
			'badge_radius',
			[
				'label'      => __( 'Radius Badge', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$badge_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_padding',
			[
				'label'      => __( 'Padding Badge', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$badge_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'badge_status_heading',
			[
				'label'     => __( 'Warna per Status', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$statuses = [
			'0'       => __( 'Status 1', 'apeiron-kit' ),
			'1'       => __( 'Status 2', 'apeiron-kit' ),
			'2'       => __( 'Status 3', 'apeiron-kit' ),
			'3_plus'  => __( 'Status 4+', 'apeiron-kit' ),
		];

		foreach ( $statuses as $index => $label ) {
			$class = '3_plus' === $index ? 'status-index-3-plus' : 'status-index-' . $index;
			$this->add_control(
				'badge_status_' . $index . '_background',
				[
					'label'     => sprintf( __( 'Latar %s', 'apeiron-kit' ), $label ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-pill.' . $class => 'background: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'badge_status_' . $index . '_color',
				[
					'label'     => sprintf( __( 'Warna %s', 'apeiron-kit' ), $label ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-pill.' . $class => 'color: {{VALUE}};' ],
				]
			);
		}

		$this->end_controls_section();
	}

	private function register_sticker_style_controls(): void {
		$this->start_controls_section(
			'section_style_sticker',
			[
				'label'     => __( 'Stiker', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_stickers' => 'yes' ],
			]
		);

		$trigger_selector = '{{WRAPPER}} .apeiron-kit-sticker-trigger';

		$this->add_apeiron_typography( 'sticker_trigger', $trigger_selector, __( 'Typography Tombol', 'apeiron-kit' ), [ 'weight' => 500 ] );

		$this->add_control(
			'sticker_trigger_color',
			[
				'label'     => __( 'Warna Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $trigger_selector => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sticker_trigger_background_color_simple',
			[
				'label'     => __( 'Latar Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $trigger_selector => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'sticker_trigger_border',
				'selector' => $trigger_selector,
			]
		);

		$this->add_responsive_control(
			'sticker_trigger_radius',
			[
				'label'      => __( 'Radius Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$trigger_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'sticker_trigger_padding',
			[
				'label'      => __( 'Padding Tombol', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$trigger_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'sticker_options_heading',
			[
				'label'     => __( 'Opsi Stiker', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$option_selector = '{{WRAPPER}} .apeiron-kit-sticker-option';

		$this->add_responsive_control(
			'sticker_option_size',
			[
				'label'     => __( 'Ukuran Stiker', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 24, 'max' => 160 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-comment-dock, {{WRAPPER}} .apeiron-kit-reply-sticker-field' => '--apeiron-sticker-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'sticker_option_gap',
			[
				'label'     => __( 'Jarak Stiker', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-sticker-track, {{WRAPPER}} .apeiron-kit-sticker-grid' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'sticker_option_border',
				'selector' => $option_selector,
			]
		);

		$this->add_responsive_control(
			'sticker_option_radius',
			[
				'label'      => __( 'Radius Opsi', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$option_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'sticker_option_selected_background',
			[
				'label'     => __( 'Latar Stiker Terpilih', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $option_selector . '.is-selected' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sticker_option_selected_border_color',
			[
				'label'     => __( 'Border Stiker Terpilih', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $option_selector . '.is-selected' => 'border-color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_sticker_popup_style_controls(): void {
		$this->start_controls_section(
			'section_style_sticker_popup',
			[
				'label'     => __( 'Popup Stiker', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_stickers'          => 'yes',
					'sticker_display_mode'   => 'popup',
				],
			]
		);

		$panel_selector = '{{WRAPPER}} .apeiron-kit-sticker-popover-panel';

		$this->add_responsive_control(
			'sticker_popup_outer_padding',
			[
				'label'      => __( 'Jarak Luar Popup dari Layar', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [ '{{WRAPPER}} .apeiron-kit-sticker-popover' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'sticker_popup_panel_padding',
			[
				'label'      => __( 'Padding Dalam Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [ $panel_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'sticker_popup_panel_width',
			[
				'label'      => __( 'Lebar Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], '%' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $panel_selector => 'width: min({{SIZE}}{{UNIT}}, 100%);' ],
			]
		);

		$this->add_responsive_control(
			'sticker_popup_panel_max_width',
			[
				'label'      => __( 'Lebar Maksimum Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], '%' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $panel_selector => 'max-width: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'sticker_popup_panel_height',
			[
				'label'      => __( 'Tinggi Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], 'vh' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $panel_selector => 'height: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'sticker_popup_panel_max_height',
			[
				'label'      => __( 'Tinggi Maksimum Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], 'vh' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $panel_selector => 'max-height: min({{SIZE}}{{UNIT}}, 100%);' ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'sticker_popup_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $panel_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'sticker_popup_border',
				'selector' => $panel_selector,
			]
		);

		$this->add_responsive_control(
			'sticker_popup_radius',
			[
				'label'      => __( 'Radius Panel', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$panel_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'sticker_popup_box_shadow',
				'selector' => $panel_selector,
			]
		);

		$this->add_control(
			'sticker_popup_backdrop_color',
			[
				'label'     => __( 'Warna Backdrop', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-sticker-popover-backdrop' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sticker_popup_title_heading',
			[
				'label'     => __( 'Judul Popup', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->register_typography_color(
			'sticker_popup_title',
			'{{WRAPPER}} .apeiron-kit-sticker-popover-title',
			__( 'Typography Judul', 'apeiron-kit' )
		);

		$this->add_control(
			'sticker_popup_tab_heading',
			[
				'label'     => __( 'Tab', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$tab_selector = '{{WRAPPER}} .apeiron-kit-sticker-tab';
		$this->add_apeiron_typography( 'sticker_tab', $tab_selector, __( 'Typography Tab', 'apeiron-kit' ), [ 'weight' => 500, 'line_height' => 1.2 ] );
		$this->add_control(
			'sticker_tab_color',
			[
				'label'     => __( 'Warna Tab', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $tab_selector => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sticker_tab_background',
			[
				'label'     => __( 'Latar Tab', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $tab_selector => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sticker_tab_active_color',
			[
				'label'     => __( 'Warna Tab Aktif', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $tab_selector . '.is-active' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sticker_tab_active_background',
			[
				'label'     => __( 'Latar Tab Aktif', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $tab_selector . '.is-active' => 'background: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_notice_style_controls(): void {
		$this->start_controls_section(
			'section_style_notice',
			[
				'label' => __( 'Pesan Notifikasi', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$notice_selector = '{{WRAPPER}} .apeiron-kit-comment-notice';

		$this->add_apeiron_typography( 'notice', $notice_selector, __( 'Typography Pesan', 'apeiron-kit' ), [ 'size' => 13, 'weight' => 500 ] );

		$this->add_control(
			'notice_color',
			[
				'label'     => __( 'Warna Pesan', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $notice_selector => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'notice_error_color',
			[
				'label'     => __( 'Warna Pesan Error', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $notice_selector . '.is-error' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'notice_background',
			[
				'label'     => __( 'Latar Toast', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $notice_selector . '.is-toast' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_responsive_control(
			'notice_padding',
			[
				'label'      => __( 'Padding Toast', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$notice_selector . '.is-toast' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'notice_radius',
			[
				'label'      => __( 'Radius Toast', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$notice_selector . '.is-toast' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'notice_box_shadow',
				'selector' => $notice_selector . '.is-toast',
			]
		);

		$this->end_controls_section();
	}

	private function register_modal_style_controls(): void {
		$this->start_controls_section(
			'section_style_modal',
			[
				'label' => __( 'Popup Balasan dan Edit', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$overlay_selector = '{{WRAPPER}} .apeiron-kit-comment-modal';
		$dialog_selector = '{{WRAPPER}} .apeiron-kit-comment-dialog';

		$this->add_responsive_control(
			'modal_outer_padding',
			[
				'label'      => __( 'Jarak Luar Popup dari Layar', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [ $overlay_selector . ':not(.apeiron-kit-comment-delete-modal)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'modal_dialog_width',
			[
				'label'      => __( 'Lebar Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], '%' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $dialog_selector . ':not(.apeiron-kit-comment-delete-dialog)' => 'width: min({{SIZE}}{{UNIT}}, 100%);' ],
			]
		);

		$this->add_responsive_control(
			'modal_dialog_max_width',
			[
				'label'      => __( 'Lebar Maksimum Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], '%' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $dialog_selector . ':not(.apeiron-kit-comment-delete-dialog)' => 'max-width: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'modal_dialog_height',
			[
				'label'      => __( 'Tinggi Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], 'vh' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $dialog_selector . ':not(.apeiron-kit-comment-delete-dialog)' => 'height: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'modal_dialog_max_height',
			[
				'label'      => __( 'Tinggi Maksimum Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [ 'px' => [ 'min' => 1, 'max' => 1200 ], 'vh' => [ 'min' => 1, 'max' => 100 ] ],
				'selectors'  => [ $dialog_selector . ':not(.apeiron-kit-comment-delete-dialog)' => '--apeiron-modal-dialog-max-height: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'modal_dialog_gap',
			[
				'label'      => __( 'Jarak Antar Isi Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
				'selectors'  => [ $dialog_selector . ':not(.apeiron-kit-comment-delete-dialog)' => 'gap: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'modal_overlay_color',
			[
				'label'     => __( 'Warna Overlay', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $overlay_selector => 'background: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'modal_dialog_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $dialog_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'modal_dialog_border',
				'selector' => $dialog_selector,
			]
		);

		$this->add_responsive_control(
			'modal_dialog_radius',
			[
				'label'      => __( 'Radius Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$dialog_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'modal_dialog_padding',
			[
				'label'      => __( 'Padding Dialog', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$dialog_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'modal_dialog_box_shadow',
				'selector' => $dialog_selector,
			]
		);

		$this->add_control(
			'modal_title_heading',
			[
				'label'     => __( 'Judul Modal', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->register_typography_color(
			'modal_title',
			'{{WRAPPER}} .apeiron-kit-comment-modal-title',
			__( 'Typography Judul', 'apeiron-kit' )
		);

		$this->register_typography_color(
			'modal_description',
			'{{WRAPPER}} .apeiron-kit-comment-modal-description',
			__( 'Typography Deskripsi', 'apeiron-kit' )
		);

		$this->register_typography_color(
			'modal_button',
			'{{WRAPPER}} .apeiron-kit-comment-modal .apeiron-kit-comment-inline-actions button',
			__( 'Typography Tombol', 'apeiron-kit' ),
			[ 'weight' => 500 ]
		);

		$this->add_control(
			'modal_button_background',
			[
				'label'     => __( 'Latar Tombol Batal', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-modal .apeiron-kit-comment-inline-actions button:not([type="submit"]):not([data-delete-confirm])' => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'modal_delete_button_color',
			[
				'label'     => __( 'Warna Tombol Hapus', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-modal [data-delete-confirm]' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'modal_delete_button_background',
			[
				'label'     => __( 'Latar Tombol Hapus', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-modal [data-delete-confirm]' => 'background-color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_inline_form_style_controls(): void {
		$this->start_controls_section(
			'section_style_inline_form',
			[
				'label' => __( 'Form Balasan Inline', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$selector = '{{WRAPPER}} .apeiron-kit-comment-inline-form, {{WRAPPER}} .apeiron-kit-comment-edit-form';
		$this->register_typography_color(
			'inline_form_button',
			'{{WRAPPER}} .apeiron-kit-comment-inline-actions button',
			__( 'Typography Tombol', 'apeiron-kit' ),
			[ 'weight' => 500 ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'inline_form_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'inline_form_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'inline_form_radius',
			[
				'label'      => __( 'Border Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'inline_form_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_pagination_style_controls(): void {
		$this->start_controls_section(
			'section_style_pagination',
			[
				'label'     => __( 'Navigasi Komentar', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'comment_list_mode' => 'pagination' ],
			]
		);

		$button_selector = '{{WRAPPER}} .apeiron-kit-comment-pagination button';

		$this->add_apeiron_typography( 'pagination', $button_selector, __( 'Typography Tombol', 'apeiron-kit' ), [ 'weight' => 500 ] );
		$this->add_apeiron_typography( 'pagination_info', '{{WRAPPER}} .apeiron-kit-comment-page-info', __( 'Typography Info Halaman', 'apeiron-kit' ), [ 'weight' => 500 ] );

		$this->add_control(
			'pagination_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $button_selector => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'pagination_background_color',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $button_selector => 'background: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'pagination_border',
				'selector' => $button_selector,
			]
		);

		$this->add_responsive_control(
			'pagination_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$button_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$button_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_alignment',
			[
				'label'   => __( 'Perataan', 'apeiron-kit' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center'     => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'flex-end'   => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'default' => 'center',
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-pagination' => 'justify-content: {{VALUE}};' ],
			]
		);

		$this->add_responsive_control(
			'pagination_gap',
			[
				'label'     => __( 'Jarak', 'apeiron-kit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-pagination' => 'gap: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'pagination_info_color',
			[
				'label'     => __( 'Warna Info Halaman', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .apeiron-kit-comment-page-info' => 'color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_load_more_style_controls(): void {
		$this->start_controls_section(
			'section_style_load_more',
			[
				'label'     => __( 'Tombol Muat Lebih Banyak', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'comment_list_mode!' => 'pagination' ],
			]
		);

		$button_selector = '{{WRAPPER}} .apeiron-kit-comment-load-more';

		$this->add_apeiron_typography( 'load_more', $button_selector, __( 'Typography Tombol', 'apeiron-kit' ), [ 'weight' => 500 ] );

		$this->add_control(
			'load_more_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $button_selector => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'load_more_background_color',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ $button_selector => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'load_more_border',
				'selector' => $button_selector,
			]
		);

		$this->add_responsive_control(
			'load_more_radius',
			[
				'label'      => __( 'Radius', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$button_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'load_more_padding',
			[
				'label'      => __( 'Padding', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					$button_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
