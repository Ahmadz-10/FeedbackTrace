import { z } from 'zod';

export const ipaRequestSchema = z.object({
  surveyId: z.string().min(1).optional(),
  responses: z.array(z.object({
    aspectCode: z.string().min(1).max(80),
    importance: z.number().min(1).max(5),
    performance: z.number().min(1).max(5),
  })).min(1).max(10000),
});

export const approvedFindingSchema = z.object({
  findingId: z.string().min(1),
  reviewStatus: z.literal('approved'),
  aspectCode: z.string().min(1),
  issueTitle: z.string().min(1),
  issueSummary: z.string().min(1),
  uniqueFeedbackCount: z.number().int().nonnegative(),
  sentimentDistribution: z.record(z.string(), z.number().int().nonnegative()),
});

export type IpaRequest = z.infer<typeof ipaRequestSchema>;
export type IpaQuadrant = 'concentrate_here' | 'keep_up_good_work' | 'low_priority' | 'possible_overkill';
export interface IpaAspectResult {
  aspectCode: string;
  averageImportance: number;
  averagePerformance: number;
  quadrant: IpaQuadrant;
  responseCount: number;
}
