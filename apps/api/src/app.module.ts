import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { DatabaseModule } from './database/database.module.js';
import { QueueInfrastructureModule } from './queue/queue.module.js';
import { SharedModule } from './shared/shared.module.js';
import { AuthModule } from './auth/auth.module.js';
import { HealthController } from './health.controller.js';
import { TaskAModule } from './task-a/task-a.module.js';
import { TaskBModule } from './task-b/task-b.module.js';

@Module({
  imports: [ConfigModule.forRoot({ isGlobal: true, envFilePath: ['../../.env', '.env'] }), DatabaseModule, QueueInfrastructureModule, SharedModule, AuthModule, TaskAModule, TaskBModule],
  controllers: [HealthController],
})
export class AppModule {}
