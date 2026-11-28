import { describe, expect, it } from 'vitest';
import { classificationMetrics, evidenceMetrics } from './metrics.js';
import { keywordAspectBaseline } from './keyword-aspect-baseline.js';
import { TfidfLogisticRegression } from './tfidf-sentiment-baseline.js';

describe('Task A evaluation', () => {
  it('calculates classification metrics', () => {
    const result = classificationMetrics(['positive','negative','negative'], ['positive','positive','negative']);
    expect(result.accuracy).toBeCloseTo(2/3);
    expect(result.confusionMatrix).toEqual([[1,1],[0,1]]);
  });
  it('validates exact evidence substrings', () => {
    const result = evidenceMetrics([{quote:'Antrean lama',source:'Antrean lama sekali'},{quote:'portal cepat',source:'Portal error'}]);
    expect(result.valid).toBe(1); expect(result.invalid).toBe(1);
  });
  it('runs keyword aspect baseline', () => {
    expect(keywordAspectBaseline('Portal akademik sering error')).toContain('digital_service_usability');
  });
  it('fits and predicts a small sentiment baseline', () => {
    const model = new TfidfLogisticRegression().fit(
      ['layanan cepat dan ramah','antrean terlalu lama','aplikasi sering error','petugas membantu'],
      ['positive','negative','negative','positive'], 80,
    );
    expect(['positive','negative']).toContain(model.predict('antrean lama'));
  });
});
