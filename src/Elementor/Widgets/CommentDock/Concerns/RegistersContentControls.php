<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\CommentDock\Concerns;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Control IDs are persisted and must not be renamed. */
trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_general',
			[
				'label' => __('Judul', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'show_heading',
			[
				'label' => __('Tampilkan Judul', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'heading',
			[
				'label' => __('Teks Judul', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Ucapan & Doa', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'show_heading' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_form_content',
			[
				'label' => __('Form', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'form_text_heading',
			[
				'label' => __('Teks Form', 'apeiron-kit'),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'name_label',
			[
				'label' => __('Label Nama', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Nama Lengkap', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'name_placeholder',
			[
				'label' => __('Placeholder Nama', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Isikan nama lengkap', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'message_label',
			[
				'label' => __('Label Ucapan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Ucapan', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'message_placeholder',
			[
				'label' => __('Placeholder Ucapan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Tulis ucapan terbaikmu...', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'enable_guest_name_from_url',
			[
				'label' => __('Kunci ke Link Tamu', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => '',
				'description' => __('Jika aktif, tamu hanya bisa mengirim dari link dengan ?to=NamaTamu.', 'apeiron-kit'),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'guest_required_message',
			[
				'label' => __('Teks Catatan Tamu', 'apeiron-kit'),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => ['active' => true],
				'default' => __('* Ucapan hanya dapat dikirim oleh tamu yang menerima undangan ini.', 'apeiron-kit'),
				'description' => __('Teks ini tampil saat fitur batas nama tamu aktif tetapi parameter nama tamu tidak ditemukan.', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'enable_guest_name_from_url' => 'yes',
				],
			]
		);

		$this->add_control(
			'form_feedback_heading',
			[
				'label' => __('Kirim dan Pesan', 'apeiron-kit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_text',
			[
				'label' => __('Teks Tombol', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Kirim Ucapan', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'success_message',
			[
				'label' => __('Pesan Berhasil', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Terima kasih atas ucapannya!', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'error_message',
			[
				'label' => __('Teks Gagal', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Gagal mengirim komentar.', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_sticker_content',
			[
				'label' => __('Stiker', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'show_stickers',
			[
				'label' => __('Aktifkan Stiker', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'sticker_label',
			[
				'label' => __('Label Stiker', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Stiker', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'show_stickers' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_display_mode',
			[
				'label' => __('Cara Pilih Stiker', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'popup',
				'options' => [
					'popup' => __('Tombol Popup', 'apeiron-kit'),
					'inline' => __('Langsung Tampil di Form', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_popup_position',
			[
				'label' => __('Posisi Popup Stiker', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => [
					'center' => __('Tengah', 'apeiron-kit'),
					'bottom' => __('Bawah', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
				],
			]
		);

		$this->add_control(
			'sticker_popup_background_scroll',
			[
				'label' => __('Scroll Halaman Saat Popup Stiker Terbuka', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'no',
				'options' => [
					'no' => __('Kunci Halaman', 'apeiron-kit'),
					'yes' => __('Izinkan Scroll Halaman', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
				],
			]
		);

		$this->add_control(
			'sticker_popup_scroll_area',
			[
				'label' => __('Area Scroll Popup Stiker', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'picker',
				'options' => [
					'picker' => __('Daftar Stiker', 'apeiron-kit'),
					'panel' => __('Seluruh Panel', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
				],
			]
		);

		$this->add_control(
			'sticker_media_type',
			[
				'label' => __('Jenis Stiker', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => [
					'all' => __('Gambar & Video', 'apeiron-kit'),
					'image' => __('Gambar saja', 'apeiron-kit'),
					'video' => __('Video saja', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_advanced_content',
			[
				'label' => __('Opsi Lanjutan', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => '',
				'separator' => 'before',
				'condition' => [
					'show_stickers' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_trigger_label',
			[
				'label' => __('Tombol Kirim', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Pilih Stiker', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
					'sticker_advanced_content' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_trigger_icon_text',
			[
				'label' => __('Ikon Tombol', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'default' => '+',
				'label_block' => false,
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
					'sticker_advanced_content' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_popup_animation',
			[
				'label' => __('Animasi Popup', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'fade-scale',
				'options' => [
					'fade-scale' => __('Fade + Scale', 'apeiron-kit'),
					'slide-up' => __('Slide Up', 'apeiron-kit'),
					'slide-down' => __('Slide Down', 'apeiron-kit'),
					'fade' => __('Fade', 'apeiron-kit'),
					'none' => __('Tanpa Animasi', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
					'sticker_advanced_content' => 'yes',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sticker_popup_close_animation',
			[
				'label' => __('Animasi Tutup Popup', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'same',
				'options' => [
					'same' => __('Ikuti Animasi Muncul', 'apeiron-kit'),
					'fade-scale' => __('Fade + Scale', 'apeiron-kit'),
					'slide-up' => __('Slide Up', 'apeiron-kit'),
					'slide-down' => __('Slide Down', 'apeiron-kit'),
					'fade' => __('Fade', 'apeiron-kit'),
					'none' => __('Tanpa Animasi', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_display_mode' => 'popup',
					'sticker_advanced_content' => 'yes',
				],
			]
		);

		$this->add_control(
			'sticker_comment_position',
			[
				'label' => __('Posisi di Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'avatar',
				'options' => [
					'avatar' => __('Ganti avatar', 'apeiron-kit'),
					'top' => __('Atas komentar', 'apeiron-kit'),
					'above_text' => __('Atas teks', 'apeiron-kit'),
					'below_text' => __('Bawah teks', 'apeiron-kit'),
					'beside_text' => __('Samping teks', 'apeiron-kit'),
				],
				'condition' => [
					'show_stickers' => 'yes',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sticker_avatar_note',
			[
				'raw' => __('Catatan: "Ganti avatar" butuh Avatar aktif. Jika Avatar dimatikan, stiker tampil di atas teks.', 'apeiron-kit'),
				'type' => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition' => [
					'show_stickers' => 'yes',
					'sticker_comment_position' => 'avatar',
				],
			]
		);

		// Legacy controls remain for saved instances; remove only with data migration.
		$this->add_control(
			'sticker_allow_image',
			[
				'type' => Controls_Manager::HIDDEN,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'sticker_allow_video',
			[
				'type' => Controls_Manager::HIDDEN,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'sticker_note',
			[
				'raw' => __('Stiker dibaca otomatis dari folder. Kelola di <a href="' . admin_url('admin.php?page=apeiron-kit&tab=stickers') . '" target="_blank">Apeiron Kit > Stiker</a>.', 'apeiron-kit'),
				'type' => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition' => [
					'show_stickers' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_attendance',
			[
				'label' => __('Kehadiran', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'attendence',
			[
				'label' => __('Tampilkan Kehadiran', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'attendance_label',
			[
				'label' => __('Label Kehadiran', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Kehadiran', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'attendence' => 'yes',
				],
			]
		);

		$this->add_control(
			'attendance_placeholder',
			[
				'label' => __('Placeholder Pilihan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Pilih salah satu', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'attendence' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_attendance_summary',
			[
				'label' => __('Status Kehadiran', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Tampilkan total komentar berdasarkan pilihan kehadiran.', 'apeiron-kit'),
				'condition' => [
					'attendence' => 'yes',
				],
			]
		);

		$this->add_control(
			'attendance_summary_position',
			[
				'label' => __('Posisi Status', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'after_submit',
				'options' => [
					'after_submit' => __('Di bawah Kirim Ucapan', 'apeiron-kit'),
					'after_heading' => __('Di bawah Judul Ucapan & Doa', 'apeiron-kit'),
				],
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$this->add_control(
			'attendance_summary_title',
			[
				'label' => __('Judul Status', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Kehadiran', 'apeiron-kit'),
				'label_block' => true,
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_attendance_summary_title',
			[
				'label' => __('Tampilkan Judul', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_attendance_summary_count',
			[
				'label' => __('Tampilkan Angka', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_attendance_summary_label',
			[
				'label' => __('Tampilkan Label Opsi', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_attendance_summary_title_line',
			[
				'label' => __('Garis Pemisah Judul', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => '',
				'description' => __('Tampilkan garis tipis di kiri dan kanan judul ringkasan kehadiran.', 'apeiron-kit'),
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
					'show_attendance_summary_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'attendance_summary_item_sizing',
			[
				'label' => __('Ukuran Kartu Item', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'fit_equal',
				'options' => [
					'fit_equal' => __('Pas Sama (Rekomendasi)', 'apeiron-kit'),
					'equal' => __('Sama (96px)', 'apeiron-kit'),
					'auto' => __('Otomatis (Mengikuti Isi)', 'apeiron-kit'),
				],
				'description' => __('Mengatur lebar setiap kartu item pada ringkasan kehadiran.', 'apeiron-kit'),
				'condition' => [
					'attendence' => 'yes',
					'show_attendance_summary' => 'yes',
				],
			]
		);

		$attendance = new Repeater();
		$attendance->add_control(
			'option_label',
			[
				'label' => __('Teks Opsi', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'default' => __('Hadir', 'apeiron-kit'),
				'label_block' => true,
				'dynamic' => ['active' => true],
			]
		);
		// Keep static: REST validates this persisted key against saved element data.
		// Dynamic Tags could reject or orphan values when their output changes.
		$attendance->add_control(
			'option_slug',
			[
				'label' => __('Kode Opsi', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'description' => __('Opsional. Jika kosong, dibuat otomatis.', 'apeiron-kit'),
			]
		);
		$attendance->add_control(
			'option_icon',
			[
				'label' => __('Ikon', 'apeiron-kit'),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-check',
					'library' => 'fa-solid',
				],
			]
		);


		$this->add_control(
			'attendance_options',
			[
				'label' => __('Pilihan Kehadiran', 'apeiron-kit'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $attendance->get_controls(),
				'title_field' => '{{{ option_label }}}',
				'default' => [
					[
						'option_label' => __('Hadir', 'apeiron-kit'),
						'option_slug' => 'hadir',
						'option_icon' => [
							'value' => 'fas fa-check',
							'library' => 'fa-solid',
						],
					],
					[
						'option_label' => __('Tidak Hadir', 'apeiron-kit'),
						'option_slug' => 'tidak-hadir',
						'option_icon' => [
							'value' => 'fas fa-times',
							'library' => 'fa-solid',
						],
					],
					[
						'option_label' => __('Masih Ragu', 'apeiron-kit'),
						'option_slug' => 'ragu',
						'option_icon' => [
							'value' => 'far fa-meh',
							'library' => 'fa-regular',
						],
					],
				],
				'condition' => [
					'attendence' => 'yes',
				],
			]
		);

		$this->add_control(
			'attendance_display',
			[
				'label' => __('Tampilan Status di Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'icon',
				'options' => [
					'icon' => __('Ikon saja', 'apeiron-kit'),
					'icon-label' => __('Ikon & teks', 'apeiron-kit'),
				],
				'condition' => [
					'attendence' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_comment_meta',
			[
				'label' => __('Tampilan Komentar', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'show_avatar',
			[
				'label' => __('Tampilkan Avatar', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_date',
			[
				'label' => __('Tampilkan Tanggal', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'date_format_type',
			[
				'label' => __('Format Waktu', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'relative',
				'options' => [
					'relative' => __('Relatif (1 menit yang lalu)', 'apeiron-kit'),
					'custom' => __('Tanggal Lengkap (7 Desember 2025)', 'apeiron-kit'),
					'default' => __('Default WordPress', 'apeiron-kit'),
				],
				'condition' => [
					'show_date' => 'yes',
				],
			]
		);

		$this->add_control(
			'default_avatar',
			[
				'label' => __('Avatar Default', 'apeiron-kit'),
				'type' => Controls_Manager::MEDIA,
				'dynamic' => ['active' => true],
				'condition' => [
					'show_avatar' => 'yes',
				],
			]
		);


		$this->end_controls_section();

		$this->start_controls_section(
			'section_comment_list',
			[
				'label' => __('Komentar Tampil', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'comment_list_mode',
			[
				'label' => __('Mode Tampilan', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto' => __('Otomatis saat penuh', 'apeiron-kit'),
					'scroll' => __('Scroll tetap', 'apeiron-kit'),
					'pagination' => __('Paginasi', 'apeiron-kit'),
				],
				'description' => __('Pilih cara menampilkan komentar saat jumlahnya banyak.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'comments_limit',
			[
				'label' => __('Scroll Setelah', 'apeiron-kit'),
				'type' => Controls_Manager::NUMBER,
				'default' => 10,
				'min' => 1,
				'max' => 200,
				'description' => __('Pada mode otomatis, daftar berubah menjadi scroll setelah jumlah komentar melewati angka ini.', 'apeiron-kit'),
				'condition' => [
					'comment_list_mode' => ['', 'auto'],
				],
			]
		);

		$this->add_control(
			'scrollable_height',
			[
				'label' => __('Tinggi Scroll', 'apeiron-kit'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 200,
						'max' => 800,
					],
				],
				'default' => [
					'size' => 400,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-list-scrollable' => 'max-height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'comment_list_mode' => ['', 'auto'],
				],
			]
		);

		$this->add_control(
			'list_scroll_height',
			[
				'label' => __('Tinggi Scroll Tetap', 'apeiron-kit'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 120,
						'max' => 600,
					],
				],
				'default' => [
					'size' => 320,
					'unit' => 'px',
				],
				'condition' => [
					'comment_list_mode' => 'scroll',
				],
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-comment-list.is-scrollable' => 'max-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'items_per_page',
			[
				'label' => __('Per Halaman', 'apeiron-kit'),
				'type' => Controls_Manager::NUMBER,
				'default' => 5,
				'min' => 1,
				'max' => 50,
				'condition' => [
					'comment_list_mode' => 'pagination',
				],
			]
		);

		$this->add_control(
			'pagination_labels_heading',
			[
				'label' => __('Label Navigasi', 'apeiron-kit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'comment_list_mode' => 'pagination',
				],
			]
		);

		$this->add_control(
			'prev_text',
			[
				'label' => __('Tombol Sebelumnya', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Sebelumnya', 'apeiron-kit'),
				'condition' => [
					'comment_list_mode' => 'pagination',
				],
			]
		);

		$this->add_control(
			'next_text',
			[
				'label' => __('Tombol Berikutnya', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Berikutnya', 'apeiron-kit'),
				'condition' => [
					'comment_list_mode' => 'pagination',
				],
			]
		);

		// Legacy list controls remain for saved instances; remove only with data migration.
		$this->add_control(
			'use_pagination',
			[
				'type' => Controls_Manager::HIDDEN,
				'default' => 'no',
			]
		);

		$this->add_control(
			'list_display_mode',
			[
				'type' => Controls_Manager::HIDDEN,
				'default' => 'auto',
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'section_reply_form_content',
			[
				'label' => __('Balasan', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'reply_form_text_heading',
			[
				'label' => __('Teks Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'reply_action_text',
			[
				'label' => __('Teks Aksi Balas', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Balas', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_popup_title',
			[
				'label' => __('Judul Popup Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Balas Komentar', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_name_placeholder',
			[
				'label' => __('Placeholder Nama', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Nama Lengkap', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_message_placeholder',
			[
				'label' => __('Placeholder Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => ['active' => true],
				'default' => __('Tulis balasan...', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_submit_text',
			[
				'label' => __('Tombol Kirim', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Kirim Balasan', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_cancel_text',
			[
				'label' => __('Tombol Batal', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Batal', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_success_message',
			[
				'label' => __('Pesan Berhasil Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Balasan dikirim.', 'apeiron-kit'),
				'label_block' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'reply_error_message',
			[
				'label' => __('Pesan Gagal Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::TEXT,
				'dynamic' => ['active' => true],
				'default' => __('Gagal mengirim balasan.', 'apeiron-kit'),
				'label_block' => true,
			]
		);

		$this->add_control(
			'reply_sticker_heading',
			[
				'label' => __('Stiker Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'enable_reply_sticker',
			[
				'label' => __('Stiker di Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Balasan memakai stiker yang sama.', 'apeiron-kit'),
				'condition' => [
					'show_stickers' => 'yes',
				],
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'section_comment_permissions',
			[
				'label' => __('Izin Pengunjung', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'enable_comment_reply',
			[
				'label' => __('Izinkan Balas Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Izinkan semua pengunjung membalas komentar.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'enable_comment_edit',
			[
				'label' => __('Izinkan Edit Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Pemilik komentar dapat mengedit komentarnya sendiri.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'enable_comment_delete',
			[
				'label' => __('Izinkan Hapus Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Pemilik komentar dapat menghapus komentarnya sendiri.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'enable_reply_edit',
			[
				'label' => __('Izinkan Edit Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Pemilik balasan dapat mengedit balasannya sendiri.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'enable_reply_delete',
			[
				'label' => __('Izinkan Hapus Balasan', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Pemilik balasan dapat menghapus balasannya sendiri.', 'apeiron-kit'),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_advanced_content',
			[
				'label' => __('Lanjutan', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'show_form_labels',
			[
				'label' => __('Tampilkan Label Form', 'apeiron-kit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Ya', 'apeiron-kit'),
				'label_off' => __('Tidak', 'apeiron-kit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => __('Sembunyikan label jika ingin form terlihat lebih ringkas dan hanya memakai placeholder.', 'apeiron-kit'),
				'selectors' => [
					'{{WRAPPER}} .apeiron-kit-form-label' => 'display: {{VALUE}};',
				],
				'selectors_dictionary' => [
					'yes' => 'block',
					'' => 'none',
				],
			]
		);

		$this->add_control(
			'notice_position',
			[
				'label' => __('Posisi Pesan', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'after_button',
				'options' => [
					'after_button' => __('Di bawah tombol', 'apeiron-kit'),
					'before_comments' => __('Di atas komentar', 'apeiron-kit'),
					'toast' => __('Toast mengambang', 'apeiron-kit'),
				],
				'description' => __('Lokasi tampilnya pesan berhasil/gagal setelah tamu mengirim ucapan.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'comment_edit_mode',
			[
				'label' => __('Mode Edit Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'popup',
				'options' => [
					'popup' => __('Popup', 'apeiron-kit'),
					'inline' => __('Di bawah komentar', 'apeiron-kit'),
				],
				'description' => __('Popup membuat form edit lebih lega, terutama untuk reply bertingkat.', 'apeiron-kit'),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'comment_reply_mode',
			[
				'label' => __('Mode Balas Komentar', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'popup',
				'options' => [
					'popup' => __('Popup', 'apeiron-kit'),
					'inline' => __('Di bawah komentar', 'apeiron-kit'),
				],
				'description' => __('Popup menjaga area komentar tetap rapi dan memberi ruang lebih lega untuk menulis balasan.', 'apeiron-kit'),
			]
		);

		$this->add_control(
			'modal_position',
			[
				'label' => __('Posisi Popup Balasan dan Edit', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => [
					'center' => __('Tengah', 'apeiron-kit'),
					'bottom' => __('Bawah', 'apeiron-kit'),
				],
			]
		);

		$this->add_control(
			'modal_background_scroll',
			[
				'label' => __('Scroll Halaman Saat Popup Balasan/Edit Terbuka', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'no',
				'options' => [
					'no' => __('Kunci Halaman', 'apeiron-kit'),
					'yes' => __('Izinkan Scroll Halaman', 'apeiron-kit'),
				],
			]
		);

		$this->add_control(
			'modal_scroll_area',
			[
				'label' => __('Area Scroll Popup Balasan/Edit', 'apeiron-kit'),
				'type' => Controls_Manager::SELECT,
				'default' => 'overlay',
				'options' => [
					'overlay' => __('Overlay', 'apeiron-kit'),
					'dialog' => __('Dalam Dialog', 'apeiron-kit'),
				],
			]
		);

		$this->end_controls_section();
	}
}
