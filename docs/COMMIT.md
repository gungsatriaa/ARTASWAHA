# Aturan Commit ArtaSwaha

## Format pesan

Gunakan format:

```text
<type>(<scope>): <ringkasan dalam bentuk perintah>
```

- `type` wajib dan ditulis huruf kecil.
- `scope` opsional; gunakan area yang berubah, misalnya `docs`, `auth`, `lessons`, atau `quiz`.
- Ringkasan singkat, spesifik, diawali kata kerja, tanpa titik di akhir.
- Tulis scope dan ringkasan commit dalam bahasa Indonesia. Hanya `type` yang tetap memakai kata baku Conventional Commits seperti `feat`, `fix`, dan `docs`.

Tipe yang digunakan:

- `feat`: menambah kemampuan yang terlihat pengguna.
- `fix`: memperbaiki bug.
- `docs`: mengubah dokumentasi.
- `style`: perubahan format/kosmetik tanpa mengubah perilaku.
- `refactor`: merapikan struktur tanpa perubahan perilaku.
- `test`: menambah atau memperbaiki test.
- `perf`: meningkatkan performa.
- `chore`: pemeliharaan, konfigurasi, atau tooling.

Contoh:

```text
docs(style): tetapkan tema gelap dan tipografi
feat(quiz): tambahkan alur bergabung ke room
fix(progress): pertahankan progres belajar anonim
```

## Ukuran dan frekuensi commit

- Buat satu commit setiap satu perubahan kecil yang selesai dan dapat dipahami secara mandiri.
- Jangan menumpuk beberapa perubahan yang tidak berkaitan dalam satu commit.
- Jangan membuat commit untuk pekerjaan yang masih setengah jadi atau gagal diverifikasi.
- Sebelum commit, tinjau `git diff` dan `git status`; stage hanya file yang termasuk perubahan tersebut.
- Pastikan `.env`, kredensial, token, data pribadi, build output, dan file sementara tidak ikut ter-stage.
- Jalankan pemeriksaan atau test terarah yang relevan sebelum commit; sebutkan jika pemeriksaan tidak dapat dijalankan.
- Jangan mengubah atau menulis ulang commit yang sudah dipublikasikan tanpa permintaan eksplisit.

## Kebiasaan kerja Codex

- Setelah pengguna meminta perubahan, selesaikan dan verifikasi perubahan itu, lalu buat commit kecil khusus untuk perubahan tersebut.
- Jangan menunggu beberapa tugas selesai untuk membuat satu commit gabungan.
- Jangan push ke remote kecuali pengguna meminta push secara eksplisit.
