<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Poll $poll): bool
    {
        return (int) $user->id === (int) $poll->user_id;
    }
}
