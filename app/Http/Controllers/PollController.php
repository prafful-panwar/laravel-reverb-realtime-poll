<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\PollRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function __construct(
        protected PollRepositoryInterface $pollRepository
    ) {}

    public function show(string $slug): Response
    {
        $poll = $this->pollRepository->findBySlug($slug);

        return Inertia::render('Polls/Show', [
            'poll' => $poll,
        ]);
    }
}
