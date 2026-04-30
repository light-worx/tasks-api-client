<?php

namespace Lightworx\TasksApiClient\DTO;

class TaskData
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $assigned_email,
        public readonly ?string $status,
        public readonly ?string $project_id,
        public readonly ?string $due_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            assigned_email: $data['assigned_email'],
            status: $data['status'] ?? null,
            project_id: $data['project_id'] ?? null,
            due_at: $data['due_at'] ?? null,
        );
    }

    public static function collection(array $items): array
    {
        return array_map(
            fn ($item) => self::fromArray($item),
            $items
        );
    }
}