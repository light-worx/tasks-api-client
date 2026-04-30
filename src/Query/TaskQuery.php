<?php

namespace Lightworx\TasksApiClient\Query;

use Lightworx\TasksApiClient\DTO\TaskData;

class TaskQuery
{
    protected array $params = [];

    public function __construct(private $client) {}

    // -------------------------
    // Filters (Eloquent-style)
    // -------------------------

    public function whereStatus(string $status): self
    {
        $this->params['status'] = $status;
        return $this;
    }

    public function status(string $status): self
    {
        return $this->whereStatus($status);
    }

    public function whereProject(string $projectId): self
    {
        $this->params['project_id'] = $projectId;
        return $this;
    }

    public function project(string $projectId): self
    {
        return $this->whereProject($projectId);
    }

    public function whereAssignedTo(string $email): self
    {
        $this->params['assigned_email'] = $email;
        return $this;
    }

    public function assignedTo(string $email): self
    {
        return $this->whereAssignedTo($email);
    }

    // -------------------------
    // Sorting
    // -------------------------

    public function latest(string $column = 'created_at'): self
    {
        $this->params['sort'] = '-' . $column;
        return $this;
    }

    public function oldest(string $column = 'created_at'): self
    {
        $this->params['sort'] = $column;
        return $this;
    }

    // -------------------------
    // Execution
    // -------------------------

    public function get(): array
    {
        $response = $this->client->http()
            ->get('/api/tasks', $this->params)
            ->json('data');

        return TaskData::collection($response ?? []);
    }

    public function first(): ?TaskData
    {
        $this->params['limit'] = 1;

        $results = $this->get();

        return $results[0] ?? null;
    }

    public function paginate(int $perPage = 50): array
    {
        $this->params['per_page'] = $perPage;

        $response = $this->client->http()
            ->get('/api/tasks', $this->params)
            ->json();

        return [
            'data' => TaskData::collection($response['data'] ?? []),
            'meta' => $response['meta'] ?? null,
        ];
    }

    public function create(array $data): TaskData
    {
        $response = $this->client->http()
            ->post('/api/tasks', $data)
            ->json();

        return TaskData::fromArray($response);
    }

    public function update(string $id, array $data): TaskData
    {
        $response = $this->client->http()
            ->put("/api/tasks/{$id}", $data)
            ->json();

        return TaskData::fromArray($response);
    }

    public function delete(string $id): bool
    {
        return $this->client->http()
            ->delete("/api/tasks/{$id}")
            ->successful();
    }
}