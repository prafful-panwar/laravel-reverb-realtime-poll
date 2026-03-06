<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoteRequest;
use App\Models\Poll;
use App\Repositories\Contracts\PollRepositoryInterface;
use App\Services\VoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class VoteController extends Controller
{
    public function __construct(
        protected VoteService $voteService,
        protected PollRepositoryInterface $pollRepository
    ) {}

    public function store(StoreVoteRequest $request, Poll $poll, int $optionId): RedirectResponse
    {
        $option = $this->pollRepository->findOption($poll, $optionId);

        if (! $request->user() && $request->cookie("has_voted_{$poll->id}")) {
            return back()->withErrors(['vote' => 'You have already voted on this poll.']);
        }

        try {
            $this->voteService->submitVote($request->toDTO($poll, $option));

            $response = back()->with('success', 'Vote recorded successfully!');

            if (! $request->user()) {
                $response->cookie("has_voted_{$poll->id}", true, 60 * 24 * 365 * 5);
            }

            return $response;
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
