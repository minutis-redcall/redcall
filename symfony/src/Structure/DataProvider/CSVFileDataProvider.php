<?php

namespace App\Structure\DataProvider;

use App\Structure\StructureImportModel;
use Symfony\Component\HttpFoundation\File\File;

class CSVFileDataProvider extends DataProvider
{
    /** @var resource|null */
    private $handler;

    /** @var string */
    private $delimiter;

    /**
     * {@inheritDoc}
     */
    public function init(array $options)
    {
        /** @var File $file */
        $file = $options['file'];
        $this->delimiter = $options['delimiter'] ?? ',';
        $this->handler = fopen($file->getRealPath(), 'r');
        $this->isInitialized = true;
    }

    /**
     * {@inheritDoc}
     */
    public function doNext()
    {
        $data = fgetcsv($this->handler, 0, $this->delimiter);
        if (!$data) {
            return false;
        }

        return new StructureImportModel(
            (int) $data[0],
            $data[1],
            $data[2],
            !empty($data[3]) ? $data[3] : null,
            (bool) $data[4],
            $data[5]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function terminate()
    {
        fclose($this->handler);
    }
}
