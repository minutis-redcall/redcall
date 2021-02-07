<?php

namespace Bundles\TwilioBundle\Command;

use App\Entity\Phone;
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
            ->addArgument('from', InputArgument::REQUIRED, 'Phone number or Alphanumeric SenderID')
            ->addArgument('to', InputArgument::REQUIRED, 'Phone number to contact')
            ->addArgument('message', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Message to send');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phone = new Phone();
        $phone->setE164($input->getArgument('phoneNumber'));
        $phone->onChange();

        $this->messageManager->sendMessage(
            $input->getArgument('from'),
            $input->getArgument('to'),
            implode(' ', $input->getArgument('message'))
        );

        return 0;
    }
}
