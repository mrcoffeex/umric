/**
 * Workflow display helpers: position and progress follow the *first* step that is
 * still waiting on an outcome (pending), not only the raw `current_step` index.
 */

export type WorkflowPaper = {
    current_step: string;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    step_data_gathering?: string | null;
    step_rating?: string | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    step_hard_bound?: string | null;
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

export type WorkflowStepKey = (typeof WORKFLOW_STEP_KEYS)[number];

export function isWorkflowStepSatisfied(
    paper: WorkflowPaper,
    stepKey: WorkflowStepKey,
): boolean {
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
            return false;
    }
}

/** Index of the first step that is not yet satisfied (where work is still pending). */
export function firstPendingWorkflowStepIndex(paper: WorkflowPaper): number {
    for (let i = 0; i < WORKFLOW_STEP_KEYS.length; i++) {
        const key = WORKFLOW_STEP_KEYS[i];

        if (!isWorkflowStepSatisfied(paper, key)) {
            return i;
        }
    }

    return WORKFLOW_STEP_KEYS.length - 1;
}

export function workflowFocusStepKey(paper: WorkflowPaper): WorkflowStepKey {
    return WORKFLOW_STEP_KEYS[firstPendingWorkflowStepIndex(paper)];
}

export function workflowProgressPercent(paper: WorkflowPaper): number {
    const n = WORKFLOW_STEP_KEYS.length;

    if (n <= 1) {
        return 0;
    }

    if (isWorkflowStepSatisfied(paper, 'completed')) {
        return 100;
    }

    return Math.round((firstPendingWorkflowStepIndex(paper) / (n - 1)) * 100);
}
