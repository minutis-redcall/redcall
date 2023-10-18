<?php

namespace App\Model\InstancesNationales;

class SheetExtract
{
    /**
     * @var string
     */
    private $identifier;
    private $rows = [];

    public function getIdentifier() : string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier) : void
    {
        $this->identifier = $identifier;
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

    public function getColumn(string $columnName) : array
    {
        if (false === $index = array_search($columnName, $this->rows[0])) {
            throw new \Exception("Column {$columnName} not found");
        }

        $column = [];
        foreach ($this->rows as $row) {
            if (empty($row)) {
                continue;
            }

            $column[] = $row[$index];
        }

        return $column;
    }

    public function toArray() : array
    {
        return [
            'identifier' => $this->identifier,
            'rows'       => $this->rows,
        ];
    }

    static public function fromArray(array $array) : self
    {
        $extract = new self();
        $extract->setIdentifier($array['identifier']);
        $extract->setRows($array['rows']);

        return $extract;
    }
}