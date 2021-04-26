<?php

namespace Bundles\TwilioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uuid_idx", columns={"uuid"})
 *     },
 *     indexes={
 *         @ORM\Index(name="sid_idx", columns={"sid"}),
 *         @ORM\Index(name="price_idx", columns={"price"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Bundles\TwilioBundle\Repository\TwilioMessageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TwilioMessage extends BaseTwilio
{
    public const TYPE = 'message';

    public function getType() : string
    {
        return self::TYPE;
    }
}
