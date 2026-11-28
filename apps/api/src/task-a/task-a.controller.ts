import { BadRequestException, Body, Controller, Get, Param, ParseIntPipe, Post, UploadedFile, UseGuards, UseInterceptors, Req } from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import type { Express, Request } from 'express';
import { memoryStorage } from 'multer';
import { JwtAuthGuard } from '../auth/jwt-auth.guard.js';
import { StaffGuard } from '../auth/staff.guard.js';
import { ImportBatchDto, ReviewFindingDto } from './task-a.dto.js';
import { TaskAService } from './task-a.service.js';

@Controller('api/v1/task-a')
@UseGuards(JwtAuthGuard, StaffGuard)
export class TaskAController {
  constructor(private readonly taskA: TaskAService) {}
  @Get('health') health() { return { status: 'ok', module: 'task_a', runtime: 'node' }; }
  @Post('batches')
  @UseInterceptors(FileInterceptor('file', { storage: memoryStorage(), limits: { fileSize: 10 * 1024 * 1024 } }))
  async importBatch(@UploadedFile() file: Express.Multer.File | undefined, @Body() body: ImportBatchDto, @Req() req: Request & { user: { id: number } }) {
    if (!file) throw new BadRequestException('A CSV file is required.');
    return this.taskA.importCsv(file.buffer, file.originalname, body, req.user.id);
  }
  @Get('batches') listBatches() { return this.taskA.listBatches(); }
  @Get('batches/:id') getBatch(@Param('id', ParseIntPipe) id: number) { return this.taskA.getBatch(id); }
  @Post('batches/:id/analyze') analyze(@Param('id', ParseIntPipe) id: number) { return this.taskA.enqueueBatch(id); }
  @Get('batches/:id/results') results(@Param('id', ParseIntPipe) id: number) { return this.taskA.getBatchResults(id); }
  @Get('batches/:id/summary') summary(@Param('id', ParseIntPipe) id: number) { return this.taskA.getBatchSummary(id); }
  @Get('findings') findings() { return this.taskA.listFindings(); }
  @Get('findings/:code') finding(@Param('code') code: string) { return this.taskA.getFinding(code); }
  @Post('findings/:code/review')
  review(@Param('code') code: string, @Body() body: ReviewFindingDto, @Req() req: Request & { user: { id: number } }) {
    return this.taskA.reviewFinding(code, body, req.user.id);
  }
}
