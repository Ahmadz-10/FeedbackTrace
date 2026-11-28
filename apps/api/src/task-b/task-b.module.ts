import { Module } from '@nestjs/common';
import { TaskBController } from './task-b.controller.js';
import { TaskBService } from './task-b.service.js';
@Module({ controllers: [TaskBController], providers: [TaskBService] })
export class TaskBModule {}
