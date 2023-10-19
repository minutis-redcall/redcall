<?php

namespace App\Model\InstancesNationales;

class SheetExtract
{
    /**
     * @var string
     */
    private $identifier;
    private $rows = [];

    static public function fromArray(array $array) : self
    {
        $extract = new self();
        $extract->setIdentifier($array['identifier']);
        $extract->setRows($array['rows']);

        return $extract;
    }

    static public function fromRows(string $identifier, int $headerIndex, array $rows) : self
    {
        if (!isset($rows[$headerIndex])) {
            throw new \Exception("Header index {$headerIndex} not found");
        }

        $instance = new self();
        $instance->setIdentifier($identifier);

        $keyedRows = [];
        $header    = $rows[$headerIndex];
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            if (empty($rows[$i])) {
                continue;
            }

            $keyedRow = [];
            foreach ($header as $key => $columnName) {
                $keyedRow[$columnName] = $rows[$i][$key] ?? null;
            }

            $keyedRows[] = $keyedRow;
        }

        $instance->setRows($keyedRows);

        return $instance;
    }

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

    public function getColumn(string $columnName) : array
    {
        $column = [];
        foreach ($this->rows as $row) {
            if (empty($row)) {
                continue;
            }

            $column[] = $row[$columnName];
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

    public function count() : int
    {
        return count($this->rows);
    }

    public function getRow(array $identifier) : ?array
    {
        foreach ($this->rows as $row) {
            if (empty($row)) {
                continue;
            }

            foreach ($identifier as $key => $value) {
                if ($row[$key] != $value) {
                    continue 2;
                }
            }

            return $row;
        }

        return null;
    }
}