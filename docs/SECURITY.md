# ARTASWAHA — Standar Keamanan

Dokumen ini menetapkan baseline keamanan untuk aplikasi Laravel ARTASWAHA. Terapkan sesuai versi Laravel dan paket yang benar-benar terpasang; jangan menganggap contoh API berlaku untuk semua versi.

## Identitas dan akses

- Gunakan autentikasi Laravel yang terpelihara dan sesi yang aman; jangan membuat skema autentikasi sendiri tanpa kebutuhan.
- Terapkan otorisasi server-side melalui policy/gate untuk semua aksi yang mengubah atau membaca data terbatas.
- Role global khusus hanya `admin`. Status host/peserta berlaku per room dan harus diperiksa pada setiap aksi room.
- Jangan mempercayai ID, role, harga, skor, atau status yang dikirim browser. Hitung dan validasi hasil kuis di server.
- Lindungi aksi sensitif dengan autentikasi ulang bila sesuai, throttling, dan pencatatan audit seperlunya.

## Validasi dan input

- Validasi semua input pada batas kepercayaan menggunakan Form Request atau validator Laravel.
- Gunakan allowlist untuk field yang boleh disimpan; lindungi model dari mass assignment.
- Escape output Blade secara default. Hindari `{!! !!}` untuk konten pengguna; sanitasi konten rich text dengan kebijakan yang jelas.
- Batasi ukuran dan tipe file upload, simpan di disk privat bila tidak untuk publik, dan jangan menjalankan file unggahan.
- Kode room harus acak, cukup sulit ditebak, dibatasi masa aktif bila sesuai, dan tidak menjadi pengganti otorisasi.

## Web dan sesi

- Produksi wajib HTTPS, `APP_DEBUG=false`, secret hanya melalui environment/secret manager, dan file `.env` tidak pernah di-commit.
- Gunakan proteksi CSRF Laravel untuk aksi berbasis sesi. Jangan mengecualikan endpoint tanpa alasan dan pengamanan pengganti.
- Cookie sesi harus `HttpOnly`, `Secure` di HTTPS, dan `SameSite` sesuai alur aplikasi.
- Regenerasi session ID setelah login/perubahan hak akses; invalidasi sesi saat logout.
- Tetapkan security headers yang sesuai, termasuk kebijakan framing, content type, referrer, dan Content Security Policy yang kompatibel dengan aset aplikasi.
- Batasi percobaan login, pembuatan room, join room, dan pengiriman jawaban untuk mengurangi brute force/abuse.

## Perlindungan anak dan privasi

- Kumpulkan data minimum yang diperlukan. Hindari meminta nama lengkap, alamat, lokasi, atau kontak anak tanpa kebutuhan dan dasar yang jelas.
- Gunakan nama tampilan/avatar di room; jangan tampilkan identitas akun atau email kepada peserta lain.
- Jangan sediakan chat publik atau unggahan teks bebas pada versi awal.
- Tentukan retensi dan penghapusan data progres, room, dan akun; jangan menyimpan event realtime atau log berisi data sensitif lebih lama dari kebutuhan.
- Informasikan dengan bahasa yang mudah dipahami tentang data yang disimpan, termasuk identifier anonim berbasis browser.

## Database, cache, dan realtime

- Gunakan parameter binding/Eloquent; hindari menyusun SQL dari input pengguna.
- Beri indeks pada foreign key dan pola pencarian yang sering digunakan; gunakan transaksi untuk perubahan skor/progres yang harus konsisten.
- Jangan menyimpan rahasia atau data personal sensitif di cache Redis tanpa kebutuhan. Pisahkan namespace/cache dan atur TTL.
- Batasi akses channel realtime berdasarkan autentikasi dan otorisasi room. Validasi payload event; jangan mengirim jawaban benar sebelum peserta menjawab jika itu merusak kuis.
- Jadikan server sumber kebenaran untuk timer, skor, status room, dan hasil.

## Operasi dan pemeliharaan

- Jangan commit `.env`, token, private key, dump database, atau kredensial; tinjau staged diff sebelum commit/push.
- Gunakan akun database dengan hak minimum. Batasi jaringan Redis dan database ke aplikasi/lingkungan yang membutuhkan.
- Pisahkan konfigurasi development, test, dan production; jangan gunakan data anak nyata dalam development/test.
- Catat kejadian keamanan penting tanpa mencatat password, token, session ID, atau payload sensitif.
- Perbarui framework dan dependensi secara terencana; tinjau advisori keamanan sebelum rilis.
- Backup database terenkripsi dan uji pemulihan secara berkala.

## Pemeriksaan sebelum rilis

- Verifikasi autentikasi, otorisasi admin/host/anggota, CSRF, validasi, throttling, upload, dan akses channel realtime.
- Pastikan data sensitif tidak muncul di HTML, log, cache publik, atau respons yang tidak berwenang.
- Uji pemulihan backup, rotasi secret, konfigurasi HTTPS, dan mode debug produksi.
- Catat risiko yang belum ditangani dan pemilik tindak lanjut sebelum peluncuran.
