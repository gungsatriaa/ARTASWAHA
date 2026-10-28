<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AksaraBaliLessonSeeder extends Seeder
{
    private const SOURCES = "## Sumber belajar\nMateri dan soal disusun ulang untuk latihan ARTASWAHA, bukan salinan bank soal atau soal ujian resmi. Esai dinilai dengan pencocokan jawaban, bukan penilaian AI atas uraian bebas.\n\nDinas Kebudayaan Buleleng — Pasang Aksara Bali\nhttps://disbudpar.bulelengkab.go.id/informasi/detail/artikel/24-pasang-aksara-bali\n\nUnicode — struktur aksara, gabungan konsonan, dan tanda baca\nhttps://unicode.org/versions/Unicode16.0.0/core-spec/chapter-17/\n\nUnicode — daftar karakter dan angka Bali\nhttps://www.unicode.org/charts/PDF/U1B00.pdf\n\nUndiksha — rujukan bentuk latihan mengenali aksara dan menulis kata (Lampiran 17)\nhttps://repo.undiksha.ac.id/5831/9/1315051061-LAMPIRAN.pdf";

    public function run(): void
    {
        DB::transaction(function (): void {
            $pathOrders = [];
            foreach ($this->modules() as $order => $module) {
                $path = LearningPath::query()->firstOrCreate(['slug' => $module['path']], [
                    'group' => 'aksara', 'title' => $module['path_title'],
                    'description' => $module['summary'], 'sort_order' => intdiv($order, 2) + 1, 'is_published' => true,
                ]);
                $content = '';
                foreach ($module['sections'] as $index => [$title, $text]) {
                    $content .= '## '.$title."\n".$text."\n\n";
                    $content .= "| Bacaan | Aksara | Perhatikan |\n";
                    foreach ($module['facts'] as $factIndex => [$reading, $script, $note]) {
                        if ($this->section($factIndex, count($module['facts'])) === $index) {
                            $content .= "| {$reading} | {$script} | {$note} |\n";
                        }
                    }
                    $content .= "\n";
                }
                $pathOrders[$path->id] = ($pathOrders[$path->id] ?? 0) + 1;
                Lesson::query()->firstOrCreate(['slug' => $module['slug']], [
                    'learning_path_id' => $path->id, 'title' => $module['title'], 'summary' => $module['summary'],
                    'content' => $content.self::SOURCES, 'sort_order' => $pathOrders[$path->id],
                    'is_published' => true, 'reviewed_at' => now(), 'keyboard_auto_open' => false,
                    'exercises' => $this->questions($module['facts'], $module['count']),
                ]);
            }
        });
    }

    private function section(int $index, int $count): int
    {
        return min(2, intdiv($index * 3, $count));
    }

    /** @param list<array{string, string, string}> $facts
     * @return list<array<string, mixed>>
     */
    private function questions(array $facts, int $count): array
    {
        $questions = [];
        $choiceCount = $count === 15 ? 9 : 6;
        for ($index = 0; $index < $choiceCount; $index++) {
            $factIndex = $index % count($facts);
            [$reading, $script, $note] = $facts[$factIndex];
            $reverse = $index >= count($facts);
            $pool = array_values(array_unique(array_column($facts, $reverse ? 0 : 1)));
            $correct = $reverse ? $reading : $script;
            $options = array_slice(array_values(array_diff($pool, [$correct])), 0, 3);
            $position = $index % 4;
            array_splice($options, $position, 0, [$correct]);
            $questions[] = [
                'section' => $this->section($factIndex, count($facts)), 'question_type' => 'single',
                'exercise_prompt' => $reverse ? "Bacaan atau nama yang sesuai untuk {$script} adalah …" : "Pilih penulisan aksara untuk {$reading} sesuai contoh materi.",
                'exercise_options' => $options, 'correct_option' => $position,
                'exercise_explanation' => "{$reading} ditulis {$script}. {$note}", 'keyboard_auto_open' => false,
            ];
        }
        $essayCount = $count === 15 ? 5 : 3;
        for ($index = 0; $index < $essayCount; $index++) {
            $factIndex = min(count($facts) - 1, (int) floor($index * count($facts) / $essayCount));
            [$reading, $script, $note] = $facts[$factIndex];
            $questions[] = [
                'section' => $this->section($factIndex, count($facts)), 'question_type' => 'essay',
                'exercise_prompt' => "Tulis {$reading} menggunakan keyboard aksara Bali sesuai bentuk pada materi. Jangan sertakan lingkaran penunjuk (◌).",
                'exercise_options' => [], 'accepted_answers' => [$script], 'keyboard_auto_open' => true,
                'exercise_explanation' => "Contoh penulisan: {$script}. {$note}",
            ];
        }
        $questions[] = [
            'section' => 2, 'question_type' => 'multiple',
            'exercise_prompt' => 'Pilih DUA pasangan bacaan dan aksara yang tepat berdasarkan materi modul ini.',
            'exercise_options' => [
                $facts[0][0].' → '.$facts[0][1], $facts[1][0].' → '.$facts[2][1],
                $facts[2][0].' → '.$facts[2][1], $facts[3][0].' → '.$facts[0][1],
            ],
            'correct_options' => [0, 2], 'keyboard_auto_open' => false,
            'exercise_explanation' => 'Pasangan yang tepat adalah '.$facts[0][0].' → '.$facts[0][1].' dan '.$facts[2][0].' → '.$facts[2][1].'.',
        ];
        usort($questions, fn (array $left, array $right): int => $left['section'] <=> $right['section']);

        return $questions;
    }

    /** @return list<array<string, mixed>> */
    private function modules(): array
    {
        return [
            [
                'path' => 'wreastra-gantungan', 'path_title' => 'Aksara Wreastra & Gantungan',
                'slug' => 'wreastra-mengenal-ha-sampai-wa', 'title' => 'Mengenal aksara: Ha sampai Wa', 'count' => 10,
                'summary' => 'Mulai dari bentuk aksara dasar, bunyi a, dan sembilan aksara pertama. Belajar sedikit, lalu langsung berlatih.',
                'sections' => [
                    ['Bentuk pertama yang perlu dikenali', 'Aksara dasar memiliki vokal bawaan a. Bacalah Ha, Na, dan Ca dengan pelan sambil membandingkan bentuknya. Ha juga dipakai sebagai dasar penulisan beberapa kata yang berawal vokal. Jangan menyamakan bentuk huruf Latin dengan bentuk aksara Bali.'],
                    ['Lanjutkan urutan Ra, Ka, Da', 'Urutan tradisional diajarkan sebagai ha na ca ra ka, kemudian da ta sa wa la. Pada bagian ini, cocokkan nama dan bentuk sebelum menghafal urutannya. Aksara yang tampil di tabel adalah huruf dasar, belum diberi pengangge.'],
                    ['Ta, Sa, dan Wa dalam satu kelompok', 'Sekarang bedakan Ta, Sa, dan Wa. Untuk latihan menulis, ketuk kategori Dasar pada keyboard, lalu pilih nama aksaranya. Jawaban satu huruf cukup ditulis dengan satu aksara; jangan menambahkan adeg-adeg jika yang diminta masih berbunyi ta, sa, atau wa.'],
                ],
                'facts' => [
                    ['Ha', 'ᬳ', 'Bunyi dasar ha; dipakai pada hati: ᬳᬢᬶ.'], ['Na', 'ᬦ', 'Bunyi dasar na; awal kata nasi.'],
                    ['Ca', 'ᬘ', 'Bunyi dasar ca; jangan tertukar dengan Ja.'], ['Ra', 'ᬭ', 'Ra adalah aksara dasar, berbeda dari tanda akhir surang.'],
                    ['Ka', 'ᬓ', 'Bunyi ka masih membawa vokal a.'], ['Da', 'ᬤ', 'Da menjadi di jika diberi ulu.'],
                    ['Ta', 'ᬢ', 'Bunyi dasar ta.'], ['Sa', 'ᬲ', 'Sa dasar dapat dipakai di awal kata sampi.'], ['Wa', 'ᬯ', 'Bunyi dasar wa.'],
                ],
            ],
            [
                'path' => 'wreastra-gantungan', 'path_title' => 'Aksara Wreastra & Gantungan',
                'slug' => 'wreastra-la-sampai-nya', 'title' => 'Melengkapi 18 aksara: La sampai Nya', 'count' => 15,
                'summary' => 'Kenali sembilan aksara berikutnya dan bedakan Nga, Nya, Ja, serta Ya melalui latihan baca dan ketik.',
                'sections' => [
                    ['La, Ma, dan Ga', 'Modul ini melanjutkan sembilan aksara pertama. Kelompok berikutnya adalah La, Ma, dan Ga. Nama aksara memperlihatkan vokal a yang dibawa bentuk dasar. Latihan awal meminta mengenali bentuk, bukan menerjemahkan kata.'],
                    ['Ba, Nga, dan Pa', 'Perhatikan bahwa Nga adalah satu aksara, walaupun ditulis dua huruf dalam Latin. Pa berbeda dari Ba. Bunyi akhir ng nanti memakai tanda cecek; dalam modul ini Nga masih merupakan konsonan dasar.'],
                    ['Ja, Ya, dan Nya', 'Akhiri dengan Ja, Ya, dan Nya. Ja tidak sama dengan Ya; Nya juga bukan Na yang diikuti Ya. Setelah menyelesaikan kelompok ini, kamu telah mengenal 18 aksara dasar dalam urutan tradisional.'],
                ],
                'facts' => [
                    ['La', 'ᬮ', 'La muncul pada laku: ᬮᬓᬸ.'], ['Ma', 'ᬫ', 'Ma muncul pada mata: ᬫᬢ.'], ['Ga', 'ᬕ', 'Ga muncul pada galang: ᬕᬮᬂ.'],
                    ['Ba', 'ᬩ', 'Ba muncul pada baju: ᬩᬚᬸ.'], ['Nga', 'ᬗ', 'Nga adalah aksara dasar, bukan tanda cecek.'], ['Pa', 'ᬧ', 'Pa berbeda dari Ba.'],
                    ['Ja', 'ᬚ', 'Ja muncul pada jaran: ᬚᬭᬦ᭄.'], ['Ya', 'ᬬ', 'Ya berbeda dari Ja.'], ['Nya', 'ᬜ', 'Nya adalah satu aksara dalam nyakan: ᬜᬓᬦ᭄.'],
                ],
            ],
            [
                'path' => 'pengangge-suara', 'path_title' => 'Pengangge Suara',
                'slug' => 'suara-ulu-suku-dalam-kata', 'title' => 'Ulu dan suku: dari a menjadi i dan u', 'count' => 10,
                'summary' => 'Bandingkan ka, ki, ku dan susun kata dija, nasi, serta buku menggunakan keyboard.',
                'sections' => [
                    ['Ubah vokal pada aksara yang sama', 'Aksara Ka berbunyi ka. Menambahkan ulu menghasilkan ki. Ulu adalah tanda vokal i; pada layar ia tampak di atas aksara. Dalam urutan ketikan digital, pilih aksara dasar lebih dulu, kemudian ulu.'],
                    ['Suku memberi bunyi u', 'Suku mengubah vokal bawaan menjadi u. Bentuknya berada di bawah aksara; sediakan ruang saat membaca agar tandanya tidak terpotong. Buku memberi contoh dua pemakaian suku pada satu kata.'],
                    ['Terapkan pada kata pendek', 'Dija berarti di mana dan nasi berarti nasi. Ulu hanya dipasang pada aksara yang berbunyi i, bukan pada semua aksara dalam kata. Pada soal ketik, pilih Dasar lalu Pangangge; jawaban tidak perlu diberi lingkaran penunjuk.'],
                ],
                'facts' => [
                    ['ka', 'ᬓ', 'Tanpa tanda vokal tambahan, Ka berbunyi ka.'], ['ki', 'ᬓᬶ', 'Ka diikuti ulu menghasilkan ki.'],
                    ['ku', 'ᬓᬸ', 'Ka diikuti suku menghasilkan ku.'], ['buku', 'ᬩᬸᬓᬸ', 'Ba + suku, lalu Ka + suku.'],
                    ['dija', 'ᬤᬶᬚ', 'Da memakai ulu, lalu Ja tanpa pengangge.'], ['nasi', 'ᬦᬲᬶ', 'Sa diberi ulu; Na tetap berbunyi na.'],
                ],
            ],
            [
                'path' => 'pengangge-suara', 'path_title' => 'Pengangge Suara',
                'slug' => 'suara-taleng-pepet-tedung', 'title' => 'Membedakan é, e pepet, o, dan ā', 'count' => 15,
                'summary' => 'Bedakan taleng, pepet, taleng tedung, dan tedung tanpa tertukar antara bentuk visual dan urutan ketik.',
                'sections' => [
                    ['Taleng dan pepet berbeda', 'Pada contoh suku kata dalam tabel, ké memakai taleng, sedangkan ke /kə/ memakai pepet. Dua tanda ini tidak saling menggantikan. Tulisan Latin é di modul ini membantu membedakan bunyi taleng dari e pepet.'],
                    ['Taleng tedung untuk o', 'Taleng tedung menghasilkan o. Contohnya ko, to, dan kata toko. Walau bagian taleng terlihat di depan, urutan ketikan tetap aksara dasar lalu tanda vokal. Keyboard menyediakan tanda gabungan Taleng tedung.'],
                    ['Tedung dan latihan perbandingan', 'Tedung digunakan untuk vokal panjang ā pada ejaan yang memerlukannya. Contoh kā di sini adalah latihan suku kata, bukan perintah menambahkan tedung pada setiap bunyi a. Bandingkan pula bi dengan bu; keduanya memakai aksara Ba tetapi tanda berbeda.'],
                ],
                'facts' => [
                    ['ké (taleng)', 'ᬓᬾ', 'Ka + taleng.'], ['ke /kə/ (pepet)', 'ᬓᭂ', 'Ka + pepet; berbeda dari taleng.'], ['mé (taleng)', 'ᬫᬾ', 'Ma + taleng.'],
                    ['ko', 'ᬓᭀ', 'Ka + taleng tedung.'], ['to', 'ᬢᭀ', 'Ta + taleng tedung.'], ['toko', 'ᬢᭀᬓᭀ', 'Ta dan Ka masing-masing memakai taleng tedung.'],
                    ['kā (tedung)', 'ᬓᬵ', 'Ka + tedung untuk ā pada contoh ini.'], ['bi', 'ᬩᬶ', 'Ba + ulu.'], ['bu', 'ᬩᬸ', 'Ba + suku.'],
                ],
            ],
            [
                'path' => 'pengangge-tengenan', 'path_title' => 'Pengangge Tengenan',
                'slug' => 'tengenan-cecek-surang-bisah', 'title' => 'Konsonan akhir: ng, r, dan h', 'count' => 10,
                'summary' => 'Kenali cecek, surang, dan bisah lewat basang, galang, pasar, dan yéh.',
                'sections' => [
                    ['Cecek untuk akhir ng', 'Tanda cecek menyatakan bunyi akhir ng. Aksara Nga dasar memiliki vokal a, sedangkan cecek menutup suku kata tanpa menambah vokal baru. Bandingkan basang dan galang; keduanya berakhir dengan cecek.'],
                    ['Surang untuk akhir r', 'Surang menandai konsonan akhir r pada contoh pasar. Ini berbeda dari aksara Ra yang dibaca ra. Baca contoh sampai akhir agar tidak menambahkan vokal yang tidak diminta.'],
                    ['Bisah untuk akhir h', 'Bisah menyatakan akhir h, misalnya pada yéh dan ngidih. Ha dasar dan bisah bukan bentuk yang dapat dipertukarkan sembarangan. Di tengah kata cihna, Ha yang disambung Na tetap ditulis sebagai rangkaian gantungan, bukan diganti bisah.'],
                ],
                'facts' => [
                    ['basang', 'ᬩᬲᬂ', 'Ba + Sa + cecek; artinya perut.'], ['galang', 'ᬕᬮᬂ', 'Ga + La + cecek; artinya terang.'],
                    ['pasar', 'ᬧᬲᬃ', 'Pa + Sa + surang.'], ['sar (Sa + surang)', 'ᬲᬃ', 'Sa + surang; contoh suku kata dengan akhir r.'], ['yéh', 'ᬬᬾᬄ', 'Ya + taleng + bisah; artinya air.'],
                    ['ngidih', 'ᬗᬶᬤᬶᬄ', 'Nga + ulu, Da + ulu, lalu bisah; artinya meminta.'],
                ],
            ],
            [
                'path' => 'pengangge-tengenan', 'path_title' => 'Pengangge Tengenan',
                'slug' => 'tengenan-adeg-adeg-kata', 'title' => 'Adeg-adeg dan kata berakhir konsonan', 'count' => 15,
                'summary' => 'Pahami vokal yang dimatikan dan praktikkan ka/k, na/n, tukad, bawak, panak, serta jaran.',
                'sections' => [
                    ['Dari ka menjadi k', 'Bentuk dasar Ka membawa vokal a. Adeg-adeg menghilangkan vokal tersebut: ka menjadi k. Contoh k dalam tabel merupakan latihan satu konsonan; tidak memerlukan tambahan titik atau spasi.'],
                    ['Dari na menjadi n', 'Aturan yang sama dapat diperhatikan pada Na. Konsonan akhir n pada contoh jaran diberi adeg-adeg. Ini berbeda dari cecek, yang menandai akhir ng. Perhatikan perbedaan n dan ng saat memilih jawaban.'],
                    ['Baca kata sampai konsonan penutup', 'Tukad berarti sungai, bawak berarti pendek, dan panak berarti anak. Adeg-adeg pada akhir contoh menghilangkan vokal a dari konsonan penutup. Ketika ada konsonan berikutnya, adeg-adeg juga terlibat dalam pembentukan sambungan; bentuknya akan dipelajari pada modul gantungan.'],
                ],
                'facts' => [
                    ['ka', 'ᬓ', 'Ka dasar masih berbunyi ka.'], ['k', 'ᬓ᭄', 'Ka + adeg-adeg.'], ['na', 'ᬦ', 'Na dasar masih berbunyi na.'],
                    ['n', 'ᬦ᭄', 'Na + adeg-adeg.'], ['jaran', 'ᬚᬭᬦ᭄', 'Ja + Ra + Na + adeg-adeg.'],
                    ['Wayan', 'ᬯᬬᬦ᭄', 'Wa + Ya + Na + adeg-adeg.'], ['tukad', 'ᬢᬸᬓᬤ᭄', 'Ta + suku, Ka, Da + adeg-adeg.'],
                    ['bawak', 'ᬩᬯᬓ᭄', 'Ba + Wa + Ka + adeg-adeg.'], ['panak', 'ᬧᬦᬓ᭄', 'Pa + Na + Ka + adeg-adeg.'],
                ],
            ],
            [
                'path' => 'wreastra-gantungan', 'path_title' => 'Aksara Wreastra & Gantungan',
                'slug' => 'gantungan-cihna-kanti-sampi', 'title' => 'Menyambung aksara: gantungan dan gempelan', 'count' => 10,
                'summary' => 'Susun cihna, kanti, dan sampi sambil memahami mengapa konsonan ditumpuk atau disambung.',
                'sections' => [
                    ['Konsonan yang bertemu', 'Pada contoh digital, konsonan pertama diikuti adeg-adeg dan konsonan berikutnya. Font lalu membentuk sambungan. hn dan nt dalam tabel adalah potongan latihan gabungan, bukan kata. Lingkaran putus-putus hanya penunjuk posisi dalam buku; jangan diketik ke jawaban.'],
                    ['Amati cihna dan kanti', 'Cihna berarti tanda atau bukti. Susun Ca + ulu, Ha + adeg-adeg + Na. Ha kehilangan vokalnya karena disambung Na. Pada kanti, Na diikuti adeg-adeg dan Ta berulu. Baca per konsonan sebelum membaca seluruh kata.'],
                    ['Gempelan pada sampi', 'Sampi berarti sapi. Susun Sa, Ma, adeg-adeg, Pa, lalu ulu. Sambungan Pa mengikuti Ma; Sa di awal tetap aksara dasar. Jangan menebak pasangan berdasarkan letak visual saja: urutan ketikan dan konsonan yang disambung juga penting.'],
                ],
                'facts' => [
                    ['hn (Ha + Na)', 'ᬳ᭄ᬦ', 'Ha + adeg-adeg + Na; potongan gabungan konsonan.'],
                    ['nt (Na + Ta)', 'ᬦ᭄ᬢ', 'Na + adeg-adeg + Ta; potongan gabungan konsonan.'],
                    ['cihna', 'ᬘᬶᬳ᭄ᬦ', 'Ca + ulu, Ha + adeg-adeg + Na.'], ['kanti', 'ᬓᬦ᭄ᬢᬶ', 'Ka, Na + adeg-adeg + Ta + ulu.'],
                    ['sampi', 'ᬲᬫ᭄ᬧᬶ', 'Sa, Ma + adeg-adeg + Pa + ulu; Pa membentuk gempelan.'],
                ],
            ],
            [
                'path' => 'pengangge-arda-suara', 'path_title' => 'Pengangge Arda Suara',
                'slug' => 'ardasuara-ya-ra-la-wa', 'title' => 'Gabungan ya, ra, la, dan wa', 'count' => 15,
                'summary' => 'Latih nania, guwung, gantungan La, dan suku kembung lewat gabungan serta kata sederhana.',
                'sections' => [
                    ['Gabungan dengan Ya dan Ra', 'Nania adalah bentuk sambung Ya, sedangkan guwung adalah bentuk sambung Ra. Keduanya mengikuti konsonan sebelumnya tanpa vokal a di antaranya. Contoh kya dan kra adalah latihan suku kata.'],
                    ['Gabungan dengan La dan Wa', 'Gantungan La membentuk kla setelah Ka, sedangkan suku kembung membentuk swa setelah Sa. Di keyboard, kamu bisa mengetik aksara dasar, adeg-adeg, lalu konsonan sambungnya. Bentuk tampilan bergantung font, tetapi urutan karakternya tetap.'],
                    ['Dari gabungan ke kata', 'Krana berarti karena atau sebab; kladi berarti talas. Tabia diperkenalkan dengan ejaan nania, dan swasti sebagai contoh gabungan swa serta sti. Modul ini melatih bentuk yang ditampilkan di tabel; variasi ejaan kata tetap perlu mengikuti pedoman dan konteks.'],
                ],
                'facts' => [
                    ['kya', 'ᬓ᭄ᬬ', 'Ka + adeg-adeg + Ya (nania).'], ['kra', 'ᬓ᭄ᬭ', 'Ka + adeg-adeg + Ra (guwung).'], ['bya', 'ᬩ᭄ᬬ', 'Ba + adeg-adeg + Ya.'],
                    ['kla', 'ᬓ᭄ᬮ', 'Ka + adeg-adeg + La.'], ['swa', 'ᬲ᭄ᬯ', 'Sa + adeg-adeg + Wa (suku kembung).'],
                    ['krana', 'ᬓ᭄ᬭᬦ', 'Kra diikuti Na; bukan kruna.'], ['kladi', 'ᬓ᭄ᬮᬤᬶ', 'Kla diikuti Da + ulu.'],
                    ['tabia (ejaan nania)', 'ᬢᬩ᭄ᬬ', 'Ta diikuti Ba + adeg-adeg + Ya.'], ['swasti', 'ᬲ᭄ᬯᬲ᭄ᬢᬶ', 'Swa diikuti Sa + adeg-adeg + Ta + ulu.'],
                ],
            ],
            [
                'path' => 'angka-aksara-bali', 'path_title' => 'Angka Aksara Bali',
                'slug' => 'angka-bali-nol-sampai-sembilan', 'title' => 'Mengenal angka Bali 0–9', 'count' => 10,
                'summary' => 'Cocokkan lambang angka Bali dengan nilainya dan gunakan kategori Angka pada keyboard.',
                'sections' => [
                    ['Mulai dari nol sampai tiga', 'Bali memiliki lambang angka sendiri. Angka nol adalah ᭐; jangan tertukar dengan tiga, ᭓. Di modul ini angka ditulis tanpa pembatas dulu agar kamu fokus mengenali lambangnya.'],
                    ['Lanjutkan empat sampai enam', 'Nilai bilangan tidak berubah ketika bentuk tulisannya berubah dari Latin ke Bali. Perhatikan bentuk empat, lima, dan enam sebelum berlatih memilih atau mengetik. Keyboard menyediakan kategori Angka agar tidak perlu menyalin dari luar.'],
                    ['Lengkapi tujuh sampai sembilan', 'Angka Bali dapat dirangkai menurut nilai tempat seperti angka Latin. Untuk saat ini, ketik satu lambang ketika yang diminta satu digit. Pembatas carik dan bilangan lebih panjang dipelajari di modul berikutnya.'],
                ],
                'facts' => [
                    ['0', '᭐', 'Nol; berbeda dari ᭓.'], ['1', '᭑', 'Satu; bentuk sehari-hari disebut besik.'], ['2', '᭒', 'Dua.'], ['3', '᭓', 'Telu; bukan nol.'],
                    ['4', '᭔', 'Papat.'], ['5', '᭕', 'Lima.'], ['6', '᭖', 'Nenem.'], ['7', '᭗', 'Pitu.'], ['8', '᭘', 'Kutus.'], ['9', '᭙', 'Sia.'],
                ],
            ],
            [
                'path' => 'angka-aksara-bali', 'path_title' => 'Angka Aksara Bali',
                'slug' => 'angka-bali-bilangan-dan-carik', 'title' => 'Merangkai bilangan dan memakai carik', 'count' => 15,
                'summary' => 'Susun bilangan dua digit, gunakan carik siki sebagai pembatas, dan bedakan tanda akhir kalimat.',
                'sections' => [
                    ['Puluhan dan satuan', 'Bilangan disusun dari kiri ke kanan menurut nilai tempat. Delapan belas ditulis satu lalu delapan. Tiga puluh ditulis tiga lalu nol, bukan tiga lalu tiga. Bacaan di tabel menjelaskan apakah jawaban perlu pembatas atau tidak.'],
                    ['Carik sebagai pembatas angka', 'Bentuk beberapa angka mirip aksara. Dalam latihan kalimat ini, bilangan diapit carik siki ᭞ untuk memperjelas bahwa tanda di tengah adalah angka. Kedua carik adalah pembatas, bukan digit tambahan.'],
                    ['Tanda akhir dan penerapan', 'Carik pareren ᭟ digunakan sebagai penutup kalimat. Bedakan bentuknya dengan carik siki ᭞. Pada latihan ketik, pembatas harus ikut ditulis hanya ketika soal meminta bentuk dengan carik; jangan menambahkan spasi di tengah bilangan.'],
                ],
                'facts' => [
                    ['10 tanpa pembatas', '᭑᭐', 'Satu lalu nol.'], ['18 tanpa pembatas', '᭑᭘', 'Satu lalu delapan.'], ['30 tanpa pembatas', '᭓᭐', 'Tiga lalu nol.'],
                    ['3 dengan carik', '᭞᭓᭞', 'Carik siki, tiga, carik siki.'], ['18 dengan carik', '᭞᭑᭘᭞', 'Kedua digit diapit sepasang carik siki.'], ['30 dengan carik', '᭞᭓᭐᭞', 'Tiga dan nol diapit carik siki.'],
                    ['carik siki', '᭞', 'Dalam modul ini dipakai sebagai pembatas bilangan; juga merupakan tanda koma.'],
                    ['carik pareren', '᭟', 'Tanda penutup kalimat.'], ['2026 dengan carik', '᭞᭒᭐᭒᭖᭞', 'Dua, nol, dua, enam dengan pembatas.'],
                ],
            ],
        ];
    }
}
