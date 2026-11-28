import { Module } from '@nestjs/common';
import { BullModule } from '@nestjs/bullmq';
import { TaskAController } from './task-a.controller.js';
import { TaskAService } from './task-a.service.js';
import { TaskAAnalysisService } from './task-a-analysis.service.js';
@Module({
  imports: [BullModule.registerQueue({ name: 'task-a-analysis' })],
  controllers: [TaskAController], providers: [TaskAService, TaskAAnalysisService], exports: [TaskAService],
})
export class TaskAModule {}
