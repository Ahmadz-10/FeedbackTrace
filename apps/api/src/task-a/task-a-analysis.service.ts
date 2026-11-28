import { Injectable } from '@nestjs/common';
import { z } from 'zod';
import { GroqService } from '../shared/groq.service.js';
import { ASPECT_TAXONOMY, REASON_CATEGORIES } from '@feedbacktrace/contracts';

const extractionSchema = z.object({
  mentions: z.array(z.object({
    aspect_code: z.string(),
    aspect_label: z.string().optional(),
    sentiment: z.enum(['positive', 'negative', 'neutral', 'mixed']),
    sentiment_strength: z.enum(['low', 'medium', 'high']).optional(),
    reason_type: z.enum(['satisfaction_driver', 'dissatisfaction_driver', 'neutral_observation']),
    reason_category: z.string(),
    reason_description: z.string().optional(),
    evidence_quotes: z.array(z.string()).default([]),
  })).max(10),
});

export interface AnalyzedMention {
  aspectCode: string;
  proposedAspectLabel?: string;
  isProposedAspect: boolean;
  sentiment: 'positive' | 'negative' | 'neutral' | 'mixed';
  sentimentStrength?: 'low' | 'medium' | 'high';
  reasonType: 'satisfaction_driver' | 'dissatisfaction_driver' | 'neutral_observation';
  reasonCategory: string;
  reasonDescription?: string;
  evidence: Array<{ quote: string; startChar: number | null; endChar: number | null; status: 'valid' | 'invalid' }>;
}

@Injectable()
export class TaskAAnalysisService {
  constructor(private readonly groq: GroqService) {}

  async analyzeComment(commentText: string, options: { allowProposedAspects: boolean; maxAspects: number; languageHint?: string }) {
    const system = [
      'Extract aspect-level student feedback into strict JSON only.',
      'Return {"mentions": []}. Each mention has one aspect, its sentiment, reason category, short reason, and exact evidence quotes.',
      'Never fabricate quotes, facts, names, policies or unsupported reasons.',
      'Use only allowed aspect and reason codes unless proposed aspects are enabled.',
      'Allowed aspects: ' + ASPECT_TAXONOMY.map((x) => x.code).join(', '),
      'Allowed reasons: ' + REASON_CATEGORIES.join(', '),
    ].join('\n');
    const user = JSON.stringify({ comment: commentText, languageHint: options.languageHint ?? 'auto', maxAspects: options.maxAspects, allowProposedAspects: options.allowProposedAspects });
    const response = await this.groq.completeJson(system, user);
    let raw: unknown;
    try { raw = JSON.parse(response.content); } catch { throw new Error('LLM response was not valid JSON.'); }
    const parsed = extractionSchema.safeParse(raw);
    if (!parsed.success) throw new Error('LLM response did not match Task A schema.');

    const aspectSet = new Set(ASPECT_TAXONOMY.map((x) => x.code));
    const reasonSet = new Set<string>(REASON_CATEGORIES);
    const mentions: AnalyzedMention[] = [];
    for (const item of parsed.data.mentions.slice(0, options.maxAspects)) {
      const known = aspectSet.has(item.aspect_code);
      if (!known && !options.allowProposedAspects) continue;
      const proposed = !known;
      const category = reasonSet.has(item.reason_category) ? item.reason_category : 'other_reason';
      const evidence = item.evidence_quotes.slice(0, 3).filter(Boolean).map((quote) => {
        const start = commentText.indexOf(quote);
        return { quote, startChar: start >= 0 ? start : null, endChar: start >= 0 ? start + quote.length : null, status: start >= 0 ? 'valid' as const : 'invalid' as const };
      });
      mentions.push({
        aspectCode: proposed ? 'other' : item.aspect_code,
        proposedAspectLabel: proposed ? (item.aspect_label ?? item.aspect_code) : undefined,
        isProposedAspect: proposed, sentiment: item.sentiment, sentimentStrength: item.sentiment_strength,
        reasonType: item.reason_type, reasonCategory: category, reasonDescription: item.reason_description, evidence,
      });
    }
    return { mentions, qualityFlags: mentions.some((m) => m.evidence.some((e) => e.status === 'invalid')) ? ['invalid_evidence'] : [], modelId: response.modelId };
  }
}
