import { Link, usePage } from '@inertiajs/react';
import { PageMeta, buttonClass } from '../Components/ui';
import Icon from '../Components/Icon';

const topics = [
    ['ringkasan', 'Ringkasan'],
    ['data', 'Data yang digunakan'],
    ['ai', 'AI & audio'],
    ['cookie', 'Cookie & sesi'],
    ['akses', 'Siapa yang mengakses'],
    ['penyimpanan', 'Masa penyimpanan'],
    ['kendali', 'Hak & penghapusan'],
    ['keamanan', 'Keamanan & anak'],
    ['pertanyaan', 'Pertanyaan umum'],
    ['kontak', 'Kontak'],
];
const dataUses = [
    [
        'user',
        'Profil anonim',
        'Nama panggilan, avatar, dan pilihan belajar',
        'Kami menyimpan nama panggilan, avatar acak atau unggahan, peran, pilihan materi, ID profil/browser acak, serta waktu profil dibuat dan diperbarui. Jawaban perkenalan dipakai untuk menyesuaikan rekomendasi. Profil dan progres tersimpan di server, bukan hanya di perangkat; cookie menghubungkannya ke browser yang sama.',
    ],
    [
        'login',
        'Akun & login Google',
        'Identitas akun dan akses lintas perangkat',
        'Akun menyimpan nama, email, hash kata sandi, avatar, status verifikasi email, dan informasi autentikasi yang diperlukan. Login Google menggunakan nama, email terverifikasi, dan ID Google untuk membuat atau menghubungkan akun. Kami tidak meminta kata sandi Google. Progres serta profil anonim di browser ini dapat ditautkan ketika kamu masuk atau mendaftar.',
    ],
    [
        'book',
        'Pelajaran & progres',
        'Pelajaran selesai, latihan, dan riwayat',
        'Kami menyimpan hubungan akun atau ID browser dengan pelajaran, status penyelesaian, jawaban latihan, ketepatan jawaban, dan waktu aktivitas yang tercatat. Data ini menampilkan progres, riwayat terakhir, serta ringkasan hari aktif. Sistem belum mengukur durasi membaca atau seluruh waktu belajar. Untuk diagnosis layanan, pengelola dapat melihat 20 kejadian analisis terakhir per profil, termasuk waktu, status, jenis error/HTTP, dan hasil AI yang berhasil divalidasi. Riwayat ini ikut terhapus bersama profil; respons mentah dan API key tidak disalin ke riwayat.',
    ],
    [
        'quiz',
        'Kuis & room',
        'Partisipasi, jawaban, waktu, dan poin',
        'Kami menyimpan kuis dan pertanyaan buatanmu, opsi serta kunci jawaban, pengaturan timer/feedback, room, peserta, jawaban terpilih (satu atau beberapa pilihan), teks jawaban esai, ketepatan, poin, dan waktu menjawab. Esai dinilai dengan jawaban acuan pembuat soal, bukan AI; hindari memasukkan data pribadi ke jawaban. Nama dan avatar terlihat oleh peserta dalam room yang sama; hasil ditampilkan sesuai pengaturan sesi. Anonim dapat bergabung dan menjawab, sedangkan membuat kuis memerlukan akun.',
    ],
    [
        'spark',
        'Dokumen & draf AI',
        'File unggahan, teks ekstraksi, dan draf soal',
        'Jika kamu memilih pembuatan draf dari dokumen, file disimpan sementara di penyimpanan privat, dikirim ke layanan OCR, lalu teksnya dikirim ke layanan AI. Teks ekstraksi, draf pertanyaan, status, dan hasil pemrosesan disimpan pada draf pembuatnya. Isi dokumen dapat mengandung data pribadi; gunakan materi yang berhak kamu unggah dan hapus informasi sensitif terlebih dahulu.',
    ],
    [
        'chart',
        'Aktivitas teknis & analitik',
        'Sesi, keamanan, dan hitungan pengunjung',
        'Sesi/server dapat mencatat alamat IP, jenis browser, waktu akses, dan informasi teknis untuk autentikasi, pembatasan permintaan, serta diagnosis gangguan. Saat halaman terlihat, sinyal aktivitas dikirim berkala. Hitungan online hanya menyimpan hash ID akun atau sesi, jenis akun, dan waktu terakhir aktif; bukan nama, IP, atau isi halaman. Statistik admin memakai data akun, profil, penyelesaian pelajaran, jawaban kuis, serta status room/AI/audio.',
    ],
];
const retention = [
    [
        'Cookie profil anonim',
        '365 hari sejak terakhir dikirim ulang; pengaturan browser dapat menghapusnya lebih awal. Masa cookie bukan batas penyimpanan profil di server.',
    ],
    [
        'Akun, profil, progres & hasil AI',
        'Belum ada jadwal penghapusan otomatis umum. Data tersimpan sampai dihapus melalui fitur yang tersedia atau ditangani pengelola.',
    ],
    [
        'File sumber draf kuis',
        'Dihapus setelah pekerjaan OCR/AI dijalankan dan selesai atau gagal. File yang masih mengantre dapat tetap tersimpan; gangguan penyimpanan dapat memerlukan pembersihan pengelola. Teks ekstraksi dan draf tidak ikut terhapus hanya karena file sumber dihapus.',
    ],
    [
        'Room & jawaban peserta',
        'Room yang nonaktif tidak otomatis dihapus. Menghapus room menghapus peserta dan jawaban terkait. Penghapusan akun juga memengaruhi room milik akun tersebut.',
    ],
    [
        'Catatan pengunjung online',
        'Aktif berarti menerima sinyal dalam dua menit terakhir. Catatan lebih dari satu hari dibersihkan pada sebagian permintaan aktivitas; ini bukan jaminan penghapusan tepat setelah 24 jam.',
    ],
    [
        'Audio, log & cadangan',
        'Audio disimpan untuk pemutaran ulang sesuai konten terkait. Tidak ada masa simpan tunggal yang dapat dijanjikan untuk log, cadangan, atau data pada penyedia lain; pengaturannya mengikuti infrastruktur dan layanan yang digunakan.',
    ],
];
const questions = [
    [
        'Apakah anonim berarti tidak ada data di server?',
        'Tidak. Anonim berarti kamu tidak perlu email dan kata sandi. Nama panggilan, avatar, pilihan belajar, dan progres tetap disimpan di server dengan pengenal browser acak. Data ini tetap perlu dilindungi.',
    ],
    [
        'Apakah menghapus cookie sama dengan menghapus akun?',
        'Tidak. Menghapus cookie dapat memutus akses ke profil anonim tanpa menghapus datanya di server. Gunakan Hapus akun pada halaman Profil terlebih dahulu. Jika cookie sudah hilang, hubungi pengelola untuk pemeriksaan kepemilikan.',
    ],
    [
        'Apakah AI menerima nama dan email saya?',
        'Permintaan analisis belajar tidak menyertakan nama, email, avatar, atau ID akun/browser. Namun, dokumen untuk draf soal dikirim melalui OCR/AI sesuai isi yang kamu unggah; jika di dalamnya ada nama atau informasi pribadi, informasi itu dapat ikut diproses.',
    ],
    [
        'Bisakah saya mematikan analisis AI saja?',
        'Saat ini belum ada tombol khusus untuk menonaktifkan analisis otomatis pada satu profil. Hubungi pengelola untuk meminta pembatasan atau menyampaikan keberatan. Penghapusan profil menghentikan pemrosesan berikutnya untuk profil tersebut, tetapi tidak menarik kembali permintaan yang sudah diterima penyedia lain.',
    ],
    [
        'Apakah situs merekam suara atau lokasi saya?',
        'Fitur pembelajaran saat ini tidak meminta akses mikrofon, kamera, atau lokasi presisi. Audio pembelajaran dibuat dari teks, bukan rekaman suaramu. Gambar/dokumen hanya diproses ketika kamu memilih unggahan; IP pada sesi atau log tetap dapat menunjukkan perkiraan jaringan/lokasi.',
    ],
    [
        'Apakah ada pelacak iklan?',
        'Kode aplikasi saat ini tidak memasang piksel iklan atau analitik pemasaran pihak ketiga. Analitik pengelola berasal dari data penggunaan di aplikasi. Google Fonts dan layanan eksternal yang dipakai fitur tertentu tetap menerima permintaan teknis.',
    ],
];
const sectionClass = 'scroll-mt-28 space-y-5';
const textClass = 'text-sm leading-7 text-foreground/65 sm:text-base sm:leading-8';
const linkClass =
    'font-semibold text-accent underline underline-offset-4 hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent';

export default function Privacy({ meta }) {
    const { navigation } = usePage().props;
    return (
        <div className="mx-auto max-w-6xl pb-8">
            <PageMeta {...meta} />
            <header className="py-5 sm:py-9">
                <p className="mb-3 flex items-center gap-2 text-sm font-semibold text-accent">
                    <Icon name="info" /> Privasi di ARTASWAHA
                </p>
                <h1 className="font-display text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                    Kebijakan privasi
                </h1>
                <p className="mt-4 max-w-2xl text-base leading-8 text-foreground/65 sm:text-lg">
                    Kebijakan privasi ini menjelaskan data yang dipakai saat kamu belajar, bermain kuis, atau
                    menggunakan AI, beserta pilihan untuk mengelolanya.
                </p>
                <p className="mt-4 text-xs text-foreground/45">
                    Diperbarui <time dateTime="2026-10-27">27 Oktober 2026</time> · Berlaku untuk situs ARTASWAHA
                </p>
            </header>

            <section
                id="ringkasan"
                aria-labelledby="summary-title"
                className="scroll-mt-28 rounded-card bg-surface p-5 sm:p-7"
            >
                <h2 id="summary-title" className="font-display text-xl font-bold">
                    Yang perlu kamu tahu terlebih dahulu
                </h2>
                <div className="mt-6 grid gap-6 md:grid-cols-3">
                    {[
                        [
                            'user',
                            'Anonim tetap punya data',
                            'Cookie mengenali browsermu. Profil dan progresnya tersimpan di server ARTASWAHA.',
                        ],
                        [
                            'spark',
                            'AI menerima data sesuai fitur',
                            'Analisis memakai ringkasan belajar. Draf soal memakai teks dokumen yang kamu unggah.',
                        ],
                        [
                            'edit',
                            'Kamu punya kendali',
                            'Kelola avatar, minta salinan atau koreksi, dan hapus akun dari halaman Profil.',
                        ],
                    ].map(([icon, title, text]) => (
                        <div key={title} className="min-w-0">
                            <span className="mb-3 grid size-10 place-items-center rounded-full bg-primary/20 text-accent">
                                <Icon name={icon} />
                            </span>
                            <h3 className="font-semibold">{title}</h3>
                            <p className="mt-2 text-sm leading-7 text-foreground/55">{text}</p>
                        </div>
                    ))}
                </div>
            </section>

            <div className="mt-8 grid items-start gap-8 lg:mt-12 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-12">
                <nav aria-label="Isi kebijakan privasi" className="min-w-0 lg:sticky lg:top-28">
                    <p className="mb-3 text-xs font-semibold text-foreground/45">Jelajahi kebijakan</p>
                    <ul className="flex gap-2 overflow-x-auto pb-2 lg:grid lg:gap-1">
                        {topics.map(([id, title]) => (
                            <li key={id} className="shrink-0">
                                <a
                                    href={`#${id}`}
                                    className="flex min-h-11 items-center rounded-full bg-surface px-4 text-xs font-semibold text-foreground/60 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent lg:bg-transparent"
                                >
                                    {title}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>

                <article aria-label="Rincian kebijakan privasi" className="min-w-0 space-y-12 sm:space-y-14">
                    <section id="data" aria-labelledby="data-title" className={sectionClass}>
                        <div>
                            <h2 id="data-title" className="font-display text-2xl font-bold">
                                Data apa yang digunakan?
                            </h2>
                            <p className={`mt-3 ${textClass}`}>
                                Pengunjung baru perlu mengisi perkenalan dan memilih profil anonim atau akun sebelum
                                membuka konten. Jawabannya disimpan agar tidak perlu diulang. Login akun yang sudah ada
                                dan kebijakan privasi tetap bisa dibuka. Buka tiap bagian untuk melihat data dan
                                tujuannya; menggunakan satu fitur tidak berarti semua jenis data di bawah selalu
                                dikumpulkan.
                            </p>
                        </div>
                        <div className="space-y-3">
                            {dataUses.map(([icon, title, summary, text]) => (
                                <details key={title} className="group rounded-3xl bg-surface p-5 sm:p-6">
                                    <summary className="flex min-h-11 cursor-pointer list-none items-center gap-3 rounded-xl focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-full bg-primary/20 text-accent">
                                            <Icon name={icon} />
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block font-semibold">{title}</span>
                                            <span className="mt-1 block text-xs leading-5 text-foreground/45">
                                                {summary}
                                            </span>
                                        </span>
                                        <Icon
                                            name="chevron"
                                            className="size-4 shrink-0 text-foreground/40 transition-transform group-open:rotate-180"
                                        />
                                    </summary>
                                    <p className={`mt-4 ${textClass}`}>{text}</p>
                                </details>
                            ))}
                        </div>
                    </section>

                    <section id="ai" aria-labelledby="ai-title" className={sectionClass}>
                        <h2 id="ai-title" className="font-display text-2xl font-bold">
                            Cara kerja AI dan audio
                        </h2>
                        <div className="rounded-card bg-primary/10 p-5 sm:p-6">
                            <h3 className="font-semibold text-accent">Analisis belajar otomatis</h3>
                            <p className={`mt-3 ${textClass}`}>
                                Sistem menjadwalkan pembaruan sekitar setiap enam jam dan dapat meminta pembaruan saat
                                profil digunakan, sesuai kondisi antrean dan konfigurasi AI. Data yang dikirim: peran,
                                judul/deskripsi materi pilihan, usia profil dalam hari, serta ringkasan aktivitas 14
                                hari berisi tanggal, pelajaran selesai, jumlah dan ketepatan jawaban kuis, dan
                                perbandingan dua periode tujuh hari. Nama, email, avatar, dan ID akun/browser tidak
                                dimasukkan dalam permintaan analisis ini.
                            </p>
                        </div>
                        <p className={textClass}>
                            Masukan analisis, status proses, ringkasan, rekomendasi, serta waktu permintaan dan
                            penyelesaian disimpan pada profil. Hari aktif berarti ada pelajaran selesai atau jawaban
                            kuis; hasil AI tidak mengukur seluruh kebiasaan, durasi membaca, kemampuan, atau
                            kepribadian. Rekomendasi bisa keliru dan tidak digunakan sebagai keputusan otomatis untuk
                            membatasi aksesmu.
                        </p>
                        <p className={textClass}>
                            Untuk draf kuis, file dikirim ke OCR dan teks ekstraksinya diteruskan ke gateway AI/penyedia
                            model. Pengelola juga dapat meminta interpretasi statistik platform berupa jumlah akun,
                            perkenalan, penyelesaian, jawaban, kategori peran, dan materi per periode, tanpa menyertakan
                            daftar nama atau email.
                        </p>
                        <p className={textClass}>
                            Audio dibuat dari teks materi atau soal melalui mesin TTS lokal atau layanan TTS yang
                            dikonfigurasi. Teks dapat diteruskan ke layanan tersebut dan hasil audionya disimpan untuk
                            diputar ulang. Ini bukan perekaman suara pengguna.
                        </p>
                        <p className={textClass}>
                            Analisis otomatis belum memiliki pengaturan nonaktif per profil. Kamu dapat menyampaikan
                            keberatan atau meminta pembatasan melalui kontak pengelola. Kami tidak menjanjikan bahwa
                            penyedia model tidak menyimpan atau menggunakan masukan untuk pelatihan: nama penyedia,
                            lokasi pemrosesan, dan ketentuan retensinya bergantung pada layanan yang dipakai dan perlu
                            dikonfirmasi kepada pengelola.
                        </p>
                    </section>

                    <section id="cookie" aria-labelledby="cookie-title" className={sectionClass}>
                        <h2 id="cookie-title" className="font-display text-2xl font-bold">
                            Cookie, sesi, dan browser
                        </h2>
                        <dl className="space-y-5">
                            <div>
                                <dt className="font-semibold">
                                    <code className="break-all text-sm text-accent">artaswaha_device</code>
                                </dt>
                                <dd className={`mt-2 ${textClass}`}>
                                    ID acak untuk menghubungkan profil dan progres anonim ke browser. Cookie ini bukan
                                    isi profil atau jawaban perkenalan. Masa berlakunya 365 hari sejak dikirim ulang;
                                    pengenal dikirim bersama permintaan ke situs dan tidak membaca data perangkat lain.
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold">Sesi, keamanan formulir, dan login berkelanjutan</dt>
                                <dd className={`mt-2 ${textClass}`}>
                                    Cookie sesi menjaga status masuk; token keamanan membantu melindungi formulir.
                                    Setelah daftar atau masuk lewat email maupun Google, cookie login jangka panjang
                                    diaktifkan otomatis agar akun tetap dikenali ketika sesi singkat berakhir. Cookie
                                    ini memakai token autentikasi, bukan kata sandi asli. Masa berlaku bawaan hingga
                                    sekitar 400 hari dan mengikuti pengaturan browser; ini bukan jaminan login
                                    selamanya. Keluar, penghapusan akun, atau reset kata sandi dapat mengakhiri akses
                                    tersebut. Menghapus cookie atau menggunakan browser/perangkat lain dapat
                                    mengharuskan login kembali. Selalu gunakan Keluar pada perangkat bersama.
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold">Data lokal bukan salinan lengkap akun</dt>
                                <dd className={`mt-2 ${textClass}`}>
                                    Tampilan, riwayat navigasi, dan aset dapat tersimpan sementara di memori atau cache
                                    browser. Profil dan progres utama tidak disimpan sebagai akun lengkap di
                                    localStorage. Menghapus data situs dapat menghilangkan pengenal anonim, tetapi tidak
                                    otomatis menghapus catatan server.
                                </dd>
                            </div>
                        </dl>
                        <p className={textClass}>
                            Memblokir cookie dapat mengganggu login, profil anonim, penyimpanan progres, dan pengiriman
                            formulir. Keluar dari akun mengakhiri sesi login; untuk menghapus data, gunakan fitur
                            penghapusan atau hubungi pengelola.
                        </p>
                    </section>

                    <section id="akses" aria-labelledby="akses-title" className={sectionClass}>
                        <h2 id="akses-title" className="font-display text-2xl font-bold">
                            Siapa yang dapat mengakses data?
                        </h2>
                        <ul className={`space-y-4 ${textClass}`}>
                            <li>
                                <strong className="text-foreground">Kamu dan peserta room:</strong> kamu mengakses
                                profil/progresmu. Peserta room melihat nama, avatar, serta informasi permainan yang
                                ditampilkan sesuai sesi. Gunakan nama panggilan dan hindari informasi sensitif pada nama
                                atau soal.
                            </li>
                            <li>
                                <strong className="text-foreground">Pengelola yang berwenang:</strong> mengelola materi,
                                meninjau profil/perkenalan, hasil analisis, draf, statistik, dan gangguan layanan
                                melalui akses admin. Hitungan online pada dashboard berupa jumlah, bukan daftar alamat
                                IP atau nama pengunjung.
                            </li>
                            <li>
                                <strong className="text-foreground">Google:</strong> login Google melibatkan layanan
                                autentikasi Google; font juga dimuat dari Google Fonts sehingga browser mengirim
                                permintaan teknis ke Google. Lihat{' '}
                                <a href="https://policies.google.com/privacy?hl=id" className={linkClass}>
                                    kebijakan privasi Google
                                </a>
                                .
                            </li>
                            <li>
                                <strong className="text-foreground">OCR, AI, TTS, hosting, dan email:</strong>{' '}
                                masing-masing memproses file, teks, data teknis, atau email yang diperlukan fitur
                                terkait. Pengiriman dapat melibatkan gateway dan penyedia model di luar server
                                ARTASWAHA.
                            </li>
                        </ul>
                        <p className={textClass}>
                            Kode aplikasi saat ini tidak memasang pelacak iklan atau menjual data melalui fitur
                            aplikasi. Lokasi hosting, negara pemrosesan pihak ketiga, masa simpan cadangan, dan
                            kebijakan penyedia yang sedang aktif belum dirinci di halaman ini. Hubungi pengelola sebelum
                            mengirim materi sensitif jika kamu membutuhkan kepastian tersebut.
                        </p>
                    </section>

                    <section id="penyimpanan" aria-labelledby="penyimpanan-title" className={sectionClass}>
                        <h2 id="penyimpanan-title" className="font-display text-2xl font-bold">
                            Berapa lama data disimpan?
                        </h2>
                        <dl className="space-y-3">
                            {retention.map(([title, text]) => (
                                <div key={title} className="rounded-3xl bg-surface p-5">
                                    <dt className="font-semibold">{title}</dt>
                                    <dd className={`mt-2 ${textClass}`}>{text}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>

                    <section id="kendali" aria-labelledby="kendali-title" className={sectionClass}>
                        <h2 id="kendali-title" className="font-display text-2xl font-bold">
                            Pilihan dan hak atas datamu
                        </h2>
                        <p className={textClass}>
                            Kamu dapat belajar tanpa email melalui profil anonim, memilih avatar bawaan, dan tidak
                            menggunakan unggahan dokumen atau login Google. Memakai fitur-fitur tersebut akan memproses
                            data yang dijelaskan di atas untuk menjalankan fitur terkait.
                        </p>
                        <p className={textClass}>
                            Untuk meminta informasi, akses atau salinan data, koreksi, penghapusan, pembatasan, atau
                            menyampaikan keberatan atas pemrosesan, hubungi pengelola. Penarikan persetujuan untuk
                            pemrosesan yang berdasar persetujuan dapat disampaikan melalui kontak yang sama. Permintaan
                            diperiksa sesuai hak yang berlaku dan dapat memerlukan verifikasi kepemilikan; jangan kirim
                            kata sandi atau kode login. Belum tersedia unduhan data otomatis di aplikasi.
                        </p>
                        <div className="rounded-card bg-surface p-5 sm:p-6">
                            <h3 className="font-display text-xl font-bold">Menghapus akun atau profil anonim</h3>
                            <ol className="mt-4 list-decimal space-y-3 pl-5 text-sm leading-7 text-foreground/65 marker:font-semibold marker:text-accent">
                                <li>Buka halaman Profil, lalu bagian Hapus akun.</li>
                                <li>
                                    Ketik persis <strong className="text-foreground">Hapus Akun Saya</strong> pada
                                    konfirmasi.
                                </li>
                                <li>Pilih Hapus permanen. Tindakan ini tidak dapat dibatalkan melalui aplikasi.</li>
                            </ol>
                            <Link href={navigation.profile} className={`${buttonClass} mt-5`}>
                                Buka profil <Icon name="arrow" className="size-4" />
                            </Link>
                        </div>
                        <p className={textClass}>
                            Penghapusan membersihkan profil, avatar unggahan, perkenalan, analisis, progres, serta
                            partisipasi dan jawaban kuis milikmu dari basis data aktif. Untuk akun terdaftar, akun,
                            tautan login Google yang tersimpan, token pemulihan, draf dan dokumen, kuis/audio kuis,
                            serta room milikmu beserta peserta dan jawabannya ikut dihapus. Materi pembelajaran platform
                            tetap ada. Sesi berakhir dan cookie pengenal pada browser ini dihapus.
                        </p>
                        <p className={textClass}>
                            Profil anonim hanya dapat dihapus untuk identitas yang dikenali browser tersebut. Bila
                            cookie hilang, hubungi pengelola agar permintaan dapat diverifikasi. Penghapusan di aplikasi
                            tidak otomatis membersihkan log, cadangan, laporan agregat yang telah dibuat, atau data yang
                            telah diterima penyedia lain. Kegagalan pembersihan file dicatat untuk ditangani pengelola.
                        </p>
                        <p className="text-xs leading-6 text-foreground/45">
                            Rujukan hak subjek data:{' '}
                            <a
                                className={linkClass}
                                href="https://jdih.komdigi.go.id/produk_hukum/view/id/832/t/crc32/"
                            >
                                UU Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi
                            </a>
                            .
                        </p>
                    </section>

                    <section id="keamanan" aria-labelledby="keamanan-title" className={sectionClass}>
                        <h2 id="keamanan-title" className="font-display text-2xl font-bold">
                            Keamanan dan pengguna anak
                        </h2>
                        <p className={textClass}>
                            Kata sandi akun disimpan sebagai hash. Akses admin, perubahan akun, jawaban room, avatar
                            unggahan, dan audio privat diperiksa oleh server sesuai hak akses. Pembatasan permintaan dan
                            token formulir membantu mengurangi penyalahgunaan. Langkah ini tidak berarti setiap risiko
                            keamanan dapat dihilangkan.
                        </p>
                        <p className={textClass}>
                            Log analisis dapat memuat ID profil internal, status antrean, waktu, jenis error, nama
                            model, dan host penyedia. Kegagalan pembuatan draf dapat mencatat cuplikan respons AI yang
                            mengandung materi unggahan. Log server atau kegagalan aplikasi juga dapat memuat informasi
                            teknis lain. Jangan mengunggah dokumen pribadi sensitif yang tidak diperlukan untuk belajar.
                        </p>
                        <p className={textClass}>
                            Pelajar anak sebaiknya menggunakan layanan dengan pendampingan orang tua, wali, atau guru.
                            Gunakan nama panggilan dan avatar bawaan; hindari nama lengkap anak, alamat, nomor
                            identitas, data kesehatan, dan informasi sekolah yang tidak diperlukan. Orang tua/wali dapat
                            menghubungi pengelola terkait data anak dengan verifikasi yang sesuai.
                        </p>
                    </section>

                    <section id="pertanyaan" aria-labelledby="faq-title" className={sectionClass}>
                        <h2 id="faq-title" className="font-display text-2xl font-bold">
                            Pertanyaan yang sering muncul
                        </h2>
                        <div className="space-y-3">
                            {questions.map(([title, text]) => (
                                <details key={title} className="group rounded-3xl bg-surface p-5 sm:p-6">
                                    <summary className="flex min-h-11 cursor-pointer list-none items-center gap-4 rounded-xl text-sm font-semibold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                                        <span className="min-w-0 flex-1">{title}</span>
                                        <Icon
                                            name="chevron"
                                            className="size-4 shrink-0 text-accent transition-transform group-open:rotate-180"
                                        />
                                    </summary>
                                    <p className={`mt-3 ${textClass}`}>{text}</p>
                                </details>
                            ))}
                        </div>
                    </section>

                    <section
                        id="kontak"
                        aria-labelledby="contact-title"
                        className="scroll-mt-28 rounded-card bg-primary/10 p-5 sm:p-7"
                    >
                        <span className="mb-4 grid size-11 place-items-center rounded-full bg-primary/20 text-accent">
                            <Icon name="mail" />
                        </span>
                        <h2 id="contact-title" className="font-display text-2xl font-bold">
                            Bicarakan datamu dengan kami
                        </h2>
                        <p className={`mt-3 ${textClass}`}>
                            Pengelola layanan: ARTASWAHA. Untuk pertanyaan, koreksi, salinan, penghapusan, atau keluhan,
                            kirim email dengan menyebutkan fitur dan jenis permintaanmu. Jangan sertakan kata sandi atau
                            dokumen sensitif sebelum diminta melalui proses verifikasi yang sesuai.
                        </p>
                        <a
                            href="mailto:kerthajayasoftware@gmail.com"
                            className={`mt-5 inline-flex min-h-11 max-w-full items-center break-all text-sm ${linkClass}`}
                        >
                            kerthajayasoftware@gmail.com
                        </a>
                        <p className="mt-4 text-xs leading-6 text-foreground/45">
                            Perubahan pemrosesan akan dijelaskan melalui pembaruan kebijakan beserta tanggalnya. Tinjau
                            halaman ini kembali ketika menggunakan fitur baru. Halaman ini menjelaskan fitur yang
                            tersedia, tanpa menjanjikan masa simpan atau praktik penyedia yang belum dikonfirmasi.
                        </p>
                    </section>
                </article>
            </div>
        </div>
    );
}
