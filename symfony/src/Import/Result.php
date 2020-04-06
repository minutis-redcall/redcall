<?php

namespace App\Import;

class Result
{
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILURE = 'failure';
    const STATUS_EMPTY = 'empty';

    /** @var string */
    private $status;

    /** @var array An array of validation errors */
    private $errors;

    /** @var int|null */
    private $nbImportedLines;

    /** @var int|null */
    private $nbIgnoredLines;

    /**
     * Result constructor.
     *
     * @param array            $errors
     * @param int|null         $nbImportedLines
     * @param int|null         $nbIgnoredLines
     */
    public function __construct(array $errors, ?int $nbImportedLines = null, ?int $nbIgnoredLines = null)
    {
        if (!empty($errors)) {
            if (!is_null($nbIgnoredLines) || !is_null($nbImportedLines)) {
                throw new \LogicException('Number of ignored and imported lines should be 0 when violation list is not empty');
            }

            $this->status = self::STATUS_FAILURE;
        } else if($this->nbImportedLines === 0) {
            $this->status = self::STATUS_EMPTY;
        } else {
            $this->status = self::STATUS_COMPLETED;
        }

        $this->errors = $errors;
        $this->nbImportedLines = $nbImportedLines;
        $this->nbIgnoredLines = $nbIgnoredLines;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return int|null
     */
    public function getNbImportedLines(): ?int
    {
        return $this->nbImportedLines;
    }

    /**
     * @return int|null
     */
    public function getNbIgnoredLines(): ?int
    {
        return $this->nbIgnoredLines;
    }
}
