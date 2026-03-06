<?php

namespace App\Repositories\Contracts;

use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Pagination\CursorPaginator;

interface PollRepositoryInterface
{
    /**
     * Get paginated polls for a specific user.
     */
    public function getPaginatedPollsForUser(int $userId, int $perPage = 10): CursorPaginator;

    /**
     * Find a poll by its slug, including its options.
     */
    public function findBySlug(string $slug): Poll;

    /**
     * Find a specific option belonging to a poll.
     */
    public function findOption(Poll $poll, int $optionId): PollOption;

    /**
     * Load a poll with its options and vote counts, served from cache.
     */
    public function findWithResults(Poll $poll): Poll;

    /**
     * Increment the cached vote count for an option, if the poll is already cached.
     */
    public function incrementCachedOptionVoteCount(Poll $poll, PollOption $option, int $delta = 1): void;

    /**
     * Create a new poll.
     */
    public function create(array $data): Poll;
}
