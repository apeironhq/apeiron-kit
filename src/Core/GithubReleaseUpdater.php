<?php

namespace ApeironKit\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GithubReleaseUpdater {
	private const REPOSITORY = 'apeironhq/apeiron-kit';
	private const RELEASE_API = 'https://api.github.com/repos/apeironhq/apeiron-kit/releases/latest';
	private const CACHE_KEY = 'apeiron_kit_github_release';

	public function register(): void {
		add_filter( 'update_plugins_github.com', [ $this, 'check_update' ], 10, 4 );
		add_filter( 'plugins_api', [ $this, 'plugin_information' ], 10, 3 );
		add_filter( 'upgrader_source_selection', [ $this, 'check_package_structure' ], 10, 4 );
	}

	public function check_update( $update, $plugin_data, $plugin_file, $locales ) {
		if ( plugin_basename( APEIRON_KIT_FILE ) !== $plugin_file || ! is_array( $plugin_data ) ) {
			return $update;
		}

		$installed = $plugin_data['Version'] ?? '';
		if ( ! is_string( $installed ) || ! preg_match( '/^\d+\.\d+\.\d+$/', $installed ) ) {
			return false;
		}

		$release = $this->get_release();
		if ( ! $release || ! version_compare( $release['version'], $installed, '>' ) ) {
			return false;
		}

		return [
			'slug'        => dirname( $plugin_file ),
			'version'     => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
			'requires_php' => $plugin_data['RequiresPHP'] ?? '',
		];
	}

	public function plugin_information( $result, $action, $args ) {
		if ( false !== $result || 'plugin_information' !== $action || ! is_object( $args )
			|| ( $args->slug ?? '' ) !== dirname( plugin_basename( APEIRON_KIT_FILE ) ) ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}
		$requirements = get_file_data( APEIRON_KIT_FILE, [
			'wp'  => 'Requires at least',
			'php' => 'Requires PHP',
		] );

		return (object) [
			'name'          => 'ApeironKit',
			'slug'          => dirname( plugin_basename( APEIRON_KIT_FILE ) ),
			'version'       => $release['version'],
			'author'        => 'Apeiron.ID',
			'homepage'      => 'https://github.com/' . self::REPOSITORY,
			'external'      => true,
			'download_link' => $release['package'],
			'requires'      => $requirements['wp'],
			'requires_php'  => $requirements['php'],
			'sections'      => [
				'changelog' => nl2br( esc_html( $release['notes'] ) ),
			],
		];
	}

	public function check_package_structure( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( ! is_array( $hook_extra ) || ( $hook_extra['plugin'] ?? '' ) !== plugin_basename( APEIRON_KIT_FILE )
			|| ( $hook_extra['type'] ?? '' ) !== 'plugin' || ( $hook_extra['action'] ?? '' ) !== 'update'
			|| is_wp_error( $source ) ) {
			return $source;
		}

		global $wp_filesystem;
		$plugin_file = basename( APEIRON_KIT_FILE );
		$expected_dir = dirname( plugin_basename( APEIRON_KIT_FILE ) );
		$path = is_string( $source ) ? trailingslashit( $source ) . $plugin_file : '';
		$updates = get_site_transient( 'update_plugins' );
		$offer = is_object( $updates ) ? ( $updates->response[ $hook_extra['plugin'] ] ?? null ) : null;
		$contents = $path && $wp_filesystem && $wp_filesystem->is_file( $path )
			? $wp_filesystem->get_contents( $path ) : false;

		if ( ! is_string( $source ) || basename( untrailingslashit( $source ) ) !== $expected_dir
			|| ! is_object( $offer ) || ! is_string( $contents )
			|| ! preg_match( '/^[ \t\/*#@]*Version:\s*(\d+\.\d+\.\d+)\s*$/mi', $contents, $match )
			|| ! isset( $offer->new_version ) || $match[1] !== $offer->new_version ) {
			return new \WP_Error( 'apeiron_invalid_release_package', 'ApeironKit release ZIP must contain Apeiron/apeiron-kit.php with the matching release version.' );
		}

		return $source;
	}

	private function get_release(): ?array {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached['release'] ?? null;
		}

		$response = wp_safe_remote_get( self::RELEASE_API, [
			'timeout' => 5,
			'redirection' => 2,
			'headers' => [
				'Accept' => 'application/vnd.github+json',
				'User-Agent' => 'ApeironKit/' . APEIRON_KIT_VERSION,
			],
		] );
		$release = null;
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			$release = $this->parse_release( $data );
		}

		set_site_transient( self::CACHE_KEY, [ 'release' => $release ], $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		return $release;
	}

	private function parse_release( $data ): ?array {
		if ( ! is_array( $data ) || ( $data['draft'] ?? true ) !== false
			|| ( $data['prerelease'] ?? true ) !== false || ! is_string( $data['tag_name'] ?? null )
			|| ! preg_match( '/^v?(\d+\.\d+\.\d+)$/', $data['tag_name'], $match )
			|| ! is_array( $data['assets'] ?? null ) ) {
			return null;
		}

		$tag = $data['tag_name'];
		$packages = [];
		foreach ( $data['assets'] as $asset ) {
			if ( ! is_array( $asset ) || ! is_string( $asset['name'] ?? null )
				|| ! preg_match( '/^[a-zA-Z0-9._-]+\.zip$/i', $asset['name'] ) ) {
				continue;
			}

			$url = $asset['browser_download_url'] ?? null;
			$parts = is_string( $url ) ? wp_parse_url( $url ) : false;
			$expected_path = '/' . self::REPOSITORY . '/releases/download/' . $tag . '/' . $asset['name'];
			if ( ! is_array( $parts ) || ( $parts['scheme'] ?? '' ) !== 'https'
				|| ( $parts['host'] ?? '' ) !== 'github.com' || ( $parts['path'] ?? '' ) !== $expected_path
				|| isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] )
				|| isset( $parts['query'] ) || isset( $parts['fragment'] )
				|| ! is_numeric( $asset['size'] ?? null ) || (int) $asset['size'] < 1 ) {
				return null;
			}

			$packages[] = $url;
		}

		if ( 1 !== count( $packages ) ) {
			return null;
		}

		return [
			'version' => $match[1],
			'url' => 'https://github.com/' . self::REPOSITORY . '/releases/tag/' . $tag,
			'package' => $packages[0],
			'notes' => is_string( $data['body'] ?? null ) ? $data['body'] : '',
		];
	}
}
