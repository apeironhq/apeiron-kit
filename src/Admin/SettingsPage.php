<?php

namespace ApeironKit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ApeironKit\Admin\Tabs\GeneralTab;
use ApeironKit\Admin\Tabs\LicenseTab;
use ApeironKit\Admin\Tabs\CoverTab;
use ApeironKit\Admin\Tabs\StickerTab;
use ApeironKit\Admin\Tabs\SocialProofTab;
use ApeironKit\Admin\Tabs\UcapanTamuTab;
use ApeironKit\Admin\Ajax\SocialProofHandler;
use ApeironKit\Admin\Ajax\UcapanTamuHandler;
use ApeironKit\Admin\Ajax\WidgetToggleHandler;
use ApeironKit\Core\LicenseManager;
use ApeironKit\Core\Plugin;

/**
 * Admin Settings Page Controller.
 * 
 * Orchestrates menu registration, settings, and delegates
 * rendering to individual Tab classes.
 */
class SettingsPage {
	private const AJAX_HANDLERS = [
		'dashboard_tab'         => [ self::class, 'handle_load_dashboard_tab' ],
		'social_proof_save'     => [ SocialProofHandler::class, 'save_settings' ],
		'social_proof_delete'   => [ SocialProofHandler::class, 'delete_entry' ],
		'social_proof_upload'   => [ SocialProofHandler::class, 'upload_photo' ],
		'social_proof_export'   => [ SocialProofHandler::class, 'export_backup' ],
		'social_proof_validate' => [ SocialProofHandler::class, 'validate_backup' ],
		'social_proof_import'   => [ SocialProofHandler::class, 'import_backup' ],
		'widget_toggle'         => [ WidgetToggleHandler::class, 'handle_toggle' ],
		'widget_bulk_toggle'    => [ WidgetToggleHandler::class, 'handle_bulk_toggle' ],
		'widget_usage'          => [ WidgetToggleHandler::class, 'handle_check_usage' ],
		'ucapan_tamu_save'      => [ UcapanTamuHandler::class, 'save_settings' ],
	];

	private string $main_slug   = 'apeiron-kit';
	private bool $registered = false;

	private ?LicenseManager $license_manager;
	private ?ElementorNoticeRelocator $notice_relocator = null;
	private ?GeneralTab $general_tab = null;
	private ?LicenseTab $license_tab = null;
	private ?CoverTab $cover_tab = null;
	private ?StickerTab $sticker_tab = null;
	private ?SocialProofTab $social_proof_tab = null;
	private ?UcapanTamuTab $ucapan_tamu_tab = null;

	public function __construct( ?LicenseManager $license_manager = null ) {
		$this->license_manager = $license_manager;
	}

	private function license_manager(): LicenseManager {
		if ( null === $this->license_manager ) {
			$this->license_manager = LicenseManager::instance();
		}

		return $this->license_manager;
	}

	/**
	 * Register all hooks and actions.
	 */
	public function register(): void {
		$is_ajax = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();

		if ( $this->registered || ( ! is_admin() && ! $is_ajax ) ) {
			return;
		}

		$this->registered = true;

		if ( $is_ajax ) {
			$this->register_ajax_action();
			return;
		}

		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'redirect_legacy_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 20 );

		$this->notice_relocator()->register();
	}

	/**
	 * Relocates Elementor's admin notices into the dashboard layout so they do
	 * not stack above the header. Scoped to the Apeiron Kit screen internally.
	 */
	private function notice_relocator(): ElementorNoticeRelocator {
		if ( null === $this->notice_relocator ) {
			$this->notice_relocator = new ElementorNoticeRelocator();
		}

		return $this->notice_relocator;
	}

	/**
	 * Register only the admin AJAX handler needed by the current request.
	 */
	private function register_ajax_action(): void {
		$raw_action = $_REQUEST['action'] ?? '';
		$action     = is_string( $raw_action ) ? sanitize_key( wp_unslash( $raw_action ) ) : '';
		$route_key  = array_search( $action, Plugin::SETTINGS_AJAX_ACTIONS, true );

		if ( false === $route_key || ! isset( self::AJAX_HANDLERS[ $route_key ] ) ) {
			return;
		}

		[ $handler_class, $method ] = self::AJAX_HANDLERS[ $route_key ];
		$handler                    = self::class === $handler_class ? $this : new $handler_class();

		add_action( 'wp_ajax_' . $action, [ $handler, $method ] );
	}

	/**
	 * Get menu icon URL.
	 *
	 * @return string
	 */
	private function get_menu_icon_url(): string {
		return APEIRON_KIT_URL . 'assets/img/icon.png';
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_menu(): void {
		add_menu_page(
			'ApeironKit',
			'ApeironKit',
			'manage_options',
			$this->main_slug,
			[ $this, 'render_page' ],
			$this->get_menu_icon_url(),
			58
		);
	}

	/**
	 * Render main settings page.
	 */
	public function render_page(): void {
		$active_tab = $this->get_current_tab();

		$this->render_tab_shell( $active_tab );
	}

	/**
	 * Get the active internal admin tab.
	 */
	private function get_current_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'widgets';

		return $this->normalize_dashboard_tab( $tab );
	}

	/**
	 * Normalize dashboard tab keys.
	 */
	private function normalize_dashboard_tab( string $tab ): string {
		$allowed = [
			'widgets',
			'cover',
			'ucapan-tamu',
			'social-proof',
			'stickers',
			'license',
		];

		return in_array( $tab, $allowed, true ) ? $tab : 'widgets';
	}

	/**
	 * Build an internal admin tab URL.
	 */
	private function get_tab_url( string $tab = 'widgets', string $fragment = '' ): string {
		$args = [ 'page' => $this->main_slug ];

		if ( 'widgets' !== $tab ) {
			$args['tab'] = $tab;
		}

		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		if ( '' !== $fragment ) {
			$url .= '#' . ltrim( $fragment, '#' );
		}

		return $url;
	}

	/**
	 * Redirect old submenu URLs to the internal admin tab.
	 */
	public function redirect_legacy_pages(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$legacy_map = [
			'apeiron-kit-license'      => 'license',
			'apeiron-kit-stickers'     => 'stickers',
			'apeiron-kit-social-proof' => 'social-proof',
		];

		if ( ! isset( $legacy_map[ $current_page ] ) ) {
			return;
		}

		wp_safe_redirect( $this->get_tab_url( $legacy_map[ $current_page ] ) );
		exit;
	}

	/**
	 * Render shared shell for internal tabs.
	 */
	private function render_tab_shell( string $active_tab ): void {
		$license = $this->license_manager()->get_license();
		$status_display = $this->license_manager()->get_status_display();
		$is_license_active = ! empty( $license['key'] ) && ! empty( $status_display['is_valid'] );
		?>
		<div class="wrap apeiron-settings-wrap">
			<?php
			/*
			 * Anchor for WordPress's client-side notice relocation. Without it,
			 * wp-admin/js/common.js falls back to `.wrap h1` and injects every
			 * notice from every plugin into the middle of the Apeiron brand
			 * block. Anchoring here keeps those notices at the top of the page,
			 * which is where they sit on any other admin screen.
			 */
			?>
			<hr class="wp-header-end">
			<div class="apeiron-dashboard-shell" data-apeiron-dashboard-tab-nonce="<?php echo esc_attr( wp_create_nonce( 'apeiron_dashboard_tab' ) ); ?>">
				<div class="apeiron-dashboard-header-area">
					<?php $this->render_admin_header( $is_license_active ); ?>
				</div>

				<div class="apeiron-dashboard-layout apeiron-dashboard-layout--tab">
					<?php $this->render_admin_nav( $active_tab ); ?>

					<main class="apeiron-dashboard-main apeiron-tab-main">
						<?php $this->notice_relocator()->render(); ?>
						<div class="apeiron-tab-content">
							<?php $this->render_active_tab_content( $active_tab ); ?>
						</div>
					</main>
				</div>

				<?php $this->render_prefetched_tab_templates( $active_tab ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Embed the cheap license fragment without executing its scripts. This avoids
	 * another full admin-ajax bootstrap for the most common dashboard transition.
	 */
	private function render_prefetched_tab_templates( string $active_tab ): void {
		if ( 'widgets' !== $active_tab ) {
			return;
		}
		?>
		<template data-apeiron-dashboard-prefetch-tab="license"><?php $this->render_active_tab_content( 'license' ); ?></template>
		<?php
	}

	/**
	 * Render shared admin header.
	 */
	private function render_admin_header( bool $is_license_active ): void {
		?>
		<?php if ( ! $is_license_active ) : ?>
			<div class="apeiron-license-alert-shell">
				<div class="apeiron-license-header-alert" role="region" aria-label="<?php esc_attr_e( 'Pemberitahuan lisensi Apeiron Kit', 'apeiron-kit' ); ?>" data-apeiron-license-alert>
					<span class="apeiron-license-header-alert__icon dashicons dashicons-lock"></span>
					<?php
					/*
					 * The status itself is already stated by the header pill a few
					 * pixels away, so this strip carries only the actionable half of
					 * the message. Repeating "Lisensi belum aktif" in both places read
					 * as two notices saying the same thing.
					 */
					?>
					<span class="apeiron-license-header-alert__body">
						<span class="apeiron-license-header-alert__text"><?php esc_html_e( 'Aktifkan lisensi untuk menjaga fitur Apeiron Kit tetap berjalan.', 'apeiron-kit' ); ?></span>
					</span>
					<a href="<?php echo esc_url( $this->get_tab_url( 'license' ) ); ?>" class="apeiron-license-header-alert__action" data-apeiron-dashboard-tab-link>
						<?php esc_html_e( 'Buka tab Lisensi', 'apeiron-kit' ); ?>
					</a>
					<button type="button" class="apeiron-license-header-alert__close" data-apeiron-license-alert-close aria-label="<?php esc_attr_e( 'Tutup pemberitahuan lisensi', 'apeiron-kit' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
			</div>
		<?php endif; ?>
		<header class="apeiron-dashboard-header">
			<div class="apeiron-dashboard-header__inner">
				<div class="apeiron-brand">
					<div class="apeiron-brand__mark">
						<img src="<?php echo esc_url( APEIRON_KIT_URL . 'assets/img/Logodashboard.png' ); ?>" alt="<?php esc_attr_e( 'Apeiron Kit', 'apeiron-kit' ); ?>">
					</div>
					<div class="apeiron-brand__body">
						<h1><?php esc_html_e( 'Apeiron Kit', 'apeiron-kit' ); ?></h1>
						<p><?php printf( esc_html__( 'Panel admin versi %s', 'apeiron-kit' ), defined( 'APEIRON_KIT_VERSION' ) ? esc_html( APEIRON_KIT_VERSION ) : '1.0.0' ); ?></p>
					</div>
				</div>
				<div class="apeiron-header-actions">
					<a href="<?php echo esc_url( $this->get_tab_url( 'license' ) ); ?>" class="apeiron-license-pill <?php echo $is_license_active ? 'is-active' : 'is-inactive'; ?>" data-apeiron-dashboard-tab-link aria-label="<?php echo esc_attr( $is_license_active ? __( 'Lisensi aktif — buka tab Lisensi', 'apeiron-kit' ) : __( 'Lisensi belum aktif — buka tab Lisensi', 'apeiron-kit' ) ); ?>">
						<span class="dashicons <?php echo $is_license_active ? 'dashicons-yes-alt' : 'dashicons-lock'; ?>"></span>
						<?php echo $is_license_active ? esc_html__( 'Lisensi aktif', 'apeiron-kit' ) : esc_html__( 'Lisensi belum aktif', 'apeiron-kit' ); ?>
					</a>
				</div>
			</div>
		</header>
		<?php
	}

	/**
	 * Render shared admin navigation.
	 */
	private function render_admin_nav( string $active_tab = 'widgets' ): void {
		$groups = [
			[
				'label' => '',
				'items' => [
					'widgets' => [ __( 'Beranda', 'apeiron-kit' ), 'screenoptions' ],
				],
			],
			[
				'label' => __( 'Pengaturan Widget', 'apeiron-kit' ),
				'items' => [
					'ucapan-tamu'  => [ __( 'Ucapan Tamu', 'apeiron-kit' ), 'format-chat' ],
					'cover'        => [ __( 'Sampul', 'apeiron-kit' ), 'format-image' ],
					'social-proof' => [ __( 'Aktivitas', 'apeiron-kit' ), 'megaphone' ],
				],
			],
			[
				'label' => __( 'Pustaka', 'apeiron-kit' ),
				'items' => [
					'stickers' => [ __( 'Galeri Stiker', 'apeiron-kit' ), 'images-alt2' ],
				],
			],
			[
				'label' => __( 'Sistem', 'apeiron-kit' ),
				'items' => [
					'license' => [ __( 'Lisensi', 'apeiron-kit' ), 'awards' ],
				],
			],
		];
		?>
		<aside class="apeiron-dashboard-nav" aria-label="<?php esc_attr_e( 'Navigasi Apeiron Kit', 'apeiron-kit' ); ?>">
			<?php foreach ( $groups as $group ) : ?>
				<?php if ( '' !== $group['label'] ) : ?>
					<span class="apeiron-dashboard-nav__group" aria-hidden="true"><?php echo esc_html( $group['label'] ); ?></span>
				<?php endif; ?>
				<?php foreach ( $group['items'] as $slug => $item ) : ?>
					<a class="apeiron-dashboard-nav__item <?php echo $slug === $active_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( $this->get_tab_url( $slug ) ); ?>" data-apeiron-dashboard-tab-link <?php echo $slug === $active_tab ? 'aria-current="page"' : ''; ?>>
						<span class="dashicons dashicons-<?php echo esc_attr( $item[1] ); ?>"></span>
						<?php echo esc_html( $item[0] ); ?>
					</a>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</aside>
		<?php
	}

	/**
	 * Render selected internal tab content.
	 */
	private function render_active_tab_content( string $active_tab ): void {
		if ( 'widgets' === $active_tab ) {
			$this->general_tab ??= new GeneralTab();
			$this->general_tab->render();
			return;
		}

		if ( 'license' === $active_tab ) {
			$this->license_tab ??= new LicenseTab( $this->license_manager() );
			$this->license_tab->render();
			return;
		}

		if ( 'cover' === $active_tab ) {
			$this->cover_tab ??= new CoverTab();
			$this->cover_tab->render();
			return;
		}

		if ( 'stickers' === $active_tab ) {
			$this->sticker_tab ??= new StickerTab();
			$this->sticker_tab->render();
			return;
		}

		if ( 'social-proof' === $active_tab ) {
			$this->social_proof_tab ??= new SocialProofTab();
			$this->social_proof_tab->render();
			return;
		}

		if ( 'ucapan-tamu' === $active_tab ) {
			$this->ucapan_tamu_tab ??= new UcapanTamuTab();
			$this->ucapan_tamu_tab->render();
			return;
		}

		$this->general_tab ??= new GeneralTab();
		$this->general_tab->render();
	}

	/**
	 * Return a dashboard tab fragment for lightweight in-page navigation.
	 */
	public function handle_load_dashboard_tab(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Anda tidak memiliki izin untuk membuka tab ini.', 'apeiron-kit' ) ],
				403
			);
		}

		check_ajax_referer( 'apeiron_dashboard_tab', 'nonce' );

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'widgets';
		$tab = $this->normalize_dashboard_tab( $tab );

		ob_start();
		$this->render_active_tab_content( $tab );
		$content = ob_get_clean();
		$response = [
			'tab'     => $tab,
			'content' => $content,
		];

		$refresh_header = isset( $_POST['refresh_header'] )
			&& is_string( $_POST['refresh_header'] )
			&& '1' === wp_unslash( $_POST['refresh_header'] );
		if ( $refresh_header ) {
			$license           = $this->license_manager()->get_license();
			$status_display    = $this->license_manager()->get_status_display();
			$is_license_active = ! empty( $license['key'] ) && ! empty( $status_display['is_valid'] );

			ob_start();
			$this->render_admin_header( $is_license_active );
			$response['header'] = ob_get_clean();
		}

		wp_send_json_success( $response );
	}

	private function get_admin_asset_suffix( string $relative_path, string $extension ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return '';
		}

		$base_path   = trailingslashit( APEIRON_KIT_PATH ) . $relative_path;
		$source_path = $base_path . '.' . $extension;
		$min_path    = $base_path . '.min.' . $extension;

		if ( ! file_exists( $min_path ) ) {
			return '';
		}

		if ( file_exists( $source_path ) && filemtime( $source_path ) > filemtime( $min_path ) ) {
			return '';
		}

		return '.min';
	}

	private function get_admin_asset_url( string $relative_path, string $extension ): string {
		$suffix = $this->get_admin_asset_suffix( $relative_path, $extension );

		return trailingslashit( APEIRON_KIT_URL ) . $relative_path . $suffix . '.' . $extension;
	}

	private function get_admin_asset_version( string $relative_path, string $extension ): string {
		$suffix = $this->get_admin_asset_suffix( $relative_path, $extension );
		$path   = trailingslashit( APEIRON_KIT_PATH ) . $relative_path . $suffix . '.' . $extension;

		return file_exists( $path ) ? (string) filemtime( $path ) : APEIRON_KIT_VERSION;
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		
		$allowed_pages = [
			$this->main_slug,
		];

		$is_apeiron_page = $this->is_apeiron_page( $current_page, $hook, $allowed_pages );

		if ( ! $is_apeiron_page ) {
			return;
		}

		$is_main_dashboard = $this->main_slug === $current_page || 'toplevel_page_' . $this->main_slug === $hook;

		wp_enqueue_script( 'jquery' );

		if ( $is_main_dashboard ) {
			wp_enqueue_style(
				'apeiron-kit-admin-panel',
				$this->get_admin_asset_url( 'assets/css/admin-dashboard', 'css' ),
				[],
				$this->get_admin_asset_version( 'assets/css/admin-dashboard', 'css' )
			);

		}

		wp_enqueue_script(
			'apeiron-kit-admin-dashboard',
			$this->get_admin_asset_url( 'assets/js/admin-dashboard', 'js' ),
			[ 'jquery' ],
			$this->get_admin_asset_version( 'assets/js/admin-dashboard', 'js' ),
			true
		);

		wp_enqueue_script(
			'apeiron-kit-admin-widgets',
			$this->get_admin_asset_url( 'assets/js/admin-widgets', 'js' ),
			[ 'jquery' ],
			$this->get_admin_asset_version( 'assets/js/admin-widgets', 'js' ),
			true
		);

		wp_enqueue_script(
			'apeiron-kit-admin-ucapan-tamu',
			$this->get_admin_asset_url( 'assets/js/admin-ucapan-tamu', 'js' ),
			[ 'jquery' ],
			$this->get_admin_asset_version( 'assets/js/admin-ucapan-tamu', 'js' ),
			true
		);

		wp_enqueue_script(
			'apeiron-kit-admin-stickers',
			$this->get_admin_asset_url( 'assets/js/admin-stickers', 'js' ),
			[ 'jquery' ],
			$this->get_admin_asset_version( 'assets/js/admin-stickers', 'js' ),
			true
		);

		wp_enqueue_script(
			'apeiron-kit-admin-social-proof',
			$this->get_admin_asset_url( 'assets/js/admin-social-proof', 'js' ),
			[ 'jquery' ],
			$this->get_admin_asset_version( 'assets/js/admin-social-proof', 'js' ),
			true
		);

		wp_enqueue_script(
			'apeiron-kit-admin-license',
			$this->get_admin_asset_url( 'assets/js/admin-license', 'js' ),
			[ 'jquery' ],
			$this->get_admin_asset_version( 'assets/js/admin-license', 'js' ),
			true
		);
	}

	/**
	 * Check if current page is an Apeiron Kit admin page.
	 *
	 * @param string $current_page Current page slug.
	 * @param string $hook Current hook.
	 * @param array  $allowed_pages Allowed page slugs.
	 * @return bool
	 */
	private function is_apeiron_page( string $current_page, string $hook, array $allowed_pages ): bool {
		// Method 1: Check by GET parameter
		if ( ! empty( $current_page ) && in_array( $current_page, $allowed_pages, true ) ) {
			return true;
		}
		
		// Method 2: Check by hook name
		$allowed_hooks = [
			'toplevel_page_' . $this->main_slug,
		];
		
		if ( in_array( $hook, $allowed_hooks, true ) ) {
			return true;
		}
		
		// Method 3: Check by screen ID
		$current_screen = get_current_screen();
		if ( $current_screen ) {
			$screen_id = $current_screen->id;
			foreach ( $allowed_pages as $page ) {
				if ( strpos( $screen_id, $page ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}
}
