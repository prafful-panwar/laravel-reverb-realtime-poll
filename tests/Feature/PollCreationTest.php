<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_a_poll_with_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.polls.store'), [
            'title' => 'Favorite Framework?',
            'description' => 'A test poll.',
            'options' => ['Laravel', 'Vue', 'React'],
        ]);

        $response->assertRedirect(route('admin.polls.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('polls', [
            'title' => 'Favorite Framework?',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('poll_options', ['text' => 'Laravel']);
        $this->assertDatabaseHas('poll_options', ['text' => 'Vue']);
        $this->assertDatabaseHas('poll_options', ['text' => 'React']);
    }

    public function test_poll_creation_fails_if_options_are_less_than_two(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.polls.store'), [
            'title' => 'Invalid Poll',
            'options' => ['Only one option'],
        ]);

        $response->assertSessionHasErrors('options');
    }
}
