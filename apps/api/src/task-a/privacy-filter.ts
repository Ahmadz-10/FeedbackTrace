/**
 * Conservative redaction before third-party inference.
 * Email and labelled student identifiers are replaced without changing string length,
 * so quotes outside the redacted span stay index-aligned with the original comment.
 */
export function redactSensitiveText(source: string): string {
  let text = source;
  text = text.replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, (match) => match.replace(/[^\s]/g, '█'));
  text = text.replace(/\b(?:nim|student\s*id|nomor\s*induk\s*mahasiswa)\s*[:#-]?\s*[A-Z0-9-]{5,}\b/gi, (match) => match.replace(/[A-Z0-9]/gi, '█'));
  text = text.replace(/(?:\+?62|0)\d[\d\s().-]{7,}\d/g, (match) => match.replace(/\d/g, '0'));
  return text;
}
