<?php

namespace Lightworx\TasksApiClient;

use Lightworx\TasksApiClient\Auth\TokenManager; 
use Illuminate\Support\Facades\Http;
use Lightworx\TasksApiClient\Meta\MetaClient;
use Lightworx\TasksApiClient\Query\TaskQuery;

class TasksApiClient
{
    protected TokenManager $tokenManager;

    public function __construct(private array $config)
    {
        $this->tokenManager = new TokenManager($config);
    }

    public function tasks(): TaskQuery
    {
        return new TaskQuery($this);
    }

    public function meta(): MetaClient
    {
        return new MetaClient($this);
    }

    public function statuses(): array
    {
        return $this->meta()->statuses();
    }

    public function http()
    {
        $token = $this->tokenManager->getToken();

        return Http::baseUrl($this->config['base_url'])
            ->withToken($token)
            ->acceptJson();
    }

    public function config(string $key)
    {
        return $this->config[$key];
    }
}