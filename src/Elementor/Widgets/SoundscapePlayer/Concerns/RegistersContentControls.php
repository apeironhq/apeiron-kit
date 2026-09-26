<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\SoundscapePlayer\Concerns;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->register_audio_source_controls();
		$this->register_playback_controls();
		$this->register_icon_controls();
		$this->register_status_message_controls();
	}

	private function register_audio_source_controls(): void {
		$this->start_controls_section(
			'section_audio',
			[
				'label' => __( 'Sumber Audio', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'src_type',
			[
				'label'   => __( 'Jenis Sumber', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'upload',
				'options' => [
					'upload'  => __( 'File Audio', 'apeiron-kit' ),
					'link'    => __( 'URL Audio', 'apeiron-kit' ),
					'youtube' => __( 'Video YouTube', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'audio_upload',
			[
				'label'      => __( 'File Audio', 'apeiron-kit' ),
				'type'       => Controls_Manager::MEDIA,
				'media_type' => 'audio',
				'condition'  => [
					'src_type' => 'upload',
				],
			]
		);

		$this->add_control(
			'audio_dynamic_url',
			[
				'label'       => __( 'URL Audio Dinamis', 'apeiron-kit' ),
				'type'        => Controls_Manager::URL,
				'description' => __( 'Opsional. Dynamic Tag yang valid menggantikan File Audio; jika kosong atau tidak valid, File Audio tetap digunakan.', 'apeiron-kit' ),
				'show_external' => false,
				'condition'   => [
					'src_type' => 'upload',
				],
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'audio_link',
			[
				'label'          => __( 'URL Audio', 'apeiron-kit' ),
				'type'           => Controls_Manager::URL,
				'placeholder'    => __( 'https://example.com/music-name.mp3', 'apeiron-kit' ),
				'show_external'  => false,
				'default'        => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
				'condition'      => [
					'src_type' => 'link',
				],
				'dynamic'        => [ 'active' => true ],
			]
		);

		$this->add_control(
			'youtube_link',
			[
				'label'       => __( 'Video YouTube', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'https://youtu.be/Trjrj_fQnIM', 'apeiron-kit' ),
				'default'     => 'https://youtu.be/Trjrj_fQnIM',
				'label_block' => true,
				'condition'   => [
					'src_type' => 'youtube',
				],
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->end_controls_section();
	}

	private function register_playback_controls(): void {
		$this->start_controls_section(
			'section_playback',
			[
				'label' => __( 'Pemutaran', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'start',
			[
				'label'              => __( 'Mulai dari (detik)', 'apeiron-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'description'        => __( 'Kosongkan untuk memulai dari awal audio.', 'apeiron-kit' ),
				'min'                => 0,
				'frontend_available' => true,
				'dynamic'            => [ 'active' => true ],
			]
		);

		$this->add_control(
			'end',
			[
				'label'              => __( 'Berhenti di (detik)', 'apeiron-kit' ),
				'type'               => Controls_Manager::NUMBER,
				'description'        => __( 'Kosongkan untuk memutar sampai selesai. Jika diisi, nilainya harus lebih besar dari Mulai dari.', 'apeiron-kit' ),
				'min'                => 0,
				'frontend_available' => true,
				'dynamic'            => [ 'active' => true ],
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => __( 'Putar Otomatis', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Browser bisa memblokir putar otomatis sampai pengunjung menekan tombol audio.', 'apeiron-kit' ),
				'condition'    => [
					'src_type' => [ 'link', 'upload' ],
				],
			]
		);

		$this->add_control(
			'loop',
			[
				'label'              => __( 'Ulangi Audio', 'apeiron-kit' ),
				'type'               => Controls_Manager::SWITCHER,
				'frontend_available' => true,
				'condition'          => [
					'src_type' => [ 'upload', 'link' ],
				],
			]
		);

		$this->add_control(
			'youtube_playback_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Pemutaran YouTube mengikuti aturan browser dan iframe YouTube. Kontrol putar otomatis dan ulangi tersedia untuk file audio atau URL audio.', 'apeiron-kit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => [
					'src_type' => 'youtube',
				],
			]
		);

		$this->add_control(
			'cover_music_start',
			[
				'label'   => __( 'Mulai Musik Saat', 'apeiron-kit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'cover_opened',
				'options' => [
					'cover_click'  => __( 'Buka Undangan Diklik', 'apeiron-kit' ),
					'cover_opened' => __( 'Sampul Selesai Terbuka', 'apeiron-kit' ),
				],
			]
		);

		$this->add_control(
			'pause_hidden',
			[
				'label'        => __( 'Jeda Saat Tab Disembunyikan', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Matikan opsi ini jika audio boleh tetap berjalan saat pengunjung pindah tab.', 'apeiron-kit' ),
			]
		);

		$this->end_controls_section();
	}

	private function register_icon_controls(): void {
		$this->start_controls_section(
			'section_icons_content',
			[
				'label' => __( 'Ikon', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'pause_icon',
			[
				'label'            => __( 'Ikon Putar', 'apeiron-kit' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'default'          => [
					'value'   => 'fas fa-play',
					'library' => 'fa-solid',
				],
				'separator'        => 'before',
			]
		);

		$this->add_control(
			'play_icon',
			[
				'label'            => __( 'Ikon Jeda', 'apeiron-kit' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'default'          => [
					'value'   => 'fas fa-pause',
					'library' => 'fa-solid',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_status_message_controls(): void {
		$this->start_controls_section(
			'section_status_messages',
			[
				'label' => __( 'Pesan Status', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'empty_message',
			[
				'label'       => __( 'Pesan Audio Kosong', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Pilih audio terlebih dahulu.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'loading_message',
			[
				'label'       => __( 'Pesan Memuat Audio', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Memuat audio...', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'error_message',
			[
				'label'       => __( 'Pesan Audio Gagal', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Audio gagal diputar.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'range_message',
			[
				'label'       => __( 'Pesan Rentang Waktu Salah', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Waktu berhenti harus lebih besar dari waktu mulai.', 'apeiron-kit' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->end_controls_section();
	}
}
