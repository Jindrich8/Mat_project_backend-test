<?php

namespace App\Policies;

use App\Models\TaskReviewTemplate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskReviewTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TaskReviewTemplate $taskReviewTemplate): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TaskReviewTemplate $taskReviewTemplate): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TaskReviewTemplate $taskReviewTemplate): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TaskReviewTemplate $taskReviewTemplate): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TaskReviewTemplate $taskReviewTemplate): bool
    {
        //
    }
}
