import { PageMeta } from '../Components/ui';
export default function Privacy({ meta }) {
    return (
        <>
            <PageMeta {...meta} />
            <div className="mx-auto max-w-7xl px-5 pb-20 pt-12 sm:px-6 lg:px-10 lg:pt-16">
                <header className="max-w-3xl border-l-4 border-accent pl-5 sm:pl-7">
                    <h1 className="font-display text-4xl font-bold tracking-tight sm:text-5xl">Kebijakan privasi</h1>
                    <p className="mt-4 font-readable text-lg leading-8 text-foreground/70">
                        Apa yang ARTASWAHA kumpulkan, mengapa dibutuhkan, dan bagaimana kamu dapat mengontrol datamu.
                    </p>
                    <p className="mt-3 text-sm text-foreground/50">Diperbarui 27 Oktober 2026</p>
                </header>

                <div className="mt-12 grid gap-12 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-16">
                    <nav aria-label="Isi kebijakan privasi" className="hidden lg:block">
                        <div className="sticky top-32 border-l border-white/15 pl-5 text-sm">
                            <p className="mb-4 font-bold text-foreground">Di halaman ini</p>
                            <ul className="space-y-3 text-foreground/60">
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#data"
                                    >
                                        Data yang dikumpulkan
                                    </a>
                                </li>
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#tujuan"
                                    >
                                        Penggunaan data
                                    </a>
                                </li>
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#cookie"
                                    >
                                        Cookie
                                    </a>
                                </li>
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#layanan"
                                    >
                                        Layanan lain
                                    </a>
                                </li>
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#penyimpanan"
                                    >
                                        Penyimpanan
                                    </a>
                                </li>
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#kendali"
                                    >
                                        Kendalimu
                                    </a>
                                </li>
                                <li>
                                    <a
                                        className="transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                        href="#kontak"
                                    >
                                        Kontak
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>

                    <article className="max-w-3xl space-y-10 font-readable text-[1.05rem] leading-8 text-foreground/70">
                        <p>
                            ARTASWAHA menyediakan materi belajar dan kuis bersama. Kamu dapat belajar secara anonim di
                            satu browser atau membuat akun untuk mengaitkan progres dengan akunmu. Kami hanya
                            menjelaskan perilaku fitur yang tersedia saat kebijakan ini diperbarui; perubahan cara
                            pemrosesan data akan disertai pembaruan halaman ini.
                        </p>

                        <section
                            id="data"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="data-title"
                        >
                            <h2 id="data-title" className="font-display text-2xl font-bold text-foreground">
                                Data yang dikumpulkan
                            </h2>
                            <dl className="mt-5 space-y-5">
                                <div>
                                    <dt className="font-bold text-foreground">Saat belajar tanpa akun</dt>
                                    <dd>
                                        Nama panggilan, avatar acak atau gambar yang kamu unggah, pilihan materi, peran
                                        yang kamu pilih, dan progres pelajaran. ID acak dalam cookie menghubungkan data
                                        tersebut dengan browser ini; cookie tidak berisi jawaban interview atau isi
                                        profil.
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-bold text-foreground">Saat membuat akun atau masuk</dt>
                                    <dd>
                                        Nama, alamat email, kata sandi yang disimpan dalam bentuk hash, avatar jika kamu
                                        memilihnya, serta data profil dan progres belajar. Jika memakai Google untuk
                                        masuk, kami juga menyimpan ID akun Google yang diperlukan untuk menghubungkan
                                        login. Progres anonim di browser ini dapat ditautkan ke akun saat kamu masuk
                                        atau mendaftar.
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-bold text-foreground">Saat membuat atau mengikuti kuis</dt>
                                    <dd>
                                        Judul dan isi kuis, pertanyaan dan pilihan jawaban, nama tampilan peserta,
                                        jawaban yang dipilih, ketepatan, poin, serta waktu menjawab. Jika kamu
                                        mengunggah dokumen untuk membuat draf dengan AI, kami menyimpan hasil teks
                                        ekstraksi dan draf soal pada akun pembuatnya.
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-bold text-foreground">Saat menggunakan situs</dt>
                                    <dd>
                                        Cookie sesi dan keamanan, serta data teknis seperti alamat IP, jenis browser,
                                        dan aktivitas sesi yang dapat dicatat oleh aplikasi atau server untuk
                                        menjalankan dan menjaga layanan. Saat halaman terlihat, situs mengirim sinyal
                                        aktivitas berkala untuk menghitung pengunjung aktif dalam dua menit terakhir.
                                        Hanya hash ID akun atau sesi, jenis akun, dan waktu aktivitas yang disimpan
                                        untuk hitungan ini; tidak termasuk IP, nama, atau isi halaman. Jika pembuatan
                                        draf AI gagal, log aplikasi dapat memuat cuplikan respons AI; cuplikan itu
                                        mungkin berisi bagian dari materi yang diunggah.
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section
                            id="tujuan"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="tujuan-title"
                        >
                            <h2 id="tujuan-title" className="font-display text-2xl font-bold text-foreground">
                                Untuk apa data digunakan?
                            </h2>
                            <p className="mt-4">
                                Data dipakai untuk mengingat titik awal belajar dan progresmu, menyediakan akun dan
                                pemulihan kata sandi, menampilkan avatar, menjalankan kuis serta room, menghitung hasil
                                jawaban, memproses dokumen yang kamu unggah, dan menjaga keamanan serta keandalan
                                layanan.
                            </p>
                            <p className="mt-3">
                                Analisis belajar otomatis diperbarui sekitar setiap enam jam. Layanan AI yang
                                dikonfigurasi pengelola menerima peran, judul dan deskripsi materi pilihan, serta
                                ringkasan aktivitas 14 hari: tanggal aktivitas, jumlah pelajaran selesai, jumlah dan
                                ketepatan jawaban kuis, dan usia profil dalam hari. Nama, email, avatar, dan ID akun
                                atau perangkat tidak disertakan dalam permintaan ini. Ringkasan dan rekomendasi disimpan
                                pada profil. Hari aktif hanya mencakup pelajaran selesai atau jawaban kuis; analisis
                                tidak mengukur durasi membaca dan bukan penilaian kemampuan atau kepribadian.
                            </p>
                            <p className="mt-3">
                                Nama tampilan dan avatar peserta dapat terlihat oleh orang lain di room yang sama.
                                Jawaban dan hasil permainan digunakan untuk menjalankan sesi kuis. Jangan gunakan nama
                                lengkap anak atau unggah informasi sensitif yang tidak diperlukan.
                            </p>
                        </section>

                        <section
                            id="cookie"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="cookie-title"
                        >
                            <h2 id="cookie-title" className="font-display text-2xl font-bold text-foreground">
                                Cookie dan penyimpanan di browser
                            </h2>
                            <p className="mt-4">
                                <code className="rounded bg-surface px-1.5 py-0.5 text-sm text-accent">
                                    artaswaha_device
                                </code>{' '}
                                menyimpan ID perangkat acak untuk mengenali profil dan progres anonim. Masa berlakunya
                                365 hari sejak terakhir dikirim ulang. Cookie sesi dan keamanan digunakan untuk
                                mempertahankan login serta melindungi formulir; masa berlakunya mengikuti konfigurasi
                                sesi layanan.
                            </p>
                            <p className="mt-3">
                                Jika kamu menghapus cookie atau data situs, profil anonim lama mungkin tidak lagi dapat
                                dikenali, walaupun datanya belum otomatis terhapus dari server. Akun yang sudah
                                didaftarkan tetap dapat diakses dengan masuk kembali.
                            </p>
                        </section>

                        <section
                            id="layanan"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="layanan-title"
                        >
                            <h2 id="layanan-title" className="font-display text-2xl font-bold text-foreground">
                                Layanan lain yang terlibat
                            </h2>
                            <ul className="mt-4 list-disc space-y-3 pl-6 marker:text-accent">
                                <li>
                                    <strong className="text-foreground">Google:</strong> jika kamu memilih login Google,
                                    autentikasi berlangsung melalui Google. Font pada halaman juga dimuat dari Google
                                    Fonts, sehingga browser menghubungi server Google.
                                </li>
                                <li>
                                    <strong className="text-foreground">OCR dan AI:</strong> dokumen yang kamu unggah
                                    dikirim ke layanan OCR yang dikonfigurasi pengelola. Teks hasil ekstraksi dikirim ke
                                    gateway AI dan penyedia model yang dikonfigurasi untuk menyusun draf soal. Jangan
                                    unggah dokumen berisi data pribadi yang tidak ingin diproses oleh layanan tersebut.
                                </li>
                                <li>
                                    <strong className="text-foreground">Infrastruktur:</strong> penyedia hosting dan
                                    pengiriman email dapat memproses data teknis atau alamat email sejauh diperlukan
                                    untuk mengoperasikan situs dan mengirim tautan pemulihan akun.
                                </li>
                            </ul>
                        </section>

                        <section
                            id="penyimpanan"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="penyimpanan-title"
                        >
                            <h2 id="penyimpanan-title" className="font-display text-2xl font-bold text-foreground">
                                Berapa lama data disimpan?
                            </h2>
                            <p className="mt-4">
                                File sumber draf kuis disimpan sementara dan dihapus setelah pekerjaan OCR/AI selesai
                                atau gagal. Selama pekerjaan masih mengantre, file tersebut masih tersimpan. Teks hasil
                                ekstraksi dan draf soal tetap tersimpan pada data draf.
                            </p>
                            <p className="mt-3">
                                Saat ini belum ada jadwal penghapusan otomatis untuk akun, profil anonim, progres, kuis,
                                dan draf. Catatan aktivitas untuk hitungan online dibersihkan secara berkala setelah
                                lebih dari satu hari. Menghapus room menghapus data peserta dan jawaban yang terkait
                                dengan room tersebut. Masa simpan log dan cadangan server bergantung pada pengelolaan
                                infrastruktur; penghapusan cookie di browser tidak menghapus data di server.
                            </p>
                        </section>

                        <section
                            id="kendali"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="kendali-title"
                        >
                            <h2 id="kendali-title" className="font-display text-2xl font-bold text-foreground">
                                Pilihan dan kendalimu
                            </h2>
                            <p className="mt-4">
                                Kamu boleh memilih belajar tanpa akun, tidak mengunggah avatar atau dokumen, dan keluar
                                dari akun kapan saja. Untuk meminta akses, koreksi, atau penghapusan data, kirim email
                                ke alamat di bawah. Kami mungkin perlu memastikan bahwa kamu adalah pemilik akun atau
                                profil sebelum memproses permintaan.
                            </p>
                            <p className="mt-3">
                                Akun terdaftar dan profil anonim dapat dihapus melalui halaman Profil, bagian Hapus
                                akun. Buka konfirmasi, lalu ketik persis “Hapus Akun Saya” dan pilih Hapus permanen.
                                Penghapusan menghapus profil, avatar unggahan, jawaban perkenalan, analisis, progres
                                belajar, dan partisipasi serta jawaban kuis milikmu dari basis data aktif. Untuk akun
                                terdaftar, akun, tautan login Google yang tersimpan, token pemulihan kata sandi, draf
                                dan dokumen unggahan, kuis, audio kuis, serta room buatanmu beserta peserta dan jawaban
                                di dalamnya ikut dihapus. Materi pembelajaran platform tidak ikut dihapus.
                            </p>
                            <p className="mt-3">
                                Setelah penghapusan, sesi login berakhir dan cookie pengenal profil di browser ini
                                dihapus. Penghapusan profil anonim hanya berlaku untuk data yang dikenali melalui profil
                                browser tersebut; tidak menghapus akun atau profil pengguna lain. Jika cookie sudah
                                hilang, hubungi pengelola agar permintaan dapat diverifikasi. Tindakan ini tidak dapat
                                dibatalkan melalui aplikasi. Menghapus cookie saja tidak menghapus data di server.
                            </p>
                            <p className="mt-3">
                                Statistik agregat tanpa identitas pribadi dapat tetap tersimpan. Log keamanan, cuplikan
                                log teknis, cadangan server, dan data yang telah diterima penyedia layanan lain tidak
                                otomatis terhapus oleh fitur ini; masa simpannya mengikuti pengelolaan infrastruktur
                                atau penyedia tersebut. Jika pembersihan file gagal karena gangguan penyimpanan,
                                kegagalan dicatat untuk ditangani pengelola. Hubungi email di bawah untuk pertanyaan
                                atau permintaan lanjutan terkait data tersebut.
                            </p>
                        </section>

                        <section
                            id="kontak"
                            className="scroll-mt-32 border-t border-white/15 pt-8"
                            aria-labelledby="kontak-title"
                        >
                            <h2 id="kontak-title" className="font-display text-2xl font-bold text-foreground">
                                Hubungi pengelola
                            </h2>
                            <p className="mt-4">
                                Pertanyaan atau permintaan terkait data pribadi dapat dikirim ke pengelola ARTASWAHA di{' '}
                                <a
                                    href="mailto:kerthajayasoftware@gmail.com"
                                    className="break-all font-bold text-accent underline underline-offset-4 hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent"
                                >
                                    kerthajayasoftware@gmail.com
                                </a>
                                .
                            </p>
                        </section>
                    </article>
                </div>
            </div>
        </>
    );
}
