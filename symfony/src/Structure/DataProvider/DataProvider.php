<?php

namespace App\Structure\DataProvider;

use App\Structure\StructureImportModel;

abstract class DataProvider
{
    /** @var bool */
    protected $isInitialized = false;

    /**
     * @return StructureImportModel|false
     */
    public function next()
    {
        if (!$this->isInitialized()) {
            throw new \RuntimeException('Data provider is not initialized. Set the $isInitialized attribute to true by calling the init() method');
        }

        $data = $this->doNext();
        if (!$data) {
            $this->terminate();
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    /**
     * @return StructureImportModel|false
     */
    abstract public function doNext();

    /**
     * @param array $options
     */
    abstract function init(array $options);
    abstract function terminate();
}
