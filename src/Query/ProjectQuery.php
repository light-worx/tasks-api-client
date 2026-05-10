<?php

namespace Lightworx\TasksApiClient\Query;

use Lightworx\TasksApiClient\DTO\ProjectData;

class ProjectQuery
{
    protected array $params = [];

    public function __construct(private $client) {}

    // -------------------------
    // Filters
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
        $response = $this->client->handleResponse(
            $this->client->request('get', '/api/projects', $this->params)
        )->json();

        $items = isset($response['data']) ? $response['data'] : $response;

        return ProjectData::collection($items ?? []);
    }

    public function find(string $id): ?ProjectData
    {
        $response = $this->client->handleResponse(
            $this->client->request('get', "/api/projects/{$id}")
        )->json();

        return $response ? ProjectData::fromArray($response) : null;
    }

    public function first(): ?ProjectData
    {
        $this->params['limit'] = 1;

        $results = $this->get();

        return $results[0] ?? null;
    }

    public function paginate(int $perPage = 50): array
    {
        $this->params['per_page'] = $perPage;

        $response = $this->client->handleResponse(
            $this->client->request('get', '/api/projects', $this->params)
        )->json();

        $items = isset($response['data']) ? $response['data'] : $response;

        return [
            'data' => ProjectData::collection($items ?? []),
            'meta' => $response['meta'] ?? null,
        ];
    }

    public function create(array $data): ProjectData
    {
        $response = $this->client->handleResponse(
            $this->client->request('post', '/api/projects', $data)
        )->json();

        return ProjectData::fromArray($response);
    }

    public function update(string $id, array $data): ProjectData
    {
        $response = $this->client->handleResponse(
            $this->client->request('put', "/api/projects/{$id}", $data)
        )->json();

        return ProjectData::fromArray($response);
    }

    public function delete(string $id): bool
    {
        $this->client->handleResponse(
            $this->client->request('delete', "/api/projects/{$id}")
        );

        return true;
    }
}