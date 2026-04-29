<?php

namespace Lightworx\TasksApiClient\Facades;

use Illuminate\Support\Facades\Facade;
use Lightworx\TasksApiClient\TasksApiClient;

class TasksApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TasksApiClient::class;
    }
}