<?php

namespace App\Structure\Validation\Constraints;

use App\Structure\StructureImportModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ParentStructureValidator extends ConstraintValidator
{
    /** @var Connection */
    private $connection;

    /**
     * ParentStructureValidator constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     *
     * @param StructureImportModel $value
     * @param ParentStructure      $constraint
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function validate($value, Constraint $constraint)
    {
        if (is_null($value->getParentStructure())) {
            return;
        }

        // Look for the parent structure in the existing structures first.
        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM structure WHERE name = :name');
        $stmt->bindValue('name', $value->getParentStructure());
        $stmt->execute();

        if ($stmt->fetch(\PDO::FETCH_COLUMN)) {
            return;
        }

        // Then, look in the structures being imported.
        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM import_structure WHERE import_id = :importId AND name = :name');
        $stmt->bindValue('importId', $value->getImportId());
        $stmt->bindValue('name', $value->getParentStructure());
        $stmt->execute();

        if ($stmt->fetch(\PDO::FETCH_COLUMN)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
