import { BadRequestException, Body, Controller, Get, Post, UseGuards } from '@nestjs/common';
import { JwtAuthGuard } from '../auth/jwt-auth.guard.js';
import { StaffGuard } from '../auth/staff.guard.js';
import { TaskBService } from './task-b.service.js';
@Controller('api/v1/task-b')
@UseGuards(JwtAuthGuard, StaffGuard)
export class TaskBController {
  constructor(private readonly taskB: TaskBService) {}
  @Get('health') health() { return { status: 'ok', module: 'task_b', runtime: 'node' }; }
  @Post('calculate-ipa') calculate(@Body() body: unknown) {
    try { return this.taskB.calculateIpa(body); } catch (error) { throw new BadRequestException(error instanceof Error ? error.message : 'Invalid IPA request.'); }
  }
  @Post('validate-findings') validate(@Body() body: unknown) {
    try { return this.taskB.validateApprovedFindings(body); } catch (error) { throw new BadRequestException(error instanceof Error ? error.message : 'Invalid findings payload.'); }
  }
}
