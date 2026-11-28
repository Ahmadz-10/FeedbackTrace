import { describe, expect, it } from 'vitest';
import { TaskBService } from './task-b.service.js';
describe('Task B IPA', () => {
  const service = new TaskBService();
  it('calculates four quadrants', () => {
    const result = service.calculateIpa({responses:[
      {aspectCode:'speed',importance:5,performance:2},
      {aspectCode:'staff',importance:5,performance:5},
      {aspectCode:'parking',importance:1,performance:1},
      {aspectCode:'wifi',importance:1,performance:5},
    ]});
    expect(result.aspects.map((row)=>row.quadrant)).toEqual(['concentrate_here','keep_up_good_work','low_priority','possible_overkill']);
  });
  it('rejects scores outside 1–5', () => {
    expect(()=>service.calculateIpa({responses:[{aspectCode:'speed',importance:6,performance:3}]})).toThrow();
  });
  it('does not accept pending Task A findings', () => {
    expect(()=>service.validateApprovedFindings([{findingId:'F1',reviewStatus:'pending_review'}])).toThrow();
  });
});
