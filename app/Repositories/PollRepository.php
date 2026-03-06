<?php

namespace App\Repositories;

use App\Models\Poll;
use App\Models\PollOption;
use App\Repositories\Contracts\PollRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;

class PollRepository implements PollRepositoryInterface
{
    public function getPaginatedPollsForUser(int $userId, int $perPage = 10): CursorPaginator
    {
        return Poll::query()
            ->where('user_id', $userId)
            ->withCount('options')
            ->withSum('options', 'votes_count')
            ->latest()
            ->cursorPaginate($perPage);
    }

    public function findBySlug(string $slug): Poll
    {
        return Cache::remember(
            "poll_{$slug}",
            now()->addSeconds(30),
            function () use ($slug) {
                return Poll::query()
                    ->with([
                        'options' => function (Relation $query): void {
                            $query->withCount('votes')->orderBy('id');
                        },
                    ])
                    ->where('slug', $slug)
                    ->firstOrFail();
            }
        );
    }

    public function findWithResults(Poll $poll): Poll
    {
        return Cache::remember(
            "poll_{$poll->slug}",
            now()->addSeconds(30),
            fn (): Poll => $poll->load([
                'options' => function (Relation $query): void {
                    $query->withCount('votes');
                },
            ])
        );
    }

    public function incrementCachedOptionVoteCount(Poll $poll, PollOption $option, int $delta = 1): void
    {
        $cacheKey = "poll_{$poll->slug}";

        if (! Cache::has($cacheKey)) {
            return;
        }

        /** @var Poll|null $cachedPoll */
        $cachedPoll = Cache::get($cacheKey);

        if (! $cachedPoll instanceof Poll) {
            return;
        }

        $cachedOption = $cachedPoll->options->firstWhere('id', $option->id);

        if ($cachedOption) {
            $cachedOption->votes_count = ($cachedOption->votes_count ?? 0) + $delta;
        }

        Cache::put($cacheKey, $cachedPoll, now()->addSeconds(30));
    }

    public function findOption(Poll $poll, int $optionId): PollOption
    {
        /** @var PollOption $option */
        $option = $poll->options()->where('id', $optionId)->firstOrFail();

        return $option;
    }

    public function create(array $data): Poll
    {
        /** @var Poll $poll */
        $poll = Poll::query()->create($data);

        return $poll;
    }
}
