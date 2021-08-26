<?php

namespace Bundles\SandboxBundle\Manager;

use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use App\Settings;
use Bundles\SettingsBundle\Manager\SettingManager;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpKernel\KernelInterface;

class AnonymizeManager
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var FakeSmsManager
     */
    private $fakeSmsManager;

    /**
     * @var FakeEmailManager
     */
    private $fakeEmailManager;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param VolunteerManager $volunteerManager
     * @param SettingManager   $settingManager
     * @param FakeSmsManager   $fakeSmsManager
     * @param FakeEmailManager $fakeEmailManager
     * @param KernelInterface  $kernel
     */
    public function __construct(VolunteerManager $volunteerManager,
        SettingManager $settingManager,
        FakeSmsManager $fakeSmsManager,
        FakeEmailManager $fakeEmailManager,
        KernelInterface $kernel)
    {
        $this->volunteerManager = $volunteerManager;
        $this->settingManager   = $settingManager;
        $this->fakeSmsManager   = $fakeSmsManager;
        $this->fakeEmailManager = $fakeEmailManager;
        $this->kernel           = $kernel;
    }

    public static function generateFirstname() : string
    {
        $names = [
            'Marie',
            'Thomas',
            'Lea',
            'Nicolas',
            'Camille',
            'Maxime',
            'Manon',
            'Quentin',
            'Chloe',
            'Alexandre',
            'Julie',
            'Julien',
            'Sarah',
            'Antoine',
            'Laura',
            'Kevin',
            'Pauline',
            'Clement',
            'Mathilde',
            'Lucas',
            'Marine',
            'Romain',
            'Emma',
            'Pierre',
            'Lucie',
            'Florian',
            'Anais',
            'Valentin',
            'Marion',
            'Guillaume',
            'Oceane',
            'Hugo',
            'Justine',
            'Theo',
            'Clara',
            'Anthony',
            'Morgane',
            'Jeremy',
            'Lisa',
            'Alexis',
            'Charlotte',
            'Paul',
            'Juliette',
            'Adrien',
            'Emilie',
            'Benjamin',
            'Melanie',
            'Mathieu',
            'Ines',
            'Vincent',
            'Elodie',
            'Arthur',
            'Louise',
            'Alex',
            'Claire',
            'Louis',
            'Amandine',
            'Baptiste',
            'Margaux',
            'Dylan',
            'Noemie',
            'Nathan',
            'Alice',
            'Corentin',
            'Audrey',
            'Leo',
            'Clemence',
            'Axel',
            'Maeva',
            'Thibault',
            'Eva',
            'Simon',
            'Melissa',
            'Jordan',
            'Amelie',
            'Matthieu',
            'Caroline',
            'Enzo',
            'Celia',
            'Remi',
            'Elise',
            'Tom',
            'Celine',
            'Aurelien',
            'Margot',
            'Victor',
            'Elisa',
            'Loic',
            'Jade',
            'Sebastien',
            'Fanny',
            'Raphael',
            'Sophie',
            'David',
            'Romane',
            'Arnaud',
            'Aurelie',
            'Damien',
            'Jeanne',
            'Bastien',
            'Lola',
            'Jonathan',
            'Estelle',
            'Gabriel',
            'Ophelie',
            'Mickael',
            'Laurine',
            'François',
            'Valentine',
            'Mathis',
            'Alexandra',
            'Robin',
            'Laetitia',
            'Martin',
            'Solene',
            'Tristan',
            'Zoe',
            'Dorian',
            'Coralie',
            'Samuel',
            'Alicia',
            'Maxence',
            'Agathe',
            'Benoit',
            'Alexia',
            'Thibaut',
            'Anna',
            'Fabien',
            'Aurore',
            'Jules',
            'Julia',
            'Yanis',
            'Lena',
            'Florent',
            'Cecile',
            'Charles',
            'Lou',
            'Marc',
            'Emeline',
            'Erwan',
            'Elsa',
            'Cedric',
            'Laurie',
            'Yann',
            'Nina',
            'Gaetan',
            'Maelle',
            'JEAN',
            'Jessica',
            'Jerome',
            'Coline',
            'Cyril',
            'Axelle',
            'Max',
            'Salome',
            'Steven',
            'Lucile',
            'Mehdi',
            'Laure',
            'Remy',
            'Andrea',
            'William',
            'Eloise',
            'Olivier',
            'Ambre',
            'Sylvain',
            'Gaelle',
            'Tony',
            'Helene',
            'Morgan',
            'Clementine',
            'Christopher',
            'Charlene',
            'Mael',
            'Sara',
            'Adam',
            'Carla',
            'Laurent',
            'Myriam',
            'Tanguy',
            'Victoria',
            'Xavier',
            'Cassandra',
            'Ludovic',
            'Heloise',
            'Killian',
            'Marina',
            'Stephane',
            'Cindy',
            'Dimitri',
            'Ludivine',
            'Antonin',
        ];

        return $names[rand() % count($names)];
    }

    public static function generateLastname() : string
    {
        $names = [
            'ADAM',
            'ANDRE',
            'ANTOINE',
            'ARNAUD',
            'AUBERT',
            'AUBRY',
            'BAILLY',
            'BARBIER',
            'BARON',
            'BARRE',
            'BARTHELEMY',
            'BENARD',
            'BENOIT',
            'BERGER',
            'BERNARD',
            'BERTIN',
            'BERTRAND',
            'BESSON',
            'BLANC',
            'BLANCHARD',
            'BONNET',
            'BOUCHER',
            'BOUCHET',
            'BOULANGER',
            'BOURGEOIS',
            'BOUVIER',
            'BOYER',
            'BRETON',
            'BRUN',
            'BRUNET',
            'CARLIER',
            'CARON',
            'CARPENTIER',
            'CARRE',
            'CHARLES',
            'CHARPENTIER',
            'CHAUVIN',
            'CHEVALIER',
            'CHEVALLIER',
            'CLEMENT',
            'COLIN',
            'COLLET',
            'COLLIN',
            'CORDIER',
            'COUSIN',
            'DA SILVA',
            'DANIEL',
            'DAVID',
            'DELAUNAY',
            'DENIS',
            'DESCHAMPS',
            'DUBOIS',
            'DUFOUR',
            'DUMAS',
            'DUMONT',
            'DUPONT',
            'DUPUIS',
            'DUPUY',
            'DURAND',
            'DUVAL',
            'ETIENNE',
            'FABRE',
            'FAURE',
            'FERNANDEZ',
            'FLEURY',
            'FONTAINE',
            'FOURNIER',
            'FRANCOIS',
            'GAILLARD',
            'GARCIA',
            'GARNIER',
            'GAUTHIER',
            'GAUTIER',
            'GAY',
            'GERARD',
            'GERMAIN',
            'GILBERT',
            'GILLET',
            'GIRARD',
            'GIRAUD',
            'GRONDIN',
            'GUERIN',
            'GUICHARD',
            'GUILLAUME',
            'GUILLOT',
            'GUYOT',
            'HAMON',
            'HENRY',
            'HERVE',
            'HOARAU',
            'HUBERT',
            'HUET',
            'HUMBERT',
            'JACOB',
            'JACQUET',
            'JEAN',
            'JOLY',
            'JULIEN',
            'KLEIN',
            'LACROIX',
            'LAMBERT',
            'LAMY',
            'LANGLOIS',
            'LAPORTE',
            'LAURENT',
            'LE GALL',
            'LE GOFF',
            'LE ROUX',
            'LEBLANC',
            'LEBRUN',
            'LECLERC',
            'LECLERCQ',
            'LECOMTE',
            'LEFEBVRE',
            'LEFEVRE',
            'LEGER',
            'LEGRAND',
            'LEJEUNE',
            'LEMAIRE',
            'LEMAITRE',
            'LEMOINE',
            'LEROUX',
            'LEROY',
            'LEVEQUE',
            'LOPEZ',
            'LOUIS',
            'LUCAS',
            'MAILLARD',
            'MALLET',
            'MARCHAL',
            'MARCHAND',
            'MARECHAL',
            'MARIE',
            'MARTIN',
            'MARTINEZ',
            'MARTY',
            'MASSON',
            'MATHIEU',
            'MENARD',
            'MERCIER',
            'MEUNIER',
            'MEYER',
            'MICHAUD',
            'MICHEL',
            'MILLET',
            'MONNIER',
            'MOREAU',
            'MOREL',
            'MORIN',
            'MOULIN',
            'MULLER',
            'NICOLAS',
            'NOEL',
            'OLIVIER',
            'PARIS',
            'PASQUIER',
            'PAYET',
            'PELLETIER',
            'PEREZ',
            'PERRET',
            'PERRIER',
            'PERRIN',
            'PERROT',
            'PETIT',
            'PHILIPPE',
            'PICARD',
            'PICHON',
            'PIERRE',
            'POIRIER',
            'POULAIN',
            'PREVOST',
            'REMY',
            'RENARD',
            'RENAUD',
            'RENAULT',
            'REY',
            'REYNAUD',
            'RICHARD',
            'RIVIERE',
            'ROBERT',
            'ROBIN',
            'ROCHE',
            'RODRIGUEZ',
            'ROGER',
            'ROLLAND',
            'ROUSSEAU',
            'ROUSSEL',
            'ROUX',
            'ROY',
            'ROYER',
            'SANCHEZ',
            'SCHMITT',
            'SCHNEIDER',
            'SIMON',
            'TESSIER',
            'THOMAS',
            'VASSEUR',
            'VIDAL',
            'VINCENT',
            'WEBER',
        ];

        return $names[rand() % count($names)];
    }

    public static function generatePhoneNumber() : string
    {
        $phone = sprintf(
            '0%d %d%d %d%d %d%d %d%d',
            6 + rand() % 2,
            rand() % 10, rand() % 10,
            rand() % 10, rand() % 10,
            rand() % 10, rand() % 10,
            rand() % 10, rand() % 10
        );

        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsed    = $phoneUtil->parse($phone, 'FR');

        return $phoneUtil->format($parsed, PhoneNumberFormat::E164);
    }

    public static function generateEmail(string $firstname, string $lastname) : string
    {
        $providers = [
            'gmail.com',
            'yahoo.com',
            'hotmail.com',
            'aol.com',
            'hotmail.fr',
            'msn.com',
            'yahoo.fr',
            'wanadoo.fr',
            'orange.fr',
            'free.fr',
        ];

        return strtolower(sprintf('%s.%s@%s', substr($firstname, 0, 1), $lastname, $providers[rand() % count($providers)]));
    }

    /**
     * Only used for pen-test environments
     */
    public function anonymizeDatabase(string $platform)
    {
        if ('cli' === php_sapi_name()) {
            $this->fakeSmsManager->truncate();
            $this->fakeEmailManager->truncate();

            $this->volunteerManager->foreach(function (Volunteer $volunteer) {
                $this->anonymize($volunteer);
            }, false);
        } elseif (time() - $this->settingManager->get(Settings::SANDBOX_LAST_ANONYMIZE, 0) > 86400) {
            $this->settingManager->set(Settings::SANDBOX_LAST_ANONYMIZE, time());

            // Executing asynchronous task to prevent against interruptions
            $console = sprintf('%s/bin/console', $this->kernel->getProjectDir());
            $command = sprintf('%s anonymize %s', escapeshellarg($console), $platform);
            exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));
        }
    }

    public function anonymizeVolunteer(string $externalId, string $platform)
    {
        $volunteer = $this->volunteerManager->findOneByExternalId($platform, $externalId);
        if ($volunteer) {
            $this->anonymize($volunteer);
        }
    }

    /**
     * Keep volunteer's nivol & skills, anonymize everything else.
     * Volunteer gets automatically locked & disabled.
     *
     * @param Volunteer $volunteer
     */
    private function anonymize(Volunteer $volunteer)
    {
        $volunteer->setFirstName($this->generateFirstname());
        $volunteer->setLastName($this->generateLastname());

        $volunteer->setEmail($this->generateEmail($volunteer->getFirstName(), $volunteer->getLastName()));
        $volunteer->getPhones()->clear();

        if (!$volunteer->getId()) {
            $this->volunteerManager->save($volunteer);
        }

        $phone = new Phone();
        $phone->setVolunteer($volunteer);
        $phone->setE164($this->generatePhoneNumber());
        $phone->setMobile(true);
        $phone->setPreferred(true);
        $volunteer->getPhones()->add($phone);

        $this->volunteerManager->save($volunteer);
    }
}
