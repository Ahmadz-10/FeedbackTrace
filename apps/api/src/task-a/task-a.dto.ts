import { Transform, Type } from 'class-transformer';
import { IsBoolean, IsIn, IsInt, IsOptional, IsString, Max, MaxLength, Min, MinLength } from 'class-validator';

export class ReviewFindingDto {
  @IsIn(['approve', 'edit', 'reject'])
  action!: 'approve' | 'edit' | 'reject';

  @IsOptional()
  @IsString()
  @MaxLength(2000)
  note?: string;

  @IsOptional()
  @IsString()
  @MinLength(1)
  @MaxLength(255)
  issueTitle?: string;

  @IsOptional()
  @IsString()
  @MinLength(1)
  @MaxLength(2000)
  issueSummary?: string;
}

export class ImportBatchDto {
  @IsOptional()
  @IsString()
  @MaxLength(255)
  title?: string;

  @IsOptional()
  @IsString()
  @MaxLength(100)
  source?: string;

  @IsOptional()
  @Transform(({ value }) => value === true || value === 'true')
  @IsBoolean()
  allowProposedAspects?: boolean;

  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(10)
  maxAspectsPerFeedback?: number;
}
