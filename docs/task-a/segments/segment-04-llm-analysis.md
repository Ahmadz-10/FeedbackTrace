# Segment 04: Mesin Analisis LLM

Task A menjalankan satu panggilan model per komentar yang lolos validasi. Prompt berversi dirender dari taxonomy dan kategori alasan. Hasil diurai menjadi aspek, sentimen, jenis/kategori alasan, deskripsi, dan kandidat kutipan.

- Komentar yang berstatus skipped tidak dikirim ke Groq.
- Task A memakai adaptor di atas klien Groq shared, bukan membuat klien baru.
- Kandidat evidence masih unverified; validasi terhadap teks asli dilakukan Segment 05.
- Kegagalan pada satu komentar tidak menghentikan proses batch.
- Pengujian biasa memakai gateway palsu. Tes live hanya berjalan dengan RUN_LIVE_LLM_TESTS=1 dan GROQ_API_KEY.

Uji: `cd ai-service && python -m pytest tests/task_a/test_extraction.py -q`.
