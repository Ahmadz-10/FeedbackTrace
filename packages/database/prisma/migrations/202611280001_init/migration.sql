CREATE TYPE "UserRole" AS ENUM ('STUDENT', 'STAFF', 'ADMIN');

CREATE TABLE "users" (
  "id" SERIAL NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "email" VARCHAR(255) NOT NULL,
  "password_hash" VARCHAR(255) NOT NULL,
  "role" "UserRole" NOT NULL DEFAULT 'STUDENT',
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "users_pkey" PRIMARY KEY ("id")
);
CREATE UNIQUE INDEX "users_email_key" ON "users"("email");

CREATE TABLE "feedback_batches" (
  "id" SERIAL NOT NULL,
  "batch_code" VARCHAR(100) NOT NULL,
  "title" VARCHAR(255),
  "source" VARCHAR(100),
  "schema_version" VARCHAR(20) NOT NULL DEFAULT '1.0',
  "status" VARCHAR(30) NOT NULL DEFAULT 'pending',
  "options" JSONB,
  "ai_request_id" VARCHAR(100),
  "ai_job_id" VARCHAR(100),
  "model_metadata" JSONB,
  "stats" JSONB,
  "error_message" TEXT,
  "started_at" TIMESTAMP(3),
  "finished_at" TIMESTAMP(3),
  "created_by" INTEGER,
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "feedback_batches_pkey" PRIMARY KEY ("id")
);
CREATE UNIQUE INDEX "feedback_batches_batch_code_key" ON "feedback_batches"("batch_code");
CREATE INDEX "feedback_batches_status_idx" ON "feedback_batches"("status");
ALTER TABLE "feedback_batches" ADD CONSTRAINT "feedback_batches_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE "feedback_records" (
  "id" SERIAL NOT NULL,
  "batch_id" INTEGER NOT NULL,
  "feedback_id" VARCHAR(100) NOT NULL,
  "question_id" VARCHAR(100) NOT NULL,
  "comment_text" TEXT NOT NULL,
  "normalized_text" TEXT,
  "language_hint" VARCHAR(20),
  "submitted_at" TIMESTAMP(3),
  "quality_flags" JSONB,
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "feedback_records_pkey" PRIMARY KEY ("id")
);
CREATE UNIQUE INDEX "feedback_records_batch_id_feedback_id_key" ON "feedback_records"("batch_id","feedback_id");
CREATE INDEX "feedback_records_question_id_idx" ON "feedback_records"("question_id");
ALTER TABLE "feedback_records" ADD CONSTRAINT "feedback_records_batch_id_fkey" FOREIGN KEY ("batch_id") REFERENCES "feedback_batches"("id") ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE "feedback_analyses" (
  "id" SERIAL NOT NULL,
  "feedback_record_id" INTEGER NOT NULL,
  "status" VARCHAR(30) NOT NULL,
  "provider" VARCHAR(50),
  "model_id" VARCHAR(100),
  "prompt_version" VARCHAR(50),
  "pipeline_version" VARCHAR(50),
  "schema_version" VARCHAR(20),
  "processing_ms" INTEGER,
  "quality_flags" JSONB,
  "error_message" TEXT,
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "feedback_analyses_pkey" PRIMARY KEY ("id")
);
CREATE UNIQUE INDEX "feedback_analyses_feedback_record_id_key" ON "feedback_analyses"("feedback_record_id");
CREATE INDEX "feedback_analyses_status_idx" ON "feedback_analyses"("status");
ALTER TABLE "feedback_analyses" ADD CONSTRAINT "feedback_analyses_feedback_record_id_fkey" FOREIGN KEY ("feedback_record_id") REFERENCES "feedback_records"("id") ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE "feedback_aspect_mentions" (
  "id" SERIAL NOT NULL,
  "feedback_analysis_id" INTEGER NOT NULL,
  "feedback_record_id" INTEGER NOT NULL,
  "mention_code" VARCHAR(100),
  "aspect_code" VARCHAR(80) NOT NULL,
  "is_proposed_aspect" BOOLEAN NOT NULL DEFAULT false,
  "proposed_aspect_label" VARCHAR(255),
  "sentiment" VARCHAR(20) NOT NULL,
  "sentiment_strength" VARCHAR(20),
  "reason_type" VARCHAR(40) NOT NULL,
  "reason_category" VARCHAR(80) NOT NULL,
  "reason_description" TEXT,
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "feedback_aspect_mentions_pkey" PRIMARY KEY ("id")
);
CREATE INDEX "feedback_aspect_mentions_aspect_code_sentiment_idx" ON "feedback_aspect_mentions"("aspect_code","sentiment");
CREATE INDEX "feedback_aspect_mentions_reason_category_idx" ON "feedback_aspect_mentions"("reason_category");
ALTER TABLE "feedback_aspect_mentions" ADD CONSTRAINT "feedback_aspect_mentions_feedback_analysis_id_fkey" FOREIGN KEY ("feedback_analysis_id") REFERENCES "feedback_analyses"("id") ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE "feedback_aspect_mentions" ADD CONSTRAINT "feedback_aspect_mentions_feedback_record_id_fkey" FOREIGN KEY ("feedback_record_id") REFERENCES "feedback_records"("id") ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE "feedback_evidence" (
  "id" SERIAL NOT NULL,
  "aspect_mention_id" INTEGER NOT NULL,
  "feedback_record_id" INTEGER NOT NULL,
  "quote" TEXT NOT NULL,
  "start_char" INTEGER,
  "end_char" INTEGER,
  "status" VARCHAR(20) NOT NULL DEFAULT 'unverified',
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "feedback_evidence_pkey" PRIMARY KEY ("id")
);
CREATE INDEX "feedback_evidence_status_idx" ON "feedback_evidence"("status");
ALTER TABLE "feedback_evidence" ADD CONSTRAINT "feedback_evidence_aspect_mention_id_fkey" FOREIGN KEY ("aspect_mention_id") REFERENCES "feedback_aspect_mentions"("id") ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE "feedback_evidence" ADD CONSTRAINT "feedback_evidence_feedback_record_id_fkey" FOREIGN KEY ("feedback_record_id") REFERENCES "feedback_records"("id") ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE "consolidated_findings" (
  "id" SERIAL NOT NULL,
  "batch_id" INTEGER NOT NULL,
  "finding_code" VARCHAR(100) NOT NULL,
  "aspect_code" VARCHAR(80) NOT NULL,
  "proposed_aspect_label" VARCHAR(255),
  "reason_category" VARCHAR(80) NOT NULL,
  "finding_type" VARCHAR(30) NOT NULL,
  "issue_title" VARCHAR(255) NOT NULL,
  "issue_summary" TEXT NOT NULL,
  "unique_feedback_count" INTEGER NOT NULL DEFAULT 0,
  "mention_count" INTEGER NOT NULL DEFAULT 0,
  "sentiment_distribution" JSONB NOT NULL,
  "minority_views" JSONB,
  "single_feedback_support" BOOLEAN NOT NULL DEFAULT false,
  "quality_flags" JSONB,
  "review_status" VARCHAR(30) NOT NULL DEFAULT 'pending_review',
  "reviewed_at" TIMESTAMP(3),
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP(3) NOT NULL,
  CONSTRAINT "consolidated_findings_pkey" PRIMARY KEY ("id")
);
CREATE UNIQUE INDEX "consolidated_findings_finding_code_key" ON "consolidated_findings"("finding_code");
CREATE INDEX "consolidated_findings_batch_id_aspect_code_idx" ON "consolidated_findings"("batch_id","aspect_code");
CREATE INDEX "consolidated_findings_review_status_idx" ON "consolidated_findings"("review_status");
ALTER TABLE "consolidated_findings" ADD CONSTRAINT "consolidated_findings_batch_id_fkey" FOREIGN KEY ("batch_id") REFERENCES "feedback_batches"("id") ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE "finding_feedback" (
  "id" SERIAL NOT NULL,
  "finding_id" INTEGER NOT NULL,
  "feedback_record_id" INTEGER NOT NULL,
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "finding_feedback_pkey" PRIMARY KEY ("id")
);
CREATE UNIQUE INDEX "finding_feedback_finding_id_feedback_record_id_key" ON "finding_feedback"("finding_id","feedback_record_id");
ALTER TABLE "finding_feedback" ADD CONSTRAINT "finding_feedback_finding_id_fkey" FOREIGN KEY ("finding_id") REFERENCES "consolidated_findings"("id") ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE "finding_feedback" ADD CONSTRAINT "finding_feedback_feedback_record_id_fkey" FOREIGN KEY ("feedback_record_id") REFERENCES "feedback_records"("id") ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE "finding_reviews" (
  "id" SERIAL NOT NULL,
  "finding_id" INTEGER NOT NULL,
  "reviewer_id" INTEGER,
  "action" VARCHAR(20) NOT NULL,
  "previous_status" VARCHAR(30) NOT NULL,
  "new_status" VARCHAR(30) NOT NULL,
  "changes" JSONB,
  "note" TEXT,
  "created_at" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "finding_reviews_pkey" PRIMARY KEY ("id")
);
CREATE INDEX "finding_reviews_finding_id_created_at_idx" ON "finding_reviews"("finding_id","created_at");
ALTER TABLE "finding_reviews" ADD CONSTRAINT "finding_reviews_finding_id_fkey" FOREIGN KEY ("finding_id") REFERENCES "consolidated_findings"("id") ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE "finding_reviews" ADD CONSTRAINT "finding_reviews_reviewer_id_fkey" FOREIGN KEY ("reviewer_id") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;
