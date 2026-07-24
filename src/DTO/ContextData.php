<?php

namespace Lightworx\TasksApiClient\DTO;

class ContextData
{
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly ?string $colour,
        public readonly int $sort_order,
        public readonly bool $is_active,
        public readonly string $owner_email,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            label: $data['label'],
            colour: $data['colour'] ?? null,
            sort_order: $data['sort_order'],
            is_active: (bool) $data['is_active'],
            owner_email: $data['owner_email'],
        );
    }

    public static function collection(array $items): array
    {
        return array_map(fn ($item) => self::fromArray($item), $items);
    }
}