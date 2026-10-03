<?php

namespace ApeironKit\Elementor\Widgets\FormOrder;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\FormOrder\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\FormOrder\Concerns\RegistersStyleControls;
use ApeironKit\Support\FormOrderSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormOrder extends BaseWidget {
	use RegistersContentControls;
	use RegistersStyleControls;
	private static bool $editor_style_hooked = false;

	public function __construct( $data = [], $args = null ) {
		if ( ! self::$editor_style_hooked && function_exists( 'add_action' ) ) {
			add_action( 'elementor/controls/register', [ self::class, 'register_field_repeater' ] );
			if ( did_action( 'elementor/controls/register' ) ) {
				self::register_field_repeater( \Elementor\Plugin::$instance->controls_manager );
			}
			add_action( 'elementor/editor/after_enqueue_scripts', [ self::class, 'enqueue_editor_style' ], 20 );
			self::$editor_style_hooked = true;
		}
		if ( is_array( $data['settings'] ?? null ) && is_array( $data['settings']['order_steps'] ?? null ) ) {
			$builder = FieldSchema::builder_rows( $data['settings'] );
			$data['settings']['order_steps'] = $builder['steps'];
			$data['settings']['order_fields'] = $builder['fields'];
		}
		parent::__construct( $data, $args );
	}

	public static function register_field_repeater( $manager ): void {
		if ( ! $manager->get_control( 'apeiron_order_fields' ) ) {
			$manager->register( new \ApeironKit\Elementor\Widgets\FormOrder\Controls\FieldRepeater() );
		}
	}

	public static function enqueue_editor_style(): void {
		$path = APEIRON_KIT_PATH . 'assets/css/editor-form-order.css';
		$script = APEIRON_KIT_PATH . 'assets/js/editor-form-order.js';
		wp_enqueue_style(
			'apeiron-kit-form-order-editor',
			APEIRON_KIT_URL . 'assets/css/editor-form-order.css',
			[],
			file_exists( $path ) ? (string) filemtime( $path ) : APEIRON_KIT_VERSION
		);
		wp_enqueue_script(
			'apeiron-kit-form-order-editor',
			APEIRON_KIT_URL . 'assets/js/editor-form-order.js',
			[ 'elementor-editor' ],
			file_exists( $script ) ? (string) filemtime( $script ) : APEIRON_KIT_VERSION,
			true
		);
	}

	public function get_name() {
		return 'apeiron-form-order';
	}

	public function get_title() {
		return __( 'Form Order', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_style_depends() {
		return array_merge( parent::get_style_depends(), [ 'apeiron-kit-form-order' ] );
	}

	public function get_script_depends() {
		return [ 'apeiron-kit-form-order-js' ];
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	protected function render_widget() {
		$settings = $this->get_settings_for_display();
		$raw_settings = $this->get_settings();
		$step_settings = is_array( $settings ) ? $settings : [];
		if ( is_array( $raw_settings ) && isset( $raw_settings['order_fields'] ) ) {
			$step_settings['order_fields'] = $raw_settings['order_fields'];
		}
		$steps = FieldSchema::resolve( is_array( $step_settings ) ? $step_settings : [] );
		if ( ! $steps ) {
			if ( $this->is_elementor_editor_preview() ) {
				echo '<p class="apeiron-form-order__empty">' . esc_html__( 'Tambahkan step pada pengaturan Form Order.', 'apeiron-kit' ) . '</p>';
			}
			return;
		}
		$order_settings = FormOrderSettings::get();
		$post_id = get_queried_object_id() ?: ( get_the_ID() ?: 0 );
		$document = method_exists( $this, 'get_document' ) ? $this->get_document() : null;
		$document_id = is_object( $document ) && method_exists( $document, 'get_main_id' ) ? (int) $document->get_main_id() : $post_id;
		$element_id = $this->get_id();
		$config = [
			'endpoint' => esc_url_raw( rest_url( 'apeiron-kit/v1/form-order' ) ),
			'nonce' => wp_create_nonce( 'apeiron_form_order' ),
			'restNonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'postId' => $post_id,
			'documentId' => $document_id,
			'elementId' => $element_id,
			'targetToken' => wp_hash( 'apeiron-form-order|' . $post_id . '|' . $document_id . '|' . $element_id ),
			'openNewTab' => in_array( $order_settings['delivery_target'], [ 'whatsapp', 'both' ], true ) && 'yes' === $order_settings['whatsapp_enabled'] && 'new_tab' === $order_settings['whatsapp_behavior'],
			'validationMessage' => (string) ( $settings['validation_text'] ?? __( 'Periksa field yang wajib diisi atau format yang belum valid.', 'apeiron-kit' ) ),
		];
		require __DIR__ . '/Partials/form-order.php';
	}
}
