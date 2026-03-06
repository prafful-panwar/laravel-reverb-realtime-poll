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

        try {
            $this->voteService->submitVote($request->toDTO($poll, $option));

            return back()->with('success', 'Vote recorded successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
