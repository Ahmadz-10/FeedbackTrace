import { ASPECT_TAXONOMY } from '@feedbacktrace/contracts';
export function keywordAspectBaseline(text: string): string[] {
  const normalized = text.toLocaleLowerCase('id');
  const found = ASPECT_TAXONOMY.filter((aspect) => aspect.keywords.some((keyword) => normalized.includes(keyword))).map((aspect) => aspect.code);
  return [...new Set(found.length ? found : ['other'])];
}
