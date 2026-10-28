<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParibasaLessonSeeder extends Seeder
{
    private const PARIBASA = 'https://id.scribd.com/doc/294471338/Paribasa-Basa-Bali';

    private const SESONGGAN = 'https://id.scribd.com/document/399384717/Bahasa-Bali';

    private const CECIMPEDAN = 'https://id.scribd.com/document/645712970/CECIMPEDAN';

    private const MAHADEWA = 'https://repo.mahadewa.ac.id/id/eprint/2447/1/BUKU%20KASUSASTRAN%20BALI.pdf';

    private const PANTUN = "Clebingkah beten biu\nBlahan pane blahan paso\nGumi linggah ajak liu\nAda kene ada keto";

    private const PANTUN_BELAJAR = "Kedis curik ninggahin gajah\nAda nolih ia makeber\nEnu cerik anteng malajah\nSuba kelih dadi dokter";

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->paths() as $pathData) {
                $path = LearningPath::query()->firstOrCreate(['slug' => $pathData['slug']], [
                    'group' => 'paribasa', 'title' => $pathData['title'], 'description' => $pathData['description'],
                    'sort_order' => $pathData['order'], 'is_published' => true,
                ]);
                foreach ($pathData['modules'] as $index => $module) {
                    $sources = array_unique([...$module['sources'], self::MAHADEWA]);
                    $references = "## Sumber belajar\nRujukan konsep dan contoh tradisional:\n".implode("\n", $sources);
                    $references .= "\n\nPetunjuk, skenario, pilihan jawaban, dan pembahasan latihan ditulis ulang untuk ARTASWAHA. Bukan salinan bank soal. Penjelasan menggunakan Bahasa Indonesia, istilah dan contoh menggunakan Bahasa Bali. Variasi ejaan dan jawaban dapat berbeda antardaerah; latihan ini memakai jawaban acuan pada pembahasan.";
                    Lesson::query()->firstOrCreate(['slug' => $pathData['slug'].'-'.$module['slug']], [
                        'learning_path_id' => $path->id, 'title' => $module['title'], 'summary' => $module['summary'],
                        'sort_order' => $index + 1, 'content' => ($module['material'] === '' ? '' : '## '.$module['title']."\n".$module['material']."\n\n").$references,
                        'exercises' => $this->questions($module['facts']),
                        'keyboard_auto_open' => false, 'is_published' => true, 'reviewed_at' => now(),
                    ]);
                }
            }
        });
    }

    /** @param list<array{string, string, string}> $facts
     * @return list<array<string, mixed>>
     */
    private function questions(array $facts): array
    {
        $questions = [];
        foreach ([false, true] as $reverse) {
            foreach ($facts as $index => [$prompt, $answer, $explanation]) {
                $questions[] = [
                    'section' => 0, 'question_type' => 'single', 'keyboard_auto_open' => false,
                    'exercise_prompt' => $reverse ? 'Manakah pertanyaan atau petunjuk yang cocok dengan jawaban “'.$answer.'”?' : $prompt,
                    'exercise_options' => array_column($facts, $reverse ? 0 : 1), 'correct_option' => $index,
                    'exercise_explanation' => $explanation,
                ];
            }
        }
        $questions[] = [
            'section' => 0, 'question_type' => 'essay', 'keyboard_auto_open' => false,
            'exercise_prompt' => $facts[0][0].' Ketik jawaban singkat yang digunakan dalam latihan sebelumnya.',
            'exercise_options' => [], 'accepted_answers' => [$facts[0][1]], 'exercise_explanation' => $facts[0][2],
        ];
        $questions[] = [
            'section' => 0, 'question_type' => 'multiple', 'keyboard_auto_open' => false,
            'exercise_prompt' => 'Pilih DUA pasangan petunjuk dan jawaban yang cocok. Baca setiap pasangan sampai selesai.',
            'exercise_options' => [
                $facts[0][0].' → '.$facts[0][1], $facts[1][0].' → '.$facts[2][1],
                $facts[2][0].' → '.$facts[2][1], $facts[3][0].' → '.$facts[0][1],
            ],
            'correct_options' => [0, 2], 'exercise_explanation' => $facts[0][2].' '.$facts[2][2],
        ];

        return $questions;
    }

    /** @return list<array<string, mixed>> */
    private function paths(): array
    {
        return [
            ['slug' => 'cecimpedan', 'title' => 'Cecimpedan', 'description' => 'Membaca petunjuk, menebak, dan mengenali permainan makna dalam teka-teki Bali.', 'order' => 1, 'modules' => $this->cecimpedan()],
            ['slug' => 'peparikan', 'title' => 'Peparikan', 'description' => 'Mengenali sampiran, isi, bunyi, dan pesan pantun Bali.', 'order' => 2, 'modules' => $this->peparikan()],
            ['slug' => 'sesonggakan', 'title' => 'Sesonggakan', 'description' => 'Mengenali ungkapan kias dan menerapkannya dalam konteks yang tepat.', 'order' => 3, 'modules' => $this->sesonggakan()],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function cecimpedan(): array
    {
        return [
            ['slug' => 'mengenali-petunjuk', 'title' => 'Cecimpedan: membaca petunjuk', 'summary' => 'Kenali cara mencari jawaban sebelum berlatih dengan benda dan kegiatan sehari-hari.',
                'material' => 'Cecimpedan adalah teka-teki. Petunjuk sering meminjam ciri benda atau gerak kegiatan. Jangan selalu membayangkan gambaran itu secara harfiah. Periksa apakah calon jawaban cocok dengan seluruh petunjuk, bukan hanya satu kata. Dalam latihan berikut, utamakan alasan, bukan kecepatan menebak.',
                'sources' => [self::CECIMPEDAN], 'facts' => [
                    ['Bentuk permainan bahasa yang meminta kita menerka jawaban disebut apa?', 'Cecimpedan', 'Cecimpedan mengajak pendengar memecahkan teka-teki.'],
                    ['Bagian teka-teki yang mengarahkan kita menuju sesuatu disebut apa?', 'Petunjuk', 'Petunjuk membatasi kemungkinan jawaban.'],
                    ['Arti tidak langsung yang meminjam gambaran lain disebut apa?', 'Makna kias', 'Gambaran dalam teka-teki dapat mewakili sesuatu yang berbeda.'],
                    ['Apa yang sebaiknya dilakukan setelah mempunyai tebakan?', 'Cocokkan seluruh cirinya', 'Jawaban kuat menjelaskan keseluruhan petunjuk.'],
                ]],
            ['slug' => 'benda-di-rumah', 'title' => 'Tebak benda di sekitar rumah', 'summary' => 'Latihan langsung: hubungkan gambaran teka-teki dengan benda yang kamu kenal.', 'material' => '',
                'sources' => [self::CECIMPEDAN], 'facts' => [
                    ['Benda kecil untuk menjahit seolah menarik seekor cacing. Apa jawaban acuannya?', 'Jaum misi benang', 'Gambaran itu mengarah pada jarum berbenang; benangnya mengikuti gerak jarum.'],
                    ['Benda yang menyangga kepala seolah sudah kenyang karena diisi sekali. Apa jawabannya?', 'Galeng', 'Galeng berarti bantal. Petunjuk mengarah pada isi bantal, bukan kegiatan makan manusia.'],
                    ['Benda yang diputar lalu seolah mengorek tanah ketika dijatuhkan disebut apa?', 'Gangsing', 'Gangsing berputar pada bagian ujungnya.'],
                    ['Naungan yang menyerupai bangunan besar bertiang satu disebut apa dalam jawaban latihan?', 'Pajeng', 'Pajeng adalah payung; bentuk terbuka menjadi petunjuknya.'],
                ]],
            ['slug' => 'rasa-dan-buah', 'title' => 'Tebak makanan dan buah', 'summary' => 'Latihan langsung dengan petunjuk warna, bagian dalam, dan bentuk buah.', 'material' => '',
                'sources' => [self::CECIMPEDAN], 'facts' => [
                    ['Jajanan kecil yang menyimpan cairan merah manis di dalamnya adalah apa?', 'Klepon', 'Isi gula pada klepon menjadi gambaran cairan merah di dalam benda kecil.'],
                    ['Buah yang dagingnya putih terlindung kulit bersisik adalah apa?', 'Salak', 'Petunjuk memisahkan bagian daging buah dengan kulit pembungkusnya.'],
                    ['Buah bersisik yang bagian atasnya seperti mempunyai mahkota disebut apa?', 'Manas', 'Manas adalah nanas; daun pada ujung buah menjadi petunjuk mahkota.'],
                    ['Buah yang biji-bijinya tampak seperti kumpulan permata merah adalah apa?', 'Delima', 'Tebakan memanfaatkan warna dan banyaknya biji buah delima.'],
                ]],
            ['slug' => 'tumbuhan-di-kebun', 'title' => 'Tebak tumbuhan di kebun', 'summary' => 'Latihan langsung: perhatikan daun, buah, dan perubahan bentuk tumbuhan.', 'material' => '',
                'sources' => [self::CECIMPEDAN], 'facts' => [
                    ['Tanaman pangan yang gambaran teka-tekinya berubah dari berambut terikat menjadi terurai adalah apa?', 'Padi', 'Perubahan tampilan padi dipakai sebagai petunjuk, bukan usia manusia sungguhan.'],
                    ['Tanaman dengan daun lebar dan buah besar yang dipakai dalam latihan ini adalah apa?', 'Waluh', 'Waluh berarti labu; ukuran daun dan buah mengarahkan tebakan.'],
                    ['Pohon yang daunnya dapat menjadi bahan lontar disebut apa dalam latihan?', 'Ental', 'Ental menjadi jawaban acuan; bandingkan ciri daun dan kegunaannya.'],
                    ['Bagian pisang yang seolah berlapis kain ketika masih muda disebut apa?', 'Pusuh biu', 'Pusuh biu adalah jantung pisang dengan lapisan kelopak pembungkus.'],
                ]],
            ['slug' => 'gerak-dan-kegiatan', 'title' => 'Tebak gerak dan kegiatan', 'summary' => 'Latihan langsung untuk membedakan petunjuk tindakan dengan petunjuk benda.', 'material' => '',
                'sources' => [self::CECIMPEDAN], 'facts' => [
                    ['Bunyi gesekan bergerak menjauh ketika halaman dibersihkan mengarah pada kegiatan apa?', 'Nyampat', 'Nyampat berarti menyapu; amati gerak alat yang menjauh dari penyapu.'],
                    ['Gambaran bunyi serangga di atas gunung mengarah pada orang yang sedang melakukan apa?', 'Macukur', 'Kepala digambarkan sebagai gunung, dan kegiatan yang dimaksud adalah mencukur.'],
                    ['Gerakan bolak-balik di mulut yang menimbulkan busa mengarah pada kegiatan apa?', 'Masikat gigi', 'Petunjuk menunjukkan kegiatan menyikat gigi, bukan nama makanan.'],
                    ['Bunyi benturan yang akhirnya memperlihatkan dua bagian bulat putih mengarah pada kegiatan apa?', 'Ngempug nyuh', 'Ngempug nyuh berarti membelah kelapa; bagian dalam kelapa menjadi petunjuk.'],
                ]],
            ['slug' => 'tantangan-petunjuk', 'title' => 'Tantangan akhir Cecimpedan', 'summary' => 'Uji cara membaca petunjuk dan berikan jawaban yang masuk akal.', 'material' => '',
                'sources' => [self::CECIMPEDAN], 'facts' => [
                    ['Petunjuk menyebut jarum yang diikuti sesuatu seperti cacing. Apa yang perlu ikut disebut?', 'Benang', 'Menyebut benang melengkapi hubungan jarum dan sesuatu yang mengikutinya.'],
                    ['Petunjuk menyebut tindakan mencukur. Jawaban itu termasuk kelompok apa?', 'Kegiatan', 'Tebakan bisa menunjuk tindakan, tidak selalu benda.'],
                    ['Dua tebakan sama-sama cocok dengan warna, tetapi hanya satu cocok dengan bentuk. Mana yang dipilih?', 'Yang cocok dengan keduanya', 'Gunakan gabungan petunjuk untuk mengurangi tebakan yang terlalu umum.'],
                    ['Saat jawaban teman berbeda karena variasi daerah, langkah yang tepat apa?', 'Bandingkan alasan dan konteks', 'Variasi lisan tidak perlu langsung dianggap salah; periksa petunjuk yang digunakan.'],
                ]],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function peparikan(): array
    {
        return [
            ['slug' => 'mengenal-pantun-bali', 'title' => 'Peparikan: mengenal empat baris', 'summary' => 'Baca contoh pantun Bali dan kenali bentuk dasarnya.',
                'material' => "Peparikan merupakan pantun Bali. Contoh latihan ini mempunyai empat baris. Baca dari awal sampai akhir sebelum memutuskan pesannya.\n\n".self::PANTUN,
                'sources' => [self::PARIBASA], 'facts' => [
                    ['Bentuk paribasa yang dipelajari melalui bait empat baris ini disebut apa?', 'Peparikan', 'Contoh ini adalah peparikan, yaitu bentuk pantun Bali.'],
                    ['Berapa jumlah baris pada bait contoh?', 'Empat', 'Hitung pemisahan baris, bukan banyaknya kalimat atau tanda baca.'],
                    ['Dua baris pembuka pada contoh berfungsi sebagai apa?', 'Sampiran', 'Bagian pembuka menyiapkan hubungan bunyi menuju isi.'],
                    ['Dua baris penutup pada contoh berfungsi sebagai apa?', 'Isi', 'Pesan utama dibaca pada bagian isi.'],
                ]],
            ['slug' => 'memisahkan-sampiran-isi', 'title' => 'Memisahkan sampiran dan isi', 'summary' => 'Bedakan letak pembuka dan pesan dalam satu bait.',
                'material' => "Pada pola yang dipakai di sini, baris pertama dan kedua adalah sampiran; baris ketiga dan keempat merupakan isi. Jangan menyimpulkan pesan hanya dari benda pada sampiran.\n\n".self::PANTUN,
                'sources' => [self::PARIBASA], 'facts' => [
                    ['Baris pertama dan kedua bersama-sama disebut bagian apa?', 'Sampiran', 'Keduanya menjadi bagian pembuka dalam pola empat baris.'],
                    ['Baris ketiga dan keempat bersama-sama disebut bagian apa?', 'Isi', 'Keduanya membawa pokok pesan.'],
                    ['Jika ingin mengetahui gagasan utama bait, bagian mana yang dibaca lebih teliti?', 'Dua baris penutup', 'Pembaca mencari pesan pada bagian isi, sambil tetap membaca bait secara utuh.'],
                    ['Jika pembaca hanya menafsirkan daun pisang pada pembuka, apa yang terlewat?', 'Pesan keseluruhan', 'Gambaran benda dalam sampiran belum tentu merupakan tujuan pembicaraan.'],
                ]],
            ['slug' => 'mendengar-bunyi-akhir', 'title' => 'Mendengar pasangan bunyi', 'summary' => 'Amati pasangan akhir baris tanpa mengabaikan makna.',
                'material' => "Bacakan bait berikut. Dengarkan akhir baris pertama dengan ketiga, lalu kedua dengan keempat. Pada contoh ini, hubungan bunyi membantu mengingat urutannya. Latihan menilai contoh ini saja, bukan memaksakan satu pola pada semua tradisi lisan.\n\n".self::PANTUN,
                'sources' => [self::PARIBASA], 'facts' => [
                    ['Akhir baris pertama, biu, dipasangkan dengan akhir baris ketiga yang berbunyi apa?', 'Liu', 'Biu dan liu sama-sama berakhir dengan bunyi iu.'],
                    ['Akhir baris kedua, paso, dipasangkan dengan akhir baris keempat yang berbunyi apa?', 'Keto', 'Paso dan keto menutup pasangan dengan vokal o.'],
                    ['Dalam bait contoh, baris pertama dipasangkan bunyinya dengan baris ke berapa?', 'Ketiga', 'Bandingkan akhir baris pertama dan ketiga.'],
                    ['Dalam bait contoh, baris kedua dipasangkan bunyinya dengan baris ke berapa?', 'Keempat', 'Bandingkan akhir baris kedua dan keempat.'],
                ]],
            ['slug' => 'menafsirkan-pesan', 'title' => 'Menafsirkan pesan tentang keberagaman', 'summary' => 'Hubungkan isi pantun dengan situasi pertemanan sehari-hari.',
                'material' => "Baca kembali isi bait. Latihan ini mengajakmu menafsirkan gagasan bahwa banyak orang hidup dengan keadaan yang beragam. Skenario berikut adalah contoh penerapan yang disusun ARTASWAHA, bukan tambahan baris pantun tradisional.\n\n".self::PANTUN,
                'sources' => [self::PARIBASA], 'facts' => [
                    ['Sikap yang sesuai ketika teman memiliki kebiasaan berbeda adalah apa?', 'Menghargai perbedaan', 'Perbedaan tidak perlu langsung dianggap kekurangan.'],
                    ['Untuk menangkap pesan pantun, kita perlu mencari apa di balik kata-katanya?', 'Makna isi', 'Pesan diperoleh dengan menafsirkan bagian isi, bukan hanya menghafal bunyi.'],
                    ['Memilih teman hanya karena semua kebiasaannya sama menunjukkan sikap apa?', 'Menolak keberagaman', 'Skenario ini menjadi kebalikan dari sikap menerima keadaan yang beragam.'],
                    ['Mendengar alasan teman sebelum menyanggah menunjukkan sikap apa?', 'Mau memahami orang lain', 'Memahami alasan adalah penerapan pesan dalam percakapan.'],
                ]],
            ['slug' => 'pantun-semangat-belajar', 'title' => 'Pantun tentang semangat belajar', 'summary' => 'Baca contoh kedua dan bedakan gambaran pembuka dengan harapan pada isi.',
                'material' => "Berikut contoh peparikan yang menghubungkan kerajinan belajar dengan harapan masa depan. Harapan dalam karya sastra bukan jaminan hasil atau kewajiban memilih satu profesi. Baca sampiran dan isi secara terpisah, lalu satukan kembali.\n\n".self::PANTUN_BELAJAR,
                'sources' => [self::MAHADEWA], 'facts' => [
                    ['Kegiatan yang ditekankan pada bagian isi contoh kedua adalah apa?', 'Belajar dengan rajin', 'Isi contoh menyampaikan ajakan belajar sejak kecil.'],
                    ['Gambaran burung pada bagian awal contoh kedua berfungsi sebagai apa?', 'Sampiran', 'Gambaran pembuka bukan pesan profesi yang hendak disampaikan.'],
                    ['Profesi yang disebut pada baris penutup contoh kedua adalah apa?', 'Dokter', 'Dokter menjadi gambaran cita-cita pada contoh, bukan satu-satunya pilihan hidup.'],
                    ['Agar pesan pantun tidak disalahartikan, harapan masa depan dipahami sebagai apa?', 'Dorongan bukan jaminan', 'Belajar penting, tetapi contoh pantun tidak menjamin profesi seseorang.'],
                ]],
            ['slug' => 'tantangan-membaca-bait', 'title' => 'Tantangan akhir Peparikan', 'summary' => 'Langsung berlatih mengenali susunan dan cara memahami pantun.', 'material' => '',
                'sources' => [self::PARIBASA, self::MAHADEWA], 'facts' => [
                    ['Dalam pola empat baris yang sudah dipelajari, bagian pembuka disebut apa?', 'Sampiran', 'Baca kembali modul pemisahan sampiran dan isi jika masih tertukar.'],
                    ['Dalam pola yang sama, bagian pembawa pesan disebut apa?', 'Isi', 'Makna utama muncul pada bagian isi.'],
                    ['Jika urutan empat baris acak, apa yang perlu diperiksa selain pesan?', 'Pasangan bunyi akhir', 'Bunyi membantu menyusun kembali pasangan baris.'],
                    ['Balasan pantun yang baik sebaiknya bagaimana terhadap topik teman?', 'Tetap berkaitan dengan topik', 'Skenario percakapan ini melatih keterhubungan, bukan sekadar persamaan bunyi.'],
                ]],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function sesonggakan(): array
    {
        return [
            ['slug' => 'membaca-makna-kias', 'title' => 'Membaca ungkapan dan makna kias', 'summary' => 'Bedakan gambaran harfiah, makna ungkapan, dan penerapannya.',
                'material' => 'Nama jalur Sesonggakan dipertahankan dari menu platform. Rujukan membedakan sesonggan (pepatah) dan sesenggakan (ibarat); keduanya tidak otomatis sama. Latihan di jalur ini berfokus pada pemahaman ungkapan kias. Awali dari gambaran harfiah, cari hubungan maknanya, lalu periksa konteks pemakaiannya. Skenario latihan merupakan contoh baru, bukan kutipan sumber.',
                'sources' => [self::SESONGGAN, self::MAHADEWA], 'facts' => [
                    ['Makna yang mengikuti arti langsung kata-kata disebut apa?', 'Makna harfiah', 'Makna harfiah menjelaskan gambaran bendanya terlebih dahulu.'],
                    ['Makna yang meminjam gambaran untuk menunjuk keadaan lain disebut apa?', 'Makna kias', 'Ungkapan dapat menunjuk perilaku atau keadaan manusia secara tidak langsung.'],
                    ['Apa yang perlu diperiksa sebelum memakai ungkapan pada seseorang?', 'Konteks percakapan', 'Pertimbangkan situasi dan hubungan agar ungkapan tidak melukai orang lain.'],
                    ['Apakah semua istilah sesonggan dan sesenggakan boleh dianggap satu jenis tanpa pemeriksaan?', 'Perlu dibedakan', 'Rujukan mengelompokkan bentuk paribasa secara lebih terperinci.'],
                ]],
            ['slug' => 'keadaan-sulit', 'title' => 'Ungkapan untuk keadaan sulit', 'summary' => 'Pahami satu ungkapan lalu terapkan pada contoh keadaan yang terjepit.',
                'material' => 'Taluh apit batu menggambarkan keadaan serba sulit atau terjepit. Latihan berikut menggunakan skenario buatan: seseorang menghadapi dua pilihan yang sama-sama membawa masalah. Jangan memakai ungkapan untuk mengejek kesulitan orang; dahulukan empati.',
                'sources' => [self::SESONGGAN], 'facts' => [
                    ['Ungkapan yang dipelajari untuk keadaan terjepit adalah apa?', 'Taluh apit batu', 'Jawaban acuan menggambarkan keadaan serba sulit.'],
                    ['Dua pilihan sama-sama membawa masalah. Keadaan itu dapat diringkas sebagai apa?', 'Serba sulit', 'Perhatikan keadaan orangnya, bukan sekadar benda dalam ungkapan.'],
                    ['Saat teman terjepit masalah, sikap yang lebih pantas apa?', 'Menawarkan bantuan', 'Skenario ini mengutamakan empati dalam memakai ungkapan.'],
                    ['Jika kedua pilihan sama-sama aman dan menyenangkan, apakah cocok dengan gambaran terjepit?', 'Tidak cocok', 'Konteks yang aman tidak sesuai dengan keadaan serba sulit.'],
                ]],
            ['slug' => 'banyak-dan-berpengalaman', 'title' => 'Jumlah besar dan pengalaman hidup', 'summary' => 'Baca ungkapan tentang banyaknya sesuatu dan panjangnya pengalaman.',
                'material' => 'Abias pasih mengibaratkan jumlah yang sangat banyak. Dawa papahne liu slepanne menghubungkan lamanya hidup dengan banyaknya pengalaman. Latihan tidak menganggap setiap orang yang lebih tua selalu benar; pengalaman tetap perlu dibahas dengan alasan yang jelas.',
                'sources' => [self::SESONGGAN], 'facts' => [
                    ['Ungkapan yang menjadi acuan jumlah sangat banyak dalam modul ini apa?', 'Abias pasih', 'Jumlahnya dibayangkan sangat besar, tidak sedang dihitung persis.'],
                    ['Ungkapan mana yang dipakai untuk membicarakan banyaknya pengalaman selama hidup?', 'Dawa papahne liu slepanne', 'Penafsiran ini berhubungan dengan panjangnya pengalaman hidup.'],
                    ['Apakah gambaran jumlah yang sangat banyak harus diterjemahkan sebagai angka pasti?', 'Tidak harus', 'Makna kias dapat menekankan banyaknya sesuatu tanpa menyebut jumlah tepat.'],
                    ['Ketika orang berpengalaman memberi saran, bagaimana kita menanggapinya?', 'Dengarkan dan pertimbangkan', 'Menghormati pengalaman dapat berjalan bersama pemikiran kritis.'],
                ]],
            ['slug' => 'belajar-dan-manfaat', 'title' => 'Keahlian dan manfaat tindakan', 'summary' => 'Bedakan tindakan mengajari orang yang sudah ahli dengan perebutan hal yang tak berguna.',
                'material' => 'Ngajahin bebek ngelangi mengarah pada tindakan mengajari orang yang sebenarnya sudah mampu. Ngrebutin balung tan paisi menunjuk perebutan sesuatu yang tidak bermanfaat. Pada latihan, nilai keadaan yang dijelaskan; jangan menempelkan cap buruk pada orangnya.',
                'sources' => [self::SESONGGAN], 'facts' => [
                    ['Ungkapan untuk mengajari orang yang sudah ahli dalam contoh ini apa?', 'Ngajahin bebek ngelangi', 'Petunjuk berpusat pada kemampuan yang telah dimiliki.'],
                    ['Ungkapan untuk berebut sesuatu yang tidak bermanfaat dalam contoh ini apa?', 'Ngrebutin balung tan paisi', 'Perebutan dinilai dari tidak adanya manfaat yang didapat.'],
                    ['Sebelum menjelaskan teknik dasar kepada seorang ahli, apa langkah yang lebih baik?', 'Tanyakan kebutuhannya', 'Skenario latihan menghargai kemampuan dan kebutuhan lawan bicara.'],
                    ['Sebelum ikut perebutan yang menguras tenaga, apa yang perlu diperiksa?', 'Manfaat yang diperjuangkan', 'Pertimbangkan apakah hasilnya layak diperjuangkan.'],
                ]],
            ['slug' => 'pilih-konteks-yang-tepat', 'title' => 'Pilih konteks ungkapan', 'summary' => 'Latihan langsung: pilih penggunaan ungkapan yang sesuai situasi.', 'material' => '',
                'sources' => [self::SESONGGAN], 'facts' => [
                    ['Pilihan A dan B sama-sama menyulitkan seorang teman. Ungkapan yang sesuai apa?', 'Taluh apit batu', 'Dua sisi yang sama-sama menyulitkan cocok dengan gambaran terjepit.'],
                    ['Banyak sekali pengalaman telah dilalui seseorang. Ungkapan mana yang lebih sesuai?', 'Dawa papahne liu slepanne', 'Petunjuknya adalah pengalaman selama perjalanan hidup.'],
                    ['Orang yang sudah mahir berenang diajari teknik paling dasar tanpa diminta. Ungkapan mana yang sesuai?', 'Ngajahin bebek ngelangi', 'Skenario ini menekankan bahwa orang tersebut telah memiliki kemampuan.'],
                    ['Peserta bertengkar memperebutkan hadiah yang tidak lagi berguna. Ungkapan mana yang sesuai?', 'Ngrebutin balung tan paisi', 'Fokusnya pada perebutan yang tidak memberi manfaat.'],
                ]],
            ['slug' => 'tantangan-berbahasa-bijak', 'title' => 'Tantangan akhir: berbahasa bijak', 'summary' => 'Langsung uji penafsiran, alasan, dan kepantasan memakai ungkapan.', 'material' => '',
                'sources' => [self::SESONGGAN, self::MAHADEWA], 'facts' => [
                    ['Sebelum menyampaikan sindiran saat teman sedang sedih, apa yang perlu diperhatikan?', 'Perasaan lawan bicara', 'Skenario ini menguji empati, bukan hafalan ungkapan.'],
                    ['Apa dasar memilih ungkapan dari beberapa kemungkinan yang tersedia?', 'Kecocokan dengan keadaan', 'Bentuk bunyi yang menarik saja belum menjamin makna yang tepat.'],
                    ['Jika penjelasan ungkapan berbeda antara dua daerah, apa langkah yang tepat?', 'Periksa konteks dan rujukan', 'Tradisi lisan dapat memiliki variasi; alasan pemakaian perlu dijelaskan.'],
                    ['Jika ungkapan berisiko mempermalukan teman di depan kelas, pilihan yang bijak apa?', 'Sampaikan nasihat dengan santun', 'Tujuan latihan adalah berkomunikasi dengan lebih baik, bukan mempermalukan orang.'],
                ]],
        ];
    }
}
