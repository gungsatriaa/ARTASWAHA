# Panduan UI React dan React Router ARTASWAHA

Status: navigasi dimigrasikan penuh dari Inertia ke React Router 8 pada 29 Oktober 2026. UI publik, autentikasi/interview, pembelajaran, profil, kuis/room, dan admin memakai satu renderer React. Tidak ada toggle dua UI.

## Arsitektur dan entry

- `resources/js/react/app.jsx` membaca payload awal `script#react-page`, memuat page chunk, lalu hydrate/mount `RouterProvider` dari React Router.
- `resources/js/react/routing.jsx` menghubungkan data router dengan respons halaman Laravel, shared page context, tautan, form, dan pemuatan statistik tambahan. Router mengelola URL, loader, pembatalan navigasi, Back/Forward, dan scroll restoration.
- `resources/js/react/resolve.jsx` memilih layout site/admin/auth dan memuat halaman via `import.meta.glob` lazy. Layout bertahan saat berpindah dalam layout yang sama; form di-reset pada navigasi biasa dan dipertahankan pada submit/reload/filter yang memintanya.
- Laravel tetap menentukan route yang sah, otorisasi, validasi, identitas akun/anonim, progres, skor/timer, job AI/TTS, dan akses file. Tidak ada catch-all Laravel yang melewati middleware bisnis.
- `ReactPage` mengirim dokumen root Blade saat direct visit dan JSON `{component, props, url}` saat header `X-React-Page: true`. `PagePresenter` tetap membatasi field yang dibagikan ke UI. HTML bootstrap memakai JSON_HEX flags agar data tidak memutus elemen script.
- `HandleReactRequests` membagikan identitas ringkas, CSRF, flash, menu, dan URL route bernama. Cookie/sesi Laravel tetap dipakai; jangan menggantinya dengan token di localStorage.

## Form dan navigasi

Tautan memakai React Router. Dokumen OAuth Google tetap dibuka melalui navigasi browser ke route auth, bukan fetch lintas domain. Form mengirim native fetch dengan kredensial same-origin, header CSRF, JSON atau multipart untuk upload. Validasi 422 tampil pada form tanpa menghapus isi; HTTP error tidak dianggap sukses. Redirect mutation memakai 303 agar PUT/DELETE tidak dikirim ulang ke halaman tujuan.

Partial reload memakai `X-React-Only` dan `X-React-Component`, hanya menggabungkan data pada komponen dan identitas yang sama. Analitik admin mengambil `statistics` setelah kerangka tampil, menampilkan fallback setelah 15 detik, dan menyediakan coba lagi. Reload gagal tetap mempertahankan halaman aktif. Navigasi GET penuh yang gagal menampilkan halaman pemulihan.

Editor memakai `useBlocker` untuk navigasi termasuk Back/Forward dan `beforeunload` untuk menutup/reload dokumen. Host room mengirim leave ketika benar-benar meninggalkan pathname room; reload atau cleanup Strict Mode pada URL yang sama tidak menonaktifkan room. Timer dan jawaban tidak ditunda oleh animasi.

## Performa dan privasi

- Page chunk, keyboard, crop, grafik Chart.js, Motion features, dan Echo/Pusher dimuat sesuai kebutuhan. Jangan memuat seluruh halaman secara eager atau menambah library animasi yang tumpang tindih.
- Tautan menghangatkan page chunk pada hover/fokus/touch. Prefetch respons hanya katalog untuk identitas yang dikenal, TTL 15 detik, maksimal 8 entri memori. Hover/fokus dan navigasi berbagi request katalog yang masih berjalan; prefetch dibatasi 5 detik. Kegagalan prefetch tidak disimpan dan navigasi mencoba request biasa. Hasil yang terlambat setelah invalidasi atau pergantian identitas tidak dipakai. Save-Data/2G tidak prefetch. Jangan prefetch room, progres, profil, atau halaman yang dapat menjadwalkan analisis.
- Cache katalog dibersihkan pada mutation/pergantian identitas. Payload pribadi tidak disimpan di history state atau localStorage. Back/Forward mengambil data lagi; otorisasi server tetap berlaku.
- Respons halaman memakai `Cache-Control: no-store, private` dan Vary berdasarkan header JSON/partial. Jangan mengaktifkan cache CDN untuk halaman berisi identitas atau jawaban.
- Presence dipasang sekali pada bootstrap klien, ping 30 detik, tanpa overlap, cleanup timer dan tanpa ping tab tersembunyi.
- Kueri katalog/progres tetap dipaginasi. Shared props tidak memuat isi latihan, kunci jawaban tersembunyi, secret provider, atau model pengguna lain.
- React Router tetap perlu respons Laravel untuk data yang belum tersedia. Ukur Network Waiting/TTFB, ukuran payload, query DB, dan render sebelum menyimpulkan masalah framework. Belum ada benchmark production yang membuktikan semua navigasi instan.

## SSR dan metadata

`resources/js/react/ssr.jsx` menyediakan server Node loopback `127.0.0.1:13714`. Jalankan `npm run build` kemudian `npm run ssr`. Endpoint `POST /render` menerima page JSON, merender static data router, dan mengembalikan `{head, body}`; `GET /health` untuk pemeriksaan proses. Batas payload 2 MiB; pesan error tidak memuat data pengguna.

Laravel memakai `config/react.php`: `REACT_SSR_ENABLED`, `REACT_SSR_URL`, timeout 3 detik. Kegagalan SSR dicatat dan fallback client rendering tersedia. `Head` mengelola title, canonical, robots, Open Graph/Twitter, CSRF, serta JSON-LD dengan deduplikasi dan cleanup. Registry SSR terpisah per request. Tidak ada adapter/server Inertia yang aktif.

Deploy client dan SSR bersama lalu restart program SSR. Perintah Supervisor Node baru dan penggantian environment ada pada `docs/DEPLOYMENT.md`. Tidak ada migration atau reseed untuk mengganti router.

## Tampilan dan animasi

Pertahankan token tema: background `#0A0A0A`, surface `#101113`, primary `#007C80`, accent `#FFC700`, foreground `#F5F3EE`; kartu tanpa border dekoratif, tombol pill, avatar bulat. Font Outfit/Plus Jakarta Sans dan glyph Vimala tetap mengikuti UI aktif.

Overlay hanya pada reload dokumen: bootstrap/font siap, progress hingga 100%, hold/fade, tombol lewati, fallback, reduced motion. Navigasi internal tanpa overlay atau bar progres atas. Halaman lama terlihat sambil loader menyiapkan data/chunk tujuan; status aksesibel diumumkan jika lambat. Jangan menambahkan jeda dekoratif pada fetch atau submit.

Reveal konten bawah layar memakai IntersectionObserver dengan cleanup dan dukungan fokus/reduced motion. Efek jawaban/suara dan hasil tetap mengikuti pengaturan kuis. Mobile pengguna memakai bottom navigation pill, admin memakai sidebar sendiri. Interview wajib hanya bagi identitas yang belum lengkap, dengan halaman langkah penuh setelah overlay.

## Verifikasi

Jalankan `npm run test:react`, test PHP yang terkait kontrak halaman dan bisnis, lalu `npm run build`. Pengujian router harus memakai data router sungguhan untuk navigasi, validation/upload, abort, error recovery, partial reload, dan history. Tes komponen yang memock transport tetap digunakan untuk interaksi lokal; jangan menjadikannya satu-satunya bukti migrasi.

Sebelum rilis, periksa browser pada desktop/mobile, direct URL + refresh/hydration, login/logout dan Back, anonim, upload, interview, editor belum disimpan, penguncian modul, feedback/hasil, timer/realtime room, error/offline dan statistik admin. OAuth/provider AI/TTS production, WebSocket serta HP fisik memerlukan pemeriksaan lingkungan sebenarnya; jangan menyatakan lulus hanya dari unit test.
