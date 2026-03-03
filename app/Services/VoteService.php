<?php

namespace App\Services;

use App\DTOs\SubmitVoteDTO;
use App\Models\Poll;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoteService
{
    /**
     * Submit a vote for a poll option.
     */
    public function submitVote(SubmitVoteDTO $dto): Vote
    {
        return DB::transaction(function () use ($dto): Vote {
            $hasVoted = false;

            if ($dto->userId) {
                // Authenticated: check by user_id
                $hasVoted = $this->hasAuthenticatedUserVoted($dto);
            } else {
                // Guest: check by IP address (cookie is checked in the controller)
                $hasVoted = $this->hasGuestVotedByIp($dto);
            }

            if ($hasVoted) {
                throw ValidationException::withMessages([
                    'vote' => 'You have already voted on this poll.',
                ]);
            }

            $vote = $this->createVote($dto);

            // Increment vote count atomically
            $dto->option->increment('votes_count');

            return $vote;
        });
    }

    /**
     * Check if an authenticated user has already voted on this poll.
     */
    private function hasAuthenticatedUserVoted(SubmitVoteDTO $dto): bool
    {
        return Vote::query()
            ->where('poll_id', $dto->poll->id)
            ->where('user_id', $dto->userId)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Check if a guest has already voted on this poll from the same IP address.
     */
    private function hasGuestVotedByIp(SubmitVoteDTO $dto): bool
    {
        return Vote::query()
            ->where('poll_id', $dto->poll->id)
            ->whereNull('user_id')
            ->where('ip_address', $dto->ipAddress)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Create the vote record in the database.
     */
    private function createVote(SubmitVoteDTO $dto): Vote
    {
        /** @var Vote $vote */
        $vote = Vote::query()->create([
            'poll_id' => $dto->poll->id,
            'poll_option_id' => $dto->option->id,
            'user_id' => $dto->userId,
            'ip_address' => $dto->ipAddress,
            'user_agent' => $dto->userAgent,
        ]);

        return $vote;
    }
}
