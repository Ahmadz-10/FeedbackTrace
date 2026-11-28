import { IsBoolean, IsIn, IsOptional, IsString, MaxLength, MinLength } from 'class-validator';
export class ReviewFindingDto {
  @IsIn(['approve', 'edit', 'reject']) action!: 'approve' | 'edit' | 'reject';
  @IsOptional() @IsString() @MaxLength(2000) note?: string;
  @IsOptional() @IsString() @MinLength(1) @MaxLength(255) issueTitle?: string;
  @IsOptional() @IsString() @MinLength(1) @MaxLength(2000) issueSummary?: string;
}
export class ImportBatchDto {
  @IsOptional() @IsString() @MaxLength(255) title?: string;
  @IsOptional() @IsString() @MaxLength(100) source?: string;
  @IsOptional() @IsBoolean() allowProposedAspects?: boolean;
  @IsOptional() maxAspectsPerFeedback?: string;
}
