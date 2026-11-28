import { Module } from '@nestjs/common';
import { BullModule } from '@nestjs/bullmq';
import { ConfigModule, ConfigService } from '@nestjs/config';
@Module({
  imports: [BullModule.forRootAsync({
    imports: [ConfigModule], inject: [ConfigService],
    useFactory: (config: ConfigService) => ({
      connection: {
        host: config.get<string>('REDIS_HOST', '127.0.0.1'),
        port: config.get<number>('REDIS_PORT', 6379),
        password: config.get<string>('REDIS_PASSWORD') || undefined,
        maxRetriesPerRequest: null,
      },
    }),
  })],
  exports: [BullModule],
})
export class QueueInfrastructureModule {}
