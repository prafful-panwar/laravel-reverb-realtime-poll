<?php

namespace App\Services;

use App\DTOs\CreatePollDTO;
use App\Models\Poll;
use App\Repositories\Contracts\PollRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PollService
{
    public function __construct(
        protected PollRepositoryInterface $pollRepository
    ) {}

    /**
     * Create a new poll with its options.
     */
    public function createPoll(CreatePollDTO $dto): Poll
    {
        return DB::transaction(function () use ($dto): Poll {
            $poll = $this->pollRepository->create([
                'user_id' => $dto->userId,
                'title' => $dto->title,
                'description' => $dto->description,
                // Slug is auto-generated in the model boot method
            ]);

            foreach ($dto->options as $optionText) {
                // We utilize the native Eloquent relationship here for atomic option attachment
                $poll->options()->create([
                    'text' => $optionText,
                ]);
            }

            return $poll;
        });
    }
}
