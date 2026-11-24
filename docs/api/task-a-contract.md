# Kontrak Data Task A: Intelijen Umpan Balik Mahasiswa

**Versi schema:** `1.0` · **Versi taxonomy:** `1.0` · **Versi kategori alasan:** `1.0`

Sumber kebenaran kode: `ai-service/app/task_a/config/` dan `ai-service/app/task_a/schemas/`.
Persistensi dimiliki Laravel; FastAPI memproses dan mengembalikan JSON terstruktur.

## 1. Alur data

```
Laravel (impor) -> POST /api/v1/task-a/analyze -> FastAPI (validasi, LLM, bukti, konsolidasi)
                <- JSON hasil <- Laravel menyimpan via Eloquent -> staf review -> approved -> Task B
```

## 2. Request: `POST /api/v1/task-a/analyze`

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `request_id` | string ≤100 | tidak | ID pelacakan dari Laravel |
| `batch.batch_id` | string ≤100 | ya | Identitas batch |
| `batch.title`, `batch.source` | string | tidak | Metadata |
| `batch.schema_version` | string | tidak | Default `1.0`; versi divalidasi |
| `options.max_aspects_per_feedback` | int 1–10 | tidak | Default 5 |
| `options.allow_proposed_aspects` | bool | tidak | Default true |
| `options.enable_consolidation` | bool | tidak | Default true |
| `options.use_embeddings` | bool | tidak | Default false |
| `options.max_retries` | int 0–3 | tidak | Default 2 |
| `feedbacks[]` | array 1–500 | ya | Daftar komentar |
| `feedbacks[].feedback_id` | string ≤100 | ya | Unik dalam batch |
| `feedbacks[].question_id` | string ≤100 | ya | ID pertanyaan |
| `feedbacks[].comment_text` | string | ya | Teks asli; komentar bermasalah diberi quality flag |
| `feedbacks[].submitted_at` | datetime | direkomendasikan | ISO 8601 |
| `feedbacks[].language_hint` | string | tidak | mis. `id`, `en` |

Field tak dikenal ditolak. Komentar kosong tidak otomatis menggagalkan batch; komentar ditandai untuk dilewati di Segment 03.

## 3. Label dan taksonomi

- Sentimen: `positive`, `negative`, `neutral`, `mixed`.
- Kekuatan sentimen: `low`, `medium`, `high`.
- Jenis alasan: `satisfaction_driver`, `dissatisfaction_driver`, `neutral_observation`.
- Jenis temuan: `service_strength`, `service_weakness`, `mixed`, `observation`.
- Status batch: `pending`, `processing`, `completed`, `partially_completed`, `failed`.
- Status analisis: `analyzed`, `skipped`, `failed`.
- Status bukti: `unverified`, `valid`, `invalid`.
- Status review: `pending_review`, `approved`, `rejected`.

Kode aspek (10): `procedure_clarity`, `information_consistency`, `service_speed`, `staff_responsiveness`, `staff_professionalism`, `service_accessibility`, `digital_service_usability`, `requirement_clarity`, `service_availability`, `other`.

Kategori alasan (11): `unclear_instructions`, `conflicting_information`, `long_waiting_time`, `slow_or_no_response`, `unhelpful_or_unprofessional_staff`, `hard_to_access`, `system_technical_issue`, `clear_guidance`, `fast_service`, `helpful_professional_staff`, `other_reason`. Daftar kategori ini masih usulan awal dan perlu disamakan dengan proposal.

## 4. Response: `AnalyzeResponse`

Field utama: `request_id`, `job_id`, `batch_id`, `status`, `metadata`, `stats`, `results[]`, `findings[]`, `warnings[]`.

- `metadata`: provider, model aktual, prompt/pipeline/schema/taxonomy version, decoding.
- `stats`: total/analyzed/skipped/failed feedback, mentions, bukti valid/invalid, findings, processing time.
- `results[]`: feedback_id, status, quality_flags, mentions, error.
- `mentions[]`: mention_id, feedback_id, aspect, proposed aspect, sentiment, strength, reason type/category/description, evidence.
- `evidence[]`: quote, feedback_id, start_char, end_char, status. Offset dihitung Python; kutipan yang tidak cocok dengan teks asli tidak valid.
- `findings[]`: finding_id, aspect, category, type, title, summary, counts, supporting IDs, sentiment distribution, minority views, single-feedback flag, quality flags, review status.
- `warnings[]`: code, message, feedback_id opsional.

Count dan distribusi dihitung oleh kode Python dari catatan terverifikasi, bukan oleh LLM.

## 5. Relasi tabel

```
feedback_batches 1──* feedback_records 1──1 feedback_analyses 1──* feedback_aspect_mentions 1──* feedback_evidence
feedback_batches 1──* consolidated_findings *──* feedback_records (finding_feedback)
consolidated_findings 1──* finding_reviews
```

| Tabel | Model | Aturan |
|---|---|---|
| `feedback_batches` | FeedbackBatch | batch_code unik; menyimpan status, metadata model, statistik |
| `feedback_records` | FeedbackRecord | unik (batch_id, feedback_id); teks asli dan normalisasi terpisah |
| `feedback_analyses` | FeedbackAnalysis | satu per komentar |
| `feedback_aspect_mentions` | FeedbackAspectMention | indeks aspek/sentimen |
| `feedback_evidence` | FeedbackEvidence | unverified/valid/invalid |
| `consolidated_findings` | ConsolidatedFinding | finding_code unik; review status tidak mass-assignable |
| `finding_feedback` | pivot | unik (finding_id, feedback_record_id) |
| `finding_reviews` | FindingReview | catatan audit |

## 6. Kontrak serah terima Task B

Hanya temuan dengan `review_status = approved` (scope `ConsolidatedFinding::approved()`) yang menjadi masukan resmi Task B. Temuan pending dan rejected bukan input Task B.

Field: schema_version, finding_id, batch_id, aspect_code, reason_category, finding_type, issue_title, issue_summary, unique_feedback_count, mention_count, supporting_feedback_ids, sentiment_distribution, minority_views, review_status, reviewed_at.

Task A tidak menghitung IPA, retrieval dokumen, RAG, atau rekomendasi.

## 7. Perubahan versi

Jika kontrak berubah, perbarui `SCHEMA_VERSION` pada `schemas/requests.py` dan dokumen ini bersama-sama serta beri tahu Task B sebelum merge.
