# Tasks API — SDK Integration Guide for Client Applications

This document is intended for AI assistants and developers writing Laravel applications that integrate with the Tasks API using the `light-worx/tasks-api-client` SDK.

---

## Overview

The Tasks API is a multi-tenant task management system. Client applications interact with it through a Laravel SDK that handles authentication, request building, and response mapping automatically.

**Composer package:** `light-worx/tasks-api-client`
**Facade:** `TasksApi`
**Namespace:** `Lightworx\TasksApiClient`

All data is scoped to the client's organisation automatically. You do not pass an organisation ID — it is resolved server-side from the bearer token.

---

## Installation

```bash
composer require light-worx/tasks-api-client
```

Publish the config file:

```bash
php artisan vendor:publish --tag=tasks-api-config
```

---

## Configuration

Add the following to your `.env` file:

```env
TASKS_API_URL=https://your-api-domain.com
TASKS_API_CLIENT_ID=cli_xxxxxxxxxxxxxxxxxxxx
TASKS_API_CLIENT_SECRET=your-client-secret
```

The published config file at `config/tasks-api.php` reads from these environment variables. **Never hardcode credentials in source code or commit them to version control.**

The SDK automatically handles token acquisition and caching. Tokens are cached for 55 minutes (the API issues tokens valid for 60 minutes). You do not need to manage tokens manually.

---

## The Facade

All examples below use the `TasksApi` facade. Import it where needed:

```php
use Lightworx\TasksApiClient\Facades\TasksApi;
```

Alternatively, inject `TasksApiClient` directly via Laravel's container:

```php
use Lightworx\TasksApiClient\TasksApiClient;

public function __construct(private TasksApiClient $tasksApi) {}
```

---

## Tasks

### Fetch all tasks

```php
$tasks = TasksApi::tasks()->get();
```

### Filter tasks

Filters can be chained in any combination:

```php
$tasks = TasksApi::tasks()
    ->status('pending')
    ->project('proj_abc123')
    ->assignedTo('jane@example.com')
    ->latest()
    ->get();
```

Available filter methods:

| Method | API parameter | Description |
|---|---|---|
| `->status(string)` | `status` | Filter by task status |
| `->whereStatus(string)` | `status` | Alias for `status()` |
| `->project(string)` | `project_id` | Filter by project ID |
| `->whereProject(string)` | `project_id` | Alias for `project()` |
| `->assignedTo(string)` | `assigned_email` | Filter by assignee email |
| `->whereAssignedTo(string)` | `assigned_email` | Alias for `assignedTo()` |
| `->perPage(int)` | `per_page` | Number of results per page |
| `->latest(string?)` | `sort` | Sort descending, default `created_at` |
| `->oldest(string?)` | `sort` | Sort ascending, default `created_at` |

### Fetch a single task by ID

```php
$task = TasksApi::tasks()->find('task_abc123');
```

### Fetch the first matching task

```php
$task = TasksApi::tasks()
    ->status('pending')
    ->first();
```

### Paginate tasks

```php
$result = TasksApi::tasks()
    ->status('pending')
    ->paginate(25);

$tasks = $result['data'];  // array of TaskData objects
$meta  = $result['meta'];  // pagination metadata from the API
```

### Create a task

```php
$task = TasksApi::tasks()->create([
    'title'          => 'Review sermon notes',
    'description'    => 'Check the notes for Sunday service',
    'assigned_email' => 'john@example.com',
    'project_id'     => 'proj_abc123',
    'due_at'         => '2026-06-01T09:00:00Z',
    'status'         => 'pending',
]);
```

### Update a task

```php
$task = TasksApi::tasks()->update('task_abc123', [
    'status' => 'complete',
]);
```

### Delete a task

```php
TasksApi::tasks()->delete('task_abc123');
```

---

## Projects

### Fetch all projects

```php
$projects = TasksApi::projects()->get();
```

### Fetch a single project by ID

```php
$project = TasksApi::projects()->find('proj_abc123');
```

### Create a project

```php
$project = TasksApi::projects()->create([
    'name'        => 'Sermon Planning',
    'description' => 'Weekly sermon preparation tasks',
]);
```

### Paginate projects

```php
$result = TasksApi::projects()->paginate(20);

$projects = $result['data'];
$meta     = $result['meta'];
```

---

## Task Statuses (Meta)

Fetch the available task statuses defined by the API:

```php
$statuses = TasksApi::statuses();
```

Returns an array of status objects. To get a key-value array suitable for a select input:

```php
$options = TasksApi::meta()->statusOptions();
// e.g. ['pending' => 'Pending', 'in_progress' => 'In Progress', 'complete' => 'Complete']
```

Status metadata is cached for one hour.

---

## Data Objects

The SDK returns typed DTO objects rather than raw arrays.

### `TaskData`

| Property | Type | Description |
|---|---|---|
| `$id` | `string` | Unique task identifier |
| `$title` | `string` | Task title |
| `$description` | `?string` | Optional description |
| `$assigned_email` | `string` | Assignee email address |
| `$status` | `?string` | Current status |
| `$project_id` | `?string` | Associated project ID |
| `$due_at` | `?string` | Due date/time (ISO 8601) |

### `ProjectData`

| Property | Type | Description |
|---|---|---|
| `$id` | `string` | Unique project identifier |
| `$name` | `string` | Project name |
| `$description` | `?string` | Optional description |
| `$status` | `?string` | Current status |
| `$created_at` | `?string` | Creation timestamp (ISO 8601) |

All properties are `readonly`. Access them directly:

```php
$task = TasksApi::tasks()->find('task_abc123');

echo $task->title;
echo $task->assigned_email;
echo $task->status;
```

---

## Error Handling

The SDK throws typed exceptions for API errors. Always wrap SDK calls in a try/catch when user input is involved.

| Exception | HTTP status | When it occurs |
|---|---|---|
| `UnauthorizedException` | 401 | Invalid or expired credentials |
| `ForbiddenException` | 403 | Authenticated but not permitted |
| `ValidationException` | 422 | Request failed validation |

```php
use Lightworx\TasksApiClient\Exceptions\UnauthorizedException;
use Lightworx\TasksApiClient\Exceptions\ForbiddenException;
use Lightworx\TasksApiClient\Exceptions\ValidationException;

try {
    $task = TasksApi::tasks()->create($data);
} catch (ValidationException $e) {
    // Returns field-level errors from the API response
    return back()->withErrors($e->errors());
} catch (ForbiddenException $e) {
    abort(403, 'You do not have permission to create tasks.');
} catch (UnauthorizedException $e) {
    Log::error('Tasks API authentication failure', ['message' => $e->getMessage()]);
    abort(500, 'Could not connect to Tasks API.');
}
```

The `ValidationException` exposes an `errors()` method that returns the full field-level error array from the API, making it straightforward to pass directly to Laravel's `withErrors()`.

---

## Rate Limits

The API enforces a limit of **60 requests per minute per client**. If you are making many calls in a single request cycle (for example, bulk operations), batch your calls or introduce short delays between them. Exceeding the rate limit will result in a `429 Too Many Requests` response.

---

## Multi-Tenancy Rules

These are enforced by the API and must be respected in all client code:

- All tasks and projects are automatically scoped to the organisation associated with your `client_id`. You do not need to pass an organisation ID.
- You cannot query data belonging to another organisation. The API will reject any such attempt.
- The `created_by_client_id` field on tasks is set automatically by the API — do not attempt to set it manually.
- Do not store `client_secret` in your database, session, or any persistent storage. Load it exclusively from environment variables at runtime.

---

## Assignment

Tasks are assigned via `assigned_email`. This is an email address for an external user and does not need to correspond to a user account within your application.

```php
TasksApi::tasks()->create([
    'title'          => 'Prepare slides',
    'assigned_email' => 'speaker@example.com',
]);
```

A future `assigned_client_id` field is planned but not yet active.

---

## Quick Reference

```php
// Tasks
TasksApi::tasks()->get();
TasksApi::tasks()->find($id);
TasksApi::tasks()->status('pending')->project($projectId)->get();
TasksApi::tasks()->assignedTo('user@example.com')->latest()->paginate(25);
TasksApi::tasks()->create([...]);
TasksApi::tasks()->update($id, [...]);
TasksApi::tasks()->delete($id);

// Projects
TasksApi::projects()->get();
TasksApi::projects()->find($id);
TasksApi::projects()->create([...]);
TasksApi::projects()->paginate(20);

// Meta
TasksApi::statuses();
TasksApi::meta()->statusOptions();
```
