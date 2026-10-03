<?php

namespace ApeironKit\Elementor\Widgets\FormOrder\Concerns;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersStyleControls {
	private function register_style_controls(): void {
		$parts = [
			'container' => [ __( 'Form Container', 'apeiron-kit' ), '.apeiron-form-order' ],
			'heading' => [ __( 'Heading', 'apeiron-kit' ), '.apeiron-form-order__title, {{WRAPPER}} .apeiron-form-order__step-title, {{WRAPPER}} .apeiron-form-order__heading' ],
			'progress' => [ __( 'Progress / Step Indicator', 'apeiron-kit' ), '.apeiron-form-order__progress-fill' ],
			'counter' => [ __( 'Nomor Tahap', 'apeiron-kit' ), '.apeiron-form-order__counter' ],
			'section' => [ __( 'Section', 'apeiron-kit' ), '.apeiron-form-order__step' ],
			'labels' => [ __( 'Label', 'apeiron-kit' ), '.apeiron-form-order__field-label' ],
			'fields' => [ __( 'Input', 'apeiron-kit' ), '.apeiron-form-order__input' ],
			'textarea' => [ __( 'Textarea', 'apeiron-kit' ), 'textarea.apeiron-form-order__input' ],
			'select' => [ __( 'Select', 'apeiron-kit' ), 'select.apeiron-form-order__input' ],
			'choice' => [ __( 'Radio / Checkbox', 'apeiron-kit' ), '.apeiron-form-order__choice' ],
			'button' => [ __( 'Tombol', 'apeiron-kit' ), '.apeiron-form-order__button' ],
			'navigation' => [ __( 'Navigation Button', 'apeiron-kit' ), '.apeiron-form-order__button--next, {{WRAPPER}} .apeiron-form-order__button--back' ],
			'submit' => [ __( 'Submit Button', 'apeiron-kit' ), '.apeiron-form-order__button--submit' ],
			'notice' => [ __( 'Status', 'apeiron-kit' ), '.apeiron-form-order__notice' ],
			'error' => [ __( 'Error', 'apeiron-kit' ), '.apeiron-form-order__notice.is-error' ],
			'success' => [ __( 'Success Message', 'apeiron-kit' ), '.apeiron-form-order__notice:not(.is-error)' ],
			'summary' => [ __( 'Ringkasan', 'apeiron-kit' ), '.apeiron-form-order__summary' ],
		];
		foreach ( $parts as $key => $part ) {
			$selector = '{{WRAPPER}} ' . $part[1];
			$this->start_controls_section( 'style_' . $key, [ 'label' => $part[0], 'tab' => Controls_Manager::TAB_STYLE ] );
			$this->add_control( $key . '_color', [
				'label' => __( 'Warna Teks', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
				'selectors' => [ $selector => 'color: {{VALUE}};' ],
			] );
			$this->add_control( $key . '_background', [
				'label' => __( 'Warna Latar', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
				'default' => 'container' === $key ? '#f7f7fb' : '',
				'selectors' => [ $selector => 'background-color: {{VALUE}};' ],
			] );
			$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => $key . '_typography', 'selector' => $selector ] );
			$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => $key . '_border', 'selector' => $selector ] );
			$this->add_responsive_control( $key . '_padding', [
				'label' => __( 'Padding', 'apeiron-kit' ), 'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors' => [ $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			] );
			$this->add_responsive_control( $key . '_radius', [
				'label' => __( 'Radius', 'apeiron-kit' ), 'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [ $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			] );
			$this->add_responsive_control( $key . '_width', [
				'label' => __( 'Lebar', 'apeiron-kit' ), 'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [ 'px' => [ 'min' => 0, 'max' => 1200 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors' => [ ( 'progress' === $key ? '{{WRAPPER}} .apeiron-form-order__progress' : $selector ) => 'width: {{SIZE}}{{UNIT}};' ],
			] );
			$this->add_responsive_control( $key . '_alignment', [
				'label' => __( 'Perataan', 'apeiron-kit' ), 'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [ 'title' => __( 'Kiri', 'apeiron-kit' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => __( 'Tengah', 'apeiron-kit' ), 'icon' => 'eicon-text-align-center' ],
					'right' => [ 'title' => __( 'Kanan', 'apeiron-kit' ), 'icon' => 'eicon-text-align-right' ],
				],
				'selectors' => [ $selector => 'text-align: {{VALUE}};' ],
			] );
			$this->end_controls_section();
		}
		$this->start_controls_section( 'style_interaction', [ 'label' => __( 'Interaksi dan Tata Letak', 'apeiron-kit' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'input_focus_border_color', [
			'label' => __( 'Warna Border Input Fokus', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__input:focus-visible' => 'border-color: {{VALUE}}; outline-color: {{VALUE}};' ],
		] );
		$this->add_control( 'error_input_border_color', [ 'label' => __( 'Border Field Tidak Valid', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__input[aria-invalid="true"]' => 'border-color: {{VALUE}};' ],
		] );
		$this->add_control( 'button_hover_background', [
			'label' => __( 'Latar Tombol Hover', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__button:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'button_hover_color', [
			'label' => __( 'Teks Tombol Hover', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__button:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'submit_hover_background', [ 'label' => __( 'Latar Submit Hover', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__button--submit:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'navigation_hover_background', [ 'label' => __( 'Latar Navigasi Hover', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__button--next:hover, {{WRAPPER}} .apeiron-form-order__button--back:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'button_focus_color', [ 'label' => __( 'Warna Fokus Tombol', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__button:focus-visible' => 'outline-color: {{VALUE}};' ],
		] );
		$this->add_control( 'button_active_background', [ 'label' => __( 'Latar Tombol Aktif', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__button:active' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'progress_active_color', [ 'label' => __( 'Warna Progress Aktif', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__progress-fill' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'progress_track_color', [ 'label' => __( 'Warna Latar Progress', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__progress' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'choice_accent_color', [ 'label' => __( 'Warna Pilihan Aktif', 'apeiron-kit' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__choice input:checked' => 'accent-color: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'field_gap', [
			'label' => __( 'Jarak Antar Field', 'apeiron-kit' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .apeiron-form-order__grid' => '--order-field-gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->end_controls_section();
	}
}
