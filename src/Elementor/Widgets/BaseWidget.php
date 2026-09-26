<?php
/**
 * Base Widget Class
 *
 * Abstract base class for all Apeiron Kit Elementor widgets.
 * Provides license validation, widget disabled gate, shared dependencies,
 * and common functionality.
 *
 * @package ApeironKit
 * @since 1.0.0
 */

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ApeironKit\Core\LicenseManager;
use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BaseWidget
 *
 * All Apeiron Kit widgets extend this class to inherit:
 * - Widget disabled gate (Layer 2 render protection)
 * - License validation and lock state rendering
 * - Common asset dependencies
 * - Widget category registration
 *
 * @abstract
 * @since 1.0.0
 */
abstract class BaseWidget extends Widget_Base {

	/**
	 * Static cache of disabled widget slugs.
	 *
	 * Populated once per request via get_option() to avoid
	 * repeated database queries when multiple widgets render.
	 *
	 * @var string[]|null
	 */
	private static ?array $disabled_cache = null;

	/**
	 * License manager instance (lazy loaded).
	 *
	 * @var LicenseManager|null
	 */
	private ?LicenseManager $license_manager = null;

	/**
	 * Get widget categories.
	 *
	 * Register this widget under the 'apeiron-kit' category.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'apeiron-kit' ];
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array Scripts this widget depends on.
	 */
	public function get_script_depends() {
		return [];
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array Stylesheets this widget depends on.
	 */
	public function get_style_depends() {
		return [ 'apeiron-kit-frontend' ];
	}

	/**
	 * Get license manager instance
	 */
	protected function get_license_manager(): LicenseManager {
		if ( null === $this->license_manager ) {
			$this->license_manager = LicenseManager::instance();
		}
		return $this->license_manager;
	}

	/**
	 * Check if license is valid
	 */
	protected function is_license_valid(): bool {
		return $this->get_license_manager()->is_valid();
	}

	/**
	 * Determine whether Elementor is rendering editor UI in either document.
	 *
	 * Elementor's parent editor shell and its frontend preview iframe use
	 * different APIs. Render-time notices must remain visible in both.
	 */
	protected function is_elementor_editor_preview(): bool {
		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;
		$is_editor_shell = isset( $elementor->editor )
			&& method_exists( $elementor->editor, 'is_edit_mode' )
			&& $elementor->editor->is_edit_mode();
		$is_preview_iframe = isset( $elementor->preview )
			&& method_exists( $elementor->preview, 'is_preview_mode' )
			&& $elementor->preview->is_preview_mode();

		return $is_editor_shell || $is_preview_iframe;
	}

	/**
	 * Check if this widget is disabled via the dashboard toggle.
	 *
	 * Layer 2 (Render Gate): Even if Elementor attempts to render
	 * a widget from cached _elementor_data, this method allows the
	 * render() function to bail out and produce no HTML output.
	 *
	 * Uses a static cache so get_option() is called only once per
	 * PHP request, regardless of how many widgets are on the page.
	 *
	 * @return bool True if widget is disabled and should not render.
	 */
	protected function is_widget_disabled(): bool {
		if ( null === self::$disabled_cache ) {
			self::$disabled_cache = WidgetRegistry::disabled_slugs();
		}

		$slug = WidgetRegistry::type_to_slug_map()[ $this->get_name() ] ?? '';

		return $slug && in_array( $slug, self::$disabled_cache, true );
	}

	/**
	 * Register controls with license enforcement.
	 *
	 * The lifecycle method is final so widgets cannot bypass the license gate.
	 * Widget-specific controls belong in register_widget_controls().
	 */
	final protected function register_controls() {
		if ( $this->is_license_valid() ) {
			$this->register_widget_controls();
			return;
		}

		$settings_url = admin_url( 'admin.php?page=apeiron-kit&tab=license' );

		$this->start_controls_section(
			'apeiron_license_locked_section',
			[
				'label' => __( 'Lisensi Apeiron Kit', 'apeiron-kit' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'apeiron_license_locked_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => sprintf(
					'<div style="padding:12px;border:0;border-radius:6px;background:#f8fafc;color:#334155;font-size:12px;line-height:1.5"><strong style="display:block;margin-bottom:4px;color:#0f172a;font-weight:500">%s</strong><span>%s</span><br><a href="%s" target="_blank" style="display:inline-block;margin-top:8px;color:#083c57;font-weight:500;text-decoration:none">%s</a></div>',
					esc_html__( 'Widget terkunci', 'apeiron-kit' ),
					esc_html__( 'Aktifkan lisensi untuk membuka pengaturan widget ini.', 'apeiron-kit' ),
					esc_url( $settings_url ),
					esc_html__( 'Buka halaman lisensi', 'apeiron-kit' )
				),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register widget-specific controls after the license gate passes.
	 */
	abstract protected function register_widget_controls();

	/**
	 * Render widget with disabled gate and license check.
	 *
	 * 1. Layer 2 — Disabled gate: if widget is toggled off, output nothing.
	 * 2. License gate: if license is invalid, show locked message.
	 * 3. Render widget-specific output through render_widget().
	 */
	final protected function render() {
		// Layer 2: Render Gate — block output for disabled widgets.
		if ( $this->is_widget_disabled() ) {
			return;
		}

		if ( ! $this->is_license_valid() ) {
			$this->render_locked_widget();
			return;
		}

		$this->render_widget();
	}

	/**
	 * Render widget-specific output after all shared gates pass.
	 */
	abstract protected function render_widget();

	/**
	 * Render locked widget message.
	 *
	 * Loads shared CSS (`apeiron-kit-widget-lock`), then
	 * includes the presentation-only partial. No inline `<style>` blocks are
	 * emitted — all styling lives in `assets/css/apeiron-widget-lock.css`.
	 */
	protected function render_locked_widget(): void {
		$is_editor = $this->is_elementor_editor_preview();
		if ( ! $is_editor ) {
			return;
		}

		wp_enqueue_style( 'apeiron-kit-widget-lock' );

		$settings_url = admin_url( 'admin.php?page=apeiron-kit&tab=license' );
		require __DIR__ . '/Partials/locked-widget.php';
	}

	/**
	 * Block JS-rendered editor preview for disabled widgets.
	 *
	 * Defense-in-depth: since disabled widgets are not registered
	 * (Layer 1), Elementor should never call this method for them.
	 * But if editor cache somehow loads a disabled widget, this
	 * ensures no JavaScript template is provided.
	 */
	protected function content_template() {
		// Intentionally empty — all Apeiron Kit widgets use PHP render().
	}
}
