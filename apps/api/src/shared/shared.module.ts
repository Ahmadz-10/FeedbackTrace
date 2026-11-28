import { Module } from '@nestjs/common';
import { GroqService } from './groq.service.js';
import { EmbeddingService } from './embedding.service.js';
@Module({ providers: [GroqService, EmbeddingService], exports: [GroqService, EmbeddingService] })
export class SharedModule {}
