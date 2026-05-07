<?php

namespace Lightworx\TasksApiClient\Exceptions;

class UnauthorizedException extends \RuntimeException
{
    public function __construct(string $message = 'Unauthorized: invalid or expired credentials')
    {
        parent::__construct($message, 401);
    }
}