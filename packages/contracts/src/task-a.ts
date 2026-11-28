import { z } from 'zod';

export const sentimentSchema = z.enum(['positive', 'negative', 'neutral', 'mixed']);
export const sentimentStrengthSchema = z.enum(['low', 'medium', 'high']);
export const reasonTypeSchema = z.enum(['satisfaction_driver', 'dissatisfaction_driver', 'neutral_observation']);
export const findingTypeSchema = z.enum(['service_strength', 'service_weakness', 'mixed', 'observation']);
export const reviewStatusSchema = z.enum(['pending_review', 'approved', 'rejected']);

export const feedbackCsvRowSchema = z.object({
  feedback_id: z.string().trim().min(1).max(100),
  question_id: z.string().trim().min(1).max(100),
  comment_text: z.string().max(20000),
  submitted_at: z.string().optional().nullable(),
  language_hint: z.string().max(20).optional().nullable(),
});

export const taskAReviewSchema = z.object({
  action: z.enum(['approve', 'edit', 'reject']),
  note: z.string().max(2000).optional(),
  issueTitle: z.string().trim().min(1).max(255).optional(),
  issueSummary: z.string().trim().min(1).max(2000).optional(),
}).superRefine((value, ctx) => {
  if (value.action === 'edit' && !value.issueTitle && !value.issueSummary) {
    ctx.addIssue({ code: 'custom', message: 'An edit must change the title or summary.' });
  }
  if (value.action !== 'edit' && (value.issueTitle || value.issueSummary)) {
    ctx.addIssue({ code: 'custom', message: 'Only edit actions may contain title or summary changes.' });
  }
});

export type Sentiment = z.infer<typeof sentimentSchema>;
export type FindingType = z.infer<typeof findingTypeSchema>;
export type ReviewStatus = z.infer<typeof reviewStatusSchema>;
export type TaskAReviewInput = z.infer<typeof taskAReviewSchema>;

export const TASK_A_PROMPT_VERSION = 'extraction_v1';
export const TASK_A_PIPELINE_VERSION = 'task-a-node-1.0.0';

export const ASPECT_TAXONOMY = [
  { code: 'procedure_clarity', name: 'Kejelasan prosedur', keywords: ['prosedur', 'alur', 'langkah', 'procedure', 'process'] },
  { code: 'information_consistency', name: 'Konsistensi informasi', keywords: ['informasi', 'berbeda', 'information'] },
  { code: 'service_speed', name: 'Kecepatan layanan', keywords: ['antre', 'antrian', 'tunggu', 'lama', 'cepat', 'waiting', 'queue'] },
  { code: 'staff_responsiveness', name: 'Daya tanggap petugas', keywords: ['respon', 'respons', 'membalas', 'tanggapan', 'response'] },
  { code: 'staff_professionalism', name: 'Profesionalisme petugas', keywords: ['ramah', 'sopan', 'petugas', 'staff', 'profesional'] },
  { code: 'service_accessibility', name: 'Aksesibilitas layanan', keywords: ['akses', 'lokasi', 'jam layanan', 'access'] },
  { code: 'digital_service_usability', name: 'Kemudahan layanan digital', keywords: ['portal', 'website', 'aplikasi', 'login', 'sistem', 'system'] },
  { code: 'requirement_clarity', name: 'Kejelasan persyaratan', keywords: ['syarat', 'dokumen', 'persyaratan', 'requirement'] },
  { code: 'service_availability', name: 'Ketersediaan layanan', keywords: ['tutup', 'jadwal', 'tersedia', 'kuota', 'availability'] },
  { code: 'other', name: 'Lainnya', keywords: [] },
] as const;

export const REASON_CATEGORIES = [
  'unclear_instructions', 'conflicting_information', 'long_waiting_time',
  'slow_or_no_response', 'unhelpful_or_unprofessional_staff', 'hard_to_access',
  'system_technical_issue', 'clear_guidance', 'fast_service',
  'helpful_professional_staff', 'other_reason',
] as const;
