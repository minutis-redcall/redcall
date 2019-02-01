<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Communication\Sender;
use App\Entity\Message;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendMessageCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('send:message')
            ->setDescription('Send the given message')
            ->addArgument('message-id', InputArgument::REQUIRED, 'Message ID');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('Europe/Paris');

        $messageId = $input->getArgument('message-id');
        $message   = $this->getManager(Message::class)->find($messageId);

        if (!$message) {
            $output->writeln(sprintf('<error>Message "%d" not found.</error>', $messageId));

            return 1;
        }

        $this->get(Sender::class)->send($message);
    }
}