export function compareEvidenceValidation(items: Array<{ quote: string; source: string }>) {
  const valid = items.filter((item) => item.quote.length > 0 && item.source.includes(item.quote));
  return {
    withoutValidation: { accepted: items.length, rejected: 0 },
    withValidation: { accepted: valid.length, rejected: items.length - valid.length },
    invalidReduction: items.length ? (items.length - valid.length) / items.length : 0,
  };
}
