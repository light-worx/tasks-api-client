<?php

namespace Lightworx\TasksApiClient\Exceptions;

class ValidationException extends \RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed', 422);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}