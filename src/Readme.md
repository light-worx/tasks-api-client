# SDK usage (Eloquent-like)

** Basic query **
$tasks = TasksApi::tasks()
    ->status('pending')
    ->get();

** Multiple filters **
$tasks = TasksApi::tasks()
    ->status('pending')
    ->project('sermon-plan')
    ->assignedTo('john@example.com')
    ->latest()
    ->get();

** Single record **
$task = TasksApi::tasks()
    ->whereStatus('pending')
    ->first();

** Pagination **
$result = TasksApi::tasks()
    ->status('pending')
    ->paginate(25);

$tasks = $result['data'];
$meta = $result['meta'];