# Tasks API — SDK Integration Guide for Client Applications

This document is intended for AI assistants and developers writing Laravel applications that integrate with the Tasks API using the `light-worx/tasks-api-client` SDK.

---

## Overview

The Tasks API is a multi-tenant task management system built around a Getting Things Done (GTD) workflow. Client applications interact with it through a Laravel SDK that handles authentication, request building, and response mapping automatically.

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

## Task Statuses

The API uses a GTD-aligned set of statuses. Tasks are created with `inbox` as the default status and processed from there.

| Status | Meaning |
|---|---|
| `inbox` | Captured but not yet processed — the default on creation |
| `next` | Clarified and actionable — do this next |
| `waiting` | Delegated — waiting on someone else |
| `scheduled` | Deferred to a specific date (use with `due_at`) |
| `someday` | Parked — maybe later, not now |
| `done` | Complete |

Statuses are strings. The full list of active statuses for an organisation can be fetched via the meta endpoint — see Task Statuses (Meta) below.

---

## Contexts

Contexts are GTD-style tags that describe where or how a task can be done (e.g. `@phone`, `@computer`, `@home`). They are personal — each context belongs to a specific user identified by `owner_email` and are managed outside the SDK via the application's own UI.

Tasks carry a `context_id` (integer, nullable) identifying which context they belong to. When filtering tasks by context, pass the numeric ID of the context.

Contexts are not managed through this SDK — they are created and maintained by users directly in the application.

---

## Task Visibility — Read This First

**The API enforces strict visibility rules on all task queries. Calling `->get()` with no filters will return an empty result unless your client has the `can_view_all_tasks` permission.**

Task visibility is determined by the permissions granted to your `client_id` on the API side. There are two access models:

### Organisation-wide access (`can_view_all_tasks`)

If your client has been granted `can_view_all_tasks`, queries work without requiring any additional filters:

```php
// Works as-is for clients with can_view_all_tasks
$tasks = TasksApi::tasks()->get();
$tasks = TasksApi::tasks()->status('next')->get();
```

### Assigned task lookup (`can_lookup_assigned_tasks`)

If your client has `can_lookup_assigned_tasks` instead, **you must pass `assigned_email` on every query** or the API will return an empty result set. This is enforced server-side — the SDK cannot work around it.

```php
// WRONG — returns empty for can_lookup_assigned_tasks clients
$tasks = TasksApi::tasks()->status('next')->get();

// CORRECT — assigned_email is required
$tasks = TasksApi::tasks()
    ->status('next')
    ->assignedTo('jane@example.com')
    ->get();
```

If you are unsure which permission your client has been granted, contact whoever manages your API client credentials.

### Project visibility

Tasks are returned if they either belong to a **public project** or have **no project at all**. Tasks on private projects are excluded by default. To include tasks from a private project, your client must have `can_lookup_assigned_tasks` and you must pass both `assigned_email` and `owner_email`:

```php
$tasks = TasksApi::tasks()
    ->assignedTo('jane@example.com')
    ->ownerEmail('pastor@example.com')
    ->get();
```

---

## Tasks

### Fetch all tasks

```php
// Only works without filters for clients with can_view_all_tasks
$tasks = TasksApi::tasks()->get();
```

### Filter tasks

Filters can be chained in any combination:

```php
$tasks = TasksApi::tasks()
    ->status('next')
    ->context(3)
    ->assignedTo('jane@example.com')
    ->latest()
    ->get();
```

Available filter methods:

| Method | API parameter | Description |
|---|---|---|
| `->status(string)` | `status` | Filter by GTD status |
| `->whereStatus(string)` | `status` | Alias for `status()` |
| `->project(string)` | `project_id` | Filter by project ID |
| `->whereProject(string)` | `project_id` | Alias for `project()` |
| `->assignedTo(string)` | `assigned_email` | Filter by assignee email. **Required** for `can_lookup_assigned_tasks` clients |
| `->whereAssignedTo(string)` | `assigned_email` | Alias for `assignedTo()` |
| `->ownerEmail(string)` | `owner_email` | Unlocks visibility of private projects owned by this email |
| `->context(int)` | `context` | Filter by context ID |
| `->whereContext(int)` | `context` | Alias for `context()` |
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
    ->status('next')
    ->assignedTo('jane@example.com')
    ->first();
```

### Paginate tasks

```php
$result = TasksApi::tasks()
    ->status('inbox')
    ->assignedTo('jane@example.com')
    ->paginate(25);

$tasks = $result['data'];  // array of TaskData objects
$meta  = $result['meta'];  // pagination metadata
```

`$result['meta']` contains: `current_page`, `last_page`, `per_page`, `total`, `from`, `to`.

### Create a task

`project_id` is optional. Tasks without a project are visible to all clients within the organisation.

```php
$task = TasksApi::tasks()->create([
    'title'          => 'Review sermon notes',
    'description'    => 'Check the notes for Sunday service',
    'assigned_email' => 'john@example.com',
    'due_at'         => '2026-06-01T09:00:00Z',
    'status'         => 'inbox',
    // optional
    'project_id'     => 'proj_abc123',
    'context_id'     => 3,
]);
```

### Update a task

```php
$task = TasksApi::tasks()->update('task_abc123', [
    'status'     => 'done',
    'context_id' => null, // remove context
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
// ['inbox' => 'Inbox', 'next' => 'Next', 'waiting' => 'Waiting', ...]
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
| `$status` | `?string` | GTD status — see Task Statuses above |
| `$project_id` | `?string` | Associated project ID (nullable — tasks need not belong to a project) |
| `$context_id` | `?int` | Associated context ID (nullable) |
| `$due_at` | `?string` | Due date/time (ISO 8601). Primarily used with `scheduled` status |

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
echo $task->status;
echo $task->context_id;
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
    return back()->withErrors($e->errors());
} catch (ForbiddenException $e) {
    abort(403, 'You do not have permission to create tasks.');
} catch (UnauthorizedException $e) {
    Log::error('Tasks API authentication failure', ['message' => $e->getMessage()]);
    abort(500, 'Could not connect to Tasks API.');
}
```

---

## Rate Limits

The API enforces a limit of **60 requests per minute per client**. Exceeding this will result in a `429 Too Many Requests` response.

---

## Multi-Tenancy Rules

These are enforced by the API and must be respected in all client code:

- All tasks and projects are automatically scoped to the organisation associated with your `client_id`. You do not need to pass an organisation ID.
- You cannot query data belonging to another organisation. The API will reject any such attempt.
- The `created_by_client_id` field on tasks is set automatically by the API — do not attempt to set it manually.
- Do not store `client_secret` in your database, session, or any persistent storage. Load it exclusively from environment variables at runtime.

---

## Assignment

Tasks are assigned via `assigned_email`. This does not need to correspond to a user account within your application.

```php
TasksApi::tasks()->create([
    'title'          => 'Prepare slides',
    'assigned_email' => 'speaker@example.com',
    'status'         => 'inbox',
]);
```

---

## Quick Reference

```php
// Tasks — organisation-wide access (can_view_all_tasks)
TasksApi::tasks()->get();
TasksApi::tasks()->find($id);
TasksApi::tasks()->status('next')->get();
TasksApi::tasks()->status('next')->context($contextId)->get();
TasksApi::tasks()->create([...]);
TasksApi::tasks()->update($id, [...]);
TasksApi::tasks()->delete($id);

// Tasks — assigned lookup access (can_lookup_assigned_tasks)
// assigned_email is required on every query
TasksApi::tasks()->assignedTo('user@example.com')->get();
TasksApi::tasks()->assignedTo('user@example.com')->status('inbox')->paginate(25);
TasksApi::tasks()->assignedTo('user@example.com')->context($contextId)->get();
TasksApi::tasks()->assignedTo('user@example.com')->ownerEmail('owner@example.com')->get();

// Projects
TasksApi::projects()->get();
TasksApi::projects()->find($id);
TasksApi::projects()->create([...]);
TasksApi::projects()->paginate(20);

// Meta
TasksApi::statuses();
TasksApi::meta()->statusOptions();
```