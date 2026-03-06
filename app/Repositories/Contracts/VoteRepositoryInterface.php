<?php

namespace App\Repositories\Contracts;

use App\Models\Vote;

interface VoteRepositoryInterface
{
    /**
     * Check if an authenticated user has already voted on a specific poll.
     */
    public function hasUserVoted(int $pollId, int $userId): bool;

    /**
     * Check if an unauthenticated user (guest) has already voted on a specific poll using their IP address.
     */
    public function hasGuestVotedByIp(int $pollId, string $ipAddress): bool;

    /**
     * Create a new vote record.
     */
    public function create(array $data): Vote;
}
