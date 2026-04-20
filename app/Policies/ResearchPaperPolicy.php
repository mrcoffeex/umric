<?php

namespace App\Policies;

use App\Models\ResearchPaper;
use App\Models\User;

class ResearchPaperPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ResearchPaper $researchPaper): bool
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->id === $researchPaper->user_id) {
            return true;
        }

        $proponentIds = collect($researchPaper->proponents)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array($user->id, $proponentIds, true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ResearchPaper $researchPaper): bool
    {
        if ($user->id === $researchPaper->user_id) {
            return true;
        }

        $proponentIds = collect($researchPaper->proponents)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return in_array($user->id, $proponentIds, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ResearchPaper $researchPaper): bool
    {
        return $user->id === $researchPaper->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ResearchPaper $researchPaper): bool
    {
        return $user->id === $researchPaper->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ResearchPaper $researchPaper): bool
    {
        return $user->id === $researchPaper->user_id;
    }
}
