# ARTASWAHA — Coding Guidelines

## Prinsip umum

- Ikuti struktur, versi dependensi, dan konvensi yang sudah ada di repository.
- Terapkan prinsip ponytail: tulis kode sesedikit mungkin selama kebutuhan, validasi, keamanan, dan keterbacaan tetap terpenuhi.
- Hindari abstraksi, wrapper, konfigurasi, dan dependensi yang belum dibutuhkan.
- Gunakan nama yang menjelaskan maksud; setiap fungsi sebaiknya punya tanggung jawab yang jelas.
- Jangan membuat dokumentasi tambahan selain yang diminta.

## Controller dan service

- Controller menangani HTTP: menerima Form Request, memanggil service/action bila ada proses bisnis, lalu mengembalikan response/redirect.
- Jangan menaruh logika bisnis atau query kompleks di controller.
- Letakkan proses yang punya aturan bisnis atau digunakan lintas entry point di service/action yang kecil dan fokus.
- Jangan membuat service untuk meneruskan satu pemanggilan tanpa menambah aturan atau nilai nyata.
- Gunakan transaksi database untuk operasi multi-tabel yang harus atomik, misalnya menyelesaikan sesi dan menyimpan hasil kuis.

## Validasi dan otorisasi

- Validasi input melalui Form Request atau validator yang sesuai.
- Otorisasi dilakukan di server menggunakan policy/gate/middleware; menyembunyikan tombol di UI bukan kontrol keamanan.
- Role sistem khusus hanya admin. Hak host dan peserta diperiksa sebagai keanggotaan room pada setiap operasi.
- Jangan mempercayai skor, jawaban benar, status sesi, atau user ID dari browser.

## Eloquent dan performa

- Definisikan relasi model dengan benar dan gunakan eager loading saat data relasi akan dipakai dalam loop/view untuk mencegah N+1.
- Ambil kolom dan jumlah data seperlunya; gunakan pagination untuk daftar panjang.
- Tambahkan indeks berdasarkan query nyata, terutama foreign key, kode room, status, dan urutan/waktu yang sering difilter.
- Hindari query berulang di Blade; siapkan data di query/controller/service yang tepat.
- Gunakan cache hanya untuk data yang aman dan layak di-cache; atur TTL dan invalidasi saat sumber berubah.
- Ukur query lambat sebelum menambah optimasi kompleks.

## Blade dan antarmuka

- Pecah view menjadi komponen ketika pola memang digunakan ulang; jangan membuat abstraksi untuk satu pemakaian sederhana.
- Escape output secara default dan jangan render input mentah.
- Pisahkan JavaScript/CSS dari template sejauh sesuai pola bundler proyek.
- Pastikan interaksi inti tetap dapat dipahami saat animasi, suara, atau koneksi realtime tidak tersedia.

## Integritas data dan realtime

- Gunakan foreign key, unique constraint, dan transaksi untuk menjaga konsistensi.
- Server menjadi sumber kebenaran untuk status room, timer, skor, dan hasil.
- Batasi event realtime pada data yang perlu diketahui penerima; otorisasi channel berdasarkan room.
- Jangan mengirim jawaban benar atau informasi peserta privat kepada klien yang tidak berhak.

## Kualitas perubahan

- Buat perubahan sekecil mungkin dan selesaikan semua pemanggil, konfigurasi, serta test yang terdampak.
- Tambahkan test untuk logika nontrivial, aturan akses, validasi, dan alur kuis yang berubah.
- Jalankan test terarah serta formatter yang sudah ditetapkan proyek sebelum menyelesaikan perubahan kode.
- Jangan mengubah dependency atau struktur folder utama tanpa alasan kebutuhan yang jelas.
