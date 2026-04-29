<?php

namespace Lightworx\TasksApiClient\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TokenManager
{
    public function __construct(private array $config) {}

    public function getToken(): string
    {
        return Cache::remember($this->cacheKey(), now()->addMinutes(
            $this->config['cache_token_minutes']
        ), function () {
            return $this->requestToken();
        });
    }

    private function requestToken(): string
    {
        $response = Http::post($this->config['base_url'].'/api/clients/token', [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Unable to authenticate with Tasks API');
        }

        return $response['access_token'];
    }

    private function cacheKey(): string
    {
        return 'tasks_api_token_'.md5($this->config['client_id']);
    }
}