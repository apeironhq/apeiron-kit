<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SocialProof\Concerns;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_settings',
			[
				'label' => __( 'Pengaturan', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'enable_popup',
			[
				'label'        => __( 'Aktifkan Popup', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'use_custom_template',
			[
				'label'        => __( 'Gunakan Format Pesan Khusus', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Aktifkan jika format pesan di halaman ini perlu berbeda dari pengaturan dashboard.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'text_template',
			[
				'label'       => __( 'Format Pesan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => '{name} telah membeli {product} pada:',
				'placeholder' => '{name} telah membeli {product} pada:',
				'description' => __( 'Gunakan {name} untuk nama pelanggan dan {product} untuk produk atau layanan.', 'apeiron-kit' ),
				'condition'   => [
					'use_custom_template' => 'yes',
				],
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'override_position',
			[
				'label'        => __( 'Gunakan Posisi Khusus', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Aktifkan jika posisi popup di halaman ini perlu berbeda dari dashboard.', 'apeiron-kit' ),
				'prefix_class' => 'apeiron-sp-custom-position-',
			]
		);

		$this->add_control(
			'popup_position',
			[
				'label'       => __( 'Posisi Popup', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'top-right'    => __( 'Atas Kanan', 'apeiron-kit' ),
					'top-left'     => __( 'Atas Kiri', 'apeiron-kit' ),
					'bottom-right' => __( 'Bawah Kanan', 'apeiron-kit' ),
					'bottom-left'  => __( 'Bawah Kiri', 'apeiron-kit' ),
				],
				'default'     => 'bottom-right',
				'description' => __( 'Popup tetap fixed pada sudut layar yang dipilih.', 'apeiron-kit' ),
				'condition'   => [
					'override_position' => 'yes',
				],
				'prefix_class' => 'apeiron-sp-position-',
			]
		);

		$this->end_controls_section();
	}
}
