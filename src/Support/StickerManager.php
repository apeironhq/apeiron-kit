<?php

namespace ApeironKit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ApeironKit\Support\ErrorLogger;
use ApeironKit\Elementor\Widgets\CommentDock\StickerLibrary;

/** Kelola unggah dan hapus stiker. */
class StickerManager {

	public function register(): void {
		add_action( 'wp_ajax_apeiron_upload_sticker', [ $this, 'handle_upload' ] );
		add_action( 'wp_ajax_apeiron_delete_sticker', [ $this, 'handle_delete' ] );
	}

	public function handle_upload(): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ErrorLogger::info( 'Sticker upload handler started', [
				'folder' => isset( $_POST['folder'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['folder'] ) ) : 'n/a',
				'has_files' => isset( $_FILES['sticker_files'] ),
			] );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), 'apeiron_sticker_management' ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed. Refresh halaman dan coba lagi.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$folder = isset( $_POST['folder'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['folder'] ) ) : '';
		$allow_video = isset( $_POST['allow_video'] ) && 'yes' === sanitize_text_field( (string) wp_unslash( $_POST['allow_video'] ) );

		$resolved_directory = $this->resolve_sticker_directory( $folder );
		if ( null === $resolved_directory ) {
			wp_send_json_error( [ 'message' => __( 'Path folder stiker tidak valid.', 'apeiron-kit' ) ] );
			return;
		}

		$base_root = $resolved_directory['base'];
		$base_path = $resolved_directory['absolute'];

		if ( ! is_dir( $base_path ) && ! wp_mkdir_p( $base_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Folder stiker tidak dapat dibuat.', 'apeiron-kit' ) ] );
			return;
		}

		$resolved_base_path = realpath( $base_path );
		if ( false === $resolved_base_path || ! $this->is_path_within_base( $resolved_base_path, $base_root ) ) {
			wp_send_json_error( [ 'message' => __( 'Folder stiker tidak valid.', 'apeiron-kit' ) ] );
			return;
		}
		$base_path = wp_normalize_path( $resolved_base_path );

		if ( ! is_writable( $base_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Folder tidak dapat ditulis. Pastikan folder memiliki permission yang tepat.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! isset( $_FILES['sticker_files'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Tidak ada file yang diupload.', 'apeiron-kit' ) ] );
			return;
		}

		$files = $_FILES['sticker_files'];
		$uploaded_count = 0;
		$errors = [];

		$file_count = is_array( $files['name'] ) ? count( $files['name'] ) : 1;

		for ( $i = 0; $i < $file_count; $i++ ) {
			$file_name = is_array( $files['name'] ) ? $files['name'][ $i ] : $files['name'];
			$file_tmp = is_array( $files['tmp_name'] ) ? $files['tmp_name'][ $i ] : $files['tmp_name'];
			$file_error = is_array( $files['error'] ) ? $files['error'][ $i ] : $files['error'];
			$file_size = is_array( $files['size'] ) ? $files['size'][ $i ] : $files['size'];

			if ( $file_error !== UPLOAD_ERR_OK ) {
				$errors[] = sprintf( __( 'Error upload file %s: %s', 'apeiron-kit' ), $file_name, $this->get_upload_error_message( $file_error ) );
				continue;
			}

			$ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
			$allowed_extensions = $allow_video
				? [ 'png', 'jpg', 'jpeg', 'gif', 'webp', 'webm', 'mp4' ]
				: [ 'png', 'jpg', 'jpeg', 'gif', 'webp' ];

			if ( ! in_array( $ext, $allowed_extensions, true ) ) {
				$errors[] = sprintf( __( 'File %s memiliki ekstensi yang tidak diizinkan.', 'apeiron-kit' ), $file_name );
				continue;
			}

			$file_type = wp_check_filetype( $file_name, $allow_video
				? [ 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'webm' => 'video/webm', 'mp4' => 'video/mp4' ]
				: [ 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp' ]
			);

			if ( empty( $file_type['type'] ) ) {
				$errors[] = sprintf( __( 'File %s memiliki tipe MIME yang tidak diizinkan.', 'apeiron-kit' ), $file_name );
				continue;
			}

			$file_info = wp_check_filetype_and_ext( $file_tmp, $file_name, $allow_video
				? [ 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'webm' => 'video/webm', 'mp4' => 'video/mp4' ]
				: [ 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp' ]
			);

			if ( empty( $file_info['type'] ) || $file_info['type'] !== $file_type['type'] ) {
				$errors[] = sprintf( __( 'File %s tidak valid atau corrupt.', 'apeiron-kit' ), $file_name );
				continue;
			}

			if ( in_array( $ext, [ 'png', 'jpg', 'jpeg', 'gif', 'webp' ], true ) ) {
				$image_size = @getimagesize( $file_tmp );
				if ( $image_size === false ) {
					$errors[] = sprintf( __( 'File %s bukan gambar yang valid.', 'apeiron-kit' ), $file_name );
					continue;
				}
			}

			$max_size = 10 * 1024 * 1024;
			if ( $file_size > $max_size ) {
				$errors[] = sprintf( __( 'File %s terlalu besar (maksimal 10MB).', 'apeiron-kit' ), $file_name );
				continue;
			}

			if ( $file_size < 100 ) {
				$errors[] = sprintf( __( 'File %s terlalu kecil atau corrupt.', 'apeiron-kit' ), $file_name );
				continue;
			}

			$sanitized_name = sanitize_file_name( $file_name );
			if ( empty( $sanitized_name ) ) {
				$errors[] = sprintf( __( 'Nama file %s tidak valid.', 'apeiron-kit' ), $file_name );
				continue;
			}

			$target_path = wp_normalize_path( trailingslashit( $base_path ) . $sanitized_name );
			if ( ! $this->is_path_within_base( $target_path, $base_root ) ) {
				$errors[] = sprintf( __( 'Target file %s tidak valid.', 'apeiron-kit' ), $file_name );
				continue;
			}

			if ( move_uploaded_file( $file_tmp, $target_path ) ) {
				$uploaded_count++;
			} else {
				$error_msg = sprintf( __( 'Gagal memindahkan file %s.', 'apeiron-kit' ), $file_name );
				$errors[] = $error_msg;
				ErrorLogger::error( 'Failed to move uploaded sticker file', [
					'file_name' => $file_name,
					'target_path' => $target_path,
					'error' => error_get_last(),
				] );
			}
		}

		if ( $uploaded_count > 0 ) {
			$message = sprintf( 
				_n( 
					'%d stiker berhasil diupload.', 
					'%d stiker berhasil diupload.', 
					$uploaded_count, 
					'apeiron-kit' 
				), 
				$uploaded_count 
			);
			
			if ( ! empty( $errors ) ) {
				$message .= ' ' . __( 'Beberapa file gagal diupload:', 'apeiron-kit' ) . ' ' . implode( ', ', $errors );
			}
			
			wp_send_json_success( [ 'message' => $message, 'uploaded' => $uploaded_count ] );
		} else {
			$error_message = ! empty( $errors ) 
				? implode( ' ', $errors ) 
				: __( 'Gagal mengupload stiker.', 'apeiron-kit' );
			wp_send_json_error( [ 'message' => $error_message ] );
		}
	}

	public function handle_delete(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( (string) wp_unslash( $_POST['nonce'] ), 'apeiron_sticker_management' ) ) {
			wp_send_json_error( [ 'message' => __( 'Nonce verification failed.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Anda tidak memiliki izin untuk melakukan aksi ini.', 'apeiron-kit' ) ] );
			return;
		}

		$sticker_path = isset( $_POST['sticker_path'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['sticker_path'] ) ) : '';

		if ( empty( $sticker_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Path stiker tidak ditemukan.', 'apeiron-kit' ) ] );
			return;
		}

		$allowed_ext = [ 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'webm', 'mp4' ];
		$ext         = strtolower( pathinfo( $sticker_path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_ext, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Ekstensi stiker tidak valid.', 'apeiron-kit' ) ] );
			return;
		}

		$resolved = StickerLibrary::resolve( $sticker_path );
		if ( null === $resolved ) {
			wp_send_json_error( [ 'message' => __( 'Path stiker tidak valid.', 'apeiron-kit' ) ] );
			return;
		}
		$real_file_path = $resolved['absolute'];

		if ( ! file_exists( $real_file_path ) ) {
			wp_send_json_error( [ 'message' => __( 'File stiker tidak ditemukan.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! is_file( $real_file_path ) ) {
			wp_send_json_error( [ 'message' => __( 'File stiker tidak ditemukan.', 'apeiron-kit' ) ] );
			return;
		}

		if ( ! is_writable( $real_file_path ) ) {
			wp_send_json_error( [ 'message' => __( 'File stiker tidak dapat dihapus. Pastikan file memiliki permission yang tepat.', 'apeiron-kit' ) ] );
			return;
		}

		wp_delete_file( $real_file_path );

		if ( ! file_exists( $real_file_path ) ) {
			wp_send_json_success( [ 'message' => __( 'Stiker berhasil dihapus.', 'apeiron-kit' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Gagal menghapus stiker. File mungkin masih ada.', 'apeiron-kit' ) ] );
		}
	}

	/**
	 * Bersihkan elemen SVG berbahaya untuk mencegah XSS.
	 *
	 * @param string $file_path Lokasi absolut berkas SVG.
	 * @return bool Benar jika sanitasi berhasil.
	 */
	public static function sanitize_svg_file( string $file_path ): bool {
		$content = @file_get_contents( $file_path );
		if ( false === $content || empty( $content ) ) {
			return false;
		}

		if ( preg_match( '/<\?php/i', $content ) ) {
			return false;
		}

		$content = preg_replace( '/<\?(?!xml\b)[^?]*\?>/i', '', $content );

		$content = preg_replace( '/<script[^>]*>.*?<\/script>/si', '', $content );
		$content = preg_replace( '/<script[^>]*\/>/si', '', $content );

		$content = preg_replace( '/\s+on\w+\s*=\s*(["\']).*?\1/si', '', $content );
		$content = preg_replace( '/\s+on\w+\s*=\s*[^\s>]+/si', '', $content );

		$content = preg_replace( '/(href|src|xlink:href)\s*=\s*(["\'])\s*(javascript|data)\s*:/si', '$1=$2#blocked:', $content );

		$content = preg_replace( '/<use[^>]+xlink:href\s*=\s*(["\'])https?:\/\/.*?\1[^>]*\/?>/si', '', $content );

		$content = preg_replace( '/<foreignObject[^>]*>.*?<\/foreignObject>/si', '', $content );
		$content = preg_replace( '/<foreignObject[^>]*\/>/si', '', $content );

		$content = preg_replace( '/<(iframe|embed|object)[^>]*>.*?<\/\1>/si', '', $content );
		$content = preg_replace( '/<(iframe|embed|object)[^>]*\/>/si', '', $content );

		$content = preg_replace( '/<(set|animate)[^>]*attributeName\s*=\s*(["\'])on\w+\2[^>]*\/?>/si', '', $content );

		if ( ! preg_match( '/<svg[\s>]/i', $content ) ) {
			return false;
		}

		$written = @file_put_contents( $file_path, $content, LOCK_EX );
		return false !== $written;
	}

	/**
	 * Pastikan direktori stiker tetap berada dalam lokasi yang diizinkan.
	 *
	 * @param string $folder Lokasi folder yang diminta.
	 * @return array<string,string>|null
	 */
	private function resolve_sticker_directory( string $folder ): ?array {
		$relative = ltrim( wp_normalize_path( $folder ), '/\\' );
		if ( '' === $relative ) {
			$relative = 'assets/stickers/gift';
		}
		if ( StickerLibrary::DEFAULT_FOLDER !== $relative ) {
			return null;
		}

		$upload = StickerLibrary::upload_directory();
		if ( null === $upload ) {
			return null;
		}

		return [
			'base'     => $upload['path'],
			'relative' => StickerLibrary::DEFAULT_FOLDER,
			'absolute' => $upload['path'],
		];
	}

	/**
	 * Periksa apakah lokasi berada dalam direktori dasar.
	 *
	 * @param string $path Lokasi yang diperiksa.
	 * @param string $base Direktori dasar.
	 * @return bool
	 */
	private function is_path_within_base( string $path, string $base ): bool {
		$path = wp_normalize_path( $path );
		$base = untrailingslashit( wp_normalize_path( $base ) );
		return $path === $base || 0 === strpos( $path, $base . '/' );
	}

	private function get_upload_error_message( int $error_code ): string {
		switch ( $error_code ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'File terlalu besar.', 'apeiron-kit' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'File hanya terupload sebagian.', 'apeiron-kit' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'Tidak ada file yang diupload.', 'apeiron-kit' );
			case UPLOAD_ERR_NO_TMP_DIR:
				return __( 'Folder temporary tidak ditemukan.', 'apeiron-kit' );
			case UPLOAD_ERR_CANT_WRITE:
				return __( 'Gagal menulis file ke disk.', 'apeiron-kit' );
			case UPLOAD_ERR_EXTENSION:
				return __( 'Upload dihentikan oleh ekstensi.', 'apeiron-kit' );
			default:
				return __( 'Error tidak diketahui.', 'apeiron-kit' );
		}
	}
}
