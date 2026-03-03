<?php

namespace App\DTOs;

class CreatePollDTO
{
    /**
     * @param  string[]  $options
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly array $options
    ) {}
}
