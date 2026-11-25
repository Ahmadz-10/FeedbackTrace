# Segment 05: Validasi Skema, Referensi, Evidence, dan Retry

Sebelum mention diterima, aplikasi memvalidasi label/taksonomi, kesesuaian sentimen dengan reason_type, ID feedback pemilik, serta kutipan terhadap teks asli. Posisi dari LLM tidak dipercaya; offset dihitung ulang dengan pemetaan Segment 03.

- Bukti valid disimpan dengan indeks karakter pada teks asli; bukti invalid tetap terlihat tetapi tidak dianggap valid.
- Mention tanpa minimal satu bukti valid tidak masuk hasil akhir.
- Retry dibatasi oleh opsi max_retries; catatan validator dikirim ke model sebagai konteks koreksi.
- Dari beberapa percobaan dipilih hasil terbaik (mention valid terbanyak).
- Kegagalan konfigurasi API key tidak diulang; kegagalan sementara / output invalid dapat diulang.

Uji: `cd ai-service && python -m pytest tests/task_a -q`. Tes live Groq tetap opt-in.
