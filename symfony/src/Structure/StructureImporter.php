<?php

namespace App\Structure;

use App\Entity\Structure;
use App\Repository\StructureRepository;
use App\Structure\DataProvider\DataProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StructureImporter
{
    const IMPORT_STATUS_ERROR = 0;
    const IMPORT_STATUS_PENDING = 1;
    const IMPORT_STATUS_COMPLETE = 2;

    /** @var DataProvider */
    private $dataProvider;

    /** @var Connection */
    private $connection;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var StructureRepository */
    private $structureRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * StructureImporter constructor.
     *
     * @param DataProvider           $dataProvider
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param StructureRepository    $structureRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        DataProvider $dataProvider,
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        StructureRepository $structureRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->dataProvider = $dataProvider;
        $this->connection = $connection;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->structureRepository = $structureRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @return ConstraintViolationList
     * @throws ConnectionException
     * @throws \Exception
     */
    public function import(): ConstraintViolationList
    {
        if (!$this->dataProvider->isInitialized()) {
            throw new \RuntimeException('Data provider is not initialized');
        }

        // Wrap all operations in a transaction.
        $this->connection->beginTransaction();

        try {
            $this->connection->insert('import', [
                'created_at_ts' => time(),
                'status' => self::IMPORT_STATUS_PENDING,
            ]);

            $importId = $this->connection->lastInsertId();

            // Write all lines in the staged structures table.
            while(false !== $model = $this->dataProvider->next()) {
                $model->setImportId($importId);
                $this->writeTemporaryLine($model);
            }

            $this->connection->commit();

            $violationList = $this->validate($importId);
            if ($violationList->count() === 0) {
                $this->doImport($importId);
                $this->markImportAsCompleted($importId);
            } else {
                $this->markImportAsFailed($importId);
            }

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
     * @return DataProvider
     */
    public function getDataProvider(): DataProvider
    {
        return $this->dataProvider;
    }

    /**
     * @param StructureImportModel $model
     *
     * @throws DBALException
     */
    private function writeTemporaryLine(StructureImportModel $model)
    {
        $this->connection->insert('import_structure', [
            'identifier' => $model->getIdentifier(),
            'type' => $model->getType(),
            'name' => $model->getName(),
            'parent_structure' => $model->getParentStructure(),
            'enabled' => $model->getEnabled(),
            'president' => $model->getPresident(),
            'import_id' => $model->getImportId(),
            'imported' => 0,
        ]);
    }

    /**
     * @param int $importId
     *
     * @return ConstraintViolationList
     * @throws \Doctrine\DBAL\DBALException
     */
    private function validate(int $importId): ConstraintViolationList
    {
        // Check all temporary lines.
        $stmt = $this->connection->prepare('SELECT * FROM import_structure WHERE import_id = :importId');
        $stmt->bindValue('importId', $importId, ParameterType::INTEGER);
        $stmt->execute();

        $violationList = new ConstraintViolationList();
        while (false !== $row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new StructureImportModel(
                $row['identifier'],
                $row['type'],
                $row['name'],
                $row['parent_structure'],
                $row['enabled'],
                $row['president']
            );

            $model->setImportId($importId);
            $violationList->addAll($this->validator->validate($model));
        }

        return $violationList;
    }

    /**
     * @param int $importId
     *
     * @throws DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function doImport(int $importId)
    {
        // Import all structures.
        $stmt = $this->connection->prepare('SELECT * FROM import_structure WHERE import_id = :importId');
        $stmt->bindValue('importId', $importId, ParameterType::INTEGER);
        $stmt->execute();

        while (false !== $row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new StructureImportModel(
                $row['identifier'],
                $row['type'],
                $row['name'],
                $row['parent_structure'],
                $row['enabled'],
                $row['president'],
                $row['imported'],
                $row['id']
            );

            // Create the structure from the model if it doesn't already exist.
            if (!$model->isImported() && $this->countStructuresByName($model->getName()) === 0) {
                $this->createStructure($model);
            }
        }
    }

    /**
     * @param StructureImportModel $model
     *
     * @return Structure
     * @throws DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createStructure(StructureImportModel $model): Structure
    {
        // Map model to the actual structure.
        $structure = new Structure();
        $structure
            ->setIdentifier($model->getIdentifier())
            ->setType($model->getType())
            ->setName($model->getName())
            ->setEnabled($model->getEnabled())
            ->setPresident($model->getPresident())
        ;

        // Get the parent structure if needed.
        if (!is_null($model->getParentStructure())) {
            $parentStructure = $this->structureRepository->findOneBy([
                'name' => $model->getParentStructure(),
            ]);

            // If the parent structure doesn't exist, look for it in the other structures being imported.
            if (is_null($parentStructure)) {
                $stmt = $this->connection->prepare('SELECT * FROM import_structure WHERE import_id = :importId AND name = :name');
                $stmt->bindValue('import_id', $model->getImportId());
                $stmt->bindValue('name', $model->getParentStructure());
                $stmt->execute();

                $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$data) {
                    throw new \RuntimeException(sprintf('Structure %s was not found in the structures staged for import', $model->getParentStructure()));
                }

                $parentStructureModel = new StructureImportModel(
                    $data['identifier'],
                    $data['type'],
                    $data['name'],
                    $data['parent_structure'],
                    $data['enabled'],
                    $data['president']
                );

                $parentStructure = $this->createStructure($parentStructureModel);
                $structure->setParentStructure($parentStructure);
            }
        }

        $this->entityManager->persist($structure);
        $this->entityManager->flush($structure);
        $this->markStagedStructureAsImported($model->getId());

        return $structure;
    }

    /**
     * @param int $id
     *
     * @throws DBALException
     */
    private function markStagedStructureAsImported(int $id)
    {
        $this->connection->update('import_structure', [
            'imported' => 1,
        ], [
            'id' => $id,
        ]);
    }

    /**
     * @param int $importId
     *
     * @throws DBALException
     */
    private function markImportAsCompleted(int $importId)
    {
        $this->connection->update('import', [
            'status' => self::IMPORT_STATUS_COMPLETE,
        ], [
            'id' => $importId,
        ]);
    }

    /**
     * @param int $importId
     *
     * @throws DBALException
     */
    private function markImportAsFailed(int $importId)
    {
        $this->connection->update('import', [
            'status' => self::IMPORT_STATUS_ERROR,
        ], [
            'id' => $importId,
        ]);
    }

    /**
     * @param string $name
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function countStructuresByName(string $name): int
    {
        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM structure WHERE name = :name');
        $stmt->bindValue('name', $name);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_COLUMN);
    }
}
