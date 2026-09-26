<?php

declare( strict_types=1 );

namespace ApeironKit\Elementor\Widgets\GuestInvitationManager\Concerns;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait RegistersContentControls {

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_header_content',
			[
				'label' => __( 'Umum', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'show_header',
			[
				'label'        => __( 'Tampilkan Judul', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Tampil', 'apeiron-kit' ),
				'label_off'    => __( 'Sembunyi', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'after',
			]
		);

		$this->add_control(
			'header_icon',
			[
				'label' => __( 'Ikon', 'apeiron-kit' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-user-plus',
					'library' => 'solid',
				],
				'condition' => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_control(
			'header_title',
			[
				'label' => __( 'Judul', 'apeiron-kit' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [ 'active' => true ],
				'default' => __( 'Kelola Tamu', 'apeiron-kit' ),
				'label_block' => true,
				'condition' => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_control(
			'header_description',
			[
				'label' => __( 'Deskripsi', 'apeiron-kit' ),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [ 'active' => true ],
				'default' => __( 'Buat daftar tamu dan bagikan undangan personal.', 'apeiron-kit' ),
				'rows' => 2,
				'condition' => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_guest_list',
			[
				'label'        => __( 'Tampilkan Hasil', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Tampil', 'apeiron-kit' ),
				'label_off'    => __( 'Sembunyi', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'description'  => __( 'Tampilkan daftar hasil dan tombol bagikan setelah data tamu dibuat.', 'apeiron-kit' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_general_settings',
			[
				'label' => __( 'Teks', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'heading_form_texts',
			[
				'label' => __( 'Formulir', 'apeiron-kit' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'invitation_link_label',
			[
				'label'       => __( 'Label Link Undangan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Link Undangan', 'apeiron-kit' ),
				'label_block' => true,
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'invitation_link_hint',
			[
				'label'       => __( 'Bantuan Link Undangan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( '* Masukkan link undangan Anda. Contoh: apeiron.id/ika-budi atau https://apeiron.id/ika-budi', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'invitation_link_placeholder',
			[
				'label'       => __( 'Teks Contoh Link', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Contoh: apeiron.id/ika-budi', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'guest_names_label',
			[
				'label'       => __( 'Label Nama Tamu', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Nama Tamu', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'guest_names_hint',
			[
				'label'       => __( 'Bantuan Nama Tamu', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( '* Satu tamu per baris. Opsional: Nama | Nomor WhatsApp.', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'guest_names_placeholder',
			[
				'label'       => __( 'Teks Contoh Nama', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Nama Tamu | 628123456789', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'template_label',
			[
				'label'       => __( 'Label Template Ucapan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Pilih Template Ucapan', 'apeiron-kit' ),
				'label_block' => true,
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'message_template_placeholder',
			[
				'label'       => __( 'Teks Contoh Ucapan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Template ucapan akan muncul di sini...', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'create_button_label',
			[
				'label'       => __( 'Label Tombol Buat Daftar', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Buat Daftar', 'apeiron-kit' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'heading_table_texts',
			[
				'label'     => __( 'Tabel Hasil', 'apeiron-kit' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_guest_list' => 'yes',
				],
			]
		);

		$this->add_control(
			'guest_list_title',
			[
				'label'       => __( 'Judul Daftar Tamu', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Daftar Tamu', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'show_guest_list' => 'yes',
				],
			]
		);

		$this->add_control(
			'table_label_no',
			[
				'label'     => __( 'Label Kolom No', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'default'   => __( 'No', 'apeiron-kit' ),
				'condition' => [
					'show_guest_list' => 'yes',
				],
			]
		);

		$this->add_control(
			'table_label_guest',
			[
				'label'     => __( 'Label Kolom Nama', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'default'   => __( 'Nama Tamu', 'apeiron-kit' ),
				'condition' => [
					'show_guest_list' => 'yes',
				],
			]
		);

		$this->add_control(
			'table_label_option',
			[
				'label'     => __( 'Label Kolom Aksi', 'apeiron-kit' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [ 'active' => true ],
				'default'   => __( 'Aksi', 'apeiron-kit' ),
				'condition' => [
					'show_guest_list' => 'yes',
				],
			]
		);

		$this->add_control(
			'guest_list_empty_text',
			[
				'label'       => __( 'Data Kosong', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Belum ada tamu. Buat daftar terlebih dahulu.', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'show_guest_list' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_general',
			[
				'label' => __( 'Template Ucapan', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'info_description',
			[
				'raw'             => __( '<b>Template token:</b> Gunakan <b>[nama]</b> untuk nama tamu dan <b>[link-undangan]</b> untuk link personal. Link frontend otomatis menambahkan parameter <b>?to=nama-tamu</b>. Untuk baris baru, gunakan <b>\n</b> di isi template.', 'apeiron-kit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'render_type'     => 'ui',
				'type'            => Controls_Manager::RAW_HTML,
			]
		);

		$repeater_templates = new Repeater();
		$repeater_templates->add_control(
			'item_label',
			[
				'label'       => __( 'Nama Template', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Template 1', 'apeiron-kit' ),
				'label_block' => true,
			]
		);
		$repeater_templates->add_control(
			'item_message',
			[
				'label'       => __( 'Template Ucapan', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'dynamic'     => [ 'active' => true ],
				'rows'        => '6',
				'default'     => '',
				'label_block' => true,
			]
		);
		$this->add_control(
			'templates',
			[
				'label'       => __( 'Template Ucapan', 'apeiron-kit' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater_templates->get_controls(),
				'default'     => [
					[
						'item_label'    => __( 'Formal', 'apeiron-kit' ),
						'item_message'  => 'Tanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i *[nama]* untuk menghadiri acara kami.\n\n*Berikut link undangan kami*, untuk info lengkap dari acara bisa kunjungi :\n\n[link-undangan]\n\nMerupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.\n\n*Mohon maaf perihal undangan hanya di bagikan melalui pesan ini.*\n\nDan agar selalu menjaga kesehatan bersama serta datang pada waktu yang telah ditentukan.\n\nTerima kasih banyak atas perhatiannya.',
					],
					[
						'item_label'    => __( 'Muslim', 'apeiron-kit' ),
						'item_message'  => '_Assalamualaikum Warahmatullahi Wabarakatuh_\n\nTanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i *[nama]* untuk menghadiri acara kami.\n\n*Berikut link undangan kami*, untuk info lengkap dari acara bisa kunjungi :\n\n[link-undangan]\n\nMerupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.\n\n*Mohon maaf perihal undangan hanya di bagikan melalui pesan ini.*\n\nDan agar selalu menjaga kesehatan bersama serta datang pada waktu yang telah ditentukan.\n\nTerima kasih banyak atas perhatiannya.\n\n_Wassalamualaikum Warahmatullahi Wabarakatuh_',
					],
					[
						'item_label'    => __( 'Nasrani', 'apeiron-kit' ),
						'item_message'  => 'Kepada Yth.\n*[nama]*\n\nSalam Sejahtera Bagi Kita Semua. Tuhan membuat segala sesuatu indah pada waktunya dan mempersatukan kami dalam suatu ikatan pernikahan kudus, semoga Tuhan memberkati dalam mengiringi pernikahan kami.\n\nTanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i: untuk menghadiri acara kami.\n\nBerikut link undangan kami:\n\n[link-undangan]\n\nMerupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.\n\n*Mohon maaf perihal undangan hanya di bagikan melalui pesan ini.*\n\nDan agar selalu menjaga kesehatan bersama serta datang pada waktu yang telah ditentukan. Semoga kita semua diberikan kesehatan dan tetap dibawah lindungan-Nya.\n\nTerima kasih.',
					],
					[
						'item_label'    => __( 'Hindu', 'apeiron-kit' ),
						'item_message'  => 'Kepada Yth.\n*[nama]*\n\nOm Swastiastu\n\nTanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i, teman sekaligus sahabat, untuk menghadiri acara pernikahan kami :\n\nBerikut link undangan kami untuk info lengkap dari acara bisa kunjungi :\n\n[link-undangan]\n\nMerupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.\n\nMohon maaf perihal undangan hanya di bagikan melalui pesan ini. Dan agar selalu menjaga kesehatan bersama serta datang pada waktu yang telah ditentukan. Terima kasih banyak atas perhatiannya.\n\nOm Shanti, Shanti, Shanti, Om.',
					],
				],
				'title_field' => '{{{ item_label }}}',
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'section_import',
			[
				'label' => __( 'Impor Tamu', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'enable_excel_import',
			[
				'label'        => __( 'Aktifkan Impor', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Aktif', 'apeiron-kit' ),
				'label_off'    => __( 'Mati', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Aktifkan upload file Excel untuk mengisi nama tamu lebih cepat.', 'apeiron-kit' ),
			]
		);

		$this->add_control(
			'excel_import_mode',
			[
				'label'     => __( 'Mode Impor', 'apeiron-kit' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'append',
				'options'   => [
					'append'  => __( 'Tambahkan ke daftar', 'apeiron-kit' ),
					'replace' => __( 'Ganti daftar saat ini', 'apeiron-kit' ),
				],
				'condition' => [
					'enable_excel_import' => 'yes',
				],
			]
		);

		$this->add_control(
			'skip_excel_header',
			[
				'label'        => __( 'Lewati Baris Judul', 'apeiron-kit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'apeiron-kit' ),
				'label_off'    => __( 'Tidak', 'apeiron-kit' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'enable_excel_import' => 'yes',
				],
			]
		);

		$this->add_control(
			'import_section_label',
			[
				'label'       => __( 'Judul Impor', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Impor dari Excel', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'enable_excel_import' => 'yes',
				],
			]
		);

		$this->add_control(
			'process_import_label',
			[
				'label'       => __( 'Tombol Impor', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Impor Excel', 'apeiron-kit' ),
				'label_block' => true,
				'condition'   => [
					'enable_excel_import' => 'yes',
				],
			]
		);

		$this->add_control(
			'download_template_link',
			[
				'label'       => __( 'Link Template', 'apeiron-kit' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [ 'active' => true ],
				'placeholder' => 'https://example.com/template.xlsx',
				'default'     => [
					'url' => '',
				],
				'label_block' => true,
				'separator'   => 'before',
				'description' => __( 'Masukkan URL file template Excel untuk diunduh oleh pengguna. Bisa berupa link eksternal atau file media.', 'apeiron-kit' ),
				'condition'   => [
					'enable_excel_import' => 'yes',
				],
			]
		);

		$this->add_control(
			'download_template_label',
			[
				'label'       => __( 'Tombol Unduh', 'apeiron-kit' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [ 'active' => true ],
				'default'     => __( 'Unduh Template', 'apeiron-kit' ),
				'label_block' => true,
				'separator'   => 'before',
				'condition'   => [
					'enable_excel_import' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}
}
