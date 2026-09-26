<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\Countdown\Concerns;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_general',
			[ 'label' => __( 'Waktu Target', 'apeiron-kit' ) ]
		);

		$this->add_control(
			'target_date',
			[
				'label'       => __( 'Tanggal dan Waktu Selesai', 'apeiron-kit' ),
				'type'        => Controls_Manager::DATE_TIME,
				'default'     => wp_date( 'Y-m-d H:i', time() + WEEK_IN_SECONDS ),
				'description' => __( 'Mengikuti timezone WordPress pada menu Settings > General.', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'expired_text',
			[
				'label'       => __( 'Pesan Setelah Waktu Habis', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Waktu habis!', 'apeiron-kit' ),
				'description' => __( 'Kosongkan jika tidak ingin menampilkan pesan setelah timer selesai.', 'apeiron-kit' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'digit_padding',
			[
				'label'       => __( 'Jumlah Digit Angka', 'apeiron-kit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '2',
				'options'     => [
					'2' => __( '2 digit (00)', 'apeiron-kit' ),
					'3' => __( '3 digit (000)', 'apeiron-kit' ),
					'4' => __( '4 digit (0000)', 'apeiron-kit' ),
				],
				'description' => __( 'Naikkan jika countdown melebihi 99 unit (misal ratusan hari).', 'apeiron-kit' ),
			]
		);

		$this->end_controls_section();

		$this->register_unit_controls();
		$this->register_separator_controls();
	}

	private function register_unit_controls(): void {
		$this->start_controls_section(
			'section_parts',
			[ 'label' => __( 'Unit Waktu', 'apeiron-kit' ) ]
		);

		$units = [
			'days'    => __( 'Hari', 'apeiron-kit' ),
			'hours'   => __( 'Jam', 'apeiron-kit' ),
			'minutes' => __( 'Menit', 'apeiron-kit' ),
			'seconds' => __( 'Detik', 'apeiron-kit' ),
		];

		foreach ( $units as $name => $label ) {
			$visibility_control = [
				'label'        => $label,
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			];

			if ( 'days' !== $name ) {
				$visibility_control['separator'] = 'before';
			}

			$this->add_control( 'show_' . $name, $visibility_control );

			$this->add_control(
				'label_' . $name,
				[
					'label'     => sprintf(
						/* translators: %s: time unit label. */
						__( 'Teks Label %s', 'apeiron-kit' ),
						$label
					),
					'type'      => Controls_Manager::TEXT,
					'default'   => $label,
					'condition' => [ 'show_' . $name => 'yes' ],
					'dynamic'   => [ 'active' => true ],
				]
			);
		}

		$this->end_controls_section();
	}

	private function register_separator_controls(): void {
		$this->start_controls_section(
			'section_separator',
			[ 'label' => __( 'Pemisah Angka', 'apeiron-kit' ) ]
		);

		$this->add_control(
			'show_separator',
			[
				'label'        => __( 'Gunakan Pemisah', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'separator_text',
			[
				'label'     => __( 'Karakter Pemisah', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => ':',
				'condition' => [ 'show_separator' => 'yes' ],
				'dynamic'   => [ 'active' => true ],
			]
		);

		$this->end_controls_section();
	}
}
