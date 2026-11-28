import { Injectable } from '@nestjs/common';
import { approvedFindingSchema, ipaRequestSchema, type IpaAspectResult, type IpaQuadrant } from '@feedbacktrace/contracts';
@Injectable()
export class TaskBService {
  calculateIpa(input: unknown) {
    const parsed = ipaRequestSchema.parse(input);
    const grouped = new Map<string, { importance: number[]; performance: number[] }>();
    for (const row of parsed.responses) {
      const values = grouped.get(row.aspectCode) ?? { importance: [], performance: [] };
      values.importance.push(row.importance); values.performance.push(row.performance); grouped.set(row.aspectCode, values);
    }
    const average = [...grouped.entries()].map(([aspectCode, values]) => ({
      aspectCode,
      averageImportance: values.importance.reduce((a,b)=>a+b,0)/values.importance.length,
      averagePerformance: values.performance.reduce((a,b)=>a+b,0)/values.performance.length,
      responseCount: values.importance.length,
    }));
    const importanceCutoff = average.reduce((sum,x)=>sum+x.averageImportance,0)/average.length;
    const performanceCutoff = average.reduce((sum,x)=>sum+x.averagePerformance,0)/average.length;
    const aspects: IpaAspectResult[] = average.map((item) => {
      const highI=item.averageImportance>=importanceCutoff, highP=item.averagePerformance>=performanceCutoff;
      let quadrant:IpaQuadrant;
      if(highI&&!highP)quadrant='concentrate_here';
      else if(highI&&highP)quadrant='keep_up_good_work';
      else if(!highI&&!highP)quadrant='low_priority';
      else quadrant='possible_overkill';
      return {...item,quadrant};
    });
    return { surveyId:parsed.surveyId??null,method:'Importance-Performance Analysis',cutoffs:{importance:importanceCutoff,performance:performanceCutoff},aspects };
  }
  validateApprovedFindings(input: unknown) {
    if(!Array.isArray(input))throw new Error('Expected an array of findings.');
    const findings=input.map((item)=>approvedFindingSchema.parse(item));
    return {accepted:true,count:findings.length,findings};
  }
}
