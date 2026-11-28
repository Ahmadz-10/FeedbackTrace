import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
type FeaturePipeline = (inputs: string[], options: { pooling: 'mean'; normalize: boolean }) => Promise<{ tolist(): number[][] }>;

@Injectable()
export class EmbeddingService {
  private readonly logger = new Logger(EmbeddingService.name);
  private pipelinePromise?: Promise<FeaturePipeline>;
  constructor(private readonly config: ConfigService) {}

  private async getPipeline(): Promise<FeaturePipeline> {
    this.pipelinePromise ??= (async () => {
      const { pipeline } = await import('@huggingface/transformers');
      const model = this.config.get<string>('EMBEDDING_MODEL', 'Xenova/multilingual-e5-base');
      const extractor = await pipeline('feature-extraction', model, {
        device: this.config.get<string>('EMBEDDING_DEVICE', 'cpu') as 'cpu',
      });
      return extractor as unknown as FeaturePipeline;
    })();
    return this.pipelinePromise;
  }

  async embed(texts: string[], inputType: 'query' | 'passage' = 'passage'): Promise<number[][]> {
    if (!texts.length) return [];
    const inputs = texts.map((text) => inputType + ': ' + text.trim());
    const output = await (await this.getPipeline())(inputs, { pooling: 'mean', normalize: true });
    this.logger.debug('Generated ' + texts.length + ' E5 embeddings.');
    return output.tolist();
  }
}
