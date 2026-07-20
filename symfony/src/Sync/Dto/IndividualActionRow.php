<?php

namespace App\Sync\Dto;

/**
 * One row of redcall_actions_menees.csv: a volunteer participating in an
 * INDIVIDUAL action (e.g. "Urgence et autres operations"), as opposed to
 * ActionRow which carries the coarser groupe d'action level.
 */
final readonly class IndividualActionRow
{
    public function __construct(
        public string $structureId,
        public string $actionId,
        public string $label
    ) {
    }

    /**
     * @return array<string,string>
     */
    public function toArray() : array
    {
        return [
            'structureId' => $this->structureId,
            'actionId'    => $this->actionId,
            'label'       => $this->label,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            structureId: (string) ($data['structureId'] ?? ''),
            actionId: (string) ($data['actionId'] ?? ''),
            label: (string) ($data['label'] ?? '')
        );
    }
}
