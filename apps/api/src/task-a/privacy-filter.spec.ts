import { describe, expect, it } from 'vitest';
import { redactSensitiveText } from './privacy-filter.js';

describe('Task A privacy filtering', () => {
  it('redacts email addresses while preserving string length', () => {
    const source = 'Email saya aliif@example.edu untuk bantuan';
    const safe = redactSensitiveText(source);
    expect(safe).not.toContain('aliif@example.edu');
    expect(safe.length).toBe(source.length);
    expect(safe).toContain('untuk bantuan');
  });
  it('redacts labelled student identifiers', () => {
    const source = 'NIM: 12345678 antrean lama';
    const safe = redactSensitiveText(source);
    expect(safe).not.toContain('12345678');
    expect(safe).toContain('antrean lama');
    expect(safe.length).toBe(source.length);
  });
});
