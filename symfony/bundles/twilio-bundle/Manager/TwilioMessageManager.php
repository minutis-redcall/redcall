<?php

namespace Bundles\TwilioBundle\Manager;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Repository\TwilioMessageRepository;

class TwilioMessageManager
{
    /**
     * @var TwilioMessageRepository
     */
    private $messageRepository;

    /**
     * @param TwilioMessageRepository $messageRepository
     */
    public function __construct(TwilioMessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * @param string $uuid
     *
     * @return TwilioMessage|null
     */
    public function get(string $uuid): ?TwilioMessage
    {
        return $this->messageRepository->findOneByUuid($uuid);
    }

    /**
     * @param TwilioMessage $outbound
     */
    public function save(TwilioMessage $outbound)
    {
        $this->messageRepository->save($outbound);
    }

    /**
     * @param int $retries
     *
     * @return TwilioMessage[]
     */
    public function findMessagesWithoutPrice(int $retries): array
    {
        return $this->messageRepository->findEntitiesWithoutPrice($retries);
    }
}