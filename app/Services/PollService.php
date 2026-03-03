<?php

namespace App\Services;

use App\DTOs\CreatePollDTO;
use App\Models\Poll;
use Illuminate\Support\Facades\DB;

class PollService
{
    /**
     * Create a new poll with its options.
     */
    public function createPoll(CreatePollDTO $dto): Poll
    {
        return DB::transaction(function () use ($dto) {
            $poll = Poll::query()->create([
                'user_id' => auth()->id(),
                'title' => $dto->title,
                'description' => $dto->description,
            ]);

            foreach ($dto->options as $optionText) {
                $poll->options()->create([
                    'text' => $optionText,
                ]);
            }

            return $poll;
        });
    }
}
