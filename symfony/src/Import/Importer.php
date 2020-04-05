<?php

namespace App\Import;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class Importer
{
    /** @var array */
    private $lines = [];

    /** @var Connection */
    private $connection;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * Importer constructor.
     *
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    )
    {
        $this->connection = $connection;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param File $file
     *
     * @return ConstraintViolationList
     * @throws ConnectionException
     * @throws DBALException
     * @throws \Exception
     */
    public function import(File $file): ConstraintViolationList
    {
        $h = fopen($file->getRealPath(), 'r');
        while (false !== $line = fgetcsv($h, 0, ';')) {
            $this->lines[] = $this->normalizeLine($line);
        }

        fclose($h);
        $this->sort();

        $violationList = new ConstraintViolationList();
        foreach ($this->lines as &$line) {
            $violationList->addAll($this->validator->validate($line, $this->getConstraints()));
        }

        if ($violationList->count() > 0) {
            return $violationList;
        }

        try {
            $this->connection->beginTransaction();
            foreach ($this->lines as &$line) {
                $this->importLine($line);
            }

            $this->connection->commit();

            return $violationList;
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
     */
    abstract protected function importLine(array &$data);
}
