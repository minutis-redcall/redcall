<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210114111403 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
            INSERT IGNORE INTO category
                ( id , name , priority )
            VALUES
                ( 1, 'Actions', 0 ),
                ( 3, 'Secours', 1 ),
                ( 4, 'Véhicules', 2 ),
                ( 5, 'Trons communs', 3 ),
                ( 6, 'Direction', 4 ),
                ( 7, 'Maraude', 5 );
        ");

        $this->addSql("
            INSERT IGNORE INTO badge
                (  id , category_id , synonym_id , parent_id ,      external_id ,           name ,                                     description , priority , visibility )
            VALUES
                (   2 ,           1 ,       NULL ,      NULL , 'groupeAction-2' , 'AS'           , 'Action Sociale'                                 ,        1 ,          1 ),
                (   7 ,           1 ,       NULL ,      NULL , 'groupeAction-1' , 'US'           , 'Urgence et Secourisme'                          ,        0 ,          1 ),
                (  10 ,           3 ,       NULL ,      NULL , 'training-171'   , 'PSC1'         , 'PREVENTION ET SECOURS CIVIQUES DE NIVEAU 1'     ,        0 ,          1 ),
                (  14 ,           5 ,       NULL ,      NULL , 'training-282'   , 'TCAU'         , 'TRONC COMMUN DES ACTEURS DE L URGENCE'          ,        0 ,          1 ),
                (  21 ,           3 ,       NULL ,        10 , 'training-166'   , 'PSE1'         , 'PREMIERS SECOURS EN EQUIPE DE NIVEAU 1'         ,        0 ,          1 ),
                (  53 ,           3 ,       NULL ,        21 , 'training-167'   , 'PSE2'         , 'PREMIERS SECOURS EN EQUIPE DE NIVEAU 2'         ,        2 ,          1 ),
                (  58 ,           4 ,       NULL ,      NULL , 'skill-9'        , 'VL'           , 'Chauffeur VL'                                   ,        0 ,          1 ),
                (  60 ,           7 ,       NULL ,      NULL , 'skill-15'       , 'Maraudeur'    , 'Maraudeur'                                      ,        0 ,          1 ),
                (  94 ,           3 ,       NULL ,        53 , 'training-17'    , 'CI'           , 'CHEF D''INTERVENTION'                           ,        3 ,          1 ),
                (  97 ,           4 ,       NULL ,        58 , 'skill-10'       , 'VPSP'         , 'Chauffeur VPSP'                                 ,        1 ,          1 ),
                ( 141 ,           5 ,       NULL ,      NULL , 'training-315'   , 'TCEO'         , 'Tronc Commun des Encadrants Opérationnels'      ,        0 ,          1 ),
                ( 221 ,           6 ,       NULL ,      NULL , 'nomination-40'  , 'DLUS'         , 'Directeur Local de l''Urgence et du Secourisme' ,        0 ,          1 ),
                ( 319 ,           6 ,       NULL ,      NULL , 'nomination-309' , 'DLAS'         , 'Directeur Local de l''Action Sociale'           ,        0 ,          1 ),
                ( 364 ,        NULL ,       NULL ,      NULL , 'skill-71'       , 'Photographe'  , 'Photographe'                                    ,        0 ,          1 ),
                ( 495 ,           7 ,       NULL ,      NULL , 'nomination-331' , 'Chef Maraude' , 'Chef d''équipe maraude'                         ,        0 ,          1 );
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
