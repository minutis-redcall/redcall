<?php

namespace App\Model\Sheets;

class SheetsExtract
{
    /**
     * @var SheetExtract[]
     */
    private $tabs = [];

    /**
     * @return SheetExtract[]
     */
    public function getTabs() : array
    {
        return $this->tabs;
    }

    public function addTab(SheetExtract $tab) : void
    {
        $this->tabs[] = $tab;
    }

    public function toArray() : array
    {
        $array = [];
        foreach ($this->tabs as $tab) {
            $array[] = $tab->toArray();
        }

        return $array;
    }

    public function getTab(string $identifier) : SheetExtract
    {
        foreach ($this->tabs as $tab) {
            if ($tab->getIdentifier() == $identifier) {
                return $tab;
            }
        }

        throw new \Exception("Tab {$identifier} not found");
    }

    static public function fromArray(array $array) : self
    {
        $sheetExtract = new self();

        foreach ($array as $tabArray) {
            $sheetExtract->addTab(SheetExtract::fromArray($tabArray));
        }

        return $sheetExtract;
    }
}