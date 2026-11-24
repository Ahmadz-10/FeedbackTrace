# Segment 06: Konsolidasi Temuan & Pelestarian Pandangan Minoritas

Konsolidasi memakai hanya mention yang memiliki minimal satu evidence valid. Kunci kelompok deterministik adalah aspect_code, label aspek usulan, reason_category, dan reason_type. Kelompok singleton tetap menjadi temuan; tidak ada ambang jumlah minimum.

Angka dihitung oleh Python:
- `unique_feedback_count`: jumlah feedback berbeda.
- `mention_count`: jumlah mention.
- Distribusi sentimen: dihitung per mention.
- ID temuan deterministik dari batch dan kunci kelompok.

Pandangan berbeda untuk aspek yang sama dicatat sebagai `minority_views` pada temuan lawannya, sekaligus tetap menjadi temuan tersendiri. Pandangan netral tidak dianggap berlawanan.

Ringkasan LLM hanya menulis `issue_title` dan `issue_summary` berdasarkan kutipan terverifikasi; pemeriksaan aplikasi menolak teks kosong, terlalu panjang, angka/kuantitas tak berdasar, dan placeholder privasi. Jika ditolak/gagal/melewati batas panggilan, dipakai templat dan `summary_fallback` ditambahkan. Ringkasan gagal tidak menggagalkan job.

Embedding hanya tersedia sebagai alat evaluasi cosine/kalibrasi ambang. Tidak dipakai untuk menggabungkan kelompok sampai ambang diuji pada data dev berlabel manusia; presisi lebih diprioritaskan daripada recall.

Uji: `cd ai-service && python -m pytest tests/task_a -q`.
