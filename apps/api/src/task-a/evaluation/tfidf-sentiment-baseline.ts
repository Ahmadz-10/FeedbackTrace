/** TF-IDF + multinomial logistic regression baseline for labelled training data. */
export class TfidfLogisticRegression {
  private vocabulary = new Map<string, number>();
  private idf: number[] = [];
  private weights: number[][] = [];
  private classes: string[] = [];
  private tokens(text: string): string[] { return text.toLocaleLowerCase('id').match(/[\p{L}\p{N}]+/gu) ?? []; }
  private vectorize(text: string): number[] {
    const tokens = this.tokens(text), counts = new Map<number, number>();
    for (const token of tokens) { const id = this.vocabulary.get(token); if (id !== undefined) counts.set(id, (counts.get(id) ?? 0) + 1); }
    const vector = this.idf.map((weight, i) => ((counts.get(i) ?? 0) / (tokens.length || 1)) * weight);
    const norm = Math.sqrt(vector.reduce((sum, value) => sum + value * value, 0)) || 1;
    return vector.map((value) => value / norm);
  }
  fit(texts: string[], labels: string[], epochs = 250, learningRate = 0.08): this {
    if (!texts.length || texts.length !== labels.length) throw new Error('Texts and labels must have equal non-zero lengths.');
    this.classes = [...new Set(labels)].sort();
    const df = new Map<string, number>();
    texts.forEach((text) => new Set(this.tokens(text)).forEach((token) => df.set(token, (df.get(token) ?? 0) + 1)));
    this.vocabulary = new Map([...df.keys()].sort().map((token, i) => [token, i]));
    this.idf = [...this.vocabulary.keys()].map((token) => Math.log((1 + texts.length) / (1 + (df.get(token) ?? 0))) + 1);
    const vectors = texts.map((text) => this.vectorize(text));
    this.weights = this.classes.map(() => Array(this.vocabulary.size).fill(0));
    for (let epoch = 0; epoch < epochs; epoch++) for (let row = 0; row < vectors.length; row++) {
      const logits = this.weights.map((weights) => weights.reduce((sum, value, i) => sum + value * vectors[row][i], 0));
      const max = Math.max(...logits), exp = logits.map((value) => Math.exp(value - max));
      const total = exp.reduce((sum, value) => sum + value, 0), probabilities = exp.map((value) => value / total);
      const expected = this.classes.indexOf(labels[row]);
      this.weights.forEach((weights, classIndex) => weights.forEach((value, featureIndex) => {
        weights[featureIndex] = value - learningRate * (probabilities[classIndex] - (classIndex === expected ? 1 : 0)) * vectors[row][featureIndex];
      }));
    }
    return this;
  }
  predict(text: string): string {
    if (!this.classes.length) throw new Error('Classifier has not been trained.');
    const vector = this.vectorize(text);
    const scores = this.weights.map((weights) => weights.reduce((sum, value, i) => sum + value * vector[i], 0));
    return this.classes[scores.indexOf(Math.max(...scores))];
  }
  predictMany(texts: string[]): string[] { return texts.map((text) => this.predict(text)); }
}
