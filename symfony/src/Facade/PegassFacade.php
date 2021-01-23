<?php

namespace App\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\PegassCrawlerBundle\Entity\Pegass;

class PegassFacade implements FacadeInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $identifier;

    /**
     * @var string|null
     */
    private $parentIdentifier;

    /**
     * @var array|null
     */
    private $content;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        $facade = new self;

        $facade->type             = Pegass::TYPE_VOLUNTEER;
        $facade->identifier       = '00000342302R';
        $facade->parentIdentifier = '|889|';
        $facade->content          = json_decode('{
            "user": {
                "id": "00000342302R",
                "structure": {
                    "id": 889,
                    "typeStructure": "UL",
                    "libelle": "UNITE LOCALE DE PARIS 1ER ET 2EME",
                    "libelleCourt": "7501-02",
                    "adresse": "1 Rue DU BEAUJOLAIS 75001 PARIS ",
                    "telephone": "01 42 00 00 00",
                    "mail": "email@croix-rouge.fr",
                    "parent": {
                        "id": 80
                    },
                    "structureMenantActiviteList": [
                        {
                            "id": 889,
                            "libelle": "UNITE LOCALE DE PARIS 1ER ET 2EME"
                        }
                    ]
                },
                "nom": "TIEMBLO",
                "prenom": "ALAIN",
                "actif": true,
                "mineur": false
            },
            "infos": {
                "id": "00000342302R",
                "inscriptionsExternes": false,
                "contactParMail": false,
                "listeRouge": false,
                "dateNaissance": "1984-07-10T00:00:00",
                "sexe": "H"
            },
            "contact": [
                {
                    "id": "00000342302R_MAIL_0",
                    "utilisateurId": "00000342302R",
                    "moyenComId": "MAIL",
                    "numero": 0,
                    "libelle": "email@croix-rouge.fr",
                    "flag": "NOT_MODIFIED",
                    "visible": true,
                    "canDelete": false,
                    "canUpdate": false
                },
                {
                    "id": "00000342302R_MAILTRAV_651887",
                    "utilisateurId": "00000342302R",
                    "moyenComId": "MAILTRAV",
                    "numero": 651887,
                    "libelle": "email@example.org",
                    "flag": "NOT_MODIFIED",
                    "visible": true,
                    "canDelete": true,
                    "canUpdate": true
                },
                {
                    "id": "00000342302R_PORE_651886",
                    "utilisateurId": "00000342302R",
                    "moyenComId": "PORE",
                    "numero": 651886,
                    "libelle": "0612345678",
                    "flag": "NOT_MODIFIED",
                    "visible": true,
                    "canDelete": true,
                    "canUpdate": true
                }
            ],
            "actions": [
                {
                    "id": 1044273,
                    "structure": {
                        "id": 889,
                        "libelle": "UNITE LOCALE DE PARIS 1ER ET 2EME"
                    },
                    "groupeAction": {
                        "id": 1,
                        "libelle": "Urgence et Secourisme"
                    },
                    "action": {
                        "id": 21,
                        "libelle": "Urgence et autres op\u00e9rations",
                        "groupeAction": {
                            "id": 1,
                            "libelle": "Urgence et Secourisme"
                        }
                    },
                    "dateEntree": "2018-10-26T00:00:00"
                },
                {
                    "id": 1044272,
                    "structure": {
                        "id": 889,
                        "libelle": "UNITE LOCALE DE PARIS 1ER ET 2EME"
                    },
                    "groupeAction": {
                        "id": 3,
                        "libelle": "Soutien aux activit\u00e9s"
                    },
                    "action": {
                        "id": 46,
                        "libelle": "D\u00e9veloppement associatif",
                        "groupeAction": {
                            "id": 3,
                            "libelle": "Soutien aux activit\u00e9s"
                        }
                    },
                    "dateEntree": "2018-10-26T00:00:00"
                }
            ],
            "skills": [
                {
                    "id": 1,
                    "libelle": "Participant"
                }
            ],
            "trainings": [
                {
                    "id": "00000342302R",
                    "formation": {
                        "id": "35",
                        "code": "IPS",
                        "libelle": "INITIATION AUX PREMIERS SECOURS",
                        "recyclage": false
                    },
                    "dateObtention": "2018-11-24T00:00:00"
                },
                {
                    "id": "00000342302R",
                    "formation": {
                        "id": "152",
                        "code": "IAPS",
                        "libelle": "INITIATION A L\'ALERTE ET AUX PREMIERS SECOURS",
                        "recyclage": false
                    },
                    "dateObtention": "2018-11-24T00:00:00"
                },
                {
                    "id": "00000342302R",
                    "formation": {
                        "id": "171",
                        "code": "PSC1",
                        "libelle": "PREVENTION ET SECOURS CIVIQUES DE NIVEAU 1",
                        "recyclage": true
                    },
                    "dateObtention": "2018-11-24T00:00:00"
                },
                {
                    "id": "00000342302R",
                    "formation": {
                        "id": "217",
                        "code": "IRR",
                        "libelle": "INITIATION A LA REDUCTION DES RISQUES",
                        "recyclage": false
                    },
                    "dateObtention": "2018-11-24T00:00:00"
                },
                {
                    "id": "00000342302R",
                    "formation": {
                        "id": "276",
                        "code": "PSC1 IRR",
                        "libelle": "PREVENTION ET SECOURS CIVIQUES DE NIVEAU 1 ",
                        "recyclage": false
                    },
                    "dateObtention": "2018-11-24T00:00:00"
                }
            ],
            "nominations": []
        }', true);
        $facade->updatedAt        = new \DateTime('2020-12-29 23:48:07');
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : PegassFacade
    {
        $this->type = $type;

        return $this;
    }

    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier) : PegassFacade
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getParentIdentifier() : ?string
    {
        return $this->parentIdentifier;
    }

    public function setParentIdentifier(?string $parentIdentifier) : PegassFacade
    {
        $this->parentIdentifier = $parentIdentifier;

        return $this;
    }

    public function getContent() : ?array
    {
        return $this->content;
    }

    public function setContent(?array $content) : PegassFacade
    {
        $this->content = $content;

        return $this;
    }

    public function getUpdatedAt() : \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt) : PegassFacade
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
