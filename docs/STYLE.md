# ArtaSwaha — UI dan Visual Style

## Implementasi styling

- Gunakan Tailwind CSS v4 untuk layout dan styling komponen; gunakan utility classes di Blade dan hindari stylesheet terpisah untuk aturan yang hanya dipakai sekali.
- Definisikan warna, tipografi, radius, dan token global sekali di `resources/css/app.css` menggunakan `@theme` serta custom properties CSS bila diperlukan.
- Gunakan token tema global melalui utility Tailwind atau `var(...)`; jangan menyalin nilai warna/radius berulang di template.
- CSS khusus hanya untuk kebutuhan yang tidak praktis ditulis dengan utility, seperti `@font-face` Vimala dan aturan glyph Aksara Bali.
- Font Nunito, Atkinson Hyperlegible, dan Quicksand dimuat dari sumber tepercaya dengan fallback; Vimala dimuat sebagai file lokal proyek.

## Arah visual

Buat pengalaman belajar yang ceria, hangat, dan mudah dipahami anak, dengan identitas visual yang berakar pada Bali. Ambil inspirasi umum dari aplikasi belajar bahasa yang ringan dan memberi umpan balik, tetapi gunakan komposisi, karakter, ilustrasi, palet, ikon, suara, dan pola navigasi ArtaSwaha sendiri. Jangan menyalin maskot, layout khas, ikon, teks, atau aset Duolingo.

## Palet warna

- **Latar utama:** charcoal `#0A0A0A`, gelap dengan sedikit nuansa di atas hitam pekat.
- **Panel dan kartu:** charcoal terang `#1E2124`, sedikit lebih terang dari latar agar kartu mudah dibedakan tanpa kontras berlebihan.
- **Primary:** teal `#007C80` untuk tombol utama, progres, dan elemen interaktif.
- **Aksen:** kuning keemasan `#FFC700` sebagai pengganti oranye, untuk sorotan, bintang, dan detail penting.
- **Teks utama:** krem terang `#F5F3EE`; gunakan `#FFC700` untuk teks aksen atau judul pendek, bukan paragraf panjang.
- Pastikan kontras teks dan kontrol tetap terbaca; jangan mengandalkan warna saja untuk menyampaikan status.

## Tipografi

- **Nunito:** font utama untuk navigasi, tombol, label, dan UI umum; ramah serta tetap jelas pada berbagai ukuran.
- **Atkinson Hyperlegible:** gunakan untuk instruksi, penjelasan, dan teks latihan ketika keterbacaan menjadi prioritas.
- **Quicksand:** gunakan untuk judul pendek, nama jalur, atau sorotan; jangan gunakan untuk paragraf panjang.
- **Vimala (`Font Vimala.ttf`):** gunakan khusus untuk menampilkan Aksara Bali. Jangan gunakan sebagai font UI atau teks Latin; pasangkan dengan ejaan/transliterasi Latin menggunakan Nunito atau Atkinson Hyperlegible.
- Simpan file font Vimala di aset aplikasi yang dikelola proyek dan muat sebagai font lokal, bukan dari CDN. Pastikan hak penggunaan/redistribusi font telah dikonfirmasi sebelum font dibundel atau dipublikasikan.
- Batasi hierarki ke ukuran dan bobot yang konsisten. Uji glyph Aksara Bali, tanda diakritik, dan kombinasi karakter Vimala pada browser target sebelum rilis.
- Muat font dari sumber tepercaya dengan versi/berkas yang stabil dan fallback sans-serif agar teks tetap terbaca jika font gagal dimuat.

## Bentuk dan sudut

- Gunakan bentuk membulat sebagai ciri UI ArtaSwaha yang ramah anak.
- **Kartu utama:** sudut besar, sekitar `24–32px`, terutama untuk kartu pelajaran, ringkasan progres, dan panel kuis.
- **Tombol utama dan chip:** gunakan bentuk kapsul/full-rounded jika label dan ukuran tombol tetap nyaman dibaca serta mudah disentuh.
- **Input dan kontrol sekunder:** sudut membulat sedang, sekitar `12–16px`, agar berbeda dari tombol kapsul.
- **Avatar, lencana, dan indikator progres melingkar:** gunakan bentuk bulat penuh.
- Terapkan radius secara konsisten melalui token desain; hindari membuat setiap elemen memiliki bentuk/radius berbeda atau menumpuk banyak outline dan bayangan.
- Kartu tetap perlu kontras yang cukup dengan latar gelap; gunakan perbedaan surface dan jarak yang rapi, bukan ornamen berlebihan.

## Prinsip desain

- **Ramah anak:** label singkat, tombol jelas, satu tugas utama per layar, dan navigasi yang mudah ditebak.
- **Budaya dengan hormat:** gunakan motif, warna, cerita, dan simbol Bali dengan konteks yang benar serta tinjauan lokal; hindari ornamen budaya sebagai dekorasi tanpa makna.
- **Interaktif dengan tujuan:** animasi dan suara memperjelas hasil tindakan, bukan mengganggu fokus belajar.
- **Konsisten:** gunakan token warna, tipografi, radius, jarak, ikon, dan pola komponen yang dipakai berulang.
- **Aksesibel:** kontras teks memadai, fokus keyboard terlihat, target sentuh cukup besar, animasi dapat dikurangi, dan suara dapat dimatikan.

## Pola layar

- Beranda menonjolkan tombol “lanjut belajar” atau “mulai belajar”.
- Jalur belajar menunjukkan urutan dan progres tanpa membebani anak dengan banyak pilihan sekaligus.
- Latihan menampilkan satu pertanyaan pada satu waktu dengan area jawaban yang lapang.
- Jawaban benar memberi konfirmasi positif; jawaban salah memberi petunjuk dan kesempatan mencoba lagi tanpa mempermalukan.
- Room kuis menampilkan kode room, status bergabung, dan kontrol host dengan hierarki visual yang jelas.
- Peringkat realtime dapat dinonaktifkan host; hindari efek yang mempermalukan peserta berperingkat rendah.

## Motion dan audio

- Gunakan transisi singkat untuk perubahan status, progres, dan hadiah.
- Hindari animasi berulang tanpa kontrol, kilatan cepat, gerakan parallax yang berat, atau suara otomatis saat halaman dibuka.
- Hormati `prefers-reduced-motion`; sediakan cara mematikan efek suara.
- Pastikan animasi tidak menjadi satu-satunya cara menyampaikan informasi.

## Aset dan bahasa

- Buat karakter, ilustrasi, serta ikon orisinal untuk ArtaSwaha.
- Gunakan ikon Font Awesome melalui CDN untuk ikon UI; pilih satu gaya ikon secara konsisten dan gunakan ikon sebagai pendamping label yang jelas, bukan pengganti label penting.
- Muat versi Font Awesome yang dipatok secara eksplisit dari CDN tepercaya, dan sediakan fallback teks/ikon agar antarmuka tetap dapat dipahami jika CDN gagal dimuat.
- Gunakan audio pelafalan yang ditinjau penutur yang kompeten.
- Gunakan bahasa yang sederhana, hangat, dan tidak menyalahkan anak.
- Hindari warna atau simbol yang mengurangi keterbacaan maupun memberi makna budaya yang keliru.
