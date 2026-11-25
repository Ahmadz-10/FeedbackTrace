# Segment 03: Impor, Validasi Feedback & Preprocessing

**Branch:** `feature/task-a-feedback-intelligence`

## Isi
- `feedback_validator.py`: validasi payload, cek ID unik, siapkan komentar untuk dianalisis atau dilewati.
- `preprocessor.py`: normalisasi spasi/karakter tak terlihat dengan pemetaan indeks ke teks asli.
- `privacy_filter.py`: samarkan email, telepon, NIK/NIM, URL, nama bersapaan/berlabel sebelum ke Groq.
- `quality_flags.py`: tandai teks kosong, terlalu pendek/panjang, tanpa huruf, dan masalah item.

Gunakan `validate_payload(dict)` atau `validate_and_prepare(AnalyzeRequest)`. Hanya `analysis_text` yang boleh diteruskan ke model. Item yang dilewati harus tetap disimpan dengan `quality_flags`.

Aturan: duplikat `feedback_id` menolak seluruh batch; komentar buruk tidak menggagalkan batch. Teks asli tidak diubah, tidak di-lowercase, negasi tidak dihapus, dan slang tidak diperluas. Ambang: minimal 4 huruf dan maksimal 5.000 karakter.

Jalankan: `cd ai-service && python -m pytest tests/task_a -q`.
