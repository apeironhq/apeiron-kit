<?php

namespace ApeironKit\Admin\Tabs;

/**
 * Abstract Tab class for admin settings tabs.
 */
abstract class AbstractTab {

	/**
	 * Get tab slug.
	 *
	 * @return string
	 */
	abstract public function get_slug(): string;

	/**
	 * Get tab title.
	 *
	 * @return string
	 */
	abstract public function get_title(): string;

	/**
	 * Render tab content.
	 *
	 * @return void
	 */
	abstract public function render(): void;

	/**
	 * Shared save icon for admin buttons.
	 *
	 * @return string
	 */
	protected function get_save_icon_markup(): string {
		return '<span class="apeiron-save-icon" aria-hidden="true"></span>';
	}

	/**
	 * Render inert JSON for an external admin runtime.
	 *
	 * @param array<string,mixed> $config Runtime configuration.
	 */
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
