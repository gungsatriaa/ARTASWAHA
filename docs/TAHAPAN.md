# Tahapan Pengembangan ArtaSwaha

Dokumen ini membagi pengembangan menjadi tahapan yang dapat diselesaikan dan diuji secara lokal. Cakupannya berakhir pada aplikasi yang berjalan dan dapat diperiksa di lingkungan lokal; tahapan deployment tidak termasuk.

## Tahap 1 — Fondasi dan keputusan produk

Status: **sebagian selesai**.

### Pekerjaan

- [x] Pastikan versi Laravel dan PHP yang terpasang.
- [ ] Catat dan verifikasi versi MySQL, Node/Vite, serta paket utama dalam dokumen proyek.
- [x] Tetapkan ruang lingkup materi: Paribasa Bali dan Aksara Bali; bahasa antarmuka Indonesia; sasaran umum anak-anak.
- [ ] Tetapkan rentang usia sasaran secara spesifik dan materi pertama untuk prototipe.
- [ ] Validasi isi Bahasa Bali/Aksara Bali/cecimpedan bersama penutur atau pengajar yang kompeten.
- [ ] Siapkan serta jalankan lingkungan lokal dengan MySQL dan Redis.
- [x] Pastikan `.env` diabaikan Git.

### Selesai bila

- Aplikasi dapat dijalankan lokal dengan konfigurasi development.
- Versi runtime dan dependensi utama terdokumentasi.
- Materi untuk prototipe pertama telah dipilih dan ditinjau.

## Tahap 2 — Sistem desain dan kerangka UI

Status: **sebagian selesai**.

### Pekerjaan

- [x] Bangun landing page Blade dengan navigasi fixed dan layout yang memiliki aturan responsif.
- [x] Terapkan palet gelap, radius, dan token tema Tailwind sesuai `STYLE.md`.
- [x] Konfigurasikan Nunito, Atkinson Hyperlegible, dan Quicksand sebagai font UI serta Vimala lokal untuk Aksara Bali.
- [ ] Buat komponen dasar reusable untuk tombol, kartu, progres, dialog, dan pesan status.
- [ ] Tuntaskan audit keyboard, fokus, kontras, reduced motion, serta kontrol suara.
- [x] Gunakan aset maskot untuk identitas/favikon tanpa menjadikannya isi utama hero.
- [x] Pastikan proses build aset frontend berhasil.

### Pengujian lokal

- [ ] Periksa halaman pada lebar ponsel dan desktop di browser lokal.
- [ ] Verifikasi navigasi keyboard, kontras, teks panjang, dan fallback saat font gagal.

### Selesai bila

- Halaman dasar responsif dan komponen memiliki perilaku serta gaya konsisten.

## Tahap 3 — Materi belajar dan panel admin

Status: **belum dimulai**.

### Pekerjaan

- Rancang model dan relasi untuk jalur, unit, pelajaran, soal, pilihan jawaban, media, dan status publikasi.
- Buat migration, model, factory/seed data lokal, validasi, dan indeks query penting.
- Buat pengelolaan materi khusus admin: buat, ubah, urutkan, tinjau, terbitkan, dan arsipkan.
- Simpan audio/gambar dengan validasi tipe dan ukuran; gunakan aset contoh lokal.
- Pastikan konten yang belum ditinjau tidak tampil sebagai materi terbit.

### Pengujian lokal

- Jalankan migration dan seeder pada database lokal kosong.
- Periksa validasi, otorisasi admin, pengurutan materi, serta kasus data kosong.

### Selesai bila

- Admin dapat menyiapkan dan menerbitkan materi yang tampil pada jalur belajar.

## Tahap 4 — Belajar mandiri anonim

### Pekerjaan

- Sediakan masuk langsung ke materi tanpa membuat akun.
- Buat latihan awal yang sederhana, misalnya pilihan ganda dan dengarkan-lalu-pilih.
- Berikan umpan balik, audio, animasi singkat, dan kontrol untuk mematikan suara.
- Simpan progres dengan identifier anonim berbasis browser/perangkat; jangan memakai nama asli.
- Tampilkan pelajaran terakhir, progres, dan latihan ulang.
- Rancang alur pengaitan progres anonim ke akun tanpa menghapus atau menggandakan progres.

### Pengujian lokal

- Pastikan progres bertahan setelah refresh dan kunjungan berikutnya pada browser yang sama.
- Periksa perilaku ketika cookie dihapus, identifier tidak valid, progres sudah ada di akun, dan jaringan lambat.

### Selesai bila

- Pengunjung dapat menyelesaikan pelajaran dan melanjutkan progres anonim di perangkat/browser yang sama.

## Tahap 5 — Akun dan perpindahan progres

### Pekerjaan

- Tambahkan registrasi, login, logout, pemulihan akses, dan validasi akun memakai pola Laravel yang sesuai versi terpasang.
- Minimalkan data yang diminta dan gunakan nama tampilan/avatar untuk aktivitas bersama.
- Gabungkan progres anonim ke akun saat registrasi/login dengan aturan duplikasi yang jelas.
- Terapkan rate limit, perlindungan sesi, dan otorisasi untuk halaman akun.

### Pengujian lokal

- Uji alur autentikasi valid/tidak valid, pembatasan percobaan, logout, serta penggabungan progres.
- Verifikasi progres milik satu akun tidak dapat dibaca akun lain.

### Selesai bila

- Pengguna dapat masuk dan progres anonim tetap terhubung secara konsisten.

## Tahap 6 — Kuis mandiri dan bank soal

### Pekerjaan

- Sediakan bank soal yang ditinjau admin dan dapat dikelompokkan berdasarkan topik/kesulitan.
- Implementasikan kuis mandiri dengan pilihan ganda, timer opsional, skor server-side, dan ringkasan hasil.
- Catat jawaban dan percobaan secukupnya untuk mendukung latihan ulang.
- Tampilkan penjelasan atau materi rujukan setelah menjawab bila tersedia.

### Pengujian lokal

- Uji batas waktu, jawaban ganda, pengiriman ulang, soal tanpa pilihan, dan manipulasi skor dari browser.
- Periksa ringkasan hasil dan latihan ulang.

### Selesai bila

- Pengguna dapat mengerjakan kuis sampai selesai dan hasil dihitung server secara benar.

## Tahap 7 — Membuat kuis dan room

### Pekerjaan

- Izinkan pengguna terdaftar membuat kuis dari bank soal yang tersedia.
- Izinkan pembuat membuat room, memilih kuis/pengaturan, dan membagikan kode atau tautan.
- Izinkan pengguna terdaftar bergabung dengan kode room.
- Terapkan status host dan peserta pada keanggotaan room, bukan role global.
- Tambahkan aturan room: status sesi, kapasitas bila dibutuhkan, kode invalid/kedaluwarsa, host keluar, dan peserta keluar.
- Sediakan kontrol host untuk mulai dan mengakhiri sesi.

### Pengujian lokal

- Uji pembuatan room, kode salah, join berulang, host/non-host, room sudah dimulai/selesai, dan akses tanpa izin.
- Pastikan kode tidak mudah ditebak dan aksi room selalu diverifikasi server-side.

### Selesai bila

- Host dapat membuat room dan peserta yang sah dapat bergabung serta mengikuti sesi.

## Tahap 8 — Realtime dengan Reverb dan Redis

### Pekerjaan

- Cocokkan versi Laravel dan paket sebelum memasang Reverb, Echo, atau dependensi tambahan.
- Jalankan Reverb lokal dan hubungkan browser melalui Echo.
- Siarkan perubahan yang diperlukan saja: peserta bergabung, sesi mulai, soal aktif, jawaban masuk, dan leaderboard.
- Gunakan channel privat/presence yang diotorisasi berdasarkan room.
- Tetapkan server sebagai sumber kebenaran untuk timer, jawaban, skor, dan hasil.
- Gunakan Redis lokal untuk queue/cache dan scaling pub/sub hanya bila dibutuhkan; jangan jadikan Redis penyimpanan permanen progres.
- Tangani reconnect, event duplikat, pesan terlambat, dan fallback ketika WebSocket terputus.

### Pengujian lokal

- Jalankan MySQL, Redis, queue worker bila diperlukan, Reverb, dan aplikasi pada lingkungan development lokal.
- Uji beberapa browser/session lokal bergabung ke room yang sama dan pastikan status serta skor konsisten.
- Uji reconnect, koneksi terputus, akses channel lintas room, dan pengiriman event berulang.

### Selesai bila

- Beberapa peserta lokal dapat menyelesaikan satu kuis bersama dan melihat pembaruan status/peringkat secara realtime.

## Tahap 9 — Peringkat, hasil, dan pengalaman kelas

### Pekerjaan

- Tampilkan papan peringkat sementara dan hasil akhir dengan nama tampilan/avatar.
- Terapkan aturan skor yang konsisten dan komunikasikan peran ketepatan/kecepatan.
- Izinkan host mematikan peringkat untuk sesi yang tidak kompetitif.
- Tampilkan ringkasan jawaban/topik yang perlu diulang tanpa mempermalukan peserta.
- Sediakan hasil yang dapat dilihat ulang oleh pengguna yang berhak.

### Pengujian lokal

- Uji seri, jawaban terlambat, peserta disconnect, leaderboard nonaktif, dan hasil setelah sesi berakhir.

### Selesai bila

- Skor dan ringkasan hasil konsisten antara server, host, dan peserta.

## Tahap 10 — Kualitas, aksesibilitas, dan keamanan

### Pekerjaan

- Tinjau validasi, policy/gate, CSRF, rate limit, session, escaping Blade, upload, channel realtime, dan data pribadi sesuai `SECURITY.md`.
- Audit query halaman utama dan room; cegah N+1, batasi kolom, pagination, dan tambahkan indeks berdasarkan query nyata.
- Pastikan antarmuka usable dengan keyboard, reduced motion, suara mati, layar kecil, dan koneksi lambat.
- Periksa bahwa tidak ada rahasia atau data sensitif di repository, log development, cache publik, atau broadcast yang salah sasaran.
- Rapikan error state, empty state, loading state, dan retry yang dibutuhkan.

### Pengujian lokal

- Jalankan test otomatis yang relevan secara lokal dan pemeriksaan manual di browser lokal.
- Jalankan formatter/linter yang sudah dikonfigurasi proyek.
- Gunakan data dummy; jangan gunakan data nyata anak.
- Catat bug yang diketahui dan pastikan tidak ada isu pemblokir pada alur utama.

### Selesai bila

- Alur anonim, akun, materi, kuis, room, dan realtime lolos pemeriksaan lokal dan tidak memiliki isu kritis yang diketahui.

## Tahap 11 — Uji coba lokal dan validasi materi

### Pekerjaan

- Jalankan skenario lengkap dari pengunjung anonim hingga sesi kuis bersama pada komputer lokal.
- Minta penutur/pengajar lokal meninjau isi, ejaan, pelafalan, konteks, dan kesesuaian budaya.
- Amati apakah anak dapat memahami navigasi dan instruksi tanpa bantuan berlebihan.
- Perbaiki temuan, lalu ulangi skenario terdampak.
- Siapkan daftar fitur yang masuk versi pertama dan daftar yang sengaja ditunda.

### Selesai bila

- Alur utama dan materi pertama telah ditinjau serta dapat didemokan sepenuhnya di lingkungan lokal.

## Urutan versi awal yang disarankan

Untuk menjaga lingkup tetap terkendali, selesaikan Tahap 1–5 dan satu jalur belajar terlebih dahulu. Berikutnya selesaikan Tahap 6–9 untuk kuis bersama. Tahap 10–11 berlaku untuk keseluruhan alur sebelum versi lokal dianggap siap ditinjau.
