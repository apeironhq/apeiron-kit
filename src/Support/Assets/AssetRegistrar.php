<?php

namespace ApeironKit\Support\Assets;

use ApeironKit\Support\WidgetRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AssetRegistrar {

	private bool $registered = false;

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		wp_register_style(
			'apeiron-kit-frontend',
			$this->url( 'assets/css/frontend', 'css' ),
			[],
			$this->version( 'assets/css/frontend', 'css' )
		);

		wp_register_style(
			'apeiron-kit-widget-lock',
			$this->url( 'assets/css/apeiron-widget-lock', 'css' ),
			[],
			$this->version( 'assets/css/apeiron-widget-lock', 'css' )
		);

		$this->register_widget_assets();
		$this->register_cover_assets();
		$this->register_page_loader_preflight();
		$this->localize_widget_assets();

		$this->registered = true;
	}

	public function url( string $relative_path, string $extension ): string {
		$suffix = $this->suffix( $relative_path, $extension );

		return trailingslashit( APEIRON_KIT_URL ) . $relative_path . $suffix . '.' . $extension;
	}

	public function version( string $relative_path, string $extension ): string {
		$suffix = $this->suffix( $relative_path, $extension );
		$path   = trailingslashit( APEIRON_KIT_PATH ) . $relative_path . $suffix . '.' . $extension;

		return file_exists( $path ) ? (string) filemtime( $path ) : APEIRON_KIT_VERSION;
	}

	private function suffix( string $relative_path, string $extension ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return '';
		}

		static $cache = [];
		$cache_key    = $relative_path . '.' . $extension;
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$base_path   = trailingslashit( APEIRON_KIT_PATH ) . $relative_path;
		$source_path = $base_path . '.' . $extension;
		$min_path    = $base_path . '.min.' . $extension;

		if ( ! file_exists( $min_path ) ) {
			return $cache[ $cache_key ] = '';
		}

		if ( file_exists( $source_path ) && filemtime( $source_path ) > filemtime( $min_path ) ) {
			return $cache[ $cache_key ] = '';
		}

		return $cache[ $cache_key ] = '.min';
	}

	private function register_widget_assets(): void {
		foreach ( WidgetRegistry::asset_registrations() as $row ) {
			if ( 'cover' === $row['slug'] ) {
				continue;
			}

			wp_register_style(
				$row['css'],
				$this->url( 'assets/css/' . $row['css_file'], 'css' ),
				[],
				$this->version( 'assets/css/' . $row['css_file'], 'css' )
			);

			wp_register_script(
				$row['js'],
				$this->url( 'assets/js/' . $row['js_file'], 'js' ),
				$row['js_deps'],
				$this->version( 'assets/js/' . $row['js_file'], 'js' ),
				true
			);
		}
	}

	private function register_cover_assets(): void {
		wp_register_style(
			'apeiron-kit-cover-base',
			$this->url( 'assets/css/apeiron-cover-base', 'css' ),
			[],
			$this->version( 'assets/css/apeiron-cover-base', 'css' )
		);

		wp_register_style(
			'apeiron-kit-cover-responsive',
			$this->url( 'assets/css/apeiron-cover-responsive', 'css' ),
			[ 'apeiron-kit-cover' ],
			$this->version( 'assets/css/apeiron-cover-responsive', 'css' )
		);

		wp_register_style(
			'apeiron-kit-cover',
			$this->url( 'assets/css/apeiron-cover', 'css' ),
			[ 'apeiron-kit-cover-base' ],
			$this->version( 'assets/css/apeiron-cover', 'css' )
		);

		wp_register_style(
			'apeiron-kit-cover-classic',
			$this->url( 'assets/css/apeiron-cover-classic', 'css' ),
			[ 'apeiron-kit-cover' ],
			$this->version( 'assets/css/apeiron-cover-classic', 'css' )
		);

		wp_register_script(
			'apeiron-kit-cover-js',
			$this->url( 'assets/js/widgets/cover', 'js' ),
			[],
			$this->version( 'assets/js/widgets/cover', 'js' ),
			true
		);
	}

	private function register_page_loader_preflight(): void {
		wp_register_script(
			'apeiron-kit-page-loader-boot',
			$this->url( 'assets/js/page-loader-boot', 'js' ),
			[],
			$this->version( 'assets/js/page-loader-boot', 'js' ),
			false
		);
	}

	private function localize_widget_assets(): void {
		wp_localize_script(
			'apeiron-kit-comment-dock-js',
			'ApeironKit',
			[
				'restUrl' => get_rest_url( null, 'apeiron-kit/v1' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'             => [
					'editTitle'         => __( 'Edit Komentar', 'apeiron-kit' ),
					'deleteTitle'       => __( 'Hapus Komentar?', 'apeiron-kit' ),
					'deleteDescription' => __( 'Komentar yang dihapus tidak bisa dikembalikan.', 'apeiron-kit' ),
					'confirmDelete'     => __( 'Hapus', 'apeiron-kit' ),
					'save'              => __( 'Simpan', 'apeiron-kit' ),
					'cancel'            => __( 'Batal', 'apeiron-kit' ),
					'sending'           => __( 'Mengirim...', 'apeiron-kit' ),
					'updateError'       => __( 'Gagal memperbarui komentar.', 'apeiron-kit' ),
					'deleteError'       => __( 'Gagal menghapus komentar.', 'apeiron-kit' ),
					'emptyComments'     => __( 'Belum ada ucapan.', 'apeiron-kit' ),
					'paginationError'   => __( 'Gagal memuat halaman ucapan.', 'apeiron-kit' ),
					'loadMoreError'     => __( 'Gagal memuat ucapan lainnya.', 'apeiron-kit' ),
				],
			]
		);

		wp_localize_script(
			'apeiron-kit-guest-invitation-js',
			'ApeironGuestManager',
			[
				'sheetjsUrl' => APEIRON_KIT_URL . 'assets/vendor/xlsx.full.min.js',
			]
		);
	}
}
