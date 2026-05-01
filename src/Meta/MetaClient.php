<?php

namespace Lightworx\TasksApiClient\Meta;

use Illuminate\Support\Facades\Cache;

class MetaClient
{
    public function __construct(private $client) {}

    public function all(): array
    {
        // Use a unique key for the client-side cache
        return Cache::remember('tasks_api.meta_client', 3600, function () {
            $response = $this->client->http()->get('/api/tasks/meta');
            
            $data = $response->json();

            // CRITICAL: Ensure we are only caching raw arrays
            // We use recursion or collection helpers to strip all PHP class info
            return json_decode(json_encode($data), true);
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