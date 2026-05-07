<?php

namespace Lightworx\TasksApiClient\DTO;

class ProjectData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $status,
        public readonly ?string $created_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            created_at: $data['created_at'] ?? null,
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