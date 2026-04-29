<?php

namespace Lightworx\TasksApiClient;

use Illuminate\Support\ServiceProvider;

class TasksApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tasks-api.php', 'tasks-api');

        $this->app->singleton(TasksApiClient::class, function () {
            return new TasksApiClient(config('tasks-api'));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/tasks-api.php' => config_path('tasks-api.php'),
        ], 'tasks-api-config');
    }
}