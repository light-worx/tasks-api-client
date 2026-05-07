<?php

namespace Lightworx\TasksApiClient\Exceptions;

class ForbiddenException extends \RuntimeException
{
    public function __construct(string $message = 'Forbidden: you do not have access to this resource')
    {
        parent::__construct($message, 403);
    }
}