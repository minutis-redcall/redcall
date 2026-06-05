<?php

namespace Bundles\TwilioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Index(name: 'sid_idx', columns: ['sid'])]
#[ORM\Index(name: 'price_idx', columns: ['price'])]
#[ORM\UniqueConstraint(name: 'uuid_idx', columns: ['uuid'])]
#[ORM\Entity(repositoryClass: \Bundles\TwilioBundle\Repository\TwilioMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TwilioMessage extends BaseTwilio
{
    public const TYPE = 'message';

    public function getType() : string
    {
        return self::TYPE;
    }
}
