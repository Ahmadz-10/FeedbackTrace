import { Body, Controller, Get, Post, Req, UseGuards } from '@nestjs/common';
import type { Request } from 'express';
import { AuthService } from './auth.service.js';
import { LoginDto } from './auth.dto.js';
import { JwtAuthGuard } from './jwt-auth.guard.js';
@Controller('api/v1/auth')
export class AuthController {
  constructor(private readonly auth: AuthService) {}
  @Post('login') login(@Body() body: LoginDto) { return this.auth.login(body.email, body.password); }
  @UseGuards(JwtAuthGuard)
  @Get('me') me(@Req() request: Request & { user: { id: number } }) { return this.auth.profile(request.user.id); }
}
