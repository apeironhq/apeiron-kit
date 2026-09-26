<?php

namespace ApeironKit\Support\Assets;

use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EditorNotifier {

	private AssetRegistrar $registrar;
	private ElementorContext $context;

	public function __construct(
		AssetRegistrar $registrar,
		ElementorContext $context
	) {
		$this->registrar = $registrar;
		$this->context   = $context;
	}

	public function enqueue(): void {
		if ( ! $this->context->is_editor_shell() ) {
			return;
		}

		wp_register_script(
			'apeiron-kit-editor-shell',
			$this->registrar->url( 'assets/js/editor-shell', 'js' ),
			[ 'elementor-editor', 'heartbeat', 'jquery' ],
			$this->registrar->version( 'assets/js/editor-shell', 'js' ),
			true
		);

		wp_localize_script(
			'apeiron-kit-editor-shell',
			'ApeironEditorShellConfig',
			[
				'disabledWidgets' => WidgetRegistry::disabled_slugs(),
				'i18n'            => [
					'changed' => __( 'Pengaturan widget Apeiron telah berubah. Segarkan editor untuk menerapkan perubahan.', 'apeiron-kit' ),
					'refresh' => __( 'Segarkan', 'apeiron-kit' ),
					'dismiss' => __( 'Tutup', 'apeiron-kit' ),
				],
			]
		);

		wp_enqueue_script( 'apeiron-kit-editor-shell' );

		wp_enqueue_style(
			'apeiron-kit-editor-shell',
			$this->registrar->url( 'assets/css/editor-shell', 'css' ),
			[],
			$this->registrar->version( 'assets/css/editor-shell', 'css' )
		);

		wp_enqueue_style(
			'apeiron-kit-editor-license',
			$this->registrar->url( 'assets/css/editor-license', 'css' ),
			[],
			$this->registrar->version( 'assets/css/editor-license', 'css' )
		);

		wp_enqueue_style(
			'apeiron-kit-widget-icons',
			$this->registrar->url( 'assets/css/widget-icons', 'css' ),
			[],
			$this->registrar->version( 'assets/css/widget-icons', 'css' )
		);
	}

	/**
	 * @param array<string,mixed> $response Heartbeat response data.
	 * @param array<string,mixed> $data     Heartbeat request data.
	 * @return array<string,mixed>
	 */
	public function heartbeat_received( array $response, array $data ): array {
		if ( ! empty( $data['apeiron_editor_active'] ) ) {
			$response['apeiron_disabled_widgets'] = WidgetRegistry::disabled_slugs();
		}

		return $response;
	}
}
