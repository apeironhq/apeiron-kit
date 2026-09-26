<?php
/**
 * Plugin Name: ApeironKit
 * Description: Ekstensi Elementor untuk membuat undangan pernikahan digital yang modern dan profesional.
 * Version:     1.0.7
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Requires Plugins: elementor
 * Author:      Apeiron.ID
 * Author URI:  https://apeiron.id/
 * Text Domain: apeiron-kit
 * Update URI:  https://github.com/apeironhq/apeiron-kit
 */

if (!defined('ABSPATH')) {
	exit;
}

define('APEIRON_KIT_FILE', __FILE__);
define('APEIRON_KIT_PATH', plugin_dir_path(__FILE__));
define('APEIRON_KIT_URL', plugin_dir_url(__FILE__));
define('APEIRON_KIT_VERSION', '1.0.7');

// License API URL - dapat di-override via filter 'apeiron_kit_license_api_url'
// Default: endpoint produksi Apeiron
if (!defined('APEIRON_KIT_LICENSE_API_URL')) {
	define('APEIRON_KIT_LICENSE_API_URL', 'https://server-apeiron.web.id/api');
}

// API Key - dapat di-override via filter 'apeiron_kit_license_api_key'
// Default: empty (dapat diisi via halaman Settings > Lisensi)
if (!defined('APEIRON_KIT_LICENSE_API_KEY')) {
	define('APEIRON_KIT_LICENSE_API_KEY', '');
}

$apeiron_classmap_file = APEIRON_KIT_PATH . 'src/classmap.php';
$apeiron_classmap = is_readable($apeiron_classmap_file) ? require $apeiron_classmap_file : [];
$apeiron_classmap = is_array($apeiron_classmap) ? $apeiron_classmap : [];

spl_autoload_register(
	static function ($class) use ($apeiron_classmap) {
		if (strpos($class, 'ApeironKit\\') !== 0) {
			return;
		}

		if (isset($apeiron_classmap[$class])) {
			$file = APEIRON_KIT_PATH . 'src/' . $apeiron_classmap[$class];
		} else {
			$relative = str_replace('ApeironKit\\', '', $class);
			$relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
			$file = APEIRON_KIT_PATH . 'src/' . $relative . '.php';
		}

		if (is_readable($file)) {
			require_once $file;
		}
	}
);

unset($apeiron_classmap_file, $apeiron_classmap);

register_activation_hook(
	__FILE__,
	static function () {
		$requirements = new \ApeironKit\Core\Requirements();

		if (!$requirements->passes()) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die(
				'<h1>' . esc_html__('Aktivasi Apeiron Kit Gagal', 'apeiron-kit') . '</h1>' .
				'<p>' . esc_html__('Pastikan semua dependensi (PHP, WordPress, dan Elementor) memenuhi versi minimum.', 'apeiron-kit') . '</p>',
				'Apeiron Kit Requirements',
				['back_link' => true]
			);
		}

		\ApeironKit\Core\Activator::activate();

		// Auto-fix: if saved URL option is empty, populate from constant
		$saved_url = get_option('apeiron_kit_license_api_url', '');
		if (empty($saved_url) && defined('APEIRON_KIT_LICENSE_API_URL') && !empty(APEIRON_KIT_LICENSE_API_URL)) {
			update_option('apeiron_kit_license_api_url', APEIRON_KIT_LICENSE_API_URL, false);
			$saved_url = APEIRON_KIT_LICENSE_API_URL;
		}

		$api_url = !empty($saved_url) ? $saved_url : (defined('APEIRON_KIT_LICENSE_API_URL') ? APEIRON_KIT_LICENSE_API_URL : '');
		$api_url = esc_url_raw(untrailingslashit($api_url));

		if ('https' !== wp_parse_url($api_url, PHP_URL_SCHEME) || empty(wp_parse_url($api_url, PHP_URL_HOST))) {
			$api_url = '';
		}

		$api_key_available = (defined('APEIRON_KIT_LICENSE_API_KEY') && !empty(APEIRON_KIT_LICENSE_API_KEY));
		if (!$api_key_available) {
			$api_key_manager = new \ApeironKit\Core\ApiKeyManager();
			$api_key_available = $api_key_manager->has_api_key();
		}

		if (empty($api_url) || !$api_key_available) {
			set_transient(
				'apeiron_kit_activation_notice',
				esc_html__('Konfigurasi license server belum lengkap. Buka Apeiron Kit > tab Lisensi untuk menambahkan License Server URL dan API Key.', 'apeiron-kit'),
				DAY_IN_SECONDS
			);
		}
	}
);

// Tampilkan informasi pasca aktivasi (sekali tampil)
add_action(
	'admin_notices',
	static function () {
		if (!current_user_can('manage_options')) {
			return;
		}

		$message = get_transient('apeiron_kit_activation_notice');
		if (!$message) {
			return;
		}

		delete_transient('apeiron_kit_activation_notice');

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html($message)
		);
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		\ApeironKit\Core\Deactivator::deactivate();
	}
);

// Plugin action links (tampil di bawah nama plugin pada halaman Plugins)
add_filter(
	'plugin_action_links_' . plugin_basename(__FILE__),
	static function (array $actions): array {
		if (!current_user_can('manage_options')) {
			return $actions;
		}

		$license_url = admin_url('admin.php?page=apeiron-kit&tab=license');
		$is_license_active = false;

		try {
			$is_license_active = \ApeironKit\Core\LicenseManager::instance()->is_valid();
		} catch (\Throwable $e) {
			$is_license_active = false;
		}

		$license_label = $is_license_active
			? __('Lisensi', 'apeiron-kit')
			: __('Aktifkan lisensi', 'apeiron-kit');

		$license_link = sprintf(
			'<a href="%1$s" style="color:#00a32a;font-weight:400;">%2$s</a>',
			esc_url($license_url),
			esc_html($license_label)
		);

		array_unshift($actions, $license_link);

		return $actions;
	}
);

// Plugin row meta links (tampil di halaman Plugins)
add_filter(
	'plugin_row_meta',
	static function (array $plugin_meta, string $plugin_file): array {
		if (plugin_basename(__FILE__) !== $plugin_file) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="https://apeiron.id/docs" target="_blank" rel="noopener noreferrer">Dokumentasi</a>';
		$plugin_meta[] = '<a href="https://lynk.id/apeiron" target="_blank" rel="noopener noreferrer">Template Undangan</a>';
		$plugin_meta[] = '<a href="https://lynk.id/apeiron/page/kontak" target="_blank" rel="noopener noreferrer">Kontak</a>';

		return $plugin_meta;
	},
	10,
	2
);

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain('apeiron-kit', false, dirname(plugin_basename(__FILE__)) . '/languages');

		( new \ApeironKit\Core\GithubReleaseUpdater() )->register();

		$plugin = new \ApeironKit\Core\Plugin();
		$plugin->boot();
	}
);
