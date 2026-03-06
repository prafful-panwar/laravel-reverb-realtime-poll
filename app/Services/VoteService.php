<?php

namespace App\Services;

use App\DTOs\SubmitVoteDTO;
use App\Events\VoteSubmitted;
use App\Models\Poll;
use App\Models\Vote;
use App\Repositories\Contracts\VoteRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoteService
{
    public function __construct(
        protected VoteRepositoryInterface $voteRepository
    ) {}

    /**
     * Submit a vote for a poll option.
     */
    public function submitVote(SubmitVoteDTO $dto): Vote
    {
        // First check: Application level check (fail fast)
        $hasVoted = $dto->userId
            ? $this->voteRepository->hasUserVoted($dto->poll->id, $dto->userId)
            : $this->voteRepository->hasGuestVotedByIp($dto->poll->id, $dto->ipAddress);

        if ($hasVoted) {
            throw ValidationException::withMessages([
                'vote' => 'You have already voted on this poll.',
            ]);
        }

        try {
            return DB::transaction(function () use ($dto): Vote {
                $vote = $this->voteRepository->create([
                    'poll_id' => $dto->poll->id,
                    'poll_option_id' => $dto->option->id,
                    'user_id' => $dto->userId,
                    'ip_address' => $dto->ipAddress,
                    'user_agent' => $dto->userAgent,
                ]);

                // Increment vote count atomically
                $dto->option->increment('votes_count');

                // Fire event
                event(new VoteSubmitted(
                    pollId: $dto->poll->id,
                    pollOwnerId: $dto->poll->user_id,
                    optionId: $dto->option->id,
                    votesCount: $dto->option->votes_count,
                ));

                return $vote;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Second check: Database level concurrency check (race condition caught)
            throw ValidationException::withMessages([
                'vote' => 'You have already voted on this poll.',
            ]);
        }
    }
}
