# Kontrak Output Task A dan Serah Terima ke Task B

Hasil per komentar berisi status, quality flags, mention aspek, sentimen per aspek, jenis/kategori alasan, dan evidence. Evidence yang berstatus valid selalu disertai offset ke teks komentar asli; kutipan yang tidak ditemukan tidak pernah dianggap valid.

Konsolidasi hanya memproses mention dengan sekurang-kurangnya satu evidence valid. Kunci kelompok: aspect_code, label aspek usulan, reason_category, reason_type. Tidak ada threshold jumlah; kelompok satu feedback tetap ditampilkan. ID temuan deterministik berdasarkan batch dan kunci kelompok. Angka dan distribusi sentimen dihitung di Python.

Temuan memuat unique_feedback_count, mention_count, supporting_feedback_ids, sentiment_distribution, minority_views, single_feedback_support, quality_flags, dan review_status awal pending_review. Pujian dan keluhan tidak dicampur. Pandangan berlawanan dari feedback lain di aspek yang sama disimpan sebagai minority_views tanpa menghapus temuan sumbernya.

Judul dan ringkasan temuan dapat dibantu LLM berdasarkan kutipan yang telah diverifikasi. Model tidak menghitung jumlah atau membuat rekomendasi. Hasil yang ditolak memakai templat deterministik dan quality flag summary_fallback. Penggabungan embedding tidak aktif secara default dan hanya dipertimbangkan setelah evaluasi berlabel manusia.

**Batas Task A:** hanya temuan yang telah direview dan berstatus `approved` oleh staf boleh diserahkan ke Task B. IPA, dokumen, RAG, dan rekomendasi tetap berada pada Task B.
