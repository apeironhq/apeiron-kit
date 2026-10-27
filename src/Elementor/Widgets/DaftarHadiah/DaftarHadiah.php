<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\DaftarHadiah;

use ApeironKit\Elementor\Widgets\BaseWidget;
use ApeironKit\Elementor\Widgets\DaftarHadiah\Concerns\RegistersContentControls;
use ApeironKit\Elementor\Widgets\DaftarHadiah\Concerns\RegistersStyleControls;
use ApeironKit\Support\DaftarHadiahCatalog;
use ApeironKit\Support\DaftarHadiahSettings;
use Elementor\Plugin;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DaftarHadiah extends BaseWidget {

	use RegistersContentControls;
	use RegistersStyleControls;

	public function get_name() {
		return 'apeiron-daftar-hadiah';
	}

	public function get_title() {
		return __( 'Daftar Hadiah', 'apeiron-kit' );
	}

	public function get_icon() {
		return 'apeiron-icon-gift';
	}

	public function get_keywords() {
		return [ 'hadiah', 'pernikahan', 'wedding', 'registry', 'gift', 'undangan' ];
	}

	public function get_style_depends() {
		return array_merge( parent::get_style_depends(), [ 'apeiron-kit-daftar-hadiah' ] );
	}

	public function get_script_depends() {
		return [ 'apeiron-kit-daftar-hadiah-js' ];
	}

	protected function register_widget_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
		// Advanced remains the native Elementor tab inherited from Widget_Base.
	}

	protected function render_widget() {
		$settings = (array) $this->get_settings_for_display();
		$document = Plugin::$instance->documents->get_current();
		$identity = RenderContext::identity( $document, $this->get_id(), $this->is_elementor_editor_preview() );
		$gifts    = RenderContext::gifts( $settings );
		$is_editor = $this->is_elementor_editor_preview();
		if ( ! $is_editor ) {
			DaftarHadiahCatalog::remember( $identity, RenderContext::gifts( array_replace( $settings, [ 'items_limit' => 0 ] ) ) );
		}
		$configured = ! $is_editor && $identity['invitation_id'] > 0 && '' !== $identity['registry_id'] && DaftarHadiahSettings::ready();
		$dom_id   = wp_unique_id( 'apeiron-daftar-hadiah-' . $this->get_id() . '-' );
		$config   = [
			'schema_version'        => 1,
			'identity'              => $identity,
			'availability_source'   => 'manual',
			'reservation_status'    => 'not_connected',
			'submission_configured' => $configured,
			'integration' => $configured ? [
				'availability_url' => esc_url_raw( rest_url( 'apeiron-kit/v1/daftar-hadiah/availability' ) ),
				'reserve_url' => esc_url_raw( rest_url( 'apeiron-kit/v1/daftar-hadiah/reserve' ) ),
				'nonce' => wp_create_nonce( DaftarHadiahCatalog::nonce_action( $identity ) ),
				'rest_nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
				'target_token' => DaftarHadiahCatalog::token( $identity ),
			] : [],
			'placeholder_image'     => Utils::get_placeholder_image_src(),
			'gifts'                 => $gifts,
			'messages'              => [
				'notConfigured' => $is_editor ? __( 'Preview Elementor tidak mengirim reservasi. Gunakan halaman undangan untuk mencoba integrasi.', 'apeiron-kit' ) : __( 'Fitur pengiriman reservasi belum dikonfigurasi. Pilihan Anda belum dikirim atau disimpan.', 'apeiron-kit' ),
				'context'       => __( 'Konteks post undangan belum tersedia. Pilihan Anda belum dikirim atau disimpan.', 'apeiron-kit' ),
				'validation'    => __( 'Periksa nama tamu dan nomor WhatsApp yang Anda masukkan.', 'apeiron-kit' ),
				'name'          => __( 'Masukkan nama tamu (maksimal 100 karakter).', 'apeiron-kit' ),
				'whatsapp'      => __( 'Masukkan nomor WhatsApp yang valid, misalnya 081234567890 atau +6281234567890.', 'apeiron-kit' ),
				'message'       => __( 'Pesan maksimal 1000 karakter.', 'apeiron-kit' ),
				'unavailable'   => __( 'Hadiah ini tidak tersedia untuk dipilih.', 'apeiron-kit' ),
				'error'         => $configured ? __( 'Hasil reservasi belum dapat dikonfirmasi. Silakan coba kembali dengan data yang sama.', 'apeiron-kit' ) : __( 'Pilihan belum dikirim. Silakan coba kembali.', 'apeiron-kit' ),
				'loading'       => __( 'Memeriksa pilihan…', 'apeiron-kit' ),
				'photo'         => __( 'Tampilkan foto %d', 'apeiron-kit' ),
				'confirmed'     => __( 'Terkonfirmasi', 'apeiron-kit' ),
				'confirmedMessage' => __( 'Pilihan hadiah berhasil dikonfirmasi.', 'apeiron-kit' ),
				'stockSynced'   => 'api' === DaftarHadiahSettings::get()['provider'] ? __( 'Ketersediaan sudah diperbarui dari reservasi API Daftar Hadiah.', 'apeiron-kit' ) : __( 'Ketersediaan sudah diperbarui dari reservasi GAS.', 'apeiron-kit' ),
			],
		];

		// Khusus editor Elementor: ingatkan bila foto hadiah memakai http:// (dapat diblokir browser di halaman HTTPS dan
		// hanya bisa ditampilkan dashboard bila website dapat diakses publik). Tidak pernah tampil ke tamu.
		if ( $is_editor ) {
			$insecure = RenderContext::insecure_image_count( $gifts );
			if ( $insecure > 0 ) {
				printf(
					'<div class="apeiron-daftar-hadiah-editor-notice" role="note" style="margin:0 0 12px;padding:10px 14px;border:1px solid #f5c26b;border-radius:8px;background:#fff8eb;color:#7a4b00;font-size:13px;line-height:1.5;">%s</div>',
					esc_html( sprintf(
						/* translators: %d: jumlah foto hadiah */
						_n( '%d foto hadiah memakai alamat http:// (bukan https://). Browser dapat memblokirnya di halaman HTTPS, dan dashboard Daftar Hadiah hanya dapat menampilkannya bila website dapat diakses publik. Gunakan HTTPS untuk website ini, lalu unggah ulang foto lewat Media Library.', '%d foto hadiah memakai alamat http:// (bukan https://). Browser dapat memblokirnya di halaman HTTPS, dan dashboard Daftar Hadiah hanya dapat menampilkannya bila website dapat diakses publik. Gunakan HTTPS untuk website ini, lalu unggah ulang foto lewat Media Library.', $insecure, 'apeiron-kit' ),
						$insecure
					) )
				);
			}
		}

		require __DIR__ . '/Partials/daftar-hadiah.php';
	}
}
