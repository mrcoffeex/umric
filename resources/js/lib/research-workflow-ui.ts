/**
 * Workflow display helpers: position and progress follow the *first* step that is
 * still waiting on an outcome (pending), not only the raw `current_step` index.
 */

import type { WorkflowStepSetup } from '@/lib/workflow-step-config';

export type WorkflowPaper = {
    current_step: string;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    step_data_gathering?: string | null;
    step_rating?: string | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    step_hard_bound?: string | null;
    custom_step_statuses?: Record<string, string | null> | null;
};

export const WORKFLOW_STEP_KEYS = [
    'title_proposal',
    'ric_review',
    'outline_defense',
    'data_gathering',
    'rating',
    'final_manuscript',
    'final_defense',
    'hard_bound',
    'completed',
] as const;

export type WorkflowStepKey = (typeof WORKFLOW_STEP_KEYS)[number] | string;

export function workflowStepKeys(steps?: string[]): string[] {
    return steps && steps.length > 0 ? steps : [...WORKFLOW_STEP_KEYS];
}

function statusForPaperStep(
    paper: WorkflowPaper,
    stepKey: string,
): string | null {
    switch (stepKey) {
        case 'ric_review':
            return paper.step_ric_review ?? null;
        case 'outline_defense':
            return paper.step_outline_defense ?? null;
        case 'data_gathering':
            return paper.step_data_gathering ?? null;
        case 'rating':
            return paper.step_rating ?? null;
        case 'final_manuscript':
            return paper.step_final_manuscript ?? null;
        case 'final_defense':
            return paper.step_final_defense ?? null;
        case 'hard_bound':
            return paper.step_hard_bound ?? null;
        default:
            return paper.custom_step_statuses?.[stepKey] ?? null;
    }
}

export function isWorkflowStepSatisfied(
    paper: WorkflowPaper,
    stepKey: string,
    configs?: Record<string, WorkflowStepSetup> | null,
): boolean {
    const config = configs?.[stepKey];

    if (config) {
        if (stepKey === 'title_proposal') {
            return paper.current_step !== 'title_proposal';
        }

        if (stepKey === 'completed') {
            return paper.current_step === 'completed';
        }

        const status = statusForPaperStep(paper, stepKey);

        return config.statuses.some(
            (option) => option.completes && option.value === status,
        );
    }

    switch (stepKey) {
        case 'title_proposal':
            return paper.current_step !== 'title_proposal';
        case 'ric_review':
            return paper.step_ric_review === 'approved';
        case 'outline_defense':
            return paper.step_outline_defense === 'passed';
        case 'data_gathering':
            return paper.step_data_gathering === 'completed';
        case 'rating':
            return paper.step_rating === 'rated';
        case 'final_manuscript':
            return paper.step_final_manuscript === 'submitted';
        case 'final_defense':
            return paper.step_final_defense === 'passed';
        case 'hard_bound':
            return paper.step_hard_bound === 'submitted';
        case 'completed':
            return paper.current_step === 'completed';
        default:
            return paper.custom_step_statuses?.[stepKey] === 'completed';
    }
}

/** Index of the first step that is not yet satisfied (where work is still pending). */
export function firstPendingWorkflowStepIndex(
    paper: WorkflowPaper,
    steps?: string[],
    configs?: Record<string, WorkflowStepSetup> | null,
): number {
    const keys = workflowStepKeys(steps);

    for (let i = 0; i < keys.length; i++) {
        const key = keys[i];

        if (key && !isWorkflowStepSatisfied(paper, key, configs)) {
            return i;
        }
    }

    return Math.max(keys.length - 1, 0);
}

export function workflowFocusStepKey(
    paper: WorkflowPaper,
    steps?: string[],
    configs?: Record<string, WorkflowStepSetup> | null,
): string {
    const keys = workflowStepKeys(steps);

    return (
        keys[firstPendingWorkflowStepIndex(paper, keys, configs)] ??
        keys[0] ??
        paper.current_step
    );
}

export function workflowProgressPercent(
    paper: WorkflowPaper,
    steps?: string[],
    configs?: Record<string, WorkflowStepSetup> | null,
): number {
    const keys = workflowStepKeys(steps);
    const n = keys.length;

    if (n <= 1) {
        return 0;
    }

    if (
        keys.includes('completed') &&
        isWorkflowStepSatisfied(paper, 'completed', configs)
    ) {
        return 100;
    }

    return Math.round(
        (firstPendingWorkflowStepIndex(paper, keys, configs) / (n - 1)) * 100,
    );
}
