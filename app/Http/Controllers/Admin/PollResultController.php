<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PollResultController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the specified poll results for the admin.
     */
    public function show(Poll $poll): Response
    {
        $this->authorize('view', $poll);

        $poll = Cache::remember(
            "poll_{$poll->slug}",
            now()->addSeconds(30),
            fn () => $poll->load([
                'options' => function (Relation $query): void {
                    $query->withCount('votes');
                },
            ])
        );

        return Inertia::render('Admin/Polls/Show', [
            'poll' => $poll,
        ]);
    }
}
