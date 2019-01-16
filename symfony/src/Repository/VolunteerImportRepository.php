<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\VolunteerImport;
use App\Exception\ImportErrorException;
use App\Exception\ImportFatalException;
use App\Exception\ImportWarningException;
use App\Services\Random;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method VolunteerImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method VolunteerImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method VolunteerImport[]    findAll()
 * @method VolunteerImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerImportRepository extends ServiceEntityRepository
{
    /**
     * VolunteerImportRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, VolunteerImport::class);
    }

    /**
     * Called before a new import.
     *
     * At scale, we'll need to set a lock system to avoid concurrent imports.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function begin()
    {
        $this->_em->getConnection()->query('TRUNCATE TABLE volunteer_import');
    }

    /**
     * Takes raw data from the google spreadseet and try to convert it on the
     * right volunteer format. If volunteer is broken, creates a log and do
     * not import it.
     *
     * @param array $row
     *
     * @return VolunteerImport|null
     * @throws \Doctrine\ORM\ORMException
     */
    public function sanitize(array $row): ?VolunteerImport
    {
        $volunteerImport = new VolunteerImport();
        $volunteerImport->setId($row['id']);

        $this
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setNivol($volunteerImport, $row['nivol']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setFirstName($volunteerImport, $row['firstname']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setLastName($volunteerImport, $row['lastname']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setIsMinor($volunteerImport, $row['minor']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setPhoneNumber($volunteerImport, $row['phone']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setPostalCode($volunteerImport, $row['postal_code']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setEmail($volunteerImport, $row['email']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setIsCallable($volunteerImport, $row['callable']);
            })
            ->tryDo($volunteerImport, function () use ($volunteerImport, $row) {
                $this->setTags($volunteerImport, $row['tags']);
            });

        $this->_em->persist($volunteerImport);
        $this->_em->flush($volunteerImport);

        return $volunteerImport;
    }

    /**
     * @param VolunteerImport $import
     * @param string          $nivol
     */
    protected function setNivol(VolunteerImport $import, string $nivol)
    {
        $import->setNivol($nivol);

        if (!$nivol) {
            $import->setNivol('missing-'.Random::generate(8));

            throw new ImportErrorException('Nivol is missing');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $firstName
     */
    protected function setFirstName(VolunteerImport $import, string $firstName)
    {
        $import->setFirstName($firstName);

        if (!$firstName) {
            throw new ImportErrorException('First name is missing');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $lastName
     */
    protected function setLastName(VolunteerImport $import, string $lastName)
    {
        $import->setLastName($lastName);

        if (!$lastName) {
            throw new ImportErrorException('Last name is missing');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $isMinor
     */
    protected function setIsMinor(VolunteerImport $import, string $isMinor)
    {
        if (!$isMinor) {
            throw new ImportErrorException('Minority information is missing');
        }

        $isMinor = strtolower($isMinor) == 'oui';

        $import->setIsMinor($isMinor);

        if ($isMinor) {
            throw new ImportErrorException('Volunteer is minor');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $phone
     */
    protected function setPhoneNumber(VolunteerImport $import, string $phone)
    {
        $import->setPhone($phone);

        if (!$phone) {
            throw new ImportErrorException('Phone number is missing');
        }

        $phone = ltrim(preg_replace('/[^0-9]/', '', $phone), 0);
        if (strlen($phone) == 9) {
            $phone = '33'.ltrim($phone, 0);
        }
        $import->setPhone($phone);

        if (strlen($phone) != 11) {
            throw new ImportErrorException('Phone number is invalid');
        }

        if (!in_array(substr($phone, 0, 3), ['336', '337'])) {
            throw new ImportErrorException('Phone number is not a mobile');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $postalCode
     */
    protected function setPostalCode(VolunteerImport $import, string $postalCode)
    {
        $postalCode = ucwords(strtolower($postalCode)); // May be a country as well

        if (!$postalCode) {
            throw new ImportWarningException('Postal code is missing');
        }

        $import->setPostalCode($postalCode);

        if (!is_numeric($postalCode)) {
            throw new ImportWarningException('Postal code is not numeric');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $email
     */
    protected function setEmail(VolunteerImport $import, string $email)
    {
        $email = trim($email, ',;');

        $import->setEmail($email);

        if (!$email) {
            throw new ImportWarningException('Email is missing');
        }

        if (!preg_match('/^.+\@\S+\.\S+$/', $email)) {
            throw new ImportWarningException('Email is invalid');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param string          $isCallable
     */
    protected function setIsCallable(VolunteerImport $import, string $isCallable)
    {
        $isCallable = strtolower($isCallable) == 'oui';

        $import->setIsCallable($isCallable);

        if (!$isCallable) {
            throw new ImportErrorException('Volunteer is not callable');
        }
    }

    /**
     * @param VolunteerImport $import
     * @param array           $tags
     */
    protected function setTags(VolunteerImport $import, array $tags)
    {
        $tagsAsBool = [];

        $count = 0;
        foreach ($tags as $tag => $value) {
            $tagsAsBool[$tag] = false;
            if (strtolower($value) == 'oui' || substr(strtolower($value), 0, 4) == 'sold') {
                $tagsAsBool[$tag] = true;
                $count++;
            }
        }

        if ($count == 0) {
            $import->setTags($tagsAsBool);

            throw new ImportErrorException('Volunteer has no tags');
        }

        // Add eventually missing skills on the doc according to the skills hierarchy
        // eg: an ambulance driver is a car driver, a PSE2 holder has PSE1...
        $hierarchy = Tag::getTagHierarchyMap();
        $fullList  = [];
        foreach ($tagsAsBool as $tag => $value) {
            if ($value) {
                $fullList = array_merge($fullList, [$tag], $hierarchy[$tag] ?? []);
            }
        }
        foreach (array_unique($fullList) as $tag) {
            $tagsAsBool[$tag] = true;
        }

        $import->setTags($tagsAsBool);
    }

    /**
     * Tries to sanitize a volunteer field, and stores logs and
     * brokenness if there's an issue.
     *
     * @param VolunteerImport $import
     * @param callable        $callable
     *
     * @return $this
     */
    private function tryDo(VolunteerImport $import, callable $callable)
    {
        try {
            $callable();
        } catch (ImportErrorException $e) {
            $import->addError($e->getMessage());
        } catch (ImportWarningException $e) {
            $import->addWarning($e->getMessage());
        }

        return $this;
    }
}
