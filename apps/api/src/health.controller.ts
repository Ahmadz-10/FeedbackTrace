import { Controller, Get } from '@nestjs/common';
@Controller()
export class HealthController {
  @Get('health')
  health() { return { status: 'ok', service: 'feedbacktrace-node-api', version: '2.0.0' }; }
}
