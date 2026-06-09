<?php

namespace App\Sync\Reference;

use App\Sync\Reader\CsvReader;

/**
 * In-memory immutable maps of the reference CSV files (ref_groupes_actions,
 * ref_actions, ref_competences, ref_formations, ref_nominations). Loaded
 * lazily once per sync run.
 */
class ReferenceTables
{
    /** @var array<string,string> id => libelle */
    private array $groupesActions = [];

    /** @var array<string,array{label:string, groupActionId:string}> id => row */
    private array $actions = [];

    /** @var array<string,string> id => libelle */
    private array $competences = [];

    /** @var array<string,array{code:string, label:string}> id => row */
    private array $formations = [];

    /** @var array<string,array{label:string, code:string}> id => row */
    private array $nominations = [];

    private CsvReader $reader;

    public function __construct(CsvReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param array<string,string> $paths filename => absolute path
     */
    public function load(array $paths) : void
    {
        $this->groupesActions = [];
        $this->actions        = [];
        $this->competences    = [];
        $this->formations     = [];
        $this->nominations    = [];

        if (isset($paths['redcall_ref_groupes_actions.csv'])) {
            foreach ($this->reader->read($paths['redcall_ref_groupes_actions.csv']) as $row) {
                // id,libelle
                $this->groupesActions[$row[0]] = $row[1];
            }
        }

        if (isset($paths['redcall_ref_actions.csv'])) {
            foreach ($this->reader->read($paths['redcall_ref_actions.csv']) as $row) {
                // id,libelle,groupe_action_id
                $this->actions[$row[0]] = [
                    'label'         => $row[1],
                    'groupActionId' => $row[2],
                ];
            }
        }

        if (isset($paths['redcall_ref_competences.csv'])) {
            foreach ($this->reader->read($paths['redcall_ref_competences.csv']) as $row) {
                // id,libelle
                $this->competences[$row[0]] = $row[1];
            }
        }

        if (isset($paths['redcall_ref_formations.csv'])) {
            foreach ($this->reader->read($paths['redcall_ref_formations.csv']) as $row) {
                // id,code,libelle
                $this->formations[$row[0]] = [
                    'code'  => $row[1],
                    'label' => $row[2],
                ];
            }
        }

        if (isset($paths['redcall_ref_nominations.csv'])) {
            foreach ($this->reader->read($paths['redcall_ref_nominations.csv']) as $row) {
                // id,libelle,libelle_court
                $this->nominations[$row[0]] = [
                    'label' => $row[1],
                    'code'  => $row[2],
                ];
            }
        }
    }

    public function hasGroupAction(string $id) : bool
    {
        return isset($this->groupesActions[$id]);
    }

    public function getGroupActionLabel(string $id) : ?string
    {
        return $this->groupesActions[$id] ?? null;
    }

    public function hasCompetence(string $id) : bool
    {
        return isset($this->competences[$id]);
    }

    public function getCompetenceLabel(string $id) : ?string
    {
        return $this->competences[$id] ?? null;
    }

    public function hasFormation(string $id) : bool
    {
        return isset($this->formations[$id]);
    }

    /**
     * @return array{code:string, label:string}|null
     */
    public function getFormation(string $id) : ?array
    {
        return $this->formations[$id] ?? null;
    }

    public function hasNomination(string $id) : bool
    {
        return isset($this->nominations[$id]);
    }

    /**
     * @return array{label:string, code:string}|null
     */
    public function getNomination(string $id) : ?array
    {
        return $this->nominations[$id] ?? null;
    }

    public function countGroupesActions() : int
    {
        return count($this->groupesActions);
    }

    public function countCompetences() : int
    {
        return count($this->competences);
    }

    public function countFormations() : int
    {
        return count($this->formations);
    }

    public function countNominations() : int
    {
        return count($this->nominations);
    }
}
