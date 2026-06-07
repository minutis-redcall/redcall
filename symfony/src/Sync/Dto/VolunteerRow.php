<?php

namespace App\Sync\Dto;

final readonly class VolunteerRow
{
    /**
     * @param ActionRow[]     $actions
     * @param TrainingRow[]   $trainings
     * @param SkillRow[]      $skills
     * @param NominationRow[] $nominations
     */
    public function __construct(
        public string $nivol,
        public string $lastName,
        public string $firstName,
        public int $age,
        public string $personalEmail,
        public string $organizationEmail,
        public string $phone,
        public string $structureId,
        public array $actions = [],
        public array $trainings = [],
        public array $skills = [],
        public array $nominations = []
    ) {
    }

    public function isMinor() : bool
    {
        return $this->age > 0 && $this->age < 18;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray() : array
    {
        return [
            'nivol'             => $this->nivol,
            'lastName'          => $this->lastName,
            'firstName'         => $this->firstName,
            'age'               => $this->age,
            'personalEmail'     => $this->personalEmail,
            'organizationEmail' => $this->organizationEmail,
            'phone'             => $this->phone,
            'structureId'       => $this->structureId,
            'actions'           => array_map(fn (ActionRow $a) => $a->toArray(), $this->actions),
            'trainings'         => array_map(fn (TrainingRow $t) => $t->toArray(), $this->trainings),
            'skills'            => array_map(fn (SkillRow $s) => $s->toArray(), $this->skills),
            'nominations'       => array_map(fn (NominationRow $n) => $n->toArray(), $this->nominations),
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            nivol: (string) ($data['nivol'] ?? ''),
            lastName: (string) ($data['lastName'] ?? ''),
            firstName: (string) ($data['firstName'] ?? ''),
            age: (int) ($data['age'] ?? 0),
            personalEmail: (string) ($data['personalEmail'] ?? ''),
            organizationEmail: (string) ($data['organizationEmail'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            structureId: (string) ($data['structureId'] ?? ''),
            actions: array_map(fn (array $a) => ActionRow::fromArray($a), $data['actions'] ?? []),
            trainings: array_map(fn (array $t) => TrainingRow::fromArray($t), $data['trainings'] ?? []),
            skills: array_map(fn (array $s) => SkillRow::fromArray($s), $data['skills'] ?? []),
            nominations: array_map(fn (array $n) => NominationRow::fromArray($n), $data['nominations'] ?? [])
        );
    }
}
