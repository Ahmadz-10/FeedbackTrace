export function pairwiseConsolidationMetrics(expectedGroups: string[][], predictedGroups: string[][]) {
  const pairs = (groups: string[][]) => {
    const set = new Set<string>();
    for (const group of groups) for (let i = 0; i < group.length; i++) for (let j = i + 1; j < group.length; j++) set.add([group[i], group[j]].sort().join('::'));
    return set;
  };
  const expected = pairs(expectedGroups), predicted = pairs(predictedGroups);
  const tp = [...predicted].filter((pair) => expected.has(pair)).length;
  const fp = predicted.size - tp, fn = expected.size - tp;
  const precision = tp + fp ? tp / (tp + fp) : 0, recall = tp + fn ? tp / (tp + fn) : 0;
  return { precision, recall, f1: precision + recall ? 2 * precision * recall / (precision + recall) : 0, tp, fp, fn };
}
