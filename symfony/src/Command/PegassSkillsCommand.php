<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Services\Pegass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PegassSkillsCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pegass:skills')
            ->setDescription('List Pegass departments')
            ->addArgument('volunteer-id', InputArgument::REQUIRED, 'Volunteer ID (nivol) to check');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $volunteerId = $input->getArgument('volunteer-id');

        $skills = array_map(function($skill) {
            return $this->get('translator')->trans(sprintf('tag.%s', $skill));
        }, $this->get(Pegass::class)->getVolunteerTags($volunteerId));

        $table = new Table($output);
        $table->setHeaderTitle(sprintf('Skills for #%s', $volunteerId));
        $table->setHeaders(['Skill']);
        $table->addRows($skills);

        $table->render();
    }
}