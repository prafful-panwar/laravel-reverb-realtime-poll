<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function show(string $slug): Response
    {
        $poll = Cache::remember(
            "poll_{$slug}",
            now()->addSeconds(30),
            function () use ($slug) {
                return Poll::query()
                    ->with([
                        'options' => function (Relation $query): void {
                            $query->withCount('votes');
                        },
                    ])
                    ->where('slug', $slug)
                    ->firstOrFail();
            }
        );

        return Inertia::render('Polls/Show', [
            'poll' => $poll,
        ]);
    }
}
