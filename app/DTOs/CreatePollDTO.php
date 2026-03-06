<?php

namespace App\DTOs;

class CreatePollDTO
{
    public readonly int $userId;

    public readonly string $title;

    public readonly ?string $description;

    public readonly array $options;

    /**
     * @param  string[]  $options
     */
    public function __construct(
        int $userId,
        string $title,
        ?string $description,
        array $options
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->description = $description;
        $this->options = $options;
    }
}
