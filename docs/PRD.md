# ARTASWAHA — Product Requirements Document

## Ringkasan

ARTASWAHA adalah platform edukasi bahasa, sastra, dan aksara Bali untuk anak-anak. Materi disajikan melalui pelajaran singkat, audio, latihan, permainan, serta kuis bersama. Pengalaman dibuat menyenangkan dan mudah digunakan anak, dengan isi yang ditinjau oleh penutur atau pengajar yang memahami budaya Bali.

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
  - **Paribasa Bali:** Cecimpedan, Peparikan, dan Sesonggan.
  - **Aksara Bali:** Aksara Wreastra beserta gantungannya, Pengangge Suara, Pengangge Tengenan, Pengangge Arda Suara, dan angka dalam Aksara Bali.
- Bentuk latihan: pilihan jawaban, cocokkan bentuk dan arti, dengarkan lalu pilih, susun aksara, lengkapi kata, serta tebak makna paribasa.
- **Keyboard Aksara Bali di layar:** membantu pengguna memasukkan Aksara Bali saat membuat soal/kuis dan saat menjawab latihan atau kuis yang menerima jawaban aksara. Keyboard menampilkan karakter dan tanda yang relevan, bekerja pada perangkat sentuh maupun komputer, serta menyediakan input alternatif bila layout perangkat tidak mendukung Aksara Bali.
- Audio pelafalan yang dapat diputar ulang.
- Umpan balik visual dan suara untuk jawaban benar, salah, dan penyelesaian pelajaran; kontrol untuk mematikan suara.
- Progres, bintang/poin sederhana, pelajaran terakhir, dan latihan ulang.
- Progres anonim dapat dipindahkan/dikaitkan ke akun saat pengguna mendaftar atau login.

### Kuis bersama

- Pengguna terdaftar dapat membuat kuis dari bank materi/soal yang tersedia.
- Pengguna dapat membuat kuis secara manual atau meminta AI menyusun draf soal dari dokumen sumber.
- Untuk pembuatan otomatis, pengguna mengunggah DOCX, PDF, JPG, atau PNG. Teks dari dokumen digital diekstrak langsung; OCR digunakan untuk PDF hasil pemindaian dan gambar. Format yang didukung pada MVP dibatasi pada tipe tersebut, bukan semua format berkas.
- Pengguna dapat memilih jumlah soal (maksimal 10 pada MVP) dan meninjau proses ekstraksi serta pembuatan soal, termasuk status gagal dengan pesan yang dapat ditindaklanjuti.
- AI menghasilkan soal pilihan ganda berdasarkan isi dokumen, termasuk pertanyaan, 2–5 opsi, jawaban benar, dan penjelasan. Hasil ditampilkan pada editor kuis yang sama dengan alur manual dan dapat diperiksa, diubah, atau dihapus sebelum disimpan.
- Soal hasil AI selalu berupa draf. AI tidak boleh menyimpan, menerbitkan, atau membuat room secara otomatis; pengguna harus meninjau dan menyimpan kuis secara eksplisit.
- Jika ekstraksi mendukungnya, hasil soal menyertakan rujukan halaman atau potongan sumber agar pengguna dapat memeriksa kesesuaian jawaban.
- Dokumen Aksara Bali atau tulisan tangan tidak dijanjikan akurat pada MVP. Tampilkan teks hasil ekstraksi agar dapat dikoreksi; dukungan OCR khusus Aksara Bali harus diuji pada dokumen nyata sebelum dinyatakan siap.
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

### Pembuatan soal berbantuan AI

- Laravel mengatur unggah, ekstraksi/OCR, antrean pekerjaan, validasi hasil, dan pengisian editor kuis. Pekerjaan yang memerlukan OCR atau pemanggilan model diproses di latar belakang agar UI tidak menggantung.
- 9Router digunakan sebagai gateway model yang kompatibel dengan API OpenAI. URL endpoint, API key, dan ID model dikonfigurasi di environment server; secret tidak pernah dikirim ke browser atau disimpan di repository.
- OpenClaw bukan dependensi untuk alur ini. OCR dijalankan sebagai komponen ekstraksi dokumen yang terpisah dari model generatif; provider/engine OCR masih perlu dipilih dan diuji.
- Teks di dalam dokumen adalah materi sumber yang tidak tepercaya, bukan instruksi untuk AI. Sistem mengabaikan instruksi yang tertanam di dokumen dan hanya menggunakannya sebagai sumber untuk membuat soal.
- Berkas disimpan privat, divalidasi berdasarkan tipe dan ukuran, dibatasi jumlah halaman/volume pemrosesan, dan memiliki kebijakan retensi serta penghapusan. Nilai batas dan masa retensi ditetapkan sebelum rilis.
- Kegagalan unggah, ekstraksi, atau generasi harus memberi status yang jelas; kegagalan tidak boleh menghasilkan kuis yang tersimpan sebagian.

## Batasan versi awal

- Fokus pada satu jalur belajar yang tervalidasi, satu set tipe soal utama, dan kuis pilihan ganda.
- Keyboard Aksara Bali dimulai dengan karakter yang diperlukan untuk materi versi awal; pemetaan dan dukungan kombinasi karakter harus ditinjau bersama ahli/penutur.
- Room memakai kode undangan; aktivitas realtime dibatasi pada status room, jawaban, dan skor.
- Belum mencakup chat, unggahan konten bebas oleh publik, marketplace, atau fitur sosial terbuka.
- Pembuatan soal AI pada MVP hanya menghasilkan pilihan ganda dari DOCX, PDF, JPG, dan PNG; batas ukuran/halaman, vendor OCR, model, serta retensi berkas ditentukan saat implementasi dan sebelum produksi.
- Pembuatan soal AI memerlukan tinjauan dan penyimpanan eksplisit oleh pengguna; akurasi OCR Aksara Bali dan tulisan tangan berada di luar jaminan MVP.
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
