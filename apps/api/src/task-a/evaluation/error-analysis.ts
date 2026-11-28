export type ErrorCategory = 'aspect_false_positive'|'aspect_false_negative'|'sentiment_error'|'reason_error'|'evidence_error'|'consolidation_merge'|'consolidation_split';
export interface ErrorCase { feedbackId: string; category: ErrorCategory; expected: string; predicted: string; note?: string }
export function summarizeErrors(cases: ErrorCase[]) {
  const counts = new Map<ErrorCategory, number>();
  cases.forEach((item) => counts.set(item.category, (counts.get(item.category) ?? 0) + 1));
  return { total: cases.length, byCategory: Object.fromEntries(counts), cases };
}
