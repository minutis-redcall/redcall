<?php

namespace App\Tests\Sync\Dto;

use App\Sync\Dto\StructureRow;
use PHPUnit\Framework\TestCase;

class StructureRowTest extends TestCase
{
    public function testFromCsvRowBuildsTypedAddress()
    {
        $row = StructureRow::fromCsvRow([
            '889', '80', 'UNITE LOCALE DE PARIS 1ER ET 2EME', 'UL PARIS0102',
            '1', 'Rue', 'DU BEAUJOLAIS', '', '75001', 'PARIS 01',
        ]);

        $this->assertSame('889', $row->id);
        $this->assertSame('80', $row->parentId);
        $this->assertSame('UNITE LOCALE DE PARIS 1ER ET 2EME', $row->label);
        $this->assertSame('UL PARIS0102', $row->shortLabel);
        $this->assertSame('1 RUE DU BEAUJOLAIS 75001 PARIS 01', $row->address);
    }

    public function testFromCsvRowWithoutParent()
    {
        $row = StructureRow::fromCsvRow([
            '1', '', 'CROIX-ROUGE FRANCAISE', 'CRF', '', '', '', '', '', '',
        ]);

        $this->assertNull($row->parentId);
    }

    public function testToArrayFromArrayRoundtrip()
    {
        $original = new StructureRow(
            id: '980',
            parentId: '80',
            label: 'UL PARIS BLABLA',
            shortLabel: 'UL P',
            address: '1 RUE DE LA PAIX 75001 PARIS'
        );

        $restored = StructureRow::fromArray($original->toArray());

        $this->assertEquals($original, $restored);
    }
}
