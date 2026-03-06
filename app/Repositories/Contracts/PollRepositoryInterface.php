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
     * Create a new poll.
     */
    public function create(array $data): Poll;
}
