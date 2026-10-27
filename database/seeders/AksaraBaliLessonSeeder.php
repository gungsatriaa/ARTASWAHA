<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AksaraBaliLessonSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->modules() as $order => $module) {
                $path = LearningPath::query()->firstOrCreate(['slug' => $module['path']], [
                    'group' => 'aksara',
                    'title' => $module['title'],
                    'description' => $module['summary'],
                    'sort_order' => $order + 1,
                    'is_published' => true,
                ]);

                Lesson::query()->firstOrCreate(['slug' => $module['path'].'-dasar-andap'], [
                    'learning_path_id' => $path->id,
                    'title' => $module['title'],
                    'summary' => $module['summary'],
                    'content' => $module['content'],
                    'sort_order' => 1,
                    'is_published' => true,
                    'reviewed_at' => now(),
                    'keyboard_auto_open' => false,
                    'exercise_prompt' => $module['question'],
                    'exercise_options' => $module['options'],
                    'correct_option' => $module['answer'],
                    'exercise_explanation' => $module['explanation'],
                ]);
            }
        });
    }

    /** @return list<array{path: string, title: string, summary: string, content: string, question: string, options: list<string>, answer: int, explanation: string}> */
    private function modules(): array
    {
        return [
            [
                'path' => 'wreastra-gantungan',
                'title' => 'Aksara Wreastra, Gantungan, dan Gempelan',
                'summary' => 'Kenali 18 aksara dasar dan bentuk tumpuk melalui kosakata Bali sehari-hari.',
                'content' => <<<'TEXT'
Tujuan belajar
Mengenali 18 aksara Wreastra, membaca contoh kata, dan memahami fungsi gantungan serta gempelan.

Aksara Wreastra dipakai untuk menulis bahasa Bali sehari-hari. Aksara dasar membawa vokal a. Pengangge suara dapat mengubah vokalnya, sedangkan adeg-adeg mematikan vokal tersebut.

Mengenal 18 aksara
Baca setiap pasangan aksara dan suara, lalu perhatikan contoh katanya.

1. ᬳ — Ha
Hati: hati / perasaan → ᬳᬢᬶ
Bentuk tumpuk: ◌᭄ᬳ (gantungan).

2. ᬦ — Na
Nasi: nasi → ᬦᬲᬶ
Bentuk tumpuk: ◌᭄ᬦ (gantungan).

3. ᬘ — Ca
Cihna: tanda / bukti → ᬘᬶᬳ᭄ᬦ
Bentuk tumpuk: ◌᭄ᬘ (gantungan).

4. ᬭ — Ra
Rurung: jalan / gang → ᬭᬸᬭᬸᬂ
Bentuk tumpuk: ◌᭄ᬭ (gantungan).

5. ᬓ — Ka
Kanti: teman / rekan → ᬓᬦ᭄ᬢᬶ
Bentuk tumpuk: ◌᭄ᬓ (gantungan).

6. ᬤ — Da
Dija: di mana → ᬤᬶᬚ
Bentuk tumpuk: ◌᭄ᬤ (gantungan).

7. ᬢ — Ta
Tukad: sungai → ᬢᬸᬓᬤ᭄
Bentuk tumpuk: ◌᭄ᬢ (gantungan).

8. ᬲ — Sa
Sampi: sapi → ᬲᬫ᭄ᬧᬶ
Bentuk sambung: ◌᭄ᬲ (gempelan).

9. ᬯ — Wa
Wayan: nama Wayan → ᬯᬬᬦ᭄
Bentuk tumpuk: ◌᭄ᬯ (gantungan).

10. ᬮ — La
Laku: laris / laku → ᬮᬓᬸ
Bentuk tumpuk: ◌᭄ᬮ (gantungan).

11. ᬫ — Ma
Mata: mata → ᬫᬢ
Bentuk tumpuk: ◌᭄ᬫ (gantungan).

12. ᬕ — Ga
Galang: terang → ᬕᬮᬂ
Bentuk tumpuk: ◌᭄ᬕ (gantungan).

13. ᬩ — Ba
Baju: baju → ᬩᬚᬸ
Bentuk tumpuk: ◌᭄ᬩ (gantungan).

14. ᬗ — Nga
Ngidih: minta → ᬗᬶᬤᬶᬄ
Bentuk tumpuk: ◌᭄ᬗ (gantungan).

15. ᬧ — Pa
Panak: anak → ᬧᬦᬓ᭄
Bentuk sambung: ◌᭄ᬧ (gempelan).

16. ᬚ — Ja
Jaran: kuda → ᬚᬭᬦ᭄
Bentuk tumpuk: ◌᭄ᬚ (gantungan).

17. ᬬ — Ya
Yéh: air → ᬬᬾᬄ
Bentuk tumpuk: ◌᭄ᬬ (gantungan).

18. ᬜ — Nya
Nyakan: memasak nasi → ᬜᬓᬦ᭄
Bentuk tumpuk: ◌᭄ᬜ (gantungan).

Cara kerja gantungan dan gempelan
Gantungan umumnya menyambung di bawah aksara sebelumnya. Gempelan, seperti Sa dan Pa, menyambung di samping. Dalam teks digital, ketik aksara sebelumnya, adeg-adeg ᭄, lalu aksara berikutnya; font membentuk sambungannya. Lingkaran ◌ pada contoh hanya menunjukkan tempat aksara dasar, bukan karakter yang disalin ke kata.

Perhatikan cihna
ᬘᬶᬳ᭄ᬦ terdiri dari Ca + ulu, Ha, adeg-adeg, dan Na. Ha di tengah kata kehilangan vokalnya ketika disambung dengan gantungan Na. Jangan mengganti Ha di sini dengan bisah.

Pada sampi, gempelan yang dipakai adalah Pa setelah Ma: ᬲᬫ᭄ᬧᬶ. Sa di awal kata tetap berupa aksara dasar.

Coba sendiri
Salin nasi, cihna, dan sampi. Temukan tanda ulu serta pasangan aksara yang tersambung, lalu kerjakan latihan di bawah.

Rujukan: https://tutlenc.blogspot.com/2020/04/panduan-menulis-aksara-bali-lanjutan.html
TEXT,
                'question' => 'Pada kata cihna (ᬘᬶᬳ᭄ᬦ), aksara apa yang menjadi gantungan setelah Ha?',
                'options' => ['Ca (ᬘ)', 'Na (ᬦ)', 'Sa (ᬲ)', 'Pa (ᬧ)'],
                'answer' => 1,
                'explanation' => 'Na menjadi gantungan setelah Ha. Rangkaian Ha + adeg-adeg + Na mematikan vokal Ha dan membentuk hn pada cihna.',
            ],
            [
                'path' => 'pengangge-suara',
                'title' => 'Pengangge Suara dalam Kata Sehari-hari',
                'summary' => 'Bedakan enam tanda vokal dasar lewat contoh dija, buku, toko, dan enyer.',
                'content' => <<<'TEXT'
Tujuan belajar
Mengenali tanda vokal dan membedakan taleng untuk é dari pepet untuk e /ə/.

Pengangge suara mengubah vokal bawaan aksara. Bagian ini memperkenalkan enam tanda, bukan seluruh variasi pengangge suara yang ada.

1. Tedong / tedung — ◌ᬵ
Menandai vokal panjang ā dalam aturan penulisan yang memerlukannya. Contoh dari materi: baba (potong rumput/padi), ditulis dengan tedong pada kedua Ba → ᬩᬵᬩᬵ. Pemakaian tedong mengikuti ejaan kata; tidak semua bunyi a perlu diberi tedong.

2. Ulu — ◌ᬶ — /i/
Dija: di mana → ᬤᬶᬚ
Ulu pada Da mengubah da menjadi di.

3. Suku — ◌ᬸ — /u/
Buku: buku → ᬩᬸᬓᬸ
Suku dipasang pada Ba dan Ka sehingga terbaca bu-ku.

4. Taleng — ◌ᬾ — /é/
Édan: gila / sinting → ᬳᬾᬤᬦ᭄
Taleng tampak di depan aksara dasar, tetapi diketik sesudah aksara dasarnya. Pada contoh ini urutannya Ha, taleng, Da, Na, adeg-adeg.

5. Taleng tedong — ◌ᭀ — /o/
Toko: toko → ᬢᭀᬓᭀ
Tanda ini juga dapat disusun dari taleng dan tedong: ◌ᭀ. Keduanya merepresentasikan vokal o; bentuk contoh memakai tanda gabungan ᭀ.

6. Pepet — ◌ᭂ — /ə/
Enyer: leleh / cair → ᬳᭂᬜᭂᬃ
Pepet digunakan pada Ha dan Nya. Akhir r ditulis dengan surang.

Jangan tertukar
Taleng ᬾ dan pepet ᭂ adalah dua tanda berbeda. Pada teks digital, simpan pengangge setelah aksara dasar meskipun bentuk visualnya berada di depan atau di atas. Lingkaran ◌ hanyalah penunjuk posisi pada contoh tanda.

Coba sendiri
Baca ᬤᬶᬚ, ᬩᬸᬓᬸ, dan ᬢᭀᬓᭀ. Sebutkan tanda yang mengubah vokal pada setiap kata, lalu jawab latihan di bawah.

Rujukan karakter: https://www.unicode.org/charts/PDF/U1B00.pdf
TEXT,
                'question' => 'Tanda mana yang digunakan untuk bunyi e pepet /ə/ pada enyer?',
                'options' => ['Ulu (◌ᬶ)', 'Taleng (◌ᬾ)', 'Pepet (◌ᭂ)', 'Suku (◌ᬸ)'],
                'answer' => 2,
                'explanation' => 'Pepet adalah ᭂ, sedangkan taleng adalah ᬾ. Enyer ditulis ᬳᭂᬜᭂᬃ dengan pepet pada Ha dan Nya.',
            ],
            [
                'path' => 'pengangge-tengenan',
                'title' => 'Pengangge Tengenan: Akhir Suku Kata',
                'summary' => 'Baca konsonan akhir ng, r, h, dan konsonan lain menggunakan empat tanda tengenan.',
                'content' => <<<'TEXT'
Tujuan belajar
Memilih tanda akhir yang tepat tanpa menambahkan vokal setelah konsonan.

Pengangge tengenan menandai konsonan akhir. Empat tanda berikut dipakai sesuai bunyi yang menutup suku kata atau kata.

1. Cecek — ◌ᬂ — akhir ng
Basang: perut → ᬩᬲᬂ
Cecek menutup kata dengan ng sehingga dibaca ba-sang, bukan ba-sa-nga.

2. Surang — ◌ᬃ — akhir r
Pasar: pasar → ᬧᬲᬃ
Surang membuat akhir kata berbunyi r, bukan ra.

3. Bisah — ◌ᬄ — akhir h
Gawah: hutan / kebun → ᬕᬯᬄ
Bisah menandai bunyi h pada akhir kata.

4. Adeg-adeg — ◌᭄ — mematikan vokal bawaan
Bawak: pendek → ᬩᬯᬓ᭄
Ka di akhir diberi adeg-adeg agar dibaca k. Adeg-adeg juga dipakai dalam rangkaian digital pembentuk gantungan dan gempelan ketika diikuti konsonan lain.

Bandingkan
ᬓ berbunyi ka, sedangkan ᬓ᭄ berbunyi k.
ᬗ adalah aksara Nga, sedangkan ᬂ adalah tanda cecek untuk akhir ng.
ᬭ adalah aksara Ra, sedangkan ᬃ adalah tanda surang untuk akhir r.
ᬳ adalah aksara Ha, sedangkan ᬄ adalah tanda bisah untuk akhir h.

Tanda akhir tidak bisa menggantikan semua pasangan konsonan di tengah kata secara sembarang. Ingat contoh cihna: Ha disambung dengan gantungan Na.

Coba sendiri
Temukan tanda akhir pada basang, pasar, gawah, dan bawak. Cocokkan masing-masing dengan bunyi ng, r, h, dan k sebelum mengerjakan latihan.
TEXT,
                'question' => 'Mengapa Ka pada bawak (ᬩᬯᬓ᭄) diberi adeg-adeg?',
                'options' => ['Agar dibaca k tanpa vokal a', 'Agar berubah menjadi ng', 'Agar dibaca ki', 'Agar menjadi angka'],
                'answer' => 0,
                'explanation' => 'Adeg-adeg mematikan vokal bawaan a pada Ka, sehingga akhir kata bawak berbunyi k.',
            ],
            [
                'path' => 'pengangge-arda-suara',
                'title' => 'Pengangge Ardasuara dan Gabungan Konsonan',
                'summary' => 'Kenali bentuk ya, ra, la, dan wa pada tabia, krana, kladi, dan swasti.',
                'content' => <<<'TEXT'
Tujuan belajar
Mengenali empat bentuk sambung yang dipakai dalam gabungan konsonan dan membaca contoh katanya.

Dalam materi ardasuara ini, bentuk sambung Ya, Ra, La, dan Wa dibaca bersama aksara sebelumnya. Contoh ditulis menggunakan adeg-adeg diikuti aksara sambungnya, bukan dengan menyisipkan lingkaran ◌ ke dalam kata.

1. Nania — ◌᭄ᬬ — gabungan dengan ya
Tabia: cabai → ᬢᬩ᭄ᬬ
Ba + adeg-adeg + Ya membentuk bya pada akhir kata. Contoh ini memperlihatkan ejaan tabia dengan nania.

2. Guung / guwung / cakra — ◌᭄ᬭ — gabungan dengan ra
Krana: karena / sebab → ᬓ᭄ᬭᬦ
Ka disambung dengan Ra sehingga membentuk kra, lalu diikuti Na. Contoh yang digunakan adalah krana, bukan kruna.

3. Gantungan La — ◌᭄ᬮ — gabungan dengan la
Kladi: talas → ᬓ᭄ᬮᬤᬶ
Ka dan La membentuk kla; Da diberi ulu untuk di.

4. Suku kembung — ◌᭄ᬯ — gabungan dengan wa
Swasti: selamat / sehat → ᬲ᭄ᬯᬲ᭄ᬢᬶ
Sa dan Wa membentuk swa. Sa berikutnya disambung dengan Ta berulu sehingga dibaca sti.

Bedakan bentuk dan bunyi
Gantungan atau sambungan merupakan bentuk aksara saat digabung. Bunyi kata tetap mengikuti ejaan dan pemakaian bahasa Bali. Swasti juga digunakan dalam ungkapan salam; bentuk ini diperkenalkan sebagai contoh suku kembung.

Coba sendiri
Salin krana dan kladi. Keduanya berawal dari Ka, tetapi bentuk sambung berikutnya berbeda. Setelah itu, kerjakan latihan.

Rujukan kelompok pengangge: https://disbudpar.bulelengkab.go.id/informasi/detail/artikel/24-pasang-aksara-bali
TEXT,
                'question' => 'Bentuk sambung apa yang dipakai setelah Ka pada krana (ᬓ᭄ᬭᬦ)?',
                'options' => ['Nania / Ya', 'Gantungan La', 'Suku kembung / Wa', 'Guung / Ra'],
                'answer' => 3,
                'explanation' => 'Krana memakai Ka + adeg-adeg + Ra. Bentuk sambung Ra disebut guung atau guwung, juga dikenal sebagai cakra.',
            ],
            [
                'path' => 'angka-aksara-bali',
                'title' => 'Angka Bali dalam Kalimat Sehari-hari',
                'summary' => 'Kenali angka 0–9 dan gunakan carik untuk menuliskan bilangan dalam kalimat.',
                'content' => <<<'TEXT'
Tujuan belajar
Mengenali sepuluh lambang angka Bali, menyusun bilangan, dan membedakannya dari huruf dengan tanda carik.

Lambang angka dan nama bilangan
0 → ᭐ — nol
1 → ᭑ — besik
2 → ᭒ — dua
3 → ᭓ — telu
4 → ᭔ — papat
5 → ᭕ — lima
6 → ᭖ — nenem
7 → ᭗ — pitu
8 → ᭘ — kutus
9 → ᭙ — sia

Nama bilangan di atas menggunakan bentuk sehari-hari. Siki, kalih, dan tiga merupakan pilihan ragam bahasa lain untuk satu, dua, dan tiga; jangan mencampurnya tanpa memperhatikan konteks tutur.

Perhatikan angka nol
Nol ditulis ᭐, bukan ᭓. Lambang ᭓ adalah angka tiga.

Menyusun bilangan
Angka disusun menurut nilai tempat, seperti angka Latin.
18 → ᭑᭘
30 → ᭓᭐
2026 → ᭒᭐᭒᭖

Carik sebagai pembatas
Ketika angka dipakai di dalam kalimat, apit bilangan dengan carik siki ᭞ agar tidak tertukar dengan huruf. Contoh delapan belas: ᭞᭑᭘᭞. Carik bukan angka dan tidak mengubah nilainya.

Contoh kalimat sehari-hari
Bahasa Bali: Meli jaje 3 bungkus.
Arti: Membeli kue 3 bungkus.
Aksara Bali: ᬫᭂᬮᬶᬚᬚᬾ᭞᭓᭞ᬩᬸᬂᬓᬸᬲ᭄᭟

Pada contoh itu, ᭞᭓᭞ berarti tiga. Jaje menggunakan taleng pada aksara Ja terakhir. Tanda ᭟ di akhir adalah penutup kalimat, bukan bagian dari bilangan.

Coba sendiri
Tulis 3, 18, dan 30 dengan angka Bali. Setelah mengenali setiap lambang, apit bilangan dengan carik dan jawab latihan di bawah.

Rujukan karakter: https://www.unicode.org/charts/PDF/U1B00.pdf
TEXT,
                'question' => 'Manakah penulisan angka 30 dengan pembatas carik yang benar?',
                'options' => ['᭞᭓᭓᭞', '᭞᭐᭓᭞', '᭞᭓᭐᭞', '᭞᭒᭐᭞'],
                'answer' => 2,
                'explanation' => '30 ditulis ᭓᭐, yaitu tiga diikuti nol. Dalam kalimat, bilangan tersebut diapit carik menjadi ᭞᭓᭐᭞.',
            ],
        ];
    }
}
