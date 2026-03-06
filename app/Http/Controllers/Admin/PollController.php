<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePollRequest;
use App\Repositories\Contracts\PollRepositoryInterface;
use App\Services\PollService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function __construct(
        protected PollService $pollService,
        protected PollRepositoryInterface $pollRepository
    ) {}

    public function index(): Response
    {
        $polls = $this->pollRepository->getPaginatedPollsForUser(auth()->id(), 10);

        return Inertia::render('Admin/Polls/Index', [
            'polls' => $polls,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Polls/Create');
    }

    public function store(StorePollRequest $request): RedirectResponse
    {
        $this->pollService->createPoll($request->toDTO());

        return to_route('admin.polls.index')
            ->with('success', 'Poll created successfully.');
    }
}
