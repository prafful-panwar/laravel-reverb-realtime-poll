<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Repositories\Contracts\PollRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class PollResultController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PollRepositoryInterface $pollRepository
    ) {}

    public function show(Poll $poll): Response
    {
        $this->authorize('view', $poll);

        $poll = $this->pollRepository->findWithResults($poll);

        return Inertia::render('Admin/Polls/Show', [
            'poll' => $poll,
        ]);
    }
}
