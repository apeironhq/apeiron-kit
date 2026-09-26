<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\GuestInvitationManager;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\GuestInvitationManager\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\GuestInvitationManager\Concerns\RegistersStyleControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GuestInvitationManager extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	private const IMPORT_MODES = [ 'append', 'replace' ];

	public function get_name() {
		return 'apeiron-guest-invitation-manager';
	}

	public function get_title() {
		return __( 'Manajemen Tamu', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-manager';
	}

	public function get_keywords() {
		return [ 'guest', 'invitation', 'tamu', 'undangan', 'whatsapp', 'apeiron' ];
	}

	public function get_style_depends() {
		$styles   = parent::get_style_depends();
		$styles[] = 'apeiron-kit-guest-invitation';
		$styles[] = 'elementor-icons-fa-solid';
		$styles[] = 'elementor-icons-fa-regular';
		$styles[] = 'elementor-icons-fa-brands';

		return array_values( array_unique( $styles ) );
	}

	public function get_script_depends() {
		$scripts   = parent::get_script_depends();
		$scripts[] = 'apeiron-kit-guest-invitation-js';

		return $scripts;
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings = $this->get_settings_for_display();

		$context = [
			'settings'          => $settings,
			'widget_id'         => $this->get_id(),
			'unique_id'         => 'apeiron-invitation-' . $this->get_id(),
			'excel_import_mode' => $this->get_excel_import_mode( $settings ),
			'skip_excel_header' => $this->get_yes_no( $settings, 'skip_excel_header' ),
			'show_guest_list'   => $this->get_yes_no( $settings, 'show_guest_list' ),
		];

		/**
		 * Filter the render context before markup is emitted.
		 *
		 * @since 1.1.0
		 *
		 * @param array $context  Render context passed to the partial.
		 * @param array $settings Full settings array.
		 * @param self  $widget   Widget instance.
		 */
		$context = (array) apply_filters(
			'apeiron_guest_invitation_render_context',
			$context,
			$settings,
			$this
		);

		$this->render_guest_invitation( $context );
	}

	/** @param array<string,mixed> $settings */
	private function get_excel_import_mode( array $settings ): string {
		$mode = $settings['excel_import_mode'] ?? '';

		return in_array( $mode, self::IMPORT_MODES, true ) ? (string) $mode : 'append';
	}

	/** @param array<string,mixed> $settings */
	private function get_yes_no( array $settings, string $key ): string {
		return 'yes' === ( $settings[ $key ] ?? 'yes' ) ? 'yes' : 'no';
	}

	/** @param array<string,mixed> $context */
	private function render_guest_invitation( array $context ): void {
		$settings          = $context['settings'];
		$widget_id         = $context['widget_id'];
		$unique_id         = $context['unique_id'];
		$excel_import_mode = $context['excel_import_mode'];
		$skip_excel_header = $context['skip_excel_header'];
		$show_guest_list   = $context['show_guest_list'];

		require __DIR__ . '/Partials/guest-invitation-manager.php';
	}
}
