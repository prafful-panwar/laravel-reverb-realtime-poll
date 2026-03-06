<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_a_guest_cannot_vote_twice_from_the_same_ip(): void
    {
        $poll = Poll::factory()->create();
        $option = PollOption::factory()->create(['poll_id' => $poll->id]);

        // First vote from a guest IP
        $response1 = $this->post(route('polls.vote', [$poll, $option]), [
            'option_id' => $option->id,
        ]);
        $response1->assertSessionHas('success');
        $this->assertEquals(1, $option->fresh()->votes_count);

        // Second vote attempt from the same IP — blocked by VoteService + DB constraint
        $response2 = $this->post(route('polls.vote', [$poll, $option]), [
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

        // First vote
        $this->actingAs($user)
            ->post(route('polls.vote', [$poll, $option]), [
                'option_id' => $option->id,
            ]);

        // Second vote attempt — blocked by VoteService + DB unique constraint
        $response = $this->actingAs($user)
            ->post(route('polls.vote', [$poll, $option]), [
                'option_id' => $option->id,
            ]);

        $response->assertSessionHasErrors('vote');
        $this->assertEquals(1, $option->fresh()->votes_count);
    }

    public function test_vote_updates_cached_poll_option_vote_count_if_cached(): void
    {
        // Ensure our cache driver supports locks; database cache store is used for the test.
        config(['cache.default' => 'database']);

        $poll = Poll::factory()->create();
        $option = PollOption::factory()->create(['poll_id' => $poll->id]);

        // Prime the cache with the poll + option payload.
        $cacheKey = "poll_{$poll->slug}";
        Cache::store('database')->put($cacheKey, $poll->load(['options' => fn ($q) => $q->withCount('votes')]), now()->addSeconds(30));

        $this->post(route('polls.vote', [$poll, $option]), [
            'option_id' => $option->id,
        ]);

        $cachedPoll = Cache::store('database')->get($cacheKey);

        $this->assertNotNull($cachedPoll);
        $this->assertEquals(1, $cachedPoll->options->firstWhere('id', $option->id)->votes_count);
    }
}
