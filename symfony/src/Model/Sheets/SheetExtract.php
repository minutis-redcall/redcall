<?php

namespace App\Model\Sheets;

class SheetExtract
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var int
     */
    private $numberOfRows = 0;

    private $tabName;

    private $rows = [];

    public function getIdentifier() : string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier) : void
    {
        $this->identifier = $identifier;
    }

    public function getNumberOfRows() : int
    {
        return $this->numberOfRows;
    }

    public function setNumberOfRows(int $numberOfRows) : void
    {
        $this->numberOfRows = $numberOfRows;
    }

    public function getTabName()
    {
        return $this->tabName;
    }

    public function setTabName($tabName) : void
    {
        $this->tabName = $tabName;
    }

    public function getRows() : array
    {
        return $this->rows;
    }

    public function setRows(array $rows) : void
    {
        $this->rows = $rows;
    }

    public function addRows(array $rows) : void
    {
        $this->rows = array_merge($this->rows, $rows);
    }

    public function toArray() : array
    {
        return [
            'identifier'   => $this->identifier,
            'tabName'      => $this->tabName,
            'numberOfRows' => $this->numberOfRows,
            'rows'         => $this->rows,
        ];
    }

    static public function fromArray(array $array) : self
    {
        $extract = new self();
        $extract->setIdentifier($array['identifier']);
        $extract->setTabName($array['tabName']);
        $extract->setNumberOfRows($array['numberOfRows']);
        $extract->setRows($array['rows']);

        return $extract;
    }
}