<?php

namespace App\Sync\Dto;

final readonly class StructureRow
{
    public function __construct(
        public string $id,
        public ?string $parentId,
        public string $label,
        public string $shortLabel,
        public string $address
    ) {
    }

    /**
     * Build a StructureRow from a raw redcall_ref_structures.csv line.
     * Format: structure_id, structure_parent_id, structure_libelle,
     *         structure_libelle_court, adresse_numero, adresse_type_voie,
     *         adresse_voie, adresse_lieu_dit, adresse_code_postal, adresse_commune
     *
     * @param array<int,string> $row
     */
    public static function fromCsvRow(array $row) : self
    {
        $address = preg_replace(
            '/\s+/u',
            ' ',
            mb_strtoupper(sprintf(
                '%s %s %s %s %s',
                $row[4] ?? '',
                $row[5] ?? '',
                $row[6] ?? '',
                $row[8] ?? '',
                $row[9] ?? ''
            ))
        );

        return new self(
            id: (string) $row[0],
            parentId: ($row[1] ?? '') !== '' ? (string) $row[1] : null,
            label: (string) ($row[2] ?? ''),
            shortLabel: (string) ($row[3] ?? ''),
            address: trim($address ?? '')
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray() : array
    {
        return [
            'id'         => $this->id,
            'parentId'   => $this->parentId,
            'label'      => $this->label,
            'shortLabel' => $this->shortLabel,
            'address'    => $this->address,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data) : self
    {
        return new self(
            id: (string) $data['id'],
            parentId: isset($data['parentId']) && '' !== $data['parentId'] ? (string) $data['parentId'] : null,
            label: (string) ($data['label'] ?? ''),
            shortLabel: (string) ($data['shortLabel'] ?? ''),
            address: (string) ($data['address'] ?? '')
        );
    }
}
