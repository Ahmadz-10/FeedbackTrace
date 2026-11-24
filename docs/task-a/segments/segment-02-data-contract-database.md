# Segment 02: Kontrak Data, Taksonomi & Database

**Branch:** `feature/task-a-feedback-intelligence` · Dokumen kontrak lengkap: `docs/api/task-a-contract.md`

## Isi
| Lokasi | Fungsi |
|---|---|
| `ai-service/app/task_a/config/labels.py` | Enum sentiment, strength, reason type, finding type, status |
| `ai-service/app/task_a/config/taxonomy.py` | 10 kode aspek + validasi |
| `ai-service/app/task_a/config/reason_categories.py` | 11 kategori alasan + jenis alasan yang diizinkan |
| `ai-service/app/task_a/config/versions.py` | `PIPELINE_VERSION` |
| `ai-service/app/task_a/schemas/requests.py` | `AnalyzeRequest`, `FeedbackItem`, `AnalysisOptions` |
| `ai-service/app/task_a/schemas/responses.py` | `AnalyzeResponse`, `AspectMention`, `ConsolidatedFinding`, `ApprovedFinding`, dst. |
| `database/migrations/2026_11_23_1000NN_*.php` | 8 tabel untuk batch, record, analisis, mention, evidence, finding, pivot, review |
| `app/Models/TaskA/*.php` | 7 model Eloquent, relasi, casts |
| `ai-service/tests/task_a/test_contracts.py`, `tests/Feature/TaskA/FeedbackSchemaTest.php` | Tes Python dan Laravel |

## Memverifikasi
```bash
cd ai-service && python -m pytest -q && cd ..
php artisan migrate
vendor/bin/pest tests/Feature/TaskA
```

## Keputusan tim
- 11 kategori alasan adalah usulan awal; samakan dengan proposal sebelum Segment 04.
- Re-analisis komentar menimpa `feedback_analyses` (satu baris per komentar).
- Koordinasikan tabel dengan Task B sebelum migrasi pada database bersama.

## Aturan data
- `comment_text` adalah teks asli dan tidak boleh diubah setelah impor; hasil preprocessing disimpan pada `normalized_text`.
- `review_status` pada `ConsolidatedFinding` tidak dapat di-mass-assign; hanya alur review yang mengubahnya.
- Hanya `ConsolidatedFinding::approved()` yang boleh diserahkan ke Task B.
