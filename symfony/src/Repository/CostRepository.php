<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Cost;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Cost|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cost|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cost[]    findAll()
 * @method Cost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CostRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cost::class);
    }

    public function truncate()
    {
        // Credits: https://stackoverflow.com/a/9710383/731138
        $cmd        = $this->_em->getClassMetadata(Cost::class);
        $connection = $this->_em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
            $connection->executeUpdate($q);
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();

            throw $e;
        }
    }
}
