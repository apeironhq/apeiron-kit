<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\GuestInvitationManager\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {

	private function register_style_controls(): void {
		$this->start_controls_section(
			'section_layout_spacing',
			[
				'label' => __( 'Jarak Cepat', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'heading_template_spacing',
			[
				'label' => __( 'Blok Template', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'template_section_margin_top',
			[
				'label'      => __( 'Jarak dari Bagian Sebelumnya', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 2,
					'unit' => 'px',
				],
				'mobile_default' => [
					'size' => 2,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-template-section' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_section_spacing',
			[
				'label'     => __( 'Tabel Hasil', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'spacing_between_template_guestlist',
			[
				'label'      => __( 'Jarak Form ke Tabel', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'default'    => [
					'size' => 22,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-invitation-form' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_container_style',
			[
				'label' => __( 'Kontainer', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'heading_main_container',
			[
				'label' => __( 'Utama', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'label'    => __( 'Latar', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-invitation-container',
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

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Jarak Dalam', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => '28',
					'right'  => '28',
					'bottom' => '28',
					'left'   => '28',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'mobile_default' => [
					'top'    => '16',
					'right'  => '16',
					'bottom' => '16',
					'left'   => '16',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-invitation-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_margin',
			[
				'label'      => __( 'Jarak Luar', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => '0',
					'right'  => '0',
					'bottom' => '0',
					'left'   => '0',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-invitation-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .apeiron-invitation-container',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'    => '1',
							'right'  => '1',
							'bottom' => '1',
							'left'   => '1',
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
			'container_border_radius',
			[
				'label'      => __( 'Sudut', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'mobile_default' => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-invitation-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style',
			[
				'label'     => __( 'Konten Atas', 'apeiron-kit' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'header_icon_size',
			[
				'label'      => __( 'Ukuran Ikon', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 12,
						'max' => 80,
					],
				],
				'default'    => [
					'size' => 18,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-header-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .apeiron-header-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_control(
			'header_icon_color',
			[
				'label'     => __( 'Warna Ikon', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-header-icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-header-icon svg' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_title_color',
			[
				'label'     => __( 'Warna Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .apeiron-header-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_title_typography',
				'label'    => __( 'Tipografi Judul', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-header-title',
			]
		);

		$this->add_control(
			'header_description_color',
			[
				'label'     => __( 'Warna Deskripsi', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .apeiron-header-description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_description_typography',
				'label'    => __( 'Tipografi Deskripsi', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-header-description',
			]
		);

		$this->add_responsive_control(
			'header_margin_top',
			[
				'label'      => __( 'Jarak Atas', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'separator'  => 'before',
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-header-section' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_spacing_bottom',
			[
				'label'      => __( 'Jarak Bawah', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 24,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-header-section' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_form_field_style',
			[
				'label' => __( 'Input', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'form_field_typography',
				'selector' => '{{WRAPPER}} .apeiron-invitation-container input, {{WRAPPER}} .apeiron-invitation-container textarea',
			]
		);

		$this->start_controls_tabs( 'tabs_form_field_style' );

		$this->start_controls_tab(
			'tab_form_field_normal',
			[
				'label' => __( 'Normal', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'form_field_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-invitation-container input, {{WRAPPER}} .apeiron-invitation-container textarea' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'form_field_background',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-invitation-container input, {{WRAPPER}} .apeiron-invitation-container textarea' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'form_field_border',
				'selector' => '{{WRAPPER}} .apeiron-invitation-container input, {{WRAPPER}} .apeiron-invitation-container textarea',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'    => '1',
							'right'  => '1',
							'bottom' => '1',
							'left'   => '1',
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => '#e5e7ef',
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_form_field_focus',
			[
				'label' => __( 'Fokus', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'form_field_focus_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-invitation-container input:focus, {{WRAPPER}} .apeiron-invitation-container textarea:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'form_field_focus_background',
			[
				'label'     => __( 'Warna Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-invitation-container input:focus, {{WRAPPER}} .apeiron-invitation-container textarea:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'form_field_focus_border',
				'label'    => __( 'Garis Fokus', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-invitation-container input:focus, {{WRAPPER}} .apeiron-invitation-container textarea:focus',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'    => '1',
							'right'  => '1',
							'bottom' => '1',
							'left'   => '1',
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => 'rgba(8, 60, 87, 0.22)',
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'form_field_border_radius',
			[
				'label'      => __( 'Sudut', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'separator'  => 'before',
				'default'    => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-invitation-container input, {{WRAPPER}} .apeiron-invitation-container textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_field_padding',
			[
				'label'      => __( 'Jarak Dalam', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => '12',
					'right'  => '14',
					'bottom' => '12',
					'left'   => '14',
					'unit'   => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-invitation-container input, {{WRAPPER}} .apeiron-invitation-container textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_label_style',
			[
				'label' => __( 'Label dan Bantuan', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typography',
				'label'    => __( 'Tipografi Label', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-form-label',
			]
		);

		$this->add_control(
			'label_text_color',
			[
				'label'     => __( 'Warna Label', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-form-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'hint_typography',
				'label'     => __( 'Tipografi Bantuan', 'apeiron-kit' ),
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .apeiron-form-hint',
			]
		);

		$this->add_control(
			'hint_text_color',
			[
				'label'     => __( 'Warna Bantuan', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-form-hint' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'section_title_typography',
				'label'     => __( 'Tipografi Judul', 'apeiron-kit' ),
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .apeiron-section-title',
			]
		);

		$this->add_control(
			'section_title_color',
			[
				'label'     => __( 'Warna Judul', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-section-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->register_button_global_style();

		$this->start_controls_section(
			'section_table_style_unified',
			[
				'label' => __( 'Tabel Tamu', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'heading_table_container',
			[
				'label' => __( 'Kontainer', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'guest_list_background',
				'label'    => __( 'Latar', 'apeiron-kit' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .apeiron-guest-list-section',
			]
		);

		$this->add_responsive_control(
			'guest_list_padding',
			[
				'label'      => __( 'Jarak Dalam', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-guest-list-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'guest_list_border_radius',
			[
				'label'      => __( 'Sudut', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-guest-list-section' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'guest_list_border',
				'selector'       => '{{WRAPPER}} .apeiron-guest-list-section',
				'fields_options' => [
					'border' => [
						'selectors' => [
							'{{WRAPPER}} .apeiron-guest-list-section' => 'border-style: {{VALUE}}; border-top-style: {{VALUE}};',
						],
					],
					'width' => [
						'selectors' => [
							'{{WRAPPER}} .apeiron-guest-list-section' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; border-top-width: {{TOP}}{{UNIT}};',
						],
					],
					'color' => [
						'selectors' => [
							'{{WRAPPER}} .apeiron-guest-list-section' => 'border-color: {{VALUE}}; border-top-color: {{VALUE}};',
						],
					],
				],
			]
		);

		$this->add_control(
			'heading_table_style',
			[
				'label'     => __( 'Gaya Tabel', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'table_header_background',
			[
				'label'     => __( 'Latar Kepala', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table thead, {{WRAPPER}} .apeiron-guest-table thead th' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_header_color',
			[
				'label'     => __( 'Warna Kepala', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table thead th' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'table_header_typography',
				'label'    => __( 'Tipografi Kepala', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-guest-table thead th',
			]
		);

		$this->add_control(
			'table_row_background',
			[
				'label'     => __( 'Warna Baris', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table tbody tr' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_row_hover_background',
			[
				'label'     => __( 'Warna Sorot Baris', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fbfcff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table tbody tr:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_body_text_color',
			[
				'label'     => __( 'Warna Isi', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d2939',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table tbody td' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_separator_color',
			[
				'label'     => __( 'Warna Garis Tabel', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#eef0f5',
				'selectors' => [
					'{{WRAPPER}} .apeiron-table-wrapper' => '--apeiron-guest-invitation-border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_empty_background',
			[
				'label'     => __( 'Latar Data Kosong', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fbfcff',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table tbody tr.apeiron-empty-state td' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'table_empty_text_color',
			[
				'label'     => __( 'Warna Data Kosong', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-guest-table tbody tr.apeiron-empty-state td' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'table_border',
				'label'    => __( 'Garis Tabel', 'apeiron-kit' ),
				'selector' => '{{WRAPPER}} .apeiron-table-wrapper',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'    => '1',
							'right'  => '1',
							'bottom' => '1',
							'left'   => '1',
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
			'table_wrapper_border_radius',
			[
				'label'      => __( 'Sudut Tabel', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-table-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_button_global_style(): void {
		$this->start_controls_section(
			'section_button_style_grouped',
			[
				'label' => __( 'Tombol', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .apeiron-btn',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .apeiron-btn',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top'    => '1',
							'right'  => '1',
							'bottom' => '1',
							'left'   => '1',
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => '#083c57',
					],
				],
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => __( 'Sudut', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Jarak Dalam', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => '12',
					'right'  => '18',
					'bottom' => '12',
					'left'   => '18',
					'unit'   => 'px',
					'isLinked' => false,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => __( 'Jarak Luar', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top'    => '0',
					'right'  => '0',
					'bottom' => '0',
					'left'   => '0',
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'button_box_shadow',
				'selector'       => '{{WRAPPER}} .apeiron-btn',
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

		$this->register_button_role_colors_section();

		$this->end_controls_section();
	}

	private function register_button_role_colors_section(): void {
		$this->add_control(
			'heading_button_role_colors',
			[
				'label'     => __( 'Jenis Tombol', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->start_controls_tabs( 'button_role_tabs' );

		$this->start_controls_tab(
			'button_role_tab_template',
			[
				'label' => __( 'Template', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'template_role_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-template' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'template_role_background_color',
			[
				'label'     => __( 'Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-template' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'template_role_border_color',
			[
				'label'     => __( 'Warna Garis', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-template' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'template_active_background_color',
			[
				'label'     => __( 'Latar Aktif', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-template.is-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'template_active_text_color',
			[
				'label'     => __( 'Teks Aktif', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-template.is-active' => 'color: {{VALUE}};',
					'{{WRAPPER}} .apeiron-btn-template.is-active:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'template_active_outline_color',
			[
				'label'     => __( 'Garis Aktif', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-template.is-active' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'template_button_padding_compact',
			[
				'label'      => __( 'Jarak Dalam', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-btn-template' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'template_button_gap_compact',
			[
				'label'      => __( 'Jarak Antar', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-template-buttons' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_role_tab_create',
			[
				'label' => __( 'Buat', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'primary_button_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-create' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'primary_button_background_color',
			[
				'label'     => __( 'Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-create' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'primary_button_hover_background_color',
			[
				'label'     => __( 'Latar Sorot', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-create:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_role_tab_download',
			[
				'label' => __( 'Unduh', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'download_role_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#475467',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-download' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'download_role_background_color',
			[
				'label'     => __( 'Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-download' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'download_role_border_color',
			[
				'label'     => __( 'Warna Garis', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e5e7ef',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-download' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_role_tab_import',
			[
				'label' => __( 'Impor', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'import_role_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-excel' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'import_role_background_color',
			[
				'label'     => __( 'Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-excel' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'import_role_hover_background_color',
			[
				'label'     => __( 'Latar Sorot', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#062f44',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-excel:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'import_button_min_width',
			[
				'label'      => __( 'Lebar Minimal', 'apeiron-kit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 80,
						'max' => 360,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-btn-excel' => 'min-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'import_button_padding_compact',
			[
				'label'      => __( 'Jarak Dalam', 'apeiron-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .apeiron-btn-excel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_role_tab_copy',
			[
				'label' => __( 'Salin', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'copy_role_text_color',
			[
				'label'     => __( 'Warna Teks', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-copy' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'copy_role_background_color',
			[
				'label'     => __( 'Latar', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#083c57',
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-copy' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'copy_role_hover_background_color',
			[
				'label'     => __( 'Latar Sorot', 'apeiron-kit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .apeiron-btn-copy:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
	}
}
