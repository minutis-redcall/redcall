<?php

namespace App\Sync\Dto;

final readonly class TrainingRow
{
    public function __construct(
        public string $formationId,
        public string $code,
        public string $label,
        public ?\DateTimeImmutable $gotAt,
        public ?\DateTimeImmutable $expiresAt
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray() : array
    {
        return [
            'formationId' => $this->formationId,
            'code'        => $this->code,
            'label'       => $this->label,
            'gotAt'       => $this->gotAt?->format(\DateTimeInterface::ATOM),
            'expiresAt'   => $this->expiresAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            formationId: (string) ($data['formationId'] ?? ''),
            code: (string) ($data['code'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            gotAt: isset($data['gotAt']) && '' !== $data['gotAt'] ? new \DateTimeImmutable($data['gotAt']) : null,
            expiresAt: isset($data['expiresAt']) && '' !== $data['expiresAt'] ? new \DateTimeImmutable($data['expiresAt']) : null
        );
    }
}
