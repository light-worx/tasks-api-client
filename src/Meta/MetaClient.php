<?php

namespace Lightworx\TasksApiClient\Meta;

use Illuminate\Support\Facades\Cache;

class MetaClient
{
    public function __construct(private $client) {}

    public function all(): array
    {
        return Cache::remember('tasks_api.meta', 3600, function () {
            return $this->client->http()
                ->get('/api/tasks/meta')
                ->json();
        });
    }

    public function statuses(): array
    {
        return $this->all()['statuses'] ?? [];
    }

    public function statusOptions(): array
    {
        return collect($this->statuses())
            ->pluck('label', 'id')
            ->toArray();
    }
}