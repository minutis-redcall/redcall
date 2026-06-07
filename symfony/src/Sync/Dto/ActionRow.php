<?php

namespace App\Sync\Dto;

final readonly class ActionRow
{
    public function __construct(
        public string $structureId,
        public string $groupActionId,
        public string $groupActionLabel
    ) {
    }

    /**
     * @return array<string,string>
     */
    public function toArray() : array
    {
        return [
            'structureId'      => $this->structureId,
            'groupActionId'    => $this->groupActionId,
            'groupActionLabel' => $this->groupActionLabel,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            structureId: (string) ($data['structureId'] ?? ''),
            groupActionId: (string) ($data['groupActionId'] ?? ''),
            groupActionLabel: (string) ($data['groupActionLabel'] ?? '')
        );
    }
}
