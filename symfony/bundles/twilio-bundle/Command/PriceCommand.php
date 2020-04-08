<?php

namespace Bundles\TwilioBundle\Command;

use Bundles\TwilioBundle\Service\Twilio;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PriceCommand extends Command
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
            ->setName('twilio:price')
            ->setDescription('Fetch missing SMS prices')
            ->addArgument('retry', InputArgument::OPTIONAL, 'Number of retries on Twilio before skipping', 48);
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
        $this->twilio->fetchPrices(
            $input->getArgument('retry')
        );
    }
}
