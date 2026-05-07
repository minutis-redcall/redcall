<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Index(name: 'campaign_idx', columns: ['campaign_id'])]
#[ORM\Index(name: 'volunteer_idx', columns: ['volunteer_id'])]
#[ORM\UniqueConstraint(name: 'unique_assignment', columns: ['campaign_id', 'volunteer_id', 'group_index'])]
#[ORM\Entity(repositoryClass: \App\Repository\VolunteerGroupRepository::class)]
class VolunteerGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Campaign::class)]
    private $campaign;

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Volunteer::class)]
    private $volunteer;

    #[ORM\Column(type: 'integer')]
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
