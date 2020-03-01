<?php

namespace Bundles\TwilioBundle\Command;

use Bundles\TwilioBundle\SMS\Twilio;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SmsCommand extends Command
{
    /**
     * @var Twilio
     */
    private $twilio;

    /**
     * @param Twilio $twilio
     */
    public function __construct(Twilio $twilio)
    {
        parent::__construct();

        $this->twilio = $twilio;
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Twilio\Exceptions\ConfigurationException
     * @throws \Twilio\Exceptions\TwilioException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->twilio->send(
            $input->getArgument('phoneNumber'),
            implode(' ', $input->getArgument('message'))
        );
    }
}
