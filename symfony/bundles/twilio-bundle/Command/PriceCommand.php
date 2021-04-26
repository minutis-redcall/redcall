<?php

namespace Bundles\TwilioBundle\Command;

use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PriceCommand extends Command
{
    /**
     * @var TwilioMessageManager
     */
    private $messageManager;

    /**
     * @var TwilioCallManager
     */
    private $callManager;

    /**
     * @param TwilioMessageManager $messageManager
     * @param TwilioCallManager    $callManager
     */
    public function __construct(TwilioMessageManager $messageManager, TwilioCallManager $callManager)
    {
        parent::__construct();

        $this->messageManager = $messageManager;
        $this->callManager    = $callManager;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('twilio:price')
            ->setDescription('Fetch missing SMS prices')
            ->addArgument('retry', InputArgument::OPTIONAL, 'Number of retries on Twilio before skipping', 50);
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
        $this->messageManager->fetchPrices(
            $input->getArgument('retry')
        );

        $this->callManager->fetchPrices(
            $input->getArgument('retry')
        );

        return 0;
    }
}
