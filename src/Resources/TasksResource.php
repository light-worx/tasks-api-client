<?php

namespace Lightworx\TasksApiClient\Resources;

use Lightworx\TasksApiClient\Query\TaskQuery;

class TasksResource
{
    public function __construct(private $client) {}

    public function query(): TaskQuery
    {
        return new TaskQuery($this->client);
    }
}