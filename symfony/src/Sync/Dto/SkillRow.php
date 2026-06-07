<?php

namespace App\Sync\Dto;

final readonly class SkillRow
{
    public function __construct(
        public string $competenceId,
        public string $label
    ) {
    }

    /**
     * @return array<string,string>
     */
    public function toArray() : array
    {
        return [
            'competenceId' => $this->competenceId,
            'label'        => $this->label,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            competenceId: (string) ($data['competenceId'] ?? ''),
            label: (string) ($data['label'] ?? '')
        );
    }
}
