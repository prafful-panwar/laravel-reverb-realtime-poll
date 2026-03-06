<?php

namespace App\DTOs;

class CreatePollDTO
{
    public readonly string $title;

    public readonly ?string $description;

    public readonly array $options;

    /**
     * @param  string[]  $options
     */
    public function __construct(
        string $title,
        ?string $description,
        array $options
    ) {
        $this->title = strip_tags(trim($title));
        $this->description = $description ? strip_tags(trim($description)) : null;
        $this->options = array_map(fn (string $o): string => strip_tags(trim($o)), $options);
    }
}
