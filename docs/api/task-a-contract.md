# Kontrak Data Task A: Intelijen Umpan Balik Mahasiswa

**Versi schema:** `1.0` · **Versi taxonomy:** `1.0` · **Versi kategori alasan:** `1.0`

## Request `POST /api/v1/task-a/analyze`

Request berisi `request_id`, metadata `batch`, opsi analisis, dan `feedbacks[]`. Setiap item membawa `feedback_id`, `question_id`, `comment_text`, serta opsional `submitted_at` dan `language_hint`. Field yang tidak dikenal pada schema Pydantic ditolak; duplikat feedback ID dalam satu batch menolak batch.

## Kontrak data
- Teks asli `comment_text` tidak boleh dimodifikasi. Hasil normalisasi terpisah di `normalized_text`.
- Hanya `analysis_text` yang telah dinormalisasi dan disamarkan PII yang boleh dikirim ke model.
- Item bermasalah tetap ada dalam hasil sebagai `skipped` dengan quality flags; item tersebut tidak boleh dipanggil ke model.
- Evidence offsets dipetakan kembali ke teks asli melalui peta indeks preprocessing dan redaksi.
- Kategori reason/aspect dan enum terpusat pada `app/task_a/config`; skema request/response ada pada `app/task_a/schemas`.

## Privasi dan preprocessing
Filter pola menyamarkan email, nomor telepon, NIK, NIM/NPM/NRP, URL, nama berlabel/sapaan, serta istilah tambahan yang dikonfigurasi. Nama tanpa label/sapaan tidak terdeteksi otomatis. Preprocessing hanya merapikan karakter dan spasi; negasi, kapitalisasi, serta bahasa informal dipertahankan.

## Batas analisis
`MIN_LETTERS = 4` dan `MAX_ANALYZABLE_CHARS = 5000`. Komentar kosong, tanpa huruf, terlalu pendek/panjang, atau item tidak valid dilewati. Komentar yang mengandung indikasi privasi tersisa setelah redaksi diblokir (fail-closed).

## Respons
Respons per batch berisi metadata, statistik, hasil per komentar, mention, bukti, temuan, dan warning. Hitungan dihitung oleh kode, bukan oleh LLM. Teks asli disimpan terpisah dari normalized/redacted text.
