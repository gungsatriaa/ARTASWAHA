# Tahapan Pengembangan ArtaSwaha

Dokumen ini membagi pengembangan menjadi tahapan yang dapat diselesaikan dan diuji secara lokal. Cakupannya berakhir pada aplikasi yang berjalan dan dapat diperiksa di lingkungan lokal; tahapan deployment tidak termasuk.

## Tahap 1 — Fondasi dan keputusan produk

Status: **sebagian selesai**.

### Pekerjaan

- [x] Pastikan versi Laravel dan PHP yang terpasang.
- [x] Catat versi MySQL, Node/Vite, Reverb, Echo, dan paket utama dalam `TECHSTACK.md`.
- [x] Tetapkan ruang lingkup materi: Paribasa Bali dan Aksara Bali; bahasa antarmuka Indonesia; sasaran umum anak-anak.
- [ ] Tetapkan rentang usia sasaran secara spesifik dan materi pertama untuk prototipe.
- [ ] Validasi isi Bahasa Bali/Aksara Bali/cecimpedan bersama penutur atau pengajar yang kompeten.
- [x] Siapkan MySQL lokal dan jalankan seluruh migration.
- [ ] Jalankan Redis lokal untuk cache, queue, dan realtime.
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

Status: **sebagian selesai**.

### Pekerjaan

- [x] Buat skema jalur, pelajaran, dan progres beserta relasi, indeks, factory, dan seed kategori.
- [x] Isi kategori awal Paribasa Bali dan Aksara Bali pada database lokal.
- [ ] Tambahkan bank soal serta pengelolaan media.
- [x] Buat alur admin untuk membuat dan mengubah jalur/pelajaran, mengurutkan, meninjau, menerbitkan, dan mengarsipkan pelajaran.
- [ ] Tambahkan penyimpanan audio/gambar dengan validasi tipe dan ukuran.
- [x] Pastikan pelajaran yang belum ditinjau tidak tampil pada halaman publik.

### Pengujian lokal

- [x] Jalankan migration dan pengujian alur memakai SQLite sementara di test lokal.
- [x] Uji otorisasi admin, validasi materi, proses tinjau/publikasi/arsip, dan tampilan draf pada test lokal.
- [x] Terapkan seluruh migration pada MySQL lokal 8.0.33.
- [ ] Uji pengurutan banyak materi dan kasus data kosong.

### Selesai bila

- Admin dapat menyiapkan dan menerbitkan materi yang tampil pada jalur belajar.

## Tahap 4 — Belajar mandiri anonim

Status: **sebagian selesai**.

### Pekerjaan

- [x] Sediakan akses ke materi terbit tanpa membuat akun.
- [x] Sediakan latihan pilihan ganda opsional pada pelajaran dengan pemeriksaan jawaban di server dan umpan balik benar/salah.
- [ ] Tambahkan audio, animasi, dan kontrol suara.
- [x] Simpan progres dengan UUID pada cookie perangkat terenkripsi oleh Laravel.
- [ ] Tampilkan pelajaran terakhir, ringkasan progres, dan latihan ulang.
- [x] Kaitkan progres anonim ke akun saat registrasi/login tanpa mengubah identifier perangkat.

### Pengujian lokal

- [x] Uji progres per cookie perangkat, pengiriman penyelesaian berulang, penilaian latihan, dan pencegahan melewati latihan pada test lokal.
- [ ] Periksa cookie terhapus/tidak valid, penggabungan progres, serta jaringan lambat di browser.

### Selesai bila

- Pengunjung dapat menyelesaikan pelajaran dan melanjutkan progres anonim di perangkat/browser yang sama.

## Tahap 5 — Akun dan perpindahan progres

Status: **sebagian selesai**.

### Pekerjaan

- [x] Tambahkan registrasi, login, logout, pemulihan kata sandi, dan validasi memakai fitur Laravel.
- [x] Gunakan nama tampilan dan batasi data akun awal pada nama tampilan, email, serta kata sandi.
- [x] Kaitkan progres anonim ke akun saat registrasi/login.
- [x] Terapkan rate limit pada form auth dan regenerasi/invalidation sesi.
- [ ] Tambahkan halaman akun dan otorisasi untuk operasi kuis/room.

### Pengujian lokal

- [x] Uji registrasi, login valid/tidak valid, logout, reset kata sandi, dan pengaitan progres.
- [ ] Uji pembatasan percobaan dan isolasi data antar akun.

### Selesai bila

- Pengguna dapat masuk dan progres anonim tetap terhubung secara konsisten.

## Tahap 6 — Kuis mandiri dan bank soal

Status: **sebagian selesai**.

### Pekerjaan

- [ ] Sediakan bank soal yang ditinjau admin dan dapat dikelompokkan berdasarkan topik/kesulitan.
- [x] Sediakan editor kuis pilihan ganda milik pengguna dengan validasi opsi dan batas jumlah pertanyaan.
- [ ] Implementasikan kuis mandiri dengan timer dan ringkasan hasil.
- [x] Hitung jawaban dan poin di server; abaikan skor kiriman browser dan cegah jawaban dihitung ganda.
- [ ] Tampilkan penjelasan atau materi rujukan setelah menjawab bila tersedia.

### Pengujian lokal

- [x] Uji validasi opsi, pengiriman jawaban berulang, dan manipulasi poin kiriman browser.
- [ ] Uji batas waktu, ringkasan hasil, serta latihan ulang.

### Selesai bila

- Pengguna dapat mengerjakan kuis sampai selesai dan hasil dihitung server secara benar.

## Tahap 7 — Membuat kuis dan room

Status: **sebagian selesai**.

### Pekerjaan

- [x] Izinkan pengguna terdaftar membuat kuis pribadi dengan pilihan ganda.
- [x] Izinkan pemilik kuis membuat room dan membagikan kode/tautan undangan acak.
- [x] Izinkan pengguna terdaftar bergabung dengan kode room yang valid.
- [x] Terapkan host/peserta per keanggotaan room tanpa role guru/siswa.
- [x] Validasi status room, kode salah, akses host, keanggotaan, dan jawaban server-side.
- [x] Sediakan kontrol host untuk mulai dan lanjut hingga sesi selesai.

### Pengujian lokal

- [x] Uji pembuatan room, gabung, kontrol host, scoring, dan akses kuis privat.
- [ ] Uji kode salah/kedaluwarsa, koneksi beberapa browser, serta konkurensi pada MySQL lokal.

### Selesai bila

- Host dapat membuat room dan peserta yang sah dapat bergabung serta mengikuti sesi.

## Tahap 8 — Realtime dengan Reverb dan Redis

Status: **sebagian selesai**.

### Pekerjaan

- [x] Cocokkan versi Laravel dan paket sebelum memasang Reverb, Echo, atau dependensi tambahan.
- [x] Siapkan konfigurasi Reverb, Laravel Echo, dan Pusher JS; contoh environment memakai Redis untuk queue/cache.
- [x] Siarkan perubahan room setelah peserta bergabung, sesi mulai, jawaban masuk, dan soal berganti.
- [x] Gunakan channel privat yang membatasi akses ke host/peserta room; payload event tidak memuat jawaban benar maupun skor.
- [x] Tetapkan server sebagai sumber kebenaran untuk jawaban, skor, dan hasil; browser memuat ulang data terotorisasi setelah event.
- [ ] Jalankan Reverb, queue worker, Redis, dan MySQL lokal secara bersamaan.
- [ ] Service Redis belum aktif lokal (PING ke `127.0.0.1:6379` ditolak).
- [ ] Uji beberapa browser, reconnect sungguhan, duplikasi/pesan terlambat, serta perilaku saat WebSocket terputus.

### Pengujian lokal

- [x] Uji otorisasi endpoint channel Reverb untuk host, peserta, dan pengguna luar memakai test lokal.
- [x] Uji bahwa payload event room tidak mengirim kunci jawaban atau data peringkat.
- [ ] Jalankan MySQL, Redis, queue worker bila diperlukan, Reverb, dan aplikasi pada lingkungan development lokal.
- [ ] Uji beberapa browser/session lokal bergabung ke room yang sama dan pastikan status serta skor konsisten.
- [ ] Uji reconnect sungguhan, koneksi terputus, akses channel lintas room, dan pengiriman event berulang.

### Selesai bila

- Beberapa peserta lokal dapat menyelesaikan satu kuis bersama dan melihat pembaruan status/peringkat secara realtime.

## Tahap 9 — Peringkat, hasil, dan pengalaman kelas

Status: **sebagian selesai**.

### Pekerjaan

- [x] Tampilkan papan peringkat room dengan nama tampilan dan poin dari server.
- [x] Beri poin hanya untuk jawaban benar; satu jawaban per peserta per pertanyaan.
- [x] Izinkan pembuat kuis mematikan papan peringkat.
- [x] Muat ulang data room dari server pada setiap pembaruan realtime; jangan sertakan skor dalam payload broadcast.
- Tampilkan ringkasan jawaban/topik yang perlu diulang tanpa mempermalukan peserta.
- Sediakan hasil yang dapat dilihat ulang oleh pengguna yang berhak.

### Pengujian lokal

- [ ] Uji seri, jawaban terlambat, peserta disconnect, leaderboard nonaktif, dan hasil setelah sesi berakhir.

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
