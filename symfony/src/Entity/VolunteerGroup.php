<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VolunteerGroupRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="campaign_idx", columns={"campaign_id"}),
 *         @ORM\Index(name="volunteer_idx", columns={"volunteer_id"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_assignment", columns={"campaign_id", "volunteer_id", "group_index"})
 *     }
 * )
 */
class VolunteerGroup
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Campaign")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Volunteer")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $volunteer;

    /**
     * @ORM\Column(type="integer")
     */
    private $groupIndex;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getCampaign() : ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign) : self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getVolunteer() : ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer) : self
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getGroupIndex() : ?int
    {
        return $this->groupIndex;
    }

    public function setGroupIndex(int $groupIndex) : self
    {
        $this->groupIndex = $groupIndex;

        return $this;
    }
}
