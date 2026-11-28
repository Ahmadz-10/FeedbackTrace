import { ASPECT_TAXONOMY, REASON_CATEGORIES } from '@feedbacktrace/contracts';
export { ASPECT_TAXONOMY, REASON_CATEGORIES };
export const ASPECT_BY_CODE: Map<string, (typeof ASPECT_TAXONOMY)[number]> = new Map(ASPECT_TAXONOMY.map((aspect) => [aspect.code, aspect]));
export const TASK_A_PROMPT_VERSION = 'extraction_v1';
export const TASK_A_PIPELINE_VERSION = 'task-a-node-1.0.0';
