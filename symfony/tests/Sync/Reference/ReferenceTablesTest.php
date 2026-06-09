<?php

namespace App\Tests\Sync\Reference;

use App\Sync\Reader\CsvReader;
use App\Sync\Reference\ReferenceTables;
use PHPUnit\Framework\TestCase;

class ReferenceTablesTest extends TestCase
{
    private string $dir;

    protected function setUp() : void
    {
        $this->dir = sys_get_temp_dir().'/reftables_'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown() : void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    private function paths() : array
    {
        $paths = [];
        foreach (glob($this->dir.'/*.csv') ?: [] as $path) {
            $paths[basename($path)] = $path;
        }

        return $paths;
    }

    public function testLoadsAllReferenceTables()
    {
        file_put_contents($this->dir.'/redcall_ref_groupes_actions.csv', "id,libelle\n1,Urgence et Secourisme\n17,Formation\n");
        file_put_contents($this->dir.'/redcall_ref_actions.csv', "id,libelle,groupe_action_id\n113,Soutien psychosocial,2\n");
        file_put_contents($this->dir.'/redcall_ref_competences.csv', "id,libelle\n10,Chauffeur VPSP\n");
        file_put_contents($this->dir.'/redcall_ref_formations.csv', "id,code,libelle\n167,PSE2,PREMIERS SECOURS EN EQUIPE DE NIVEAU 2\n");
        file_put_contents($this->dir.'/redcall_ref_nominations.csv', "id,libelle,libelle_court\n533,Referent Inclusion Numerique,RTIN\n");

        $tables = new ReferenceTables(new CsvReader());
        $tables->load($this->paths());

        $this->assertSame(2, $tables->countGroupesActions());
        $this->assertSame('Urgence et Secourisme', $tables->getGroupActionLabel('1'));
        $this->assertSame('Formation', $tables->getGroupActionLabel('17'));
        $this->assertTrue($tables->hasGroupAction('1'));
        $this->assertFalse($tables->hasGroupAction('99'));

        $this->assertSame(1, $tables->countCompetences());
        $this->assertSame('Chauffeur VPSP', $tables->getCompetenceLabel('10'));

        $this->assertSame(1, $tables->countFormations());
        $this->assertSame(['code' => 'PSE2', 'label' => 'PREMIERS SECOURS EN EQUIPE DE NIVEAU 2'], $tables->getFormation('167'));
        $this->assertTrue($tables->hasFormation('167'));
        $this->assertFalse($tables->hasFormation('99'));

        $this->assertSame(1, $tables->countNominations());
        $this->assertSame(['label' => 'Referent Inclusion Numerique', 'code' => 'RTIN'], $tables->getNomination('533'));
    }

    public function testLoadResetsPreviousState()
    {
        file_put_contents($this->dir.'/redcall_ref_formations.csv', "id,code,libelle\n167,PSE2,Lvl2\n");

        $tables = new ReferenceTables(new CsvReader());
        $tables->load($this->paths());
        $this->assertSame(1, $tables->countFormations());

        // Wipe the formations file, load again — expect empty
        unlink($this->dir.'/redcall_ref_formations.csv');
        $tables->load($this->paths());
        $this->assertSame(0, $tables->countFormations());
        $this->assertNull($tables->getFormation('167'));
    }

    public function testMissingFilesAreSkippedSilently()
    {
        $tables = new ReferenceTables(new CsvReader());
        $tables->load([]); // no files at all

        $this->assertSame(0, $tables->countGroupesActions());
        $this->assertSame(0, $tables->countCompetences());
        $this->assertSame(0, $tables->countFormations());
        $this->assertSame(0, $tables->countNominations());
    }
}
