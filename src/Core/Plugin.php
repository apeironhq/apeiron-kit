<?php

namespace ApeironKit\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ApeironKit\Admin\SettingsPage;
use ApeironKit\Elementor\WidgetManager;
use ApeironKit\Rest\CommentsController;
use ApeironKit\Support\AssetManager;
use ApeironKit\Support\CommentDockSettingsStore;
use ApeironKit\Support\LegacyContentCleanup;
use ApeironKit\Support\StickerManager;
use ApeironKit\Support\WidgetUsageIndex;

class Plugin {
	public const SETTINGS_AJAX_ACTIONS = [
		'dashboard_tab'         => 'apeiron_load_dashboard_tab',
		'social_proof_save'     => 'apeiron_save_social_proof',
		'social_proof_delete'   => 'apeiron_delete_social_proof_entry',
		'social_proof_upload'   => 'apeiron_upload_client_photo',
		'social_proof_export'   => 'apeiron_export_social_proof_backup',
		'social_proof_validate' => 'apeiron_validate_social_proof_backup',
		'social_proof_import'   => 'apeiron_import_social_proof_backup',
		'widget_toggle'         => 'apeiron_toggle_widget',
		'widget_bulk_toggle'    => 'apeiron_bulk_toggle_widgets',
		'widget_usage'          => 'apeiron_check_widget_usage',
		'ucapan_tamu_save'      => 'apeiron_save_ucapan_tamu',
	];

	private const LICENSE_AJAX_ACTIONS = [
		'apeiron_activate_license',
		'apeiron_deactivate_license',
		'apeiron_check_license',
		'apeiron_save_server_config',
		'apeiron_test_server_connection',
	];

	private Requirements $requirements;
	private ?AssetManager $assets = null;
	private ?SettingsPage $settings = null;

	public function __construct() {
		$this->requirements = new Requirements();
	}

	/**
	 * Lazily build the SettingsPage and its admin/AJAX dependencies.
	 *
	 * Admin page and AJAX routing dependencies are unnecessary on frontend,
	 * REST, and cron requests.
	 */
	private function settings(): SettingsPage {
		if ( null === $this->settings ) {
			$this->settings = new SettingsPage();
		}

		return $this->settings;
	}

	public function boot(): void {
		// Attach Elementor hooks only after requirements have been verified. Attaching
		// them earlier produced a half-boot state where widgets registered despite a
		// failing/unavailable Elementor, while missing assets, REST, license, and
		// comment-dock services left the plugin in an inconsistent partial-initialisation.
		if ( ! $this->requirements->passes() ) {
			$this->requirements->register_notice();
			return;
		}

		( new WidgetManager() )->register();
		CommentDockSettingsStore::register();

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function init(): void {
		if ( is_admin() || wp_doing_ajax() ) {
			( new WidgetUsageIndex() )->register();
		}

		if ( $this->should_register_license_manager() ) {
			LicenseManager::instance()->register();
		}
		$this->register_asset_bootstrap_hooks();

		if ( wp_doing_ajax() && in_array( $this->request_action(), [ 'apeiron_upload_sticker', 'apeiron_delete_sticker' ], true ) ) {
			( new StickerManager() )->register();
		}

		// Admin hooks are only meaningful in wp-admin or admin-ajax.
		if ( ( is_admin() && ! wp_doing_ajax() )
			|| ( wp_doing_ajax() && in_array( $this->request_action(), self::SETTINGS_AJAX_ACTIONS, true ) )
		) {
			$this->settings()->register();
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			( new LegacyContentCleanup() )->register();
		}
	}

	/**
	 * Register REST routes without loading the controller on non-REST requests.
	 */
	public function register_rest_routes(): void {
		( new CommentsController() )->register_routes();
	}

	/**
	 * Load the asset service only when one of its hooks can run.
	 */
	private function register_asset_bootstrap_hooks(): void {
		if ( $this->is_rest_request() || wp_doing_cron() ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'register_assets' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 0 );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'register_assets' ], 0 );

		if ( wp_doing_ajax() && 'heartbeat' === $this->request_action() ) {
			$this->register_assets();
		}
	}

	/**
	 * Instantiate AssetManager at the last responsible lifecycle hook.
	 */
	public function register_assets(): void {
		if ( null !== $this->assets || ! $this->should_register_assets() ) {
			return;
		}

		$this->assets = new AssetManager();
		$this->assets->register();
	}

	private function should_register_assets(): bool {
		if ( $this->is_rest_request() || wp_doing_cron() ) {
			return false;
		}

		if ( ! wp_doing_ajax() || 'heartbeat' === $this->request_action() ) {
			return true;
		}

		return doing_action( 'wp_head' )
			|| doing_action( 'wp_enqueue_scripts' )
			|| doing_action( 'elementor/editor/after_enqueue_scripts' );
	}

	private function is_rest_request(): bool {
		if ( function_exists( 'wp_is_serving_rest_request' ) ) {
			return wp_is_serving_rest_request();
		}

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	private function request_action(): string {
		$action = $_REQUEST['action'] ?? '';

		return is_string( $action ) ? wp_unslash( $action ) : '';
	}

	private function should_register_license_manager(): bool {
		if ( wp_doing_cron() || ( is_admin() && ! wp_doing_ajax() ) ) {
			return true;
		}

		return wp_doing_ajax()
			&& in_array( $this->request_action(), self::LICENSE_AJAX_ACTIONS, true );
	}
}

