<?php

namespace ApeironKit\Core;

class Requirements {

	private string $min_php        = '7.4';
	private string $min_wp         = '6.0';
	private string $min_elementor  = '3.7.0';

	private array $requirements = [];

	/**
	 * Build the requirements at check time instead of construction time.
	 *
	 * Elementor can finish loading after this plugin has been instantiated. A
	 * cached result from an earlier lifecycle phase would otherwise prevent the
	 * widget hooks from ever being registered.
	 *
	 * @return array<int,array{check:string,passed:bool,message:string}>
	 */
	private function get_requirements(): array {
		$elementor_loaded = $this->check_elementor();

		return [
			[
				'check'  => 'elementor_loaded',
				'passed' => $elementor_loaded,
				'message' => __( 'Apeiron Kit membutuhkan Elementor aktif.', 'apeiron-kit' ),
			],
			[
				'check'   => 'elementor_version',
				'passed'  => $elementor_loaded && $this->check_elementor_version(),
				'message' => sprintf(
					/* translators: %s: required Elementor version */
					__( 'Apeiron Kit membutuhkan Elementor versi %s atau lebih baru.', 'apeiron-kit' ),
					$this->min_elementor
				),
			],
			[
				'check'  => 'php_version',
				'passed' => version_compare( PHP_VERSION, $this->min_php, '>=' ),
				'message' => sprintf(
					/* translators: %s: required PHP version */
					__( 'Apeiron Kit membutuhkan PHP %s atau lebih baru.', 'apeiron-kit' ),
					$this->min_php
				),
			],
			[
				'check'  => 'wp_version',
				'passed' => $this->check_wp_version(),
				'message' => sprintf(
					/* translators: %s: required WordPress version */
					__( 'Apeiron Kit membutuhkan WordPress %s atau lebih baru.', 'apeiron-kit' ),
					$this->min_wp
				),
			],
		];
	}

	/**
	 * Check if Elementor is loaded and available.
	 *
	 * Uses `elementor/loaded` first because Elementor fires it only after its own
	 * dependencies and Plugin instance are fully ready. Checking only `is_plugin_active()`
	 * treated "plugin file present" as "running", which let Apeiron boot against an
	 * Elementor installation that had aborted its own bootstrap (e.g. on an
	 * unsupported WordPress release).
	 */
	private function check_elementor(): bool {
		if ( did_action( 'elementor/loaded' ) ) {
			return true;
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance ) ) {
			return true;
		}

		return false;
	}

	private function check_elementor_version(): bool {
		if ( ! $this->check_elementor() ) {
			return false;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return false;
		}

		return version_compare( ELEMENTOR_VERSION, $this->min_elementor, '>=' );
	}

	private function check_wp_version(): bool {
		global $wp_version;

		if ( empty( $wp_version ) ) {
			return false;
		}

		return version_compare( $wp_version, $this->min_wp, '>=' );
	}

	public function passes(): bool {
		$this->requirements = $this->get_requirements();

		foreach ( $this->requirements as $requirement ) {
			if ( ! $requirement['passed'] ) {
				return false;
			}
		}

		return true;
	}

	public function register_notice(): void {
		$this->requirements = $this->get_requirements();

		add_action(
			'admin_notices',
			function () {
				$messages = array_filter(
					$this->requirements,
					static function ( $requirement ) {
						return ! $requirement['passed'];
					}
				);

				if ( empty( $messages ) ) {
					return;
				}

				echo '<div class="notice notice-error"><p>';
				foreach ( $messages as $message ) {
					echo esc_html( $message['message'] ) . '<br>';
				}
				echo '</p></div>';
			}
		);
	}
}

