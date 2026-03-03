<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_vote_and_it_increases_the_vote_count(): void
    {
        $poll = Poll::factory()->create();
        $option = PollOption::factory()->create(['poll_id' => $poll->id]);

        $response = $this->post(route('polls.vote', [$poll, $option]), [
            'option_id' => $option->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(1, $option->fresh()->votes_count);
        $this->assertDatabaseHas('votes', [
            'poll_id' => $poll->id,
            'poll_option_id' => $option->id,
        ]);
    }

    public function test_a_guest_cannot_vote_twice_on_the_same_browser(): void
    {
        $poll = Poll::factory()->create();
        $option = PollOption::factory()->create(['poll_id' => $poll->id]);

        // First Vote from a pristine browser
        $response1 = $this->post(route('polls.vote', [$poll, $option]), [
            'option_id' => $option->id,
        ]);

        $response1->assertSessionHas('success');
        $this->assertEquals(1, $option->fresh()->votes_count);
        $response1->assertCookie("has_voted_{$poll->id}", true);

        // Second Vote Attempt from the same browser (presenting the cookie)
        $response2 = $this->withCookie("has_voted_{$poll->id}", true)
            ->post(route('polls.vote', [$poll, $option]), [
                'option_id' => $option->id,
            ]);

        $response2->assertSessionHasErrors('vote');
        $this->assertEquals(1, $option->fresh()->votes_count);
    }

    public function test_an_authenticated_user_cannot_vote_twice(): void
    {
        $user = User::factory()->create();
        $poll = Poll::factory()->create();
        $option = PollOption::factory()->create(['poll_id' => $poll->id]);

        // First Vote
        $this->actingAs($user)
            ->post(route('polls.vote', [$poll, $option]), [
                'option_id' => $option->id,
            ]);

        // Second Vote Attempt
        $response = $this->actingAs($user)
            ->post(route('polls.vote', [$poll, $option]), [
                'option_id' => $option->id,
            ]);

        $response->assertSessionHasErrors('vote');
        $this->assertEquals(1, $option->fresh()->votes_count);
    }
}
