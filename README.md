<h1 align="center">ApeironKit</h1>

<p align="center">
  <strong>Toolkit Elementor untuk undangan pernikahan digital yang modern dan interaktif.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/WordPress-6.0%2B-21759B?logo=wordpress&amp;logoColor=white" alt="WordPress 6.0 atau lebih baru">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&amp;logoColor=white" alt="PHP 7.4 atau lebih baru">
  <img src="https://img.shields.io/badge/Elementor-3.7.0%2B-92003B" alt="Elementor 3.7.0 atau lebih baru">
  <img src="https://img.shields.io/badge/Widget-14-0F766E" alt="14 widget Elementor">
</p>

<p align="center">
  <a href="https://apeiron.id/">Website</a> ·
  <a href="https://apeiron.id/docs">Dokumentasi</a> ·
  <a href="https://github.com/apeironhq/apeiron-kit/releases">Rilis</a> ·
  <a href="https://lynk.id/apeiron/page/kontak">Kontak</a>
</p>

---

**ApeironKit** adalah ekstensi WordPress untuk Elementor dari **Apeiron.ID**. Bangun pengalaman undangan dalam satu alur: sampul pembuka, musik latar, cerita pasangan, hitung mundur, ucapan dan RSVP, hadiah, hingga tautan personal untuk setiap tamu.

Plugin ini juga menyediakan widget formulir pemesanan dan notifikasi aktivitas untuk kebutuhan layanan undangan digital.

## Daftar isi

- [Fitur utama](#fitur-utama)
- [Seluruh widget](#seluruh-widget)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Mulai menggunakan](#mulai-menggunakan)
- [Dashboard ApeironKit](#dashboard-apeironkit)
- [Paralaks 2.5D dan container native](#paralaks-25d-dan-container-native)
- [Integrasi dan konten dinamis](#integrasi-dan-konten-dinamis)
- [Pembaruan plugin](#pembaruan-plugin)
- [Struktur repositori](#struktur-repositori)
- [Pertanyaan umum](#pertanyaan-umum)
- [Tautan resmi](#tautan-resmi)

## Fitur utama

- **Desain langsung di Elementor** dengan kontrol konten, gaya, dan pengaturan responsif pada kontrol yang mendukungnya.
- **Pengalaman pembuka undangan** melalui Sampul, Pemuat Halaman, dan Tombol Audio.
- **Konten acara dan pasangan** melalui Hitung Mundur, Cerita Cinta, serta Paralaks 2.5D.
- **Interaksi tamu** melalui ucapan, balasan, stiker, konfirmasi kehadiran, dan reservasi hadiah.
- **Undangan personal** dengan nama penerima dari URL serta pengelolaan daftar tamu dan pesan WhatsApp.
- **Formulir dan konversi** melalui Form WhatsApp, Form Order bertahap, dan notifikasi Aktivitas.
- **Pengelolaan terpusat** untuk memilih widget aktif, mengatur integrasi, mengelola stiker, dan mengaktifkan lisensi.

## Seluruh widget

Saat ini tersedia **14 widget utama**. Nama berikut mengikuti label di Elementor dan dashboard ApeironKit.

| Widget | Kegunaan | Tipe Elementor |
| --- | --- | --- |
| **Sampul** | Pembuka undangan dengan animasi panel, pita, kartu penerima, dan tombol buka undangan. | `apeiron-cover` |
| **Pemuat Halaman** | Tampilan Coffee Loader selama proses pemuatan halaman. | `apeiron-page-loader` |
| **Tombol Audio** | Kontrol musik latar dari file audio, URL audio, atau YouTube; dapat dihubungkan dengan pembukaan Sampul. | `apeiron-soundscape` |
| **Hitung Mundur** | Countdown menuju waktu acara dengan tampilan angka dan label yang dapat disesuaikan. | `apeiron-countdown` |
| **Cerita Cinta** | Timeline cerita pasangan bergaya scrapbook, foto polaroid, dan animasi scroll. | `apeiron-cerita-cinta` |
| **Paralaks 2.5D** | Adegan scroll sinematik dengan kamera virtual, kedalaman layer, gambar, video, teks, template, dan container Elementor. | `apeiron-depthscape` |
| **Ucapan Tamu** | Ucapan, balasan, stiker, RSVP, ringkasan kehadiran, dan moderasi komentar. | `apeiron-comment-dock` |
| **Daftar Hadiah** | Katalog hadiah, ketersediaan, dan reservasi hadiah per undangan dengan integrasi API atau Google Apps Script. | `apeiron-daftar-hadiah` |
| **Tombol Salin** | Menyalin rekening, alamat, tautan, atau teks lain dengan umpan balik setelah disalin. | `apeiron-clipboard-tap` |
| **Manajemen Tamu** | Membuat tautan undangan personal, mengolah daftar nama, mengimpor Excel, dan menyiapkan pesan WhatsApp. | `apeiron-guest-invitation-manager` |
| **Form WhatsApp** | Formulir dengan field dan format pesan yang dapat disesuaikan untuk dikirim melalui WhatsApp. | `apeiron-signal-form` |
| **Form Order** | Formulir pemesanan undangan bertahap, dengan tujuan WhatsApp, penyimpanan melalui Form Order API, atau keduanya. | `apeiron-form-order` |
| **Aktivitas** | Notifikasi aktivitas dengan data, foto, pesan, posisi, dan waktu tampil yang dikelola melalui dashboard. | `apeiron-social-proof` |
| **Gulir Otomatis** | Kontrol scroll otomatis, pengaturan kecepatan, dan indikator progres membaca halaman. | `apeiron-autoscroll` |

Daftar registrasi widget tersedia di [`src/Support/WidgetRegistry.php`](src/Support/WidgetRegistry.php).

## Persyaratan

| Komponen | Minimum |
| --- | --- |
| WordPress | 6.0 |
| PHP | 7.4 |
| Elementor | 3.7.0, dalam keadaan aktif |
| Lisensi ApeironKit | Valid untuk menggunakan widget berlisensi |

Untuk **layer container native pada Paralaks 2.5D**, gunakan Elementor yang mendukung Container dan aktifkan fitur Container pada instalasi tersebut.

Penyedia Dynamic Tags, termasuk integrasi ACF, mengikuti kemampuan Elementor dan plugin tambahan yang terpasang.

## Instalasi

Dokumentasi ini mengikuti source pada branch **`main`**. Isi paket pada halaman **Releases** mengikuti versi rilisnya masing-masing.

### Melalui paket ZIP

1. Pasang dan aktifkan **Elementor**.
2. Unduh paket plugin `.zip` yang tersedia di [GitHub Releases](https://github.com/apeironhq/apeiron-kit/releases).
3. Di WordPress, buka **Plugins → Add New Plugin → Upload Plugin**.
4. Unggah paket, pasang, lalu aktifkan **ApeironKit**.
5. Lanjutkan ke menu **ApeironKit → Lisensi** untuk aktivasi.

### Dari source GitHub

Letakkan source di direktori `wp-content/plugins/Apeiron/`, sehingga file utama berada di:

```text
wp-content/plugins/Apeiron/apeiron-kit.php
```

Jika menggunakan Git, jalankan dari direktori `wp-content/plugins/`:

```sh
git clone https://github.com/apeironhq/apeiron-kit.git Apeiron
```

Alternatifnya, unduh **Code → Download ZIP**, ekstrak, lalu beri nama folder plugin **`Apeiron`** sebelum menyalinnya ke direktori plugin WordPress.

Aktifkan plugin melalui halaman **Plugins**. Aset runtime sudah disertakan di repositori, sehingga instalasi dari source tidak memerlukan proses build Node.js atau Composer.

## Mulai menggunakan

1. **Aktifkan lisensi.** Buka **ApeironKit → Lisensi**, lengkapi konfigurasi dan kunci yang diberikan Apeiron.ID, lalu pastikan status lisensi aktif.
2. **Pilih widget.** Buka **ApeironKit → Beranda** dan aktifkan widget yang dibutuhkan.
3. **Buat halaman undangan.** Edit halaman atau post dengan Elementor. Template **Elementor Canvas** dapat digunakan untuk tampilan tanpa header dan footer tema.
4. **Susun pembuka dan konten.** Tambahkan Sampul, Tombol Audio, informasi pasangan, Hitung Mundur, Cerita Cinta, atau Paralaks 2.5D sesuai desain.
5. **Tambahkan interaksi.** Pasang Ucapan Tamu untuk RSVP, Daftar Hadiah untuk reservasi hadiah, serta Tombol Salin untuk rekening atau informasi penting.
6. **Siapkan tautan tamu.** Gunakan Manajemen Tamu untuk membuat tautan personal dan pesan undangan.
7. **Periksa lalu terbitkan.** Uji desktop, tablet, dan mobile, termasuk tombol pembuka, musik, formulir, dan tautan yang digunakan.

### Nama penerima dari URL

Widget Sampul mendukung nama tamu melalui parameter URL. Contoh dengan parameter bawaan `to`:

```text
https://example.com/undangan/?to=Nama%20Tamu
```

Aktifkan **Ambil Nama dari URL** pada pengaturan kartu penerima Sampul. Sesuaikan **Parameter Tamu** apabila menggunakan nama parameter lain.

## Dashboard ApeironKit

| Menu | Pengaturan |
| --- | --- |
| **Beranda** | Katalog widget, pencarian, serta pengaktifan atau penonaktifan widget. |
| **Ucapan Tamu** | Pengaturan global ucapan, kehadiran, dan moderasi. |
| **Form Order** | Tujuan pengiriman pesanan, konfigurasi WhatsApp, dan koneksi Form Order API. |
| **Daftar Hadiah** | Koneksi penyedia API atau Google Apps Script, sinkronisasi katalog, dan pemeriksaan reservasi. |
| **Aktivitas** | Entri notifikasi, foto, format pesan, interval, dan posisi popup. |
| **Galeri Stiker** | Pustaka stiker untuk interaksi pada Ucapan Tamu. |
| **Lisensi** | Konfigurasi API Key, aktivasi License Key, dan status lisensi. |

## Paralaks 2.5D dan container native

Paralaks 2.5D menggunakan **GSAP, ScrollTrigger, dan SplitText** untuk menghubungkan gerakan kamera dan layer dengan scroll halaman. Library tersebut disertakan secara lokal pada aset plugin.

### Alur penyusunan adegan

1. Tambahkan widget **Paralaks 2.5D** ke halaman Elementor.
2. Di **Layer & Konten**, susun gambar, video, teks langsung, template Elementor, bentuk/gradasi, atau container native.
3. Berikan **ID Layer** yang unik untuk setiap layer yang akan dianimasikan.
4. Atur **Adegan & Kamera** untuk menentukan panjang scroll serta posisi kamera di akhir tiap adegan.
5. Gunakan **Animasi Layer** untuk mengatur gerakan, skala, rotasi, opasitas, blur, atau animasi muncul teks.
6. Sesuaikan kehalusan scroll, gerakan berulang, partikel, dan pengaturan lain sesuai kebutuhan desain.
7. Periksa hasil melalui pratinjau Elementor dan halaman frontend.

### Mengedit konten dengan widget Elementor biasa

- Klik **Tambah Layer Container**, lalu **Edit Isi Container**.
- Masukkan Heading, Text Editor, Button, atau widget Elementor lain ke container tersebut.
- Atur padding, margin, tipografi, dan tampilan responsif melalui kontrol Elementor.
- Saat mengedit isi, layer dibuat **diam dan terlihat** agar konten mudah dipilih.
- Klik **Pratinjau Animasi** untuk kembali melihat perilaku animasinya.

Pengaturan **Mode Pratinjau Editor** dapat menahan kamera pada adegan tertentu atau mengembalikannya ke mode interaktif. Pengaturan ini berlaku di editor.

Layer video diputar tanpa suara saat terlihat, mendukung pengulangan, dan dijeda ketika tidak terlihat. Pengaturan reduced-motion perangkat juga diperhitungkan oleh engine.

## Integrasi dan konten dinamis

### Dynamic Tags

Field yang mendukung Dynamic Tags dapat mengambil konten dari sumber dinamis yang tersedia di Elementor. Dukungan sumber seperti ACF bergantung pada integrasi yang terpasang dan jenis field yang digunakan.

### WhatsApp

**Form WhatsApp**, **Form Order**, dan **Manajemen Tamu** dapat menyiapkan pesan dan membuka WhatsApp sesuai alurnya. Pengiriman pesan diselesaikan pengguna melalui WhatsApp.

### Form Order API

Penyimpanan pesanan melalui API memerlukan endpoint **Form Order API** dan Token Website dari konfigurasi API Daftar Hadiah. Endpoint tersebut menggunakan protokol, host, dan port yang sama dengan API Daftar Hadiah. Gunakan pengaturan serta uji koneksi pada dashboard **Form Order** sebelum mengaktifkan tujuan penyimpanan.

### Daftar Hadiah

Pilih penyedia **API** atau **Google Apps Script**, lengkapi konfigurasi koneksi, lalu lakukan uji koneksi dan sinkronisasi katalog melalui dashboard. Layanan penyedia tersebut perlu tersedia agar katalog dan reservasi dapat tersinkron.

### Ucapan dan RSVP

Ucapan menggunakan sistem komentar WordPress, dengan metadata kehadiran dan dukungan stiker. Aktifkan komentar pada halaman atau post undangan dan sesuaikan moderasi melalui pengaturan Ucapan Tamu.

## Pembaruan plugin

ApeironKit memiliki integrasi pembaruan dari **GitHub Releases**. Pembaruan ditawarkan di WordPress ketika tersedia paket rilis yang valid dengan versi lebih tinggi daripada plugin yang terpasang.

Source terbaru pada `main` dapat memuat perubahan yang belum masuk ke paket rilis. Riwayat versi dan paket yang diterbitkan tersedia di [halaman Releases](https://github.com/apeironhq/apeiron-kit/releases).

## Struktur repositori

```text
Apeiron/
├── apeiron-kit.php          # Bootstrap dan metadata plugin
├── assets/
│   ├── css/                 # Style frontend dan editor
│   ├── js/                  # Perilaku frontend dan editor
│   ├── icon/                # Ikon widget
│   ├── img/                 # Gambar dan identitas visual
│   └── vendor/              # Library runtime pihak ketiga
├── src/
│   ├── Admin/               # Dashboard, tab, dan handler admin
│   ├── Core/                # Lifecycle, persyaratan, lisensi, dan updater
│   ├── Elementor/Widgets/   # Implementasi widget Elementor
│   ├── Rest/                # Endpoint REST untuk interaksi frontend
│   ├── Support/             # Registry widget, aset, dan pengaturan
│   └── classmap.php         # Pemetaan class plugin
└── uninstall.php
```

## Pertanyaan umum

### Mengapa widget belum muncul atau tidak dapat digunakan?

Pastikan Elementor aktif dan memenuhi versi minimum, lisensi ApeironKit valid, serta widget yang dicari diaktifkan pada **ApeironKit → Beranda**. Untuk container native, periksa dukungan dan status fitur Container Elementor.

### Mengapa musik belum berputar saat halaman dibuka?

Browser dapat memerlukan interaksi pengguna sebelum memutar audio. Gunakan tombol putar atau hubungkan pemutaran dengan aksi membuka Sampul. Pemutaran dari YouTube juga mengikuti aturan player YouTube.

### Mengapa countdown berbeda dengan waktu acara?

Periksa zona waktu WordPress di **Settings → General**. Hitung Mundur mengikuti zona waktu yang diatur pada situs.

### Mengapa layer paralaks diam ketika mengedit?

Mode edit isi container sengaja menstabilkan layer. Klik **Pratinjau Animasi** untuk kembali ke animasi, dan periksa pilihan **Mode Pratinjau Editor** apabila kamera sedang ditahan pada satu adegan.

## Tautan resmi

- **Website:** [apeiron.id](https://apeiron.id/)
- **Dokumentasi:** [apeiron.id/docs](https://apeiron.id/docs)
- **Template undangan:** [lynk.id/apeiron](https://lynk.id/apeiron)
- **Kontak:** [lynk.id/apeiron/page/kontak](https://lynk.id/apeiron/page/kontak)
- **Source dan rilis:** [github.com/apeironhq/apeiron-kit](https://github.com/apeironhq/apeiron-kit)
