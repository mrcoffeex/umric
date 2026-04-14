/**
 * Unified step color system for the research workflow.
 *
 * Each step maps to a consistent badge style (bg + text)
 * and a solid bar color for progress bars / charts.
 */

/** Badge classes: light bg + bold text, with dark mode variants. */
export const stepBadgeColors: Record<string, string> = {
    title_proposal: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-300',
    ric_review: 'bg-teal-50 text-teal-700 dark:bg-teal-950/30 dark:text-teal-300',
    plagiarism_check: 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-300',
    outline_defense: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    rating: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    final_manuscript: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300',
    final_defense: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/30 dark:text-cyan-300',
    hard_bound: 'bg-lime-50 text-lime-700 dark:bg-lime-950/30 dark:text-lime-300',
    completed: 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300',
};

/** Solid bar colors for progress bars / chart fills. */
export const stepBarColors: Record<string, string> = {
    title_proposal: 'bg-orange-500',
    ric_review: 'bg-teal-500',
    plagiarism_check: 'bg-purple-500',
    outline_defense: 'bg-blue-500',
    rating: 'bg-amber-500',
    final_manuscript: 'bg-indigo-500',
    final_defense: 'bg-cyan-500',
    hard_bound: 'bg-lime-500',
    completed: 'bg-green-500',
};

/** Fallback badge class when step key is unknown. */
export const defaultBadgeColor = 'bg-muted text-muted-foreground';

/** Fallback bar class when step key is unknown. */
export const defaultBarColor = 'bg-muted-foreground/40';

export function getStepBadgeClass(step: string): string {
    return stepBadgeColors[step] ?? defaultBadgeColor;
}

export function getStepBarClass(step: string): string {
    return stepBarColors[step] ?? defaultBarColor;
}

/**
 * Workflow step card border/bg for "current vs past vs future" display.
 */
export function getStepStatusClass(step: string, currentStep: string, orderedSteps: string[]): string {
    if (currentStep === step) {
        return 'border-orange-300 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-950/30 dark:text-orange-300';
    }

    const currentIndex = orderedSteps.indexOf(currentStep);
    const thisIndex = orderedSteps.indexOf(step);

    if (thisIndex >= 0 && currentIndex > thisIndex) {
        return 'border-green-300 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300';
    }

    return 'border-border bg-card text-muted-foreground';
}
