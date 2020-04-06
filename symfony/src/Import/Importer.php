<?php

namespace App\Import;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class Importer
{
    /** @var Connection */
    private $connection;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var TranslatorInterface */
    private $translator;

    /** @var array */
    protected $lines = [];

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var array */
    protected $constraints = [];

    /**
     * Importer constructor.
     *
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     */
    public function __construct(
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    )
    {
        $this->connection = $connection;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->translator =  $translator;
        $this->constraints = $this->getConstraints();
    }

    /**
     * @param File $file
     *
     * @return Result
     * @throws ConnectionException
     * @throws DBALException
     * @throws \Exception
     */
    public function import(File $file): Result
    {
        $h = fopen($file->getRealPath(), 'r');
        $n = 0;
        while (false !== $line = fgetcsv($h, 0, ';')) {
            // Skip the first line.
            if ($n !== 0) {
                $this->lines[] = $this->normalizeLine($line);
            }

            $n++;
        }

        fclose($h);
        $this->sort();

        $errors = [];
        foreach ($this->lines as $lineNumber => &$line) {
             $violationList = $this->validator->validate($line, new Collection($this->constraints));
             /** @var ConstraintViolation $violation */
            foreach ($violationList as &$violation) {
                $errors[] = $this->translator->trans('import.error_message', [
                    '%lineNumber%' => $lineNumber,
                    '%field%' => $violation->getPropertyPath(),
                    '%error%' => $violation->getMessage(),
                    '%value%' => $violation->getInvalidValue(),
                 ]);
            }
        }

        if (!empty($errors)) {
            return new Result($errors);
        }

        try {
            $this->connection->beginTransaction();
            $nbImportedLines = 0;
            $nbIgnoredLines = 0;

            foreach ($this->lines as &$line) {
                $r = $this->importLine($line);
                $r ? $nbImportedLines++ : $nbIgnoredLines++;
            }

            $this->connection->commit();

            return new Result($errors, $nbImportedLines, $nbIgnoredLines);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    abstract protected function normalizeLine(array $data): array;

    /**
     * Sorts the data set.
     */
    abstract protected function sort();

    /**
     * Validates the data set.
     *
     * @return Constraint[]
     */
    abstract protected function getConstraints(): array;

    /**
     * @param array $data
     *
     * @return bool True if the line was imported, false if it was ignored.
     */
    abstract protected function importLine(array &$data): bool;
}
