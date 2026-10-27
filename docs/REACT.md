# Panduan migrasi UI ARTASWAHA ke React dan Inertia

Status: seluruh route antarmuka memakai React/Inertia, 27 Oktober 2026. Tidak ada sakelar renderer atau dua versi UI aktif. UI Blade lama dan initializer DOM yang tidak dipakai telah dihapus atas permintaan pengguna. Blade aktif hanya menjadi dokumen root, overlay sebelum bootstrap, dan sitemap XML. Referensi sebelum migrasi tersedia melalui riwayat Git.

## Implementasi saat ini

- Entry tunggal: `resources/js/react/app.jsx`; root: `resources/views/react.blade.php`. Resolver memisahkan halaman per route. `config/ui.php` dan `REACT_UI_ENABLED` dihapus; `?ui=blade` tidak mengganti renderer.
- Beranda, login/register/pemulihan, interview multi-pilihan, katalog/pelajaran, profil/analisis/avatar, kuis/editor/room, privasi, dan seluruh halaman admin sudah React, pada desktop maupun mobile.
- Laravel tetap memegang validasi, sesi/cookie anonim, otorisasi, antrean AI/TTS, timer, skor, dan penyimpanan. `PagePresenter` mengirim props eksplisit. Room tidak mengirim soal masa depan atau kunci jawaban sebelum diizinkan. Daftar modul admin tidak mengirim isi latihan/kunci yang tidak diperlukan.
- Shell pengguna/admin persisten: navigasi mobile pill, sidebar admin, dialog native, satu toast atas, heartbeat dengan cleanup. Reload host tidak mengirim aksi keluar; tab yang ditutup mengikuti expiry heartbeat server 90 detik. Keluar melalui navigasi aplikasi mengirim aksi keluar.
- Chart.js, Echo/Pusher, keyboard, dan crop avatar dimuat sesuai kebutuhan. Helper edit aksara tetap digunakan keyboard React dan aman pada SSR.
- SSR aktif secara default dengan entry `resources/js/react/ssr.jsx`, bind loopback `127.0.0.1:13714`, batas request 3 detik. Production perlu proses SSR terpisah; lihat `docs/DEPLOYMENT.md`. History Inertia dienkripsi dan dibersihkan ketika identitas berganti; DevTools recording nonaktif.
- Overlay muncul pada setiap reload dokumen, bukan pada visit Inertia/partial reload. Bootstrap dimulai sebelum React, menunggu mount dan font dengan batas waktu, mencapai 100%, hold 450 ms, lalu fade. Progress menunjukkan kesiapan tampilan, bukan byte unduhan atau penyelesaian job AI. Ada lewati, fallback, dan reduced motion.
- Transisi halaman memakai CSS; reveal kartu di bawah layar memakai IntersectionObserver sekali dengan cleanup/fokus/reduced motion. Tidak ada dependency AOS tambahan. Skeleton dipakai untuk navigasi GET lambat, chunk tambahan, dan status analisis; error dan batas polling menghentikan animasi tunggu. Submit jawaban/timer tidak diberi delay dekoratif.

Dependency terpasang: Laravel adapter Inertia 3.5.2, React/React DOM 19.3.1, adapter React Inertia 3.9.1, Motion 14.6.0, plugin React/Vite 6.1.2. Tidak ada dependency aplikasi baru dalam cutover menyeluruh ini. Build menghasilkan client dan SSR; lockfile tetap acuan versi.

Verifikasi cutover:

- 247 tes PHP lulus: route/props, validation, otorisasi, identitas/progres anonim, audio, timer, skor, batas waktu total, dan feedback. Assertion markup Blade dipindah ke kontrak Inertia; markup/interaksi React diperiksa pada suite berikut.
- 57 tes React lulus: multi-select, form/transform, modal login anonim, payload editor/ID soal, rendering admin, teks aman, keyboard editor terkontrol, avatar, countdown, feedback, terminal polling, cleanup Strict Mode, overlay 100%, skeleton GET, chunk gagal, serta SSR tanpa global browser dan metadata/JSON-LD.
- Tes khusus initializer DOM Blade telah dihapus bersama implementasi lamanya; lima tes helper edit aksara tetap dipertahankan. Tes installer worker yang target skripnya sudah tidak ada juga dihapus. Tes deployment dan installer TTS yang masih tersedia tetap dipertahankan.
- HTTP preview dengan SSR aktif: halaman publik dan admin, login dengan rotasi CSRF, interview anonim multi-pilihan, kuis dua peserta, feedback privat, kemajuan peserta independen, dan durasi penuh soal berikutnya lolos. Memakai SQLite sementara dan data uji; data asli serta layanan production tidak diubah.
- Browser sempat memverifikasi navigasi/katalog/profil desktop dan viewport 393 × 852 tanpa error konsol. Browser lokal kemudian dinonaktifkan di pengaturan Work. Pemeriksaan visual akhir untuk overlay/reveal baru, seluruh breakpoint, upload crop pada browser, serta WebSocket Reverb belum bisa diselesaikan. Pengujian SSR/HTTP/komponen tidak menggantikan pengujian tersebut.
- Pengujian HP fisik, React Profiler/trace LCP-INP, tab panjang, OAuth nyata, provider AI/TTS production, dan jaringan putus nyata belum dilakukan. Ukuran chunk build bukan klaim performa perangkat nyata.

Tujuan: UI terasa seperti aplikasi, animasi responsif, navigasi lebih mulus, dan pemuatan tetap ringan pada HP. React mengelola tampilan dan interaksi; Laravel tetap mengelola aturan bisnis, identitas pengguna, penyimpanan, otorisasi, antrean, dan realtime.

## 1. Keputusan dasar

- Gunakan React dan Inertia dalam aplikasi Laravel yang sama, dengan Tailwind v4 dan Vite yang sudah digunakan.
- Gunakan JSX untuk mengikuti JavaScript proyek saat ini. Jangan sekaligus menambahkan migrasi bahasa, framework UI besar, atau state manager global.
- Routing tetap di `routes/web.php`. Jangan menambahkan React Router atau membuat REST API pengganti seluruh controller.
- Pertahankan service, Form Request, policy/middleware, job, model, route bernama, dan kontrak data yang masih relevan.
- Gunakan React state untuk interaksi lokal, props Inertia untuk data server, serta context kecil hanya untuk kebutuhan lintas halaman yang nyata.
- Animasi sederhana memakai CSS. Motion for React dipakai untuk interaksi yang memerlukan urutan, gesture, atau animasi masuk/keluar terkoordinasi.
- Jangan menggabungkan AOS, GSAP, Motion, dan runtime animasi maskot lain untuk mengerjakan hal yang sama.

Inertia menghubungkan halaman React dengan routing/controller server; bukan pengganti backend Laravel. [Konsep resmi Inertia](https://inertiajs.com/).

## 2. Kondisi proyek dan pemeriksaan versi

Laravel terpasang pada versi 13.38.0. Proyek memakai Vite 8, Tailwind 4, Chart.js, Laravel Echo, Pusher JS, React, Inertia, dan Motion. Versi aktif tercatat pada bagian implementasi dan lockfile.

Dokumentasi resmi Inertia saat ini mengarah ke v3. Arah pilihan adalah React 19 dan Inertia 3, setelah kompatibilitas pasangan adapter Laravel/React, PHP, Vite, dan Node diverifikasi. Jangan menyalin contoh v2 ke v3 tanpa memeriksa API-nya.

Sebelum pemasangan:

1. Periksa `composer show --direct`, `package.json`, lockfile, versi PHP, serta Node lokal dan production.
2. Periksa dokumentasi instalasi untuk major yang dipilih. Tentukan integrasi Vite resmi yang diperlukan tanpa menggandakan fungsi plugin.
3. Pasang hanya dependency yang dibutuhkan fase tersebut; commit perubahan manifest dan lockfile bersama.
4. Pertahankan entry Blade. Jangan memakai starter kit yang menimpa auth, route, atau konfigurasi aplikasi.

Dependency migrasi yang sudah terpasang: `react`, `react-dom`, `@inertiajs/react`, adapter `inertiajs/inertia-laravel`, serta integrasi React/Vite yang sesuai. Tambahkan `motion` ketika komponen animasi mulai dibuat. Tidak perlu Redux, React Query, UI kit penuh, atau wrapper Chart.js pada fase awal.

## 3. Referensi UI setelah pembersihan

UI aktif ada pada `resources/js/react/Pages/`, `resources/js/react/Components/`, dan `resources/js/react/Layouts/`. Tema bersama ada pada `resources/css/app.css`.

Blade yang masih aktif:

- `resources/views/react.blade.php`: dokumen root Inertia dan metadata awal.
- `resources/views/components/react-loader.blade.php`: overlay sebelum React siap.
- `resources/views/sitemap.blade.php`: sitemap XML.

Template dan initializer Blade lama sudah dihapus setelah cutover selesai. Gunakan riwayat Git untuk melihat referensi lama. Jangan mengembalikan entry `resources/js/app.js` atau memasang listener DOM lama pada elemen React. Helper `resources/js/aksara-edit.js` tetap dipakai keyboard React.

## 4. Tema dan komponen bersama

Pertahankan token dari `resources/css/app.css`:

| Token | Nilai saat ini | Penggunaan |
| --- | --- | --- |
| Background | `#0A0A0A` | Latar aplikasi |
| Surface | `#101113` | Kartu dan panel tanpa border dekoratif |
| Primary | `#007C80` | Aksi dan progres |
| Accent | `#FFC700` | Sorotan dan status aktif |
| Foreground | `#F5F3EE` | Teks utama |
| Radius kartu | `1.75rem` | Kartu utama |
| Radius kontrol | `0.875rem` | Input dan kontrol |

Tombol utama tetap pill, avatar bulat, dan ikon aktif navigasi mobile berada dalam lingkaran. Fokus keyboard, pilihan jawaban, error, dan divider yang membantu membaca tetap terlihat; larangan border dekoratif bukan alasan menghilangkan indikator interaksi.

Catatan: `docs/STYLE.md` masih menyebut Nunito/Atkinson/Quicksand dan Font Awesome, sedangkan UI aktif menggunakan Plus Jakarta Sans, Outfit, Vimala, serta banyak SVG inline. Untuk migrasi, pertahankan tampilan aktif sebagai referensi. Selaraskan perbedaan dokumen sebelum standardisasi; jangan tiba-tiba memuat font dan paket ikon tambahan.

Bangun komponen bersama saat benar-benar dipakai: Button, Card, Input, Dialog, Avatar, Toast, Skeleton, Progress, dan Mascot. Hindari komponen dengan puluhan opsi untuk mengantisipasi semua layar.

Layout yang dibutuhkan: publik, pengguna, dan admin. Pada mobile, dashboard aplikasi berlaku untuk akun atau profil anonim yang sudah selesai interview. Pengunjung baru/interview tetap mengikuti alur publik. Pengguna mobile memakai bottom navigation pill tanpa sidebar/hamburger; admin mempertahankan layout admin tersendiri.

## 5. Komunikasi dan batas data

| Kebutuhan | Jalur yang dipakai |
| --- | --- |
| Membuka halaman React | Visit Inertia → controller → props yang dibatasi |
| Submit form | Form Inertia → route Laravel → Form Request/service → redirect dan error/flash |
| Filter/pagination | Parameter URL dan partial reload pada page component yang sama |
| Ringkasan tambahan | Deferred props atau pemuatan saat terlihat bila tersedia di major terpilih |
| Analisis AI/audio | Job server; client hanya memeriksa status/hasil |
| Room realtime | Echo/Reverb untuk event; aksi tetap divalidasi server |
| Audio/file privat | Endpoint yang sudah mengotorisasi akses; tidak dibundel sebagai props |

- Shared props hanya berisi identitas ringkas, mode anonim/akun, flash, dan data shell yang diperlukan. Jangan membagikan seluruh katalog, statistik, atau model pada setiap visit.
- Pilih field/relasi secara eksplisit. Semua props dapat dibaca pengguna; menyembunyikan elemen React bukan otorisasi.
- Data awal halaman harus cukup untuk menampilkan tugas utama. Jangan menunggu grafik atau analisis sebelum menampilkan nama dan materi.
- Partial reload harus disertai lazy evaluation di server bila ingin menghindari query yang tidak dibutuhkan. Gunakan hanya untuk page component yang sama. [Partial reloads](https://inertiajs.com/docs/v3/data-props/partial-reloads).
- Deferred props mengambil data setelah respons awal; ini bukan pengganti queue untuk inferensi AI. Kelompokkan data terkait untuk menghindari banyak request kecil. [Deferred props](https://inertiajs.com/docs/v3/data-props/deferred-props).
- Endpoint status yang sekarang mengembalikan fragmen Blade perlu kontrak props/JSON untuk React. Jangan memasukkan fragmen itu dengan `dangerouslySetInnerHTML`. Tidak ada lagi halaman UI Blade yang membutuhkan fragmen HTML legacy.
- Gunakan session/cookie dan mekanisme CSRF Laravel yang ada. Jangan memindahkan identitas anonim atau sesi login menjadi JWT di localStorage.
- Jangan pernah memasukkan API key AI, payload internal job, jawaban benar yang belum boleh dibuka, atau data pengguna lain ke props/variabel `VITE_*`.
- Bersihkan state pribadi, cache props, draft yang sensitif, dan subscription saat logout/pergantian identitas. Uji tombol Back setelah logout.

## 6. Batas performa dan pemuatan kode

Angka berikut adalah target awal internal, bukan hasil benchmark atau jaminan. Catat baseline UI lama, kemudian ukur build production di perangkat/koneksi yang sama.

| Ukuran | Target awal |
| --- | --- |
| JS yang ditransfer untuk membuka layar pengguna pertama | Maksimal 180 KiB gzip, termasuk shared chunk dan page chunk yang dimuat |
| Tambahan JS route biasa | Maksimal 70 KiB gzip; editor/grafik berat ditinjau terpisah |
| Props awal | Maksimal 100 KiB sebelum kompresi; pagination untuk daftar besar |
| LCP / INP / CLS | Target ≤2,5 detik / ≤200 ms / ≤0,1 pada skenario ukur yang dicatat |
| Kebocoran lifecycle | Tidak ada penambahan listener, timer, atau subscription setelah keluar-masuk layar berulang |

Aturan implementasi:

1. Split page per route. Jangan memakai `import.meta.glob(..., { eager: true })` untuk seluruh halaman. Verifikasi resolver/build mengikuti adapter terpasang. [Code splitting Inertia](https://inertiajs.com/docs/v3/advanced/code-splitting).
2. Lazy-load Chart.js pada halaman grafik, editor soal saat diperlukan, dan keyboard Aksara Bali ketika dibuka. Echo/Pusher hanya dimuat untuk fitur yang memerlukannya.
3. Gunakan `React.lazy` dan `Suspense` untuk kode komponen tambahan; deklarasikan `lazy` di level modul. Tambahkan error boundary untuk kegagalan chunk. Suspense tidak otomatis menangani semua fetch di effect. [React lazy](https://react.dev/reference/react/lazy).
4. Jangan prefetch seluruh aplikasi. Prefetch selektif pada tujuan yang mungkin dibuka, dan hentikan pemuatan spekulatif yang tidak perlu pada koneksi hemat data.
5. Batasi gambar maskot/ilustrasi menurut ukuran tampil; tetapkan dimensi agar layout stabil. Gambar hero penting tidak di-lazy-load; gambar di bawah layar boleh lazy.
6. Pertahankan font yang dipakai, fallback, dan glyph Vimala. Jangan memuat banyak keluarga/font weight demi satu heading.
7. Jangan menambahkan `memo`, `useMemo`, atau `useCallback` pada semua komponen. Gunakan setelah profiler menunjukkan pekerjaan render yang bermasalah.
8. Timer kuis tidak boleh menyebabkan seluruh halaman, daftar peserta, dan layout dirender ulang setiap frame. Isolasi countdown dan gunakan ref/motion value bila sesuai.
9. Hindari efek blur besar, shadow bertumpuk, filter SVG kompleks, dan animasi ukuran/layout pada daftar panjang. Virtualisasi hanya bila pengukuran membuktikan pagination tidak cukup.

## 7. Sistem animasi

Maksimalkan kualitas respons terhadap tindakan, bukan jumlah elemen bergerak.

| Interaksi | Durasi acuan | Teknik awal |
| --- | --- | --- |
| Tekan tombol/pilih opsi | 100–160 ms | CSS transform dan warna |
| Dialog, toast, pergantian panel | 160–240 ms | CSS atau Motion masuk/keluar |
| Pergantian konten halaman | 180–280 ms | Opacity/translate kecil pada area konten |
| Pengisian progres | 300–450 ms | Transform; nilai tetap dari server |
| Sapaan maskot | 600–900 ms, sekali | Transform aset yang sudah ada |

- Pilih satu pemilik transisi halaman. Jangan menumpuk View Transitions global lama dengan animasi keluar/masuk Motion pada area yang sama.
- Persistent layout mempertahankan shell. Jangan memberi key seluruh aplikasi berdasarkan URL yang membuat layout, form, audio, dan subscription remount pada setiap filter.
- `AnimatePresence` hanya melingkupi bagian yang memang berganti. State server baru dan pencatatan jawaban tidak boleh menunggu exit animation.
- Utamakan `transform` dan `opacity`; jangan menjanjikan semua efek otomatis berjalan di compositor.
- Gunakan satu konfigurasi reduced motion. Gerakan dekoratif dihentikan, informasi/hasil tetap muncul, dan pengguna tidak dipaksa menunggu durasi animasi.
- Target sentuh nyaman, fokus keyboard jelas, dialog memiliki focus trap/restore, dan status penting dapat dibaca screen reader.
- Tidak ada suara otomatis. Haptic/suara tambahan bukan prasyarat keberhasilan aksi.

### Motion tetap ringan

Gunakan `LazyMotion` dan `m` dengan feature bundle async; jangan mengimpor fitur penuh dari setiap komponen. `domAnimation` cukup untuk banyak entrance/exit/gesture. Fitur layout/drag yang membutuhkan `domMax` dimuat hanya pada bagian yang memerlukannya. [Panduan bundle Motion](https://motion.dev/docs/react-reduce-bundle-size).

Pola ilustratif untuk versi Motion yang mendukung API berikut, bukan bootstrap aplikasi yang siap ditempel:

```jsx
// motion-features.js: modul terpisah, dimuat saat dibutuhkan
export { domAnimation as default } from 'motion/react';

// MotionBoundary.jsx: modul berbeda dari motion-features.js
import { LazyMotion, MotionConfig } from 'motion/react';
import * as m from 'motion/react-m';

const loadFeatures = () => import('./motion-features.js').then(module => module.default);

export function MotionBoundary({ children }) {
    return (
        <MotionConfig reducedMotion="user">
            <LazyMotion features={loadFeatures} strict>
                {children}
            </LazyMotion>
        </MotionConfig>
    );
}

// Di dalam boundary; konten tetap terlihat sebelum fitur selesai dimuat.
export function FeedbackPanel({ children }) {
    return <m.div initial={false} animate={{ opacity: 1 }}>{children}</m.div>;
}
```

Jangan mengawali seluruh halaman dengan opacity nol sambil menunggu bundle animasi. Kegagalan animasi harus tetap menghasilkan UI yang bisa dibaca dan dioperasikan.

### Maskot

Mulai dari WebP/SVG/aset yang sudah tersedia: sapaan saat tap, respons setelah menyelesaikan pelajaran, dan perayaan singkat. Jangan menambah runtime Lottie/Rive atau canvas/WebGL sebelum kebutuhan animasi karakter membuktikan aset biasa tidak cukup. Hentikan efek saat elemen tidak terlihat/tab tersembunyi. Jangan menjalankan loop idle tanpa akhir, mengikuti pointer global, atau mengubah React state setiap frame.

## 8. Loading dan skeleton

- Bedakan loading kode, menunggu data, menunggu queue, dan error. Semua state punya akhir atau fallback yang dapat dipahami.
- Sesuai permintaan terbaru, tampilkan overlay setiap reload dokumen pada semua halaman: minimum sekitar 850 ms, penutupan setelah bar 100%, hold sekitar 450 ms, lalu fade sekitar 240 ms. Sediakan lewati dan fail-safe. Reduced motion melewati jeda dekoratif.
- Bootstrap overlay harus dapat tampil sebelum React siap. Jangan menunggu `useEffect` halaman selesai lalu baru menampilkan overlay.
- Jangan gunakan session marker untuk meniadakan overlay saat reload. Visit Inertia, partial reload, dan mount ulang komponen tidak membuat overlay baru; restore dari back-forward cache tidak memulai bootstrap baru.
- Overlay pembuka bukan indikator semua request selesai. Jangan menunggu job AI, audio, prefetch, atau gambar lazy untuk menutupnya; jangan mengklaim persentasenya sebagai byte unduhan jaringan.
- Visit berikutnya memakai progress navigasi tipis bila diperlukan, bukan overlay fullscreen. Jangan menampilkan progress navigasi kedua bersamaan dengan indikator Inertia bawaan.
- Skeleton mengikuti ruang konten agar layout stabil. Error/gagal analisis harus mengganti skeleton dengan pesan, bukan terus beranimasi.
- Skeleton dekoratif 400 ms yang sudah disepakati boleh dipertahankan pada bagian terpilih. Jangan menambah delay ke request, pencatatan progres, submit jawaban, atau timer berikutnya. Fokus/ketukan langsung mengakhiri efek.

## 9. Lifecycle React dan realtime

- Setiap effect yang membuat timer, observer, listener, audio, request, atau subscription harus memiliki cleanup yang sesuai.
- Uji dengan Strict Mode di development. Jangan menonaktifkannya untuk menyembunyikan double subscription atau effect yang tidak bersih.
- Batalkan/abaikan respons request lama setelah pindah halaman atau filter berubah. Jangan biarkan respons lama menimpa state terbaru.
- Persistent layout tidak boleh membuat heartbeat presence baru pada setiap visit. Pertahankan interval yang sesuai perilaku sekarang dan berhenti saat tab tidak terlihat.
- Room subscribe hanya sekali untuk room/identitas aktif; leave saat keluar. Reconnect harus mengambil snapshot server untuk mengejar event yang terlewat.
- Urutkan/abaikan event lama memakai identitas/versi state yang disediakan backend. Jangan menambah skema event baru sebelum mengecek kontrak yang sudah ada.
- Gunakan batas waktu server sebagai acuan countdown; jangan menghitung durasi resmi dengan mengurangi satu angka per interval client.
- Feedback benar/salah tidak boleh memotong durasi soal berikutnya. Pertahankan aturan durasi per soal, pengaturan global, batas total, dan mode feedback yang ada di service saat migrasi dilakukan.
- Prefetch tidak boleh menjalankan aksi tulis: join room, mulai kuis, mencatat jawaban/progres, atau memicu inferensi berulang. Pisahkan side effect yang sekarang terjadi saat GET dari jalur prefetch sebelum mengaktifkannya.
- Hook React untuk analisis tidak memanggil provider AI langsung. Status antrean, percobaan ulang, dan deduplikasi tetap keputusan server.

## 10. Fitur yang tidak boleh hilang

- Profil anonim dan akun tetap memakai route profil yang sama; avatar, riwayat, progres, dan penautan akun tetap bekerja.
- Interview disimpan sekali dan pilihan materi tetap mendukung banyak pilihan. Navigasi ulang tidak mengulang atau menghapus jawaban tersimpan.
- Anonim dapat bergabung/menjawab kuis; pembuatan kuis tetap meminta login melalui modal.
- Host, kepemilikan kuis, larangan terlambat bergabung, room nonaktif, dan hak admin tetap diperiksa server.
- Jangan mengirim kunci jawaban/soal masa depan untuk memberi efek animasi lebih cepat. Feedback, skor, dan hasil resmi berasal dari server.
- Editor tetap mempertahankan ID soal, audio, duplikasi, arsip, keyboard, dan pengaturan durasi yang berlaku saat itu.
- Keyboard Aksara Bali dan audio privat tetap dapat digunakan pada mobile. Jangan mereset tulisan atau menghentikan audio karena render kecil.
- Toast hanya satu pengelola per shell; pertahankan posisi atas dan gaya sukses hijau/pill. Jangan merender flash yang sama dua kali.
- Statistik admin dan progres pengguna berasal dari data nyata. Jangan membuat streak, angka online, atau skor contoh terlihat sebagai hasil pengguna.

## 11. SEO dan halaman publik

Pertahankan title, description, canonical, Open Graph, robots, sitemap, semantic HTML, dan URL yang sudah ada. Setiap halaman punya heading utama yang jelas; gunakan link/button sesuai fungsi.

Halaman publik yang diindeks sekarang dilayani React dengan SSR. Pastikan proses SSR tetap sehat setelah deployment. Jangan menggantinya dengan root kosong yang baru berisi konten setelah JavaScript berjalan. Jika halaman publik dipindahkan penuh ke Inertia React, siapkan dan uji SSR beserta fallback, metadata, hydration, serta proses production yang dibutuhkan. `Head` saja tidak menggantikan pengujian HTML awal. [SSR Inertia](https://inertiajs.com/docs/v3/advanced/server-side-rendering).

Kode yang dibaca saat SSR tidak mengakses `window`, `document`, atau sessionStorage di level modul. Inisialisasi fitur browser pada lifecycle client, dengan markup awal yang konsisten untuk mencegah hydration mismatch.

## 12. Urutan migrasi dan kriteria selesai

| Fase | Pekerjaan | Syarat sebelum lanjut |
| --- | --- | --- |
| 0 | Inventaris route/fitur, acuan visual, baseline performa, versi dependency | Perbedaan dokumen/style dicatat; UI lama tetap utuh |
| 1 | Root Inertia, entry React terpisah, satu halaman pilot, batas Blade–React | Direct visit, refresh, Back/Forward, auth, 404, dan rollback bekerja |
| 2 | Token, komponen dasar, shell pengguna, toast dan navigasi mobile | Desktop/mobile dan aksesibilitas lolos |
| 3 | Katalog, detail pelajaran, profil/dashboard, interview | Progres, identitas anonim, multi-select, upload, dan error form setara |
| 4 | Kuis, editor, room realtime, keyboard/audio | Multi-peserta, reconnect, timer, scoring, dan kontrol akses lolos |
| 5 | Admin dan analitik | Grafik terpisah dari bundle pengguna; data/otorisasi setara |
| 6 | Beranda dan halaman publik React, bila SSR siap | HTML awal, metadata, SEO, dan deployment SSR lolos |
| 7 | Cutover per route dan pengukuran ulang | Tidak ada regresi fitur/performa; referensi lama tetap disimpan |

Fase pilot telah selesai. Permintaan terbaru menetapkan cutover seluruh route sekaligus dan menghapus sakelar renderer. Checklist di atas dipakai untuk menilai area migrasi; batas verifikasi nyata tercatat pada bagian awal. Memasang React bukan alasan mengubah skema database atau menulis ulang backend.

Setiap fase harus mencakup:

- Tes PHP terhadap respons/props, validation, kepemilikan, dan data yang tidak boleh bocor. Pertahankan tes renderer Blade yang masih aktif.
- Tes komponen/interaksi hanya untuk state penting: form, multi-select, dialog, feedback, cleanup, dan error. Pilih alat React yang minimal pada fase fondasi; jangan menganggap tes VM DOM lama cukup untuk lifecycle React.
- Uji browser untuk batas Blade–React, submit ganda, navigasi Back, upload gagal, koneksi lambat/putus, chunk gagal, dan reload versi aset setelah deploy.
- Uji lebar 320, 360, 393, 440, 768, dan 1280+ px, orientasi landscape, safe area, keyboard virtual, fokus keyboard, dan reduced motion.
- Uji beberapa peserta room sekaligus, tab latar, reconnect, serta AI queued/processing/completed/failed. Animasi tidak menentukan hasil/timing server.
- Build production, pemeriksaan ukuran chunk/request, React Profiler, dan trace browser pada perangkat representatif. Catat metode, bukan hanya skor Lighthouse.
- Diff terarah, formatter, tes relevan, serta commit kecil sesuai `docs/COMMIT.md`. Perbarui `docs/DEPLOYMENT.md` ketika build/runtime/SSR benar-benar berubah.

Penghapusan UI Blade lama telah dilakukan atas permintaan pengguna setelah fase 7; referensi tetap tersedia di riwayat Git. Risiko sisa yang harus dicatat sebelum rilis: peningkatan bundle, konsumsi memori tab panjang, regresi aksesibilitas, state lintas navigasi, dan operasional SSR bila diaktifkan.
