<?php

namespace Bundles\SandboxBundle\Command;

use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DummyFixturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('volunteer:fixtures')
            ->setDescription('Generate N volunteers that you can copy/paste on a Google Sheets')
            ->addArgument('n', InputArgument::REQUIRED, 'Number of volunteers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $n = $input->getArgument('n');

        $csv = Writer::createFromString('');
        $csv->setOutputBOM(Reader::BOM_UTF8);
        $csv->setDelimiter(';');

        // Skip the 4 first lines to fit with the production file
        for ($i = 0; $i < 4; $i++) {
            $csv->insertOne([]);
        }

        for ($i = 0; $i < $n; $i++) {
            $lastname  = $this->generateLastname();
            $firstname = $this->generateFirstname();

            $entry = [
                'nivol'       => $this->generateNivol(),
                'lastname'    => $lastname,
                'firstname'   => $firstname,
                'minor'       => rand() % 75 == 0 ? 'Oui' : 'Non',
                'phoneNumber' => $this->generatePhoneNumber(),
                'postalCode'  => $this->generatePostalCode(),
                'email'       => $this->generateEmail($firstname, $lastname),
                'statut'      => sprintf('Bénévole %s', rand() % 5 == 0 ? 'inactif' : 'actif'),
                'us'          => rand() % 2 ? 'Oui' : 'Non',
                'as'          => rand() % 2 ? 'Oui' : 'Non',
                'fgp'         => rand() % 2 ? 'Oui' : 'Non',
                'url'         => 'https://example.com',
            ];

            // Skills (higher they are, fewer we have volunteers)
            for ($j = 0; $j < 9; $j++) {
                $entry[] = rand() % ($j + 2) == 0 ? 'Soldé' : 'NA';
            }

            $entry['callable'] = rand() % 10 == 0 ? 'Non' : 'Oui';

            $csv->insertOne($entry);
        }

        $output->writeln($csv->getContent());
    }

    private function generateEmail(string $firstname, string $lastname): string
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

    private function generatePostalCode(): string
    {
        return sprintf('750%02d', rand() % 20 + 1);
    }

    private function generatePhoneNumber(): string
    {
        return sprintf(
            '0%d %d%d %d%d %d%d %d%d',
            6 + rand() % 2,
            rand() % 10, rand() % 10,
            rand() % 10, rand() % 10,
            rand() % 10, rand() % 10,
            rand() % 10, rand() % 10
        );
    }

    private function generateFirstname(): string
    {
        $names = [
            'Marie',
            'Thomas',
            'Léa',
            'Nicolas',
            'Camille',
            'Maxime',
            'Manon',
            'Quentin',
            'Chloé',
            'Alexandre',
            'Julie',
            'Julien',
            'Sarah',
            'Antoine',
            'Laura',
            'Kevin',
            'Pauline',
            'Clément',
            'Mathilde',
            'Lucas',
            'Marine',
            'Romain',
            'Emma',
            'Pierre',
            'Lucie',
            'Florian',
            'Anaïs',
            'Valentin',
            'Marion',
            'Guillaume',
            'Océane',
            'Hugo',
            'Justine',
            'Théo',
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
            'émilie',
            'Benjamin',
            'Melanie',
            'Mathieu',
            'Inès',
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
            'Noémie',
            'Nathan',
            'Alice',
            'Corentin',
            'Audrey',
            'Léo',
            'Clémence',
            'Axel',
            'Maëva',
            'Thibault',
            'Eva',
            'Simon',
            'Mélissa',
            'Jordan',
            'Amélie',
            'Matthieu',
            'Caroline',
            'Enzo',
            'Celia',
            'Rémi',
            'Elise',
            'Tom',
            'Celine',
            'Aurélien',
            'Margot',
            'Victor',
            'Elisa',
            'Loic',
            'Jade',
            'Sébastien',
            'Fanny',
            'Raphaël',
            'Sophie',
            'David',
            'Romane',
            'Arnaud',
            'Aurélie',
            'Damien',
            'Jeanne',
            'Bastien',
            'Lola',
            'Jonathan',
            'Estelle',
            'Gabriel',
            'Ophélie',
            'Mickael',
            'Laurine',
            'François',
            'Valentine',
            'Mathis',
            'Alexandra',
            'Robin',
            'Laetitia',
            'Martin',
            'Solène',
            'Tristan',
            'Zoé',
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
            'Cécile',
            'Charles',
            'Lou',
            'MARC',
            'Emeline',
            'Erwan',
            'Elsa',
            'Cedric',
            'Laurie',
            'Yann',
            'Nina',
            'Gaetan',
            'Maëlle',
            'JEAN',
            'Jessica',
            'Jérôme',
            'Coline',
            'Cyril',
            'Axelle',
            'Max',
            'Salomé',
            'Steven',
            'Lucile',
            'Mehdi',
            'Laure',
            'Remy',
            'Andréa',
            'William',
            'Eloïse',
            'Olivier',
            'Ambre',
            'Sylvain',
            'Gaelle',
            'Tony',
            'Hélène',
            'Morgan',
            'Clementine',
            'Christopher',
            'Charlène',
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
            'Héloïse',
            'Killian',
            'Marina',
            'Stéphane',
            'Cindy',
            'Dimitri',
            'Ludivine',
            'Antonin',
        ];

        return $names[rand() % count($names)];
    }

    private function generateLastname(): string
    {
        $names = [
            'MARTIN',
            'BERNARD',
            'THOMAS',
            'PETIT',
            'ROBERT',
            'RICHARD',
            'DURAND',
            'DUBOIS',
            'MOREAU',
            'LAURENT',
            'SIMON',
            'MICHEL',
            'LEFEBVRE',
            'LEROY',
            'ROUX',
            'DAVID',
            'BERTRAND',
            'MOREL',
            'FOURNIER',
            'GIRARD',
            'BONNET',
            'DUPONT',
            'LAMBERT',
            'FONTAINE',
            'ROUSSEAU',
            'VINCENT',
            'MULLER',
            'LEFEVRE',
            'FAURE',
            'ANDRE',
            'MERCIER',
            'BLANC',
            'GUERIN',
            'BOYER',
            'GARNIER',
            'CHEVALIER',
            'FRANCOIS',
            'LEGRAND',
            'GAUTHIER',
            'GARCIA',
            'PERRIN',
            'ROBIN',
            'CLEMENT',
            'MORIN',
            'NICOLAS',
            'HENRY',
            'ROUSSEL',
            'MATHIEU',
            'GAUTIER',
            'MASSON',
            'MARCHAND',
            'DUVAL',
            'DENIS',
            'DUMONT',
            'MARIE',
            'LEMAIRE',
            'NOEL',
            'MEYER',
            'DUFOUR',
            'MEUNIER',
            'BRUN',
            'BLANCHARD',
            'GIRAUD',
            'JOLY',
            'RIVIERE',
            'LUCAS',
            'BRUNET',
            'Nombre',
            'GAILLARD',
            'BARBIER',
            'ARNAUD',
            'MARTINEZ',
            'GERARD',
            'ROCHE',
            'RENARD',
            'SCHMITT',
            'ROY',
            'LEROUX',
            'COLIN',
            'VIDAL',
            'CARON',
            'PICARD',
            'ROGER',
            'FABRE',
            'AUBERT',
            'LEMOINE',
            'RENAUD',
            'DUMAS',
            'LACROIX',
            'OLIVIER',
            'PHILIPPE',
            'BOURGEOIS',
            'PIERRE',
            'BENOIT',
            'REY',
            'LECLERC',
            'PAYET',
            'ROLLAND',
            'LECLERCQ',
            'GUILLAUME',
            'LECOMTE',
            'LOPEZ',
            'JEAN',
            'DUPUY',
            'GUILLOT',
            'HUBERT',
            'BERGER',
            'CARPENTIER',
            'SANCHEZ',
            'DUPUIS',
            'MOULIN',
            'LOUIS',
            'DESCHAMPS',
            'HUET',
            'VASSEUR',
            'PEREZ',
            'BOUCHER',
            'FLEURY',
            'ROYER',
            'KLEIN',
            'JACQUET',
            'ADAM',
            'PARIS',
            'POIRIER',
            'MARTY',
            'AUBRY',
            'GUYOT',
            'CARRE',
            'CHARLES',
            'RENAULT',
            'CHARPENTIER',
            'MENARD',
            'MAILLARD',
            'BARON',
            'BERTIN',
            'Nombre',
            'BAILLY',
            'HERVE',
            'SCHNEIDER',
            'FERNANDEZ',
            'LE',
            'COLLET',
            'LEGER',
            'BOUVIER',
            'JULIEN',
            'PREVOST',
            'MILLET',
            'PERROT',
            'DANIEL',
            'LE',
            'COUSIN',
            'GERMAIN',
            'BRETON',
            'BESSON',
            'LANGLOIS',
            'REMY',
            'LE',
            'PELLETIER',
            'LEVEQUE',
            'PERRIER',
            'LEBLANC',
            'BARRE',
            'LEBRUN',
            'MARCHAL',
            'WEBER',
            'MALLET',
            'HAMON',
            'BOULANGER',
            'JACOB',
            'MONNIER',
            'MICHAUD',
            'RODRIGUEZ',
            'GUICHARD',
            'GILLET',
            'ETIENNE',
            'GRONDIN',
            'POULAIN',
            'TESSIER',
            'CHEVALLIER',
            'COLLIN',
            'CHAUVIN',
            'DA',
            'BOUCHET',
            'GAY',
            'LEMAITRE',
            'BENARD',
            'MARECHAL',
            'HUMBERT',
            'REYNAUD',
            'ANTOINE',
            'HOARAU',
            'PERRET',
            'BARTHELEMY',
            'CORDIER',
            'PICHON',
            'LEJEUNE',
            'GILBERT',
            'LAMY',
            'DELAUNAY',
            'PASQUIER',
        ];

        return $names[rand() % count($names)];
    }

    private function generateNivol(): string
    {
        // 11 digits
        $nivol = '';
        for ($i = 0; $i < 11; $i++) {
            $nivol .= chr(ord('0') + rand() % 10);
        }

        // 1 uppercase letter
        $nivol .= chr(ord('A') + rand() % 26);

        return $nivol;
    }
}