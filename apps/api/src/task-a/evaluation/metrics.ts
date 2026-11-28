export interface ClassificationMetrics {
  accuracy: number; macroPrecision: number; macroRecall: number; macroF1: number;
  labels: string[]; confusionMatrix: number[][];
}
export function classificationMetrics(expected: string[], predicted: string[]): ClassificationMetrics {
  if (!expected.length || expected.length !== predicted.length) throw new Error('Expected and predicted must have equal non-zero lengths.');
  const labels = [...new Set([...expected, ...predicted])].sort();
  const matrix = labels.map(() => labels.map(() => 0));
  expected.forEach((label, i) => matrix[labels.indexOf(label)][labels.indexOf(predicted[i])]++);
  const measures = labels.map((_, i) => {
    const tp = matrix[i][i];
    const fp = matrix.reduce((n, row, rowIndex) => n + (rowIndex === i ? 0 : row[i]), 0);
    const fn = matrix[i].reduce((n, count, col) => n + (col === i ? 0 : count), 0);
    const precision = tp + fp ? tp / (tp + fp) : 0;
    const recall = tp + fn ? tp / (tp + fn) : 0;
    return { precision, recall, f1: precision + recall ? 2 * precision * recall / (precision + recall) : 0 };
  });
  const correct = matrix.reduce((sum, row, i) => sum + row[i], 0);
  return {
    accuracy: correct / expected.length,
    macroPrecision: measures.reduce((sum, item) => sum + item.precision, 0) / measures.length,
    macroRecall: measures.reduce((sum, item) => sum + item.recall, 0) / measures.length,
    macroF1: measures.reduce((sum, item) => sum + item.f1, 0) / measures.length,
    labels, confusionMatrix: matrix,
  };
}
export function evidenceMetrics(items: Array<{ quote: string; source: string }>) {
  const valid = items.filter((item) => item.quote.length > 0 && item.source.includes(item.quote)).length;
  return { total: items.length, valid, invalid: items.length - valid, validRate: items.length ? valid / items.length : 0 };
}
