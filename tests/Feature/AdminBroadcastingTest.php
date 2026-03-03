<?php

namespace Tests\Feature;

use App\Events\VoteSubmitted;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_vote_submitted_event_is_dispatched(): void
    {
        Event::fake();

        $poll = Poll::factory()->create();
        $option = PollOption::factory()->create(['poll_id' => $poll->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('polls.vote', [$poll, $option]), [
                'option_id' => $option->id,
            ]);

        // Assert
        Event::assertDispatched(function (VoteSubmitted $event) use ($poll): bool {
            return $event->pollId === $poll->id;
        });
    }

    public function test_vote_submitted_broadcasts_on_admin_private_channel(): void
    {
        $poll = Poll::factory()->create();

        $event = new VoteSubmitted(
            pollId: $poll->id,
            pollOwnerId: $poll->user_id,
            optionId: 123,
            votesCount: 456,
        );
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-admin.polls.'.$poll->user_id, $channels[0]->name);
    }
}
