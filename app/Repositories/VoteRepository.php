<?php

namespace App\Repositories;

use App\Models\Vote;
use App\Repositories\Contracts\VoteRepositoryInterface;

class VoteRepository implements VoteRepositoryInterface
{
    public function hasUserVoted(int $pollId, int $userId): bool
    {
        return Vote::query()
            ->where('poll_id', $pollId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function hasGuestVotedByIp(int $pollId, string $ipAddress): bool
    {
        return Vote::query()
            ->where('poll_id', $pollId)
            ->whereNull('user_id')
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    public function create(array $data): Vote
    {
        /** @var Vote $vote */
        $vote = Vote::query()->create($data);

        return $vote;
    }
}
