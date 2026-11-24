# Kontrak Data Task A

## Request
`POST /api/v1/task-a/analyze` menerima metadata batch, opsi, dan 1–500 komentar. ID feedback harus unik dalam batch. Komentar asli tidak diubah; hanya teks hasil preprocessing dan redaksi yang dikirim ke LLM.

## Analisis dan validasi
Setiap mention harus menggunakan taxonomy/aspect dan reason category yang dikenal, dengan sentiment dan reason_type konsisten. Setiap mention harus merujuk feedback_id pemilik. Bukti harus ditemukan dalam teks yang dilihat model dan dipetakan kembali ke teks asli; offset dari model tidak dipercaya. Mention tanpa bukti valid dibuang.

## Retry
`max_retries` menentukan jumlah percobaan ulang di luar panggilan pertama (0–3). Aplikasi memberi catatan kesalahan validator sebagai petunjuk koreksi. Hasil percobaan terbaik dipertahankan. Kegagalan satu komentar tidak menghentikan batch.

## Hasil
Respons memuat status per komentar, mention, evidence, quality flags, metadata model/prompt/pipeline, statistik, dan warning. Hanya hasil yang telah lolos validasi yang dapat menjadi bahan konsolidasi. Temuan yang disetujui staf saja dikirim ke Task B.
