<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VoteSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Ensure the broadcast job is only queued after commit.
     */
    public bool $afterCommit = true;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $pollId,
        public readonly int $pollOwnerId,
        public readonly int $optionId,
        public readonly int $votesCount,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.polls.'.$this->pollOwnerId),
        ];
    }

    /**
     * Minimal payload optimized for high-throughput broadcasting.
     *
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return [
            'poll_id' => $this->pollId,
            'option_id' => $this->optionId,
            'votes_count' => $this->votesCount,
        ];
    }

    /**
     * Route broadcast jobs to a dedicated queue.
     */
    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }
}
