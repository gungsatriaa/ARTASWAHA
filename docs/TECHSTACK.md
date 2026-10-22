# ArtaSwaha — Tech Stack

## Stack inti

| Area | Teknologi | Peran |
|---|---|---|
| Backend | Laravel (versi mengikuti baseline proyek) | Routing, autentikasi, validasi, otorisasi, logika aplikasi, queue dan event |
| UI | Blade | Render halaman server-side dan komponen antarmuka |
| Interaksi UI | JavaScript ringan dan CSS | Latihan interaktif, animasi, audio, dan pembaruan antarmuka |
| Database | MySQL | Pengguna, materi, soal, room, jawaban, progres, dan hasil |
| Cache, queue, dan scaling realtime | Redis | Cache, throttling, queue, serta pub/sub untuk scaling Reverb bila diperlukan |
| Realtime | Laravel Reverb + Laravel Echo | WebSocket untuk status room, peserta, soal, jawaban, dan peringkat live |
| Hosting | VPS dengan aaPanel | Menjalankan aplikasi Laravel, MySQL, Redis, dan proses Reverb |

## Komponen aplikasi yang direncanakan

- **Laravel Blade** sebagai UI utama; SPA tidak diperlukan untuk versi awal.
- **Laravel Reverb** sebagai server WebSocket first-party Laravel dan **Laravel Echo** di browser untuk menerima event kuis realtime. Reverb cocok untuk kebutuhan room dan leaderboard live; pastikan versi Laravel serta paket Echo kompatibel sebelum instalasi.
- **Redis** untuk cache dan queue aplikasi; Reverb dapat memakai Redis pub/sub jika nanti Reverb perlu diskalakan ke beberapa proses/server.
- Pada VPS aaPanel, Reverb perlu dijalankan sebagai proses persisten yang dipantau process manager, dan WebSocket harus diproksikan melalui web server dengan TLS/WSS. Jangan membuka port Reverb ke publik tanpa kebutuhan; batasi origin dan lindungi channel private/presence dengan otorisasi.
- **Laravel queue** untuk pekerjaan latar belakang seperti pemrosesan aset atau laporan bila memang diperlukan.
- **Penyimpanan file** untuk audio dan gambar; gunakan storage lokal saat development dan layanan object storage yang kompatibel saat produksi bila kebutuhan skala menuntut.
- **Testing** mengikuti tool yang sudah terpasang di proyek dan pola PHPUnit yang digunakan repository.

## Prinsip pemilihan

- Pertahankan dependensi seminimal mungkin; gunakan kemampuan Laravel atau paket yang sudah ada sebelum menambah paket.
- Cocokkan semua sintaks dan integrasi dengan versi terpasang melalui `composer.lock`/`composer show`.
- Redis bukan database utama; data permanen dan progres tersimpan di MySQL.
- Sediakan fallback yang masuk akal jika animasi/suara atau koneksi realtime tidak tersedia.

## Di luar keputusan saat ini

Versi Laravel, paket autentikasi, detail konfigurasi aaPanel/web server, serta object storage belum ditetapkan dalam dokumen ini. Cocokkan Reverb dengan versi Laravel terpasang; dokumentasi Laravel menjelaskan Reverb sebagai integrasi broadcasting dan Redis pub/sub untuk scaling, sementara deployment Reverb memerlukan process manager dan konfigurasi WebSocket/TLS yang benar. [Laravel Broadcasting](https://laravel.com/docs/broadcasting), [Laravel Reverb](https://laravel.com/docs/reverb), [Laravel Deployment](https://laravel.com/docs/deployment).
