<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\GdprManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnonymizeCommand extends BaseCommand
{
    /**
     * @var GdprManager
     */
    private $gdprManager;

    /**
     * @param GdprManager $gdprManager
     */
    public function __construct(GdprManager $gdprManager)
    {
        parent::__construct();

        $this->gdprManager = $gdprManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('anonymize')
            ->addOption('volunteer', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Volunteer nivol to anonymize')
            ->setDescription('Anonymize a specified volunteer, or the whole RedCall database');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('UTC');

        if ($volunteers = $input->getOption('volunteer')) {
            foreach ($volunteers as $nivol) {
                $this->gdprManager->anonymizeVolunteer(ltrim($nivol, '0'));
            }
        } else {
            // Anonymize everything
            $this->gdprManager->anonymizeDatabase();
        }
    }
}