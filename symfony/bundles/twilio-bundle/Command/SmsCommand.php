<?php

namespace Bundles\TwilioBundle\Command;

use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SmsCommand extends Command
{
    /**
     * @var TwilioMessageManager
     */
    private $messageManager;

    public function __construct(TwilioMessageManager $messageManager)
    {
        parent::__construct();

        $this->messageManager = $messageManager;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('twilio:sms')
            ->setDescription('Send an SMS to the given phone number')
            ->addArgument('phoneNumber', InputArgument::REQUIRED, 'Phone number to contact')
            ->addArgument('message', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Message to send');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->messageManager->sendMessage(
            $input->getArgument('phoneNumber'),
            implode(' ', $input->getArgument('message'))
        );

        return 0;
    }
}
