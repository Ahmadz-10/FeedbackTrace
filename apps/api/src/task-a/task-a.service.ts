import { BadRequestException, ConflictException, Injectable, NotFoundException } from '@nestjs/common';
import { InjectQueue } from '@nestjs/bullmq';
import { Queue } from 'bullmq';
import { parse } from 'csv-parse/sync';
import { randomUUID } from 'node:crypto';
import { Prisma } from '@feedbacktrace/database';
import { PrismaService } from '../database/prisma.service.js';
import { TaskAAnalysisService, type AnalyzedMention } from './task-a-analysis.service.js';
import type { ImportBatchDto, ReviewFindingDto } from './task-a.dto.js';
import { ASPECT_BY_CODE, TASK_A_PIPELINE_VERSION, TASK_A_PROMPT_VERSION } from './task-a.constants.js';

interface CsvRow {
  feedback_id?: string;
  question_id?: string;
  comment_text?: string;
  submitted_at?: string;
  language_hint?: string;
}

@Injectable()
export class TaskAService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly analysis: TaskAAnalysisService,
    @InjectQueue('task-a-analysis') private readonly queue: Queue,
  ) {}

  async importCsv(buffer: Buffer, filename: string, body: ImportBatchDto, userId: number) {
    let rows: CsvRow[];
    try {
      rows = parse(buffer, {
        bom: true,
        columns: (header: string[]) => {
          const names = header.map((name) => name.trim().toLowerCase());
          const required = ['feedback_id', 'question_id', 'comment_text'];
          if (new Set(names).size !== names.length) throw new Error('CSV headers must be unique.');
          if (required.some((name) => !names.includes(name))) throw new Error('Missing one or more required CSV headers.');
          return names;
        },
        skip_empty_lines: true,
      }) as CsvRow[];
    } catch {
      throw new BadRequestException('The uploaded CSV could not be parsed.');
    }
    if (!rows.length) throw new BadRequestException('The CSV has no feedback records.');
    if (rows.length > 5000) throw new BadRequestException('A batch may contain at most 5,000 feedback records.');

    const seen = new Set<string>();
    const records = rows.map((row, index) => {
      const feedbackId = String(row.feedback_id ?? '').trim();
      const questionId = String(row.question_id ?? '').trim();
      const commentText = String(row.comment_text ?? '');
      if (!feedbackId || !questionId) throw new BadRequestException('CSV row ' + (index + 2) + ' requires feedback_id and question_id.');
      if (seen.has(feedbackId)) throw new BadRequestException('Duplicate feedback_id: ' + feedbackId);
      if (feedbackId.length > 100 || questionId.length > 100 || commentText.length > 20000) {
        throw new BadRequestException('CSV row ' + (index + 2) + ' exceeds a field length limit.');
      }
      seen.add(feedbackId);
      let submittedAt: Date | undefined;
      if (row.submitted_at) {
        submittedAt = new Date(row.submitted_at);
        if (Number.isNaN(submittedAt.getTime())) throw new BadRequestException('Invalid submitted_at in CSV row ' + (index + 2) + '.');
      }
      return {
        feedbackId,
        questionId,
        commentText,
        languageHint: row.language_hint?.slice(0, 20) || null,
        submittedAt,
      };
    });

    const allowProposedAspects = body.allowProposedAspects === true || String(body.allowProposedAspects) === 'true';
    const maxAspectsPerFeedback = Math.min(10, Math.max(1, body.maxAspectsPerFeedback ?? 5));
    const batch = await this.prisma.feedbackBatch.create({
      data: {
        batchCode: 'BATCH-' + randomUUID().slice(0, 12).toUpperCase(),
        title: body.title || filename.replace(/\.csv$/i, ''),
        source: body.source || 'csv_import',
        createdBy: userId,
        options: { allowProposedAspects, maxAspectsPerFeedback },
        records: { createMany: { data: records } },
      },
      include: { _count: { select: { records: true } } },
    });

    const job = await this.queue.add('analyze-batch', { batchId: batch.id }, {
      jobId: 'task-a-batch-' + batch.id,
      attempts: 3,
      backoff: { type: 'exponential', delay: 15000 },
      removeOnComplete: 500,
      removeOnFail: 1000,
    });
    await this.prisma.feedbackBatch.update({
      where: { id: batch.id },
      data: { aiJobId: String(job.id), status: 'pending' },
    });

    return { batchId: batch.id, batchCode: batch.batchCode, status: 'pending', feedbackCount: batch._count.records, jobId: String(job.id) };
  }

  async enqueueBatch(batchId: number) {
    const batch = await this.prisma.feedbackBatch.findUnique({ where: { id: batchId } });
    if (!batch) throw new NotFoundException('Batch not found.');
    if (await this.prisma.findingReview.count({ where: { finding: { batchId } } })) {
      throw new ConflictException('A batch with reviewed findings cannot be re-analysed.');
    }
    const job = await this.queue.add('analyze-batch', { batchId }, {
      jobId: 'task-a-batch-' + batchId + '-' + Date.now(),
      attempts: 3,
      backoff: { type: 'exponential', delay: 15000 },
    });
    await this.prisma.feedbackBatch.update({
      where: { id: batchId },
      data: { aiJobId: String(job.id), status: 'pending', errorMessage: null },
    });
    return { batchId, jobId: String(job.id), status: 'pending' };
  }

  listBatches() {
    return this.prisma.feedbackBatch.findMany({
      orderBy: { createdAt: 'desc' },
      take: 100,
      include: { _count: { select: { records: true, findings: true } } },
    });
  }

  async getBatch(id: number) {
    const batch = await this.prisma.feedbackBatch.findUnique({
      where: { id },
      include: { _count: { select: { records: true, findings: true } } },
    });
    if (!batch) throw new NotFoundException('Batch not found.');
    return batch;
  }

  async getBatchResults(id: number) {
    const batch = await this.prisma.feedbackBatch.findUnique({
      where: { id },
      include: {
        records: { orderBy: { id: 'asc' }, include: { analysis: { include: { aspectMentions: { include: { evidence: true } } } } } },
        findings: true,
      },
    });
    if (!batch) throw new NotFoundException('Batch not found.');
    return batch;
  }

  async getBatchSummary(id: number) {
    const batch = await this.prisma.feedbackBatch.findUnique({
      where: { id },
      include: { _count: { select: { records: true, findings: true } } },
    });
    if (!batch) throw new NotFoundException('Batch not found.');

    const [analysed, skipped, failed, mentions] = await Promise.all([
      this.prisma.feedbackAnalysis.count({ where: { record: { batchId: id }, status: 'analyzed' } }),
      this.prisma.feedbackAnalysis.count({ where: { record: { batchId: id }, status: 'skipped' } }),
      this.prisma.feedbackAnalysis.count({ where: { record: { batchId: id }, status: 'failed' } }),
      this.prisma.feedbackAspectMention.groupBy({
        by: ['aspectCode', 'sentiment'],
        where: { record: { batchId: id } },
        _count: { _all: true },
      }),
    ]);

    return {
      batchId: id,
      batchCode: batch.batchCode,
      status: batch.status,
      stats: { totalFeedbacks: batch._count.records, analysed, skipped, failed, totalFindings: batch._count.findings },
      mentions,
    };
  }

  listFindings() {
    return this.prisma.consolidatedFinding.findMany({
      orderBy: { createdAt: 'desc' },
      take: 200,
      include: { batch: { select: { id: true, batchCode: true, title: true } } },
    });
  }

  async getFinding(code: string) {
    const finding = await this.prisma.consolidatedFinding.findUnique({
      where: { findingCode: code },
      include: {
        batch: true,
        reviews: { orderBy: { createdAt: 'desc' }, include: { reviewer: { select: { id: true, name: true } } } },
        feedbackLinks: {
          include: {
            feedbackRecord: {
              include: { analysis: { include: { aspectMentions: { include: { evidence: true } } } } },
            },
          },
        },
      },
    });
    if (!finding) throw new NotFoundException('Finding not found.');
    return finding;
  }

  async reviewFinding(code: string, input: ReviewFindingDto, reviewerId: number) {
    const finding = await this.prisma.consolidatedFinding.findUnique({ where: { findingCode: code } });
    if (!finding) throw new NotFoundException('Finding not found.');
    if (finding.reviewStatus !== 'pending_review') throw new ConflictException('This finding has already received a final review decision.');
    if (input.action === 'edit' && !input.issueTitle && !input.issueSummary) throw new BadRequestException('Edit requires a changed title or summary.');
    if (input.action !== 'edit' && (input.issueTitle || input.issueSummary)) throw new BadRequestException('Only edit actions may change the finding title or summary.');

    return this.prisma.$transaction(async (tx) => {
      const current = await tx.consolidatedFinding.findUnique({ where: { id: finding.id } });
      if (!current || current.reviewStatus !== 'pending_review') throw new ConflictException('Finding is no longer pending review.');

      const changes: Record<string, { old: string; new: string }> = {};
      if (input.action === 'edit') {
        if (input.issueTitle && input.issueTitle !== current.issueTitle) changes.issueTitle = { old: current.issueTitle, new: input.issueTitle };
        if (input.issueSummary && input.issueSummary !== current.issueSummary) changes.issueSummary = { old: current.issueSummary, new: input.issueSummary };
        if (!Object.keys(changes).length) throw new BadRequestException('The edit did not change any field.');
      }

      const newStatus = input.action === 'approve' ? 'approved' : input.action === 'reject' ? 'rejected' : 'pending_review';
      const updated = await tx.consolidatedFinding.update({
        where: { id: current.id },
        data: {
          ...(changes.issueTitle ? { issueTitle: changes.issueTitle.new } : {}),
          ...(changes.issueSummary ? { issueSummary: changes.issueSummary.new } : {}),
          reviewStatus: newStatus,
          reviewedAt: input.action === 'edit' ? null : new Date(),
        },
      });
      const review = await tx.findingReview.create({
        data: {
          findingId: current.id,
          reviewerId,
          action: input.action,
          previousStatus: current.reviewStatus,
          newStatus,
          changes: Object.keys(changes).length ? changes as Prisma.InputJsonValue : Prisma.JsonNull,
          note: input.note,
        },
      });
      return { finding: updated, review };
    }, { isolationLevel: Prisma.TransactionIsolationLevel.Serializable });
  }

  async runBatch(batchId: number) {
    const batch = await this.prisma.feedbackBatch.findUnique({
      where: { id: batchId },
      include: { records: { orderBy: { id: 'asc' } } },
    });
    if (!batch) throw new NotFoundException('Batch not found.');
    if (!batch.records.length) throw new BadRequestException('Batch contains no feedback.');
    if (await this.prisma.findingReview.count({ where: { finding: { batchId } } })) {
      throw new ConflictException('A batch with reviewed findings cannot be re-analysed.');
    }

    await this.prisma.feedbackBatch.update({
      where: { id: batchId },
      data: { status: 'processing', startedAt: new Date(), errorMessage: null },
    });

    const options = (batch.options ?? {}) as { allowProposedAspects?: boolean; maxAspectsPerFeedback?: number };
    const results: Array<{
      recordId: number; feedbackId: string; commentText: string; status: string;
      mentions: AnalyzedMention[]; qualityFlags: string[]; error?: string; modelId?: string;
    }> = [];

    for (const record of batch.records) {
      if (!record.commentText.trim()) {
        results.push({ recordId: record.id, feedbackId: record.feedbackId, commentText: record.commentText, status: 'skipped', mentions: [], qualityFlags: ['empty_comment'] });
        continue;
      }
      try {
        const analyzed = await this.analysis.analyzeComment(record.commentText, {
          allowProposedAspects: options.allowProposedAspects ?? false,
          maxAspects: options.maxAspectsPerFeedback ?? 5,
          languageHint: record.languageHint ?? undefined,
        });
        results.push({
          recordId: record.id, feedbackId: record.feedbackId, commentText: record.commentText,
          status: 'analyzed', mentions: analyzed.mentions, qualityFlags: analyzed.qualityFlags, modelId: analyzed.modelId,
        });
      } catch (error) {
        results.push({
          recordId: record.id, feedbackId: record.feedbackId, commentText: record.commentText,
          status: 'failed', mentions: [], qualityFlags: ['analysis_failed'],
          error: error instanceof Error ? error.message : 'Unknown analysis error',
        });
      }
    }

    const groups = new Map<string, Array<{ recordId: number; feedbackId: string; mention: AnalyzedMention }>>();
    for (const result of results) {
      for (const mention of result.mentions) {
        const key = mention.aspectCode + '::' + mention.reasonCategory;
        const group = groups.get(key) ?? [];
        group.push({ recordId: result.recordId, feedbackId: result.feedbackId, mention });
        groups.set(key, group);
      }
    }

    try {
      await this.prisma.$transaction(async (tx) => {
        await tx.feedbackAnalysis.deleteMany({ where: { record: { batchId } } });
        await tx.consolidatedFinding.deleteMany({ where: { batchId } });

        for (const result of results) {
          await tx.feedbackRecord.update({
            where: { id: result.recordId },
            data: { qualityFlags: result.qualityFlags as Prisma.InputJsonValue },
          });
          await tx.feedbackAnalysis.create({
            data: {
              feedbackRecordId: result.recordId,
              status: result.status,
              provider: result.modelId ? 'groq' : null,
              modelId: result.modelId,
              promptVersion: TASK_A_PROMPT_VERSION,
              pipelineVersion: TASK_A_PIPELINE_VERSION,
              schemaVersion: '1.0',
              qualityFlags: result.qualityFlags as Prisma.InputJsonValue,
              errorMessage: result.error,
              aspectMentions: {
                create: result.mentions.map((mention) => ({
                  feedbackRecordId: result.recordId,
                  mentionCode: randomUUID(),
                  aspectCode: mention.aspectCode,
                  isProposedAspect: mention.isProposedAspect,
                  proposedAspectLabel: mention.proposedAspectLabel,
                  sentiment: mention.sentiment,
                  sentimentStrength: mention.sentimentStrength,
                  reasonType: mention.reasonType,
                  reasonCategory: mention.reasonCategory,
                  reasonDescription: mention.reasonDescription,
                  evidence: {
                    create: mention.evidence.map((evidence) => ({
                      feedbackRecordId: result.recordId,
                      quote: evidence.quote,
                      startChar: evidence.startChar,
                      endChar: evidence.endChar,
                      status: evidence.status,
                    })),
                  },
                })),
              },
            },
          });
        }

        for (const group of groups.values()) {
          const counts = { positive: 0, negative: 0, neutral: 0, mixed: 0 };
          for (const item of group) counts[item.mention.sentiment] += 1;
          const dominant = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0] ?? 'neutral';
          const findingType = dominant === 'positive' ? 'service_strength' : dominant === 'negative' ? 'service_weakness' : dominant === 'mixed' ? 'mixed' : 'observation';
          const first = group[0];
          const aspect = ASPECT_BY_CODE.get(first.mention.aspectCode);
          const feedbackIds = [...new Set(group.map((item) => item.feedbackId))];
          const descriptions = [...new Set(group.map((item) => item.mention.reasonDescription).filter((value): value is string => Boolean(value)))];
          const finding = await tx.consolidatedFinding.create({
            data: {
              batchId,
              findingCode: 'FND-' + randomUUID().slice(0, 12).toUpperCase(),
              aspectCode: first.mention.aspectCode,
              proposedAspectLabel: first.mention.proposedAspectLabel,
              reasonCategory: first.mention.reasonCategory,
              findingType,
              issueTitle: (aspect?.name ?? first.mention.proposedAspectLabel ?? first.mention.aspectCode) + ': ' + first.mention.reasonCategory.replaceAll('_', ' '),
              issueSummary: descriptions.slice(0, 3).join(' ') || 'Feedback mentions ' + first.mention.reasonCategory.replaceAll('_', ' ') + '.',
              uniqueFeedbackCount: feedbackIds.length,
              mentionCount: group.length,
              sentimentDistribution: counts,
              minorityViews: group.filter((item) => item.mention.sentiment !== dominant).map((item) => ({
                feedback_id: item.feedbackId,
                sentiment: item.mention.sentiment,
                summary: item.mention.reasonDescription ?? item.mention.reasonCategory,
              })) as Prisma.InputJsonValue,
              singleFeedbackSupport: feedbackIds.length === 1,
            },
          });
          await tx.findingFeedback.createMany({
            data: [...new Set(group.map((item) => item.recordId))].map((feedbackRecordId) => ({ findingId: finding.id, feedbackRecordId })),
          });
        }

        const analyzedCount = results.filter((item) => item.status === 'analyzed').length;
        const skippedCount = results.filter((item) => item.status === 'skipped').length;
        const failedCount = results.filter((item) => item.status === 'failed').length;
        const validEvidence = results.reduce((sum, item) => sum + item.mentions.reduce((count, mention) => count + mention.evidence.filter((e) => e.status === 'valid').length, 0), 0);
        const evidenceTotal = results.reduce((sum, item) => sum + item.mentions.reduce((count, mention) => count + mention.evidence.length, 0), 0);
        const status = failedCount === results.length ? 'failed' : failedCount > 0 ? 'partially_completed' : 'completed';
        const models = [...new Set(results.map((item) => item.modelId).filter((model): model is string => Boolean(model)))];

        await tx.feedbackBatch.update({
          where: { id: batchId },
          data: {
            status,
            finishedAt: new Date(),
            modelMetadata: {
              provider: 'groq',
              modelId: models.join(','),
              promptVersion: TASK_A_PROMPT_VERSION,
              pipelineVersion: TASK_A_PIPELINE_VERSION,
              schemaVersion: '1.0',
              taxonomyVersion: '1.0',
              decoding: { temperature: 0, jsonMode: true, maxCompletionTokens: 4096 },
            },
            stats: {
              totalFeedbacks: results.length,
              analyzedFeedbacks: analyzedCount,
              skippedFeedbacks: skippedCount,
              failedFeedbacks: failedCount,
              totalFindings: groups.size,
              validEvidenceCount: validEvidence,
              invalidEvidenceCount: evidenceTotal - validEvidence,
            },
          },
        });
      });
    } catch (error) {
      await this.prisma.feedbackBatch.update({
        where: { id: batchId },
        data: { status: 'failed', errorMessage: error instanceof Error ? error.message.slice(0, 500) : 'Persistence failed.', finishedAt: new Date() },
      });
      throw error;
    }

    const analysed = results.filter((item) => item.status === 'analyzed').length;
    const skipped = results.filter((item) => item.status === 'skipped').length;
    const failed = results.filter((item) => item.status === 'failed').length;
    return { batchId, status: failed === results.length ? 'failed' : failed > 0 ? 'partially_completed' : 'completed', totalFeedbacks: results.length, analyzed: analysed, skipped, failed, findings: groups.size };
  }
}
