import { Processor, WorkerHost } from '@nestjs/bullmq';
import { Job } from 'bullmq';
import { TaskAService } from './task-a.service.js';
@Processor('task-a-analysis', { concurrency: 2 })
export class TaskAProcessor extends WorkerHost {
  constructor(private readonly taskA: TaskAService) { super(); }
  async process(job: Job<{ batchId: number }>): Promise<unknown> {
    if (job.name !== 'analyze-batch') return { ignored: true };
    return this.taskA.runBatch(job.data.batchId);
  }
}
