<?php

namespace Bundles\SandboxBundle\Command;

use App\Base\BaseCommand;
use Bundles\SandboxBundle\Manager\AnonymizeManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnonymizeCommand extends BaseCommand
{
    /**
     * @var AnonymizeManager
     */
    private $anonymizeManager;

    /**
     * @param AnonymizeManager $anonymizeManager
     */
    public function __construct(AnonymizeManager $anonymizeManager)
    {
        parent::__construct();

        $this->anonymizeManager = $anonymizeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('anonymize')
            ->addOption('platform', null, InputOption::VALUE_REQUIRED, 'Platform in which volunteers are stored')
            ->addOption('volunteer', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Volunteer\'s external id to anonymize')
            ->setDescription('Anonymize a specified volunteer, or the whole RedCall database');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($volunteers = $input->getOption('volunteer')) {
            if (null === $input->getOption('platform')) {
                throw new \InvalidArgumentException('Please provide --platform option to anonymize individual volunteers');
            }

            foreach ($volunteers as $externalId) {
                $this->anonymizeManager->anonymizeVolunteer(ltrim($externalId, '0'), $input->getArgument('platform'));
            }
        } else {
            $this->anonymizeManager->anonymizeDatabase();
        }

        return 0;
    }
}