<?php

namespace Lightworx\TasksApiClient\Resources;

use Lightworx\TasksApiClient\TasksApiClient;

class TasksResource
{
    public function __construct(private TasksApiClient $client) {}

    public function all()
    {
        return $this->client->http()
            ->get('/api/tasks')
            ->json();
    }

    public function create(array $data)
    {
        return $this->client->http()
            ->post('/api/tasks', $data)
            ->json();
    }

    public function byAssignee(string $email)
    {
        return $this->client->http()
            ->get("/api/tasks/assignee/{$email}")
            ->json();
    }
}