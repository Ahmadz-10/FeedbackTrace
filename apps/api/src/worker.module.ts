import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { DatabaseModule } from './database/database.module.js';
import { QueueInfrastructureModule } from './queue/queue.module.js';
import { SharedModule } from './shared/shared.module.js';
import { TaskAModule } from './task-a/task-a.module.js';
import { TaskAProcessor } from './task-a/task-a.processor.js';

@Module({
  imports: [ConfigModule.forRoot({ isGlobal: true }), DatabaseModule, QueueInfrastructureModule, SharedModule, TaskAModule],
  providers: [TaskAProcessor],
})
export class WorkerModule {}
