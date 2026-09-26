<?php
/**
 * Locked widget partial (editor + frontend fallback).
 *
 * Rendered by BaseWidget::render_locked_widget() when the license is invalid.
 * All CSS lives in assets/css/apeiron-widget-lock.css — this file must contain
 * markup only.
 *
 * @package ApeironKit
 * @since   1.1.0
 *
 * @var string $settings_url Fully-qualified URL to the license activation page.
 * @var bool   $is_editor    True in the Elementor shell or preview iframe.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $is_editor ) {
	?>
	<div class="apeiron-widget-locked-frontend apeiron-widget-locked-frontend--hidden" aria-hidden="true"></div>
	<?php
	return;
}
?>
<div class="apeiron-widget-locked-frontend" role="status">
	<div class="apeiron-locked-frontend-icon" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
			<circle cx="15.5" cy="8.5" r="1.5"></circle>
			<path d="M12 2a6.5 6.5 0 0 1 6.5 6.5c0 1.63-.6 3.12-1.59 4.26l-.41.44-5.5 5.5a2.5 2.5 0 0 1-2.7.54l-.3-.14a2.5 2.5 0 0 1-.54-.39l-.7-.7a2.5 2.5 0 0 1 0-3.54l.7-.7-.7-.7a2.5 2.5 0 0 1 0-3.54l.35-.35L9 8.5A6.5 6.5 0 0 1 12 2z"></path>
		</svg>
	</div>
	<h3><?php esc_html_e( 'Widget Terkunci', 'apeiron-kit' ); ?></h3>
	<p><?php esc_html_e( 'Aktifkan Lisensi Untuk Menggunakan Widget Ini', 'apeiron-kit' ); ?></p>
	<a href="<?php echo esc_url( $settings_url ); ?>" class="apeiron-locked-activate-btn" target="_blank" rel="noopener noreferrer">
		<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
			<path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
		</svg>
		<?php esc_html_e( 'Aktifkan Lisensi', 'apeiron-kit' ); ?>
	</a>
</div>
