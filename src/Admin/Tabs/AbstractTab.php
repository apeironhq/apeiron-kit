<?php

namespace ApeironKit\Admin\Tabs;

/** Dasar untuk tab pengaturan admin. */
abstract class AbstractTab {

	abstract public function get_slug(): string;

	abstract public function get_title(): string;

	abstract public function render(): void;

	protected function get_save_icon_markup(): string {
		return '<span class="apeiron-save-icon" aria-hidden="true"></span>';
	}

	/** @param array<string,mixed> $config Konfigurasi untuk skrip admin. */
	protected function render_config_payload( string $name, array $config ): void {
		$encoded = wp_json_encode( $config );
		$encoded = is_string( $encoded ) ? $encoded : '{}';

		printf(
			'<span hidden data-apeiron-admin-config="%1$s" data-config="%2$s"></span>',
			esc_attr( $name ),
			esc_attr( $encoded )
		);
	}
}
