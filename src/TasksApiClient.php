<?php

namespace Lightworx\TasksApiClient;

use Lightworx\TasksApiClient\Auth\TokenManager; 
use Illuminate\Support\Facades\Http;
use Lightworx\TasksApiClient\Meta\MetaClient;
use Lightworx\TasksApiClient\Query\ProjectQuery;
use Lightworx\TasksApiClient\Query\TaskQuery;
use Lightworx\TasksApiClient\Exceptions\ForbiddenException;
use Lightworx\TasksApiClient\Exceptions\UnauthorizedException;
use Lightworx\TasksApiClient\Exceptions\ValidationException;
use Illuminate\Http\Client\Response;

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

    public function projects(): ProjectQuery
    {
        return new ProjectQuery($this);
    }

    public function request(string $method, string $url, array $data = []): Response
    {
        $response = $this->http()->{$method}($url, $data);

        if ($response->status() === 401) {
            // Token was rejected — refresh and retry once
            $this->tokenManager->refreshToken();
            $response = $this->http()->{$method}($url, $data);
        }

        return $response;
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

    public function handleResponse(Response $response): Response
    {
        match ($response->status()) {
            401 => throw new UnauthorizedException(),
            403 => throw new ForbiddenException(),
            422 => throw new ValidationException($response->json('errors') ?? []),
            default => null,
        };

        return $response;
    }
}