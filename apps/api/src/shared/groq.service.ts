import { Injectable, ServiceUnavailableException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import Groq from 'groq-sdk';

@Injectable()
export class GroqService {
  private readonly client: Groq;
  readonly model: string;
  constructor(config: ConfigService) {
    this.model = config.get<string>('GROQ_MODEL', 'llama-3.3-70b-versatile');
    this.client = new Groq({ apiKey: config.get<string>('GROQ_API_KEY') || 'missing-groq-api-key' });
  }

  async completeJson(system: string, user: string): Promise<{ content: string; modelId: string }> {
    if (!process.env.GROQ_API_KEY) throw new ServiceUnavailableException('GROQ_API_KEY is not configured.');
    try {
      const response = await this.client.chat.completions.create({
        model: this.model,
        temperature: Number(process.env.GROQ_TEMPERATURE ?? 0),
        max_completion_tokens: Number(process.env.GROQ_MAX_TOKENS ?? 4096),
        response_format: { type: 'json_object' },
        messages: [{ role: 'system', content: system }, { role: 'user', content: user }],
      });
      const content = response.choices[0]?.message?.content;
      if (!content) throw new Error('Groq returned an empty completion.');
      return { content, modelId: response.model || this.model };
    } catch (error) {
      const name = error instanceof Error ? error.name : 'UnknownError';
      throw new ServiceUnavailableException('Groq request failed (' + name + ').');
    }
  }
}
