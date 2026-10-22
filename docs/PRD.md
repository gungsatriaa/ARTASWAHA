# ArtaSwaha — Product Requirements Document

## Ringkasan

ArtaSwaha adalah platform pembelajaran interaktif untuk anak-anak di Bali yang berfokus pada Paribasa Bali dan Aksara Bali. Materi disajikan melalui pelajaran singkat, audio, latihan, permainan, serta kuis bersama. Pengalaman dibuat menyenangkan dan mudah digunakan anak, dengan isi yang ditinjau oleh penutur atau pengajar yang memahami budaya Bali.

## Pengguna dan akses

- **Pengunjung anonim:** dapat belajar mandiri tanpa membuat akun. Progres disimpan berdasarkan perangkat/browser dan dapat hilang jika data browser dihapus atau perangkat berganti.
- **Pengguna terdaftar:** dapat belajar mandiri, membuat kuis/room, dan bergabung ke room orang lain.
- **Admin:** satu-satunya role sistem khusus; mengelola pengguna, materi, soal, dan moderasi konten.
- Tidak ada role akun guru atau siswa. Pembuat room mendapat hak host hanya untuk room yang dibuatnya; peserta menjadi status keanggotaan dalam room, bukan role global.

## Tujuan produk

1. Membuat anak tertarik mempelajari bahasa dan budaya Bali.
2. Menyajikan latihan pendek dengan umpan balik yang jelas dan audio-visual.
3. Mendukung aktivitas belajar bersama melalui room kuis yang bisa dibagikan.
4. Memungkinkan pengguna mulai belajar tanpa login, sambil memberi manfaat tambahan bagi pengguna terdaftar.

## Fitur

### Belajar mandiri

- Dua jalur utama dengan unit pelajaran berurutan dan bertahap:
  - **Paribasa Bali:** Cecimpedan, Peparikan, dan Sesonggakan.
  - **Aksara Bali:** Aksara Wreastra beserta gantungannya, Pengangge Suara, Pengangge Tengenan, Pengangge Arda Suara, dan angka dalam Aksara Bali.
- Bentuk latihan: pilihan jawaban, cocokkan bentuk dan arti, dengarkan lalu pilih, susun aksara, lengkapi kata, serta tebak makna paribasa.
- **Keyboard Aksara Bali di layar:** membantu pengguna memasukkan Aksara Bali saat membuat soal/kuis dan saat menjawab latihan atau kuis yang menerima jawaban aksara. Keyboard menampilkan karakter dan tanda yang relevan, bekerja pada perangkat sentuh maupun komputer, serta menyediakan input alternatif bila layout perangkat tidak mendukung Aksara Bali.
- Audio pelafalan yang dapat diputar ulang.
- Umpan balik visual dan suara untuk jawaban benar, salah, dan penyelesaian pelajaran; kontrol untuk mematikan suara.
- Progres, bintang/poin sederhana, pelajaran terakhir, dan latihan ulang.
- Progres anonim dapat dipindahkan/dikaitkan ke akun saat pengguna mendaftar atau login.

### Kuis bersama

- Pengguna terdaftar dapat membuat kuis dari bank materi/soal yang tersedia.
- Pembuat soal dapat memilih tipe jawaban Aksara Bali dan memasukkan teks soal/jawaban dengan keyboard Aksara Bali di layar.
- Pembuat kuis dapat membuat room, memilih kuis dan pengaturan sesi, lalu membagikan kode atau tautan undangan.
- Pengguna terdaftar dapat bergabung dengan kode room.
- Host dapat memulai sesi dan mengendalikan jalannya kuis.
- Peserta menjawab soal dan menerima umpan balik; status peserta dan papan peringkat diperbarui realtime.
- Hasil akhir menampilkan skor dan ringkasan jawaban/topik yang perlu diulang.
- Sediakan mode tanpa peringkat atau pilihan host untuk menonaktifkan papan peringkat.

### Admin dan pengelolaan materi

- Admin mengelola jalur, pelajaran, soal, pilihan jawaban, audio, dan aset terkait.
- Admin dapat meninjau dan menyembunyikan konten yang dilaporkan atau tidak sesuai untuk anak.
- Admin dapat mengelola akun dan akses platform.

## Batasan versi awal

- Fokus pada satu jalur belajar yang tervalidasi, satu set tipe soal utama, dan kuis pilihan ganda.
- Keyboard Aksara Bali dimulai dengan karakter yang diperlukan untuk materi versi awal; pemetaan dan dukungan kombinasi karakter harus ditinjau bersama ahli/penutur.
- Room memakai kode undangan; aktivitas realtime dibatasi pada status room, jawaban, dan skor.
- Belum mencakup chat, unggahan konten bebas oleh publik, marketplace, atau fitur sosial terbuka.
- Materi Paribasa Bali, kaidah penulisan Aksara Bali, kombinasi gantungan/pengangge, dan audio harus ditinjau oleh penutur atau pengajar yang kompeten sebelum diterbitkan.

## Kebutuhan nonfungsional

- Antarmuka responsif, aksesibel, dan nyaman untuk anak.
- Data pribadi anak diminimalkan; nama tampilan/avatar lebih disukai daripada nama asli di room.
- Validasi dan otorisasi dilakukan di server, termasuk akses host dan anggota room.
- Halaman materi dan query umum harus cepat; cegah N+1 dan gunakan cache untuk data yang sesuai.
- Audio, animasi, serta peringkat memiliki pilihan yang ramah koneksi dan aksesibilitas.

## Indikator keberhasilan

- Pengguna dapat menyelesaikan pelajaran pertama tanpa bantuan.
- Pengguna anonim kembali melanjutkan progres di browser yang sama.
- Host dapat membuat room dan peserta dapat bergabung serta menyelesaikan kuis.
- Pengguna memahami hasil kuis dan dapat mengulang materi yang belum dikuasai.
- Materi yang dipublikasikan memiliki status tinjauan yang jelas.
