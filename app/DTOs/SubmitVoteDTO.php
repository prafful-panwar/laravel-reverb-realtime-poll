<?php

namespace App\DTOs;

use App\Models\Poll;
use App\Models\PollOption;

class SubmitVoteDTO
{
    public function __construct(
        public readonly Poll $poll,
        public readonly PollOption $option,
        public readonly ?int $userId,
        public readonly string $ipAddress,
        public readonly ?string $userAgent
    ) {}
}
