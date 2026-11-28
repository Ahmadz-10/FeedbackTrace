import { CanActivate, ExecutionContext, ForbiddenException, Injectable } from '@nestjs/common';
@Injectable()
export class StaffGuard implements CanActivate {
  canActivate(context: ExecutionContext): boolean {
    const request = context.switchToHttp().getRequest<{ user?: { role?: string } }>();
    if (!['STAFF', 'ADMIN'].includes(request.user?.role ?? '')) throw new ForbiddenException('Staff or admin access is required.');
    return true;
  }
}
