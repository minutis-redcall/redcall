<?php

namespace App\Sync\Dto;

final readonly class NominationRow
{
    public function __construct(
        public string $nominationId,
        public string $code,
        public string $label,
        public string $structureId,
        public ?\DateTimeImmutable $gotAt
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray() : array
    {
        return [
            'nominationId' => $this->nominationId,
            'code'         => $this->code,
            'label'        => $this->label,
            'structureId'  => $this->structureId,
            'gotAt'        => $this->gotAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            nominationId: (string) ($data['nominationId'] ?? ''),
            code: (string) ($data['code'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            structureId: (string) ($data['structureId'] ?? ''),
            gotAt: isset($data['gotAt']) && '' !== $data['gotAt'] ? new \DateTimeImmutable($data['gotAt']) : null
        );
    }
}
