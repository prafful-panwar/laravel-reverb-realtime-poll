<?php

namespace App\Services;

use App\DTOs\SubmitVoteDTO;
use App\Events\VoteSubmitted;
use App\Models\Vote;
use App\Repositories\Contracts\PollRepositoryInterface;
use App\Repositories\Contracts\VoteRepositoryInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoteService
{
    public function __construct(
        protected VoteRepositoryInterface $voteRepository,
        protected PollRepositoryInterface $pollRepository
    ) {}

    /**
     * Submit a vote for a poll option.
     */
    public function submitVote(SubmitVoteDTO $dto): Vote
    {
        $lockKey = $this->getVoteLockKey($dto);

        try {
            return Cache::lock($lockKey, 5)->block(3, function () use ($dto): Vote {
                // Fail fast if the user/guest already voted.
                $hasVoted = $dto->userId
                    ? $this->voteRepository->hasUserVoted($dto->poll->id, $dto->userId)
                    : $this->voteRepository->hasGuestVotedByIp($dto->poll->id, $dto->ipAddress);

                if ($hasVoted) {
                    throw ValidationException::withMessages([
                        'vote' => 'You have already voted on this poll.',
                    ]);
                }

                return DB::transaction(function () use ($dto): Vote {
                    $vote = $this->voteRepository->create([
                        'poll_id' => $dto->poll->id,
                        'poll_option_id' => $dto->option->id,
                        'user_id' => $dto->userId,
                        'ip_address' => $dto->ipAddress,
                        'user_agent' => $dto->userAgent,
                    ]);

                    $dto->option->increment('votes_count');
                    $dto->option->refresh();

                    $this->pollRepository->incrementCachedOptionVoteCount($dto->poll, $dto->option, 1);

                    // Fire event with the accurate post-increment votes_count
                    event(new VoteSubmitted(
                        pollId: $dto->poll->id,
                        pollOwnerId: $dto->poll->user_id,
                        optionId: $dto->option->id,
                        votesCount: $dto->option->votes_count,
                    ));

                    return $vote;
                });
            });
        } catch (LockTimeoutException $e) {
            // Lock could not be acquired, likely due to high contention. Tell the user to retry.
            throw ValidationException::withMessages([
                'vote' => 'Your vote is being processed. Please try again in a moment.',
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Second check: Database-level concurrency guard (race condition caught)
            throw ValidationException::withMessages([
                'vote' => 'You have already voted on this poll.',
            ]);
        }
    }

    private function getVoteLockKey(SubmitVoteDTO $dto): string
    {
        $voterId = $dto->userId ?? $dto->ipAddress;

        return sprintf('vote_submission:%d:%s', $dto->poll->id, $voterId);
    }
}
